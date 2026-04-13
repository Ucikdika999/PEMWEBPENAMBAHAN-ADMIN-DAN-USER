<?php
session_start();
session_unset();
session_destroy();

// Pastikan file tujuan 'index.php' ada di folder yang sama
echo "<script>
    alert('Anda telah berhasil keluar.');
    window.location='index.php'; 
</script>";
exit;
?>