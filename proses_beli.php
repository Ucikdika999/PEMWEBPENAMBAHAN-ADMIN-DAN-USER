<?php
session_start();
include "koneksi.php";

// Cek apakah user sudah login
if(!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// Menangkap data dari URL (id wisata)
if(isset($_GET['id'])) {
    $id_wisata = $_GET['id'];
    $username = $_SESSION['user'];

    // 1. Cari dulu detail wisatanya berdasarkan ID
    $query_wisata = mysqli_query($koneksi, "SELECT * FROM destinasi WHERE id_wisata = '$id_wisata'");
    $data_wisata = mysqli_fetch_assoc($query_wisata);

    if($data_wisata) {
        $nama_wisata = $data_wisata['nama_wisata'];
        $harga = $data_wisata['harga'];

        // 2. Simpan ke tabel pesanan
        $insert = mysqli_query($koneksi, "INSERT INTO pesanan (username, nama_wisata, harga) 
                                         VALUES ('$username', '$nama_wisata', '$harga')");

        if($insert) {
            echo "<script>
                alert('Tiket Berhasil Dipesan!');
                window.location='tiket_saya.php';
            </script>";
        } else {
            echo "Gagal memesan: " . mysqli_error($koneksi);
        }
    }
} else {
    header("Location: user_dashboard.php");
}
?>