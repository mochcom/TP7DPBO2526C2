<?php
// ============================================================================
// FILE: view/header.php
// DESKRIPSI: Komponen modular bagian atas halaman (Header, Slogan Truk, & Navbar)
// ============================================================================

// Mengecek halaman apa yang sedang aktif agar kita bisa memberi class 'active' pada tombol menu
$currentPage = $_GET['page'] ?? 'items';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Mengatur encoding karakter menjadi UTF-8 -->
    <meta charset="UTF-8">
    <!-- Mengatur viewport agar tampilan responsif di berbagai ukuran layar -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Judul halaman yang tampil di tab browser -->
    <title>Sistem Truk Perabotan Keliling - "Berkah Ibu Komplek"</title>
    <!-- Menghubungkan halaman HTML dengan file styling style.css di root folder -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Container utama pembungkus seluruh elemen aplikasi -->
    <div class="app-container">

        <!-- Bagian Header dengan nuansa khas spanduk/bak truk keliling Indonesia -->
        <header class="truck-header">
            <div class="header-badge">🔊 "PERABOOOT... PERABOOOT... EMBER ANTI PECAH, KURSI NAPOLLY, KASUR LIPAT!"</div>
            <h1>🚛 Truk Perabotan Keliling "Putra Priangan"</h1>
            <p class="subtitle">Sistem Manajemen Stok Muatan Truk, Pelanggan Ibu-Ibu Komplek &amp; Catatan Adu Tawar Harga</p>

            <!-- Navigasi Utama untuk berpindah antar halaman entitas (items, customers, transactions) -->
            <nav class="main-nav">
                <!-- Menu 1: Mengarah ke index.php?page=items -->
                <a href="index.php?page=items" class="nav-link <?= ($currentPage === 'items') ? 'active' : '' ?>">
                    🪣 Katalog Perabotan (Items)
                </a>
                <!-- Menu 2: Mengarah ke index.php?page=customers -->
                <a href="index.php?page=customers" class="nav-link <?= ($currentPage === 'customers') ? 'active' : '' ?>">
                    👩‍🦱 Ibu-Ibu Langganan (Customers)
                </a>
                <!-- Menu 3: Mengarah ke index.php?page=transactions -->
                <a href="index.php?page=transactions" class="nav-link <?= ($currentPage === 'transactions') ? 'active' : '' ?>">
                    🤝 Transaksi &amp; Nawar Harga (Transactions)
                </a>
            </nav>
        </header>

        <!-- Pembuka tag main tempat konten masing-masing file view akan dimuat -->
        <main class="main-content">