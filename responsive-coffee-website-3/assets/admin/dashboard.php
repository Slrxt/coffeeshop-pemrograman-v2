<?php
session_start();
// Path ke config.php (asumsi berada di ../php/config.php)
include "../php/config.php"; 

// PENTING: Cek Permission (Pastikan hanya Admin yang bisa mengakses)
// Ganti 'admin' dengan nama permission yang Anda gunakan untuk administrator
if (!isset($_SESSION['permission']) || $_SESSION['permission'] !== 'admin') {
    // Redirect ke halaman login admin jika bukan admin
    header("Location: index.php"); // index.php di direktori assets/admin/
    exit;
}

$admin_name = htmlspecialchars($_SESSION['nama'] ?? 'Admin');

// 1. Ambil semua data produk
$sql = "SELECT idpro, nama, harga, deskripsi, image FROM produk ORDER BY idpro ASC";
$result_produk = mysqli_query($conn, $sql);

if (!$result_produk) {
    die("Query produk gagal: " . mysqli_error($conn));
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Manajemen Produk</title>
    <link rel="stylesheet" href="../css/styles.css"> 
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <style>
        /* Gaya dasar untuk Dashboard Admin */
        .admin-layout {
            display: flex;
            min-height: 100vh;
            padding-top: 50px; /* Jaga jarak dari header/nav jika ada */
            background-color: var(--body-color);
        }
        .sidebar {
            width: 250px;
            background-color: var(--body-white-color);
            padding: 2rem 1.5rem;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar a {
            display: block;
            padding: 10px 0;
            margin-bottom: 0.5rem;
            color: var(--title-color);
            text-decoration: none;
            font-weight: var(--font-semi-bold);
            border-bottom: 1px solid #eee;
        }
        .main-content {
            flex-grow: 1;
            padding: 2rem;
        }
        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--body-white-color);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .dashboard-table th, .dashboard-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .dashboard-table th {
            background-color: var(--first-color);
            color: white;
            font-weight: var(--font-bold);
            font-size: var(--normal-font-size);
        }
        .dashboard-table tr:hover {
            background-color: #f5f5f5;
        }
        .product-img-thumb {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        .action-btns a {
            margin-right: 0.5rem;
            color: var(--first-color);
            text-decoration: none;
            font-size: 1.1rem;
        }
        .action-btns .ri-delete-bin-line {
            color: red;
        }
    </style>
</head>
<body>
    
    <div class="admin-layout">
        <div class="sidebar">
            <h3 style="margin-bottom: 2rem; color: var(--first-color);">StarCoffee Admin</h3>
            <p style="margin-bottom: 1rem; font-size: var(--small-font-size); color: var(--text-color);">Halo, <?php echo $admin_name; ?>!</p>
            <a href="dashboard.php"><i class="ri-dashboard-line"></i> Dashboard</a>
            <a href="register_kurir.php"><i class="ri-user-add-line"></i> Daftar Kurir</a>
            <a href="list_pesanan.php"><i class="ri-shopping-bag-line"></i> Kelola Pesanan</a>
            <a href="../../logout.php"><i class="ri-logout-box-line"></i> Logout</a>
        </div>

        <div class="main-content">
            <h1 style="color: var(--title-color); margin-bottom: 1.5rem;">Manajemen Daftar Produk</h1>
            <a href="add_product.php" class="button button--small" style="margin-bottom: 1.5rem; display: inline-block;">+ Tambah Produk Baru</a>
            
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Foto</th>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Deskripsi Singkat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($produk = mysqli_fetch_assoc($result_produk)): 
                        // Ambil 50 karakter pertama deskripsi
                        $deskripsi_singkat = substr($produk['deskripsi'], 0, 50) . (strlen($produk['deskripsi']) > 50 ? '...' : '');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($produk['idpro']); ?></td>
                        <td>
                            <img src="../../<?php echo htmlspecialchars($produk['image']); ?>" alt="<?php echo htmlspecialchars($produk['nama']); ?>" class="product-img-thumb">
                        </td>
                        <td><?php echo htmlspecialchars($produk['nama']); ?></td>
                        <td>Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($deskripsi_singkat); ?></td>
                        <td class="action-btns">
                            <a href="edit_produk.php?id=<?php echo htmlspecialchars($produk['idpro']); ?>" title="Edit"><i class="ri-edit-line"></i></a>
                            <a href="delete_produk.php?id=<?php echo htmlspecialchars($produk['idpro']); ?>" title="Hapus" onclick="return confirm('Yakin ingin menghapus produk ini?');"><i class="ri-delete-bin-line"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>