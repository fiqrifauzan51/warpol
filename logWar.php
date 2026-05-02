<?php
require_once 'config.php';
if (isset($_SESSION['user_id'])) { header('Location: dashWar.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = mysqli_prepare($conn, "SELECT id, nama, password, role FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'dashWar.php'));
            exit;
        } else { $error = 'Username atau password salah.'; }
    } else { $error = 'Harap isi semua field.'; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Coffe Warpol</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="logo-container">
            <!-- Logo: cofe.jpg, bulat 90px -->
            <img src="logo.png" alt="Coffe Warpol"
                 style="width:90px;height:90px;object-fit:cover;border-radius:50%;margin-bottom:1rem;border:3px solid #14b8a6;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
            <h1>Coffe Warpol</h1>
            <p class="subtitle">Sistem Manajemen Stok Opname</p>
        </div>

        <?php if ($error): ?>
            <div style="background:#fee2e2;color:#dc2626;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email / Nama Pengguna</label>
                <input type="text" name="username" placeholder="Masukkan username" required>
            </div>
            <div class="form-group">
                <label>Kata Sandi</label>
                <input type="password" name="password" placeholder="Masukkan kata sandi" required>
            </div>
            <button type="submit" class="btn-login">Masuk</button>
            <p class="footer-text">Belum punya akun? Hubungi administrator</p>
        </form>
    </div>
</body>
</html>