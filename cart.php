<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'buy_now' && isset($_POST['product_id'])) {
    $id = $_POST['product_id'];

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $stmt_stock = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt_stock->bind_param("i", $id);
    $stmt_stock->execute();
    $result_stock = $stmt_stock->get_result();
    $product_stock = $result_stock->fetch_assoc()['stock'];
    $stmt_stock->close();

    $current_qty = isset($_SESSION['cart'][$id]) ? $_SESSION['cart'][$id] : 0;

    if (($current_qty + 1) <= $product_stock) {
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = 1;
        } else {
            $_SESSION['cart'][$id]++;
        }
    }

    header("Location: cart.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['product_id'])) {
    $id = $_GET['product_id'];
    
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
    
    header("Location: cart.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Keranjang Belanja</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">DAPUR<b>BAYU</b></div>
        <a href="index.php" style="font-weight: bold;">Lanjut Belanja</a>
    </nav>

    <div class="container cart-page">
        <h2>Tas Belanja Anda</h2>
        
        <?php 
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])): 
        ?>
            <p style="text-align: center; padding: 50px 0;">Keranjang belanja Anda masih kosong. Silakan <a href="index.php">cari produk</a> di beranda.</p>
        <?php 
        else: 
        ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($_SESSION['cart'] as $id => $qty):
                        $stmt = $conn->prepare("SELECT name, price, stock FROM products WHERE id=?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        
                        if ($item = $res->fetch_assoc()):
                            $subtotal = $item['price'] * $qty;
                            $total += $subtotal;
                    ?>
                    <tr>
                        <td><?php echo $item['name']; ?></td>
                        <td><?php echo $qty; ?></td>
                        <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                        <td>
                            <a href="cart.php?action=delete&product_id=<?php echo $id; ?>" 
                               style="color: red; text-decoration: none; font-weight: bold;"
                               onclick="return confirm('Yakin ingin menghapus produk ini?');">
                                <i class="fa-solid fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endif;
                        $stmt->close();
                    endforeach; 
                    ?>
                </tbody>
            </table>
            
            <div class="cart-summary">
                <h3>Total: Rp <?php echo number_format($total, 0, ',', '.'); ?></h3>
                <form action="checkout.php" method="POST">
                    <input type="hidden" name="total" value="<?php echo $total; ?>">
                    <button type="submit" class="btn-primary" style="padding: 15px 30px; font-size:16px;">BAYAR SEKARANG</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>