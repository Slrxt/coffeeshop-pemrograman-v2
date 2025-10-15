<?php
session_start();
include "config.php";

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// 1. Cek Status Login
if (!$isLoggedIn) {
    // Jika belum login, redirect ke halaman login (sesuaikan path)
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_data = null;
$orders = [];

// 2. Ambil Data Pribadi Pengguna
$stmt_user = $conn->prepare("SELECT nama, email FROM akun WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
} else {
    // Seharusnya tidak terjadi, tapi sebagai penanganan darurat
    session_destroy();
    header("Location: ../../login.php");
    exit;
}

// 3. Ambil Riwayat Pesanan
// Catatan: Asumsi Anda memiliki kolom 'user_id' di tabel 'pemesanan'
$stmt_orders = $conn->prepare("SELECT kodepesan, waktupesan, harga, status, metode FROM pemesanan WHERE user_id = ? ORDER BY waktupesan DESC");
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();

while ($row = $result_orders->fetch_assoc()) {
    $orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <style>
        /* Gaya Kustom untuk Halaman Profil */
        .profile-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: 4rem 1rem;
            min-height: calc(100vh - 100px);
        }
        .profile-section {
            background-color: var(--body-white-color);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .profile-title {
            font-size: var(--h2-font-size);
            color: var(--title-color);
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--first-color);
            padding-bottom: 0.5rem;
        }
        .profile-data p {
            font-size: var(--normal-font-size);
            margin-bottom: 0.75rem;
            color: var(--text-color);
        }
        .profile-data strong {
            color: var(--title-color);
            min-width: 120px;
            display: inline-block;
        }

        /* Gaya untuk Daftar Pesanan */
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .order-table th, .order-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: var(--small-font-size);
        }
        .order-table th {
            background-color: var(--first-color-alt);
            color: var(--white-color);
            font-weight: var(--font-semi-bold);
            text-transform: uppercase;
        }
        .order-status-ditunda { color: orange; font-weight: var(--font-semi-bold); }
        .order-status-proses { color: blue; font-weight: var(--font-semi-bold); }
        .order-status-selesai { color: var(--first-color); font-weight: var(--font-semi-bold); }
        .order-status-batal { color: red; font-weight: var(--font-semi-bold); }
    </style>
</head>
<body>
    <header class="header" id="header">
      <nav class="nav container">
        <a href="../../index.php" class="nav__logo">STARCOFFEE</a>
        <div class="nav__menu" id="nav-menu">
          <ul class="nav__list">
            <li><a href="../../index.php" class="nav__link">Beranda</a></li>
            <?php if(!$isLoggedIn): ?>
              <li><a href="login.php" class="nav__link">Login</a></li>
            <?php else: ?>
              <li><a href="logout.php" class="nav__link">Logout</a></li>
            <?php endif; ?>
          </ul>
        </div>
        <div class="nav__toggle" id="nav-toggle"><i class="ri-menu-line"></i></div>
      </nav>
    </header>

    <main class="main">
        <div class="profile-wrapper">
            
            <div class="profile-section">
                <h2 class="profile-title">Data Pribadi</h2>
                <div id="profile-status" style="margin-bottom: 1rem; font-weight: bold;"></div>

                <?php if ($user_data): ?>
                <div class="profile-data">
                    <p><strong>Username:</strong> <span id="current-nama"><?php echo htmlspecialchars($user_data['nama']); ?></span></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
                
                <hr style="border: 0; height: 1px; background-color: #ddd; margin: 1.5rem 0;"> 

                <h3 style="font-size: var(--h3-font-size); margin-top: 2rem; margin-bottom: 1rem; color: var(--title-color);">Ganti Username</h3>
                <form id="updateNameForm">
                    <input type="text" name="nama_baru" placeholder="Username Baru" required 
                           style="width: 100%; padding: 0.75rem; margin-bottom: 1rem; border-radius: 8px; border: 1px solid #ddd;"
                           value="<?php echo htmlspecialchars($user_data['nama']); ?>">
                    <button type="submit" class="button button--small" style="width: 100px;">Simpan</button>
                    <div id="name-error" class="error-msg" style="color: red; margin-top: 0.5rem;"></div>
                </form>

                <h3 style="font-size: var(--h3-font-size); margin-top: 3rem; margin-bottom: 1rem; color: var(--title-color);">Ganti Password</h3>
                <form id="updatePasswordForm">
                    <input type="password" name="password_lama" placeholder="Password Lama" required 
                           style="width: 100%; padding: 0.75rem; margin-bottom: 1rem; border-radius: 8px; border: 1px solid #ddd;">
                    <input type="password" name="password_baru" placeholder="Password Baru" required 
                           style="width: 100%; padding: 0.75rem; margin-bottom: 1rem; border-radius: 8px; border: 1px solid #ddd;">
                    <input type="password" name="konfirmasi_password" placeholder="Konfirmasi Password Baru" required 
                           style="width: 100%; padding: 0.75rem; margin-bottom: 1rem; border-radius: 8px; border: 1px solid #ddd;">
                    <button type="submit" class="button button--small" style="width: 100px;">Ganti</button>
                    <div id="password-error" class="error-msg" style="color: red; margin-top: 0.5rem;"></div>
                </form>

                <?php endif; ?>
            </div>

            <div class="profile-section">
                <h2 class="profile-title">Riwayat Pesanan</h2>
                <?php if (!empty($orders)): ?>
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Kode Pesanan</th>
                                <th>Tanggal Pesan</th>
                                <th>Total Bayar</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['kodepesan']); ?></td>
                                <td><?php echo date("d M Y H:i", strtotime($order['waktupesan'])); ?></td>
                                <td>Rp <?php echo number_format($order['harga'], 0, ',', '.'); ?></td>
                                <td><?php echo strtoupper(htmlspecialchars($order['metode'])); ?></td>
                                <td>
                                    <span class="order-status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_detail.php?id=<?php echo htmlspecialchars($order['kodepesan']); ?>" class="button button--small" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        Lihat Detail
                                    </a>
                                    <a href="bayar.php?id=<?php echo urlencode($order['kodepesan']); ?>" 
                                        class="button button--small" 
                                        style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        Pembayaran
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-color);">Anda belum memiliki riwayat pesanan.</p>
                <?php endif; ?>

                <?php
                // Tampilkan pesan transfer jika ada pesanan yang 'ditunda'
                $has_pending_transfer = false;
                foreach ($orders as $order) {
                    if (strtolower($order['metode']) == 'transfer' && strtolower($order['status']) == 'ditunda') {
                        $has_pending_transfer = true;
                        break;
                    }
                }
                if ($has_pending_transfer):
                ?>
                    <div style="margin-top: 2rem; padding: 1rem; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px;">
                        <p style="color: #856404; font-weight: var(--font-semi-bold);">
                            ⚠️ Perhatian: Anda memiliki pesanan dengan metode Transfer yang masih **Ditunda** (Belum Dibayar).
                            Segera lakukan transfer ke **XX-XX-XX-XX** dan konfirmasi pembayaran agar pesanan diproses.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
<script>
        document.addEventListener('DOMContentLoaded', () => {
            const updateNameForm = document.getElementById('updateNameForm');
            const updatePasswordForm = document.getElementById('updatePasswordForm');
            const nameError = document.getElementById('name-error');
            const passwordError = document.getElementById('password-error');
            const profileStatus = document.getElementById('profile-status');
            const currentNama = document.getElementById('current-nama');

            function showStatus(message, isSuccess = true) {
                profileStatus.textContent = message;
                profileStatus.style.color = isSuccess ? 'var(--first-color)' : 'red';
                setTimeout(() => {
                    profileStatus.textContent = '';
                }, 4000);
            }

            // Aksi Ganti Nama
            updateNameForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                nameError.textContent = '';
                const formData = new FormData(updateNameForm);
                formData.append('action', 'update_name');

                const res = await fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.status === 'success') {
                    showStatus('Username berhasil diubah!', true);
                    currentNama.textContent = formData.get('nama_baru');
                    // Reset input nama
                    updateNameForm.reset();
                    updateNameForm.querySelector('input[name="nama_baru"]').value = data.new_name;

                } else {
                    nameError.textContent = data.message;
                    showStatus('Gagal mengubah Username.', false);
                }
            });

            // Aksi Ganti Password
            updatePasswordForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                passwordError.textContent = '';
                const formData = new FormData(updatePasswordForm);
                formData.append('action', 'update_password');

                if (formData.get('password_baru') !== formData.get('konfirmasi_password')) {
                    passwordError.textContent = 'Password baru dan konfirmasi tidak cocok.';
                    return;
                }

                const res = await fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.status === 'success') {
                    showStatus('Password berhasil diubah!', true);
                    updatePasswordForm.reset();
                } else {
                    passwordError.textContent = data.message;
                    showStatus('Gagal mengubah Password.', false);
                }
            });
        });
    </script>
    </body>
</html>