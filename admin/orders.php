<?php
require_once '../config.php';
requireLogin();
if ($_SESSION['user_role'] !== 'admin') { header('Location: ../dashWar.php'); exit; }

// Pastikan kolom PO ada
mysqli_query($conn, "ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS no_po VARCHAR(30) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS pengirim VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS penerima VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS approved_by INT(11) DEFAULT NULL");

$feedback = '';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['action'])) {
    $order_id = (int)$_POST['order_id'];
    $action   = $_POST['action'];
    $admin_id = $_SESSION['user_id'];

    $order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id"));
    $pengirim_acc = trim($_POST['pengirim_acc'] ?? '');

    if (!$order) {
        $error = 'Order tidak ditemukan.';
    } elseif ($order['status'] !== 'pending') {
        $error = 'Hanya order pending yang bisa diubah.';
    } else {
        if ($action === 'approve') {
            if (!$pengirim_acc) {
                $error = 'Nama pengirim wajib diisi sebelum ACC.';
            } else {
                // Generate no_po jika belum ada
                $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT no_po FROM orders WHERE id = $order_id"));
                $no_po = $existing['no_po'] ?: ('PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));

                // Update stok dari order_items (multi-barang)
                $items_q = mysqli_query($conn, "SELECT barang_id, qty FROM order_items WHERE order_id = $order_id");
                if ($items_q && mysqli_num_rows($items_q) > 0) {
                    while ($itm = mysqli_fetch_assoc($items_q)) {
                        (function() use ($conn, $itm) {
                        $stmt_u = mysqli_prepare($conn, "UPDATE barang SET stok_sistem = stok_sistem + ? WHERE id = ?");
                        $qty_u = (int)$itm['qty']; $bid_u = (int)$itm['barang_id'];
                        mysqli_stmt_bind_param($stmt_u, 'ii', $qty_u, $bid_u);
                        mysqli_stmt_execute($stmt_u);
                    })();
                    }
                }

                $pen   = mysqli_real_escape_string($conn, $pengirim_acc);
                $no_po = mysqli_real_escape_string($conn, $no_po);
                $ok    = (function() use ($conn, $no_po, $admin_id, $pen, $order_id) {
                $stmt_acc = mysqli_prepare($conn, "UPDATE orders SET status='received', no_po=?, approved_at=NOW(), approved_by=?, pengirim=? WHERE id=?");
                mysqli_stmt_bind_param($stmt_acc, 'sisi', $no_po, $admin_id, $pen, $order_id);
                return mysqli_stmt_execute($stmt_acc);
            })();

                if ($ok) {
                    header('Location: orders.php?success=acc&no_po=' . urlencode($no_po));
                    exit;
                } else {
                    $error = 'Gagal menyimpan: ' . mysqli_error($conn);
                }
            }
        } elseif ($action === 'cancel') {
            (function() use ($conn, $order_id) {
            $stmt_c = mysqli_prepare($conn, "UPDATE orders SET status='cancelled' WHERE id=?");
            mysqli_stmt_bind_param($stmt_c, 'i', $order_id);
            mysqli_stmt_execute($stmt_c);
        })();
            header('Location: orders.php?success=cancel');
            exit;
        }
    }
}

// Pesan dari redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'acc') {
        $feedback = '✅ Order di-ACC! No. PO: <b>' . htmlspecialchars($_GET['no_po'] ?? '') . '</b> — Stok diperbarui. PDF siap didownload.';
    } elseif ($_GET['success'] === 'cancel') {
        $feedback = 'Order berhasil dibatalkan.';
    }
}

$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total, SUM(status='pending') as pending,
     SUM(status='received') as received, SUM(status='cancelled') as cancelled FROM orders"));

$orders = mysqli_query($conn, "
    SELECT o.id, o.no_po, o.created_at, o.status, o.notes,
           o.pengirim, o.penerima, o.approved_at,
           u.nama AS petugas,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS jml_item,
           (SELECT GROUP_CONCAT(b.nama_barang SEPARATOR ', ')
            FROM order_items oi2 JOIN barang b ON oi2.barang_id = b.id
            WHERE oi2.order_id = o.id LIMIT 50) AS nama_barang_list
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Masuk - Admin KALA Coffee</title>
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
            <a href="orders.php" class="nav-item active"><img src="../poto/input.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Order Masuk</span></a>
            <a href="riwayatOrder.php" class="nav-item"><img src="../poto/riwayat.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Riwayat Order</span></a>
            <a href="laporan.php" class="nav-item"><img src="../poto/riwayat.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Laporan</span></a>
            <a href="../dashWar.php" class="nav-item"><img src="../poto/stok.png" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0;"><span>Mode Petugas</span></a>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="hamburger-btn" id="hamburger-btn"><span></span><span></span><span></span></button>
                <h1>Order Masuk</h1>
                <p>ACC atau batalkan order dari petugas</p>
            </div>
            <div class="header-right">
                <a href="../logout.php" class="logout-btn">
                    <img src="../poto/keluar.jpg" alt="" style="width:24px;height:24px;object-fit:cover;border-radius:5px;">
                    <span>Keluar</span>
                </a>
            </div>
        </header>

        <main class="page-content">
            <div class="page-header">
                <h1>Daftar Order Masuk</h1>
                <p>Review dan setujui order barang dari petugas.</p>
            </div>

            <div class="summary-grid">
                <div class="summary-card"><p>Total Order</p><h3><?= $stats['total'] ?></h3></div>
                <div class="summary-card"><p>Menunggu ACC</p><h3 style="color:#d97706;"><?= $stats['pending'] ?></h3></div>
                <div class="summary-card"><p>Disetujui</p><h3 style="color:#16a34a;"><?= $stats['received'] ?></h3></div>
                <div class="summary-card"><p>Dibatalkan</p><h3 style="color:#dc2626;"><?= $stats['cancelled'] ?></h3></div>
            </div>

            <?php if ($feedback): ?>
                <div style="background:#dcfce7;color:#166534;padding:0.85rem 1rem;border-radius:0.5rem;margin-bottom:1rem;border:1px solid #bbf7d0;"><?= $feedback ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background:#fee2e2;color:#dc2626;padding:0.85rem 1rem;border-radius:0.5rem;margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h2>Semua Order</h2></div>
                <div class="card-body overflow-auto">
                    <table class="riwayat-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. PO</th>
                                <th>Tanggal</th>
                                <th>Barang</th>
                                <th class="text-center">Jumlah</th>
                                <th>Pengirim</th>
                                <th>Penerima</th>
                                <th>Petugas</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($orders)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td style="font-family:monospace;font-size:11px;color:#0F766E;font-weight:600;">
                                    <?= htmlspecialchars($row['no_po'] ?? '-') ?>
                                </td>
                                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($row['nama_barang_list'] ?? '-') ?>
                                    <?php if ($row['jml_item'] > 1): ?>
                                        <br><small style="color:#0d9488;font-weight:600;"><?= $row['jml_item'] ?> jenis barang</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $row['jml_item'] ?> item</td>
                                <td><?= htmlspecialchars($row['pengirim'] ?? '<em style="color:#9ca3af;">Diisi saat ACC</em>') ?></td>
                                <td><?= htmlspecialchars($row['penerima'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['petugas']) ?></td>
                                <td>
                                    <?php
                                    $s = $row['status'];
                                    $badge_style = $s === 'received' ? 'background:#dcfce7;color:#166534;' :
                                                  ($s === 'cancelled' ? 'background:#fee2e2;color:#dc2626;' :
                                                                        'background:#fef3c7;color:#92400e;');
                                    ?>
                                    <span style="<?= $badge_style ?> padding:2px 10px;border-radius:20px;font-size:12px;font-weight:500;">
                                        <?= $s === 'received' ? '✅ Disetujui' : ($s === 'cancelled' ? '❌ Batal' : '⏳ Pending') ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <button type="button" class="action-btn edit"
                                                onclick="bukaModalAcc(<?= $row['id'] ?>)">
                                                ✅ ACC
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="action-btn delete"
                                                    onclick="return confirm('Batalkan order ini?')">❌ Batal</button>
                                            </form>
                                        <?php elseif ($row['status'] === 'received'): ?>
                                            <a href="../po_pdf.php?id=<?= $row['id'] ?>" target="_blank" class="action-btn view">📄 Lihat PO</a>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;font-size:12px;">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($orders) === 0): ?>
                            <tr><td colspan="10" style="text-align:center;color:#6b7280;padding:2rem;">Belum ada order masuk.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.getElementById('hamburger-btn').addEventListener('click', function () {
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('sidebar-overlay').classList.toggle('open');
});
document.addEventListener('click', function (event) {
    const sidebar   = document.querySelector('.sidebar');
    const hamburger = document.getElementById('hamburger-btn');
    const overlay   = document.getElementById('sidebar-overlay');
    if (!sidebar.contains(event.target) && !hamburger.contains(event.target) && window.innerWidth <= 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    }
});

function bukaModalAcc(orderId) {
    document.getElementById('modal_order_id').value = orderId;
    document.getElementById('modal_pengirim').value = '';
    document.getElementById('modalAcc').classList.add('active');
    setTimeout(function() { document.getElementById('modal_pengirim').focus(); }, 100);
}

function tutupModalAcc() {
    document.getElementById('modalAcc').classList.remove('active');
}

document.getElementById('modalAcc').addEventListener('click', function(e) {
    if (e.target === this) tutupModalAcc();
});
</script>

<!-- Modal ACC -->
<div class="modal-overlay" id="modalAcc">
    <div class="modal" style="max-width:440px;">
        <button class="modal-close" onclick="tutupModalAcc()">✕</button>
        <h3>✅ ACC Order Barang</h3>
        <p style="font-size:0.875rem;color:#6b7280;margin-bottom:1.25rem;">
            Isi nama pengirim barang sebelum menyetujui order ini.
        </p>
        <form method="POST">
            <input type="hidden" name="order_id" id="modal_order_id">
            <input type="hidden" name="action" value="approve">
            <div class="form-group">
                <label>Nama Pengirim <span style="color:#dc2626;">*</span></label>
                <input type="text" name="pengirim_acc" id="modal_pengirim"
                    placeholder="Nama supplier / pengirim barang" required
                    style="margin-bottom:0.25rem;">
                <p class="help-text">Pihak yang mengirimkan barang ke coffee shop</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="tutupModalAcc()">Batal</button>
                <button type="submit" class="btn-primary" style="width:auto;padding:0.6rem 1.5rem;">
                    ✅ Setujui & ACC
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>