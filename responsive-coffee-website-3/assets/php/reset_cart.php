<?php
session_start();

// hapus semua isi keranjang
unset($_SESSION['cart']);

// redirect balik ke halaman utama (atau ke produk langsung)
header("Location: ../../index.php#products");
exit;
