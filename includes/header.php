<?php
// Set variabel default jika belum didefinisikan di halaman pemanggil
if (!isset($page_title)) {
    $page_title = 'Main Wahyu';
}
if (!isset($active_page)) {
    $active_page = '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-g" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="<?php echo BASE_URL; ?>css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="<?php echo BASE_URL; ?>index.php"><i class="fas fa-shield-alt me-2"></i>Main Wahyu</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        
        <ul class="navbar-nav ms-auto me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="#!">Pengaturan</a></li>
                    <li><hr class="dropdown-divider" /></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>