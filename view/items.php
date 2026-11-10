<?php
// ============================================================================
// FILE: view/items.php
// DESKRIPSI: Tampilan GUI & Controller Form untuk Entitas Perabotan (Items CRUD)
// ============================================================================

// Memanggil class Item untuk mengakses method CRUD
require_once __DIR__ . '/../class/Item.php';
$itemObj = new Item();

// Variabel penampung notifikasi pesan dan status (sukses / gagal)
$message = "";
$alertType = "";

// ============================================================================
// 1. PROSES AKSI FORM (CREATE, UPDATE, DELETE)
// ============================================================================

// A. PROSES DELETE (Hapus Data)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $idToDelete = $_GET['id'];
    $result = $itemObj->deleteItem($idToDelete);
    
    // Mengatur pesan notifikasi berdasarkan hasil penghapusan (mengantisipasi aturan RESTRICT)
    $message = $result['message'];
    $alertType = $result['status'] ? "alert-success" : "alert-danger";
}

// B. PROSES SUBMIT FORM (CREATE / UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil dan membersihkan input dari form
    $item_name     = trim($_POST['item_name']);
    $category      = trim($_POST['category']);
    $opening_price = intval($_POST['opening_price']);
    $stock         = intval($_POST['stock']);
    
    // Mengecek apakah form disubmit sebagai mode Update (berdasarkan hidden input 'id')
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $_POST['id'];
        $success = $itemObj->updateItem($id, $item_name, $category, $opening_price, $stock);
        
        if ($success) {
            $message = "Data perabotan berhasil diperbarui!";
            $alertType = "alert-success";
        } else {
            $message = "Gagal memperbarui data perabotan.";
            $alertType = "alert-danger";
        }
    } else {
        // Jika tidak ada ID, jalankan mode Create (Tambah Baru)
        $success = $itemObj->createItem($item_name, $category, $opening_price, $stock);
        
        if ($success) {
            $message = "Perabotan baru berhasil ditambahkan ke atas truk!";
            $alertType = "alert-success";
        } else {
            $message = "Gagal menambahkan perabotan.";
            $alertType = "alert-danger";
        }
    }
}

// ============================================================================
// 2. AMBIL DATA UNTUK FORM EDIT (JIKA USER KLIK TOMBOL EDIT)
// ============================================================================
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editData = $itemObj->getItemById($_GET['id']);
}

// Mengambil seluruh data perabotan terbaru dari database untuk ditampilkan di tabel
$itemsList = $itemObj->getAllItems();
?>

<!-- Judul Sub-Halaman -->
<div class="page-title-box" style="margin-bottom: 20px;">
    <h2>🪣 Manajemen Katalog Perabotan Truk</h2>
    <p style="color: #666; font-size: 0.95rem;">Kelola daftar barang muatan bak truk, atur harga buka awal, dan pantau sisa stok harian.</p>
</div>

<!-- Menampilkan Kotak Notifikasi (Alert) jika ada aksi yang dilakukan -->
<?php if (!empty($message)): ?>
    <div class="alert <?= $alertType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Layout Grid 2 Kolom: Kiri (Form Input/Edit), Kanan (Tabel Data) -->
<div class="content-grid">

    <!-- ==================================================================== -->
    <!-- KOLOM KIRI: FORM DINAMIS (CREATE / UPDATE) -->
    <!-- ==================================================================== -->
    <div class="card">
        <h2><?= $editData ? '✏️ Edit Perabotan' : '➕ Tambah Perabotan Baru' ?></h2>
        
        <!-- Form mengarah ke halaman ini sendiri menggunakan method POST -->
        <form action="index.php?page=items" method="POST">
            
            <!-- Jika mode Edit, sisipkan hidden input untuk menyimpan ID data yang diubah -->
            <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="item_name">Nama Perabotan:</label>
                <input type="text" id="item_name" name="item_name" 
                       value="<?= $editData ? htmlspecialchars($editData['item_name']) : '' ?>" 
                       placeholder="Cth: Baskom Jerman Jumbo" required>
            </div>

            <div class="form-group">
                <label for="category">Kategori Perabotan:</label>
                <select id="category" name="category" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php 
                    $categories = [
                        "Alat Mandi & Cuci", 
                        "Kursi & Lemari", 
                        "Alat Rumah Tangga", 
                        "Alat Kebersihan", 
                        "Perlengkapan Dapur", 
                        "Kasur & Bantal"
                    ];
                    foreach ($categories as $cat) {
                        $selected = ($editData && $editData['category'] === $cat) ? 'selected' : '';
                        echo "<option value=\"$cat\" $selected>$cat</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="opening_price">Harga Buka Awal (Rp):</label>
                <input type="number" id="opening_price" name="opening_price" min="0" 
                       value="<?= $editData ? $editData['opening_price'] : '' ?>" 
                       placeholder="Cth: 45000" required>
            </div>

            <div class="form-group">
                <label for="stock">Stok Muatan di Truk (Unit):</label>
                <input type="number" id="stock" name="stock" min="0" 
                       value="<?= $editData ? $editData['stock'] : '' ?>" 
                       placeholder="Cth: 20" required>
            </div>

            <!-- Tombol Submit Form -->
            <button type="submit" class="btn <?= $editData ? 'btn-warning' : 'btn-primary' ?>">
                <?= $editData ? '💾 Simpan Perubahan' : '➕ Tambah ke Truk' ?>
            </button>

            <!-- Tombol Batal Edit jika sedang dalam mode Edit -->
            <?php if ($editData): ?>
                <a href="index.php?page=items" class="btn btn-secondary">❌ Batal Edit</a>
            <?php endif; ?>

        </form>
    </div>

    <!-- ==================================================================== -->
    <!-- KOLOM KANAN: TABEL READ & AKSI CRUD -->
    <!-- ==================================================================== -->
    <div class="card">
        <h2>📋 Daftar Muatan Perabotan di Truk</h2>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Harga Buka</th>
                        <th>Stok Truk</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($itemsList) > 0): ?>
                        <?php $no = 1; foreach ($itemsList as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                                <td>
                                    <span class="badge badge-yellow">
                                        <?= htmlspecialchars($row['category']) ?>
                                    </span>
                                </td>
                                <td>Rp <?= number_format($row['opening_price'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="badge <?= $row['stock'] > 5 ? 'badge-green' : 'badge-red' ?>">
                                        <?= $row['stock'] ?> unit
                                    </span>
                                </td>
                                <td>
                                    <!-- Tombol Edit: Mengarah ke URL dengan parameter action=edit dan id -->
                                    <a href="index.php?page=items&action=edit&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                    
                                    <!-- Tombol Delete: Mengarah ke URL dengan konfirmasi JavaScript sederhana -->
                                    <a href="index.php?page=items&action=delete&id=<?= $row['id'] ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Yakin ingin menurunkan/menghapus barang ini dari truk?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: #777;">
                                Belum ada perabotan yang terdaftar di truk. Silakan tambah melalui form di sebelah kiri!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>