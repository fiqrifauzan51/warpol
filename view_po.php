<?php
require_once 'config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: riwayatOrder.php'); exit; }

$order = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT o.*, b.nama_barang, b.satuan, u.nama AS petugas, u.username,
           a.nama AS admin_nama
    FROM orders o
    JOIN barang b ON o.barang_id = b.id
    JOIN users u  ON o.user_id   = u.id
    LEFT JOIN users a ON o.approved_by = a.id
    WHERE o.id = $id
"));

if (!$order) { header('Location: riwayatOrder.php'); exit; }

// Petugas hanya bisa lihat PO miliknya, admin bisa lihat semua
if ($_SESSION['user_role'] !== 'admin' && $order['user_id'] != $_SESSION['user_id']) {
    header('Location: riwayatOrder.php'); exit;
}

// Hanya order yang di-ACC yang bisa dilihat PO-nya oleh petugas
if ($_SESSION['user_role'] !== 'admin' && $order['status'] !== 'received') {
    header('Location: riwayatOrder.php?error=PO+hanya+tersedia+setelah+disetujui+Admin'); exit;
}

$tgl_order    = date('d F Y', strtotime($order['created_at']));
$tgl_approved = $order['approved_at'] ? date('d F Y', strtotime($order['approved_at'])) : '-';
$no_po        = $order['no_po'] ?? 'PO-' . $id;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PO <?= htmlspecialchars($no_po) ?> - KALA Coffe</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f3f4f6; }

        /* Toolbar (tidak ikut di-print) */
        .toolbar {
            background: #0F766E;
            color: white;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .toolbar h2 { font-size: 15px; flex: 1; }
        .btn-dl {
            background: white;
            color: #0F766E;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-dl:hover { background: #ccfbf1; }
        .btn-back {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
        }
        .btn-back:hover { background: rgba(255,255,255,0.25); }

        /* Wrapper PO */
        .po-wrapper {
            display: flex;
            justify-content: center;
            padding: 30px 16px;
        }

        /* Dokumen PO */
        #po-document {
            background: white;
            width: 794px;
            min-height: 1123px;
            padding: 48px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        /* Header PO */
        .po-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #0F766E;
            padding-bottom: 20px;
            margin-bottom: 28px;
        }
        .po-brand h1 { font-size: 26px; color: #0F766E; font-weight: 700; }
        .po-brand p  { font-size: 13px; color: #6b7280; margin-top: 2px; }
        .po-title-box { text-align: right; }
        .po-title-box h2 { font-size: 22px; font-weight: 700; color: #1e293b; }
        .po-title-box .no-po { font-size: 15px; color: #0F766E; font-weight: 600; margin-top: 4px; }
        .po-title-box .status-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-received { background: #dcfce7; color: #166534; }
        .status-pending  { background: #fef3c7; color: #92400e; }

        /* Info grid */
        .po-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .po-info-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 16px;
        }
        .po-info-box h4 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 8px; }
        .po-info-box p  { font-size: 13px; color: #1e293b; margin-bottom: 3px; }
        .po-info-box p strong { color: #0F766E; }

        /* Tabel barang */
        .po-table-title { font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 10px; }
        .po-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .po-table th {
            background: #0F766E;
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
        }
        .po-table th:last-child,
        .po-table td:last-child { text-align: center; }
        .po-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            color: #1e293b;
        }
        .po-table tr:last-child td { border-bottom: none; }
        .po-table .total-row td {
            background: #f0fdf4;
            font-weight: 600;
            color: #166534;
        }

        /* Keterangan */
        .po-notes {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 36px;
        }
        .po-notes h4 { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 6px; }
        .po-notes p  { font-size: 13px; color: #475569; }

        /* Tanda tangan */
        .po-signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 20px;
        }
        .po-sig-box { text-align: center; }
        .po-sig-box p { font-size: 13px; color: #374151; margin-bottom: 4px; }
        .po-sig-box .sig-name { font-weight: 600; }
        .po-sig-line {
            border-bottom: 1px solid #374151;
            margin: 60px 20px 8px;
        }
        .po-sig-box .sig-role { font-size: 11px; color: #6b7280; }

        /* Footer PO */
        .po-footer {
            margin-top: 40px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }

        @media print {
            .toolbar { display: none !important; }
            .po-wrapper { padding: 0; }
            #po-document { box-shadow: none; }
        }
    </style>
</head>
<body>

<!-- Toolbar -->
<div class="toolbar">
    <a href="<?= $_SESSION['user_role'] === 'admin' ? 'admin/orders.php' : 'riwayatOrder.php' ?>" class="btn-back">← Kembali</a>
    <h2>Purchase Order — <?= htmlspecialchars($no_po) ?></h2>
    <button class="btn-dl" onclick="downloadPDF()">⬇️ Download PDF</button>
</div>

<!-- Dokumen PO -->
<div class="po-wrapper">
<div id="po-document">

    <!-- Header -->
    <div class="po-header">
        <div class="po-brand">
            <h1>☕ KALA Coffe</h1>
            <p>Sistem Manajemen Stok Opname</p>
            <p>Coffee Shop</p>
        </div>
        <div class="po-title-box">
            <h2>PURCHASE ORDER</h2>
            <div class="no-po"><?= htmlspecialchars($no_po) ?></div>
            <span class="status-badge <?= $order['status'] === 'received' ? 'status-received' : 'status-pending' ?>">
                <?= $order['status'] === 'received' ? '✅ Disetujui' : '⏳ Menunggu ACC' ?>
            </span>
        </div>
    </div>

    <!-- Info order -->
    <div class="po-info">
        <div class="po-info-box">
            <h4>Informasi Order</h4>
            <p>No. PO: <strong><?= htmlspecialchars($no_po) ?></strong></p>
            <p>Tanggal Order: <?= $tgl_order ?></p>
            <p>Tanggal Disetujui: <?= $tgl_approved ?></p>
            <p>Status: <strong><?= ucfirst($order['status']) ?></strong></p>
        </div>
        <div class="po-info-box">
            <h4>Petugas</h4>
            <p>Nama: <strong><?= htmlspecialchars($order['petugas']) ?></strong></p>
            <p>Username: <?= htmlspecialchars($order['username']) ?></p>
            <?php if ($order['admin_nama']): ?>
                <p>Disetujui oleh: <strong><?= htmlspecialchars($order['admin_nama']) ?></strong></p>
            <?php endif; ?>
        </div>
        <div class="po-info-box">
            <h4>Pengirim</h4>
            <p><strong><?= htmlspecialchars($order['pengirim'] ?? '-') ?></strong></p>
        </div>
        <div class="po-info-box">
            <h4>Penerima</h4>
            <p><strong><?= htmlspecialchars($order['penerima'] ?? '-') ?></strong></p>
        </div>
    </div>

    <!-- Tabel barang -->
    <p class="po-table-title">Detail Barang yang Dipesan</p>
    <table class="po-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td><?= htmlspecialchars($order['nama_barang']) ?></td>
                <td><?= htmlspecialchars($order['satuan']) ?></td>
                <td style="text-align:center;font-weight:600;"><?= $order['qty'] ?></td>
                <td><?= htmlspecialchars($order['notes'] ?: '-') ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align:right;">Total:</td>
                <td><?= $order['qty'] ?> <?= htmlspecialchars($order['satuan']) ?></td>
                <td>-</td>
            </tr>
        </tbody>
    </table>

    <!-- Keterangan -->
    <div class="po-notes">
        <h4>Catatan / Keterangan</h4>
        <p><?= htmlspecialchars($order['notes'] ?: 'Tidak ada keterangan tambahan.') ?></p>
    </div>

    <!-- Tanda tangan -->
    <div class="po-signatures">
        <div class="po-sig-box">
            <p class="sig-name">Pengirim</p>
            <div class="po-sig-line"></div>
            <p class="sig-name"><?= htmlspecialchars($order['pengirim'] ?? '-') ?></p>
            <p class="sig-role">Tanda Tangan Pengirim</p>
        </div>
        <div class="po-sig-box">
            <p class="sig-name">Penerima</p>
            <div class="po-sig-line"></div>
            <p class="sig-name"><?= htmlspecialchars($order['penerima'] ?? '-') ?></p>
            <p class="sig-role">Tanda Tangan Penerima</p>
        </div>
    </div>

    <!-- Footer -->
    <div class="po-footer">
        Dokumen ini digenerate otomatis oleh Sistem KALA Coffe &nbsp;|&nbsp;
        <?= htmlspecialchars($no_po) ?> &nbsp;|&nbsp; <?= date('d F Y H:i') ?>
    </div>

</div>
</div>

<script>
function downloadPDF() {
    const element = document.getElementById('po-document');
    const opt = {
        margin:      [10, 10, 10, 10],
        filename:    '<?= htmlspecialchars($no_po) ?>.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    const btn = document.querySelector('.btn-dl');
    btn.textContent = '⏳ Generating PDF...';
    btn.disabled = true;
    html2pdf().set(opt).from(element).save().then(() => {
        btn.textContent = '⬇️ Download PDF';
        btn.disabled = false;
    });
}
</script>
</body>
</html>
