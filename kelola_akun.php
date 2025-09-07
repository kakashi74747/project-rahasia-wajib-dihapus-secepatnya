<?php

//======================================================================
// KELOLA AKUN - Halaman Utama
//======================================================================
// Bertanggung jawab untuk menampilkan, menambah, mengedit, dan
// menghapus data akun pengguna.
//======================================================================


//--- SECTION: INISIALISASI & KEAMANAN ---
require_once 'includes/auth_check.php';

// Inisialisasi variabel utama
$user_id            = $_SESSION['user_id'];
$pesan_sukses       = '';
$pesan_error        = '';
$filter_category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;


//--- SECTION: PENANGANAN PESAN SESSION ---
// Ambil pesan dari session setelah redirect, lalu hapus agar tidak muncul lagi.
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_sukses = $_SESSION['pesan_sukses'];
    unset($_SESSION['pesan_sukses']);
}
if (isset($_SESSION['pesan_error'])) {
    $pesan_error = $_SESSION['pesan_error'];
    unset($_SESSION['pesan_error']);
}


//--- SECTION: KONFIGURASI ENKRIPSI ---
// Kunci enkripsi diambil dari hash password login pengguna untuk keamanan.
define('ENCRYPTION_METHOD', 'aes-256-cbc');
$stmt_key = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt_key->execute([$user_id]);
$user_login_hash = $stmt_key->fetchColumn();
define('SECRET_KEY', hash('sha256', $user_login_hash));
define('SECRET_IV', substr(hash('sha256', 'iv-for-' . $user_login_hash), 0, 16));


//--- SECTION: FUNGSI-FUNGSI BANTU ---

/**
 * Mengenkripsi string (password).
 * @param string $string Teks yang akan dienkripsi.
 * @return string Teks terenkripsi dalam format base64.
 */
function encrypt_password($string)
{
    if (empty($string)) return '';
    return base64_encode(openssl_encrypt($string, ENCRYPTION_METHOD, SECRET_KEY, 0, SECRET_IV));
}

/**
 * Mengunggah gambar profil dan mengembalikannya nama file baru.
 * @param array $file Variabel $_FILES['nama_input'].
 * @return array Hasil upload ['success' => bool, 'filename' => string|null, 'message' => string|null].
 */
function uploadProfilePicture($file)
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => true, 'filename' => null];
    }

    $target_dir = UPLOADS_PATH . "/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename   = uniqid('profile_', true) . '.' . $file_extension;
    $target_file    = $target_dir . $new_filename;
    $allowed_types  = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];

    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Hanya format gambar (JPG, PNG, GIF, SVG, WEBP) yang diizinkan.'];
    }
    if ($file["size"] > 2000000) { // 2MB
        return ['success' => false, 'message' => 'Ukuran file maksimal adalah 2MB.'];
    }
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    }
    return ['success' => false, 'message' => 'Gagal mengunggah file. Silakan coba lagi.'];
}

/**
 * Memformat angka menjadi format singkat (e.g., 1.5K, 2 JT).
 * @param int $n Angka yang akan diformat.
 * @return string Angka dalam format singkat.
 */
function format_likes_short($n)
{
    if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1, ',', ''), '0'), ',') . ' JT';
    if ($n >= 1000)    return rtrim(rtrim(number_format($n / 1000, 1, ',', ''), '0'), ',') . 'K';
    return number_format($n, 0, ',', '.');
}

/**
 * Menghitung dan memformat umur akun dari tanggal pembuatan.
 * @param string $creation_date Tanggal dalam format Y-m-d.
 * @param int $total_days Total hari umur akun.
 * @return string String umur yang diformat.
 */
function format_age_string($creation_date, $total_days)
{
    if ($total_days < 0) return "Tanggal belum tercapai";

    try {
        $start    = new DateTime($creation_date);
        $end      = new DateTime('now');
        $interval = $end->diff($start);
        $parts    = [];

        if ($interval->y > 0) $parts[] = $interval->y . ' TAHUN';
        if ($interval->m > 0) $parts[] = $interval->m . ' BULAN';
        if ($interval->d > 0) $parts[] = $interval->d . ' HARI';

        if (empty($parts)) return "Hari ini";

        return implode(', ', $parts) . ' | Total ' . number_format($total_days, 0, ',', '.') . ' hari';
    } catch (Exception $e) {
        return "Invalid date";
    }
}


//--- SECTION: LOGIKA PROSES FORM (POST REQUEST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    try {
        $action = $_POST['action'];

        // Aksi: Menyimpan Akun (Tambah atau Edit)
        if ($action == 'save_account') {
            $account_id    = isset($_POST['account_id']) && !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;
            $account_name  = trim($_POST['account_name']);
            $category_id   = (int)$_POST['category_id'];
            $username      = trim($_POST['username']);
            $email         = trim($_POST['email']);
            $password      = $_POST['password'];
            $notes         = trim($_POST['notes']);
            $creation_date = !empty($_POST['creation_date']) ? $_POST['creation_date'] : date('Y-m-d');
            $followers     = (int)str_replace('.', '', $_POST['followers'] ?? 0);
            $likes         = (int)str_replace('.', '', $_POST['likes'] ?? 0);

            if (empty($account_name) || empty($category_id)) {
                throw new Exception("Nama Akun dan Kategori wajib diisi.");
            }
            if (!$account_id && empty($password)) {
                throw new Exception("Password wajib diisi untuk akun baru.");
            }

            $upload_result = uploadProfilePicture($_FILES['profile_picture']);
            if (!$upload_result['success']) {
                throw new Exception($upload_result['message']);
            }
            $new_filename = $upload_result['filename'];

            if ($account_id) { // Mode Edit
                $stmt_get_old = $pdo->prepare("SELECT profile_picture, password, email FROM accounts WHERE id = ? AND user_id = ?");
                $stmt_get_old->execute([$account_id, $user_id]);
                $old_data = $stmt_get_old->fetch();

                $filename_to_save = $new_filename ?? $old_data['profile_picture'];
                $password_to_save = !empty($password) ? encrypt_password($password) : $old_data['password'];
                $email_to_save    = !empty($email) ? $email : $old_data['email'];

                $stmt = $pdo->prepare("UPDATE accounts SET category_id=?, account_name=?, username=?, email=?, password=?, notes=?, profile_picture=?, creation_date=?, followers=?, likes=? WHERE id=? AND user_id=?");
                $stmt->execute([$category_id, $account_name, $username, $email_to_save, $password_to_save, $notes, $filename_to_save, $creation_date, $followers, $likes, $account_id, $user_id]);

                // Hapus gambar lama jika ada gambar baru yang diupload
                if ($new_filename && !empty($old_data['profile_picture']) && file_exists(UPLOADS_PATH . '/' . $old_data['profile_picture'])) {
                    unlink(UPLOADS_PATH . '/' . $old_data['profile_picture']);
                }
                $_SESSION['pesan_sukses'] = "Detail akun berhasil diperbarui.";
            } else { // Mode Tambah
                $encrypted_password = encrypt_password($password);
                $stmt = $pdo->prepare("INSERT INTO accounts (user_id, category_id, account_name, username, email, password, notes, profile_picture, creation_date, followers, likes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $category_id, $account_name, $username, $email, $encrypted_password, $notes, $new_filename, $creation_date, $followers, $likes]);
                $_SESSION['pesan_sukses'] = "Akun baru berhasil ditambahkan.";
            }
        }
        // Aksi: Update Statistik
        elseif ($action == 'update_stats') {
            $account_id  = (int)$_POST['account_id'];
            $followers   = (int)str_replace('.', '', $_POST['followers']);
            $likes       = (int)str_replace('.', '', $_POST['likes']);
            $record_date = !empty($_POST['record_date']) ? $_POST['record_date'] . ' ' . date('H:i:s') : date('Y-m-d H:i:s');
            
            // Simpan statistik lama ke history sebelum diupdate
            $stmt_history = $pdo->prepare("INSERT INTO stats_history (account_id, followers, likes, record_date) SELECT id, followers, likes, ? FROM accounts WHERE id = ?");
            $stmt_history->execute([$record_date, $account_id]);
            
            // Update statistik saat ini
            $stmt = $pdo->prepare("UPDATE accounts SET followers = ?, likes = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$followers, $likes, $account_id, $user_id]);
            $_SESSION['pesan_sukses'] = "Statistik akun berhasil diperbarui.";
        }
        // Aksi: Hapus Akun
        elseif ($action == 'hapus') {
            $id = (int)$_POST['id'];
            
            // Ambil nama file gambar untuk dihapus
            $stmt_get_pic = $pdo->prepare("SELECT profile_picture FROM accounts WHERE id = ? AND user_id = ?");
            $stmt_get_pic->execute([$id, $user_id]);
            $pic_to_delete = $stmt_get_pic->fetchColumn();

            // Hapus data dari database
            $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);

            // Hapus file gambar jika ada
            if ($pic_to_delete && file_exists(UPLOADS_PATH . '/' . $pic_to_delete)) {
                unlink(UPLOADS_PATH . '/' . $pic_to_delete);
            }
            $_SESSION['pesan_sukses'] = "Akun berhasil dihapus.";
        }
    } catch (Exception $e) {
        $_SESSION['pesan_error'] = "Terjadi kesalahan: " . $e->getMessage();
    }

    // Redirect kembali ke halaman ini (dengan filter jika ada) untuk mencegah resubmit form
    $redirect_url = 'kelola_akun.php' . ($filter_category_id ? '?category=' . $filter_category_id : '');
    header('Location: ' . $redirect_url);
    exit();
}


//--- SECTION: PENGAMBILAN DATA DARI DATABASE ---

// 1. Ambil semua kategori milik user
$categories_list = $pdo->query("SELECT id, category_name FROM categories WHERE user_id = {$user_id} ORDER BY display_order, category_name ASC")->fetchAll();

// 2. Siapkan dan ambil semua akun berdasarkan filter kategori
$sql_filter = $filter_category_id ? " AND a.category_id = ?" : "";
$params     = $filter_category_id ? [$user_id, $filter_category_id] : [$user_id];
$accounts_stmt = $pdo->prepare("
    SELECT 
        a.*, 
        c.category_name, 
        CAST(julianday('now') - julianday(a.creation_date) AS INTEGER) as account_age_days 
    FROM accounts a 
    JOIN categories c ON a.category_id = c.id 
    WHERE a.user_id = ? {$sql_filter} 
    ORDER BY c.display_order, c.category_name, a.display_order, a.account_name ASC
");
$accounts_stmt->execute($params);
$accounts_list_raw = $accounts_stmt->fetchAll();

// 3. Ambil riwayat statistik untuk semua akun
$stats_history_stmt = $pdo->prepare("SELECT * FROM stats_history WHERE account_id IN (SELECT id FROM accounts WHERE user_id = ?) ORDER BY record_date DESC");
$stats_history_stmt->execute([$user_id]);
$stats_history_raw = $stats_history_stmt->fetchAll();

// 4. Proses data untuk ditampilkan: Kelompokkan akun dan riwayat statistik
$stats_history_grouped = [];
foreach ($stats_history_raw as $history) {
    $stats_history_grouped[$history['account_id']][] = $history;
}

$accounts_grouped = [];
foreach ($accounts_list_raw as $account) {
    $accounts_grouped[$account['category_name']][] = $account;
}

//--- SECTION: PENGATURAN HALAMAN & INCLUDE HEADER ---
$page_title  = 'Kelola Akun';
$active_page = 'kelola_akun';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
    .sortable-ghost {
        opacity: 0.4;
        background: #f0f0f0;
    }
    .drag-handle {
        cursor: move;
        color: #ced4da;
        margin-right: 15px;
    }
    .list-group-item:hover .drag-handle {
        color: #495057;
    }
    .history-diff {
        font-size: 0.8em;
        font-weight: bold;
    }
    .diff-increase {
        color: #198754; /* success */
    }
    .diff-decrease {
        color: #dc3545; /* danger */
    }
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mt-4">Kelola Akun</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Kelola Akun</li>
            </ol>
        </div>
        <div class="my-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#accountModal" onclick="prepareAddModal()">
                <i class="fas fa-plus me-1"></i> Tambah Akun
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i>Filter Kategori</div>
        <div class="card-body">
            <ul class="nav nav-pills category-filter-nav flex-wrap">
                <li class="nav-item">
                    <a class="nav-link <?= !$filter_category_id ? 'active' : ''; ?>" href="kelola_akun.php">Semua</a>
                </li>
                <?php foreach ($categories_list as $cat) : ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($filter_category_id == $cat['id']) ? 'active' : ''; ?>" href="kelola_akun.php?category=<?= $cat['id']; ?>">
                            <?= htmlspecialchars($cat['category_name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?php if (!empty($pesan_sukses)) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($pesan_sukses); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($pesan_error)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($pesan_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($accounts_grouped)) : ?>
        <div class="alert alert-info text-center m-3">
            <h4>Tidak ada akun ditemukan.</h4>
            <p>Coba pilih kategori lain atau tambah akun baru.</p>
        </div>
    <?php else : ?>
        <?php foreach ($accounts_grouped as $category_name => $accounts) : ?>
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-tags me-2"></i><?= htmlspecialchars($category_name); ?></div>
                <div class="list-group list-group-flush sortable-accounts">
                    <?php foreach ($accounts as $account) : ?>
                        <div class="list-group-item account-card" data-id="<?= $account['id']; ?>">
                            <div class="d-flex w-100 align-items-center flex-wrap">
                                <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                                
                                <div class="d-flex align-items-center account-info mb-2 mb-md-0">
                                    <?php
                                    $image_path = BASE_URL . 'uploads/' . ($account['profile_picture'] ?? 'default.png');
                                    if (empty($account['profile_picture']) || !file_exists(UPLOADS_PATH . '/' . $account['profile_picture'])) {
                                        $initial = strtoupper(substr($account['account_name'], 0, 1));
                                        $image_path = "https://via.placeholder.com/50/6c757d/FFFFFF?text=" . urlencode($initial);
                                    }
                                    ?>
                                    <img src="<?= $image_path; ?>" alt="Logo" class="rounded-circle me-3" width="50" height="50" style="object-fit: cover;">
                                    <div>
                                        <h5 class="mb-0 account-name"><?= htmlspecialchars($account['account_name']); ?></h5>
                                        <div class="account-subtext d-block"><?= htmlspecialchars($account['username'] ?: $account['email']); ?></div>
                                        <small class="text-muted" title="Tanggal Dibuat: <?= date('d M Y', strtotime($account['creation_date'])); ?>">
                                            <?= format_age_string($account['creation_date'], $account['account_age_days']); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="account-stats ms-md-auto me-md-3 my-2 my-md-0 text-start text-md-end">
                                    <span class="me-3" title="<?= number_format($account['followers'], 0, ',', '.'); ?> followers">
                                        <i class="fas fa-users text-primary me-1"></i> <?= number_format($account['followers'], 0, ',', '.'); ?>
                                    </span>
                                    <span title="<?= number_format($account['likes'], 0, ',', '.'); ?> likes">
                                        <i class="fas fa-heart text-danger me-1"></i> <?= format_likes_short($account['likes']); ?>
                                    </span>
                                </div>

                                <div>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="showStatsHistory(<?= $account['id']; ?>)" title="Lihat History Statistik"><i class="fas fa-history"></i></button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="prepareStatsModal(<?= $account['id']; ?>)" title="Update Statistik"><i class="fas fa-chart-line"></i></button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="showPassword(<?= $account['id']; ?>)" title="Tampilkan Password"><i class="fas fa-eye"></i></button>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#" onclick="prepareEditModal(<?= $account['id']; ?>)">Edit Detail Akun</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><button class="dropdown-item text-danger" type="button" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $account['id']; ?>">Hapus Akun</button></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($account['notes'])) : ?>
                                <div class="notes-content pt-2" style="margin-left: 65px; font-size: 0.9em; color: #6c757d;">
                                    <?= nl2br(htmlspecialchars($account['notes'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="kelola_akun.php<?= $filter_category_id ? '?category=' . $filter_category_id : ''; ?>" enctype="multipart/form-data" id="accountForm">
                <input type="hidden" name="action" value="save_account">
                <input type="hidden" name="account_id" id="account_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Akun/Layanan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account_name" name="account_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories_list as $cat) { echo "<option value='{$cat['id']}'>" . htmlspecialchars($cat['category_name']) . "</option>"; } ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span id="password-required-star" class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Dibuat</label>
                            <input type="date" class="form-control" id="creation_date" name="creation_date">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Followers Awal</label>
                            <input type="text" class="form-control number-format" id="followers" name="followers" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Likes Awal</label>
                            <input type="text" class="form-control number-format" id="likes" name="likes" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo/Gambar Profil</label>
                        <input class="form-control" type="file" id="profile_picture" name="profile_picture" accept="image/*">
                        <small class="form-text text-muted" id="current_image_text"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="saveButton"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="statsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statsModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="kelola_akun.php<?= $filter_category_id ? '?category=' . $filter_category_id : ''; ?>">
                <input type="hidden" name="action" value="update_stats">
                <input type="hidden" name="account_id" id="stats_account_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Followers</label>
                        <input type="text" class="form-control number-format" id="stats_followers" name="followers" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Likes</label>
                        <input type="text" class="form-control number-format" id="stats_likes" name="likes" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Pencatatan</label>
                        <input type="date" class="form-control" id="stats_record_date" name="record_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-secondary">Simpan Statistik</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="statsHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statsHistoryModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="statsHistoryBody">
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php foreach ($accounts_list_raw as $account) : ?>
    <div class="modal fade" id="hapusModal<?= $account['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="kelola_akun.php<?= $filter_category_id ? '?category=' . $filter_category_id : ''; ?>">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id" value="<?= $account['id']; ?>">
                    <div class="modal-body">
                        <p>Yakin ingin menghapus akun <strong><?= htmlspecialchars($account['account_name']); ?></strong>?</p>
                        <p class="text-danger small">Tindakan ini tidak bisa dibatalkan.</p>
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

<div class="modal fade" id="pinModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verifikasi PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Masukkan PIN 4 digit Anda.</p>
                <div id="pinAlert" class="alert alert-danger d-none"></div>
                <input type="password" id="pinInput" class="form-control text-center" maxlength="4" inputmode="numeric" pattern="\d*">
                <input type="hidden" id="pinAccountId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" onclick="verifyPin()">Verifikasi</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="passwordDisplayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordDisplayModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Password</label>
                <div class="input-group mb-3">
                    <input type="text" id="revealedPassword" class="form-control" readonly>
                    <button class="btn btn-outline-secondary" onclick="copyToClipboard('revealedPassword', 'copyFeedback')"><i class="fas fa-copy"></i></button>
                </div>
                <small id="copyFeedback" class="text-success d-block"></small>

                <div id="emailDisplayGroup" class="d-none mt-3">
                    <label class="form-label small">Email</label>
                    <div class="input-group">
                        <input type="text" id="revealedEmail" class="form-control" readonly>
                        <button class="btn btn-outline-secondary" onclick="copyToClipboard('revealedEmail', 'copyFeedbackEmail')"><i class="fas fa-copy"></i></button>
                    </div>
                    <small id="copyFeedbackEmail" class="text-success d-block"></small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
//--- SECTION: PERSIAPAN DATA UNTUK JAVASCRIPT ---
// Mengirim data PHP ke JavaScript dengan aman menggunakan json_encode.
$php_data_for_js = json_encode(array_map(fn ($acc) => [
    'id' => $acc['id'], 'account_name' => $acc['account_name'], 'username' => $acc['username'],
    'email' => $acc['email'], 'notes' => $acc['notes'], 'profile_picture' => $acc['profile_picture'],
    'followers' => $acc['followers'], 'likes' => $acc['likes'], 'category_id' => $acc['category_id'],
    'creation_date' => $acc['creation_date']
], $accounts_list_raw));

$stats_history_js = json_encode($stats_history_grouped);


//--- SECTION: SKRIP JAVASCRIPT ---
// Skrip tambahan untuk fungsionalitas halaman (sorting, modal, AJAX, dll).
$additional_scripts = <<<JS
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    // Data dari PHP
    const allAccountsData = {$php_data_for_js};
    const statsHistoryData = {$stats_history_js};

    //--- Fungsi Bantuan ---
    function formatNumberWithDots(number) { return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
    function removeDots(string) { return String(string).replace(/\./g, ''); }
    function copyToClipboard(elementId, feedbackId) {
        navigator.clipboard.writeText(document.getElementById(elementId).value).then(() => {
            const feedbackEl = document.getElementById(feedbackId);
            feedbackEl.innerText = 'Tersalin!';
            setTimeout(() => { feedbackEl.innerText = ''; }, 2000);
        });
    }

    //--- Event Listener Utama ---
    document.addEventListener('DOMContentLoaded', function () {
        // Otomatis format angka dengan titik pada input
        document.querySelectorAll('.number-format').forEach(input => {
            input.addEventListener('keyup', function() {
                let value = removeDots(this.value);
                this.value = !isNaN(value) && value.length > 0 ? formatNumberWithDots(value) : '';
            });
        });

        // Inisialisasi SortableJS untuk drag-and-drop
        document.querySelectorAll('.sortable-accounts').forEach(function(list) {
            new Sortable(list, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                    let orderedIds = Array.from(list.querySelectorAll('.list-group-item')).map(item => item.dataset.id);
                    const formData = new FormData();
                    formData.append('action', 'update_order');
                    orderedIds.forEach(id => formData.append('order[]', id));

                    fetch('ajax_handler.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => { if (!data.success) console.error('Gagal menyimpan urutan.'); })
                        .catch(error => console.error('Error:', error));
                }
            });
        });
    });

    //--- Fungsi-fungsi Modal ---
    function prepareAddModal() {
        const form = document.getElementById('accountForm');
        form.reset();
        document.getElementById('accountModalLabel').innerText = 'Tambah Akun Baru';
        document.getElementById('saveButton').innerText = 'Simpan Akun';
        document.getElementById('account_id').value = '';
        document.getElementById('current_image_text').innerText = '';
        document.getElementById('password').placeholder = "Wajib diisi untuk akun baru";
        document.getElementById('password').required = true;
        document.getElementById('password-required-star').style.display = '';
        document.getElementById('email').placeholder = "Email (opsional)";
        document.getElementById('creation_date').valueAsDate = new Date();
        document.getElementById('followers').value = '0';
        document.getElementById('likes').value = '0';
    }

    function prepareEditModal(accountId) {
        const account = allAccountsData.find(acc => acc.id === accountId);
        if (!account) return;

        const form = document.getElementById('accountForm');
        form.reset();
        document.getElementById('accountModalLabel').innerText = 'Edit Detail Akun: ' + account.account_name;
        document.getElementById('saveButton').innerText = 'Simpan Perubahan';
        document.getElementById('account_id').value = account.id;
        document.getElementById('account_name').value = account.account_name;
        document.getElementById('category_id').value = account.category_id;
        document.getElementById('username').value = account.username;
        document.getElementById('email').value = account.email;
        document.getElementById('notes').value = account.notes;
        document.getElementById('creation_date').value = account.creation_date;
        document.getElementById('password').placeholder = "Isi hanya jika ingin mengubah password";
        document.getElementById('password').required = false;
        document.getElementById('password-required-star').style.display = 'none';
        document.getElementById('current_image_text').innerText = account.profile_picture ? "File saat ini: " + account.profile_picture : 'Belum ada gambar.';
        document.getElementById('followers').value = formatNumberWithDots(account.followers);
        document.getElementById('likes').value = formatNumberWithDots(account.likes);
        
        new bootstrap.Modal(document.getElementById('accountModal')).show();
    }

    function prepareStatsModal(accountId) {
        const account = allAccountsData.find(acc => acc.id === accountId);
        if (!account) return;
        
        document.getElementById('statsModalLabel').innerText = 'Update Statistik: ' + account.account_name;
        document.getElementById('stats_account_id').value = account.id;
        document.getElementById('stats_followers').value = formatNumberWithDots(account.followers);
        document.getElementById('stats_likes').value = formatNumberWithDots(account.likes);
        document.getElementById('stats_record_date').valueAsDate = new Date();
        
        new bootstrap.Modal(document.getElementById('statsModal')).show();
    }

    function showPassword(accountId) {
        document.getElementById('pinAccountId').value = accountId;
        document.getElementById('pinInput').value = '';
        document.getElementById('pinAlert').classList.add('d-none');
        
        const pinModal = new bootstrap.Modal(document.getElementById('pinModal'));
        pinModal.show();

        document.getElementById('pinModal').addEventListener('shown.bs.modal', function () {
            document.getElementById('pinInput').focus();
        });
    }

    function verifyPin() {
        const pin = document.getElementById('pinInput').value;
        const accountId = document.getElementById('pinAccountId').value;
        const formData = new URLSearchParams({ action: 'verify_pin', pin: pin, account_id: accountId });

        fetch('ajax_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('pinModal')).hide();
                    const account = allAccountsData.find(acc => acc.id == accountId);
                    
                    document.getElementById('passwordDisplayModalLabel').innerText = 'Kredensial untuk ' + account.account_name;
                    document.getElementById('revealedPassword').value = data.password;
                    document.getElementById('copyFeedback').innerText = '';

                    const emailGroup = document.getElementById('emailDisplayGroup');
                    if (data.email && data.email.trim() !== '') {
                        document.getElementById('revealedEmail').value = data.email;
                        emailGroup.classList.remove('d-none');
                    } else {
                        emailGroup.classList.add('d-none');
                    }
                    document.getElementById('copyFeedbackEmail').innerText = '';
                    
                    new bootstrap.Modal(document.getElementById('passwordDisplayModal')).show();
                } else {
                    const pinAlert = document.getElementById('pinAlert');
                    pinAlert.innerText = data.message || 'PIN salah!';
                    pinAlert.classList.remove('d-none');
                }
            })
            .catch(error => {
                document.getElementById('pinAlert').innerText = 'Terjadi error saat verifikasi.';
                document.getElementById('pinAlert').classList.remove('d-none');
            });
    }

    function showStatsHistory(accountId) {
        const account = allAccountsData.find(acc => acc.id === accountId);
        const history = statsHistoryData[accountId] || [];
        
        document.getElementById('statsHistoryModalLabel').innerText = 'Riwayat Statistik: ' + account.account_name;
        
        let tableHtml;
        if (history.length === 0) {
            tableHtml = '<div class="alert alert-info text-center">Belum ada riwayat statistik untuk akun ini.</div>';
        } else {
            let currentData = { record_date: 'Sekarang', followers: account.followers, likes: account.likes };
            let fullHistory = [currentData, ...history];
            let tableRows = '';

            for (let i = 0; i < fullHistory.length - 1; i++) {
                let current = fullHistory[i];
                let previous = fullHistory[i+1];
                let followersDiff = current.followers - previous.followers;
                let likesDiff = current.likes - previous.likes;

                let f_diff_str = followersDiff !== 0 ? `(<span class="history-diff \${followersDiff > 0 ? 'diff-increase' : 'diff-decrease'}">\${followersDiff > 0 ? '+' : ''}\${formatNumberWithDots(followersDiff)}</span>)` : '';
                let l_diff_str = likesDiff !== 0 ? `(<span class="history-diff \${likesDiff > 0 ? 'diff-increase' : 'diff-decrease'}">\${likesDiff > 0 ? '+' : ''}\${formatNumberWithDots(likesDiff)}</span>)` : '';
                
                let dateStr = current.record_date === 'Sekarang' 
                    ? '<b>Sekarang</b>' 
                    : new Date(current.record_date).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});

                tableRows += `
                    <tr>
                        <td>\${dateStr}</td>
                        <td>\${formatNumberWithDots(current.followers)} \${f_diff_str}</td>
                        <td>\${formatNumberWithDots(current.likes)} \${l_diff_str}</td>
                    </tr>
                `;
            }

            // Tambahkan baris paling awal (tanpa perbandingan)
            let last = fullHistory[fullHistory.length - 1];
            tableRows += `
                <tr>
                    <td>\${new Date(last.record_date).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'})}</td>
                    <td>\${formatNumberWithDots(last.followers)}</td>
                    <td>\${formatNumberWithDots(last.likes)}</td>
                </tr>
            `;

            tableHtml = `
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Followers</th>
                                <th>Likes</th>
                            </tr>
                        </thead>
                        <tbody>\${tableRows}</tbody>
                    </table>
                </div>
            `;
        }

        document.getElementById('statsHistoryBody').innerHTML = tableHtml;
        new bootstrap.Modal(document.getElementById('statsHistoryModal')).show();
    }
</script>
JS;

include 'includes/footer.php';
?>