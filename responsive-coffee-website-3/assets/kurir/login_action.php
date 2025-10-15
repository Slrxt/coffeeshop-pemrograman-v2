<?php
session_start();
header('Content-Type: application/json');
include '../php/config.php'; // Asumsi path ke config.php

// Cek metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
  exit;
}

// 1. Ambil input Nama (bukan Email)
$username = trim($_POST['username'] ?? ''); // Ambil 'username' dari form
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
  echo json_encode(['status' => 'error', 'message' => 'Nama dan password wajib diisi.']);
  exit;
}

// 2. Ambil data kurir dari database berdasarkan NAMA
// Ganti WHERE email = ? menjadi WHERE nama = ?
$stmt = $conn->prepare("SELECT id, nama, password, permission FROM akun WHERE nama = ? AND permission = 'kurir' LIMIT 1");

if (!$stmt) {
  echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan statement database.']);
  exit;
}
$stmt->bind_param("s", $username); // Bind variable 's' (string) untuk nama
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo json_encode(['status' => 'error', 'message' => 'Nama Kurir atau password salah.']);
  exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// 3. Verifikasi Password
if (password_verify($password, $user['password'])) {
  
  // 4. SET SESSION
  session_regenerate_id(true); 

  $_SESSION['kurir_id'] = $user['id']; // Gunakan session khusus untuk kurir
  $_SESSION['nama_kurir'] = $user['nama'];
  $_SESSION['permission'] = 'kurir'; 

  // Berhasil login
  echo json_encode(['status' => 'success', 'redirect' => 'dashboard_kurir.php']); // Sesuaikan redirect
  
} else {
  // Password salah
  echo json_encode(['status' => 'error', 'message' => 'Nama Kurir atau password salah.']);
}
?>