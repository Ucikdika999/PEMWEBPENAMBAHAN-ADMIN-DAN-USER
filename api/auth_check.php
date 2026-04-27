<?php
// Mulai langsung di baris 1, tanpa spasi di atasnya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login'])) {
    if (isset($_COOKIE['user_id'])) {
        $_SESSION['login'] = true;
        $_SESSION['user']  = $_COOKIE['user_nama'];
        $_SESSION['role']  = $_COOKIE['user_role'];
    } else {
        header("Location: login.php");
        exit();
    }
}
// JANGAN ada spasi atau baris kosong setelah tag penutup atau biarkan tanpa ?>