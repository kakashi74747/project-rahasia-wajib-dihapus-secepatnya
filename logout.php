<?php
// Selalu mulai dengan memanggil session_start() untuk bisa memanipulasi session
session_start();

// Langkah 1: Hapus semua data yang tersimpan di dalam variabel $_SESSION.
// Ini akan menghapus 'user_id' dan 'username' yang kita simpan saat login.
$_SESSION = array();

// Langkah 2: Hancurkan session itu sendiri.
// Ini adalah langkah pembersihan akhir untuk memastikan tidak ada sisa sesi.
session_destroy();

// Langkah 3: Arahkan pengguna kembali ke halaman login.
// Kita tambahkan parameter ?logout=1 agar di halaman login bisa menampilkan pesan perpisahan.
// (Ini opsional tapi bagus untuk user experience).
header("Location: login.php?logout=1");
exit();
?>