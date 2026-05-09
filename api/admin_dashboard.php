<?php
ob_start();
include "auth_check.php";
include "koneksi.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: user_dashboard.php");
    exit();
}

// Statistik
$total_all   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users"))['t'];
$total_users = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='user'"))['t'];
$total_admin = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='admin'"))['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | WISATA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; }
        .navbar {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: white; padding: 15px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .navbar h1 { font-size: 1.4rem; }
        .logout-btn {
            background: rgba(255,255,255,0.2); color: white;
            border: none; padding: 8px 16px; border-radius: 8px;
            cursor: pointer; text-decoration: none; font-size: 0.9rem;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        .container { padding: 30px; max-width: 1200px; margin: auto; }
        .welcome { margin-bottom: 25px; }
        .welcome h2 { color: #1a73e8; font-size: 1.6rem; }
        .welcome p { color: #666; margin-top: 5px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .stat-card {
            background: white; border-radius: 12px;
            padding: 20px; text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #1a73e8;
        }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; color: #1a73e8; }
        .stat-card .label { color: #666; margin-top: 5px; }
        .card {
            background: white; border-radius: 12px;
            padding: 25px; margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .card h3 { color: #333; margin-bottom: 15px; border-bottom: 2px solid #f0f4f8; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0f4f8; padding: 12px; text-align: left; color: #555; font-weight: 600; }
        td { padding: 12px; border-bottom: 1px solid #f0f4f8; color: #333; }
        tr:hover { background: #f9fbff; }
        .badge {
            padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;
        }
        .badge-admin { background: #e8f0fe; color: #1a73e8; }
        .badge-user { background: #e6f4ea; color: #2e7d32; }
        .btn-danger {
            background: #ff4444; color: white; border: none;
            padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem;
        }
        .btn-danger:hover { background: #cc0000; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #e6f4ea; color: #2e7d32; border-left: 4px solid #2e7d32; }
        .alert-error { background: #fce8e6; color: #c62828; border-left: 4px solid #c62828; }
    </style>
</head>
<body>
<nav class="navbar">
    <h1>🏔️ WISATA Admin</h1>
    <a href="logout.php" class="logout-btn">🚪 Logout</a>
    <div style="display:flex; gap:10px;">
        <a href="kelola_wisata.php" class="logout-btn">🏝️ Kelola Wisata</a>
        <a href="kelola_user.php" class="logout-btn">👥 Kelola User</a>
        <a href="laporan_pesanan.php" class="logout-btn">📋 Laporan Pesanan</a>
        <a href="profil.php" class="logout-btn">👤 Profil</a>
        <a href="logout.php" class="logout-btn" style="background:rgba(255,80,80,0.4);">🚪 Logout</a>
    </div>
</nav>
</nav>

<div class="container">
    <div class="welcome">
        <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['user']) ?>!</h2>
        <p>Panel Admin - Sistem Monitoring Tiket Wisata</p>
    </div>

    <?php
    // Tampilkan pesan flash jika ada
    if (isset($_SESSION['msg'])) {
        $type = $_SESSION['msg_type'] ?? 'success';
        echo "<div class='alert alert-{$type}'>{$_SESSION['msg']}</div>";
        unset($_SESSION['msg'], $_SESSION['msg_type']);
    }
    ?>

    <?php
    include 'koneksi.php';

    $total_all   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users"))['t'];
    $total_users = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='user'"))['t'];
    $total_admin = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='admin'"))['t'];
    ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?= $total_all ?></div>
            <div class="label">Total Pengguna</div>
        </div>
        <div class="stat-card" style="border-left-color:#2e7d32;">
            <div class="number" style="color:#2e7d32;"><?= $total_users ?></div>
            <div class="label">Pengunjung</div>
        </div>
        <div class="stat-card" style="border-left-color:#f57c00;">
            <div class="number" style="color:#f57c00;"><?= $total_admin ?></div>
            <div class="label">Petugas/Admin</div>
        </div>
    </div>

    <div class="card">
        <h3>👥 Daftar Semua Pengguna</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmt = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id ASC");
            $no = 1;
            while ($row = mysqli_fetch_assoc($stmt)):
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td>
                        <span class="badge <?= $row['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                            <?= $row['role'] === 'admin' ? '🔑 Admin' : '👤 User' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['username'] !== $_SESSION['user']): ?>
                        <form method="POST" action="delete_user.php" onsubmit="return confirm('Hapus user ini?')">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn-danger">🗑️ Hapus</button>
                        </form>
                        <?php else: ?>
                            <em style="color:#aaa">Anda sendiri</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>