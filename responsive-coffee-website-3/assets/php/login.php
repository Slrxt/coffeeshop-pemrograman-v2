<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/styles.css">
  <title>Login</title>
  <style>
    /* Merubah gaya agar sesuai dengan register.php */
    .login-container {
      /* Mengganti container utama agar menjadi flexbox untuk centering */
      display: flex; justify-content: center; align-items: center;
      height: 100vh; background: var(--body-color); /* Menggunakan variabel warna body */
      padding: 0; /* Menghapus padding container asli */
      box-shadow: none; /* Menghapus shadow container asli */
      width: 100%; /* Menggunakan lebar penuh */
      max-width: none; /* Menghapus batas lebar */
      animation: none; /* Menghapus animasi fadeIn */
    }

    .login-box {
      /* Menambahkan box baru seperti di register.php */
      background: var(--body-white-color);
      padding: 2rem; border-radius: 12px;
      width: 100%; max-width: 400px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.3);
      text-align: center;
    }

    .login-box h2 {
      margin-bottom: 1.5rem; color: var(--title-color);
    }

    /* Mengganti form-input agar sesuai dengan input di register.php */
    .form-input {
      width: 100%; padding: 0.75rem; margin-bottom: 1rem;
      border-radius: 8px; border: 1px solid #ddd;
      outline: none; /* Tambahan untuk memastikan fokus state minimal */
    }

    .form-input:focus {
      border-color: #1abc9c; /* Menggunakan warna yang konsisten atau default focus */
      box-shadow: none; /* Menghilangkan shadow focus yang kompleks */
    }

    /* Memastikan tombol sesuai dengan gaya di register.php */
    .button {
      width: 100%; border-radius: 8px; margin-bottom: 1rem;
    }

    /* Gaya untuk pesan error */
    .error-message {
      color: red; margin-bottom: 1rem; font-size: 0.9rem;
      display: none; /* Pastikan defaultnya tersembunyi */
    }

    /* Mengganti footer-text agar sesuai dengan register.php */
    .footer-text {
      margin-top: 0; /* Menghilangkan margin atas yang besar */
      font-size: 0.9rem;
      color: #555;
    }

    .footer-text a {
      color: #1abc9c;
      text-decoration: none;
      font-weight: 500;
    }

    .footer-text a:hover {
      text-decoration: underline;
    }

  </style>
</head>
<body>

  <div class="login-container">
    <div class="login-box"> <h2>Login</h2>

      <div id="errorBox" class="error-message"></div>

      <form id="loginForm">
        <input type="text" name="email" class="form-input" placeholder="Username" required>
        <input type="password" name="password" class="form-input" placeholder="Password" required>
        <button type="submit" class="button">Login</button>
      </form>

      <p class="footer-text">Belum punya akun? 
        <a href="register.php">Daftar di sini</a>
      </p>
    </div> </div>

  <script>
    document.getElementById("loginForm").addEventListener("submit", async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const res = await fetch("login_action.php", {
        method: "POST",
        body: formData
      });
      const data = await res.json();

      const errorBox = document.getElementById("errorBox");
      errorBox.style.display = "none"; // Sembunyikan pesan error lama

      if (data.status === "success") {
        const cartData = localStorage.getItem("cart");

        if (cartData) {
          try {
            const cartItems = JSON.parse(cartData);

            // Perbaikan Krusial: Mengirimkan data dalam format objek {items: cartItems}
            await fetch("sync_cart.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ items: cartItems }) 
            })
            .then(response => response.json())
            .then(sync_data => {
                if (sync_data.status === 'success') {
                    console.log("Keranjang berhasil disinkronkan.");
                    // Hapus data keranjang dari LocalStorage setelah sukses dikirim ke DB
                    localStorage.removeItem("cart");
                } else {
                    console.error("Gagal sinkronisasi keranjang:", sync_data.message);
                }
            });
          } catch (e) {
            console.error("Gagal parsing LocalStorage cart:", e);
            // Lanjutkan login meskipun gagal sinkronisasi keranjang tamu
          }
        }
        
        // Redirect ke index.php dengan cache busting
        const timestamp = new Date().getTime();
        window.location.replace("../../index.php?t=" + timestamp);
        
      } else {
        errorBox.innerText = data.message || "Login gagal.";
        errorBox.style.display = "block";
      }
    });
  </script>
</body>
</html>