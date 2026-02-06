<?php
include 'db.php';

$message = "";

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT id, username, password, role FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            
            header("Location: index.php");
            exit();
        } else {
            $message = "Username atau password salah.";
        }
    } else {
        $message = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Akun - DapurBAYU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-box">
        <h2>Masuk ke Akun</h2>
        <p>Silakan login untuk melanjutkan belanja.</p>
        
        <?php if($message): ?>
            <p style="color:red; font-size: 14px;"><?php echo $message; ?></p>
        <?php endif; ?>
    <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login" class="btn-black">MASUK</button>
     </form>
        <div style="margin-top: 10px; text-align: right; font-size: 13px;">
            <a href="forgot_password.php" style="color: #000000ff; text-decoration:underline;">Lupa Password?</a>
        </div>

        <div style="margin-top: 20px; font-size: 14px;">
            Belum punya akun? <a href="register.php" style="font-weight:bold; text-decoration:underline;">Daftar di sini</a>
        </div>
    </div>
</body>
</html>
