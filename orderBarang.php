<?php
require_once 'config.php';
requireLogin();

// Pastikan tabel & kolom ada
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

// Ensure legacy single-item columns don't block multi-item PO inserts.
// If the old foreign key to `barang(id)` exists, drop it and make the
// `barang_id`/`qty` columns nullable so we can keep the historic columns
// but avoid failing inserts that don't set them.
$fk_check = mysqli_query($conn,
    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'
     AND REFERENCED_TABLE_NAME = 'barang' LIMIT 1");
if ($fk_check && mysqli_num_rows($fk_check) > 0) {
    $fk_row = mysqli_fetch_assoc($fk_check);
    $fk_name = $fk_row['CONSTRAINT_NAME'];
    // Drop the foreign key if present (safe to ignore failure)
    @mysqli_query($conn, "ALTER TABLE orders DROP FOREIGN KEY `" . $fk_name . "`");
}
// Make legacy columns nullable (won't fail inserts that omit them)
@mysqli_query($conn, "ALTER TABLE orders
    MODIFY COLUMN barang_id INT(11) DEFAULT NULL,
    MODIFY COLUMN qty INT(11) DEFAULT NULL");

$barang_list_raw = mysqli_query($conn, "SELECT id, nama_barang, satuan, stok_sistem FROM barang ORDER BY nama_barang");
$barang_arr = [];
while ($b = mysqli_fetch_assoc($barang_list_raw)) $barang_arr[] = $b;

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $penerima   = trim($_POST['penerima'] ?? '');
    $po_notes   = trim($_POST['po_notes'] ?? '');
    $user_id    = (int)$_SESSION['user_id'];
    $barang_ids = $_POST['barang_id']  ?? [];
    $qtys       = $_POST['qty']        ?? [];
    $item_notes = $_POST['item_notes'] ?? [];

    // Kumpulkan item yang valid
    $items = [];
    foreach ($barang_ids as $i => $bid) {
        $bid = (int)$bid;
        $qty = (int)($qtys[$i] ?? 0);
        if ($bid > 0 && $qty > 0) {
            $items[] = [
                'barang_id' => $bid,
                'qty'       => $qty,
                'notes'     => trim($item_notes[$i] ?? '')
            ];
        }
    }

    if (!$penerima) {
        $error = 'Nama penerima wajib diisi.';
    } elseif (empty($items)) {
        $error = 'Minimal isi 1 barang dengan jumlah lebih dari 0.';
    } else {
        $no_po = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $stmt  = mysqli_prepare($conn,
            "INSERT INTO orders (no_po, user_id, status, notes, penerima) VALUES (?,?,'pending',?,?)");
        mysqli_stmt_bind_param($stmt, 'siss', $no_po, $user_id, $po_notes, $penerima);

        if (mysqli_stmt_execute($stmt)) {
            $order_id = mysqli_insert_id($conn);
            $stmt2    = mysqli_prepare($conn,
                "INSERT INTO order_items (order_id, barang_id, qty, notes) VALUES (?,?,?,?)");
            foreach ($items as $item) {
                mysqli_stmt_bind_param($stmt2, 'iiis',
                    $order_id, $item['barang_id'], $item['qty'], $item['notes']);
                mysqli_stmt_execute($stmt2);
            }
            $success = "Order berhasil dibuat! No. PO: <b>$no_po</b> — Menunggu persetujuan Admin.";
        } else {
            $error = 'Gagal membuat order: ' . mysqli_error($conn);
        }
    }
}

$order_stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total, SUM(status='pending') as pending,
     SUM(status='received') as received, SUM(status='cancelled') as cancelled
     FROM orders WHERE user_id = " . (int)$_SESSION['user_id']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Barang - KALA Coffee</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .items-table { width:100%; border-collapse:collapse; margin-bottom:0.75rem; }
        .items-table th {
            background:#f0fdfa; color:#0d9488; padding:8px 10px;
            font-size:0.82rem; font-weight:600; border:1px solid #ccfbf1; text-align:left;
        }
        .items-table td { padding:6px 8px; border:1px solid #e5e7eb; vertical-align:middle; }
        .items-table select,
        .items-table input[type="number"],
        .items-table input[type="text"] {
            width:100%; padding:6px 8px; border:1px solid #e5e7eb;
            border-radius:6px; font-size:0.85rem; background:#f9fafb;
        }
        .items-table select:focus,
        .items-table input:focus { border-color:#14b8a6; background:#fff; outline:none; }
        .btn-add-row {
            background:#f0fdfa; color:#0d9488;
            border:1.5px dashed #14b8a6; padding:8px 16px;
            border-radius:8px; cursor:pointer; font-size:0.875rem;
            font-weight:500; width:100%; margin-bottom:1rem;
            transition: background 0.2s;
        }
        .btn-add-row:hover { background:#ccfbf1; }
        .btn-del-row {
            background:#fee2e2; color:#dc2626; border:none;
            width:28px; height:28px; border-radius:6px;
            cursor:pointer; font-size:14px; font-weight:700;
        }
        .btn-del-row:hover { background:#fecaca; }
        .satuan-cell {
            background:#e9ecef; color:#6b7280;
            font-size:0.82rem; text-align:center; white-space:nowrap;
        }
    </style>
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
                    <a href="orderBarang.php" class="nav-sub-item active"><img src="poto/input.png" alt=""><span>Order Barang</span></a>
                    <a href="riwayatOrder.php" class="nav-sub-item"><img src="poto/riwayat.png" alt=""><span>Riwayat Order</span></a>
                </div>
            </div>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="hamburger-btn" id="hamburger-btn"><span></span><span></span><span></span></button>
                <h1>Order Barang</h1>
                <p>Buat permintaan order barang (PO)</p>
            </div>
            <div class="header-right">
                <button class="notification-btn"><img src="poto/notif.png" alt="" class="header-img-icon"><span class="notification-badge"></span></button>
                <a href="logout.php" class="logout-btn"><img src="poto/keluar.jpg" alt="" class="header-img-icon"><span>Keluar</span></a>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="page-header">
                <h1>Buat Order Barang (PO)</h1>
                <p>Tambahkan beberapa barang dalam satu PO, lalu kirim ke Admin untuk disetujui.</p>
            </div>

            <!-- Stats -->
            <div class="summary-grid">
                <div class="summary-card"><p>Total Order Saya</p><h3><?= $order_stats['total'] ?></h3></div>
                <div class="summary-card"><p>Menunggu ACC</p><h3 style="color:#d97706;"><?= $order_stats['pending'] ?></h3></div>
                <div class="summary-card"><p>Disetujui</p><h3 style="color:#16a34a;"><?= $order_stats['received'] ?></h3></div>
                <div class="summary-card"><p>Dibatalkan</p><h3 style="color:#dc2626;"><?= $order_stats['cancelled'] ?></h3></div>
            </div>

            <?php if ($success): ?>
                <div style="background:#dcfce7;color:#166534;padding:0.85rem 1rem;border-radius:0.5rem;margin-bottom:1rem;border:1px solid #bbf7d0;">
                    ✅ <?= $success ?> — <a href="riwayatOrder.php" style="color:#15803d;font-weight:600;">Lihat Riwayat</a>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background:#fee2e2;color:#dc2626;padding:0.85rem 1rem;border-radius:0.5rem;margin-bottom:1rem;border:1px solid #fecaca;">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="grid-layout">
                <!-- Form -->
                <div class="card-padded">
                    <h2>Form Order Barang</h2>
                    <form method="POST" id="formOrder">

                        <!-- Nama Penerima -->
                        <div class="form-group">
                            <label>Nama Penerima <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="penerima"
                                value="<?= htmlspecialchars($_SESSION['user_nama']) ?>"
                                placeholder="Nama petugas yang menerima barang" required>
                            <p class="help-text">Pihak yang menerima barang (Pengirim diisi Admin saat ACC)</p>
                        </div>

                        <!-- Tabel multi-barang -->
                        <div class="form-group">
                            <label>Daftar Barang yang Dipesan <span style="color:#dc2626;">*</span></label>
                            <table class="items-table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width:38%;">Nama Barang</th>
                                        <th style="width:15%;">Jumlah</th>
                                        <th style="width:12%;">Satuan</th>
                                        <th>Keterangan</th>
                                        <th style="width:36px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- baris diisi JS -->
                                </tbody>
                            </table>
                            <button type="button" class="btn-add-row" id="btnAddRow">
                                ＋ Tambah Barang Lagi
                            </button>
                        </div>

                        <!-- Catatan -->
                        <div class="form-group">
                            <label>Catatan Umum PO</label>
                            <textarea name="po_notes" rows="2"
                                placeholder="Catatan untuk seluruh order ini (opsional)"></textarea>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn-primary">
                                <img src="poto/input.png" alt="" class="btn-img-icon">
                                Kirim Order ke Admin
                            </button>
                            <button type="button" class="btn-secondary" onclick="resetForm()">Reset</button>
                        </div>
                    </form>
                </div>

                <!-- Panel info -->
                <div class="info-panel">
                    <div class="card-padded info-card">
                        <h3>Alur Order PO</h3>
                        <ul class="steps-list">
                            <li><span class="step-number">1</span><span>Isi nama penerima</span></li>
                            <li><span class="step-number">2</span><span>Tambahkan semua barang dalam satu PO</span></li>
                            <li><span class="step-number">3</span><span>Klik Kirim Order ke Admin</span></li>
                            <li><span class="step-number">4</span><span>Admin mengisi pengirim lalu ACC</span></li>
                            <li><span class="step-number">5</span><span>PDF PO bisa didownload di Riwayat Order</span></li>
                        </ul>
                    </div>
                    <div class="tips-card">
                        <h3>Multi-Barang</h3>
                        <p>Klik <b>＋ Tambah Barang Lagi</b> untuk menambah baris. Satu PO bisa berisi banyak barang sekaligus.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
const barangData = <?= json_encode($barang_arr, JSON_HEX_TAG | JSON_HEX_APOS) ?>;

function buildOptions(selectedId) {
    let html = '<option value="">-- Pilih Barang --</option>';
    barangData.forEach(function(b) {
        const sel = String(b.id) === String(selectedId) ? ' selected' : '';
        html += '<option value="' + b.id + '" data-satuan="' + b.satuan + '"' + sel + '>'
             + b.nama_barang + ' (Stok: ' + b.stok_sistem + ' ' + b.satuan + ')</option>';
    });
    return html;
}

function addRow(selectedId, qty, notes) {
    const tbody = document.getElementById('itemsBody');
    const tr    = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML =
        '<td><select name="barang_id[]" onchange="updateSatuan(this)">' + buildOptions(selectedId || '') + '</select></td>'
      + '<td><input type="number" name="qty[]" min="1" value="' + (qty || '') + '" placeholder="0"></td>'
      + '<td class="satuan-cell"><input type="text" name="satuan_display" readonly tabindex="-1" style="background:#e9ecef;color:#6b7280;text-align:center;font-size:0.82rem;border:none;" value=""></td>'
      + '<td><input type="text" name="item_notes[]" value="' + (notes || '') + '" placeholder="Keterangan barang ini..."></td>'
      + '<td style="text-align:center;"><button type="button" class="btn-del-row" onclick="delRow(this)" title="Hapus baris">✕</button></td>';
    tbody.appendChild(tr);

    // Kalau selectedId ada, update satuan
    if (selectedId) {
        const sel = tr.querySelector('select');
        updateSatuan(sel);
    }
}

function updateSatuan(sel) {
    const opt = sel.options[sel.selectedIndex];
    const satuan = opt ? (opt.dataset.satuan || '') : '';
    sel.closest('tr').querySelector('input[name="satuan_display"]').value = satuan;
}

function delRow(btn) {
    if (document.getElementById('itemsBody').rows.length <= 1) {
        alert('Minimal harus ada 1 baris barang.'); return;
    }
    btn.closest('tr').remove();
}

function resetForm() {
    document.getElementById('formOrder').reset();
    document.getElementById('itemsBody').innerHTML = '';
    addRow();
}

// Init: 1 baris kosong
addRow();

document.getElementById('btnAddRow').addEventListener('click', function() { addRow(); });

// Hamburger & sidebar
document.getElementById('hamburger-btn').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('sidebar-overlay').classList.toggle('open');
});
document.addEventListener('click', function(e) {
    const sb = document.querySelector('.sidebar');
    const hb = document.getElementById('hamburger-btn');
    const ov = document.getElementById('sidebar-overlay');
    if (!sb.contains(e.target) && !hb.contains(e.target) && window.innerWidth <= 768) {
        sb.classList.remove('open'); ov.classList.remove('open');
    }
});
document.querySelectorAll('.nav-group-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const g    = btn.closest('.nav-group');
        const open = g.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
});
</script>
</body>
</html>