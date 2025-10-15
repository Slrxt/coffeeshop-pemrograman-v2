<?php
session_start();
header('Content-Type: application/json');

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk sinkronisasi keranjang.']);
    exit;
}

// Sertakan konfigurasi database
include 'config.php';

$user_id = $_SESSION['user_id'];

// Ambil data JSON mentah dari request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Cek apakah data valid dan keranjang LocalStorage tidak kosong
if (empty($data) || !isset($data['items']) || !is_array($data['items'])) {
    // Jika tidak ada data LocalStorage, anggap sinkronisasi selesai
    echo json_encode(['status' => 'success', 'message' => 'Tidak ada keranjang tamu untuk disinkronkan.']);
    exit;
}

$cartItems = $data['items'];

// Mulai proses sinkronisasi ke tabel 'cart'
try {
    // Siapkan statement untuk Upsert (Insert atau Update)
    // Asumsi tabel 'cart' memiliki kolom: user_id, produk_id, quantity
    // Serta PRIMARY KEY atau UNIQUE KEY gabungan pada (user_id, produk_id)
    $query = "INSERT INTO cart (user_id, produk_id, quantity) 
              VALUES (?, ?, ?) 
              ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
         throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Looping melalui item dari LocalStorage
    foreach ($cartItems as $produk_id => $quantity) {
        // Pastikan product_id adalah integer dan quantity adalah integer > 0
        $pid = intval($produk_id);
        $qty = intval($quantity);
        
        if ($qty > 0) {
            $stmt->bind_param("iii", $user_id, $pid, $qty);
            $stmt->execute();
        }
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Keranjang berhasil disinkronkan ke akun.']);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Cart Sync Error: " . $e->getMessage()); // Catat error ke log server
    echo json_encode(['status' => 'error', 'message' => 'Sinkronisasi gagal, coba lagi nanti.']);
}

$conn->close();
?>