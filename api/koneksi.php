<?php
$host     = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com";
$port     = 4000;
$username = "2tfzdzpXzpZ8yRy.root";
$password = "xG0M9RxORnl8ZUZz";
$database = "monitoring_tiket2";

$koneksi = mysqli_init();

// Set SSL (harus sebelum real_connect)
mysqli_ssl_set($koneksi, NULL, NULL, NULL, NULL, NULL);

if (!mysqli_real_connect($koneksi, $host, $username, $password, $database, $port)) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>