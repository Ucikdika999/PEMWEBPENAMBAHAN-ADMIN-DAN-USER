<?php
// Cek apakah parameter ke-3 (password) sudah kosong "" 
$koneksi = mysqli_connect("localhost", "root", "", "monitoring_tiket2");

if (mysqli_connect_errno()) {
    echo "Koneksi database gagal : " . mysqli_connect_error();
}
?>