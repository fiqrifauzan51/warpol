<?php
require_once 'config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: riwayatPO.php'); exit; }

// Ambil data PO
$po = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT po.*, u.nama as nama_petugas, u.username,
           a.nama as nama_approver
    FROM purchase_orders po
    JOIN users u ON po.petugas_id = u.id
    LEFT JOIN users a ON po.approved_by = a.id
    WHERE po.id = $id
"));

if (!$po) { echo "PO tidak ditemukan."; exit; }

// Cek: hanya PO milik sendiri (petugas) atau admin yang bisa cetak
$is_admin = ($_SESSION['user_role'] === 'admin');
if (!$is_admin && $po['petugas_id'] != $_SESSION['user_id']) {
    echo "Akses ditolak."; exit;
}

// Hanya PO disetujui yang bisa dicetak
if ($po['status'] !== 'disetujui') {
    echo "<script>alert('PO belum disetujui admin.'); history.back();</script>"; exit;
}

// Ambil items
$items = mysqli_query($conn, "SELECT * FROM po_items WHERE po_id = $id ORDER BY id");
$item_rows = mysqli_fetch_all($items, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order <?= htmlspecialchars($po['nomor_po']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f0f0f0;
            color: #000;
        }

        /* Tombol aksi — tidak dicetak */
        .action-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #0d9488;
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .action-bar span { color: white; font-family: sans-serif; font-size: 0.9rem; }
        .btn-print {
            padding: 0.5rem 1.5rem;
            background: white;
            color: #0d9488;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-family: sans-serif;
        }
        .btn-print:hover { background: #f0fdfa; }
        .btn-back {
            padding: 0.5rem 1.25rem;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 0.375rem;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            font-family: sans-serif;
        }
        .btn-back:hover { background: rgba(255,255,255,0.3); }

        /* Halaman PO */
        .page-wrapper { padding: 60px 2rem 2rem; }

        .po-paper {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 20mm 20mm 25mm 20mm;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: relative;
        }

        /* Header PO */
        .po-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6mm; border-bottom: 3px solid #0d9488; padding-bottom: 5mm; }
        .po-company h1 { font-size: 22pt; color: #0d9488; font-weight: 700; letter-spacing: 1px; }
        .po-company p { font-size: 9pt; color: #555; margin-top: 2px; }
        .po-title { text-align: right; }
        .po-title h2 { font-size: 18pt; font-weight: 700; color: #1e293b; letter-spacing: 2px; }
        .po-title .nomor { font-size: 10pt; color: #0d9488; font-weight: 600; margin-top: 4px; }
        .po-title .tgl { font-size: 9pt; color: #555; }

        /* Info box */
        .po-info { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; margin: 6mm 0; }
        .po-info-box { border: 1px solid #e2e8f0; border-radius: 4px; padding: 4mm; }
        .po-info-box .label { font-size: 7.5pt; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; font-family: Arial, sans-serif; }
        .po-info-box .value { font-size: 11pt; font-weight: 600; color: #1e293b; }

        /* Status approved banner */
        .po-status { background: #dcfce7; border: 1px solid #86efac; border-radius: 4px; padding: 2.5mm 4mm; margin-bottom: 5mm; display: flex; align-items: center; gap: 3mm; }
        .po-status .status-text { font-size: 9pt; color: #166534; font-family: Arial, sans-serif; font-weight: 600; }
        .po-status .approver { font-size: 8.5pt; color: #15803d; }

        /* Keterangan */
        .po-keterangan { background: #f8fafc; border-left: 3px solid #14b8a6; padding: 3mm 4mm; margin-bottom: 5mm; font-size: 9.5pt; color: #374151; }
        .po-keterangan .kl { font-size: 8pt; color: #888; font-family: Arial, sans-serif; text-transform: uppercase; margin-bottom: 1px; }

        /* Tabel item */
        .po-table { width: 100%; border-collapse: collapse; margin-bottom: 6mm; }
        .po-table thead { background: #0d9488; }
        .po-table thead th { color: white; padding: 3mm 4mm; text-align: left; font-size: 9pt; font-family: Arial, sans-serif; font-weight: 600; }
        .po-table thead th.center { text-align: center; }
        .po-table tbody tr:nth-child(even) { background: #f8fafc; }
        .po-table tbody td { padding: 3mm 4mm; font-size: 10pt; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .po-table tbody td.center { text-align: center; }
        .po-table tfoot td { padding: 3mm 4mm; font-size: 9.5pt; font-family: Arial, sans-serif; background: #f1f5f9; border-top: 2px solid #0d9488; font-weight: 600; }

        /* Tanda tangan */
        .po-signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 15mm; margin-top: 12mm; }
        .po-sign-box { text-align: center; }
        .po-sign-box .sign-label { font-size: 9.5pt; color: #374151; font-family: Arial, sans-serif; margin-bottom: 1mm; }
        .po-sign-box .sign-name { font-size: 10.5pt; font-weight: 700; color: #1e293b; margin-bottom: 2mm; }
        .po-sign-space { border-bottom: 1.5px solid #1e293b; height: 18mm; margin: 3mm 5mm; position: relative; }
        .po-sign-space .sign-hint { position: absolute; bottom: 2mm; left: 50%; transform: translateX(-50%); font-size: 7.5pt; color: #aaa; white-space: nowrap; font-family: Arial, sans-serif; }
        .po-sign-box .sign-date { font-size: 8.5pt; color: #555; margin-top: 2mm; }

        /* Footer */
        .po-footer { border-top: 1px solid #e2e8f0; padding-top: 4mm; margin-top: auto; display: flex; justify-content: space-between; align-items: center; }
        .po-footer .footer-note { font-size: 7.5pt; color: #94a3b8; font-family: Arial, sans-serif; }
        .po-footer .footer-brand { font-size: 8pt; color: #0d9488; font-family: Arial, sans-serif; font-weight: 600; }

        /* Watermark disetujui */
        .po-watermark {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 72pt;
            color: rgba(22, 163, 74, 0.07);
            font-weight: 900;
            letter-spacing: 8px;
            pointer-events: none;
            white-space: nowrap;
            z-index: 0;
            font-family: Arial, sans-serif;
        }

        /* Print styles */
        @media print {
            body { background: white; }
            .action-bar { display: none !important; }
            .page-wrapper { padding: 0; }
            .po-paper { box-shadow: none; margin: 0; width: 100%; min-height: auto; }
            @page { size: A4; margin: 0; }
        }
    </style>
</head>
<body>

<!-- Action bar (tidak dicetak) -->
<div class="action-bar">
    <a href="riwayatPO.php" class="btn-back">← Kembali</a>
    <span>Purchase Order: <strong><?= htmlspecialchars($po['nomor_po']) ?></strong></span>
    <button class="btn-print" onclick="window.print()">
        🖨️ Cetak / Download PDF
    </button>
    <span style="font-size:0.8rem;opacity:0.8;">(Pilih "Save as PDF" di dialog cetak untuk download)</span>
</div>

<div class="page-wrapper">
    <div class="po-paper">

        <!-- Watermark -->
        <div class="po-watermark">DISETUJUI</div>

        <!-- Header -->
        <div class="po-header">
            <div class="po-company">
                <h1> KALA Coffee</h1>
                 <img src="poto/logo.png" alt="Logo" class="img-logo-sidebar">
                <p>Sistem Manajemen Stok Opname</p>
                <p style="margin-top:2px;font-size:8.5pt;color:#888;">Coffee Shop Management System</p>
            </div>
            <div class="po-title">
                <h2>PURCHASE ORDER</h2>
                <div class="nomor"><?= htmlspecialchars($po['nomor_po']) ?></div>
                <div class="tgl">Tanggal: <?= date('d F Y', strtotime($po['created_at'])) ?></div>
            </div>
        </div>

        <!-- Status -->
        <div class="po-status">
            <span style="font-size:12pt;">✅</span>
            <div>
                <div class="status-text">TELAH DISETUJUI</div>
                <div class="approver">
                    Disetujui oleh: <strong><?= htmlspecialchars($po['nama_approver'] ?? 'Admin') ?></strong>
                    <?php if ($po['approved_at']): ?>
                        &nbsp;|&nbsp; <?= date('d M Y, H:i', strtotime($po['approved_at'])) ?> WIB
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Info Pengirim & Penerima -->
        <div class="po-info">
            <div class="po-info-box">
                <div class="label">Dibuat oleh (Pengirim)</div>
                <div class="value"><?= htmlspecialchars($po['pengirim']) ?></div>
                <div style="font-size:8.5pt;color:#888;margin-top:2px;">Petugas: <?= htmlspecialchars($po['nama_petugas']) ?></div>
            </div>
            <div class="po-info-box">
                <div class="label">Ditujukan kepada (Penerima)</div>
                <div class="value"><?= htmlspecialchars($po['penerima']) ?></div>
            </div>
        </div>

        <!-- Keterangan -->
        <?php if (!empty($po['keterangan'])): ?>
        <div class="po-keterangan">
            <div class="kl">Keterangan</div>
            <?= htmlspecialchars($po['keterangan']) ?>
        </div>
        <?php endif; ?>

        <!-- Tabel item -->
        <table class="po-table">
            <thead>
                <tr>
                    <th style="width:5%">No</th>
                    <th style="width:40%">Nama Barang</th>
                    <th class="center" style="width:12%">Jumlah</th>
                    <th style="width:10%">Satuan</th>
                    <th style="width:33%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($item_rows as $i => $item): ?>
                <tr>
                    <td class="center"><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                    <td class="center"><strong><?= $item['jumlah'] ?></strong></td>
                    <td><?= htmlspecialchars($item['satuan']) ?></td>
                    <td style="font-size:9pt;color:#555;"><?= htmlspecialchars($item['keterangan'] ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total Item</td>
                    <td class="center" style="font-size:11pt;"><?= count($item_rows) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        <!-- Catatan Admin -->
        <?php if (!empty($po['catatan_admin'])): ?>
        <div style="background:#fef9c3;border:1px solid #fde047;border-radius:4px;padding:3mm 4mm;margin-bottom:6mm;font-size:9pt;">
            <span style="font-size:8pt;color:#92400e;font-family:Arial,sans-serif;text-transform:uppercase;">Catatan Admin: </span>
            <?= htmlspecialchars($po['catatan_admin']) ?>
        </div>
        <?php endif; ?>

        <!-- Tanda Tangan -->
        <div class="po-signatures">
            <div class="po-sign-box">
                <div class="sign-label">Dibuat oleh</div>
                <div class="sign-name"><?= htmlspecialchars($po['pengirim']) ?></div>
                <div class="po-sign-space">
                    <span class="sign-hint">(Tanda tangan)</span>
                </div>
                <div class="sign-date">
                    Tasikmalaya, <?= date('d F Y', strtotime($po['created_at'])) ?>
                </div>
            </div>
            <div class="po-sign-box">
                <div class="sign-label">Diterima oleh</div>
                <div class="sign-name"><?= htmlspecialchars($po['penerima']) ?></div>
                <div class="po-sign-space">
                    <span class="sign-hint">(Tanda tangan)</span>
                </div>
                <div class="sign-date">
                    Tasikmalaya, _____ / _____ / _________
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="po-footer">
            <div class="footer-note">
                Dokumen ini digenerate otomatis oleh sistem &nbsp;|&nbsp;
                Dicetak: <?= date('d M Y H:i') ?> WIB
            </div>
            <div class="footer-brand">☕ KALA Coffee</div>
        </div>

    </div>
</div>

</body>
</html>
