<?php
// File ini akan menjadi penjaga untuk semua halaman internal.

// 1. Memanggil konfigurasi dasar yang juga memulai session
require_once 'config.php';

// 2. Cek apakah session user_id ADA atau TIDAK
if (!isset($_SESSION['user_id'])) {
    // Jika tidak ada, artinya pengguna belum login.
    // Arahkan paksa kembali ke halaman login.
    header("Location: " . BASE_URL . "login.php");
    // Hentikan eksekusi skrip agar tidak ada kode lain yang dijalankan.
    exit();
}

// Jika session ada, skrip akan lanjut ke kode halaman yang memanggilnya.
?>