<?php
// ============================================================================
// FILE: class/Customer.php
// DESKRIPSI: Class Model OOP untuk menangani operasi CRUD pada tabel 'customers'
// ============================================================================

// Memanggil file konfigurasi database (config/db.php) menggunakan path absolut __DIR__
require_once __DIR__ . '/../config/db.php';

class Customer {
    // Properti private untuk menyimpan objek koneksi PDO dari class Database
    private $db;

    // Method Constructor: otomatis dijalankan saat class Customer di-instansiasi
    public function __construct() {
        // Membuat objek baru dari class Database
        $database = new Database();
        // Menyimpan koneksi PDO aktif ke dalam properti $this->db
        $this->db = $database->conn;
    }

    // ========================================================================
    // 1. READ (Ambil Semua Data Ibu Pelanggan + Hitung Total Kali Belanja)
    // ========================================================================
    public function getAllCustomers() {
        // Menyiapkan query SELECT yang menggabungkan (LEFT JOIN) tabel customers (c)
        // dengan tabel transactions (t) untuk menghitung jumlah transaksi (total_shopping)
        // Menggunakan LEFT JOIN agar ibu yang baru terdaftar (belum pernah belanja / 0 transaksi) tetap tampil
        $sql = "SELECT c.*, COUNT(t.id) AS total_shopping 
                FROM customers c 
                LEFT JOIN transactions t ON c.id = t.customer_id 
                GROUP BY c.id 
                ORDER BY c.id DESC";
                
        // Menyiapkan statement dengan PDO prepare() (Wajib sesuai spesifikasi modul)
        $stmt = $this->db->prepare($sql);
        
        // Mengeksekusi prepared statement
        $stmt->execute();
        
        // Mengembalikan seluruh hasil query dalam bentuk associative array ke view
        return $stmt->fetchAll();
    }

    // ========================================================================
    // 2. READ BY ID (Ambil 1 Data Ibu Pelanggan Berdasarkan ID untuk Form Edit)
    // ========================================================================
    public function getCustomerById($id) {
        // Menyiapkan query SELECT dengan placeholder (?) untuk mencari pelanggan berdasarkan id
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        
        // Mengeksekusi statement dengan mengirimkan parameter [$id]
        $stmt->execute([$id]);
        
        // Mengambil 1 baris data pelanggan yang ditemukan
        return $stmt->fetch();
    }

    // ========================================================================
    // 3. CREATE (Tambah Data Pelanggan Ibu-Ibu Baru)
    // ========================================================================
    public function createCustomer($customer_name, $complex_address, $bargain_style) {
        // Menyiapkan query INSERT dengan 3 placeholder (?) sesuai kolom pada tabel customers
        $sql = "INSERT INTO customers (customer_name, complex_address, bargain_style) 
                VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        // Mengeksekusi query dengan mengirimkan ketiga data baru secara berurutan
        return $stmt->execute([$customer_name, $complex_address, $bargain_style]);
    }

    // ========================================================================
    // 4. UPDATE (Ubah Data Pelanggan Ibu-Ibu yang Sudah Ada)
    // ========================================================================
    public function updateCustomer($id, $customer_name, $complex_address, $bargain_style) {
        // Menyiapkan query UPDATE dengan placeholder (?) untuk memperbarui data pelanggan berdasarkan id
        $sql = "UPDATE customers 
                SET customer_name = ?, complex_address = ?, bargain_style = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        // Mengeksekusi prepared statement dengan urutan array yang cocok dengan tanda (?)
        return $stmt->execute([$customer_name, $complex_address, $bargain_style, $id]);
    }

    // ========================================================================
    // 5. DELETE (Hapus Data Pelanggan Ibu-Ibu - Otomatis ON DELETE CASCADE)
    // ========================================================================
    public function deleteCustomer($id) {
        // Karena di database relasi customer_id menggunakan ON DELETE CASCADE,
        // maka saat data ibu ini dihapus, riwayat belanjanya di tabel transactions otomatis ikut terhapus bersih
        $stmt = $this->db->prepare("DELETE FROM customers WHERE id = ?");
        
        // Mengeksekusi perintah hapus dengan mengirimkan parameter [$id]
        return $stmt->execute([$id]);
    }
}
?>