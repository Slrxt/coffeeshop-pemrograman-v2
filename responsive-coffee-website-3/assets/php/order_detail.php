<?php
session_start();
include "config.php";

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Cek status login
if (!$isLoggedIn) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? '';
$order_data = null;
$items = [];

if (empty($order_id)) {
    // Jika tidak ada ID pesanan, redirect kembali ke profil
    header("Location: profile.php");
    exit;
}

// 1. Ambil Data Pesanan
// Penting: Pastikan pesanan adalah milik user yang sedang login
$stmt_order = $conn->prepare("SELECT kodepesan, waktupesan, harga, status, alamat, items, nama_penerima, metode FROM pemesanan WHERE kodepesan = ? AND user_id = ?");
$stmt_order->bind_param("si", $order_id, $user_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();

if ($result_order->num_rows > 0) {
    $order_data = $result_order->fetch_assoc();
    // Decode JSON items
    $items = json_decode($order_data['items'], true);
} else {
    // Pesanan tidak ditemukan atau bukan milik pengguna ini
    header("Location: profile.php");
    exit;
}
// --- LOGIKA PERHITUNGAN BARU: DISKON 10% TANPA PPN ---
$initial_subtotal = 0;

// 1. Hitung Subtotal Awal (sebelum diskon)
foreach ($items as $item) {
    // Bersihkan harga dari 'Rp', titik, dan koma
    $harga = floatval(str_replace(['Rp', '.', ','], '', $item['price']));
    $kuantitas = intval($item['quantity']);
    $initial_subtotal += $harga * $kuantitas;
}

// 2. Hitung Diskon yang Diterapkan
// Diskon adalah selisih antara Subtotal Awal dan Harga Akhir yang tersimpan
$diskon_applied = $initial_subtotal - $order_data['harga'];

// Pembulatan dan Validasi
$diskon_applied = round($diskon_applied);
if (abs($diskon_applied) < 1) { 
    $diskon_applied = 0;
} 
// --- AKHIR LOGIKA PERHITUNGAN ---
$TOTAL_WIDTH = 40; 

// === LOGIKA CEK PRINT MODE ===
$current_url = $_SERVER['REQUEST_URI'];
$is_print_mode = isset($_GET['print']) && $_GET['print'] === 'true';

// Variabel URL yang akan digunakan tombol cetak
$print_url = $current_url;

// Cek apakah '?' sudah ada di URL
if (strpos($print_url, '?') === false) {
    // Jika tidak ada '?', tambahkan '?'
    $print_url .= '?print=true';
} else {
    // Jika ada '?', tambahkan '&'
    $print_url .= '&print=true';
}

// --- FUNCTION UTILITY CETAK ---
function print_center($text, $width) {
    return str_pad(strtoupper($text), $width, ' ', STR_PAD_BOTH);
}

// Menggunakan spasi sebagai padding untuk perataan harga
function print_line_space($left, $right, $width) {
    $left_segment = $left;
    $right_segment = $right;
    
    $padding_length = $width - strlen($left_segment) - strlen($right_segment);
    $padding = str_repeat(' ', max(1, $padding_length));
    return $left_segment . $padding . $right_segment;
}
?> 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan <?php echo htmlspecialchars($order_id); ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Gaya Kustom untuk Halaman Detail Pesanan */
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
        
        /* Gaya Daftar Produk */
        .product-list {
            list-style: none;
            padding: 0;
        }
        .product-item {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        .product-item:last-child {
            border-bottom: none;
        }
        .product-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-right: 1rem;
        }
        .product-details {
            flex-grow: 1;
        }
        .product-name {
            font-weight: var(--font-semi-bold);
            color: var(--title-color);
            margin-bottom: 0.25rem;
        }
        .product-qty-price {
            font-size: var(--small-font-size);
            color: var(--text-color);
        }
    </style>
    <style>
        @media print {

    @page {
        size: 12cm auto; /* Printer thermal lebar 80mm */
        margin: 0 !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        width: 12cm !important;
        font-family: 'Poppins', sans-serif !important;
        background: white !important;
    }

    /* ❌ SEMBUNYIKAN layout web biasa */
    header, .detail-section, .back-btn {
        visibility: hidden;
        display: none;
        overflow: hidden;
    }

    /* ✅ HANYA TAMPIL STRUK */
    .struk-print {
        visibility: visible !important;
    }

    .detail-wrapper-pr, .detail-section-pr, .detail-title-pr, 
    .info-grid-pr, .info-box-pr, .info-box-pr p, 
    .info-box-pr strong, .product-list-pr, 
    .product-item-pr, .product-item-pr:last-child, 
    .product-img-pr, .product-details-pr, 
    .product-name-pr, .product-qty-price-pr {
        visibility: visible !important;
    }

    .struk-print {
        width: 12cm !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        box-sizing: border-box;
        transform: translateX(40%) !important;
    }

    /* GARIS HIJAU SESUAI TEMPLATE */
    .struk-line {
        width: 100%;
        height: 3px;
        background: #14b892;
        margin: 6px 0 10px;
        border-radius: 6px;
    }

    /* TEXT STYLE */
    .struk-title {
        font-size: 15px;
        font-weight: 600;
        text-align: left;
        margin-bottom: 2px;
    }

    .struk-order-id {
        font-size: 13px;
        font-weight: 400;
        margin-bottom: 4px;
        color: #555;
    }

    .struk-item {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin-bottom: 4px;
        font-weight: 400;
    }

    .struk-subtitle {
        font-size: 12px;
        margin-top: 6px;
        font-weight: 500;
        color: #555;
    }

    .struk-total {
        font-size: 14px;
        font-weight: 700;
        text-align: right;
        margin-top: 6px;
    }
}
    </style>
    <style>
        .detail-wrapper-pr {
            max-width: 900px;
            margin: 0;
        }
        .detail-section-pr {
            background-color: var(--body-white-color);
            padding: 1rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin-bottom: 2px;
        }
        .detail-title-pr {
            font-size: var(--h2-font-size);
            color: var(--title-color);
            margin-bottom: 2px;
            border-bottom: 2px solid var(--first-color);
        }
        .info-grid-pr {
            display: grid;
            margin-bottom: 2px;
        }
        .info-box-pr {
            margin-top: 4px;
        }
        .info-box-pr p { margin-bottom: 0.5rem; font-size: var(--small-font-size); }
        .info-box-pr strong { color: var(--title-color); }
        
        /* Gaya Daftar Produk */
        .product-list-pr {
            list-style: none;
            padding: 0;
        }
        .product-item-pr {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 4px 0;
        }
        .product-item-pr:last-child {
            border-bottom: none;
        }
        .product-details-pr {
            flex-grow: 1;
        }
        .product-name-pr {
            font-weight: var(--font-semi-bold);
            color: var(--title-color);
            margin-bottom: 0.25rem;
        }
        .product-qty-price-pr {
            font-size: var(--small-font-size);
            color: var(--text-color);
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
            <a class="back-btn" href="profile.php" style="display: inline-block; margin-bottom: 1.5rem; color: var(--first-color); font-weight: var(--font-semi-bold);"><i class="ri-arrow-left-line"></i> Kembali ke Profil</a>
            
            <div class="detail-section">
                <h1 class="detail-title">Detail Pesanan #<?php echo htmlspecialchars($order_data['kodepesan']); ?></h1>
                
                <div class="info-grid">
                    <div class="info-box">
                        <p><strong>Tanggal Pesan:</strong><br><?php echo date("d F Y H:i", strtotime($order_data['waktupesan'])); ?></p>
                        <p><strong>Status:</strong><br><span class="order-status-<?php echo strtolower($order_data['status']); ?>"><?php echo ucfirst(htmlspecialchars($order_data['status'])); ?></span></p>
                        <p><strong>Metode Bayar:</strong><br><?php echo strtoupper(htmlspecialchars($order_data['metode'])); ?></p>
                    </div>
                    <div class="info-box">
                        <p><strong>Nama Penerima:</strong><br><?php echo htmlspecialchars($order_data['nama_penerima']); ?></p>
                        <p><strong>Alamat Kirim:</strong><br><?php echo nl2br(htmlspecialchars($order_data['alamat'])); ?></p>
                    </div>
                </div>

                <h3 style="font-size: var(--h3-font-size); margin-bottom: 1rem; color: var(--title-color);">Produk Dipesan</h3>
                <ul class="product-list">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): 
                            // 1. Ganti 'img_url' menjadi 'image' (sesuai JSON yang diberikan)
                            $img_path_from_json = $item['image'] ?? ''; 
                            
                            // 2. Sesuaikan path: Tambahkan '../../' karena order_detail.php ada di assets/php/
                            // Kita perlu naik dua level untuk mengakses path seperti assets/img/...
                            if (!empty($img_path_from_json)) {
                                $final_img_url = '../../' . $img_path_from_json;
                            } else {
                                // Gunakan default jika path kosong
                                $final_img_url = '../../assets/img/default-product.png'; 
                            }
                            
                            $subtotal = intval(preg_replace('/[^0-9]/', '', $item['price'])) * $item['quantity'];
                        ?>
                        <li class="product-item">
                            <img src="<?php echo htmlspecialchars($final_img_url); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-img">
                            <div class="product-details">
                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-qty-price">
                                    <?php echo htmlspecialchars($item['quantity']); ?> x <?php echo htmlspecialchars($item['price']); ?>
                                </div>
                                <div class="product-qty-price" style="font-weight: var(--font-semi-bold);">
                                    Subtotal: Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Tidak ada produk dalam pesanan ini.</p>
                    <?php endif; ?>
                </ul>
                
                <hr style="border: 0; height: 1px; background-color: #ddd; margin: 1.5rem 0;">
                
                <p style="text-align: right; font-size: var(--normal-font-size); color: var(--text-color);">
                    Subtotal Produk: Rp <?php echo number_format($initial_subtotal, 0, ',', '.'); ?>
                </p>

                <p style="text-align: right; font-size: var(--normal-font-size); font-weight: var(--font-semi-bold); 
                    /* Atur warna diskon menjadi merah jika ada, atau abu-abu jika 0 */
                    color: <?php echo $diskon_applied > 0 ? 'red' : 'var(--text-color)'; ?>;
                ">
                    Diskon Member (10%): - Rp <?php echo number_format($diskon_applied, 0, ',', '.'); ?>
                </p>

                <h3 style="text-align: right; font-size: var(--h2-font-size); color: var(--title-color);">
                    TOTAL BAYAR: Rp <?php echo number_format($order_data['harga'], 0, ',', '.'); ?>
                </h3>
                <?php if (!$is_print_mode): ?>
    <a href="<?php echo htmlspecialchars($print_url); ?>" class="button print-button" 
       style="margin-bottom: 1.5rem; display: inline-block; background-color: var(--first-color); color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: var(--font-semi-bold);">
        <i class="ri-printer-line" style="margin-right: 0.5rem;"></i> Cetak Struk
    </a>
    <?php endif; ?>
            </div>
            <?php if(isset($_GET['print']) && $_GET['print'] == 'true'): ?>
<div class="struk-print">
<div class="detail-section-pr">
                <h1 class="detail-title-pr">Detail Pesanan #<?php echo htmlspecialchars($order_data['kodepesan']); ?></h1>
                
                <div class="info-grid-pr">
                    <div class="info-box-pr">
                        <p><strong>Tanggal Pesan:</strong><br><?php echo date("d F Y H:i", strtotime($order_data['waktupesan'])); ?></p>
                        <p><strong>Status:</strong><br><span class="order-status-<?php echo strtolower($order_data['status']); ?>"><?php echo ucfirst(htmlspecialchars($order_data['status'])); ?></span></p>
                        <p><strong>Metode Bayar:</strong><br><?php echo strtoupper(htmlspecialchars($order_data['metode'])); ?></p>
                        <p><strong>Nama Penerima:</strong><br><?php echo htmlspecialchars($order_data['nama_penerima']); ?></p>
                        <p><strong>Alamat Kirim:</strong><br><?php echo nl2br(htmlspecialchars($order_data['alamat'])); ?></p>
                    </div>
                </div>

                <h3 style="font-size: var(--h3-font-size); margin-bottom: 1rem; color: var(--title-color);">Produk Dipesan</h3>
                <ul class="product-list-pr">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): 
                            // 1. Ganti 'img_url' menjadi 'image' (sesuai JSON yang diberikan)
                            $img_path_from_json = $item['image'] ?? ''; 
                            
                            // 2. Sesuaikan path: Tambahkan '../../' karena order_detail.php ada di assets/php/
                            // Kita perlu naik dua level untuk mengakses path seperti assets/img/...
                            if (!empty($img_path_from_json)) {
                                $final_img_url = '../../' . $img_path_from_json;
                            } else {
                                // Gunakan default jika path kosong
                                $final_img_url = '../../assets/img/default-product.png'; 
                            }
                            
                            $subtotal = intval(preg_replace('/[^0-9]/', '', $item['price'])) * $item['quantity'];
                        ?>
                        <li class="product-item-pr">
                            <div class="product-details-pr">
                                <div class="product-name-pr"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-qty-price-pr">
                                    <?php echo htmlspecialchars($item['quantity']); ?> x <?php echo htmlspecialchars($item['price']); ?>
                                </div>
                                <div class="product-qty-price-pr" style="font-weight: var(--font-semi-bold);">
                                    Subtotal: Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Tidak ada produk dalam pesanan ini.</p>
                    <?php endif; ?>
                </ul>
                
                <hr style="border: 0; height: 1px; background-color: #ddd; margin: 4px 0;">
                
                <p style="text-align: right; font-size: var(--normal-font-size); color: var(--text-color);">
                    Subtotal Produk: Rp <?php echo number_format($initial_subtotal, 0, ',', '.'); ?>
                </p>

                <p style="text-align: right; font-size: var(--normal-font-size); font-weight: var(--font-semi-bold); 
                    /* Atur warna diskon menjadi merah jika ada, atau abu-abu jika 0 */
                    color: <?php echo $diskon_applied > 0 ? 'red' : 'var(--text-color)'; ?>;
                ">
                    Diskon Member (10%): - Rp <?php echo number_format($diskon_applied, 0, ',', '.'); ?>
                </p>

                <h3 style="text-align: right; font-size: var(--h2-font-size); color: var(--title-color);">
                    TOTAL BAYAR: Rp <?php echo number_format($order_data['harga'], 0, ',', '.'); ?>
                </h3>
            </div>
</div>
<?php endif; ?>

            <a class="back-btn" href="profile.php" style="display: inline-block; margin-bottom: 1.5rem; color: var(--first-color); font-weight: var(--font-semi-bold);"><i class="ri-arrow-left-line"></i> Kembali ke Profil</a>
        </div>
    </main>
    <script>
    // Ambil parameter URL
    const urlParams = new URLSearchParams(window.location.search);
    const shouldPrint = urlParams.get('print');
    
    // Jika parameter 'print' ada dan bernilai 'true', panggil fungsi cetak (Ctrl+P)
    if (shouldPrint === 'true') {
        window.onload = function() {
            // Memberi waktu sebentar agar browser selesai merender CSS print
            setTimeout(() => {
                window.print();
            }, 300); 
        };
    }
</script>
</body>
</html>