<?php
// 1. Panggil penjaga keamanan dan koneksi database
require_once 'includes/auth_check.php';

// Inisialisasi variabel
$user_id = $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';
$form_mode = 'tambah'; // Mode default untuk form
$edit_data = null; // Data untuk form edit

// 3. Logika untuk memproses form (Tambah, Edit, Hapus)
// Cek jika ada aksi POST yang dikirim dari form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    try {
        // Aksi: Menambah kategori baru
        if ($_POST['action'] == 'tambah') {
            $category_name = trim($_POST['category_name']);
            if (empty($category_name)) {
                throw new Exception("Nama kategori tidak boleh kosong.");
            }
            $stmt = $pdo->prepare("INSERT INTO categories (user_id, category_name) VALUES (?, ?)");
            $stmt->execute([$user_id, $category_name]);
            $pesan_sukses = "Kategori '".htmlspecialchars($category_name)."' berhasil ditambahkan.";
        }
        // Aksi: Mengedit kategori yang ada
        elseif ($_POST['action'] == 'edit') {
            $id = (int)$_POST['id'];
            $category_name = trim($_POST['category_name']);
            if (empty($category_name)) {
                throw new Exception("Nama kategori tidak boleh kosong.");
            }
            $stmt = $pdo->prepare("UPDATE categories SET category_name = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$category_name, $id, $user_id]);
            $pesan_sukses = "Kategori berhasil diperbarui. Mengalihkan...";
            // Redirect setelah 2 detik untuk user membaca pesan sukses
            header("Refresh:2; url=kategori.php");
        }
        // Aksi: Menghapus kategori
        elseif ($_POST['action'] == 'hapus') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $pesan_sukses = "Kategori berhasil dihapus.";
        }
    } catch (Exception $e) {
        $pesan_error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// 2. Logika untuk menyiapkan data form edit
// Cek jika URL memiliki parameter action=edit dan sebuah id
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $form_mode = 'edit';
    $id_to_edit = (int)$_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id_to_edit, $user_id]);
    $edit_data = $stmt->fetch();

    if (!$edit_data) {
        $pesan_error = "Data kategori tidak ditemukan.";
        $form_mode = 'tambah'; // Kembali ke mode tambah jika data tidak valid
    }
}

// 4. Ambil semua data kategori untuk ditampilkan di tabel
$stmt_kategori = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY category_name ASC");
$stmt_kategori->execute([$user_id]);
$categories = $stmt_kategori->fetchAll();

// 5. Menyiapkan variabel untuk layout
$page_title = 'Kelola Kategori';
$active_page = 'kategori';

// 6. "Rakit" halaman
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Kelola Kategori</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Kategori</li>
    </ol>

    <div class="alert alert-secondary">
        <p class="mb-0">Gunakan kategori untuk mengelompokkan akun Anda (misalnya: "Media Sosial", "Pekerjaan", "Keuangan", "Hiburan"). Ini akan membuat data Anda lebih terorganisir.</p>
    </div>

    <?php if (!empty($pesan_sukses)) { echo '<div class="alert alert-success">'.htmlspecialchars($pesan_sukses).'</div>'; } ?>
    <?php if (!empty($pesan_error)) { echo '<div class="alert alert-danger">'.htmlspecialchars($pesan_error).'</div>'; } ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus me-1"></i>
            <?php echo $form_mode == 'edit' ? 'Edit Kategori: ' . htmlspecialchars($edit_data['category_name']) : 'Tambah Kategori Baru'; ?>
        </div>
        <div class="card-body">
            <form method="POST" action="kategori.php">
                <input type="hidden" name="action" value="<?php echo $form_mode; ?>">
                <?php if ($form_mode == 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_data['id']); ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" class="form-control" name="category_name" placeholder="Contoh: Media Sosial" value="<?php echo htmlspecialchars($edit_data['category_name'] ?? ''); ?>" required>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <?php if ($form_mode == 'edit'): ?>
                        <a href="kategori.php" class="btn btn-secondary">Batal Edit</a>
                        <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-table me-1"></i>Daftar Kategori Anda</div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nama Kategori</th>
                        <th style="width: 15%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                        <td class="text-center">
                            <a href="kategori.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?php echo $cat['id']; ?>" title="Hapus"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($categories as $cat): ?>
<div class="modal fade" id="hapusModal<?php echo $cat['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="kategori.php">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                <div class="modal-body">
                    <p>Yakin ingin menghapus kategori <strong><?php echo htmlspecialchars($cat['category_name']); ?></strong>?</p>
                    <p class="text-danger small">Menghapus kategori juga akan menghapus semua akun yang ada di dalamnya.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php
// Script tambahan untuk datatables (jika diperlukan)
// Kita akan aktifkan nanti jika datanya sudah banyak
/*
$additional_scripts = '
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script>
    window.addEventListener(\'DOMContentLoaded\', event => {
        const datatablesSimple = document.getElementById(\'datatablesSimple\');
        if (datatablesSimple) {
            new simpleDatatables.DataTable(datatablesSimple);
        }
    });
</script>';
*/

include 'includes/footer.php';
?>