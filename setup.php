<?php
// Memanggil file konfigurasi utama yang berisi semua logika dasar
require_once 'includes/config.php';

// Cek lagi: Jika user sudah ada, jangan biarkan halaman ini diakses.
// Ini mencegah orang lain membuat akun baru setelah setup selesai.
$stmt = $pdo->query("SELECT COUNT(id) as count FROM users");
$user_exists = $stmt->fetchColumn() > 0;

if ($user_exists) {
    // Jika sudah ada user, langsung arahkan ke halaman login
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$error_message = '';

// Proses form HANYA jika metode request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $pin = trim($_POST['pin']);

    // Validasi sederhana
    if (empty($username) || empty($password) || empty($pin)) {
        $error_message = "Semua kolom wajib diisi.";
    } elseif ($password !== $password_confirm) {
        $error_message = "Konfirmasi password tidak cocok.";
    } elseif (!ctype_digit($pin) || strlen($pin) !== 4) {
        $error_message = "PIN harus terdiri dari 4 angka.";
    } else {
        try {
            // Hashing password dan PIN untuk keamanan (standar industri)
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $hashed_pin = password_hash($pin, PASSWORD_BCRYPT);

            // Menyimpan user baru ke database
            $stmt = $pdo->prepare("INSERT INTO users (username, password, pin) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $hashed_pin]);
            
            // Mengarahkan ke halaman login dengan pesan sukses
            header('Location: ' . BASE_URL . 'login.php?success=1');
            exit();

        } catch (PDOException $e) {
            // Cek jika error disebabkan oleh username yang sudah ada (meski seharusnya tidak terjadi di sini)
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                 $error_message = "Username sudah terdaftar.";
            } else {
                 $error_message = "Terjadi kesalahan pada database: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Setup Aplikasi Main Wahyu" />
    <meta name="author" content="Wahyu" />
    <title>Setup Awal - Main Wahyu</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="bg-primary">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-7">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header"><h3 class="text-center font-weight-light my-4">Selamat Datang! <br> Buat Akun Admin Anda</h3></div>
                                <div class="card-body">
                                    
                                    <?php if (!empty($error_message)): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo htmlspecialchars($error_message); ?>
                                        </div>
                                    <?php endif; ?>

                                    <form action="setup.php" method="POST">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputUsername" name="username" type="text" placeholder="Username" required />
                                            <label for="inputUsername">Username</label>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Buat password" required />
                                                    <label for="inputPassword">Password</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputPasswordConfirm" name="password_confirm" type="password" placeholder="Konfirmasi password" required />
                                                    <label for="inputPasswordConfirm">Konfirmasi Password</label>
                                                </div>
                                            </div>
                                        </div>
                                         <div class="form-floating mb-3">
                                            <input class="form-control" id="inputPin" name="pin" type="password" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="Buat PIN 4 digit" required />
                                            <label for="inputPin">Buat PIN 4 Digit (untuk melihat password)</label>
                                        </div>
                                        <div class="mt-4 mb-0">
                                            <div class="d-grid"><button type="submit" class="btn btn-primary btn-block">Buat Akun dan Selesaikan Setup</button></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>
</html>