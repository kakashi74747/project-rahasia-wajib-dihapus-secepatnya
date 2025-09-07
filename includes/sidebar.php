<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    <div class="sb-sidenav-menu-heading">Menu Utama</div>
                    <a class="nav-link <?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>index.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                        Dashboard
                    </a>
                    <a class="nav-link <?php echo ($active_page == 'kelola_akun') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>kelola_akun.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-key"></i></div>
                        Kelola Akun
                    </a>
                    <a class="nav-link <?php echo ($active_page == 'kategori') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>kategori.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
                        Kategori
                    </a>

                    <div class="sb-sidenav-menu-heading">Financial Tracker</div>
                    <a class="nav-link disabled" href="#">
                        <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>
                        Aset (Segera Hadir)
                    </a>
                     <a class="nav-link disabled" href="#">
                        <div class="sb-nav-link-icon"><i class="fas fa-tasks"></i></div>
                        Anggaran (Segera Hadir)
                    </a>
                </div>
            </div>
            <div class="sb-sidenav-footer">
                <div class="small">Login sebagai:</div>
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
        </nav>
    </div>
    <div id="layoutSidenav_content">
        <main>