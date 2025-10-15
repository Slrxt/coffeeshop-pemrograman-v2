<?php
// Pastikan session dimulai di baris paling atas
session_start();

// Menggunakan header JSON di awal
header('Content-Type: application/json');

// Asumsi 'config.php' sudah ada di direktori yang sama
include 'config.php';

// Cek apakah request menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); // Metode Tidak Diizinkan
  echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
  exit;
}

// Ambil dan bersihkan data input
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
  echo json_encode(['status' => 'error', 'message' => 'Email dan password wajib diisi.']);
  exit;
}

// 1. Ambil data pengguna dari database berdasarkan email
$stmt = $conn->prepare("SELECT id, nama, password, permission FROM akun WHERE email = ? AND permission = 'pelanggan' LIMIT 1");
if (!$stmt) {
  echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan statement database.']);
  exit;
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo json_encode(['status' => 'error', 'message' => 'Email atau password salah.']);
  exit;
}

$user = $result->fetch_assoc();
$stmt->close(); // Tutup statement

// 2. Verifikasi Password
if (password_verify($password, $user['password'])) {
  
  // 3. SET SESSION (Ini adalah bagian terpenting)
  // Buat ulang ID sesi untuk mencegah Session Fixation Attack (Best Practice)
  session_regenerate_id(true); 

  $_SESSION['user_id'] = $user['id'];
  $_SESSION['nama'] = $user['nama'];
  $_SESSION['permission'] = $user['permission'];

  // Kirim respon sukses
  echo json_encode([
    'status' => 'success',
    'message' => 'Login berhasil!'
  ]);
  exit;

} else {
  // Password salah
  echo json_encode([
    'status' => 'error',
    'message' => 'Email atau password salah.'
  ]);
  exit;
}
?>