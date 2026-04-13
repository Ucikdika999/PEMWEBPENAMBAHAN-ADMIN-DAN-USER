<?php
session_start();
if(!isset($_SESSION['login'])) { 
    header("Location: login.php"); 
    exit; 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pengunjung | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1350&q=80');
            background-size: cover; background-attachment: fixed; color: white; min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px; padding: 40px; margin-top: 40px;
        }
        .ticket-box {
            background: white; color: black; border-radius: 15px; padding: 20px;
            border-left: 10px solid #ffc107; display: flex; justify-content: space-between; align-items: center;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-transparent pt-4 px-5">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold fs-3" href="#"><i class="fas fa-mountain-sun me-2"></i>WISATA</a>
        <a href="logout.php" class="btn btn-outline-light rounded-pill px-4">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="glass-card shadow-lg">
        <div class="row align-items-center mb-5">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold mb-0">Halo, <?php echo $_SESSION['user']; ?>!</h1>
                <p class="lead opacity-75 mt-2">Cek status tiket dan jadwal liburanmu di sini.</p>
            </div>
            <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
                <div class="p-3 bg-white text-dark rounded-4 fw-bold">PENGUNJUNG VIP</div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-7">
                <h4 class="mb-4"><i class="fas fa-ticket text-warning me-2"></i>Tiket Anda</h4>
                <div class="ticket-box shadow">
                    <div>
                        <p class="text-muted small mb-1">KODE BOOKING: #WDT-9920</p>
                        <h5 class="fw-bold mb-0">Tiket Wahana SkyView</h5>
                        <p class="text-primary mb-0 small">Valid s/d 20 April 2026</p>
                    </div>
                    <div class="text-center p-3 border-start">
                        <i class="fas fa-qrcode fa-3x"></i>
                        <div class="small fw-bold">SCAN</div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <h4 class="mb-4"><i class="fas fa-info-circle text-info me-2"></i>Informasi Hari Ini</h4>
                <div class="glass-card p-3 mt-0">
                    <p class="mb-2"><i class="fas fa-cloud-sun me-2 text-warning"></i>Cuaca: Cerah (31°C)</p>
                    <p class="mb-2"><i class="fas fa-clock me-2 text-success"></i>Buka: 08.00 - 17.00 WIB</p>
                    <p class="mb-0"><i class="fas fa-users me-2 text-info"></i>Antrean: Normal (5 mnt)</p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>