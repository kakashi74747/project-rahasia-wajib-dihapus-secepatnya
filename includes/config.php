<?php
// =================================================================
//  FILE KONFIGURASI UTAMA APLIKASI "MAIN WAHYU" (VERSI TERBARU)
// =================================================================

// 1. MEMULAI SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. PENGATURAN DASAR
define('DB_PATH', __DIR__ . '/../database/main_wahyu.sqlite');
define('UPLOADS_PATH', __DIR__ . '/../uploads');
define('BASE_URL', 'http://localhost/main-wahyu/'); // Sesuaikan dengan nama folder proyek Anda

// 3. FUNGSI UNTUK KONEKSI DATABASE (MENGGUNAKAN PDO)
function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            if (!is_dir(dirname(DB_PATH))) {
                mkdir(dirname(DB_PATH), 0755, true);
            }
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Koneksi ke database gagal: " . $e->getMessage());
        }
    }
    return $pdo;
}

// --- FUNGSI UPGRADE & INISIALISASI DATABASE (VERSI FINAL) ---
function initializeDatabase() {
    $pdo = getDbConnection();
    
    // Perintah untuk membuat tabel-tabel awal
    $initial_commands = [
        "CREATE TABLE IF NOT EXISTS `users` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT, `username` TEXT NOT NULL UNIQUE, `password` TEXT NOT NULL, `pin` TEXT NOT NULL,
          `created_at` TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        );",
        
        "CREATE TABLE IF NOT EXISTS `categories` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT, `user_id` INTEGER NOT NULL, `category_name` TEXT NOT NULL,
          `display_order` INTEGER NOT NULL DEFAULT 0,
          `created_at` TEXT NOT NULL DEFAULT (datetime('now','localtime')),
          FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS `accounts` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT, `user_id` INTEGER NOT NULL, `category_id` INTEGER NOT NULL,
          `account_name` TEXT NOT NULL, `username` TEXT DEFAULT NULL, `email` TEXT DEFAULT NULL, `password` TEXT NOT NULL, `notes` TEXT DEFAULT NULL,
          `profile_picture` TEXT DEFAULT NULL, `followers` INTEGER DEFAULT 0, `likes` INTEGER DEFAULT 0,
          `creation_date` TEXT, `display_order` INTEGER DEFAULT 0,
          `created_at` TEXT NOT NULL DEFAULT (datetime('now','localtime')),
          `updated_at` TEXT NOT NULL DEFAULT (datetime('now','localtime')),
          FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
          FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS `stats_history` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT, `account_id` INTEGER NOT NULL, `followers` INTEGER NOT NULL,
          `likes` INTEGER NOT NULL, `record_date` TEXT NOT NULL DEFAULT (datetime('now','localtime')),
          FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
        );",

        "CREATE TRIGGER IF NOT EXISTS update_accounts_updated_at AFTER UPDATE ON accounts FOR EACH ROW
          BEGIN
              UPDATE accounts SET updated_at = datetime('now','localtime') WHERE id = OLD.id;
          END;"
    ];

    // Eksekusi setiap perintah SQL
    foreach ($initial_commands as $command) {
        $pdo->exec($command);
    }

    // Cek dan tambahkan kolom baru jika belum ada (Untuk upgrade dari versi lama)
    try {
        // Upgrade untuk tabel 'accounts'
        $result_acc = $pdo->query("PRAGMA table_info(accounts);")->fetchAll();
        $columns_acc = array_column($result_acc, 'name');
        
        if (!in_array('followers', $columns_acc)) $pdo->exec("ALTER TABLE accounts ADD COLUMN followers INTEGER DEFAULT 0;");
        if (!in_array('likes', $columns_acc)) $pdo->exec("ALTER TABLE accounts ADD COLUMN likes INTEGER DEFAULT 0;");
        if (!in_array('creation_date', $columns_acc)) {
            $pdo->exec("ALTER TABLE accounts ADD COLUMN creation_date TEXT;");
            $pdo->exec("UPDATE accounts SET creation_date = date(created_at);");
        }
        if (!in_array('display_order', $columns_acc)) {
            $pdo->exec("ALTER TABLE accounts ADD COLUMN display_order INTEGER DEFAULT 0;");
        }

        // DITAMBAHKAN: Upgrade untuk tabel 'categories'
        $result_cat = $pdo->query("PRAGMA table_info(categories);")->fetchAll();
        $columns_cat = array_column($result_cat, 'name');
        if (!in_array('display_order', $columns_cat)) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN display_order INTEGER NOT NULL DEFAULT 0;");
        }

    } catch (PDOException $e) {
        // Abaikan error jika alter table gagal (misal kolom sudah ada)
    }
}

// 5. LOGIKA "FIRST-RUN SETUP"
function checkFirstRun() {
    $pdo = getDbConnection();
    try {
        $stmt = $pdo->query("SELECT COUNT(id) as count FROM users");
        $result = $stmt->fetch();
        if ($result && $result['count'] == 0) {
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page != 'setup.php') {
                header('Location: ' . BASE_URL . 'setup.php');
                exit();
            }
        }
    } catch (PDOException $e) {
        // Tabel belum ada, biarkan initializeDatabase() yang menanganinya
    }
}

// =================================================================
//  EKSEKUSI FUNGSI-FUNGSI PENTING
// =================================================================
$pdo = getDbConnection();
initializeDatabase();
checkFirstRun();
?>