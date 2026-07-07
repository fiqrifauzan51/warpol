<?php
require_once 'config.php';
requireLogin();

$success = $error = '';
$barang_list = mysqli_query($conn, "SELECT id, nama_barang, stok_sistem FROM barang ORDER BY nama_barang");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barang_id   = (int)($_POST['barang_id'] ?? 0);
    $stok_sistem = (int)($_POST['stok_sistem'] ?? 0);
    $stok_fisik  = (int)($_POST['stok_fisik'] ?? 0);
    $keterangan  = trim($_POST['keterangan'] ?? '');
    $user_id     = $_SESSION['user_id'];

    if ($barang_id && $stok_sistem >= 0 && $stok_fisik >= 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO stok_opname (barang_id, user_id, stok_sistem, stok_fisik, keterangan, status) VALUES (?, ?, ?, ?, ?, 'selesai')");
        mysqli_stmt_bind_param($stmt, 'iiiss', $barang_id, $user_id, $stok_sistem, $stok_fisik, $keterangan);
        if (mysqli_stmt_execute($stmt)) {
            $stmt_upd = mysqli_prepare($conn, "UPDATE barang SET stok_sistem = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_upd, 'ii', $stok_fisik, $barang_id);
            mysqli_stmt_execute($stmt_upd);
            $success = 'Data stok opname berhasil disimpan!';
        } else { $error = 'Gagal menyimpan data.'; }
    } else { $error = 'Harap isi semua field dengan benar.'; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Stok Opname - KALA Coffee</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .img-logo-sidebar { width:42px;height:42px;object-fit:cover;border-radius:50%;border:2px solid rgba(255,255,255,0.5);flex-shrink:0; }
        .img-nav          { width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0; }
        .img-header       { width:24px;height:24px;object-fit:cover;border-radius:5px; }
        .img-btn          { width:20px;height:20px;object-fit:cover;border-radius:4px; }
    </style>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="poto/logo.png" alt="Logo" class="img-logo-sidebar">
                <div class="logo-text"><h2>KALA Coffee</h2><p>Coffee Shop</p></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashWar.php" class="nav-item">
                <span>Dashboard</span>
            </a>

            <div class="nav-group open" id="group-stok-opname">
                <button type="button" class="nav-group-toggle" data-target="stokOpnameMenu" aria-expanded="true">
                    <img src="poto/input.png" alt="">
                    <span>Stok Opname</span>
                    <span class="chevron">▸</span>
                </button>
                <div class="nav-group-menu" id="stokOpnameMenu">
                    <a href="inputWar.php" class="nav-sub-item active">
                        <img src="poto/input.png" alt="">
                        <span>Input Stok Opname</span>
                    </a>
                    <a href="riwayatWar.php" class="nav-sub-item">
                        <img src="poto/riwayat.png" alt="">
                        <span>Riwayat Stok</span>
                    </a>
                </div>
            </div>

            <div class="nav-group" id="group-order-barang">
                <button type="button" class="nav-group-toggle" data-target="orderMenu" aria-expanded="false">
                    <img src="poto/input.png" alt="">
                    <span>Order Barang</span>
                    <span class="chevron">▸</span>
                </button>
                <div class="nav-group-menu" id="orderMenu">
                    <a href="orderBarang.php" class="nav-sub-item">
                        <img src="poto/input.png" alt="">
                        <span>Order Barang</span>
                    </a>
                    <a href="riwayatOrder.php" class="nav-sub-item">
                        <img src="poto/riwayat.png" alt="">
                        <span>Riwayat Order</span>
                    </a>
                </div>
            </div>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="hamburger-btn" id="hamburger-btn">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1>Selamat datang, <?= htmlspecialchars($_SESSION['user_nama']) ?></h1>
                <p>Petugas - KALA Coffee</p>
            </div>
            <div class="header-right">
                <button class="notification-btn">
                    <img src="poto/notif.png" alt="Notifikasi" class="img-header">
                    <span class="notification-badge"></span>
                </button>
                <a href="logout.php" class="logout-btn">
                    <img src="poto/keluar.jpg" alt="Keluar" class="img-header">
                    <span>Keluar</span>
                </a>
            </div>
        </header>

        <main class="page-content">
            <div class="page-header">
                <h1>Input Stok Opname</h1>
                <p>Masukkan data stok opname barang coffee shop KALA Coffe</p>
            </div>

            <div class="grid-layout">
                <div class="card-padded">
                    <h2>Form Input Stok Opname</h2>

                    <?php if ($success): ?>
                        <div style="background:#dcfce7;color:#166534;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">✅ <?= $success ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div style="background:#fee2e2;color:#dc2626;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">❌ <?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" id="formStok">
                        <div class="form-group">
                            <label>Nama Barang</label>
                            <select name="barang_id" required onchange="isiStokSistem(this)">
                                <option value="">Pilih Barang</option>
                                <?php while ($b = mysqli_fetch_assoc($barang_list)): ?>
                                    <option value="<?= $b['id'] ?>" data-stok="<?= $b['stok_sistem'] ?>">
                                        <?= htmlspecialchars($b['nama_barang']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Stok Sistem</label>
                                <input type="number" id="stok_sistem" name="stok_sistem" placeholder="0" required min="0" readonly
                                       style="background-color:#e9ecef;cursor:not-allowed;color:#6b7280;" onchange="hitungSelisih()">
                                <p class="help-text" style="color:#dc2626;">🔒 Hanya bisa diubah oleh Admin</p>
                                <p class="help-text">Otomatis terisi saat pilih barang</p>
                            </div>
                            <div class="form-group">
                                <label>Stok Fisik</label>
                                <input type="number" id="stok_fisik" name="stok_fisik" placeholder="0" required min="0" onchange="hitungSelisih()">
                                <p class="help-text">Stok hasil perhitungan fisik</p>
                            </div>
                        </div>
                        <div id="selisihBox" style="display:none;"></div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" rows="4" placeholder="Masukkan keterangan tambahan (opsional)"></textarea>
                        </div>
                        <div class="btn-group">
                            <button type="submit" class="btn-primary">
                                <img src="poto/simpan.png" alt="" class="img-btn">
                                <span>Simpan Data</span>
                            </button>
                            <button type="button" class="btn-secondary" onclick="resetForm()">Reset</button>
                        </div>
                    </form>
                </div>

                <div class="info-panel">
                    <div class="card-padded info-card">
                        <h3>Petunjuk</h3>
                        <ul class="steps-list">
                            <li><span class="step-number">1</span><span>Pilih nama barang yang akan di-opname</span></li>
                            <li><span class="step-number">2</span><span>Stok sistem otomatis terisi dari database</span></li>
                            <li><span class="step-number">3</span><span>Hitung stok fisik di gudang/rak</span></li>
                            <li><span class="step-number">4</span><span>Sistem akan menghitung selisih otomatis</span></li>
                            <li><span class="step-number">5</span><span>Tambahkan keterangan jika diperlukan</span></li>
                        </ul>
                    </div>
                    <div class="tips-card">
                        <h3>Tips</h3>
                        <p>Lakukan stok opname secara berkala untuk menjaga akurasi data inventori dan mencegah kehilangan barang.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
function isiStokSistem(select) {
    document.getElementById('stok_sistem').value = select.options[select.selectedIndex].dataset.stok || '';
    hitungSelisih();
}
function hitungSelisih() {
    const ss = parseInt(document.getElementById('stok_sistem').value) || 0;
    const sf = parseInt(document.getElementById('stok_fisik').value) || 0;
    const box = document.getElementById('selisihBox');
    if (document.getElementById('stok_fisik').value !== '') {
        const s = sf - ss;
        box.style.display = 'block';
        box.className = 'selisih-box ' + (s < 0 ? 'negative' : 'positive');
        box.innerHTML = `<div class="selisih-info"><span style="font-size:0.875rem;">Selisih:</span>
            <span class="selisih-value ${s < 0 ? 'negative' : 'positive'}">${s > 0 ? '+' : ''}${s}</span></div>
            <p class="help-text" style="margin-top:0.5rem;">${s < 0 ? 'Stok fisik lebih sedikit dari sistem' : s > 0 ? 'Stok fisik lebih banyak dari sistem' : 'Stok sesuai'}</p>`;
    } else { box.style.display = 'none'; }
}
function resetForm() {
    document.getElementById('formStok').reset();
    document.getElementById('selisihBox').style.display = 'none';
}

document.getElementById('hamburger-btn').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('sidebar-overlay').classList.toggle('open');
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    const hamburger = document.getElementById('hamburger-btn');
    const overlay = document.getElementById('sidebar-overlay');
    if (!sidebar.contains(event.target) && !hamburger.contains(event.target) && window.innerWidth <= 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    }
});

// Collapsible sidebar groups
document.querySelectorAll('.nav-group-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        const targetId = btn.getAttribute('data-target');
        const menu = document.getElementById(targetId);
        if (!menu) return;
        const group = btn.closest('.nav-group');
        const isOpen = group.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});
</script>
</body>
</html>