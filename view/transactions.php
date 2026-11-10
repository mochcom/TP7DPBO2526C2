<?php
// ============================================================================
// FILE: view/transactions.php
// DESKRIPSI: Tampilan GUI & Controller Form untuk Entitas Transaksi & Adu Tawar (Transactions CRUD)
// ============================================================================

// Memanggil class yang dibutuhkan (Transaction, Item, dan Customer)
require_once __DIR__ . '/../class/Transaction.php';
require_once __DIR__ . '/../class/Item.php';
require_once __DIR__ . '/../class/Customer.php';

$transactionObj = new Transaction();
$itemObj        = new Item();
$customerObj    = new Customer();

// Variabel penampung notifikasi pesan dan status (sukses / gagal)
$message = "";
$alertType = "";

// ============================================================================
// 1. PROSES AKSI FORM (CREATE, UPDATE, DELETE)
// ============================================================================

// A. PROSES DELETE (Hapus Transaksi - Stok Otomatis Kembali via Trigger Database)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $idToDelete = $_GET['id'];
    $success = $transactionObj->deleteTransaction($idToDelete);
    
    $message = $success ? "Transaksi dibatalkan & stok perabotan otomatis dikembalikan ke truk!" : "Gagal membatalkan transaksi.";
    $alertType = $success ? "alert-success" : "alert-danger";
}

// B. PROSES SUBMIT FORM (CREATE / UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil dan membersihkan input dari form
    $item_id          = intval($_POST['item_id']);
    $customer_id      = intval($_POST['customer_id']);
    $quantity         = intval($_POST['quantity']);
    $deal_price       = intval($_POST['deal_price']);
    $transaction_date = trim($_POST['transaction_date']);
    
    // Mengecek apakah form disubmit sebagai mode Update (berdasarkan hidden input 'id')
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $_POST['id'];
        $result = $transactionObj->updateTransaction($id, $item_id, $customer_id, $quantity, $deal_price, $transaction_date);
        
        $message = $result['message'];
        $alertType = $result['status'] ? "alert-success" : "alert-danger";
    } else {
        // Jika tidak ada ID, jalankan mode Create (Tambah Transaksi Baru)
        $result = $transactionObj->createTransaction($item_id, $customer_id, $quantity, $deal_price, $transaction_date);
        
        $message = $result['message'];
        $alertType = $result['status'] ? "alert-success" : "alert-danger";
    }
}

// ============================================================================
// 2. AMBIL DATA UNTUK FORM EDIT & DROPDOWN PILIHAN
// ============================================================================
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editData = $transactionObj->getTransactionById($_GET['id']);
}

// Mengambil data untuk dropdown pilihan relasi Foreign Key
$itemsList     = $itemObj->getAllItems();
$customersList = $customerObj->getAllCustomers();

// Mengambil seluruh riwayat transaksi terbaru beserta hasil JOIN tabel
$transactionsList = $transactionObj->getAllTransactions();
?>

<!-- Judul Sub-Halaman -->
<div class="page-title-box" style="margin-bottom: 20px;">
    <h2>🤝 Transaksi &amp; Catatan Adu Tawar Harga</h2>
    <p style="color: #666; font-size: 0.95rem;">Catat interaksi belanja ibu-ibu kompleks, hitung nominal kesepakatan tawar, dan pantau pemotongan stok otomatis.</p>
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
        <h2><?= $editData ? '✏️ Edit Transaksi' : '➕ Catat Belanja Baru' ?></h2>
        
        <!-- Form mengarah ke halaman ini sendiri menggunakan method POST -->
        <form action="index.php?page=transactions" method="POST">
            
            <!-- Jika mode Edit, sisipkan hidden input untuk menyimpan ID data yang diubah -->
            <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="item_id">Pilih Perabotan (Item):</label>
                <select id="item_id" name="item_id" required>
                    <option value="">-- Pilih Barang di Truk --</option>
                    <?php foreach ($itemsList as $it): ?>
                        <?php 
                        $selected = ($editData && $editData['item_id'] == $it['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $it['id'] ?>" <?= $selected ?>>
                            <?= htmlspecialchars($it['item_name']) ?> (Stok: <?= $it['stock'] ?> | Buka: Rp <?= number_format($it['opening_price'], 0, ',', '.') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="customer_id">Pilih Ibu Pelanggan:</label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">-- Pilih Ibu Komplek --</option>
                    <?php foreach ($customersList as $cs): ?>
                        <?php 
                        $selected = ($editData && $editData['customer_id'] == $cs['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $cs['id'] ?>" <?= $selected ?>>
                            <?= htmlspecialchars($cs['customer_name']) ?> (<?= htmlspecialchars($cs['complex_address']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Jumlah Beli (Unit):</label>
                <input type="number" id="quantity" name="quantity" min="1" 
                       value="<?= $editData ? $editData['quantity'] : '1' ?>" required>
            </div>

            <div class="form-group">
                <label for="deal_price">Harga Deal Setelah Ditawar (Rp):</label>
                <input type="number" id="deal_price" name="deal_price" min="0" 
                       value="<?= $editData ? $editData['deal_price'] : '' ?>" 
                       placeholder="Cth: 35000" required>
                <small style="color: #666; font-size: 0.78rem;">*Masukkan total harga akhir kesepakatan tawar-menawar dengan ibu.</small>
            </div>

            <div class="form-group">
                <label for="transaction_date">Tanggal Transaksi:</label>
                <input type="date" id="transaction_date" name="transaction_date" 
                       value="<?= $editData ? $editData['transaction_date'] : date('Y-m-d') ?>" required>
            </div>

            <!-- Tombol Submit Form -->
            <button type="submit" class="btn <?= $editData ? 'btn-warning' : 'btn-primary' ?>">
                <?= $editData ? '💾 Simpan Perubahan' : '🤝 Simpan Transaksi' ?>
            </button>

            <!-- Tombol Batal Edit jika sedang dalam mode Edit -->
            <?php if ($editData): ?>
                <a href="index.php?page=transactions" class="btn btn-secondary">❌ Batal Edit</a>
            <?php endif; ?>

        </form>
    </div>

    <!-- ==================================================================== -->
    <!-- KOLOM KANAN: TABEL READ & AKSI CRUD -->
    <!-- ==================================================================== -->
    <div class="card">
        <h2>📋 Riwayat Belanja &amp; Adu Tawar Ibu-Ibu</h2>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama Ibu</th>
                        <th>Barang &amp; Qty</th>
                        <th>Harga Normal</th>
                        <th>Harga Deal Tawar</th>
                        <th>Hemat Ibu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactionsList) > 0): ?>
                        <?php $no = 1; foreach ($transactionsList as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['transaction_date']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['customer_name']) ?></strong><br>
                                    <small style="color: #666;"><?= htmlspecialchars($row['complex_address']) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['item_name']) ?><br>
                                    <span class="badge badge-yellow"><?= $row['quantity'] ?> unit</span>
                                </td>
                                <td>Rp <?= number_format($row['normal_total'], 0, ',', '.') ?></td>
                                <td><strong>Rp <?= number_format($row['deal_price'], 0, ',', '.') ?></strong></td>
                                <td>
                                    <?php if ($row['saved_amount'] > 0): ?>
                                        <span class="badge badge-green">Hemat Rp <?= number_format($row['saved_amount'], 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #EEE; color: #555;">Pas / Tanpa Diskon</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Tombol Edit -->
                                    <a href="index.php?page=transactions&action=edit&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                    
                                    <!-- Tombol Delete (Memicu Trigger pengembalian stok) -->
                                    <a href="index.php?page=transactions&action=delete&id=<?= $row['id'] ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Batalkan transaksi ini? Stok barang akan otomatis dikembalikan ke truk.')">Batalkan</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px; color: #777;">
                                Belum ada riwayat transaksi belanja. Silakan catat transaksi baru di sebelah kiri!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>