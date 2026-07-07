<?php
require_once '../config.php';
requireLogin();
if ($_SESSION['user_role'] !== 'admin') { header('Location: ../dashWar.php'); exit; }

$success = $error = '';

// Tambah user
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    if ($nama && $username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssss', $nama, $username, $hash, $role);
        if (mysqli_stmt_execute($stmt)) $success = "User \"$nama\" berhasil ditambahkan.";
        else $error = "Username sudah dipakai atau terjadi kesalahan.";
    } else { $error = "Harap isi semua field."; }
}

// Edit user
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id       = (int)$_POST['id'];
    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $role     = $_POST['role'];
    $password = $_POST['password'];

    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET nama=?, username=?, password=?, role=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssssi', $nama, $username, $hash, $role, $id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET nama=?, username=?, role=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssi', $nama, $username, $role, $id);
    }
    if (mysqli_stmt_execute($stmt)) $success = "User berhasil diperbarui.";
    else $error = "Gagal memperbarui user.";
}

// Hapus user
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id !== (int)$_SESSION['user_id']) {
        (function() use ($conn, $id) {
        $stmt_d = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
        mysqli_stmt_bind_param($stmt_d, 'i', $id);
        mysqli_stmt_execute($stmt_d);
    })();
        $success = "User berhasil dihapus.";
    } else { $error = "Tidak bisa menghapus akun sendiri."; }
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY role, nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Admin KALA Coffee</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
<div class="container">
    <aside class="sidebar admin">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../poto/logo.png" alt="Logo" style="width:42px;height:42px;object-fit:cover;border-radius:50%;border:2px solid rgba(255,255,255,0.5);">
                <div class="logo-text"><h2>KALA Coffee</h2><p>Admin Panel</p></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><span>Dashboard</span></a>
            <a href="user.php" class="nav-item active"><img src="../poto/notif.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Manajemen User</span></a>
            <a href="barang.php" class="nav-item"><img src="../poto/input.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Manajemen Barang</span></a>
            <a href="orders.php" class="nav-item"><img src="../poto/input.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Order Masuk</span></a>
            <a href="riwayatOrder.php" class="nav-item"><img src="../poto/riwayat.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Riwayat Order</span></a>
            <a href="laporan.php" class="nav-item"><img src="../poto/riwayat.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Laporan</span></a>
            <a href="../dashWar.php" class="nav-item"><img src="../poto/stok.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Mode Petugas</span></a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1><?= htmlspecialchars($_SESSION['user_nama']) ?> <span class="admin-badge">ADMIN</span></h1>
                <p>Panel Administrator - KALA Coffee</p>
            </div>
            <div class="header-right">
                <a href="../logout.php" class="logout-btn"><img src="../poto/keluar.jpg" alt="Keluar" style="width:24px;height:24px;object-fit:cover;border-radius:5px;"><span>Keluar</span></a>
            </div>
        </header>

        <main class="page-content">
            <div class="page-header">
                <h1>Manajemen User</h1>
                <p>Kelola akun admin dan petugas</p>
            </div>

            <?php if ($success): ?>
                <div style="background:#dcfce7;color:#166534;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">✅ <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background:#fee2e2;color:#dc2626;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">❌ <?= $error ?></div>
            <?php endif; ?>

            <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
                <button class="btn-primary" style="width:auto;padding:0.6rem 1.25rem;" onclick="bukaModal('modalTambah')">
                    ＋ Tambah User
                </button>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h2>Daftar Pengguna</h2>
                    <p>Semua akun yang terdaftar di sistem</p>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no=1; while ($u = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($u['nama']) ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><span class="role-badge <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <button class="action-btn edit" onclick="bukaEdit(<?= htmlspecialchars(json_encode($u)) ?>)">✏️ Edit</button>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <a href="?hapus=<?= $u['id'] ?>" class="action-btn delete" onclick="return confirm('Hapus user ini?')">🗑️ Hapus</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal">
        <button class="modal-close" onclick="tutupModal('modalTambah')">✕</button>
        <h3>Tambah User Baru</h3>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" required placeholder="Masukan Nama Lengkap"></div>
            <div class="form-group"><label>Username</label><input type="text" name="username" required placeholder="Masukan username"></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required placeholder="Minimal 6 karakter"></div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="petugas">Petugas</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="tutupModal('modalTambah')">Batal</button>
                <button type="submit" class="btn-primary" style="width:auto;padding:0.6rem 1.25rem;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal">
        <button class="modal-close" onclick="tutupModal('modalEdit')">✕</button>
        <h3>Edit User</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" id="edit_nama" required></div>
            <div class="form-group"><label>Username</label><input type="text" name="username" id="edit_username" required></div>
            <div class="form-group"><label>Password Baru </small></label><input type="password" name="password" placeholder="Kosongkan jika tidak diubah"></div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="edit_role">
                    <option value="petugas">Petugas</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="tutupModal('modalEdit')">Batal</button>
                <button type="submit" class="btn-primary" style="width:auto;padding:0.6rem 1.25rem;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id) { document.getElementById(id).classList.add('active'); }
function tutupModal(id) { document.getElementById(id).classList.remove('active'); }

function bukaEdit(user) {
    document.getElementById('edit_id').value       = user.id;
    document.getElementById('edit_nama').value     = user.nama;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_role').value     = user.role;
    bukaModal('modalEdit');
}

// Tutup modal kalau klik di luar
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
</script>
</body>
</html>