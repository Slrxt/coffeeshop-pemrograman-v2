<?php
session_start();
include "config.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

// VALIDASI DATA INPUT
if (!$data || !isset($data['cart']) || !isset($data['alamat']) || !isset($data['nama_penerima']) || !isset($data['metode'])) {
    echo json_encode(["status" => "error", "message" => "Data pemesanan tidak lengkap (Keranjang, Alamat, Nama Penerima, atau Metode Pembayaran hilang)."]);
    exit;
}

$cart              = $data['cart'];
$alamat            = mysqli_real_escape_string($conn, $data['alamat']);
$nama_penerima     = mysqli_real_escape_string($conn, $data['nama_penerima']);
$metode_pembayaran = mysqli_real_escape_string($conn, $data['metode']); 

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0; 

// PENTING: Validasi nilai ENUM (pastikan hanya 'transfer' atau 'cod')
if (!in_array($metode_pembayaran, ['transfer', 'cod'])) {
    echo json_encode(["status" => "error", "message" => "Metode pembayaran tidak valid."]);
    exit;
}

// 1. HITUNG TOTAL HARGA (SUBTOTAL AWAL)
$total_harga = 0;
foreach ($cart as $item) {
    // Mengasumsikan format harga adalah 'Rp 1.000' atau 'Rp 10.000', dll.
    $harga = floatval(str_replace(['Rp', '.'], '', $item['price'])); 
    $kuantitas = intval($item['quantity']);
    $total_harga += $harga * $kuantitas;
}

// 2. TERAPKAN DISKON 10% (TANPA PPN)
$total_bayar = $total_harga;
if ($isLoggedIn) {
    // Diskon 10% untuk member
    $diskon_amount = $total_harga * 0.10;
    $total_bayar -= $diskon_amount; 
}
// Pembulatan wajib untuk menghindari masalah tipe data float di MySQL
$total_bayar = round($total_bayar);

// 3. SIAPKAN DATA JSON
$items_json = mysqli_real_escape_string($conn, json_encode($cart, JSON_UNESCAPED_SLASHES));

// 4. PROSES INSERT (Mencoba beberapa kali jika ID duplikat)
$order_id_str = '';
$max_retries = 5;
$success = false;

// Tentukan status awal: COD = 'dikirim', Transfer = 'ditunda'
$initial_status = ($metode_pembayaran === 'cod') ? 'siap dikirim' : 'ditunda';

for ($i = 0; $i < $max_retries; $i++) {
    // Buat ID pesanan unik (kodepesan: ORD + uniqid + 5 char random)
    $order_id_str = "ORD" . uniqid() . substr(md5(rand()), 0, 5); 

    // --- A. INSERT ke PEMESANAN ---
    $sql_pesanan = "INSERT INTO pemesanan (user_id, kodepesan, status, waktupesan, harga, alamat, items, nama_penerima, metode)
            VALUES ('$user_id', '$order_id_str', '$initial_status', NOW(), $total_bayar, '$alamat', '$items_json', '$nama_penerima', '$metode_pembayaran')";
            
    if (mysqli_query($conn, $sql_pesanan)) {
        
        // --- B. BERHASIL PEMESANAN: INSERT ke PEMBAYARAN ---
        
        // Tentukan nilai konfirmasicustom
        $konfirmasi_cod_sql = ""; 
        $konfirmasi_value = "";  

        if ($metode_pembayaran === 'cod') {
            $konfirmasi_cod_sql = ", konfirmasicustom";
            $konfirmasi_value = ", NOW()";
        }

        $sql_pembayaran = "INSERT INTO pembayaran (idpes, metode, totalbayar $konfirmasi_cod_sql)
                           VALUES ('$order_id_str', '$metode_pembayaran', $total_bayar $konfirmasi_value)";
                   
        if (mysqli_query($conn, $sql_pembayaran)) {
            // Kedua insert berhasil
            $success = true;
            break; 
        } else {
            // GAGAL INSERT PEMBAYARAN: Lakukan Rollback 
            mysqli_query($conn, "DELETE FROM pemesanan WHERE kodepesan = '$order_id_str'");
            
            // Beri error spesifik
            echo json_encode(["status" => "error", "message" => "Gagal mencatat pembayaran. Error DB: " . mysqli_error($conn)]);
            exit; 
        }

    } else {
        // GAGAL INSERT PEMESANAN
        if (mysqli_errno($conn) == 1062) {
            continue; // Duplikat Kodepesan, coba ID lain
        } else {
            // Error lain, laporkan dan keluar
            echo json_encode(["status" => "error", "message" => "Gagal membuat pesanan. Error DB: " . mysqli_error($conn)]);
            exit;
        }
    }
}

// 5. Cek jika semua percobaan gagal
if (!$success) {
    echo json_encode(["status" => "error", "message" => "Gagal membuat ID pesanan unik setelah beberapa kali percobaan."]);
    exit;
}

// 6. Berhasil: Beri response sukses
echo json_encode([
    "status"   => "success",
    "message"  => "Pesanan berhasil dibuat",
    "order_id" => $order_id_str
]);