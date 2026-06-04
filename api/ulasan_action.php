<?php
session_start();
include "koneksi.php";

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── SUBMIT ULASAN (user login) ────────────────────────────────────────
if ($action === 'submit') {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php"); exit;
    }
    $id_wisata = (int)($_POST['id_wisata'] ?? 0);
    $rating    = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $komentar  = mysqli_real_escape_string($koneksi, trim($_POST['komentar'] ?? ''));
    $foto_url  = mysqli_real_escape_string($koneksi, trim($_POST['foto_url'] ?? ''));
    $username  = mysqli_real_escape_string($koneksi, $_SESSION['user']);

    if (!$id_wisata || !$komentar) {
        header("Location: detail_wisata.php?id=$id_wisata&msg=error_empty"); exit;
    }

    // Cek apakah user sudah pernah ulasan destinasi ini
    $cek = mysqli_query($koneksi, "SELECT id FROM ulasan WHERE id_wisata='$id_wisata' AND username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: detail_wisata.php?id=$id_wisata&msg=already"); exit;
    }

    $foto_sql = $foto_url ? "'$foto_url'" : "NULL";
    $sql = "INSERT INTO ulasan (id_wisata, username, rating, komentar, foto_url, status)
            VALUES ('$id_wisata','$username','$rating','$komentar',$foto_sql,'pending')";
    mysqli_query($koneksi, $sql);
    header("Location: detail_wisata.php?id=$id_wisata&msg=success"); exit;
}

// ── APPROVE (admin) ───────────────────────────────────────────────────
if ($action === 'approve') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { http_response_code(403); exit; }
    $id = (int)($_GET['id'] ?? 0);
    mysqli_query($koneksi, "UPDATE ulasan SET status='approved' WHERE id='$id'");
    header("Location: kelola_ulasan.php?msg=approved"); exit;
}

// ── REJECT (admin) ────────────────────────────────────────────────────
if ($action === 'reject') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { http_response_code(403); exit; }
    $id = (int)($_GET['id'] ?? 0);
    mysqli_query($koneksi, "UPDATE ulasan SET status='rejected' WHERE id='$id'");
    header("Location: kelola_ulasan.php?msg=rejected"); exit;
}

// ── HAPUS (admin) ─────────────────────────────────────────────────────
if ($action === 'hapus') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { http_response_code(403); exit; }
    $id = (int)($_GET['id'] ?? 0);
    mysqli_query($koneksi, "DELETE FROM ulasan WHERE id='$id'");
    header("Location: kelola_ulasan.php?msg=deleted"); exit;
}

header("Location: index.php"); exit;