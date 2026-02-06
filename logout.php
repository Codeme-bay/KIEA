<?php
// Pastikan session sudah dimulai
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Jika menggunakan session cookie, hapus juga cookie-nya
// Catatan: Ini akan menghancurkan session, bukan hanya data session!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Arahkan (redirect) pengguna ke halaman utama
header("Location: index.php");
exit;
?>