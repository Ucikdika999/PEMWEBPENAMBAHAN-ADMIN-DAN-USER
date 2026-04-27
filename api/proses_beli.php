<?php
ob_start();
session_start();

// 1. Gunakan auth_check.php agar pengecekan login seragam
// File ini akan memastikan session atau cookie kamu terbaca dengan benar
include "auth_check.php"; 
include "koneksi.php";

// 2. Pastikan variabel session ada (antisipasi jika auth_check belum redirect)
if(!isset($_SESSION['login']) || !isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Menangkap data dari URL (id wisata)
if(isset($_GET['id'])) {
    // Gunakan mysqli_real_escape_string untuk keamanan
    $id_wisata = mysqli_real_escape_string($koneksi, $_GET['id']);
    $username = $_SESSION['user'];

    // 1. Cari dulu detail wisatanya berdasarkan ID
    $query_wisata = mysqli_query($koneksi, "SELECT * FROM destinasi WHERE id_wisata = '$id_wisata'");
    $data_wisata = mysqli_fetch_assoc($query_wisata);

    if($data_wisata) {
        $nama_wisata = mysqli_real_escape_string($koneksi, $data_wisata['nama_wisata']);
        $harga = $data_wisata['harga'];

        // 2. Simpan ke tabel pesanan
        // Pastikan tabel 'pesanan' kamu memiliki kolom: username, nama_wisata, harga
        $insert = mysqli_query($koneksi, "INSERT INTO pesanan (username, nama_wisata, harga) 
                                          VALUES ('$username', '$nama_wisata', '$harga')");

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
        echo "<script>alert('Data wisata tidak ditemukan!'); window.location='user_dashboard.php';</script>";
    }
} else {
    header("Location: user_dashboard.php");
    exit;
}
ob_end_flush();
?>