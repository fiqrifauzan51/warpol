<?php
require_once '../config.php';
requireAdmin();

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'semua';

$where = "WHERE 1=1";
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (o.no_po LIKE '%$s%' OR b_agg.nama_list LIKE '%$s%' OR u.nama LIKE '%$s%' OR o.pengirim LIKE '%$s%' OR o.penerima LIKE '%$s%')";
}
if ($status !== 'semua') {
    $st = mysqli_real_escape_string($conn, $status);
    $where .= " AND o.status = '$st'";
}

$data = mysqli_query($conn, "
    SELECT o.id, o.no_po, o.created_at, o.approved_at, o.status,
           o.notes, o.pengirim, o.penerima,
           u.nama AS petugas,
           admin_u.nama AS admin_nama,
           COALESCE(b_agg.nama_list, b2.nama_barang) AS barang_list,
           COALESCE(b_agg.jml_item, 1)               AS jml_item,
           COALESCE(b_agg.total_qty, o.qty)           AS total_qty
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN users admin_u ON o.approved_by = admin_u.id
    LEFT JOIN (
        SELECT oi.order_id,
               GROUP_CONCAT(b.nama_barang ORDER BY b.nama_barang SEPARATOR ', ') AS nama_list,
               COUNT(oi.id)   AS jml_item,
               SUM(oi.qty)    AS total_qty
        FROM order_items oi
        JOIN barang b ON oi.barang_id = b.id
        GROUP BY oi.order_id
    ) b_agg ON b_agg.order_id = o.id
    LEFT JOIN barang b2 ON b2.id = o.barang_id
    $where
    ORDER BY o.created_at DESC
");

$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total,
           SUM(status='pending')   as pending,
           SUM(status='received')  as received,
           SUM(status='cancelled') as cancelled
    FROM orders
"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Order - Admin KALA Coffee</title>
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
            <a href="riwayatOrder.php" class="nav-item active"><img src="../poto/riwayat.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Riwayat Order</span></a>
            <a href="laporan.php" class="nav-item"><img src="../poto/riwayat.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Laporan</span></a>
            <a href="../dashWar.php" class="nav-item"><img src="../poto/stok.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Mode Petugas</span></a>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="hamburger-btn" id="hamburger-btn">
                    <span></span><span></span><span></span>
                </button>
                <h1><?= htmlspecialchars($_SESSION['user_nama']) ?> <span class="admin-badge">ADMIN</span></h1>
                <p>Panel Administrator - KALA Coffee</p>
            </div>
            <div class="header-right">
                <a href="../logout.php" class="logout-btn">
                    <img src="../poto/keluar.jpg" alt="Keluar" style="width:24px;height:24px;object-fit:cover;border-radius:5px;">
                    <span>Keluar</span>
                </a>
            </div>
        </header>

        <main class="page-content">
            <div class="page-header">
                <h1>Riwayat Semua Order</h1>
                <p>Semua transaksi order barang dari seluruh petugas</p>
            </div>

            <!-- Stats -->
            <div class="summary-grid">
                <div class="summary-card"><p>Total Order</p><h3><?= $stats['total'] ?></h3></div>
                <div class="summary-card"><p>Menunggu</p><h3 style="color:#d97706;"><?= $stats['pending'] ?></h3></div>
                <div class="summary-card"><p>Disetujui</p><h3 style="color:#16a34a;"><?= $stats['received'] ?></h3></div>
                <div class="summary-card"><p>Dibatalkan</p><h3 style="color:#dc2626;"><?= $stats['cancelled'] ?></h3></div>
            </div>

            <!-- Filter -->
            <div class="card">
                <div class="card-body">
                    <form method="GET">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label>Cari No. PO / Barang / Petugas</label>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                    placeholder="Ketik pencarian...">
                            </div>
                            <div class="form-group">
                                <label>Filter Status</label>
                                <select name="status">
                                    <option value="semua" <?= $status==='semua'?'selected':'' ?>>Semua Status</option>
                                    <option value="pending"   <?= $status==='pending'?'selected':'' ?>>⏳ Menunggu</option>
                                    <option value="received"  <?= $status==='received'?'selected':'' ?>>✅ Disetujui</option>
                                    <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>❌ Dibatalkan</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-export">🔍 Cari</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel -->
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Order (<?= mysqli_num_rows($data) ?> order)</h2>
                </div>
                <div class="card-body overflow-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. PO</th>
                                <th>Tgl Order</th>
                                <th>Barang Dipesan</th>
                                <th class="text-center">Jml Item</th>
                                <th>Penerima</th>
                                <th>Pengirim</th>
                                <th>Petugas</th>
                                <th>Di-ACC oleh</th>
                                <th>Tgl ACC</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong style="color:#0d9488;"><?= htmlspecialchars($row['no_po'] ?? '-') ?></strong></td>
                                <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <?php
                                    $list = explode(', ', $row['barang_list'] ?? '');
                                    $show = array_slice($list, 0, 2);
                                    echo htmlspecialchars(implode(', ', $show));
                                    if (count($list) > 2) echo ' <span style="color:#0d9488;font-size:0.78rem;">+' . (count($list)-2) . ' lagi</span>';
                                    ?>
                                </td>
                                <td class="text-center"><?= $row['jml_item'] ?> jenis<br><small style="color:#6b7280;"><?= $row['total_qty'] ?> total</small></td>
                                <td><?= htmlspecialchars($row['penerima'] ?? '-') ?></td>
                                <td><?php echo $row['pengirim'] ? htmlspecialchars($row['pengirim']) : '<em style="color:#9ca3af;font-size:0.8rem;">-</em>'; ?></td>
                                <td><?= htmlspecialchars($row['petugas']) ?></td>
                                <td><?php echo $row['admin_nama'] ? htmlspecialchars($row['admin_nama']) : '<em style="color:#9ca3af;">-</em>'; ?></td>
                                <td><?= $row['approved_at'] ? date('d M Y', strtotime($row['approved_at'])) : '-' ?></td>
                                <td>
                                    <?php
                                    $s = $row['status'];
                                    if ($s === 'received')  echo '<span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">✅ Disetujui</span>';
                                    elseif ($s === 'cancelled') echo '<span style="background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">❌ Dibatalkan</span>';
                                    else echo '<span style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">⏳ Menunggu</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'received'): ?>
                                        <a href="../po_pdf.php?id=<?= $row['id'] ?>" target="_blank"
                                           class="action-btn view">📄 Lihat PO</a>
                                    <?php else: ?>
                                        <span style="color:#9ca3af;font-size:0.75rem;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($data) === 0): ?>
                            <tr><td colspan="12" style="text-align:center;color:#6b7280;padding:2rem;">Tidak ada data order.</td></tr>
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