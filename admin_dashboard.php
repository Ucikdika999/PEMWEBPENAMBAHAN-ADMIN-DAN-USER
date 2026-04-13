<?php
session_start();
if($_SESSION['role'] != 'admin') { 
    header("Location: login.php"); 
    exit; 
}
include "koneksi.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .sidebar { height: 100vh; width: 250px; position: fixed; background: #212529; color: white; padding: 20px; }
        .content { margin-left: 250px; padding: 30px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 fw-bold text-primary"><i class="fas fa-shield-halved"></i> ADMIN PANEL</h4>
        <nav class="nav flex-column">
            <a class="nav-link text-white active" href="#"><i class="fas fa-chart-line me-2"></i> Dashboard</a>
            <a class="nav-link text-white" href="#"><i class="fas fa-users me-2"></i> Kelola User</a>
            <hr>
            <a class="nav-link text-danger" href="logout.php"><i class="fas fa-power-off me-2"></i> Logout</a>
        </nav>
    </div>

    <div class="content">
        <h2 class="mb-4">Selamat Datang, <?php echo $_SESSION['user']; ?></h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-primary text-white p-4 rounded-4">
                    <h5>Total Pengguna</h5>
                    <h1 class="fw-bold"><?php echo $data_user['total']; ?></h1>
                    <p class="mb-0">Jiwa Terdaftar</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-success text-white p-4 rounded-4">
                    <h5>Status Server</h5>
                    <h1 class="fw-bold">Online</h1>
                    <p class="mb-0">Sistem Berjalan Normal</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>