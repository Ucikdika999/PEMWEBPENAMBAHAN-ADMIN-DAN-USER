<?php
$host     = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com";
$port     = 4000;
$username = "2tfzdzpXzpZ8yRy.root";
$password = "xG0M9RxORnl8ZUZz";
$database = "monitoring_tiket2";

$koneksi = mysqli_connect($host, $username, $password, $database, $port);

mysqli_ssl_set($koneksi, NULL, NULL, NULL, NULL, NULL);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>