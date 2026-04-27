<?php
// auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cek apakah ada Session login
if (!isset($_SESSION['login'])) {
    
    // 2. Jika tidak ada Session, cek apakah ada Cookie (Remember Me)
    if (isset($_COOKIE['user_id']) && isset($_COOKIE['user_role'])) {
        // Kembalikan data dari Cookie ke Session
        $_SESSION['login'] = true;
        $_SESSION['user']  = $_COOKIE['user_nama'];
        $_SESSION['role']  = $_COOKIE['user_role'];
    } else {
        // Jika tidak ada Session maupun Cookie, lempar ke login
        header("Location: login.php");
        exit();
    }
}
?>