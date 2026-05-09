<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit();
}
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengunjung | WISATA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; }
        .navbar {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white; padding: 15px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .navbar h1 { font-size: 1.4rem; }
        .logout-btn {
            background: rgba(255,255,255,0.2); color: white;
            border: none; padding: 8px 16px; border-radius: 8px;
            cursor: pointer; text-decoration: none;
        }
        .container { padding: 30px; max-width: 900px; margin: auto; }
        .card {
            background: white; border-radius: 12px;
            padding: 25px; margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .card h3 { color: #2e7d32; margin-bottom: 15px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f4f8; }
        .info-label { color: #666; font-weight: 500; }
        .info-value { color: #333; font-weight: 600; }
        .ticket {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white; border-radius: 16px; padding: 30px;
            text-align: center; margin-top: 10px;
        }
        .ticket h2 { font-size: 1.2rem; opacity: 0.9; margin-bottom: 10px; }
        .ticket .ticket-id { font-size: 2rem; font-weight: bold; letter-spacing: 3px; }
        .ticket .ticket-date { opacity: 0.8; margin-top: 10px; font-size: 0.9rem; }
        .btn {
            display: inline-block; padding: 12px 24px; border-radius: 8px;
            font-weight: 600; cursor: pointer; border: none; font-size: 1rem;
            text-decoration: none; margin-top: 10px;
        }
        .btn-primary { background: #1a73e8; color: white; }
        .btn-primary:hover { background: #1558b0; }
    </style>
</head>
<body>
<nav class="navbar">
    <h1>🌿 WISATA Pengunjung</h1>
    <a href="logout.php" class="logout-btn">🚪 Logout</a>
</nav>

<div class="container">
    <div class="card">
        <h3>👤 Profil Saya</h3>
        <div class="info-row">
            <span class="info-label">Username</span>
            <span class="info-value"><?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Role</span>
            <span class="info-value">🌿 Pengunjung</span>
        </div>
        <div class="info-row">
            <span class="info-label">Login Pada</span>
            <span class="info-value"><?= date('d M Y, H:i') ?> WIB</span>
        </div>
    </div>

    <div class="card">
        <h3>🎫 Tiket Saya</h3>
        <div class="ticket">
            <h2>🏔️ Tiket Masuk Wisata</h2>
            <div class="ticket-id">WST-<?= strtoupper(substr(md5($_SESSION['username']), 0, 8)) ?></div>
            <div class="ticket-date">Berlaku: <?= date('d M Y') ?></div>
        </div>
        <center><a href="#" class="btn btn-primary" style="margin-top:15px;">📥 Unduh Tiket PDF</a></center>
    </div>
</div>
</body>
</html>