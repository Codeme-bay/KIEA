<?php
include 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// LOGIC PEMBATALAN PESANAN
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $order_id = $conn->real_escape_string($_GET['id']);
    
    // Hanya izinkan pembatalan jika status masih 'Pending'
    $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $order_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "<p style='color: green; text-align: center;'>Pesanan #$order_id berhasil dibatalkan.</p>";
    } else {
        $message = "<p style='color: red; text-align: center;'>Pesanan tidak dapat dibatalkan (mungkin sudah diproses atau tidak ditemukan).</p>";
    }
    $stmt->close();
}

// Ambil semua pesanan user
$sql = "SELECT id, total_price, order_date, status FROM orders WHERE user_id = '$user_id' ORDER BY order_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pesanan Saya</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">DAPUR<b>BAYU</b></div>
        <a href="index.php">Lanjut Belanja</a>
    </nav>
    <div class="container" style="padding-top: 50px;">
        <h2>Riwayat Pesanan Saya</h2>
        <?php echo $message; ?>

        <table class="cart-table" style="width: 100%;">
            <thead>
                <tr>
                    <th>ID Order</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>0000<?php echo $row['id']; ?></td>
                        <td><?php echo $row['order_date']; ?></td>
                        <td>Rp <?php echo number_format($row['total_price'], 0, ',', '.'); ?></td>
                        <td style="font-weight: bold; color: <?php 
                            if ($row['status'] === 'Completed') echo 'green';
                            else if ($row['status'] === 'Cancelled') echo 'red';
                            else echo 'orange';
                        ?>;"><?php echo $row['status']; ?></td>
                        <td>
                            <?php if ($row['status'] === 'Pending'): ?>
                                <a href="user_orders.php?action=cancel&id=<?php echo $row['id']; ?>" 
                                   onclick="return confirm('Yakin membatalkan pesanan 0000<?php echo $row['id']; ?>?');"
                                   style="color: red; text-decoration: none;">
                                    Batal
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center;">Anda belum memiliki riwayat pesanan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>