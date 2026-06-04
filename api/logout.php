<?php
session_start();
session_destroy();
header('Location: /api/index.html');
exit();
?>