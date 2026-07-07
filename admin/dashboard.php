<?php
require_once '../config.php';
requireLogin();
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../dashWar.php'); exit;
}

// Statistik
$total_barang   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang"))['n'];
$total_user     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM users"))['n'];
$total_opname   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM stok_opname"))['n'];
$stok_minta     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE stok_sistem < 30"))['n'];

// 5 aktivitas terbaru
$aktivitas = mysqli_query($conn, "
    SELECT so.created_at, b.nama_barang, so.stok_sistem, so.stok_fisik,
           so.selisih, u.nama, so.status
    FROM stok_opname so
    JOIN barang b ON so.barang_id = b.id
    JOIN users u ON so.user_id = u.id
    ORDER BY so.created_at DESC LIMIT 5
");

// Barang hampir habis
$hampir_habis = mysqli_query($conn, "SELECT nama_barang, stok_sistem, satuan FROM barang WHERE stok_sistem < 30 ORDER BY stok_sistem ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - KALA Coffee</title>
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
            <a href="dashboard.php" class="nav-item active"><span>Dashboard</span></a>
            <a href="user.php" class="nav-item"><img src="../poto/notif.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Manajemen User</span></a>
            <a href="barang.php" class="nav-item"><img src="../poto/input.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Manajemen Barang</span></a>
            <a href="orders.php" class="nav-item"><img src="../poto/input.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Order Masuk</span></a>
            <a href="riwayatOrder.php" class="nav-item"><img src="../poto/riwayat.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Riwayat Order</span></a>
            <a href="laporan.php" class="nav-item"><img src="../poto/riwayat.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Laporan</span></a>
            <a href="../dashWar.php" class="nav-item"><img src="../poto/stok.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Mode Petugas</span></a>
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
                <h1><?= htmlspecialchars($_SESSION['user_nama']) ?> <span class="admin-badge">ADMIN</span></h1>
                <p>Panel Administrator - KALA Coffee</p>
            </div>
            <div class="header-right">
                <a href="../logout.php" class="logout-btn"><img src="../poto/keluar.jpg" alt="Keluar" style="width:24px;height:24px;object-fit:cover;border-radius:5px;"><span>Keluar</span></a>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="page-header">
                <h1>Dashboard Admin</h1>
                <p>Pantau seluruh aktivitas sistem KALA Coffee</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info"><p>Total Barang</p><h3><?= $total_barang ?></h3><small>Jenis barang terdaftar</small></div>
                        <div class="stat-icon blue">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                    </div>
                </div>
                <div class="stat-card accent-purple">
                    <div class="stat-card-content">
                        <div class="stat-info"><p>Total Pengguna</p><h3><?= $total_user ?></h3><small>Admin & petugas aktif</small></div>
                        <div class="stat-icon" style="background:#ede9fe;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#7c3aed;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="stat-card accent-amber">
                    <div class="stat-card-content">
                        <div class="stat-info"><p>Total Opname</p><h3><?= $total_opname ?></h3><small>Semua transaksi</small></div>
                        <div class="stat-icon" style="background:#fef3c7;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#d97706;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info"><p>Stok Hampir Habis</p><h3><?= $stok_minta ?></h3><small>Perlu restock segera</small></div>
                        <div class="stat-icon red">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">
                <!-- Aktivitas terbaru -->
                <div class="table-card">
                    <div class="table-header">
                        <h2>Aktivitas Opname Terbaru</h2>
                        <p>5 transaksi terakhir dari semua petugas</p>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Barang</th>
                                    <th class="text-center">Selisih</th>
                                    <th>Petugas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = mysqli_fetch_assoc($aktivitas)): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                    <td class="text-center">
                                        <span class="<?= $row['selisih'] < 0 ? 'text-red' : ($row['selisih'] > 0 ? 'text-green' : 'text-blue') ?>">
                                            <?= ($row['selisih'] > 0 ? '+' : '') . $row['selisih'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['nama']) ?></td>
                                    <td><span class="badge"><?= ucfirst($row['status']) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Barang hampir habis -->
                <div class="table-card">
                    <div class="table-header">
                        <h2>⚠️ Stok Kritis</h2>
                        <p>Barang dengan stok &lt; 30</p>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr><th>Barang</th><th class="text-center">Stok</th></tr>
                            </thead>
                            <tbody>
                            <?php
                            $count = 0;
                            while ($row = mysqli_fetch_assoc($hampir_habis)):
                                $count++;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                    <td class="text-center"><span class="text-red"><?= $row['stok_sistem'] ?> <?= $row['satuan'] ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($count === 0): ?>
                                <tr><td colspan="2" style="text-align:center;color:#6b7280;padding:1.5rem;">Semua stok aman ✅</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
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
</script>

</body>
</html>