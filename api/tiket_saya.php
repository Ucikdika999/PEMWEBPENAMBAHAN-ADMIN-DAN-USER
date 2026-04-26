<?php
session_start();
include "koneksi.php";

if(!isset($_SESSION['login'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_login = $_SESSION['user'];
$query_tiket = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE username = '$user_login' ORDER BY id_pesanan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Tiket Saya | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f4f7f6; }
        .ticket-card {
            border: none; border-left: 8px solid #0d6efd;
            border-radius: 15px; background: white;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="user_dashboard.php"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
    </div>
</nav>

<div class="container">
    <h3 class="mb-4">Riwayat Pembelian Tiket</h3>

    <div class="row">
        <?php if(mysqli_num_rows($query_tiket) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($query_tiket)): ?>
            <div class="col-md-6 mb-3">
                <div class="card ticket-card shadow-sm p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted">KODE BOOKING: #WDT-<?php echo $row['id_pesanan']; ?></small>
                            <h4 class="fw-bold mt-1 text-primary"><?php echo $row['nama_wisata']; ?></h4>
                            <p class="mb-0 text-muted"><i class="fas fa-calendar-alt me-1"></i> Tanggal: <?php echo date('d M Y', strtotime($row['tanggal_pesan'])); ?></p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success mb-2">LUNAS</span>
                            <h5 class="fw-bold">Rp<?php echo number_format($row['harga']); ?></h5>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <small class="text-muted">Tunjukkan tiket ini di gerbang masuk</small>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center mt-5">
                <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                <p class="lead">Belum ada tiket yang dibeli.</p>
                <a href="user_dashboard.php" class="btn btn-primary">Cari Destinasi</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>