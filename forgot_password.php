<?php
include 'db.php'; 

$message = "";

if (isset($_POST['find_user'])) {
    $username = $conn->real_escape_string($_POST['username']);

    $sql = "SELECT id FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $user_id = $row['id'];

        $reset_token = base64_encode($user_id);

        header("Location: reset_password.php?token=" . $reset_token);
        exit();
        
    } else {
        $message = "Username tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Lupa Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-box" style="width: 350px;">
        <h2>Lupa Password?</h2>
        <p>Masukkan username Anda untuk mengatur ulang password.</p>
        
        <?php if($message): ?>
            <p style="color:red; font-size: 14px;"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username Anda" required>
            <button type="submit" name="find_user" class="btn-black">Cari Akun</button>
        </form>
        
        <div style="margin-top: 15px; font-size: 14px;">
            <a href="login.php">Kembali ke Login</a>
        </div>
    </div>
</body>
</html>