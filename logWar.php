<?php
require_once 'config.php';

// Kalau sudah login → redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'dashWar.php'));
    exit;
}

$error  = '';
$locked = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ── Cek lockout ──────────────────────────────────────────────────────────
    if (isLoginLocked($conn)) {
        $locked  = true;
        $minutes = lockoutMinutesLeft($conn);
        $error   = "Terlalu banyak percobaan login. Coba lagi dalam <b>$minutes menit</b>.";
    } elseif ($username && $password) {
        // ── Cek kredensial (prepared statement) ──────────────────────────────
        $stmt = mysqli_prepare($conn,
            "SELECT id, nama, password, role FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user && password_verify($password, $user['password'])) {
            // Berhasil → bersihkan attempts, set session
            clearLoginAttempts($conn);
            session_regenerate_id(true);   // cegah session fixation
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashWar.php'));
            exit;
        } else {
            // Gagal → catat percobaan
            recordFailedLogin($conn, $username);
            $remaining = LOGIN_MAX_ATTEMPTS
                       - mysqli_fetch_assoc(mysqli_query($conn,
                           "SELECT COUNT(*) AS c FROM login_attempts
                            WHERE ip = '" . mysqli_real_escape_string($conn, getClientIP()) . "'
                              AND attempted_at > NOW() - INTERVAL " . LOGIN_LOCKOUT_MINUTES . " MINUTE"
                         ))['c'];

            if ($remaining <= 0) {
                $locked = true;
                $error  = "Terlalu banyak percobaan login. Akun dikunci selama <b>" . LOGIN_LOCKOUT_MINUTES . " menit</b>.";
            } else {
                $error = "Username atau password salah. Sisa percobaan: <b>$remaining</b>.";
            }
        }
    } else {
        $error = 'Harap isi semua field.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KALA Coffee</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-page">
    <div class="bg-3d" aria-hidden="true">
        <span class="cup" style="--left:10%;--top:20%;--size:160px;--delay:0s;--duration:14s;"><span class="cup-inner"><svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><g fill="none" fill-rule="evenodd"><ellipse cx="60" cy="88" rx="46" ry="10" fill="rgba(20,24,28,0.12)"/><path d="M28 78c0-18 8-30 32-30s32 12 32 30" fill="#fff" stroke="#f3c6a5" stroke-width="2"/><path d="M40 44c6-6 20-8 36 0" stroke="#8b5e34" stroke-width="3" stroke-linecap="round"/><g stroke="#8b5e34" stroke-width="2" stroke-linecap="round"><path d="M86 58c8 0 12-6 12-12s-6-10-12-10"/></g></g></svg><span class="steam" aria-hidden="true"><i></i><i></i><i></i></span></span></span>
        <span class="cup" style="--left:75%;--top:10%;--size:120px;--delay:2s;--duration:12s;"><span class="cup-inner"><svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><g fill="none" fill-rule="evenodd"><ellipse cx="60" cy="86" rx="36" ry="8" fill="rgba(20,24,28,0.12)"/><path d="M30 66c0-14 6-22 30-22s30 8 30 22" fill="#fff" stroke="#f3c6a5" stroke-width="2"/><path d="M44 46c5-5 16-7 28 0" stroke="#8b5e34" stroke-width="2.5" stroke-linecap="round"/></g></svg><span class="steam" aria-hidden="true"><i></i><i></i></span></span></span>
        <span class="bean" style="--left:5%;--top:40%;--size:18px;--delay:0s;"></span>
        <span class="bean" style="--left:18%;--top:15%;--size:22px;--delay:1s;"></span>
        <span class="bean" style="--left:70%;--top:42%;--size:18px;--delay:1.8s;"></span>
        <span class="bean" style="--left:88%;--top:28%;--size:16px;--delay:2.5s;"></span>
    </div>

    <div class="login-card">
        <div class="logo-container">
            <img src="poto/logo.png" alt="KALA Coffee"
                 style="width:90px;height:90px;object-fit:cover;border-radius:50%;margin-bottom:1rem;border:3px solid #0B448C;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
            <h1>KALA Coffee</h1>
            <p class="subtitle">Sistem Manajemen Stok Opname</p>
        </div>

        <?php if ($error): ?>
            <div style="background:<?= $locked ? '#fef3c7' : '#fee2e2' ?>;
                        color:<?= $locked ? '#92400e' : '#dc2626' ?>;
                        padding:0.75rem 1rem;border-radius:0.5rem;
                        margin-bottom:1rem;font-size:0.875rem;
                        border:1px solid <?= $locked ? '#fde68a' : '#fecaca' ?>;">
                <?= $locked ? '🔒 ' : '❌ ' ?><?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label>Email / Nama Pengguna</label>
                <input type="text" name="username"
                       placeholder="Masukkan username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       <?= $locked ? 'disabled' : '' ?> required>
            </div>
            <div class="form-group">
                <label>Kata Sandi</label>
                <input type="password" name="password"
                       placeholder="Masukkan kata sandi"
                       <?= $locked ? 'disabled' : '' ?> required>
            </div>
            <button type="submit" class="btn-login" <?= $locked ? 'disabled style="opacity:0.6;cursor:not-allowed;"' : '' ?>>
                <?= $locked ? '🔒 Akun Terkunci' : 'Masuk' ?>
            </button>
            <p class="footer-text">Belum punya akun? Hubungi administrator</p>
        </form>
    </div>
</body>
</html>