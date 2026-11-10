-- ============================================================================
-- FILE: database/db_furniture_truck.sql
-- DESKRIPSI: Skema Database, Relasi Foreign Key, Trigger Stok, & Data Dummy
-- TEMA: Sistem Penjualan Truk Perabotan Keliling (Ibu-Ibu Komplek)
-- ============================================================================

-- Membuat database baru jika belum ada dengan nama db_furniture_truck
CREATE DATABASE IF NOT EXISTS db_furniture_truck;

-- Menggunakan database db_furniture_truck untuk seluruh eksekusi di bawah ini
USE db_furniture_truck;

-- Menghapus tabel terlebih dahulu jika sudah ada (urutan dari tabel anak ke induk)
-- agar tidak terjadi error constraint saat import ulang
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS items;

-- ============================================================================
-- 1. TABEL MASTER 1: items (Data Perabotan di Atas Truk)
-- ============================================================================
CREATE TABLE items (
    -- id sebagai Primary Key yang bertambah otomatis (Auto Increment)
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- item_name menyimpan nama perabotan yang dijual (Wajib diisi / NOT NULL)
    item_name VARCHAR(100) NOT NULL,
    -- category menyimpan jenis perabotan (misal: Alat Mandi, Dapur, Kebersihan)
    category VARCHAR(50) NOT NULL,
    -- opening_price menyimpan harga buka awal dari abang penjual sebelum ditawar
    opening_price INT NOT NULL,
    -- stock menyimpan jumlah sisa stok barang di atas bak truk (Default 0)
    stock INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ============================================================================
-- 2. TABEL MASTER 2: customers (Data Pelanggan Ibu-Ibu Komplek)
-- ============================================================================
CREATE TABLE customers (
    -- id sebagai Primary Key untuk identifikasi unik tiap ibu-ibu pelanggan
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- customer_name menyimpan panggilan/nama ibu pelanggan
    customer_name VARCHAR(100) NOT NULL,
    -- complex_address menyimpan alamat blok/RT rumah pelanggan
    complex_address VARCHAR(150) NOT NULL,
    -- bargain_style menyimpan karakteristik unik gaya menawar ibu tersebut
    bargain_style VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ============================================================================
-- 3. TABEL TRANSAKSI (BERELASI): transactions (Catatan Belanja & Tawar Harga)
-- ============================================================================
CREATE TABLE transactions (
    -- id sebagai Primary Key transaksi pembelian
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- item_id sebagai Foreign Key yang merujuk ke kolom id pada tabel items
    item_id INT NOT NULL,
    -- customer_id sebagai Foreign Key yang merujuk ke kolom id pada tabel customers
    customer_id INT NOT NULL,
    -- quantity menyimpan jumlah unit barang yang dibeli dalam satu transaksi
    quantity INT NOT NULL,
    -- deal_price menyimpan total harga kesepakatan akhir setelah ditawar ibu-ibu
    deal_price INT NOT NULL,
    -- transaction_date menyimpan tanggal terjadinya transaksi
    transaction_date DATE NOT NULL,

    -- RELASI FOREIGN KEY 1: Ke tabel items
    -- ON DELETE RESTRICT: Mencegah barang dihapus jika masih ada di catatan transaksi
    -- ON UPDATE CASCADE: Jika ID barang berubah, item_id di transaksi ikut ter-update
    CONSTRAINT fk_transaction_item
        FOREIGN KEY (item_id) REFERENCES items(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    -- RELASI FOREIGN KEY 2: Ke tabel customers
    -- ON DELETE CASCADE: Jika data ibu pelanggan dihapus, riwayat transaksinya otomatis terhapus
    -- ON UPDATE CASCADE: Jika ID pelanggan berubah, customer_id di transaksi ikut ter-update
    CONSTRAINT fk_transaction_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================================
-- 4. TRIGGER OTOMATISASI STOK BARANG (items.stock)
-- ============================================================================
-- Mengubah delimiter sementara menjadi $$ agar titik koma di dalam BEGIN...END
-- tidak dianggap sebagai akhir dari perintah pembuatan Trigger
DELIMITER $$

-- TRIGGER 1: Otomatis mengurangi stok di tabel items saat ada transaksi baru (INSERT)
CREATE TRIGGER trg_after_transaction_insert
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    -- Mengurangi kolom stock pada tabel items sebesar jumlah beli (NEW.quantity)
    -- untuk barang yang memiliki id sesuai NEW.item_id
    UPDATE items 
    SET stock = stock - NEW.quantity 
    WHERE id = NEW.item_id;
END$$

-- TRIGGER 2: Otomatis menyesuaikan stok saat data transaksi diedit/diubah (UPDATE)
CREATE TRIGGER trg_after_transaction_update
AFTER UPDATE ON transactions
FOR EACH ROW
BEGIN
    -- Langkah A: Kembalikan dulu stok barang lama (OLD.item_id) sebanyak jumlah lama (OLD.quantity)
    UPDATE items 
    SET stock = stock + OLD.quantity 
    WHERE id = OLD.item_id;

    -- Langkah B: Kurangi stok barang baru (NEW.item_id) sebanyak jumlah baru (NEW.quantity)
    -- Cara ini aman baik ketika user hanya mengubah jumlah beli maupun mengganti jenis barang!
    UPDATE items 
    SET stock = stock - NEW.quantity 
    WHERE id = NEW.item_id;
END$$

-- TRIGGER 3: Otomatis mengembalikan stok ke tabel items saat transaksi dihapus (DELETE)
CREATE TRIGGER trg_after_transaction_delete
AFTER DELETE ON transactions
FOR EACH ROW
BEGIN
    -- Menambahkan kembali kolom stock pada tabel items sebesar jumlah yang batal dibeli (OLD.quantity)
    UPDATE items 
    SET stock = stock + OLD.quantity 
    WHERE id = OLD.item_id;
END$$

-- Mengembalikan delimiter ke tanda titik koma standar (;)
DELIMITER ;

-- ============================================================================
-- 5. DATA DUMMY AWAL (INSERT DATA CONTOH)
-- ============================================================================

-- Mengisi data awal perabotan khas truk keliling ke tabel items
INSERT INTO items (item_name, category, opening_price, stock) VALUES
('Baskom Jerman Anti Pecah (Ukuran Jumbo)', 'Alat Mandi & Cuci', 45000, 20),
('Kursi Plastik Napolly Merah', 'Kursi & Lemari', 85000, 15),
('Jemuran Handuk Aluminium Lipat', 'Alat Rumah Tangga', 150000, 10),
('Sapu Ijuk Garut Gagang Kayu', 'Alat Kebersihan', 35000, 25),
('Gayung Love Pink Menyala', 'Alat Mandi & Cuci', 15000, 40),
('Rak Piring Plastik 3 Susun + Tutup', 'Perlengkapan Dapur', 120000, 8);

-- Mengisi data awal ibu-ibu pelanggan komplek ke tabel customers
INSERT INTO customers (customer_name, complex_address, bargain_style) VALUES
('Bu Haji Lilis', 'Komplek Griya Asri Blok A No. 12', 'Nawar Sadis Setengah Harga'),
('Mama Daffa', 'Jl. Melati RT 04 / RW 02', 'Suka Minta Bonus Gayung/Sikat'),
('Bu RT Yuyun', 'Pos RT 01 Komplek Mekar', 'Beli Borongan Buat Hadiah Lomba 17an'),
('Teh Rina (Ibu Kos)', 'Kosan Putri Harmoni lantai 1', 'Langsung Deal Kalau Barang Awet');

-- Mengisi data awal transaksi pembelian ke tabel transactions
-- Catatan: Saat INSERT ini dijalankan, Trigger otomatis memotong stok di tabel items!
INSERT INTO transactions (item_id, customer_id, quantity, deal_price, transaction_date) VALUES
(1, 1, 2, 65000, '2026-11-08'),  -- Bu Haji Lilis beli 2 Baskom (Harga buka 90rb ditawar jadi 65rb)
(2, 4, 4, 280000, '2026-11-09'), -- Teh Rina beli 4 Kursi Napolly buat kosan
(4, 2, 1, 25000, '2026-11-10');  -- Mama Daffa beli 1 Sapu Ijuk ditawar jadi 25rb