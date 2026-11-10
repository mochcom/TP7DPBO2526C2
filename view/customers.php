<?php
// ============================================================================
// FILE: view/customers.php
// DESKRIPSI: Tampilan GUI & Controller Form untuk Entitas Pelanggan Ibu-Ibu (Customers CRUD)
// ============================================================================

// Memanggil class Customer untuk mengakses method CRUD pelanggan
require_once __DIR__ . '/../class/Customer.php';
$customerObj = new Customer();

// Variabel penampung notifikasi pesan dan status (sukses / gagal)
$message = "";
$alertType = "";

// ============================================================================
// 1. PROSES AKSI FORM (CREATE, UPDATE, DELETE)
// ============================================================================

// A. PROSES DELETE (Hapus Data Pelanggan)
// Karena menggunakan ON DELETE CASCADE, riwayat transaksi ibu ini otomatis ikut terhapus di database
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $idToDelete = $_GET['id'];
    $success = $customerObj->deleteCustomer($idToDelete);
    
    $message = $success ? "Data ibu pelanggan berhasil dihapus (riwayat belanjanya ikut dibersihkan)!" : "Gagal menghapus data pelanggan.";
    $alertType = $success ? "alert-success" : "alert-danger";
}

// B. PROSES SUBMIT FORM (CREATE / UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil dan membersihkan input dari form
    $customer_name   = trim($_POST['customer_name']);
    $complex_address = trim($_POST['complex_address']);
    $bargain_style   = trim($_POST['bargain_style']);
    
    // Mengecek apakah form disubmit sebagai mode Update (berdasarkan hidden input 'id')
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $_POST['id'];
        $success = $customerObj->updateCustomer($id, $customer_name, $complex_address, $bargain_style);
        
        if ($success) {
            $message = "Data ibu pelanggan berhasil diperbarui!";
            $alertType = "alert-success";
        } else {
            $message = "Gagal memperbarui data ibu pelanggan.";
            $alertType = "alert-danger";
        }
    } else {
        // Jika tidak ada ID, jalankan mode Create (Tambah Pelanggan Baru)
        $success = $customerObj->createCustomer($customer_name, $complex_address, $bargain_style);
        
        if ($success) {
            $message = "Ibu pelanggan baru berhasil didaftarkan!";
            $alertType = "alert-success";
        } else {
            $message = "Gagal mendaftarkan pelanggan baru.";
            $alertType = "alert-danger";
        }
    }
}

// ============================================================================
// 2. AMBIL DATA UNTUK FORM EDIT (JIKA USER KLIK TOMBOL EDIT)
// ============================================================================
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editData = $customerObj->getCustomerById($_GET['id']);
}

// Mengambil seluruh data pelanggan terbaru dari database untuk ditampilkan di tabel
$customersList = $customerObj->getAllCustomers();
?>

<!-- Judul Sub-Halaman -->
<div class="page-title-box" style="margin-bottom: 20px;">
    <h2>👩‍🦱 Manajemen Pelanggan Ibu-Ibu Komplek</h2>
    <p style="color: #666; font-size: 0.95rem;">Catat data ibu-ibu pelanggan setia truk perabotan beserta karakteristik gaya tawar-menawarnya.</p>
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
        <h2><?= $editData ? '✏️ Edit Data Ibu' : '➕ Tambah Ibu Pelanggan' ?></h2>
        
        <!-- Form mengarah ke halaman ini sendiri menggunakan method POST -->
        <form action="index.php?page=customers" method="POST">
            
            <!-- Jika mode Edit, sisipkan hidden input untuk menyimpan ID data yang diubah -->
            <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="customer_name">Nama Panggilan Ibu:</label>
                <input type="text" id="customer_name" name="customer_name" 
                       value="<?= $editData ? htmlspecialchars($editData['customer_name']) : '' ?>" 
                       placeholder="Cth: Bu Haji Lilis / Mama Daffa" required>
            </div>

            <div class="form-group">
                <label for="complex_address">Alamat Komplek / RT:</label>
                <input type="text" id="complex_address" name="complex_address" 
                       value="<?= $editData ? htmlspecialchars($editData['complex_address']) : '' ?>" 
                       placeholder="Cth: Griya Asri Blok A No. 12" required>
            </div>

            <div class="form-group">
                <label for="bargain_style">Gaya Menawar Harga:</label>
                <select id="bargain_style" name="bargain_style" required>
                    <option value="">-- Pilih Karakter Nawar --</option>
                    <?php 
                    $styles = [
                        "Nawar Sadis Setengah Harga", 
                        "Suka Minta Bonus Gayung/Sikat", 
                        "Beli Borongan Buat Hadiah Lomba", 
                        "Langsung Deal Kalau Barang Awet",
                        "Nawar Alus Sambil Ngobrol Gosip"
                    ];
                    foreach ($styles as $st) {
                        $selected = ($editData && $editData['bargain_style'] === $st) ? 'selected' : '';
                        echo "<option value=\"$st\" $selected>$st</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Tombol Submit Form -->
            <button type="submit" class="btn <?= $editData ? 'btn-warning' : 'btn-primary' ?>">
                <?= $editData ? '💾 Simpan Perubahan' : '➕ Daftarkan Ibu' ?>
            </button>

            <!-- Tombol Batal Edit jika sedang dalam mode Edit -->
            <?php if ($editData): ?>
                <a href="index.php?page=customers" class="btn btn-secondary">❌ Batal Edit</a>
            <?php endif; ?>

        </form>
    </div>

    <!-- ==================================================================== -->
    <!-- KOLOM KANAN: TABEL READ & AKSI CRUD -->
    <!-- ==================================================================== -->
    <div class="card">
        <h2>📋 Daftar Ibu-Ibu Langganan Truk</h2>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Ibu</th>
                        <th>Alamat Rumah</th>
                        <th>Karakter Tawar</th>
                        <th>Total Belanja</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($customersList) > 0): ?>
                        <?php $no = 1; foreach ($customersList as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($row['customer_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['complex_address']) ?></td>
                                <td>
                                    <span class="badge badge-yellow">
                                        <?= htmlspecialchars($row['bargain_style']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-green">
                                        <?= $row['total_shopping'] ?> kali transaksi
                                    </span>
                                </td>
                                <td>
                                    <!-- Tombol Edit: Mengarah ke URL dengan parameter action=edit dan id -->
                                    <a href="index.php?page=customers&action=edit&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                    
                                    <!-- Tombol Delete: Mengarah ke URL dengan konfirmasi -->
                                    <a href="index.php?page=customers&action=delete&id=<?= $row['id'] ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Yakin ingin menghapus data ibu ini? Riwayat transaksinya juga akan ikut terhapus.')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: #777;">
                                Belum ada data ibu-ibu pelanggan yang terdaftar. Silakan tambah melalui form di sebelah kiri!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>