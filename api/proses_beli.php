<?php
ob_start();
session_start();

// Gunakan auth_check.php agar login tetap terjaga
include "auth_check.php"; 
include "koneksi.php";

// Cek apakah user sudah login melalui session
if(!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// Menangkap ID wisata dari URL
if(isset($_GET['id'])) {
    $id_wisata = mysqli_real_escape_string($koneksi, $_GET['id']);
    $username = $_SESSION['user'];

    // 1. Ambil detail wisata
    $query_wisata = mysqli_query($koneksi, "SELECT * FROM destinasi WHERE id_wisata = '$id_wisata'");
    $data_wisata = mysqli_fetch_assoc($query_wisata);

    if($data_wisata) {
        $nama_wisata = mysqli_real_escape_string($koneksi, $data_wisata['nama_wisata']);
        $harga = $data_wisata['harga'];

        // 2. Simpan ke tabel pesanan
        // Kita tidak perlu memasukkan 'id_pesanan' karena sudah AUTO_INCREMENT
        $sql = "INSERT INTO pesanan (username, nama_wisata, harga) 
                VALUES ('$username', '$nama_wisata', '$harga')";
        
        $insert = mysqli_query($koneksi, $sql);

        if($insert) {
            echo "<script>
                alert('Tiket Berhasil Dipesan!');
                window.location='tiket_saya.php';
            </script>";
            exit;
        } else {
            echo "Gagal memesan: " . mysqli_error($koneksi);
        }
    } else {
        echo "<script>alert('Wisata tidak ditemukan!'); window.location='user_dashboard.php';</script>";
    }
} else {
    header("Location: user_dashboard.php");
    exit;
}
ob_end_flush();
?>