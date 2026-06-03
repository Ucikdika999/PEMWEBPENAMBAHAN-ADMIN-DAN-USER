<?php
// catat_kunjungan.php
// Include file ini di halaman-halaman yang ingin dilacak
// Pastikan koneksi.php sudah di-include sebelumnya
// Catat kunjungan
$_hal = basename($_SERVER['PHP_SELF']);
$_usr = isset($_SESSION['user']) ? mysqli_real_escape_string($koneksi, $_SESSION['user']) : 'tamu';
$_ip  = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
mysqli_query($koneksi, "INSERT INTO log_kunjungan (halaman, username, ip_address) VALUES ('$_hal', '$_usr', '$_ip')");

function catatKunjungan($koneksi, $halaman = '') {
    if (empty($halaman)) {
        $halaman = basename($_SERVER['PHP_SELF']);
    }
    $username   = isset($_SESSION['user']) ? $_SESSION['user'] : 'tamu';
    $ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $halaman    = mysqli_real_escape_string($koneksi, $halaman);
    $username   = mysqli_real_escape_string($koneksi, $username);
    $ip         = mysqli_real_escape_string($koneksi, $ip);

    mysqli_query($koneksi,
        "INSERT INTO log_kunjungan (halaman, username, ip_address)
         VALUES ('$halaman', '$username', '$ip')"
    );
}
?>