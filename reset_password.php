<?php
include 'db.php'; 

$message = "";
$user_id = null;

// Validasi Token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $decoded_id = base64_decode($token);
    
    // Pastikan ID yang didekode adalah integer yang valid
    if (filter_var($decoded_id, FILTER_VALIDATE_INT)) {
        $user_id = (int)$decoded_id;
        
        // Optional: Cek apakah ID user benar-benar ada di database
        $check_sql = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $check_sql->bind_param("i", $user_id);
        $check_sql->execute();
        if ($check_sql->get_result()->num_rows === 0) {
            $user_id = null;
        }
        $check_sql->close();

    }
}

// Jika user_id tidak valid, hentikan
if (is_null($user_id)) {
    $message = "Tautan reset tidak valid atau kedaluwarsa.";
}


// Logic Ganti Password
if ($user_id !== null && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = "Password baru dan konfirmasi password tidak cocok.";
    } elseif (strlen($new_password) < 5) {
        $message = "Password harus memiliki minimal 5 karakter.";
    } else {
        // Hash password baru
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password di database
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $message = "<span style='color: green;'>Password berhasil diubah! Silakan <a href=\"login.php\">Login</a>.</span>";
            // Setelah berhasil ganti password, batalkan user_id agar form tidak muncul lagi
            $user_id = null; 
        } else {
            $message = "Gagal mengubah password: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-box" style="width: 350px;">
        <h2>Ganti Password Baru</h2>
        
        <?php echo $message; ?>

        <?php if ($user_id !== null): // Tampilkan form hanya jika token valid ?>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                
                <label for="new_password">Password Baru:</label>
                <input type="password" name="new_password" id="new_password" required>
                
                <label for="confirm_password">Konfirmasi Password Baru:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>

                <button type="submit" name="reset_password" class="btn-black">GANTI PASSWORD</button>
            </form>
        <?php endif; ?>
        
        <div style="margin-top: 15px; font-size: 14px;">
            <a href="login.php">Kembali ke Login</a>
        </div>
    </div>
</body>
</html>