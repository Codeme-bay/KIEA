<?php 
include 'db.php';

// --- LOGIC: TAMBAH KE KERANJANG ---
if (isset($_POST['action']) && $_POST['action'] == 'add' && isset($_POST['product_id'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    $id = (int)$_POST['product_id'];
    if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
    
    // Cek Stok Real-time
    $stmt_stock = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt_stock->bind_param("i", $id);
    $stmt_stock->execute();
    $product_stock = $stmt_stock->get_result()->fetch_assoc()['stock'];
    $stmt_stock->close();
    
    $current_qty = $_SESSION['cart'][$id] ?? 0;
    
    if (($current_qty + 1) <= $product_stock) {
        $_SESSION['cart'][$id] = $current_qty + 1;
        $_SESSION['message'] = "Produk berhasil ditambahkan ke keranjang!";
    } else {
        $_SESSION['message'] = "Maaf, stok tidak mencukupi.";
    }

    header("Location: index.php");
    exit(); 
}

// --- LOGIC: BATALKAN PESANAN ---
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id && isset($_GET['action']) && $_GET['action'] == 'cancel_order' && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $order_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['message'] = "Pesanan #$order_id berhasil dibatalkan.";
    }
    header("Location: index.php");
    exit();
}

// --- LOGIC: PENCARIAN & DATA PRODUK ---
$where_clause = "";
$search_term_input = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where_clause = " WHERE name LIKE '%$search%' OR description LIKE '%$search%' ";
    $search_term_input = htmlspecialchars($search);
}

$sql = "SELECT * FROM products" . $where_clause . " ORDER BY id DESC";
$result = $conn->query($sql);
$product_count = $result->num_rows;

$notification_message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DapurBAYU - Peralatan Dapur Premium</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css"> 
    <style>
        /* --- ANIMASI PRELOADER --- */
        #preloader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #fff; display: flex; justify-content: center;
            align-items: center; z-index: 10000; transition: opacity 0.5s;
        }
        .spinner {
            width: 40px; height: 40px; border: 4px solid #f3f3f3;
            border-top: 4px solid #333; border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* --- HERO BACKGROUND --- */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('images/banner.jpg') no-repeat center center/cover;
            height: 70vh; display: flex; align-items: center; justify-content: center;
            color: white; text-align: center;
        }

        /* --- NOTIFIKASI --- */
        .notif { background: #333; color: white; padding: 15px; position: fixed; bottom: 20px; right: 20px; z-index: 999; border-radius: 5px; animation: slideIn 0.5s; }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
    </style>
</head>
<body>

    <div id="preloader">
        <div style="text-align:center;">
            <div class="spinner"></div>
            <p style="letter-spacing:2px; font-size:12px; margin-top:10px;">DAPUR<b>BAYU</b></p>
        </div>
    </div>

    <nav class="navbar">
        <div class="logo">DAPUR<b>BAYU</b></div>
        
        <div class="search-form">
            <form action="index.php" method="GET">
                <input type="text" name="search" placeholder="Cari alat masak..." value="<?php echo $search_term_input; ?>">
                <button type="submit"><i class="fa fa-search"></i></button>
            </form>
        </div>
        
        <div class="user-actions">
            <?php if($user_id): ?>
                <span>Hi, <?php echo $_SESSION['username']; ?></span>
                <?php if(($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="admin_dashboard.php" style="color: #ff9800;">[Admin]</a> 
                <?php endif; ?>
                <a href="logout.php">Keluar</a>
            <?php else: ?>
                <a href="login.php">Masuk</a>
            <?php endif; ?>
            
            <a href="cart.php" class="cart-btn">
                <i class="fa-solid fa-bag-shopping"></i> 
                <span>(<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</span>
            </a>
        </div>
    </nav>

    <?php if ($notification_message): ?>
        <div class="notif" id="notif-box"><?php echo $notification_message; ?></div>
    <?php endif; ?>

    <header class="hero">
        <div class="hero-content">
            <h1>Kitchen Starts Here</h1>
            <p>Peralatan dapur pilihan untuk masakan istimewa setiap hari.</p>
            <a href="#products" class="btn-primary" style="background:#fff; color:#333; padding: 12px 25px; text-decoration:none; font-weight:bold;">MULAI BELANJA</a>
        </div>
    </header>

    <section id="products" class="container" style="padding: 50px 0;">
        <h2 style="text-align:center; margin-bottom:40px;">
            <?php echo $search_term_input ? "Hasil Pencarian: '$search_term_input'" : "Koleksi Terbaik Kami"; ?>
        </h2>

        <div class="product-grid">
            <?php if ($product_count > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <div style="height:250px; overflow:hidden;">
                            <img src="<?php echo $row['image']; ?>" alt="produk" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                        <div class="product-info" style="padding:15px;">
                            <h3 style="font-size:16px; margin:0;"><?php echo $row['name']; ?></h3>
                            <p class="price" style="color:#333; font-weight:bold; margin:10px 0;">Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></p>
                            <p style="font-size:12px; color:#777;">Stok: <?php echo $row['stock']; ?></p>

                            <?php if ($row['stock'] > 0): ?>
                                <form action="index.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn-buy" style="width:100%; background:none; border:1px solid #333; padding:8px; cursor:pointer;">+ Keranjang</button>
                                </form>
                            <?php else: ?>
                                <button disabled style="width:100%; padding:8px; background:#eee; border:none; color:#aaa;">Habis</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align:center; padding: 50px;">
                    <i class="fa fa-search" style="font-size: 40px; color: #ddd;"></i>
                    <p>Produk tidak ditemukan.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Menghilangkan Preloader
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            setTimeout(() => {
                preloader.style.opacity = '0';
                setTimeout(() => preloader.style.display = 'none', 500);
            }, 500);
        });

        // Menghilangkan Notifikasi Otomatis
        const notif = document.getElementById('notif-box');
        if(notif) {
            setTimeout(() => {
                notif.style.opacity = '0';
                setTimeout(() => notif.remove(), 500);
            }, 3000);
        }
    </script>
</body>
</html>