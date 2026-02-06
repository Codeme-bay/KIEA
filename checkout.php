<?php
include 'db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = $_SESSION['cart'];
$total = 0;
$message = "";

$product_data = [];
$valid_cart = true;

if (!empty($cart_items)) {
    $ids = implode(',', array_keys($cart_items));
    $sql = "SELECT id, name, price, stock FROM products WHERE id IN ($ids)";
    $result = $conn->query($sql);

    while ($item = $result->fetch_assoc()) {
        $qty = $cart_items[$item['id']];
        if ($qty > $item['stock']) {
            $valid_cart = false;
            break;
        }
        $subtotal = $item['price'] * $qty;
        $total += $subtotal;
        $product_data[$item['id']] = $item;
    }
}

if (!$valid_cart) {
    header("Location: cart.php?error=stock_mismatch");
    exit();
}

$checkout_stage = 1;

if (isset($_SESSION['checkout_details']) && !empty($_SESSION['checkout_details'])) {
    $checkout_stage = 2;
}

if (isset($_GET['stage']) && $_GET['stage'] == 1) {
    $checkout_stage = 1;
}

if (isset($_POST['process_detail'])) {

    $address = $conn->real_escape_string($_POST['address']);
    $note = $conn->real_escape_string($_POST['note']);

    $_SESSION['checkout_details'] = [
        'address' => $address,
        'note' => $note
    ];
    $checkout_stage = 2;
}

if (isset($_POST['confirm_order']) && $checkout_stage == 2) {

    $details = $_SESSION['checkout_details'];
    $default_payment = 'Transfer Bank';

    $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_price, shipping_address, seller_note, payment_method, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmt_order->bind_param("idsss", $user_id, $total, $details['address'], $details['note'], $default_payment);
    $stmt_order->execute();
    $order_id = $stmt_order->insert_id;
    $stmt_order->close();

    foreach ($cart_items as $product_id => $qty) {
        $stmt_stock_update = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt_stock_update->bind_param("ii", $qty, $product_id);
        $stmt_stock_update->execute();
        $stmt_stock_update->close();
    }

    unset($_SESSION['cart']);
    unset($_SESSION['checkout_details']);

    header("Location: checkout_success.php?id=" . $order_id);
    exit();
}

$details = isset($_SESSION['checkout_details']) ? $_SESSION['checkout_details'] : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Checkout - DapurBAYU</title>
    <link rel="stylesheet" href="style.css">
    <style>
      .checkout-box { width: 600px; margin: 50px auto; padding: 30px; border: 1px solid #ddd; background: #fff; border-radius: 8px; }
        .checkout-box h3 { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .checkout-box label { font-weight: bold; display: block; margin-top: 10px; }
        .checkout-box input[type="text"], .checkout-box textarea, .checkout-box select { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; box-sizing: border-box; }
        .review-box p { margin: 5px 0; }
        .summary-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .summary-table th, .summary-table td { padding: 8px; border: 1px solid #eee; text-align: left; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">DAPUR<b>BAYU</b></div>
        <a href="cart.php">Kembali ke Keranjang</a>
    </nav>
    <div class="container">
    <div class="checkout-box">

        <?php if ($checkout_stage == 1): ?>
            <h2>Langkah 1: Detail Pengiriman</h2>
            <form method="POST">
                
                <h3>Alamat Pengiriman</h3>
                <label for="address">Alamat Lengkap:</label>
                <textarea name="address" id="address" rows="4" required><?php echo isset($details['address']) ? htmlspecialchars($details['address']) : ''; ?></textarea>
                
                <h3>Catatan Ke Penjual</h3>
                <label for="note">Catatan Tambahan (Opsional):</label>
                <textarea name="note" id="note" rows="2"><?php echo isset($details['note']) ? htmlspecialchars($details['note']) : ''; ?></textarea>
                
                <p style="text-align: right; margin-top: 20px; font-weight: bold;">Total Belanja: Rp <?php echo number_format($total, 0, ',', '.'); ?></p>
                
                <button type="submit" name="process_detail" class="btn-primary" style="width: 100%; padding: 15px;">LANJUT KE KONFIRMASI</button>
            </form>

        <?php elseif ($checkout_stage == 2): ?>
            <h2>Langkah 2: Konfirmasi Pesanan</h2>
            <form method="POST">
                
                <h3>Rincian Pesanan</h3>
                <table class="summary-table">
                    <tr><th>Produk</th><th>Qty</th><th>Harga</th></tr>
                    <?php foreach ($cart_items as $id => $qty): ?>
                        <tr>
                            <td><?php echo $product_data[$id]['name']; ?></td>
                            <td><?php echo $qty; ?></td>
                            <td>Rp <?php echo number_format($product_data[$id]['price'] * $qty, 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <div class="review-box">
                    <p><strong>Total Pembayaran:</strong> <span style="float: right; font-size: 18px; color: green;">Rp <?php echo number_format($total, 0, ',', '.'); ?></span></p>
                    <p><strong>Metode Pembayaran:</strong> <span style="float: right; font-weight: bold;">COD</span></p>
                    <p><strong>Alamat Pengiriman:</strong> <span style="float: right;"><?php echo htmlspecialchars($details['address']); ?></span></p>
                    <p><strong>Catatan Penjual:</strong> <span style="float: right;"><?php echo htmlspecialchars($details['note']); ?></span></p>
                </div>

                <a href="checkout.php?stage=1" style="display: block; text-align: center; margin-top: 15px; margin-bottom: 10px;">&lt; Kembali untuk Mengubah Detail</a>
                
                <button type="submit" name="confirm_order" class="btn-primary" style="width: 100%; padding: 15px; background-color:#333;">KONFIRMASI & PESAN</button>
            </form>

        <?php endif; ?>
    </div>
  </div>
</body>
</html>