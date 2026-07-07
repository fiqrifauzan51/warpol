<?php
require_once '../config.php';
requireLogin();
if ($_SESSION['user_role'] !== 'admin') { header('Location: ../dashWar.php'); exit; }

// Filter
$bulan   = $_GET['bulan']   ?? '';
$dari    = $_GET['dari']    ?? '';
$sampai  = $_GET['sampai']  ?? '';
$barang  = $_GET['barang']  ?? '';
$petugas = $_GET['petugas'] ?? '';

if ($bulan && preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    $dari   = "$bulan-01";
    $sampai = date('Y-m-t', strtotime($dari));
}

if (!$dari) {
    $dari = date('Y-m-01');
}
if (!$sampai) {
    $sampai = date('Y-m-d');
}

$where = "WHERE DATE(so.created_at) BETWEEN '$dari' AND '$sampai'";
if ($barang)  { $b = mysqli_real_escape_string($conn, $barang);  $where .= " AND so.barang_id = '$b'"; }
if ($petugas) { $p = mysqli_real_escape_string($conn, $petugas); $where .= " AND so.user_id = '$p'"; }

$data = mysqli_query($conn, "
    SELECT so.created_at, b.nama_barang, b.satuan, so.stok_sistem, so.stok_fisik,
           so.selisih, u.nama as petugas, so.keterangan, so.status
    FROM stok_opname so
    JOIN barang b ON so.barang_id = b.id
    JOIN users u  ON so.user_id   = u.id
    $where
    ORDER BY so.created_at DESC
");

// Ringkasan periode ini
$ring = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total,
           SUM(selisih < 0) as negatif,
           SUM(selisih > 0) as positif,
           SUM(selisih = 0) as sesuai,
           SUM(selisih) as total_selisih
    FROM stok_opname so $where
"));

$barang_opts  = mysqli_query($conn, "SELECT id, nama_barang FROM barang ORDER BY nama_barang");
$petugas_opts = mysqli_query($conn, "SELECT id, nama FROM users ORDER BY nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin KALA Coffee</title>
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
            <a href="barang.php" class="nav-item"><img src="../poto/input.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Manajemen Barang</span></a>
            <a href="orders.php" class="nav-item"><img src="../poto/input.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Order Masuk</span></a>
            <a href="riwayatOrder.php" class="nav-item"><img src="../poto/riwayat.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Riwayat Order</span></a>
            <a href="laporan.php" class="nav-item active"><img src="../poto/riwayat.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Laporan</span></a>
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
                <button class="logout-btn" onclick="window.print()"><span>🖨️</span><span>Print</span></button>
                <a href="../logout.php" class="logout-btn"><img src="../poto/keluar.jpg" alt="Keluar" style="width:24px;height:24px;object-fit:cover;border-radius:5px;"><span>Keluar</span></a>
            </div>
        </header>

        <main class="page-content">
            <div class="page-header">
                <h1>Laporan Stok Opname</h1>
                <p>Rekap data opname berdasarkan periode, barang, dan petugas</p>
            </div>

            <!-- Filter -->
            <div class="card">
                <div class="card-body">
                    <form method="GET">
                        <div class="laporan-filter">
                            <div class="form-group">
                                <label>Dari Tanggal</label>
                                <input type="date" name="dari" value="<?= $dari ?>">
                            </div>
                            <div class="form-group">
                                <label>Bulan</label>
                                <input type="month" name="bulan" id="bulan" value="<?= htmlspecialchars($bulan) ?>">
                            </div>
                            <div class="form-group">
                                <label>Sampai Tanggal</label>
                                <input type="date" name="sampai" id="sampai" value="<?= $sampai ?>">
                            </div>
                            <div class="form-group">
                                <label>Barang</label>
                                <select name="barang">
                                    <option value="">Semua Barang</option>
                                    <?php while ($opt = mysqli_fetch_assoc($barang_opts)): ?>
                                        <option value="<?= $opt['id'] ?>" <?= $barang == $opt['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opt['nama_barang']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Petugas</label>
                                <select name="petugas">
                                    <option value="">Semua Petugas</option>
                                    <?php while ($opt = mysqli_fetch_assoc($petugas_opts)): ?>
                                        <option value="<?= $opt['id'] ?>" <?= $petugas == $opt['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opt['nama']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn-export" style="align-self:flex-end;">🔍 Tampilkan</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ringkasan -->
            <div class="summary-grid">
                <div class="summary-card">
                    <p>Total Opname</p>
                    <h3><?= $ring['total'] ?></h3>
                </div>
                <div class="summary-card">
                    <p>Selisih Negatif</p>
                    <h3 style="color:#dc2626;"><?= $ring['negatif'] ?></h3>
                </div>
                <div class="summary-card">
                    <p>Selisih Positif</p>
                    <h3 style="color:#16a34a;"><?= $ring['positif'] ?></h3>
                </div>
                <div class="summary-card">
                    <p>Stok Sesuai</p>
                    <h3 style="color:#2563eb;"><?= $ring['sesuai'] ?></h3>
                </div>
                <div class="summary-card">
                    <p>Total Selisih</p>
                    <h3 style="color:<?= $ring['total_selisih'] < 0 ? '#dc2626' : '#16a34a' ?>;">
                        <?= ($ring['total_selisih'] > 0 ? '+' : '') . (int)$ring['total_selisih'] ?>
                    </h3>
                </div>
            </div>

            <!-- Tabel -->
            <div class="table-card">
                <div class="table-header">
                    <h2>Data Laporan</h2>
                    <p>
                        Periode: <?= date('d M Y', strtotime($dari)) ?> – <?= date('d M Y', strtotime($sampai)) ?>
                        <?php if ($bulan): ?>
                            (Bulan: <?= date('F Y', strtotime($bulan . '-01')) ?>)
                        <?php endif; ?>
                    </p>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal & Waktu</th>
                                <th>Nama Barang</th>
                                <th>Satuan</th>
                                <th class="text-center">Stok Sistem</th>
                                <th class="text-center">Stok Fisik</th>
                                <th class="text-center">Selisih</th>
                                <th>Petugas</th>
                                <th>Keterangan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $no = 1;
                        $rows = mysqli_fetch_all($data, MYSQLI_ASSOC);
                        foreach ($rows as $row):
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td><?= htmlspecialchars($row['satuan']) ?></td>
                                <td class="text-center"><?= $row['stok_sistem'] ?></td>
                                <td class="text-center"><?= $row['stok_fisik'] ?></td>
                                <td class="text-center">
                                    <span class="<?= $row['selisih'] < 0 ? 'text-red' : ($row['selisih'] > 0 ? 'text-green' : 'text-blue') ?>">
                                        <?= ($row['selisih'] > 0 ? '+' : '') . $row['selisih'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['petugas']) ?></td>
                                <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                                <td><span class="badge"><?= ucfirst($row['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="10" style="text-align:center;color:#6b7280;padding:2rem;">Tidak ada data pada periode ini.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
const bulanInput = document.getElementById('bulan');
const dariInput  = document.querySelector('input[name="dari"]');
const sampaiInput = document.getElementById('sampai');
if (bulanInput && dariInput && sampaiInput) {
    bulanInput.addEventListener('change', function() {
        const value = this.value;
        if (!value) {
            return;
        }
        dariInput.value = value + '-01';
        const [y, m] = value.split('-').map(Number);
        const lastDay = new Date(y, m, 0).getDate();
        sampaiInput.value = value + '-' + String(lastDay).padStart(2, '0');
    });
}
</script>
</body>
</html>