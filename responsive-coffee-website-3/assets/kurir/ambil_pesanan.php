<?php
// AMBIL_PESANAN.PHP

// Opsional: Matikan error reporting agar tidak merusak JSON (HANYA UNTUK DEBUG/LIVE)
// ini_set('display_errors', 0); 

session_start();
header('Content-Type: application/json');

// Pastikan path ke config.php benar (Relatif ke assets/kurir/)
include '../php/config.php'; 

// 1. Cek Login dan Akses
if (!isset($_SESSION['kurir_id']) || $_SESSION['permission'] !== 'kurir') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir atau akses ditolak.']);
    exit;
}

// 2. Cek Metode POST dan Data Input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$kurir_id = $_SESSION['kurir_id'];
$order_id = trim($_POST['kodepesan'] ?? '');

if (empty($order_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode pesanan tidak valid.']);
    exit;
}

// 3. Cek Status Pesanan Saat Ini
$stmt_check = $conn->prepare("SELECT status FROM pemesanan WHERE kodepesan = ?");
$stmt_check->bind_param("s", $order_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Pesanan tidak ditemukan.']);
    exit;
}
$order = $result_check->fetch_assoc();
$stmt_check->close();

// Status harus sama dengan yang di-query di dashboard.php: 'siap dikirim'
$allowed_status = ['siap dikirim']; 

if (!in_array(strtolower($order['status']), $allowed_status)) {
    echo json_encode(['status' => 'error', 'message' => 'Pesanan tidak dapat diambil karena status: ' . $order['status']]);
    exit;
}

// 4. Update status pesanan dan catat ID Kurir
$new_status = 'Dalam Pengiriman'; 

$stmt_update = $conn->prepare("UPDATE pemesanan SET status = ?, kurir_id = ? WHERE kodepesan = ?");

if (!$stmt_update) {
    // Menangkap error jika prepared statement gagal
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan query update.']);
    exit;
}

$stmt_update->bind_param("sis", $new_status, $kurir_id, $order_id);

if ($stmt_update->execute()) {
    $stmt_update->close();
    echo json_encode([
        'status' => 'success', 
        'message' => 'Pesanan ' . $order_id . ' berhasil diambil. Status: ' . $new_status,
        'new_status' => $new_status
    ]);
} else {
    $stmt_update->close();
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate status pesanan di database.']);
}

$conn->close();
?>