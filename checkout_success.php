<?php
include 'db.php'; 

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$order_id = $conn->real_escape_string($_GET['id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pesanan Berhasil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-box" style="width: 400px;">
        <h2 style="color:#333;">Pesanan Berhasil Dibuat!</h2>
        <p>Terima kasih atas pesanan Anda. Pesanan Anda 0000<?php echo $order_id; ?> telah berhasil kami terima.</p>
        <h3>Langkah Selanjutnya:</h3>
        <a href="user_orders.php" class="btn-primary" style="width: 100%; padding: 10px; margin-top: 20px;">Lihat Status Pesanan</a>
        <a href="index.php" style="display: block; text-align: center; margin-top: 10px;">Kembali ke Beranda</a>
    </div>
</body>
</html>