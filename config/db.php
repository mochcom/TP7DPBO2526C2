<?php
// ============================================================================
// FILE: config/db.php
// DESKRIPSI: Class Database untuk menangani koneksi ke MySQL menggunakan PDO
// ============================================================================

class Database {
    // Deklarasi properti private (Encapsulation) agar kredensial database
    // hanya bisa diakses dari dalam class Database ini saja
    private $host = "localhost";           // Host server database (default Laragon/XAMPP: localhost)
    private $dbname = "db_furniture_truck"; // Nama database yang sudah kita buat di file .sql
    private $username = "root";            // Username default MySQL di Laragon/XAMPP
    private $password = "";                // Password default MySQL di Laragon/XAMPP (kosong)
    
    // Properti public untuk menyimpan objek koneksi PDO yang aktif
    public $conn;

    // Method Constructor: otomatis dipanggil saat class Database di-instansiasi (new Database())
    public function __construct() {
        // Mengosongkan variabel koneksi terlebih dahulu
        $this->conn = null;

        // Menggunakan blok try-catch untuk menangkap error jika koneksi ke MySQL gagal
        try {
            // Menyusun DSN (Data Source Name) untuk driver MySQL beserta charset utf8mb4
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8mb4";

            // Membuat objek PDO baru dengan menyertakan DSN, username, dan password
            $this->conn = new PDO($dsn, $this->username, $this->password);

            // Mengatur mode error PDO menjadi Exception agar jika ada kesalahan query SQL,
            // PHP akan menampilkan pesan error yang jelas dan mudah di-debug
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Mengatur hasil fetch default menjadi Associative Array (key berupa nama kolom tabel)
            // sehingga di view nanti kita bisa memanggil $row['item_name'] dengan rapi
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Mematikan emulasi prepared statement agar PDO menggunakan Native Prepared Statement
            // asli dari mesin MySQL (Lebih aman dari SQL Injection & sesuai spesifikasi modul)
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $exception) {
            // Jika koneksi gagal (misal MySQL di Laragon belum dinyalakan atau nama DB salah),
            // hentikan program dan tampilkan pesan error-nya
            die("Koneksi Database Gagal: " . $exception->getMessage());
        }
    }
}
?>