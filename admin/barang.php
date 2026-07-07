<?php
require_once '../config.php';
requireLogin();
if ($_SESSION['user_role'] !== 'admin') { header('Location: ../dashWar.php'); exit; }

$success = $error = '';

// Tambah barang
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama   = trim($_POST['nama_barang']);
    $satuan = trim($_POST['satuan']);
    $stok   = (int)$_POST['stok_sistem'];
    if ($nama && $satuan) {
        $stmt = mysqli_prepare($conn, "INSERT INTO barang (nama_barang, satuan, stok_sistem) VALUES (?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssi', $nama, $satuan, $stok);
        if (mysqli_stmt_execute($stmt)) $success = "Barang \"$nama\" berhasil ditambahkan.";
        else $error = "Gagal menambah barang.";
    } else { $error = "Harap isi nama dan satuan."; }
}

// Edit barang
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id     = (int)$_POST['id'];
    $nama   = trim($_POST['nama_barang']);
    $satuan = trim($_POST['satuan']);
    $stok   = (int)$_POST['stok_sistem'];
    $stmt   = mysqli_prepare($conn, "UPDATE barang SET nama_barang=?, satuan=?, stok_sistem=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssii', $nama, $satuan, $stok, $id);
    if (mysqli_stmt_execute($stmt)) $success = "Barang berhasil diperbarui.";
    else $error = "Gagal memperbarui barang.";
}

// Hapus barang
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ((function() use ($conn, $id) {
        $stmt_d = mysqli_prepare($conn, "DELETE FROM barang WHERE id=?");
        mysqli_stmt_bind_param($stmt_d, 'i', $id);
        mysqli_stmt_execute($stmt_d);
    })()) $success = "Barang berhasil dihapus.";
    else $error = "Tidak bisa menghapus, barang sudah punya data opname.";
}

$barang_list = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Barang - Admin KALA Coffee</title>
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
            <a href="user.php" class="nav-item"><img src="../poto/notif.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Manajemen User</span></a>
            <a href="barang.php" class="nav-item active"><img src="../poto/input.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Manajemen Barang</span></a>
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
                <h1>Manajemen Barang</h1>
                <p>Kelola daftar barang yang tersedia di sistem</p>
            </div>

            <?php if ($success): ?>
                <div style="background:#dcfce7;color:#166534;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">✅ <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background:#fee2e2;color:#dc2626;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">❌ <?= $error ?></div>
            <?php endif; ?>

            <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
                <button class="btn-primary" style="width:auto;padding:0.6rem 1.25rem;" onclick="bukaModal('modalTambah')">
                    ＋ Tambah Barang
                </button>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h2>Daftar Barang</h2>
                    <p>Semua barang yang terdaftar di inventori</p>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Barang</th>
                                <th>Satuan</th>
                                <th class="text-center">Stok Sistem</th>
                                <th>Status Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no=1; while ($b = mysqli_fetch_assoc($barang_list)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                                <td><?= htmlspecialchars($b['satuan']) ?></td>
                                <td class="text-center"><?= $b['stok_sistem'] ?></td>
                                <td>
                                    <?php if ($b['stok_sistem'] < 10): ?>
                                        <span style="background:#fee2e2;color:#dc2626;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.75rem;">Kritis</span>
                                    <?php elseif ($b['stok_sistem'] < 30): ?>
                                        <span style="background:#fef3c7;color:#92400e;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.75rem;">Hampir Habis</span>
                                    <?php else: ?>
                                        <span style="background:#dcfce7;color:#166534;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.75rem;">Aman</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="action-btn edit" onclick="bukaEdit(<?= htmlspecialchars(json_encode($b)) ?>)">✏️ Edit</button>
                                    <a href="?hapus=<?= $b['id'] ?>" class="action-btn delete" onclick="return confirm('Hapus barang ini?')">🗑️ Hapus</a>
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
        <h3>Tambah Barang Baru</h3>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group"><label>Nama Barang</label><input type="text" name="nama_barang"></div>
            <div class="form-group">
                <label>Satuan</label>
                <select name="satuan">
                    <option value="pcs">pcs</option>
                    <option value="gram">gram</option>
                    <option value="kg">kg</option>
                    <option value="liter">liter</option>
                    <option value="ml">ml</option>
                    <option value="botol">botol</option>
                    <option value="kaleng">kaleng</option>
                    <option value="pak">pak</option>
                </select>
            </div>
            <div class="form-group"><label>Stok Awal</label><input type="number" name="stok_sistem" value="0" min="0"></div>
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
        <h3>Edit Barang</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group"><label>Nama Barang</label><input type="text" name="nama_barang" id="edit_nama" required></div>
            <div class="form-group">
                <label>Satuan</label>
                <select name="satuan" id="edit_satuan">
                    <option value="pcs">pcs</option>
                    <option value="gram">gram</option>
                    <option value="kg">kg</option>
                    <option value="liter">liter</option>
                    <option value="ml">ml</option>
                    <option value="botol">botol</option>
                    <option value="kaleng">kaleng</option>
                    <option value="pak">pak</option>
                </select>
            </div>
            <div class="form-group"><label>Stok Sistem</label><input type="number" name="stok_sistem" id="edit_stok" min="0"></div>
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

function bukaEdit(b) {
    document.getElementById('edit_id').value   = b.id;
    document.getElementById('edit_nama').value = b.nama_barang;
    document.getElementById('edit_stok').value = b.stok_sistem;
    document.getElementById('edit_satuan').value = b.satuan;
    bukaModal('modalEdit');
}

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
</script>
</body>
</html>