<?php
require_once 'config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: riwayatOrder.php'); exit; }

// Ambil data order header
$order = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT o.id, o.no_po, o.created_at, o.status, o.notes,
           o.pengirim, o.penerima, o.approved_at, o.user_id,
           u.nama AS petugas
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = $id
"));

if (!$order) { echo 'Order tidak ditemukan.'; exit; }

if ($order['status'] !== 'received') {
    echo '<script>alert("PDF hanya bisa didownload setelah order di-ACC oleh Admin.");history.back();</script>';
    exit;
}

// Hanya petugas pemilik order atau admin yang bisa akses
if ($_SESSION['user_role'] !== 'admin' && $order['user_id'] != $_SESSION['user_id']) {
    echo 'Akses ditolak.'; exit;
}

// Ambil semua item dari order_items
$items_result = mysqli_query($conn, "
    SELECT oi.qty, oi.notes AS item_notes,
           b.nama_barang, b.satuan
    FROM order_items oi
    JOIN barang b ON oi.barang_id = b.id
    WHERE oi.order_id = $id
    ORDER BY oi.id ASC
");
$items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $items[] = $row;
}

// Fallback: kalau order_items kosong, pakai data dari tabel orders lama
if (empty($items)) {
    $legacy = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT o.qty, o.notes AS item_notes, b.nama_barang, b.satuan
        FROM orders o JOIN barang b ON o.barang_id = b.id
        WHERE o.id = $id
    "));
    if ($legacy) $items[] = $legacy;
}

$total_qty  = array_sum(array_column($items, 'qty'));
$no_po      = htmlspecialchars($order['no_po'] ?? 'PO-' . $id);
$tgl_order  = date('d F Y', strtotime($order['created_at']));
$tgl_acc    = $order['approved_at'] ? date('d F Y', strtotime($order['approved_at'])) : '-';
$pengirim   = htmlspecialchars($order['pengirim'] ?? '-');
$penerima   = htmlspecialchars($order['penerima'] ?? '-');
$petugas    = htmlspecialchars($order['petugas']);
$po_notes   = htmlspecialchars($order['notes'] ?? '-');
$jml_item   = count($items);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PO - <?= $no_po ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            color: #111;
            background: #fff;
        }
        .po-wrapper {
            width: 794px;
            min-height: 1123px;
            margin: 0 auto;
            padding: 50px 60px;
        }

        /* KOP */
        .kop {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px double #000;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }
        .kop-logo {
            width: 70px; height: 70px;
            border-radius: 50%;
            border: 2px solid #0d9488;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; flex-shrink: 0;
        }
        .kop-text { flex: 1; text-align: center; }
        .kop-text h1 {
            font-size: 22px; font-weight: 700;
            letter-spacing: 1px; text-transform: uppercase;
        }
        .kop-text p { font-size: 11px; color: #444; margin-top: 2px; }
        .kop-right { width: 130px; text-align: right; font-size: 11px; color: #555; }
        .status-badge {
            display: inline-block;
            background: #dcfce7; color: #166534;
            padding: 3px 14px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
            border: 1px solid #bbf7d0;
        }

        /* JUDUL */
        .po-title { text-align: center; margin: 18px 0 6px; }
        .po-title h2 {
            font-size: 16px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 2px;
            border-bottom: 1.5px solid #000;
            display: inline-block; padding-bottom: 3px;
        }
        .po-no { text-align: center; font-size: 12px; color: #444; margin-bottom: 18px; }

        /* INFO */
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 3px 6px; vertical-align: top; font-size: 12.5px; }
        .info-table td:first-child { width: 150px; font-weight: 600; }
        .info-table td:nth-child(2) { width: 10px; }

        /* TABEL BARANG */
        .barang-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .barang-table th {
            background: #0d9488; color: #fff;
            padding: 8px 10px; text-align: center;
            font-size: 12px; font-weight: 600;
            border: 1px solid #0d9488;
        }
        .barang-table td {
            border: 1px solid #ccc;
            padding: 8px 10px; font-size: 12px;
            text-align: center;
        }
        .barang-table td.left { text-align: left; }
        .barang-table tr:nth-child(even) td { background: #f0fdfa; }
        .barang-table tfoot td {
            font-weight: 700;
            background: #f8fafc;
            border-top: 2px solid #0d9488;
        }

        /* KETERANGAN */
        .keterangan-box {
            border: 1px solid #ccc; border-radius: 6px;
            padding: 10px 14px; margin-bottom: 24px;
            font-size: 12px; min-height: 44px;
        }
        .keterangan-box .label { font-weight: 700; margin-bottom: 4px; }

        /* TTD */
        .ttd-section { display: flex; justify-content: space-between; margin-top: 30px; }
        .ttd-box { width: 45%; text-align: center; }
        .ttd-box .ttd-label { font-weight: 700; font-size: 12px; margin-bottom: 4px; }
        .ttd-box .ttd-name-label { font-size: 11.5px; color: #444; margin-bottom: 70px; }
        .ttd-box .ttd-line { border-top: 1.5px solid #111; width: 80%; margin: 0 auto 4px; }
        .ttd-box .ttd-name { font-size: 12px; font-weight: 600; }
        .ttd-box .ttd-role { font-size: 11px; color: #666; }

        /* FOOTER */
        .po-footer {
            margin-top: 40px; border-top: 1px solid #ddd;
            padding-top: 10px; text-align: center;
            font-size: 10px; color: #888;
        }

        /* WATERMARK */
        .watermark {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 90px; font-weight: 900;
            color: rgba(20,184,166,0.06);
            pointer-events: none; letter-spacing: 4px;
            text-transform: uppercase; z-index: 0; white-space: nowrap;
        }

        /* PRINT BAR */
        .print-bar {
            background: #0d9488; padding: 12px 20px;
            display: flex; align-items: center; justify-content: space-between;
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 100; box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .print-bar span { color: white; font-family: sans-serif; font-size: 14px; }
        .print-bar .btns { display: flex; gap: 10px; }
        .btn-print {
            background: white; color: #0d9488; border: none;
            padding: 8px 20px; border-radius: 6px;
            font-size: 13px; font-weight: 600; cursor: pointer; font-family: sans-serif;
        }
        .btn-back {
            background: rgba(255,255,255,0.2); color: white;
            border: 1px solid rgba(255,255,255,0.4);
            padding: 8px 16px; border-radius: 6px;
            font-size: 13px; cursor: pointer; font-family: sans-serif; text-decoration: none;
        }

        @media print {
            .print-bar { display: none !important; }
            .po-wrapper { padding: 30px 50px; }
            body { margin: 0; padding-top: 0 !important; }
        }
        body { padding-top: 55px; }
    </style>
</head>
<body>

<div class="print-bar">
    <span>📋 Purchase Order — <?= $no_po ?> (<?= $jml_item ?> barang)</span>
    <div class="btns">
        <a href="riwayatOrder.php" class="btn-back">← Kembali</a>
        <button class="btn-print" onclick="window.print()">🖨️ Print / Simpan PDF</button>
    </div>
</div>

<div class="watermark">PT KALA Coffee</div>

<div class="po-wrapper">

    <!-- KOP SURAT -->
    <div class="kop">
        <div class="kop-logo">
            <img src="poto/logo.png" alt="KALA Coffe"
                 style="width:90px;height:90px;object-fit:cover;border-radius:50%;margin-bottom:1rem;border:3px solid #0B448C;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
        </div>
        <div class="kop-text">
            <h1>KALA Coffee</h1>
            <p>Sistem Manajemen Stok Opname</p>
            <p>Jl.Sukirman No. 1 Tasikmalaya, Coffee District &nbsp;|&nbsp; Telp: 0852-8276-3391</p>
        </div>
        <div class="kop-right">
            <div class="status-badge">✓ DISETUJUI</div>
            <p style="margin-top:8px;">Tgl ACC:<br><strong><?= $tgl_acc ?></strong></p>
        </div>
    </div>

    <!-- JUDUL -->
    <div class="po-title"><h2>Purchase Order (PO)</h2></div>
    <div class="po-no">Nomor: <?= $no_po ?></div>

    <!-- INFO -->
    <table class="info-table">
        <tr>
            <td>Tanggal Order</td><td>:</td><td><?= $tgl_order ?></td>
            <td width="60"></td>
            <td width="130">Nomor PO</td><td width="10">:</td><td><strong><?= $no_po ?></strong></td>
        </tr>
        <tr>
            <td>Dibuat oleh</td><td>:</td><td><?= $petugas ?></td>
            <td></td>
            <td>Tanggal ACC</td><td>:</td><td><?= $tgl_acc ?></td>
        </tr>
        <tr>
            <td>Jumlah Item</td><td>:</td><td><?= $jml_item ?> jenis barang</td>
            <td></td><td>Status</td><td>:</td>
            <td><span class="status-badge">✓ Disetujui Admin</span></td>
        </tr>
    </table>

    <!-- TABEL BARANG — multi item -->
    <table class="barang-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th>Nama Barang</th>
                <th width="90">Satuan</th>
                <th width="90">Jumlah</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i => $item): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td class="left"><?= htmlspecialchars($item['nama_barang']) ?></td>
                <td><?= htmlspecialchars($item['satuan']) ?></td>
                <td><?= number_format($item['qty']) ?></td>
                <td class="left"><?= htmlspecialchars($item['item_notes'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php
        // Tambah baris kosong supaya tabel tidak terlalu pendek (min 5 baris)
        $kosong = max(0, 5 - $jml_item);
        for ($k = 0; $k < $kosong; $k++):
        ?>
            <tr>
                <td><?= $jml_item + $k + 1 ?></td>
                <td></td><td></td><td></td><td></td>
            </tr>
        <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="left" style="padding-left:10px;">
                    Total <?= $jml_item ?> jenis barang
                </td>
                <td><?= number_format($total_qty) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- CATATAN UMUM -->
    <div class="keterangan-box">
        <div class="label">Catatan Umum PO:</div>
        <div><?= $po_notes !== '-' ? $po_notes : '<em style="color:#9ca3af;">Tidak ada catatan</em>' ?></div>
    </div>

    <!-- TANDA TANGAN -->
    <div class="ttd-section">
        <div class="ttd-box">
            <div class="ttd-label">Pengirim</div>
            <div class="ttd-name-label"><?= $pengirim ?></div>
            <div class="ttd-line"></div>
            <div class="ttd-name"><?= $pengirim ?></div>
            <div class="ttd-role">Pengirim Barang</div>
        </div>
        <div class="ttd-box">
            <div class="ttd-label">Penerima</div>
            <div class="ttd-name-label"><?= $penerima ?></div>
            <div class="ttd-line"></div>
            <div class="ttd-name"><?= $penerima ?></div>
            <div class="ttd-role">Penerima Barang</div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="po-footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Manajemen Stok Opname KALA Coffee &nbsp;|&nbsp;
        <?= $no_po ?> &nbsp;|&nbsp; Dicetak pada <?= date('d F Y H:i') ?>
    </div>
</div>

<script>
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 300));
}
</script>
</body>
</html>