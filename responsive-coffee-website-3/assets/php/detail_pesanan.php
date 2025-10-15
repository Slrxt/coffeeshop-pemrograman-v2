<?php
include "config.php";

$sql = "SELECT * FROM pemesanan ORDER BY waktupesan DESC";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Belum ada pesanan.");
}

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['items'] = json_decode($row['items'], true);
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Semua Pesanan</title>
  <link rel="stylesheet" href="styles.css"> <!-- gunakan style utama -->
</head>
<body>
  <div class="container">
    <h2>Daftar Semua Pesanan</h2>

    <?php foreach ($orders as $order): ?>
      <div class="order-box" style="margin-bottom:20px; padding:15px; border:1px solid #ccc; border-radius:10px;">
        <h3>Pesanan #<?= htmlspecialchars($order['kodepesan']) ?> (<?= htmlspecialchars($order['status']) ?>)</h3>
        <p><strong>ID Pesanan:</strong> <?= $order['idpes'] ?></p>
        <p><strong>Waktu:</strong> <?= $order['waktupesan'] ?></p>
        <p><strong>Alamat:</strong> <?= htmlspecialchars($order['alamat']) ?></p>
        <p><strong>Total:</strong> Rp <?= number_format($order['harga'],0,',','.') ?></p>

        <table border="1" cellpadding="8" cellspacing="0" width="100%">
          <thead>
            <tr>
              <th>Produk</th>
              <th>Harga</th>
              <th>Kuantitas</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($order['items'] as $item): ?>
              <?php
                $harga = floatval(str_replace(['Rp', '.', ','], '', $item['price']));
                $qty = $item['quantity'];
                $subtotal = $harga * $qty;
              ?>
              <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['price'] ?></td>
                <td><?= $qty ?></td>
                <td>Rp <?= number_format($subtotal,0,',','.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
