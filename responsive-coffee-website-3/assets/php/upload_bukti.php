<?php
session_start();
include "config.php"; // Path ke konfigurasi DB Anda

// 1. Cek Status Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php"); 
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Cek Data POST dan File
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['kodepesan']) || !isset($_FILES['bukti_bayar'])) {
    // Redirect dengan pesan error
    header("Location: profile.php?error=data_tidak_lengkap");
    exit;
}

$kodepesan = $_POST['kodepesan'];
$file = $_FILES['bukti_bayar'];

// 3. Validasi dan Proses File Upload
$target_dir = "../../assets/bukti_pembayaran/"; // Sesuaikan path ini!
$allowed_types = ['image/jpeg', 'image/png'];
$max_size = 2 * 1024 * 1024; // 2 MB

// Error file upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    // Jika file kosong atau ada error lain
    header("Location: bayar.php?id=$kodepesan&error=upload_error");
    exit;
}

// Cek tipe file
if (!in_array($file['type'], $allowed_types)) {
    header("Location: bayar.php?id=$kodepesan&error=tipe_salah");
    exit;
}

// Cek ukuran file
if ($file['size'] > $max_size) {
    header("Location: bayar.php?id=$kodepesan&error=ukuran_terlalu_besar");
    exit;
}

// Buat nama file unik: kodepesan_timestamp.ext
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_file_name = $kodepesan . '_' . time() . '.' . $file_extension;
$target_file = $target_dir . $new_file_name;

// Pindahkan file dari temporary ke folder target
if (move_uploaded_file($file['tmp_name'], $target_file)) {
    
    // 4. Update Database
    // Path relatif yang akan disimpan di DB (contoh: assets/bukti_pembayaran/ORD_xxxx.jpg)
    $db_path = "assets/bukti_pembayaran/" . $new_file_name; 
    
    // Update tabel pembayaran: buktibayar dan konfirmasicustom
    // Memastikan pesanan milik user ini dan metodenya 'transfer'
    $stmt = $conn->prepare("
    UPDATE pembayaran b
    JOIN pemesanan p ON b.idpes = p.kodepesan
    SET 
        b.buktibayar = ?, 
        b.konfirmasicustom = NOW(),
        p.status = 'siap dikirim'  -- BARIS KRUSIAL: Ubah status pesanan
    WHERE b.idpes = ? 
    AND p.user_id = ? 
    AND p.metode = 'transfer'
    AND p.status = 'ditunda'  -- Hanya ubah jika status masih 'ditunda'
    ");

    $stmt->bind_param("ssi", $db_path, $kodepesan, $user_id);

    if ($stmt->execute()) {
        // SUKSES: Redirect ke halaman bayar dengan pesan sukses
        header("Location: bayar.php?id=$kodepesan&status=sukses_upload");
        exit;
    } else {
        // GAGAL UPDATE DB: Hapus file yang baru diupload
        unlink($target_file); 
        header("Location: bayar.php?id=$kodepesan&error=db_update_gagal");
        exit;
    }

} else {
    // GAGAL Pindah File
    header("Location: bayar.php?id=$kodepesan&error=move_file_gagal");
    exit;
}
?>