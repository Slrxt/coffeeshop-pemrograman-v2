<?php
session_start();
include "config.php"; // Pastikan path ini benar

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Cek status login
if (!$isLoggedIn) {
    // Sesuaikan path login. Di order_detail.php Anda menggunakan "login.php"
    header("Location: login.php"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$kodepesan = $_GET['id'] ?? '';
$data_pesanan = null; // Akan menyimpan hasil JOIN

if (empty($kodepesan)) {
    // Jika tidak ada ID pesanan, redirect kembali ke profil
    header("Location: profile.php");
    exit;
}

// 1. Ambil Data Pesanan dan Pembayaran menggunakan JOIN
// JOIN antara tabel pemesanan (p) dan pembayaran (b)
$stmt = $conn->prepare("
    SELECT 
        p.kodepesan, p.waktupesan, p.harga AS total_pesanan, p.status, p.alamat, p.nama_penerima, p.metode,
        b.totalbayar, b.konfirmasicustom, b.konfirmasiseller
    FROM pemesanan p
    LEFT JOIN pembayaran b ON p.kodepesan = b.idpes
    WHERE p.kodepesan = ? AND p.user_id = ?
");
$stmt->bind_param("si", $kodepesan, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data_pesanan = $result->fetch_assoc();
} else {
    // Pesanan tidak ditemukan atau bukan milik user
    header("Location: profile.php");
    exit;
}

// Gunakan total_pesanan dari pemesanan jika totalbayar dari pembayaran kosong (seharusnya tidak terjadi jika insert berhasil)
$total_akhir = $data_pesanan['totalbayar'] ?? $data_pesanan['total_pesanan'];
?> 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pembayaran <?php echo htmlspecialchars($kodepesan); ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <style>
        /* Gaya Kustom dari order_detail.php */
        .detail-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 4rem 1rem;
        }
        .detail-section {
            background-color: var(--body-white-color);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .detail-title {
            font-size: var(--h2-font-size);
            color: var(--title-color);
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--first-color);
            padding-bottom: 0.5rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-box {
            background-color: #f7f7f7;
            padding: 1rem;
            border-radius: 8px;
        }
        .info-box p { margin-bottom: 0.5rem; font-size: var(--small-font-size); }
        .info-box strong { color: var(--title-color); }
        
        /* Tambahan untuk status/info pembayaran */
        .status-badge {
            display: inline-block; padding: 0.3rem 0.6rem; border-radius: 4px;
            font-weight: var(--font-semi-bold); font-size: var(--small-font-size);
        }
        .status-badge.cod { background-color: var(--first-color); color: white; }
        .status-badge.transfer { background-color: #007bff; color: white; }
        .status-badge.pending { background-color: #ffc107; color: #343a40; }
        .status-badge.confirmed { background-color: #28a745; color: white; }
        
        .payment-status-box {
             border: 1px solid #ddd;
             padding: 1.5rem;
             border-radius: 8px;
             margin-top: 1.5rem;
        }
        .payment-status-box p {
            margin-bottom: 0.8rem;
        }
    </style>
</head>
<body>
    <header class="header" id="header">
      <nav class="nav container">
        <a href="../../index.php" class="nav__logo">STARCOFFEE</a>
        <div class="nav__menu" id="nav-menu">
          <ul class="nav__list">
            <li><a href="../../index.php" class="nav__link">Beranda</a></li>
            <li><a href="profile.php" class="nav__link">Profil</a></li>
            <li><a href="logout.php" class="nav__link">Logout</a></li>
          </ul>
        </div>
        <div class="nav__toggle" id="nav-toggle"><i class="ri-menu-line"></i></div>
      </nav>
    </header>

    <main class="main">
        <div class="detail-wrapper">
            <a href="profile.php" style="display: inline-block; margin-bottom: 1.5rem; color: var(--first-color); font-weight: var(--font-semi-bold);"><i class="ri-arrow-left-line"></i> Kembali ke Riwayat</a>
            
            <div class="detail-section">
                <h1 class="detail-title">Detail Pembayaran #<?php echo htmlspecialchars($data_pesanan['kodepesan']); ?></h1>
                
                <div class="info-grid">
                    <div class="info-box">
                        <p><strong>Kode Pesanan:</strong><br><?php echo htmlspecialchars($data_pesanan['kodepesan']); ?></p>
                        <p><strong>Tanggal Pesan:</strong><br><?php echo date("d F Y H:i", strtotime($data_pesanan['waktupesan'])); ?></p>
                        <p><strong>Status Pesanan:</strong><br><span class="order-status-<?php echo strtolower($data_pesanan['status']); ?>"><?php echo ucfirst(htmlspecialchars($data_pesanan['status'])); ?></span></p>
                        <p><strong>Metode Bayar:</strong><br>
                            <span class="status-badge <?php echo strtolower($data_pesanan['metode']); ?>">
                                <?php echo strtoupper(htmlspecialchars($data_pesanan['metode'])); ?>
                            </span>
                        </p>
                    </div>
                    <div class="info-box">
                        <p><strong>Nama Penerima:</strong><br><?php echo htmlspecialchars($data_pesanan['nama_penerima']); ?></p>
                        <p><strong>Alamat Kirim:</strong><br><?php echo nl2br(htmlspecialchars($data_pesanan['alamat'])); ?></p>
                    </div>
                </div>

                <h3 style="font-size: var(--h3-font-size); margin-bottom: 1rem; color: var(--title-color);">Status Konfirmasi</h3>
                
                <div class="payment-status-box">
                    <p><strong>Konfirmasi Customer:</strong> 
                        <span>
                            <?php if ($data_pesanan['konfirmasicustom']): ?>
                                <span class="status-badge confirmed">Dikonfirmasi</span> pada <?php echo date('d F Y H:i', strtotime($data_pesanan['konfirmasicustom'])); ?>
                            <?php else: ?>
                                <span class="status-badge pending">Belum Dikonfirmasi</span>
                            <?php endif; ?>
                        </span>
                    </p>
                    <p><strong>Konfirmasi Seller:</strong> 
                        <span>
                            <?php if ($data_pesanan['konfirmasiseller']): ?>
                                <span class="status-badge confirmed">Dikonfirmasi</span> pada <?php echo date('d F Y H:i', strtotime($data_pesanan['konfirmasiseller'])); ?>
                            <?php else: ?>
                                <span class="status-badge pending">Menunggu Admin</span>
                            <?php endif; ?>
                        </span>
                    </p>
                </div>

                <hr style="border: 0; height: 1px; background-color: #ddd; margin: 1.5rem 0;">

                <h3 style="text-align: right; font-size: var(--h2-font-size); color: var(--title-color);">
                    TOTAL BAYAR: Rp <?php echo number_format($total_akhir, 0, ',', '.'); ?>
                </h3>

                <?php 
                $isTransfer = strtolower($data_pesanan['metode']) == 'transfer';
                $isNotConfirmed = empty($data_pesanan['konfirmasicustom']);
                
                if ($isTransfer && $isNotConfirmed): ?>
                    <div style="margin-top: 2rem; padding: 1.25rem; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px;">
                        <h4 style="color: #856404; font-size: var(--h3-font-size); margin-bottom: 0.5rem;">Instruksi Transfer</h4>
                        <p style="color: #856404; margin-top: 0.5rem;">
                            Silakan transfer sebesar **Rp <?php echo number_format($total_akhir, 0, ',', '.'); ?>** ke No. Rekening **XX-XX-XX-XX**.
                        </p>
                        <p style="color: #856404; font-weight: var(--font-bold); justify-content: flex-start;">
                            <span>Atas Nama:</span> <span>STARCOFFEE ID</span><br><br><br>
                            Kemudian unggah bukti transfer dibawah dan tunjukkan pada Kurir. Pesanan akan dikirim setelah Anda mengirim bukti pembayaran.
                        </p>
                        <br><br><br>
                    <h4 style="color: #856404; font-size: var(--h3-font-size); margin-bottom: 0.5rem;">Kirim Bukti Pembayaran</h4>
                    <form action="upload_bukti.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="kodepesan" value="<?php echo htmlspecialchars($kodepesan); ?>">
                        
                        <div style="margin-bottom: 1rem;">
                            <label for="bukti_bayar" style="display: block; font-weight: var(--font-semi-bold); margin-bottom: 0.5rem;">Pilih File Bukti (Max 2MB, JPG/PNG):</label>
                            <input type="file" name="bukti_bayar" id="bukti_bayar" required 
                                style="border: 1px solid #ddd; padding: 0.5rem; width: 100%; border-radius: 4px;">
                        </div>

                        <button type="submit" class="button button--small" style="width: 100%; text-align: center;">
                            <i class="ri-upload-cloud-2-line"></i> Upload & Konfirmasi
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../js/main.js"></script> 
</body>
</html>