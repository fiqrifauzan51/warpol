<?php
require_once 'config.php';
requireLogin();

// Pastikan kolom PO ada
mysqli_query($conn, "ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS no_po       VARCHAR(30)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS pengirim    VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS penerima    VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS notes       TEXT         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP    NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS approved_by INT(11)      DEFAULT NULL");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS order_items (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    order_id  INT NOT NULL,
    barang_id INT NOT NULL,
    qty       INT NOT NULL DEFAULT 1,
    notes     TEXT DEFAULT NULL,
    FOREIGN KEY (order_id)  REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$user_id       = (int)$_SESSION['user_id'];
$search        = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? 'Semua';

$where = "WHERE o.user_id = $user_id";
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (b.nama_barang LIKE '%$s%' OR o.no_po LIKE '%$s%')";
}
if ($filter_status !== 'Semua') {
    $fs = strtolower(mysqli_real_escape_string($conn, $filter_status));
    $where .= " AND o.status = '$fs'";
}

// Query baru — ambil dari order_items + group per order
$data = mysqli_query($conn, "
    SELECT o.id, o.no_po, o.created_at, o.status, o.notes,
           o.pengirim, o.penerima, o.approved_at,
           u.nama AS petugas,
           GROUP_CONCAT(b.nama_barang ORDER BY oi.id SEPARATOR ', ') AS nama_barang_list,
           COUNT(oi.id)  AS jml_item,
           SUM(oi.qty)   AS total_qty
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN barang b       ON oi.barang_id = b.id
    $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$total = mysqli_num_rows($data);

// Stats hanya milik petugas ini
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total,
            SUM(status='pending')   as pending,
            SUM(status='received')  as received,
            SUM(status='cancelled') as cancelled
     FROM orders WHERE user_id = $user_id"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Order - KALA Coffee</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="poto/logo.png" alt="Logo" class="logo-img">
                <div class="logo-text"><h2>KALA Coffee</h2><p>Coffee Shop</p></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashWar.php" class="nav-item"><span>Dashboard</span></a>

            <div class="nav-group" id="group-stok-opname">
                <button type="button" class="nav-group-toggle" data-target="stokOpnameMenu" aria-expanded="false">
                    <img src="poto/input.png" alt=""><span>Stok Opname</span><span class="chevron">▸</span>
                </button>
                <div class="nav-group-menu" id="stokOpnameMenu">
                    <a href="inputWar.php" class="nav-sub-item"><img src="poto/input.png" alt=""><span>Input Stok Opname</span></a>
                    <a href="riwayatWar.php" class="nav-sub-item"><img src="poto/riwayat.png" alt=""><span>Riwayat Stok</span></a>
                </div>
            </div>

            <div class="nav-group open" id="group-order-barang">
                <button type="button" class="nav-group-toggle" data-target="orderMenu" aria-expanded="true">
                    <img src="poto/input.png" alt=""><span>Order Barang</span><span class="chevron">▸</span>
                </button>
                <div class="nav-group-menu" id="orderMenu">
                    <a href="orderBarang.php" class="nav-sub-item"><img src="poto/input.png" alt=""><span>Order Barang</span></a>
                    <a href="riwayatOrder.php" class="nav-sub-item active"><img src="poto/riwayat.png" alt=""><span>Riwayat Order</span></a>
                </div>
            </div>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="hamburger-btn" id="hamburger-btn"><span></span><span></span><span></span></button>
                <h1>Riwayat Order</h1>
                <p>Data order barang yang sudah diajukan</p>
            </div>
            <div class="header-right">
                <button class="notification-btn"><img src="poto/notif.png" alt="" class="header-img-icon"><span class="notification-badge"></span></button>
                <a href="logout.php" class="logout-btn"><img src="poto/keluar.jpg" alt="" class="header-img-icon"><span>Keluar</span></a>
            </div>
        </header>

        <main class="page-content">
            <div class="page-header">
                <h1>Riwayat Order Saya</h1>
                <p>Semua order barang yang pernah kamu ajukan ke Admin.</p>
            </div>

            <!-- Statistik -->
            <div class="summary-grid">
                <div class="summary-card"><p>Total Order</p><h3><?= $stats['total'] ?></h3></div>
                <div class="summary-card"><p>Menunggu ACC</p><h3 style="color:#d97706;"><?= $stats['pending'] ?></h3></div>
                <div class="summary-card"><p>Disetujui</p><h3 style="color:#16a34a;"><?= $stats['received'] ?></h3></div>
                <div class="summary-card"><p>Dibatalkan</p><h3 style="color:#dc2626;"><?= $stats['cancelled'] ?></h3></div>
            </div>

            <!-- Filter -->
            <div class="card">
                <div class="card-body">
                    <form method="GET">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label>Cari No. PO atau Nama Barang</label>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari...">
                            </div>
                            <div class="form-group">
                                <label>Filter Status</label>
                                <select name="status">
                                    <option value="Semua"    <?= $filter_status === 'Semua'    ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="pending"  <?= $filter_status === 'pending'  ? 'selected' : '' ?>>⏳ Menunggu ACC</option>
                                    <option value="received" <?= $filter_status === 'received' ? 'selected' : '' ?>>✓ Disetujui</option>
                                    <option value="cancelled"<?= $filter_status === 'cancelled'? 'selected' : '' ?>>✗ Dibatalkan</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-export">
                                <img src="poto/cari.png" alt="" class="btn-img-icon"> Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel -->
            <div class="card">
                <div class="card-header"><h2>Daftar Order (<?= $total ?> order)</h2></div>
                <div class="card-body overflow-auto">
                    <table class="riwayat-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. PO</th>
                                <th>Tanggal</th>
                                <th>Barang Dipesan</th>
                                <th class="text-center">Jml Item</th>
                                <th>Penerima</th>
                                <th>Pengirim</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td style="font-family:monospace;font-size:11px;color:#0f766e;font-weight:700;">
                                    <?= htmlspecialchars($row['no_po'] ?? '-') ?>
                                </td>
                                <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                <td style="max-width:200px;">
                                    <?= htmlspecialchars($row['nama_barang_list'] ?? '-') ?>
                                    <?php if ($row['jml_item'] > 1): ?>
                                        <br><small style="color:#0d9488;font-weight:600;"><?= $row['jml_item'] ?> jenis barang</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $row['total_qty'] ?? '-' ?></td>
                                <td><?= htmlspecialchars($row['penerima'] ?? '-') ?></td>
                                <td><?php echo $row['pengirim'] ? htmlspecialchars($row['pengirim']) : '<em style="color:#9ca3af;font-size:0.8rem;">Diisi Admin</em>'; ?></td>
                                <td>
                                    <?php
                                    $s = $row['status'];
                                    if ($s === 'received') {
                                        echo '<span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">✓ Disetujui</span>';
                                    } elseif ($s === 'cancelled') {
                                        echo '<span style="background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">✗ Dibatalkan</span>';
                                    } else {
                                        echo '<span style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">⏳ Menunggu</span>';
                                    }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($row['notes'] ?: '-') ?></td>
                                <td class="text-center">
                                    <?php if ($row['status'] === 'received'): ?>
                                        <a href="po_pdf.php?id=<?= $row['id'] ?>" target="_blank"
                                           style="background:#0d9488;color:white;padding:5px 12px;border-radius:6px;font-size:0.75rem;font-weight:600;text-decoration:none;white-space:nowrap;display:inline-block;">
                                            📄 Download PO
                                        </a>
                                    <?php elseif ($row['status'] === 'pending'): ?>
                                        <span style="font-size:0.75rem;color:#9ca3af;">Menunggu Admin</span>
                                    <?php else: ?>
                                        <span style="font-size:0.75rem;color:#dc2626;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($total === 0): ?>
                            <tr><td colspan="10" style="text-align:center;color:#6b7280;padding:2rem;">
                                Belum ada order. <a href="orderBarang.php" style="color:#0d9488;">Buat order sekarang</a>
                            </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
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
document.addEventListener('click', function(e) {
    const sb = document.querySelector('.sidebar'), hb = document.getElementById('hamburger-btn'), ov = document.getElementById('sidebar-overlay');
    if (!sb.contains(e.target) && !hb.contains(e.target) && window.innerWidth <= 768) {
        sb.classList.remove('open'); ov.classList.remove('open');
    }
});
document.querySelectorAll('.nav-group-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const g = btn.closest('.nav-group'), open = g.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
});
</script>
</body>
</html>