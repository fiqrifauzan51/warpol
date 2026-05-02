<?php
// config.php — Koneksi database

define('DB_HOST', 'localhost');
define('DB_USER', 'root');  
define('DB_PASS', '');       
define('DB_NAME', 'warpol_db');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Koneksi database gagal: ' . mysqli_connect_error()
    ]));
}

mysqli_set_charset($conn, 'utf8mb4');

// Mulai session di semua halaman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: cek apakah sudah login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: logWar.php');
        exit;
    }
}
?>
