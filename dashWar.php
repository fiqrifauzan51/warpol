<?php
require_once 'config.php';
requireLogin();

$total_opname  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM stok_opname"))['total'];
$stok_oke      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM stok_opname WHERE selisih >= 0"))['total'];
$persen_target = $total_opname > 0 ? round(($stok_oke / $total_opname) * 100) : 0;
$hampir_habis  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM barang WHERE stok_sistem < 30"))['total'];

$aktivitas = mysqli_query($conn, "
    SELECT so.created_at, b.nama_barang, so.stok_sistem, so.stok_fisik, so.selisih, u.nama, so.status
    FROM stok_opname so
    JOIN barang b ON so.barang_id = b.id
    JOIN users u ON so.user_id = u.id
    ORDER BY so.created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Coffe Warpol</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .img-logo-sidebar {
            width: 42px; height: 42px;
            object-fit: cover; border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.5);
            flex-shrink: 0;
        }
        .img-nav {
            width: 26px; height: 26px;
            object-fit: cover; border-radius: 6px;
            flex-shrink: 0;
        }
        .img-header {
            width: 24px; height: 24px;
            object-fit: cover; border-radius: 5px;
        }
        .img-stat {
            width: 32px; height: 32px;
            object-fit: cover; border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="container">

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="logo.png" alt="Logo" class="img-logo-sidebar">
                <div class="logo-text"><h2>Warpol</h2><p>Coffee Shop</p></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashWar.php" class="nav-item active">
            
                <span>Dashboard</span>
            </a>
            <a href="inputWar.php" class="nav-item">
                <img src="input.png" alt="" class="img-nav">
                <span>Input Stok Opname</span>
            </a>
            <a href="riwayatWar.php" class="nav-item">
                <img src="riwayat.png" alt="" class="img-nav">
                <span>Riwayat Stok</span>
            </a>
        </nav>
    </aside>

    <div class="main-content">

        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <h1>Selamat datang, <?= htmlspecialchars($_SESSION['user_nama']) ?></h1>
                <p>Petugas - Warpol</p>
            </div>
            <div class="header-right">
                <button class="notification-btn" title="Notifikasi">
                    <img src="notif.png" alt="Notifikasi" class="img-header">
                    <span class="notification-badge"></span>
                </button>
                <a href="logout.php" class="logout-btn">
                    <img src="keluar.jpg" alt="Keluar" class="img-header">
                    <span>Keluar</span>
                </a>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="page-header">
                <h1>Dashboard Petugas</h1>
                <p>Kelola dan pantau stok opname barang coffee shop Warpol</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info"><p>Input Opname</p><h3><?= $total_opname ?></h3><small>Total keseluruhan</small></div>
                        <div class="stat-icon blue">
                            <img src="input.png" alt="" class="img-stat">
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info"><p>Target Tercapai</p><h3><?= $persen_target ?>%</h3><small>Stok sesuai / lebih</small></div>
                        <div class="stat-icon green">
                            <img src="target.png" alt="" class="img-stat">
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="stat-info"><p>Stok Hampir Habis</p><h3><?= $hampir_habis ?></h3><small>Perlu restock</small></div>
                        <div class="stat-icon red">
                            <img src="stok.png" alt="" class="img-stat">
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header"><h2>Aktivitas Opname Terbaru</h2><p>5 data stok opname terakhir</p></div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th><th>Nama Barang</th>
                                <th class="text-center">Stok Awal</th><th class="text-center">Stok Akhir</th>
                                <th class="text-center">Selisih</th><th>Petugas</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = mysqli_fetch_assoc($aktivitas)): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td class="text-center"><?= $row['stok_sistem'] ?></td>
                                <td class="text-center"><?= $row['stok_fisik'] ?></td>
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
        </main>
    </div>
</div>
</body>
</html>