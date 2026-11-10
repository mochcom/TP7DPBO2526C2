<?php
// ============================================================================
// FILE: class/Transaction.php
// DESKRIPSI: Class Model OOP untuk menangani operasi CRUD pada tabel 'transactions'
//            yang berelasi (Foreign Key) dengan tabel 'items' dan 'customers'
// ============================================================================

// Memanggil file konfigurasi database (config/db.php) menggunakan path absolut __DIR__
require_once __DIR__ . '/../config/db.php';

class Transaction {
    // Properti private untuk menyimpan objek koneksi PDO dari class Database
    private $db;

    // Method Constructor: otomatis dipanggil saat objek Transaction dibuat (new Transaction())
    public function __construct() {
        // Menginstansiasi objek dari class Database
        $database = new Database();
        // Menyimpan koneksi PDO aktif ke dalam properti $this->db
        $this->db = $database->conn;
    }

    // ========================================================================
    // 1. READ (Ambil Semua Data Transaksi + JOIN Tabel + Hitung Selisih Nawar)
    // ========================================================================
    public function getAllTransactions() {
        // Menyusun query SELECT dengan menggabungkan (INNER JOIN) 3 tabel:
        // transactions (t), items (i), dan customers (c)
        // Menghitung normal_total (harga buka * jumlah beli) dan
        // saved_amount (selisih hemat hasil tawar-menawar ibu-ibu dengan abang truk)
        $sql = "SELECT 
                    t.*, 
                    i.item_name, 
                    i.category,
                    i.opening_price, 
                    c.customer_name, 
                    c.complex_address,
                    c.bargain_style,
                    (i.opening_price * t.quantity) AS normal_total,
                    ((i.opening_price * t.quantity) - t.deal_price) AS saved_amount
                FROM transactions t
                INNER JOIN items i ON t.item_id = i.id
                INNER JOIN customers c ON t.customer_id = c.id
                ORDER BY t.transaction_date DESC, t.id DESC";
                
        // Menyiapkan query menggunakan Prepared Statement sesuai aturan wajib modul
        $stmt = $this->db->prepare($sql);
        
        // Mengeksekusi prepared statement
        $stmt->execute();
        
        // Mengembalikan seluruh baris hasil gabungan tabel sebagai associative array
        return $stmt->fetchAll();
    }

    // ========================================================================
    // 2. READ BY ID (Ambil 1 Data Transaksi Berdasarkan ID untuk Form Edit)
    // ========================================================================
    public function getTransactionById($id) {
        // Menyiapkan query SELECT dengan placeholder (?) untuk mencari 1 transaksi spesifik
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id = ?");
        
        // Mengeksekusi statement dengan mengirimkan parameter [$id]
        $stmt->execute([$id]);
        
        // Mengambil 1 baris data transaksi untuk ditampilkan di form Update
        return $stmt->fetch();
    }

    // ========================================================================
    // 3. CREATE (Tambah Transaksi Baru + Validasi Stok dengan Prepared Statement)
    // ========================================================================
    public function createTransaction($item_id, $customer_id, $quantity, $deal_price, $transaction_date) {
        // LANGKAH 1: Cek ketersediaan stok barang di tabel items menggunakan Prepared Statement
        // (Bukan memakai $this->db->query() mentah agar mematuhi aturan keamanan modul)
        $stmtCheck = $this->db->prepare("SELECT item_name, stock FROM items WHERE id = ?");
        $stmtCheck->execute([$item_id]);
        $itemData = $stmtCheck->fetch();

        // Jika data barang tidak ditemukan di database
        if (!$itemData) {
            return [
                'status' => false,
                'message' => 'Gagal! Data perabotan tidak ditemukan di atas truk.'
            ];
        }

        // Memeriksa apakah jumlah yang ingin dibeli ibu-ibu melebihi sisa stok di truk
        if ($itemData['stock'] < $quantity) {
            return [
                'status' => false,
                'message' => 'Stok tidak cukup! Sisa stok "' . $itemData['item_name'] . '" di truk hanya tinggal ' . $itemData['stock'] . ' unit.'
            ];
        }

        // LANGKAH 2: Jika stok mencukupi, jalankan query INSERT ke tabel transactions
        // Catatan: Begitu INSERT ini sukses, TRIGGER 'trg_after_transaction_insert' di MySQL
        // akan otomatis memotong stok di tabel items sebanyak $quantity!
        $sql = "INSERT INTO transactions (item_id, customer_id, quantity, deal_price, transaction_date) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$item_id, $customer_id, $quantity, $deal_price, $transaction_date]);

        // Mengembalikan array status dan pesan untuk ditampilkan sebagai notifikasi di View
        return [
            'status' => $success,
            'message' => $success ? 'Transaksi belanja ibu berhasil dicatat & stok truk otomatis berkurang!' : 'Gagal menyimpan transaksi.'
        ];
    }

    // ========================================================================
    // 4. UPDATE (Ubah Data Transaksi + Validasi Selisih Stok)
    // ========================================================================
    public function updateTransaction($id, $item_id, $customer_id, $quantity, $deal_price, $transaction_date) {
        // LANGKAH 1: Ambil data transaksi lama sebelum di-update untuk mengetahui jumlah & barang sebelumnya
        $oldTx = $this->getTransactionById($id);
        if (!$oldTx) {
            return [
                'status' => false,
                'message' => 'Data transaksi yang ingin diubah tidak ditemukan!'
            ];
        }

        // LANGKAH 2: Ambil data stok barang tujuan saat ini menggunakan Prepared Statement
        $stmtCheck = $this->db->prepare("SELECT item_name, stock FROM items WHERE id = ?");
        $stmtCheck->execute([$item_id]);
        $itemData = $stmtCheck->fetch();

        // Hitung stok efektif yang tersedia:
        // Jika barang yang dipilih masih barang yang sama dengan transaksi lama,
        // maka stok yang tersedia adalah stok di truk saat ini ditambah jumlah beli lama ($oldTx['quantity'])
        $availableStock = $itemData['stock'];
        if ($oldTx['item_id'] == $item_id) {
            $availableStock += $oldTx['quantity'];
        }

        // Validasi apakah stok efektif mencukupi untuk jumlah beli yang baru ($quantity)
        if ($availableStock < $quantity) {
            return [
                'status' => false,
                'message' => 'Gagal Update! Stok "' . $itemData['item_name'] . '" tidak mencukupi (Maksimal tersedia: ' . $availableStock . ' unit).'
            ];
        }

        // LANGKAH 3: Eksekusi query UPDATE menggunakan Prepared Statement
        // Catatan: TRIGGER 'trg_after_transaction_update' di MySQL akan otomatis mengembalikan
        // stok barang lama dan memotong stok barang baru!
        $sql = "UPDATE transactions 
                SET item_id = ?, customer_id = ?, quantity = ?, deal_price = ?, transaction_date = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$item_id, $customer_id, $quantity, $deal_price, $transaction_date, $id]);

        return [
            'status' => $success,
            'message' => $success ? 'Data transaksi berhasil diperbarui & stok otomatis disesuaikan!' : 'Gagal memperbarui transaksi.'
        ];
    }

    // ========================================================================
    // 5. DELETE (Hapus Data Transaksi - Stok Otomatis Kembali via Trigger)
    // ========================================================================
    public function deleteTransaction($id) {
        // Menyiapkan query DELETE dengan Prepared Statement
        // Catatan: Saat baris transaksi dihapus, TRIGGER 'trg_after_transaction_delete'
        // di MySQL akan otomatis mengembalikan jumlah barang ke stok tabel items!
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ?");
        
        // Mengeksekusi perintah hapus dengan parameter [$id]
        return $stmt->execute([$id]);
    }
}
?>