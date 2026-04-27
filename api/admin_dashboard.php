<?php
ob_start();
session_start();

// 1. Sertakan file pengecekan login
include "auth_check.php"; 

// 2. Sertakan koneksi database
include "koneksi.php";

// 3. Proteksi tambahan: Pastikan hanya ADMIN yang bisa akses halaman ini
// Jika user biasa coba masuk, lempar ke user_dashboard
if ($_SESSION['role'] !== 'admin') {
    header("Location: user_dashboard.php");
    exit();
}

// 4. Ambil data statistik dari database
$query_user = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users");
$data_user = mysqli_fetch_assoc($query_user);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { height: 100vh; width: 250px; position: fixed; background: #212529; color: white; padding: 20px; }
        .content { margin-left: 250px; padding: 30px; }
        .nav-link { padding: 10px 15px; transition: 0.3s; color: rgba(255,255,255,0.7) !important; }
        .nav-link:hover { background: rgba(255,255,255,0.1); border-radius: 5px; color: white !important; }
        .nav-link.active { background: #0d6efd; color: white !important; border-radius: 5px; }
        .card-custom { transition: 0.3s; }
        .card-custom:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <div class="sidebar shadow">
        <h4 class="mb-5 fw-bold text-primary text-center"><i class="fas fa-shield-halved"></i> ADMIN PANEL</h4>
        <nav class="nav flex-column">
            <a class="nav-link active" href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a>
            <a class="nav-link" href="kelola_wisata.php"><i class="fas fa-mountain-sun me-2"></i> Kelola Wisata</a>
            <a class="nav-link" href="kelola_user.php"><i class="fas fa-users me-2"></i> Kelola User</a>
            <hr class="bg-secondary">
            <a class="nav-link text-danger fw-bold" href="logout.php"><i class="fas fa-power-off me-2"></i> Logout</a>
        </nav>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Ringkasan Statistik</h2>
            <span class="badge bg-dark p-2">Sesi: <?php echo htmlspecialchars($_SESSION['user']); ?></span>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-custom shadow-sm border-0 bg-primary text-white p-4 rounded-4">
                    <h5>Total Pengguna</h5>
                    <h1 class="fw-bold display-4"><?php echo $data_user['total']; ?></h1>
                    <p class="mb-0 opacity-75">Jiwa Terdaftar</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom shadow-sm border-0 bg-success text-white p-4 rounded-4">
                    <h5>Status Server</h5>
                    <h1 class="fw-bold display-4">Online</h1>
                    <p class="mb-0 opacity-75">Sistem Berjalan Normal</p>
                </div>
            </div>
        </div>

        <div class="mt-5 p-4 bg-white rounded-4 shadow-sm">
            <h4>Selamat Datang Kembali, Admin!</h4>
            <p class="text-muted">Gunakan menu di samping untuk mengelola destinasi wisata atau memantau akun pengguna.</p>
        </div>
    </div>
</body>
</html>