<?php session_start(); 
// Cek jika sudah login sebagai kurir, langsung redirect
if (isset($_SESSION['permission']) && $_SESSION['permission'] === 'kurir') {
    header("Location: dashboard.php"); // Ganti ke dashboard kurir Anda
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/styles.css"> 
  <title>Login Kurir</title>
  <style>
    /* Mengambil style dari login.php pelanggan */
    .login-container {
      display: flex; justify-content: center; align-items: center;
      height: 100vh; background: var(--body-color);
      padding: 0; 
      box-shadow: none; 
      width: 100%; 
      max-width: none; 
      animation: none; 
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

    /* Hapus link register (jika ada di style global, override di sini) */
    .register-link { display: none; }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h2>LOGIN KURIR</h2>
      <div id="error-message" class="error-msg"></div>

      <form action="login_action.php" method="POST" id="login-form">
    <div class="input-group">
        <label for="username">Nama Kurir</label>
        <input type="text" id="username" name="username" 
               placeholder="Masukkan Nama Kurir Anda" required 
               autocomplete="username"> 
               </div>

    <div class="input-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" 
               placeholder="Masukkan Password" required 
               autocomplete="current-password">
    </div>

    <button type="submit" class="button">LOGIN</button>
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
    
    // Kirim data ke login_action.php di direktori ini
    const response = await fetch('login_action.php', {
      method: 'POST',
      body: new FormData(form)
    });

    const data = await response.json();

    if (data.status === 'success') {
      // Redirect ke dashboard kurir setelah login berhasil
      window.location.replace("dashboard.php"); 
    } else {
      errorBox.innerText = data.message || "Login gagal.";
      errorBox.style.display = "block";
    }
  });
  </script>
</body>
</html>