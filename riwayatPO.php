<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$po_list = mysqli_query($conn, "
    SELECT po.*, u.nama as nama_petugas,
           a.nama as nama_approver
    FROM purchase_orders po
    JOIN users u ON po.petugas_id = u.id
    LEFT JOIN users a ON po.approved_by = a.id
    WHERE po.petugas_id = $user_id
    ORDER BY po.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat PO - KALA Coffe</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .status-badge { display:inline-block; padding:0.25rem 0.75rem; border-radius:9999px; font-size:0.75rem; font-weight:600; }
        .status-badge.pending    { background:#fef3c7; color:#92400e; }
        .status-badge.disetujui  { background:#dcfce7; color:#166534; }
        .status-badge.ditolak    { background:#fee2e2; color:#dc2626; }
        .btn-cetak { display:inline-flex;align-items:center;gap:0.4rem;padding:0.35rem 0.75rem;background:#0d9488;color:white;border:none;border-radius:0.375rem;font-size:0.78rem;cursor:pointer;text-decoration:none; }
        .btn-cetak:hover { background:#0f766e; }
        .btn-cetak.disabled { background:#9ca3af; cursor:not-allowed; pointer-events:none; }
        .img-logo-sidebar { width:42px;height:42px;object-fit:cover;border-radius:50%;border:2px solid rgba(255,255,255,0.5);flex-shrink:0; }
        .img-nav { width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0; }
        .img-header { width:24px;height:24px;object-fit:cover;border-radius:5px; }
    </style>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="poto/logo.png" alt="Logo" class="img-logo-sidebar">
                <div class="logo-text"><h2>KALA Coffe</h2><p>Coffee Shop</p></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashWar.php" class="nav-item"><span>Dashboard</span></a>
            <div class="nav-group" id="group-stok">
                <button type="button" class="nav-group-toggle" data-target="menuStok" aria-expanded="false">
                    <img src="poto/input.png" alt="" class="img-nav"><span>Stok Opname</span><span class="chevron">▸</span>
                </button>
                <div class="nav-group-menu" id="menuStok">
                    <a href="inputWar.php" class="nav-sub-item"><img src="poto/input.png" alt=""><span>Input Stok Opname</span></a>
                    <a href="riwayatWar.php" class="nav-sub-item"><img src="poto/riwayat.png" alt=""><span>Riwayat Stok</span></a>
                </div>
            </div>
            <div class="nav-group open" id="group-order">
                <button type="button" class="nav-group-toggle" data-target="menuOrder" aria-expanded="true">
                    <img src="poto/input.png" alt="" class="img-nav"><span>Order Barang</span><span class="chevron">▸</span>
                </button>
                <div class="nav-group-menu" id="menuOrder">
                    <a href="orderBarang.php" class="nav-sub-item"><img src="poto/input.png" alt=""><span>Order Barang</span></a>
                    <a href="riwayatOrder.php" class="nav-sub-item"><img src="poto/riwayat.png" alt=""><span>Riwayat Order</span></a>
                    <a href="buatPO.php" class="nav-sub-item"><img src="poto/input.png" alt=""><span>Buat PO</span></a>
                    <a href="riwayatPO.php" class="nav-sub-item active"><img src="poto/riwayat.png" alt=""><span>Riwayat PO</span></a>
                </div>
            </div>
        </nav>
    </aside>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Selamat datang, <?= htmlspecialchars($_SESSION['user_nama']) ?></h1>
                <p>Petugas - KALA Coffe</p>
            </div>
            <div class="header-right">
                <button class="notification-btn"><img src="poto/notif.png" alt="" class="img-header"><span class="notification-badge"></span></button>
                <a href="logout.php" class="logout-btn"><img src="poto/keluar.jpg" alt="" class="img-header"><span>Keluar</span></a>
            </div>
        </header>

        <main class="page-content">
            <div class="page-header">
                <h1>Riwayat Purchase Order</h1>
                <p>Pantau status PO yang sudah kamu ajukan</p>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
                <a href="buatPO.php" class="btn-primary" style="width:auto;padding:0.65rem 1.25rem;text-decoration:none;">＋ Buat PO Baru</a>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h2>Daftar PO Kamu</h2>
                    <p>PO yang sudah disetujui admin dapat dicetak sebagai PDF</p>
                </div>
                <div class="table-container" style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nomor PO</th>
                                <th>Tanggal</th>
                                <th>Pengirim</th>
                                <th>Penerima</th>
                                <th class="text-center">Total Item</th>
                                <th>Status</th>
                                <th>Catatan Admin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($po_list)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($row['nomor_po']) ?></strong></td>
                                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['pengirim']) ?></td>
                                <td><?= htmlspecialchars($row['penerima']) ?></td>
                                <td class="text-center"><?= $row['total_item'] ?> item</td>
                                <td>
                                    <span class="status-badge <?= $row['status'] ?>">
                                        <?= $row['status'] === 'pending' ? '⏳ Menunggu' : ($row['status'] === 'disetujui' ? '✅ Disetujui' : '❌ Ditolak') ?>
                                    </span>
                                </td>
                                <td style="font-size:0.8rem;color:#6b7280;"><?= htmlspecialchars($row['catatan_admin'] ?? '-') ?></td>
                                <td>
                                    <?php if ($row['status'] === 'disetujui'): ?>
                                        <a href="cetakPO.php?id=<?= $row['id'] ?>" target="_blank" class="btn-cetak">
                                            🖨️ Cetak PDF
                                        </a>
                                    <?php else: ?>
                                        <span class="btn-cetak disabled">🔒 Belum disetujui</span>
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
<script>
document.querySelectorAll('.nav-group-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const menu = document.getElementById(btn.getAttribute('data-target'));
        if (!menu) return;
        const group = btn.closest('.nav-group');
        const isOpen = group.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});
</script>
</body>
</html>
