<?php session_start(); 
// Cek jika sudah login sebagai admin, langsung redirect
if (isset($_SESSION['permission']) && $_SESSION['permission'] === 'admin') {
    header("Location: dashboard.php"); // Ganti ke dashboard admin Anda
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/styles.css"> 
  <title>Login Admin</title>
  <style>
    /* Gunakan gaya yang sama dengan login pelanggan/kurir */
    .login-container {
      display: flex; justify-content: center; align-items: center;
      height: 100vh; background: var(--body-color);
    }
    .login-box {
      background: var(--body-white-color);
      padding: 2rem; border-radius: 12px;
      width: 100%; max-width: 400px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.3);
      text-align: center;
    }
    .login-box h2 { margin-bottom: 1.5rem; color: var(--title-color); }
    .login-box input {
      width: 100%; padding: 0.75rem; margin-bottom: 1rem;
      border-radius: 8px; border: 1px solid #ddd;
    }
    .login-box .button { width: 100%; border-radius: 8px; margin-bottom: 1rem; }
    .error-msg { color: red; margin-bottom: 1rem; font-size: 0.9rem; display: none; }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h2>LOGIN ADMINISTRATOR</h2>
      <div id="error-message" class="error-msg"></div>

      <form id="login-form">
        <input type="text" name="nama" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <button type="submit" class="button">Masuk</button>
      </form>
    </div>
  </div>

  <script>
  document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const errorBox = document.getElementById('error-message');
    errorBox.style.display = "none";
    errorBox.innerText = "";
    
    // Kirim data ke login_action.php
    const response = await fetch('login_action.php', {
      method: 'POST',
      body: new FormData(form)
    });

    const data = await response.json();

    if (data.status === 'success') {
      window.location.replace("dashboard.php"); 
    } else {
      errorBox.innerText = data.message || "Login gagal.";
      errorBox.style.display = "block";
    }
  });
  </script>
</body>
</html>