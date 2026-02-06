<?php
include 'db.php';

$message = "";

if (isset($_POST['register'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email    = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $address  = $conn->real_escape_string($_POST['address']);

    // 1. Cek apakah username sudah ada
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $message = "Username sudah terpakai, silakan pilih yang lain.";
    } else {
        // 2. Enkripsi Password (Hashing)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 3. Masukkan ke Database
        $sql = "INSERT INTO users (username, password, email, address) VALUES ('$username', '$hashed_password', '$email', '$address')";
        
        if ($conn->query($sql) === TRUE) {
            // Jika sukses, arahkan ke login dengan pesan
            echo "<script>alert('Pendaftaran Berhasil! Silakan Login.'); window.location.href='login.php';</script>";
            exit();
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru - DapurBAYU</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-box" style="width: 350px;"> <h2>Buat Akun Baru</h2>
        <p>Bergabunglah untuk mulai belanja.</p>
        
        <?php if($message): ?>
            <p style="color:red; font-size: 14px;"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email Aktif" required>
            <input type="password" name="password" placeholder="Password" required>
            <textarea name="address" placeholder="Alamat Lengkap Pengiriman" rows="3" required></textarea>
            
            <button type="submit" name="register" class="btn-black">DAFTAR SEKARANG</button>
        </form>

        <div style="margin-top: 20px; font-size: 14px;">
            Sudah punya akun? <a href="login.php" style="font-weight:bold; text-decoration:underline;">Masuk di sini</a>
        </div>
    </div>
</body>
</html>