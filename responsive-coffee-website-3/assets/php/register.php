<?php
include "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama     = trim($_POST["nama"]); // username
    $email    = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // 1. Cek apakah username sudah ada
    $check_nama = $conn->prepare("SELECT id FROM akun WHERE nama = ?");
    $check_nama->bind_param("s", $nama);
    $check_nama->execute();
    $check_nama->store_result();

    if ($check_nama->num_rows > 0) {
        $error = "Username sudah digunakan!";
    } else {
        // 2. BARIS BARU: Cek apakah email sudah ada
        $check_email = $conn->prepare("SELECT id FROM akun WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();

        if ($check_email->num_rows > 0) {
            $error = "Email sudah digunakan!"; // Pesan error baru
        } else {
            // Jika nama dan email belum digunakan, lakukan pendaftaran
            $stmt = $conn->prepare("INSERT INTO akun (nama, email, password, permission) VALUES (?, ?, ?, 'pelanggan')");
            $stmt->bind_param("sss", $nama, $email, $password);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $error = "Gagal mendaftar, coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .register-container {
      display: flex; justify-content: center; align-items: center;
      height: 100vh; background: var(--body-color);
    }
    .register-box {
      background: var(--body-white-color);
      padding: 2rem; border-radius: 12px;
      width: 100%; max-width: 400px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.3);
      text-align: center;
    }
    .register-box h2 { margin-bottom: 1.5rem; color: var(--title-color); }
    .register-box input {
      width: 100%; padding: 0.75rem; margin-bottom: 1rem;
      border-radius: 8px; border: 1px solid #ddd;
    }
    .register-box .button { width: 100%; border-radius: 8px; margin-bottom: 1rem; }
    .error-msg { color: red; margin-bottom: 1rem; font-size: 0.9rem; }
  </style>
</head>
<body>
  <div class="register-container">
    <div class="register-box">
      <h2>Daftar</h2>
      <?php if ($error): ?>
        <div class="error-msg"><?= $error ?></div>
      <?php endif; ?>
      <form method="POST" action="">
        <input type="text" name="nama" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="button">Register</button>
      </form>
      <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
  </div>
</body>
</html>
