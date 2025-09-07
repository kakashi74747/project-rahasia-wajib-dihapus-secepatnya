<?php
// Langkah 1: Panggil penjaga keamanan. Ini harus ada di baris paling atas.
require_once 'includes/auth_check.php';

// Langkah 2: Panggil koneksi database dari config.php
// Kita tidak perlu memanggil config.php lagi karena auth_check.php sudah memanggilnya.

// Menyiapkan judul halaman dan menandai halaman 'dashboard' sebagai halaman aktif
$page_title = 'Dashboard - Main Wahyu';
$active_page = 'dashboard';

// Langkah 3: Ambil data ringkasan dari database
try {
    // Ambil ID user yang sedang login dari session
    $user_id = $_SESSION['user_id'];

    // Query untuk menghitung total kategori milik user ini
    $stmt_cat = $pdo->prepare("SELECT COUNT(id) FROM categories WHERE user_id = ?");
    $stmt_cat->execute([$user_id]);
    $total_categories = $stmt_cat->fetchColumn();

    // Query untuk menghitung total akun yang disimpan milik user ini
    $stmt_acc = $pdo->prepare("SELECT COUNT(id) FROM accounts WHERE user_id = ?");
    $stmt_acc->execute([$user_id]);
    $total_accounts = $stmt_acc->fetchColumn();

} catch (PDOException $e) {
    // Jika ada error saat mengambil data, tampilkan pesan sederhana
    $total_categories = 0;
    $total_accounts = 0;
    // (Opsional) Anda bisa mencatat error $e->getMessage() ke file log untuk debugging
}


// Langkah 4: "Rakit" halaman dengan menyertakan file-file layout
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboard</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Selamat datang kembali, <?php echo htmlspecialchars($_SESSION['username']); ?>!</li>
    </ol>

    <div class="row">
        <div class="col-xl-6 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-tags fa-2x"></i>
                        </div>
                        <div class="text-end">
                            <div class="fs-1 fw-bold"><?php echo $total_categories; ?></div>
                            <div>Total Kategori</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="kategori.php">Lihat Detail</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-key fa-2x"></i>
                        </div>
                        <div class="text-end">
                            <div class="fs-1 fw-bold"><?php echo $total_accounts; ?></div>
                            <div>Total Akun Tersimpan</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="kelola_akun.php">Lihat Detail</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-bullhorn me-1"></i>
            Papan Informasi
        </div>
        <div class="card-body">
            <p>Selamat datang di **Main Wahyu v1.0**, pusat kendali digital pribadi Anda.</p>
            <ul>
                <li>Gunakan menu **"Kelola Akun"** untuk mulai menyimpan password Anda dengan aman.</li>
                <li>Gunakan menu **"Kategori"** untuk mengelompokkan akun Anda agar lebih rapi.</li>
                <li>Fitur **Financial Tracker** akan segera hadir di pembaruan berikutnya!</li>
            </ul>
            <p class="mb-0">Semua data Anda disimpan secara lokal di perangkat Anda, memberikan Anda privasi dan kontrol penuh.</p>
        </div>
    </div>

</div>
<?php
// Langkah 5: Panggil footer untuk menutup halaman
include 'includes/footer.php';
?>