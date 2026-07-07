<?php
<<<<<<< HEAD
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
=======
// config.php — Koneksi database

define('DB_HOST', 'localhost');
define('DB_USER', 'root');  
define('DB_PASS', '');       
>>>>>>> 2eca77405b0fc96d6d66db72dbe7cbecffa3b0d3
define('DB_NAME', 'warpol_db');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
<<<<<<< HEAD
    die('<h3 style="color:red;font-family:sans-serif;padding:2rem;">
        Koneksi database gagal: ' . mysqli_connect_error() . '
    </h3>');
=======
    die(json_encode([
        'status' => 'error',
        'message' => 'Koneksi database gagal: ' . mysqli_connect_error()
    ]));
>>>>>>> 2eca77405b0fc96d6d66db72dbe7cbecffa3b0d3
}

mysqli_set_charset($conn, 'utf8mb4');

<<<<<<< HEAD
// ── Buat tabel login_attempts jika belum ada ──────────────────────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS login_attempts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ip         VARCHAR(45)  NOT NULL,
    username   VARCHAR(100) NOT NULL DEFAULT '',
    attempted_at TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip),
    INDEX idx_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Konstanta rate limit ──────────────────────────────────────────────────────
define('LOGIN_MAX_ATTEMPTS', 5);      // maks percobaan salah
define('LOGIN_LOCKOUT_MINUTES', 15);  // lama blokir (menit)

/**
 * Cek apakah IP sedang terkunci karena terlalu banyak percobaan login gagal.
 * @return bool true = masih terkunci
 */
function isLoginLocked($conn) {
    $ip      = getClientIP();
    $window  = LOGIN_LOCKOUT_MINUTES;
    $max     = LOGIN_MAX_ATTEMPTS;
    $ip_safe = mysqli_real_escape_string($conn, $ip);

    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS cnt FROM login_attempts
         WHERE ip = '$ip_safe'
           AND attempted_at > NOW() - INTERVAL $window MINUTE"
    ));
    return (int)$r['cnt'] >= $max;
}

/**
 * Catat satu percobaan login gagal dari IP ini.
 */
function recordFailedLogin($conn, $username = '') {
    $ip       = mysqli_real_escape_string($conn, getClientIP());
    $username = mysqli_real_escape_string($conn, $username);
    mysqli_query($conn,
        "INSERT INTO login_attempts (ip, username) VALUES ('$ip', '$username')"
    );
}

/**
 * Reset (hapus) semua percobaan gagal dari IP ini setelah login berhasil.
 */
function clearLoginAttempts($conn) {
    $ip = mysqli_real_escape_string($conn, getClientIP());
    mysqli_query($conn, "DELETE FROM login_attempts WHERE ip = '$ip'");
}

/**
 * Berapa menit tersisa sampai lockout berakhir.
 */
function lockoutMinutesLeft($conn) {
    $ip     = mysqli_real_escape_string($conn, getClientIP());
    $window = LOGIN_LOCKOUT_MINUTES;
    $max    = LOGIN_MAX_ATTEMPTS;

    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT attempted_at FROM login_attempts
         WHERE ip = '$ip'
           AND attempted_at > NOW() - INTERVAL $window MINUTE
         ORDER BY attempted_at ASC
         LIMIT 1 OFFSET " . ($max - 1)
    ));
    if (!$r) return 0;

    $unlock = strtotime($r['attempted_at']) + LOGIN_LOCKOUT_MINUTES * 60;
    return max(0, (int)ceil(($unlock - time()) / 60));
}

/**
 * Ambil IP client (dengan dukungan proxy dasar).
 */
function getClientIP() {
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

// ── Helpers akses ─────────────────────────────────────────────────────────────
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        $depth = substr_count($_SERVER['PHP_SELF'], '/') - 2;
        $root  = $depth > 0 ? str_repeat('../', $depth) : '';
        header('Location: ' . $root . 'logWar.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        $depth = substr_count($_SERVER['PHP_SELF'], '/') - 2;
        $root  = $depth > 0 ? str_repeat('../', $depth) : '';
        header('Location: ' . $root . 'dashWar.php');
        exit;
    }
}
=======
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
>>>>>>> 2eca77405b0fc96d6d66db72dbe7cbecffa3b0d3
