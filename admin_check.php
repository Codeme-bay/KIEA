<?php
// Catatan: Pastikan 'db.php' sudah di-include di file pemanggil
// karena 'db.php' yang memulai session_start() dan mendefinisikan koneksi ($conn)

// 1. Cek apakah pengguna sudah login (user_id ada di sesi)
// ATAU
// 2. Cek apakah role pengguna BUKAN 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    
    // Jika salah satu kondisi di atas terpenuhi (Tidak login atau Bukan Admin),
    // alihkan pengguna kembali ke halaman utama (index.php)
    header("Location: index.php"); 
    
    // Hentikan eksekusi skrip agar kode halaman admin tidak dijalankan
    exit();
}

// Jika pengguna adalah admin, skrip akan melanjutkan ke kode berikutnya
// di file admin_dashboard.php atau admin_edit.php
?>