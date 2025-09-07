<?php
// Memanggil file konfigurasi utama
require_once 'includes/config.php';

// Jika user SUDAH login, langsung arahkan ke halaman utama (dashboard).
// Ini mencegah user yang sudah login melihat halaman login lagi.
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . 'index.php'); // Kita akan buat index.php nanti
    exit();
}

$error_message = '';

// Proses form HANYA jika metode request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = "Username dan password wajib diisi.";
    } else {
        try {
            // 1. Cari user berdasarkan username
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // 2. Verifikasi password
            // password_verify() adalah fungsi aman untuk membandingkan password mentah dengan hash
            if ($user && password_verify($password, $user['password'])) {
                // Jika password cocok, proses login berhasil
                
                // Regenerasi session ID untuk keamanan (mencegah session fixation)
                session_regenerate_id(true);

                // Simpan informasi penting ke dalam session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Arahkan ke halaman dashboard utama
                header("Location: " . BASE_URL . 'index.php');
                exit();
            } else {
                // Jika user tidak ditemukan atau password salah
                $error_message = "Username atau password salah.";
            }

        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan pada database.";
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
    <title>Login - Main Wahyu</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="bg-primary">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header"><h3 class="text-center font-weight-light my-4">Login Main Wahyu</h3></div>
                                <div class="card-body">

                                    <?php if (!empty($error_message)): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo htmlspecialchars($error_message); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($_GET['success'])): ?>
                                        <div class="alert alert-success" role="alert">
                                            Setup berhasil! Silakan login dengan akun yang baru Anda buat.
                                        </div>
                                    <?php endif; ?>

                                    <form action="login.php" method="POST">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputUsername" name="username" type="text" placeholder="Username" required />
                                            <label for="inputUsername">Username</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Password" required />
                                            <label for="inputPassword">Password</label>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                            <button type="submit" class="btn btn-primary w-100">Login</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center py-3">
                                    <div class="small">Aplikasi Password & Financial Manager Pribadi</div>
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