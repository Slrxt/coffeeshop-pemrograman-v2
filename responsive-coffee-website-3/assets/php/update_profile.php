<?php
session_start();
include "config.php";

header('Content-Type: application/json');

// Cek status login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Anda harus login."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// --- FUNGSI UTAMA: UPDATE NAMA (USERNAME) ---
if ($action === 'update_name') {
    $new_name = trim($_POST['nama_baru'] ?? '');

    if (empty($new_name)) {
        echo json_encode(["status" => "error", "message" => "Username baru tidak boleh kosong."]);
        exit;
    }
    
    // 1. Cek apakah username baru sudah digunakan oleh orang lain
    $check_stmt = $conn->prepare("SELECT id FROM akun WHERE nama = ? AND id != ?");
    $check_stmt->bind_param("si", $new_name, $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username ini sudah digunakan."]);
        exit;
    }

    // 2. Update nama
    $update_stmt = $conn->prepare("UPDATE akun SET nama = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_name, $user_id);

    if ($update_stmt->execute()) {
        // Update session juga
        $_SESSION['nama'] = $new_name;
        echo json_encode(["status" => "success", "message" => "Username berhasil diubah.", "new_name" => $new_name]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal memperbarui username di database."]);
    }
    exit;
}

// --- FUNGSI UTAMA: UPDATE PASSWORD ---
if ($action === 'update_password') {
    $old_password = $_POST['password_lama'] ?? '';
    $new_password = $_POST['password_baru'] ?? '';
    $confirm_password = $_POST['konfirmasi_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(["status" => "error", "message" => "Semua field password wajib diisi."]);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(["status" => "error", "message" => "Password baru dan konfirmasi tidak cocok."]);
        exit;
    }
    
    if (strlen($new_password) < 6) {
        echo json_encode(["status" => "error", "message" => "Password baru minimal 6 karakter."]);
        exit;
    }

    // 1. Ambil password hash lama
    $stmt = $conn->prepare("SELECT password FROM akun WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($old_password, $user['password'])) {
        echo json_encode(["status" => "error", "message" => "Password lama salah."]);
        exit;
    }

    // 2. Update password baru
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE akun SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $user_id);

    if ($update_stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Password berhasil diubah."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal memperbarui password di database."]);
    }
    exit;
}

echo json_encode(["status" => "error", "message" => "Aksi tidak dikenal."]);
?>