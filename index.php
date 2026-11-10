<?php
// ============================================================================
// FILE: index.php
// DESKRIPSI: Titik awal aplikasi (Entry Point & Page Router)
// ============================================================================

// Menangkap parameter 'page' dari URL (misal: index.php?page=customers)
// Menggunakan Null Coalescing Operator (??): jika parameter 'page' belum ada di URL,
// maka halaman default yang dibuka otomatis adalah 'items' (Daftar Perabotan)
$page =$_GET['page'] ?? 'items';

// Memanggil bagian atas halaman HTML (judul banner dan menu navigasi) dari folder view/
require_once __DIR__ . '/view/header.php';

// Struktur kontrol switch-case untuk menentukan file view mana yang harus dimuat
// sesuai dengan nilai variabel $page yang diminta oleh Client Browser
switch ($page) {
    case 'items':
        // Jika ?page=items, tampilkan halaman CRUD Katalog Perabotan Truk
        require_once __DIR__ . '/view/items.php';
        break;

    case 'customers':
        // Jika ?page=customers, tampilkan halaman CRUD Pelanggan Ibu-Ibu Komplek
        require_once __DIR__ . '/view/customers.php';
        break;

    case 'transactions':
        // Jika ?page=transactions, tampilkan halaman CRUD Transaksi & Adu Tawar Harga
        require_once __DIR__ . '/view/transactions.php';
        break;

    default:
        // Jika user mengetikkan nama page yang tidak terdaftar di URL,
        // arahkan kembali secara aman ke halaman default yaitu Katalog Perabotan
        require_once __DIR__ . '/view/items.php';
        break;
}

// Memanggil bagian penutup halaman HTML (footer) dari folder view/
require_once __DIR__ . '/view/footer.php';
?>