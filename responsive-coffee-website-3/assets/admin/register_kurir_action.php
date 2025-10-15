<?php
// PASTIKAN BARIS INI BENAR-BENAR BARIS PERTAMA.
session_start();
header('Content-Type: application/json');

// PERIKSA PATH INI! (Sangat penting untuk mencegah output non-JSON)
include "../php/config.php"; 

// Cek Permission (Admin)
if (!isset($_SESSION['permission']) || $_SESSION['permission'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya Admin yang diizinkan.']);
    exit;
}

// --- FUNGSI GENERATE KURIR ID ---
function generateKurirId($conn) {
    do {
        $random_number = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $generated_kurir_id = 'JK' . $random_number; 
        
        $check = $conn->prepare("SELECT id FROM akun WHERE kurir_id = ?"); 
        $check->bind_param("s", $generated_kurir_id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);
    
    return $generated_kurir_id;
}
// ---------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama     = trim($_POST["nama"] ?? '');
    $email    = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $password_confirm = $_POST["password_confirm"] ?? '';

    // LENGKAPI VALIDASI INPUT KRUSIAL DI SINI
    if (empty($nama) || empty($email) || empty($password) || empty($password_confirm)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi.']);
        exit;
    }
    
    // Validasi Password Cocok
    if ($password !== $password_confirm) {
        echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password tidak cocok.']);
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Cek apakah username atau email sudah ada
    $check = $conn->prepare("SELECT id FROM akun WHERE nama = ? OR email = ?");
    $check->bind_param("ss", $nama, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username atau Email sudah digunakan!']);
        exit;
    } else {
        
        $kurir_id = generateKurirId($conn);
        
        // Insert Akun Kurir
        $stmt = $conn->prepare("
            INSERT INTO akun (nama, email, password, permission, kurir_id) 
            VALUES (?, ?, ?, 'kurir', ?)
        ");
        $stmt->bind_param("ssss", $nama, $email, $hashed_password, $kurir_id); 

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'kurir_id' => $kurir_id]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftarkan kurir. Error DB: ' . $conn->error]);
            exit;
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
}

// PASTIKAN TIDAK ADA KARAKTER ATAU SPASI SETELAH BARIS INI
?>