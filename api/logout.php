<?php
session_start();
session_unset();
session_destroy();

// Hapus cookie login
setcookie('user_id', '', time() - 3600, "/");
setcookie('user_nama', '', time() - 3600, "/");
setcookie('user_role', '', time() - 3600, "/");

header('Location: /api/login.php');
exit();
?>