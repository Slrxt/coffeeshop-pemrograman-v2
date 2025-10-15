<?php
session_start();
header('Content-Type: application/json');

// Asumsi config.php ada di assets/php/
include '../php/config.php'; // SESUAIKAN PATH INI

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); 
  echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
  exit;
}

// Ambil input 'nama' (username)
$nama = trim($_POST['nama'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($nama) || empty($password)) {
  echo json_encode(['status' => 'error', 'message' => 'Username dan password wajib diisi.']);
  exit;
}

// 1. Ambil data pengguna dari database berdasarkan NAMA, HANYA untuk permission 'admin'
$stmt = $conn->prepare("SELECT id, nama, password, permission FROM akun WHERE nama = ? AND permission = 'admin' LIMIT 1");

if (!$stmt) {
  echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan statement database.']);
  exit;
}

// Bind parameter menggunakan $nama (username)
$stmt->bind_param("s", $nama);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  // Jika nama tidak ada ATAU permission bukan 'admin'
  echo json_encode(['status' => 'error', 'message' => 'Username atau password salah.']);
  exit;
}

$user = $result->fetch_assoc();
$stmt->close(); 

// 2. Verifikasi Password
if (password_verify($password, $user['password'])) {
  
  // 3. SET SESSION 
  session_regenerate_id(true); 

  $_SESSION['user_id'] = $user['id'];
  $_SESSION['nama'] = $user['nama'];
  $_SESSION['permission'] = $user['permission']; // Menyimpan 'admin'

  // 4. Beri response sukses
  echo json_encode(['status' => 'success', 'redirect' => 'dashboard.php']);
  
} else {
  echo json_encode(['status' => 'error', 'message' => 'Username atau password salah.']);
}
?>