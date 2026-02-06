<?php
include 'db.php';
include 'admin_check.php'; 

$is_edit = false;
$message = "";
$product = [
    'name' => '', 
    'price' => 0, 
    'stock' => 0, 
    'description' => '', 
    'image' => ''
];

// 1. MODE EDIT: Ambil data produk berdasarkan ID
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
        $is_edit = true;
    } else {
        $message = "Produk tidak ditemukan!";
    }
    $stmt->close();
}

// 2. PROSES SIMPAN (INSERT atau UPDATE)
if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $description = trim($_POST['description']);
    $existing_image = $_POST['existing_image']; // Path gambar lama
    $final_image_path = $existing_image;

    // PROSES UPLOAD FILE JIKA ADA
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === 0) {
        $target_dir = "images/";
        
        // Buat folder jika belum ada
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $ext = pathinfo($_FILES["image_upload"]["name"], PATHINFO_EXTENSION);
        $new_name = "produk_" . time() . "." . $ext;
        $target_file = $target_dir . $new_name;

        // Validasi format
        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
            if (move_uploaded_file($_FILES["image_upload"]["tmp_name"], $target_file)) {
                $final_image_path = $target_file;

                // HAPUS GAMBAR LAMA (Jika sedang edit dan gambar lama bukan string kosong)
                if ($is_edit && !empty($existing_image) && file_exists($existing_image)) {
                    unlink($existing_image);
                }
            } else {
                $message = "Gagal memindahkan file ke folder images.";
            }
        } else {
            $message = "Format gambar tidak didukung (Hanya JPG, PNG, WebP).";
        }
    }

    // UPDATE KE DATABASE
    if (empty($message)) {
        if ($is_edit) {
            $stmt = $conn->prepare("UPDATE products SET name=?, price=?, stock=?, description=?, image=? WHERE id=?");
            $stmt->bind_param("sdissi", $name, $price, $stock, $description, $final_image_path, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, price, stock, description, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdiss", $name, $price, $stock, $description, $final_image_path);
        }

        if ($stmt->execute()) {
            header("Location: admin_dashboard.php?msg=success");
            exit();
        } else {
            $message = "Gagal menyimpan ke database: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_edit ? 'Edit' : 'Tambah'; ?> Produk - Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container { max-width: 600px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); font-family: sans-serif; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 14px; }
        input[type="text"], input[type="number"], textarea, input[type="file"] {
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;
        }
        .row { display: flex; gap: 20px; }
        .col { flex: 1; }
        .preview-box { margin-top: 10px; border: 2px dashed #ddd; padding: 10px; text-align: center; border-radius: 8px; }
        #img-preview { max-width: 100%; height: 200px; object-fit: contain; display: <?php echo $product['image'] ? 'block' : 'none'; ?>; margin: 0 auto; }
        .btn-save { background: #333; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn-save:hover { background: #000; }
        .alert { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body style="background: #f4f4f4;">

<div class="form-container">
    <h2 style="margin-top:0;"><?php echo $is_edit ? 'Edit Produk' : 'Tambah Produk Baru'; ?></h2>
    
    <?php if($message): ?>
        <div class="alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Nama Produk</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>

        <div class="row">
            <div class="col form-group">
                <label>Harga (Rp)</label>
                <input type="number" name="price" value="<?php echo $product['price']; ?>" required>
            </div>
            <div class="col form-group">
                <label>Stok</label>
                <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Gambar Produk</label>
            <input type="file" name="image_upload" accept="image/*" onchange="previewFile(this)">
            <input type="hidden" name="existing_image" value="<?php echo $product['image']; ?>">
            
            <div class="preview-box">
                <p id="preview-text" style="display: <?php echo $product['image'] ? 'none' : 'block'; ?>; color: #999;">Belum ada gambar terpilih</p>
                <img id="img-preview" src="<?php echo $product['image']; ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="description" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>

        <button type="submit" name="submit" class="btn-save">
            <?php echo $is_edit ? 'UPDATE PRODUK' : 'SIMPAN PRODUK'; ?>
        </button>

        <a href="admin_dashboard.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none; font-size:14px;">Batal</a>
    </form>
</div>

<script>
function previewFile(input) {
    const file = input.files[0];
    const preview = document.getElementById('img-preview');
    const text = document.getElementById('preview-text');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            text.style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
}
</script>

</body>
</html>