<?php
require_once 'config.php';
requireLogin();

$success = $error = '';
$barang_list = mysqli_query($conn, "SELECT id, nama_barang, satuan FROM barang ORDER BY nama_barang");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pengirim   = trim($_POST['pengirim'] ?? '');
    $penerima   = trim($_POST['penerima'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $barang_ids  = $_POST['barang_id'] ?? [];
    $jumlah_arr  = $_POST['jumlah'] ?? [];
    $ket_arr     = $_POST['ket_item'] ?? [];
    $user_id     = $_SESSION['user_id'];

    // Filter baris yang valid
    $items = [];
    foreach ($barang_ids as $i => $bid) {
        if (!empty($bid) && !empty($jumlah_arr[$i]) && (int)$jumlah_arr[$i] > 0) {
            $stmt = mysqli_prepare($conn, "SELECT nama_barang, satuan FROM barang WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'i', $bid);
            mysqli_stmt_execute($stmt);
            $b = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if ($b) {
                $items[] = [
                    'barang_id'   => (int)$bid,
                    'nama_barang' => $b['nama_barang'],
                    'satuan'      => $b['satuan'],
                    'jumlah'      => (int)$jumlah_arr[$i],
                    'keterangan'  => trim($ket_arr[$i] ?? ''),
                ];
            }
        }
    }

    if (empty($pengirim) || empty($penerima)) {
        $error = 'Pengirim dan penerima wajib diisi.';
    } elseif (empty($items)) {
        $error = 'Minimal satu item barang harus diisi.';
    } else {
        // Generate nomor PO: PO-YYYYMMDD-XXX
        $tgl    = date('Ymd');
        $hitung = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM purchase_orders WHERE DATE(created_at) = CURDATE()"))['n'];
        $nomor  = 'PO-' . $tgl . '-' . str_pad($hitung + 1, 3, '0', STR_PAD_LEFT);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO purchase_orders (nomor_po, petugas_id, pengirim, penerima, keterangan, total_item)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, 'sisss i', $nomor, $user_id, $pengirim, $penerima, $keterangan, $total);
        // Fix bind
        $total = count($items);
        $stmt2 = mysqli_prepare($conn,
            "INSERT INTO purchase_orders (nomor_po, petugas_id, pengirim, penerima, keterangan, total_item)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt2, 'sissi', $nomor, $user_id, $pengirim, $penerima, $keterangan, $total);
        // Hmm let me redo this cleanly
        $q = "INSERT INTO purchase_orders (nomor_po, petugas_id, pengirim, penerima, keterangan, total_item)
              VALUES ('$nomor', $user_id, '" . mysqli_real_escape_string($conn, $pengirim) . "',
              '" . mysqli_real_escape_string($conn, $penerima) . "',
              '" . mysqli_real_escape_string($conn, $keterangan) . "', " . count($items) . ")";

        if (mysqli_query($conn, $q)) {
            $po_id = mysqli_insert_id($conn);
            foreach ($items as $item) {
                $ket_esc   = mysqli_real_escape_string($conn, $item['keterangan']);
                $nama_esc  = mysqli_real_escape_string($conn, $item['nama_barang']);
                $sat_esc   = mysqli_real_escape_string($conn, $item['satuan']);
                mysqli_query($conn, "INSERT INTO po_items (po_id, barang_id, nama_barang, satuan, jumlah, keterangan)
                    VALUES ($po_id, {$item['barang_id']}, '$nama_esc', '$sat_esc', {$item['jumlah']}, '$ket_esc')");
            }
            $success = "Purchase Order <strong>$nomor</strong> berhasil dibuat dan menunggu persetujuan admin.";
        } else {
            $error = 'Gagal menyimpan PO: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Purchase Order - KALA Coffee</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .po-form-section { background: white; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .po-form-section h3 { font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        .items-table th { background: #f3f4f6; padding: 0.6rem 0.75rem; text-align: left; font-size: 0.8rem; font-weight: 600; color: #374151; }
        .items-table td { padding: 0.5rem 0.4rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .items-table select, .items-table input { width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; font-size: 0.875rem; background: #f9fafb; }
        .items-table select:focus, .items-table input:focus { border-color: #14b8a6; background: white; outline: none; }
        .btn-add-row { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: #f0fdfa; border: 1px dashed #14b8a6; color: #0d9488; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; transition: all 0.2s; }
        .btn-add-row:hover { background: #ccfbf1; }
        .btn-del-row { padding: 0.35rem 0.6rem; background: #fee2e2; color: #dc2626; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.8rem; transition: opacity 0.2s; }
        .btn-del-row:hover { opacity: 0.8; }
        .img-logo-sidebar { width:42px;height:42px;object-fit:cover;border-radius:50%;border:2px solid rgba(255,255,255,0.5);flex-shrink:0; }
        .img-nav { width:26px;height:26px;object-fit:cover;border-radius:6px;flex-shrink:0; }
        .img-header { width:24px;height:24px;object-fit:cover;border-radius:5px; }
    </style>
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="poto/logo.png" alt="Logo" class="img-logo-sidebar">
                <div class="logo-text"><h2>KALA Coffee</h2><p>Coffee Shop</p></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashWar.php" class="nav-item"><span>Dashboard</span></a>

            <div class="nav-group" id="group-stok">
                <button type="button" class="nav-group-toggle" data-target="menuStok" aria-expanded="false">
                    <img src="poto/input.png" alt="" class="img-nav">
                    <span>Stok Opname</span>
                    <span class="chevron">▸</span>
                </button>
                <div class="nav-group-menu" id="menuStok">
                    <a href="inputWar.php" class="nav-sub-item"><img src="poto/input.png" alt=""><span>Input Stok Opname</span></a>
                    <a href="riwayatWar.php" class="nav-sub-item"><img src="poto/riwayat.png" alt=""><span>Riwayat Stok</span></a>
                </div>
            </div>

            <div class="nav-group open" id="group-order">
                <button type="button" class="nav-group-toggle" data-target="menuOrder" aria-expanded="true">
                    <img src="poto/input.png" alt="" class="img-nav">
                    <span>Order Barang</span>
                    <span class="chevron">▸</span>
                </button>
                <div class="nav-group-menu" id="menuOrder">
                    <a href="orderBarang.php" class="nav-sub-item"><img src="poto/input.png" alt=""><span>Order Barang</span></a>
                    <a href="riwayatOrder.php" class="nav-sub-item"><img src="poto/riwayat.png" alt=""><span>Riwayat Order</span></a>
                    <a href="buatPO.php" class="nav-sub-item active"><img src="poto/input.png" alt=""><span>Buat PO</span></a>
                    <a href="riwayatPO.php" class="nav-sub-item"><img src="poto/riwayat.png" alt=""><span>Riwayat PO</span></a>
                </div>
            </div>
        </nav>
    </aside>

    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <h1>Selamat datang, <?= htmlspecialchars($_SESSION['user_nama']) ?></h1>
                <p>Petugas - KALA Coffee</p>
            </div>
            <div class="header-right">
                <button class="notification-btn">
                    <img src="poto/notif.png" alt="Notifikasi" class="img-header">
                    <span class="notification-badge"></span>
                </button>
                <a href="logout.php" class="logout-btn">
                    <img src="poto/keluar.jpg" alt="Keluar" class="img-header">
                    <span>Keluar</span>
                </a>
            </div>
        </header>

        <main class="page-content">
            <div class="page-header">
                <h1>Buat Purchase Order</h1>
                <p>Ajukan permintaan order barang kepada admin</p>
            </div>

            <?php if ($success): ?>
                <div style="background:#dcfce7;color:#166534;padding:1rem;border-radius:0.5rem;margin-bottom:1rem;">
                    ✅ <?= $success ?> — <a href="riwayatPO.php" style="color:#0d9488;font-weight:600;">Lihat Riwayat PO</a>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background:#fee2e2;color:#dc2626;padding:1rem;border-radius:0.5rem;margin-bottom:1rem;">❌ <?= $error ?></div>
            <?php endif; ?>

            <form method="POST" id="formPO">
                <!-- Info Umum -->
                <div class="po-form-section">
                    <h3>📋 Informasi Umum PO</h3>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Pengirim <span style="color:#dc2626">*</span></label>
                            <input type="text" name="pengirim" value="<?= htmlspecialchars($_SESSION['user_nama']) ?>" required placeholder="Nama pengirim / petugas">
                        </div>
                        <div class="form-group">
                            <label>Penerima <span style="color:#dc2626">*</span></label>
                            <input type="text" name="penerima" required placeholder="Nama penerima / supplier / admin">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Keterangan Umum</label>
                        <textarea name="keterangan" rows="2" placeholder="Keperluan order, catatan khusus, dll..."></textarea>
                    </div>
                </div>

                <!-- Daftar Item -->
                <div class="po-form-section">
                    <h3>📦 Daftar Item Barang</h3>
                    <div style="overflow-x:auto;">
                        <table class="items-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width:35%">Nama Barang</th>
                                    <th style="width:12%">Jumlah</th>
                                    <th style="width:12%">Satuan</th>
                                    <th style="width:33%">Keterangan Item</th>
                                    <th style="width:8%">Hapus</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr>
                                    <td>
                                        <select name="barang_id[]" required onchange="setSatuan(this)">
                                            <option value="">Pilih Barang</option>
                                            <?php
                                            mysqli_data_seek($barang_list, 0);
                                            while ($b = mysqli_fetch_assoc($barang_list)):
                                            ?>
                                                <option value="<?= $b['id'] ?>" data-satuan="<?= htmlspecialchars($b['satuan']) ?>">
                                                    <?= htmlspecialchars($b['nama_barang']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </td>
                                    <td><input type="number" name="jumlah[]" min="1" placeholder="0" required></td>
                                    <td><input type="text" name="satuan_display[]" placeholder="Satuan" readonly style="background:#e9ecef;color:#6b7280;cursor:not-allowed;"></td>
                                    <td><input type="text" name="ket_item[]" placeholder="Opsional..."></td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-del-row" onclick="hapusBaris(this)" title="Hapus baris">✕</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn-add-row" onclick="tambahBaris()">
                        ＋ Tambah Barang
                    </button>
                </div>

                <!-- Tombol -->
                <div class="btn-group">
                    <button type="submit" class="btn-primary" style="max-width:220px;">
                        📄 Kirim PO ke Admin
                    </button>
                    <button type="button" class="btn-secondary" onclick="resetForm()">Reset</button>
                    <a href="riwayatPO.php" class="btn-secondary" style="display:inline-flex;align-items:center;text-decoration:none;padding:0.75rem 1rem;">
                        📜 Lihat Riwayat PO
                    </a>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
// Template HTML untuk satu baris item (diambil dari baris pertama)
function getBarangOptions() {
    const select = document.querySelector('select[name="barang_id[]"]');
    return select ? select.innerHTML : '';
}

function tambahBaris() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="barang_id[]" required onchange="setSatuan(this)">
                ${getBarangOptions()}
            </select>
        </td>
        <td><input type="number" name="jumlah[]" min="1" placeholder="0" required></td>
        <td><input type="text" name="satuan_display[]" placeholder="Satuan" readonly style="background:#e9ecef;color:#6b7280;cursor:not-allowed;"></td>
        <td><input type="text" name="ket_item[]" placeholder="Opsional..."></td>
        <td style="text-align:center;">
            <button type="button" class="btn-del-row" onclick="hapusBaris(this)" title="Hapus baris">✕</button>
        </td>
    `;
    tbody.appendChild(tr);
}

function hapusBaris(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.rows.length <= 1) { alert('Minimal harus ada satu item.'); return; }
    btn.closest('tr').remove();
}

function setSatuan(select) {
    const opt = select.options[select.selectedIndex];
    const satuan = opt.dataset.satuan || '';
    const row = select.closest('tr');
    row.querySelector('input[name="satuan_display[]"]').value = satuan;
}

function resetForm() {
    document.getElementById('formPO').reset();
    const tbody = document.getElementById('itemsBody');
    while (tbody.rows.length > 1) tbody.deleteRow(1);
}

// Collapsible sidebar
document.querySelectorAll('.nav-group-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const targetId = btn.getAttribute('data-target');
        const menu = document.getElementById(targetId);
        if (!menu) return;
        const group = btn.closest('.nav-group');
        const isOpen = group.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});
</script>
</body>
</html>
