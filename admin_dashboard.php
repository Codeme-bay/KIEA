<?php
include 'db.php';
include 'admin_check.php'; // Lindungi halaman ini!

// Logic Hapus Produk (DELETE)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $sql = "DELETE FROM products WHERE id = '$id'";
    if ($conn->query($sql) === TRUE) {
        header("Location: admin_dashboard.php?msg=deleted");
        exit();
    } else {
        echo "Error menghapus record: " . $conn->error;
    }
}

// Ambil Ringkasan Data untuk Dashboard
$total_products = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$total_orders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];

// Ambil semua produk
$result = $conn->query("SELECT id, name, price, stock, image FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - DAPURBAYU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Gaya tambahan untuk Dashboard */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid #eee;
        }
        .stat-card i { font-size: 24px; color: #333; }
        .stat-card h3 { margin: 0; font-size: 20px; }
        .stat-card p { margin: 0; color: #666; font-size: 14px; }
        
        .nav-link-custom {
            padding: 8px 15px;
            background: #f1f1f1;
            border-radius: 5px;
            color: #333 !important;
            font-weight: 600;
            transition: 0.3s;
        }
        .nav-link-custom:hover { background: #333; color: #fff !important; }
        .nav-link-stats { background: #e3f2fd; color: #0d47a1 !important; }
        .nav-link-stats:hover { background: #0d47a1; color: #fff !important; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">ADMIN DASHBOARD</div>
        <div class="user-actions">
            <span>Hi, admin (<?php echo $_SESSION['username']; ?>)</span>
            <a href="admin_orders.php" >Statistik Penjualan
            </a>
            <a href="admin_manage_orders.php">Manajemen Pesanan
            </a>
            <a href="index.php">Lihat Toko</a>
        </div>
    </nav>

    <div class="container">
        <div class="stats-container">
            <div class="stat-card">
                <i class="fa-solid fa-utensils"></i>
                <div>
                    <h3><?php echo $total_products; ?></h3>
                    <p>Total Produk</p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-cart-shopping"></i>
                <div>
                    <h3><?php echo $total_orders; ?></h3>
                    <p>Total Pesanan</p>
                </div>
            </div>
                </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 30px;">

        <h2>Manajemen Produk</h2>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
            <p style="color: green;">Produk berhasil disimpan/diperbarui!</p>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <p style="color: red;">Produk berhasil dihapus.</p>
        <?php endif; ?>

        <div style="margin-bottom: 20px;">
            <a href="admin_edit.php" class="btn-primary" style="padding: 10px 20px; font-weight: normal; background:#333; text-decoration:none; border-radius:5px; color:#fff;">
                <i class="fa-solid fa-plus"></i> Tambah Produk Baru
            </a>
        </div>

        <table class="cart-table" style="width: 100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Produk</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Gambar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                    <td><?php echo $row['stock']; ?></td>
                    <td><img src="<?php echo $row['image']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;"></td>
                    <td>
                        <a href="admin_edit.php?id=<?php echo $row['id']; ?>" style="color: #007bff; text-decoration:none;"><i class="fa-solid fa-pen-to-square"></i> Edit</a> |
                        <a href="admin_dashboard.php?action=delete&id=<?php echo $row['id']; ?>" style="color: red; text-decoration:none;" onclick="return confirm('Yakin ingin menghapus produk ini?');"><i class="fa-solid fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>