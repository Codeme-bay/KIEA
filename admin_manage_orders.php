<?php
include 'db.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 1. Logika Update Status Pesanan
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $conn->query("UPDATE orders SET status = '$new_status' WHERE id = '$order_id'");
    header("Location: admin_manage_orders.php?msg=Status Berhasil Diperbarui");
    exit();
}

// 2. Logika Hapus Pesanan
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    // Hapus item pesanan dulu baru pesanan utamanya (Foreign Key Constraint)
    $conn->query("DELETE FROM order_items WHERE order_id = '$delete_id'");
    $conn->query("DELETE FROM orders WHERE id = '$delete_id'");
    header("Location: admin_manage_orders.php?msg=Pesanan Dihapus");
    exit();
}

// 3. Ambil Data Pesanan dengan Detail User
// Ambil Data Pesanan
// Jika data nama/alamat ada di tabel lain, Anda harus menggunakan JOIN di sini
$orders_query = "SELECT * FROM orders ORDER BY order_date DESC";
$orders_res = $conn->query($orders_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Pesanan - Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; color: #333; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .header-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .admin-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #edf2f7; margin-bottom: 25px; }

        .order-table { width: 100%; border-collapse: collapse; }
        .order-table th { background: #333; color: #fff; padding: 12px; text-align: left; font-size: 14px; }
        .order-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: top; font-size: 14px; }

        .status-select { padding: 5px; border-radius: 4px; border: 1px solid #ddd; font-size: 12px; }
        .btn-update { background: #333; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 11px; }
        .btn-delete { color: #d73a49; text-decoration: none; font-size: 12px; margin-left: 10px; }
        .btn-delete:hover { text-decoration: underline; }

        .customer-info { font-size: 12px; color: #666; line-height: 1.4; }
        .customer-info strong { color: #333; display: block; margin-bottom: 2px; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; }
        .status-pending { background: #fff8e1; color: #f39c12; }
        .status-completed { background: #e6ffed; color: #28a745; }
        .status-cancelled { background: #ffeef0; color: #d73a49; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-nav">
        <a href="admin_dashboard.php" style="text-decoration:none; color:#333; font-weight:bold;">← Kembali</a>
        <h2 style="margin:0; font-size:20px;">Manajemen Pesanan</h2>
        <div style="width:70px;"></div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div style="background:#d4edda; color:#155724; padding:10px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <table class="order-table">
            <thead>
                <tr>
                    <th>ID / Tanggal</th>
                    <th>Pelanggan & Alamat</th>
                    <th>Total Bayar</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($order = $orders_res->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong>#<?= $order['id'] ?></strong><br>
                        <span style="color:#999; font-size:11px;"><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></span>
                    </td>
                <td>
                   <div class="customer-info">
                    <strong><?= htmlspecialchars($order['full_name'] ?? 'Nama Tidak Tersedia') ?></strong>
                    <span style="display:block; font-size: 11px; color: #666;">
                        <?= htmlspecialchars($order['phone'] ?? '-') ?>
                    </span>
                    <small style="color: #888;">
                        <?= htmlspecialchars($order['address'] ?? 'Alamat belum diisi') ?>
                    </small>
                </div>
                </td>
                    <td><strong>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></strong></td>
                    <td>
                        <span class="badge status-<?= strtolower($order['status']) ?>">
                            <?= strtoupper($order['status']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status" class="status-select">
                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-update">Update</button>
                        </form>
                        <a href="admin_manage_orders.php?delete_id=<?= $order['id'] ?>" class="btn-delete" onclick="return confirm('Hapus pesanan ini?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>