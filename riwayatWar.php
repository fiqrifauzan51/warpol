<?php
require_once 'config.php';
requireLogin();

$search        = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? 'Semua';

$where = "WHERE 1=1";
if ($search) { $s = mysqli_real_escape_string($conn, $search); $where .= " AND (b.nama_barang LIKE '%$s%' OR u.nama LIKE '%$s%')"; }
if ($filter_status !== 'Semua') { $fs = strtolower(mysqli_real_escape_string($conn, $filter_status)); $where .= " AND so.status = '$fs'"; }

$data = mysqli_query($conn, "
    SELECT so.created_at, b.nama_barang, so.stok_sistem, so.stok_fisik,
           so.selisih, u.nama, so.keterangan, so.status
    FROM stok_opname so
    JOIN barang b ON so.barang_id = b.id
    JOIN users u ON so.user_id = u.id
    $where ORDER BY so.created_at DESC
");
$total = mysqli_num_rows($data);

$ring = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, SUM(selisih < 0) as negatif, SUM(selisih > 0) as positif, SUM(selisih = 0) as sesuai FROM stok_opname"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Stok - Warpol</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="logo.png" alt="Logo" class="logo-img">
                <div class="logo-text"><h2>Warpol</h2><p>Coffee Shop</p></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashWar.php" class="nav-item">
                <span>Dashboard</span>
            </a>
            <a href="inputWar.php" class="nav-item">
                <img src="input.png" alt="" class="nav-img-icon">
                <span>Input Stok Opname</span>
            </a>
            <a href="riwayatWar.php" class="nav-item active">
                <img src="riwayat.png" alt="" class="nav-img-icon">
                <span>Riwayat Stok</span>
            </a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Selamat datang, <?= htmlspecialchars($_SESSION['user_nama']) ?></h1>
                <p>Petugas - Warpol</p>
            </div>
            <div class="header-right">
                <button class="notification-btn">
                    <img src="notif.png" alt="Notifikasi" class="header-img-icon">
                    <span class="notification-badge"></span>
                </button>
                <a href="logout.php" class="logout-btn">
                    <img src="keluar.jpg" alt="Keluar" class="header-img-icon">
                    <span>Keluar</span>
                </a>
            </div>
        </header>

        <main class="page-content">
            <div class="page-header">
                <h1>Riwayat Stok Opname</h1>
                <p>Lihat dan kelola riwayat stok opname barang</p>
            </div>

            <div class="summary-grid">
                <div class="summary-card"><p>Total Opname</p><h3><?= $ring['total'] ?></h3></div>
                <div class="summary-card"><p>Selisih Negatif</p><h3 style="color:#dc2626;"><?= $ring['negatif'] ?></h3></div>
                <div class="summary-card"><p>Selisih Positif</p><h3 style="color:#16a34a;"><?= $ring['positif'] ?></h3></div>
                <div class="summary-card"><p>Stok Sesuai</p><h3 style="color:#2563eb;"><?= $ring['sesuai'] ?></h3></div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label>Cari Barang atau Petugas</label>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari berdasarkan nama barang atau petugas...">
                            </div>
                            <div class="form-group">
                                <label>Filter Status</label>
                                <select name="status">
                                    <option value="Semua" <?= $filter_status === 'Semua' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="Selesai" <?= $filter_status === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-export">
                                <img src="cari.png" alt="" class="btn-img-icon"> Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2>Data Riwayat (<?= $total ?> data)</h2></div>
                <div class="card-body overflow-auto">
                    <table class="riwayat-table">
                        <thead>
                            <tr>
                                <th>No</th><th>Tanggal & Waktu</th><th>Nama Barang</th>
                                <th class="text-center">Stok Sistem</th><th class="text-center">Stok Fisik</th>
                                <th class="text-center">Selisih</th><th>Petugas</th><th>Keterangan</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no=1; while ($row = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td class="text-center"><?= $row['stok_sistem'] ?></td>
                                <td class="text-center"><?= $row['stok_fisik'] ?></td>
                                <td class="text-center">
                                    <span class="<?= $row['selisih'] < 0 ? 'text-red' : ($row['selisih'] > 0 ? 'text-green' : 'text-blue') ?>">
                                        <?= ($row['selisih'] > 0 ? '+' : '') . $row['selisih'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                                <td><span class="badge"><?= ucfirst($row['status']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($total === 0): ?>
                            <tr><td colspan="9" style="text-align:center;color:#6b7280;padding:2rem;">Tidak ada data ditemukan.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>