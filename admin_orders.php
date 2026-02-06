<?php
include 'db.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 1. Ambil daftar produk untuk filter
$products_res = $conn->query("SELECT id, name FROM products ORDER BY name ASC");

// 2. Tangkap data dari Filter
$filter_product = isset($_GET['product_id']) ? $_GET['product_id'] : '';
$filter_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$filter_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// 3. Query untuk Data Chart (Statistik Penjualan)
// Menggunakan LEFT JOIN agar data tetap muncul meskipun filter produk kosong
$chart_query = "SELECT DATE(o.order_date) as tgl, SUM(oi.price * oi.quantity) as total_sales 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.status != 'cancelled'";

if ($filter_product != '') $chart_query .= " AND oi.product_id = '" . $conn->real_escape_string($filter_product) . "'";
if ($filter_start != '')   $chart_query .= " AND DATE(o.order_date) >= '" . $conn->real_escape_string($filter_start) . "'";
if ($filter_end != '')     $chart_query .= " AND DATE(o.order_date) <= '" . $conn->real_escape_string($filter_end) . "'";

$chart_query .= " GROUP BY DATE(o.order_date) ORDER BY tgl ASC";
$chart_res = $conn->query($chart_query);

$labels = [];
$data_sales = [];
while ($row = $chart_res->fetch_assoc()) {
    $labels[] = date('d M', strtotime($row['tgl']));
    $data_sales[] = (float)$row['total_sales'];
}

// 4. Query untuk Tabel Riwayat (PENTING: Gunakan LEFT JOIN untuk keamanan data)
$table_query = "SELECT o.id, o.order_date, p.name as product_name, oi.quantity, (oi.price * oi.quantity) as subtotal, o.status 
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE 1=1";

if ($filter_product != '') $table_query .= " AND oi.product_id = '" . $conn->real_escape_string($filter_product) . "'";
if ($filter_start != '')   $table_query .= " AND DATE(o.order_date) >= '" . $conn->real_escape_string($filter_start) . "'";
if ($filter_end != '')     $table_query .= " AND DATE(o.order_date) <= '" . $conn->real_escape_string($filter_end) . "'";

$table_query = "SELECT o.id, o.order_date, p.name as product_name, oi.quantity, (oi.price * oi.quantity) as subtotal, o.status 
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE 1=1";
$table_result = $conn->query($table_query);

// DEBUGGING: Aktifkan baris di bawah ini jika data masih belum muncul
// echo $conn->error; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Statistik Penjualan - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS yang Anda miliki sudah bagus, hanya dirapikan sedikit */
        body { background-color: #f8f9fa; color: #333; font-family: 'Inter', sans-serif; }
        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: #fff;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .btn-back { text-decoration: none; color: #333; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-back:hover { color: #000; transform: translateX(-5px); }
        .btn-back-icon { background: #f1f1f1; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        .admin-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #edf2f7; }
        .filter-row { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filter-item { flex: 1; min-width: 200px; }
        .filter-item label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #666; }
        .filter-item select, .filter-item input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn-group { display: flex; gap: 10px; }
        .btn-submit { background: #333; color: #fff; border: none; padding: 11px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-reset { background: #f1f1f1; color: #333; text-decoration: none; padding: 11px 20px; border-radius: 6px; font-size: 14px; border: 1px solid #ddd; text-align: center; }
        .btn-submit:hover { background: #000; }
        .modern-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .modern-table th { background: #333; color: #fff; text-align: left; padding: 15px; font-size: 14px; }
        .modern-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; }
        .badge { padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-completed { background: #e6fffa; color: #2c7a7b; }
        .status-pending { background: #fffaf0; color: #975a16; }
        .status-cancelled { background: #fff5f5; color: #c53030; }
    </style>
</head>
<body>

<div class="container" style="margin: 40px auto; max-width: 1200px; padding: 0 20px;">
    <div class="header-nav">
        <a href="admin_dashboard.php" class="btn-back">
            <span class="btn-back-icon">←</span> Kembali ke Admin Panel
        </a>
        <h2 style="margin: 0; font-size: 20px;">Statistik & Laporan Pesanan</h2>
        <div style="width: 170px;"></div> </div>

    <div class="admin-card">
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-item">
                    <label>Filter Produk</label>
                    <select name="product_id">
                        <option value="">Semua Produk</option>
                        <?php 
                        $products_res->data_seek(0);
                        while($p = $products_res->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= ($filter_product == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label>Dari Tanggal</label>
                    <input type="date" name="start_date" value="<?= $filter_start ?>">
                </div>
                <div class="filter-item">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="end_date" value="<?= $filter_end ?>">
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn-submit">Tampilkan</button>
                    <a href="admin_orders.php" class="btn-reset">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h4 style="margin-bottom: 20px; font-size: 16px;">Tren Pendapatan</h4>
        <div style="height: 350px;">
            <canvas id="lineChart"></canvas>
        </div>
    </div>

    <div class="admin-card">
        <h4 style="margin-bottom: 20px; font-size: 16px;">Riwayat Transaksi</h4>
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Produk</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($table_result && $table_result->num_rows > 0): ?>
                        <?php while($row = $table_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                            <td><strong><?= htmlspecialchars($row['product_name']) ?></strong></td>
                            <td><?= $row['quantity'] ?> Pcs</td>
                            <td>Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                            <td>
                                <span class="badge status-<?= strtolower($row['status']) ?>">
                                    <?= strtoupper($row['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; color: #999; padding: 40px;">Tidak ada data ditemukan di database.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('lineChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: <?php echo json_encode($data_sales); ?>,
                borderColor: '#333',
                backgroundColor: 'rgba(51, 51, 51, 0.05)',
                borderWidth: 3,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#333',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0' },
                    ticks: { callback: (v) => 'Rp ' + v.toLocaleString('id-ID') }
                },
                x: { grid: { display: false } }
            }
        }
    });
</script>

</body>
</html>