<?php
session_start();
// Path ke config.php (asumsi berada di ../php/config.php)
include "../php/config.php"; 

// PENTING: Cek Permission (Pastikan hanya Kurir yang bisa mengakses)
if (!isset($_SESSION['permission']) || $_SESSION['permission'] !== 'kurir') {
    // Redirect ke halaman login kurir
    header("Location: index.php"); 
    exit;
}

$kurir_id = $_SESSION['kurir_id'];
$kurir_name = htmlspecialchars($_SESSION['nama_kurir'] ?? 'Kurir');

// 1. Ambil Data Profil Kurir
$stmt_profile = $conn->prepare("SELECT nama, email FROM akun WHERE id = ?");
$stmt_profile->bind_param("i", $kurir_id);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$profile_data = $result_profile->fetch_assoc();
$stmt_profile->close();

// 2. Ambil Daftar Pesanan SIAP DIAMBIL (Status 'dikirim' DAN belum ada kurir_id)
// ASUMSI: Tabel 'pemesanan' memiliki kolom 'kurir_id'
$sql_orders = "
    SELECT 
        kodepesan, waktupesan, harga, alamat, nama_penerima, metode 
    FROM 
        pemesanan 
    WHERE 
        status = 'siap dikirim' 
    AND 
        (kurir_id IS NULL OR kurir_id = 0)
    ORDER BY 
        waktupesan ASC
";
$result_orders = mysqli_query($conn, $sql_orders);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kurir - Ambil Pesanan</title>
    <link rel="stylesheet" href="../css/styles.css"> 
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <style>
        .kurir-header {
            background-color: var(--first-color);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            margin-bottom: 2rem;
        }
        .profile-info h1 {
            margin-top: 0;
            color: white;
            font-size: var(--h1-font-size);
        }
        .profile-info p {
            margin: 0;
            font-size: var(--normal-font-size);
        }
        .main-content {
            padding: 0 2rem 2rem;
            max-width: 1200px;
            margin: auto;
        }
        .order-card {
            background-color: var(--body-white-color);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid var(--first-color-alt);
        }
        .order-card h3 {
            color: var(--title-color);
            margin-top: 0;
            margin-bottom: 0.5rem;
        }
        .order-card .details {
            font-size: var(--small-font-size);
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        .order-card .details strong {
            color: var(--title-color);
        }
        .action-link {
            display: inline-block;
            background-color: var(--first-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: var(--font-semi-bold);
            transition: background-color 0.3s;
        }
        .action-link:hover {
            background-color: var(--first-color-alt);
        }
    </style>
</head>
<body>
    
    <header class="kurir-header">
        <div class="profile-info">
            <h1><i class="ri-user-2-line"></i> Dashboard Kurir</h1>
            <p>Selamat datang, **<?php echo htmlspecialchars($profile_data['nama']); ?>**</p>
            <p>Email: <?php echo htmlspecialchars($profile_data['email']); ?></p>
            <a href="../../logout.php" style="color: #ffc107; font-weight: var(--font-semi-bold); text-decoration: none; display: block; margin-top: 10px;"><i class="ri-logout-box-line"></i> Logout</a>
        </div>
    </header>

    <main class="main-content">
        <h2 style="color: var(--title-color); margin-bottom: 1.5rem;"><i class="ri-map-pin-line"></i> Pesanan Siap Diambil</h2>
        <p style="margin-bottom: 2rem;">Berikut adalah daftar pesanan yang sudah dikonfirmasi dan belum ditugaskan ke kurir mana pun. Silakan ambil pesanan yang dekat dengan Anda.</p>

        <?php if (mysqli_num_rows($result_orders) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($result_orders)): ?>
            <div class="order-card">
                <h3>Pesanan #<?php echo htmlspecialchars($order['kodepesan']); ?></h3>
                <div class="details">
                    <p>Penerima: **<?php echo htmlspecialchars($order['nama_penerima']); ?>**</p>
                    <p>Alamat Pengiriman: <?php echo htmlspecialchars($order['alamat']); ?></p>
                    <p>Total Bayar: **Rp <?php echo number_format($order['harga'], 0, ',', '.'); ?>**</p>
                    <p>Metode: <?php echo htmlspecialchars(strtoupper($order['metode'])); ?></p>
                    <p>Waktu Pesan: <?php echo date('d M Y, H:i', strtotime($order['waktupesan'])); ?></p>
                </div>
                <button onclick="ambilPesanan('<?php echo htmlspecialchars($order['kodepesan']); ?>')" 
        class="action-link" 
        style="border: none; cursor: pointer;">
    <i class="ri-hand-coin-line"></i> Ambil Pesanan Ini
</button>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem; border: 1px dashed #ddd; border-radius: 8px;">
                <i class="ri-inbox-line" style="font-size: 3rem; color: var(--text-color);"></i>
                <p style="color: var(--text-color);">Tidak ada pesanan yang siap diambil saat ini.</p>
            </div>
        <?php endif; ?>
    </main>
<script>
    function ambilPesanan(kodepesan) {
    // Tampilkan konfirmasi (opsional)
    if (!confirm(`Apakah Anda yakin ingin mengambil pesanan ${kodepesan}?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('kodepesan', kodepesan);

    fetch('ambil_pesanan.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            // Muat ulang halaman atau perbarui baris tabel secara dinamis
            window.location.reload(); 
        } else {
            alert('Gagal: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menghubungi server.');
    });
}
</script>
</body>
</html>