<?php
session_start();
// Path ke config.php (asumsi berada di ../php/config.php)
include "../php/config.php"; 

// PENTING: Cek Permission (Pastikan hanya Admin yang bisa mengakses)
if (!isset($_SESSION['permission']) || $_SESSION['permission'] !== 'admin') {
    // Redirect ke halaman login admin jika bukan admin
    header("Location: ../../login.php"); 
    exit;
}

$admin_name = htmlspecialchars($_SESSION['nama'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Kurir</title>
  <link rel="stylesheet" href="../css/styles.css"> 
  <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
  <style>
    /* Gaya meniru register.php */
    .register-container {
      display: flex; justify-content: center; align-items: center;
      min-height: 100vh; background: var(--body-color);
    }
    .register-box {
      background: var(--body-white-color);
      padding: 2rem; border-radius: 12px;
      width: 100%; max-width: 400px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.3);
      text-align: center;
    }
    .register-box h2 { 
        margin-bottom: 1.5rem; 
        color: var(--title-color); 
        border-bottom: 2px solid var(--first-color);
        padding-bottom: 10px;
    }
    .register-box input {
      width: 100%; padding: 0.75rem; margin-bottom: 1rem;
      border-radius: 8px; border: 1px solid #ddd;
    }
    .register-box .button { 
        width: 100%; 
        border-radius: 8px; 
        margin-bottom: 1rem;
        background-color: var(--first-color-alt); 
    }
    .error-msg { color: red; margin-bottom: 1rem; font-size: 0.9rem; display: none; }

    /* Gaya Modal (Ambil dari checkout.php) */
    .modal {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.7); display: none;
        justify-content: center; align-items: center; z-index: 1000;
    }
    .modal.active { display: flex; }
    .modal-content {
        background-color: white; padding: 30px; border-radius: 10px;
        width: 90%; max-width: 400px; text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    .modal-content i {
        font-size: 3rem; margin-bottom: 15px; 
        color: var(--first-color);
    }
    .modal-content .modal-error-icon { color: red; }

  </style>
</head>
<body>
  <div class="register-container">
    <div class="register-box">
      <h2>Daftar Kurir Baru</h2>
      <a href="dashboard.php" style="color: var(--first-color); margin-bottom: 1rem; display: block;">&larr; Kembali ke Dashboard</a>

      <p id="form-error-message" class="error-msg"></p>

      <form id="register-kurir-form">
        <input type="text" name="nama" placeholder="Username (Nama Panggilan)" required>
        <input type="email" name="email" placeholder="Email Kurir" required>
        <input type="password" name="password" placeholder="Password Awal" required>
        <input type="password" name="password_confirm" placeholder="Konfirmasi Password" required>
        
        <button type="submit" class="button">Daftarkan Kurir</button>
      </form>
      
    </div>
  </div>

  <div id="statusModal" class="modal">
    <div class="modal-content">
        <i id="modalIcon" class="ri-check-circle-line"></i>
        <h3 id="modalTitle" style="color: var(--title-color);">Status Pendaftaran</h3>
        <p id="modalMessage" style="margin-bottom: 20px; color: var(--text-color);">
            Pesan status akan ditampilkan di sini.
        </p>
        <button id="modalCloseBtn" class="button">OK</button>
    </div>
  </div>

<script>
document.getElementById('register-kurir-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = e.target;
    const errorBox = document.getElementById('form-error-message');
    const modal = document.getElementById('statusModal');
    const modalIcon = document.getElementById('modalIcon');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalCloseBtn = document.getElementById('modalCloseBtn');

    errorBox.style.display = "none";
    errorBox.innerText = "";
    
    // Kirim data ke register_kurir_action.php
    try {
        const response = await fetch('register_kurir_action.php', {
            method: 'POST',
            // Gunakan FormData untuk mengirim data form
            body: new FormData(form) 
        });

        const data = await response.json();

        if (data.status === 'success') {
            // Tampilkan Modal Sukses
            modalIcon.className = 'ri-check-circle-line';
            modalIcon.style.color = 'var(--first-color)';
            modalTitle.textContent = 'Pendaftaran Berhasil!';
            // Tampilkan kurir_id yang baru dibuat
            modalMessage.innerHTML = `Kurir baru dengan ID **${data.kurir_id}** telah berhasil didaftarkan.`;
            modal.classList.add('active');
            
            // Bersihkan form
            form.reset();

        } else {
            // Tampilkan Modal Gagal/Error
            modalIcon.className = 'ri-close-circle-line modal-error-icon';
            modalIcon.style.color = 'red';
            modalTitle.textContent = 'Pendaftaran Gagal';
            modalMessage.innerHTML = `Terjadi kesalahan: ${data.message}`;
            modal.classList.add('active');
        }
    } catch (error) {
        // Error jaringan atau server tidak merespons JSON
        modalIcon.className = 'ri-error-warning-line modal-error-icon';
        modalIcon.style.color = 'orange';
        modalTitle.textContent = 'Kesalahan Koneksi';
        modalMessage.innerHTML = `Gagal menghubungi server. Cek koneksi Anda atau file **register_kurir_action.php**.`;
        modal.classList.add('active');
        console.error("Fetch Error:", error);
    }
    
    // Setup event listener untuk tombol OK di modal
    // Setelah OK ditekan, modal ditutup
    modalCloseBtn.onclick = () => {
        modal.classList.remove('active');
    };
});
</script>
</body>
</html>