<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// JANGAN PROTEKSI JIKA USER SEDANG DI HALAMAN LOGIN
// Ini penting supaya tidak terjadi perulangan redirect (looping)
$halaman_sekarang = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['login'])) {
    if (isset($_COOKIE['user_id']) && isset($_COOKIE['user_nama']) && isset($_COOKIE['user_role'])) {
        $_SESSION['login'] = true;
        $_SESSION['user']  = $_COOKIE['user_nama'];
        $_SESSION['role'] = $_COOKIE['user_role'];
    } else {
        // Hanya redirect jika user TIDAK sedang di login.php
        if ($halaman_sekarang !== 'login.php') {
            header("Location: /api/login.php");
            exit();
        }
    }
}