-- =============================================
-- Tambahan tabel Purchase Order (PO)
-- Jalankan di phpMyAdmin setelah warpol_db.sql
-- =============================================

USE warpol_db;

-- Tabel PO Header
CREATE TABLE IF NOT EXISTS purchase_orders (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nomor_po     VARCHAR(30) NOT NULL UNIQUE,
    petugas_id   INT NOT NULL,
    pengirim     VARCHAR(100) NOT NULL,
    penerima     VARCHAR(100) NOT NULL,
    keterangan   TEXT,
    status       ENUM('pending','disetujui','ditolak') DEFAULT 'pending',
    catatan_admin TEXT,
    total_item   INT DEFAULT 0,
    approved_by  INT NULL,
    approved_at  TIMESTAMP NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (petugas_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Tabel PO Detail (item-item dalam satu PO)
CREATE TABLE IF NOT EXISTS po_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    po_id      INT NOT NULL,
    barang_id  INT NOT NULL,
    nama_barang VARCHAR(100) NOT NULL,
    satuan     VARCHAR(20) NOT NULL,
    jumlah     INT NOT NULL,
    keterangan VARCHAR(255),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id)
);
