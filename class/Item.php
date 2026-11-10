<?php
// ============================================================================
// FILE: class/Item.php
// DESKRIPSI: Class Model OOP untuk menangani operasi CRUD pada tabel 'items'
// ============================================================================

// Memanggil file konfigurasi database (menggunakan require_once agar tidak ter-load ganda)
// Menggunakan __DIR__ agar path file selalu akurat meskipun dipanggil dari index.php atau view
require_once __DIR__ . '/../config/db.php';

class Item {
    // Properti private untuk menyimpan objek koneksi PDO dari class Database
    private $db;

    // Method Constructor: otomatis dijalankan saat kita membuat objek baru: $item = new Item();
    public function __construct() {
        // Membuat instansiasi objek dari class Database (yang ada di config/db.php)
        $database = new Database();
        // Menyimpan koneksi PDO aktif ($database->conn) ke dalam properti $this->db
        $this->db = $database->conn;
    }

    // ========================================================================
    // 1. READ (Ambil Semua Data Perabotan)
    // ========================================================================
    public function getAllItems() {
        // Menyiapkan query SQL SELECT dengan Prepared Statement (meskipun tanpa parameter)
        // untuk mematuhi aturan modul: dilarang menggunakan $db->query() mentah langsung
        $stmt = $this->db->prepare("SELECT * FROM items ORDER BY id DESC");
        
        // Mengeksekusi prepared statement yang sudah disiapkan
        $stmt->execute();
        
        // Mengembalikan seluruh baris data sebagai array asosiatif ke file view
        return $stmt->fetchAll();
    }

    // ========================================================================
    // 2. READ BY ID (Ambil 1 Data Perabotan Berdasarkan ID untuk Form Edit/Update)
    // ========================================================================
    public function getItemById($id) {
        // Menyiapkan query SQL dengan tanda tanya (?) sebagai placeholder parameter ID
        $stmt = $this->db->prepare("SELECT * FROM items WHERE id = ?");
        
        // Mengeksekusi statement dengan mengirimkan nilai $id ke dalam placeholder (?)
        $stmt->execute([$id]);
        
        // Mengambil hanya 1 baris data perabotan yang cocok dengan ID tersebut
        return $stmt->fetch();
    }

    // ========================================================================
    // 3. CREATE (Tambah Data Perabotan Baru ke Atas Truk)
    // ========================================================================
    public function createItem($item_name, $category, $opening_price, $stock) {
        // Menyiapkan query INSERT dengan 4 placeholder (?) sesuai jumlah kolom input
        $sql = "INSERT INTO items (item_name, category, opening_price, stock) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        // Mengeksekusi query dengan mengikat (binding) ke-4 variabel ke dalam array secara berurutan
        // Mengembalikan nilai boolean (true jika berhasil disimpan, false jika gagal)
        return $stmt->execute([$item_name, $category, $opening_price, $stock]);
    }

    // ========================================================================
    // 4. UPDATE (Ubah Data Perabotan yang Sudah Ada)
    // ========================================================================
    public function updateItem($id, $item_name, $category, $opening_price, $stock) {
        // Menyiapkan query UPDATE dengan placeholder (?) untuk setiap kolom dan klausa WHERE id
        $sql = "UPDATE items 
                SET item_name = ?, category = ?, opening_price = ?, stock = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        // Mengeksekusi prepared statement dengan urutan array yang sama persis dengan tanda (?)
        return $stmt->execute([$item_name, $category, $opening_price, $stock, $id]);
    }

    // ========================================================================
    // 5. DELETE (Hapus Data Perabotan dengan Pengaman ON DELETE RESTRICT)
    // ========================================================================
    public function deleteItem($id) {
        // Menggunakan blok try-catch untuk mengantisipasi aturan ON DELETE RESTRICT
        // apabila barang yang ingin dihapus ternyata sudah tercatat di tabel transactions
        try {
            // Menyiapkan query DELETE menggunakan Prepared Statement
            $stmt = $this->db->prepare("DELETE FROM items WHERE id = ?");
            
            // Mengeksekusi penghapusan data berdasarkan $id
            $stmt->execute([$id]);
            
            // Jika berhasil dan tidak terhalang Foreign Key, kembalikan status sukses
            return [
                'status' => true,
                'message' => 'Data perabotan berhasil diturunkan/dihapus dari truk!'
            ];
        } catch (PDOException $e) {
            // Memeriksa apakah kode error SQLState adalah '23000' (Integrity Constraint Violation)
            if ($e->getCode() == '23000') {
                // Mengembalikan status gagal beserta pesan informasi yang jelas untuk ditampilkan di GUI
                return [
                    'status' => false,
                    'message' => 'Gagal dihapus! Perabotan ini sudah pernah dibeli ibu-ibu di Riwayat Transaksi (terlindungi oleh aturan ON DELETE RESTRICT). Hapus dulu transaksinya jika ingin menghapus barang ini.'
                ];
            }
            // Jika error disebabkan oleh hal lain, kembalikan pesan error teknisnya
            return [
                'status' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ];
        }
    }
}
?>