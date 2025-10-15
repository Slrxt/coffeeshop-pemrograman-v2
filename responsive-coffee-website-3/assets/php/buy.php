<?php
session_start();
include 'config.php'; // koneksi DB

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // ambil data produk
    $result = mysqli_query($koneksi, "SELECT * FROM produk WHERE idpro=$id");
    $product = mysqli_fetch_assoc($result);

    if ($product) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // kalau produk sudah ada di cart, tambah qty
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty']++;
        } else {
            $_SESSION['cart'][$id] = [
                'nama' => $product['nama'],
                'harga' => $product['harga'],
                'qty' => 1
            ];
        }
    }
}

// balik ke halaman utama (atau produk)
header("Location: ../../index.php#products");
exit;
