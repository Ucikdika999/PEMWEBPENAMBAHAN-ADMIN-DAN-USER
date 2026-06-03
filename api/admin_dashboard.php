<?php
session_start();
include "auth_check.php";
include "koneksi.php";
$_hal = basename($_SERVER['PHP_SELF']);
$_usr = isset($_SESSION['user']) ? mysqli_real_escape_string($koneksi, $_SESSION['user']) : 'tamu';
$_ip  = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
mysqli_query($koneksi, "INSERT INTO log_kunjungan (halaman, username, ip_address) VALUES ('$_hal', '$_usr', '$_ip')");

if($_SESSION['role'] != 'admin') { 
    header("Location: login.php"); 
    exit; 
}

// Total pengguna
$q_user = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users");
$data_user = mysqli_fetch_assoc($q_user);

// Total pesanan
$q_pesanan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan");
$total_pesanan = mysqli_fetch_assoc($q_pesanan)['total'] ?? 0;

// Total pendapatan
$q_pend = mysqli_query($koneksi, "SELECT SUM(harga) as total FROM pesanan");
$total_pendapatan = mysqli_fetch_assoc($q_pend)['total'] ?? 0;

// Kunjungan hari ini
$q_hari = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_kunjungan WHERE DATE(waktu) = CURDATE()");
$kunjungan_hari_ini = mysqli_fetch_assoc($q_hari)['total'] ?? 0;

// Destinasi terlaris
$q_terlaris = mysqli_query($koneksi,
    "SELECT nama_wisata, COUNT(*) as total FROM pesanan GROUP BY nama_wisata ORDER BY total DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | WISATA</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    :root {
        --sidebar-bg:#0f172a; --accent:#2563eb; --border:#e2e8f0;
        --surface:#fff; --bg:#f1f5f9; --text-primary:#0f172a;
        --text-muted:#64748b; --sidebar-w:240px;
    }
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text-primary);display:flex;min-height:100vh;}
    .sidebar{width:var(--sidebar-w);min-height:100vh;position:fixed;top:0;left:0;background:var(--sidebar-bg);display:flex;flex-direction:column;z-index:200;}
    .sb-brand{padding:22px 18px 16px;border-bottom:1px solid rgba(255,255,255,0.06);}
    .sb-logo{display:flex;align-items:center;gap:10px;}
    .sb-icon{width:40px;height:40px;background:var(--accent);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
    .sb-title{color:#fff;font-weight:800;font-size:1rem;line-height:1.1;}
    .sb-sub{color:rgba(255,255,255,0.35);font-size:0.68rem;}
    .sb-menu{padding:14px 10px;flex:1;}
    .menu-section{font-size:.62rem;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,.25);font-weight:700;padding:10px 10px 5px;margin-top:4px;}
    .nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;color:rgba(255,255,255,.45);font-size:.84rem;font-weight:500;text-decoration:none;transition:.18s;margin-bottom:2px;}
    .nav-item .ni-icon{width:30px;height:30px;border-radius:7px;background:rgba(255,255,255,.05);display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;}
    .nav-item:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.85);}
    .nav-item.active{background:var(--accent);color:#fff;box-shadow:0 4px 16px rgba(37,99,235,.35);}
    .nav-item.active .ni-icon{background:rgba(255,255,255,.18);}
    .nav-item.logout{color:rgba(239,100,100,.7);}
    .nav-item.logout:hover{background:rgba(239,68,68,.1);color:#fca5a5;}
    .sb-footer{padding:12px 10px;border-top:1px solid rgba(255,255,255,.06);}
    .admin-tag{display:flex;align-items:center;gap:9px;padding:9px 10px;background:rgba(255,255,255,.04);border-radius:9px;margin-bottom:6px;}
    .admin-ava{width:32px;height:32px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;}
    .admin-name{color:#fff;font-size:.8rem;font-weight:600;}
    .admin-role{color:rgba(255,255,255,.3);font-size:.65rem;}
    .main{margin-left:var(--sidebar-w);flex:1;padding:28px 30px;}
    .page-title{font-size:1.45rem;font-weight:800;margin-bottom:4px;}
    .page-sub{color:var(--text-muted);font-size:.85rem;margin-bottom:24px;}
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
    .stat-card{background:var(--surface);border-radius:14px;padding:20px;box-shadow:0 1px 8px rgba(0,0,0,.06);}
    .stat-ico{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:.95rem;margin-bottom:14px;}
    .stat-num{font-size:1.7rem;font-weight:800;line-height:1;}
    .stat-num-sm{font-size:1.1rem;font-weight:800;line-height:1;}
    .stat-lbl{font-size:.75rem;color:var(--text-muted);margin-top:5px;}
    .card-box{background:var(--surface);border-radius:14px;padding:22px;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:18px;}
    .card-box h5{font-weight:700;font-size:.95rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
    .terlaris-item{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);}
    .terlaris-item:last-child{border-bottom:none;}
    .tracking-banner{background:linear-gradient(135deg,#1e40af,#7c3aed);border-radius:14px;padding:22px 28px;color:white;display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;}
    .tracking-banner h5{font-weight:800;font-size:1.1rem;margin:0 0 4px;}
    .tracking-banner p{font-size:.85rem;opacity:.8;margin:0;}
    .btn-tracking{background:white;color:#1e40af;border:none;border-radius:9px;padding:10px 20px;font-weight:700;font-size:.85rem;cursor:pointer;text-decoration:none;}
    .btn-tracking:hover{background:#f0f4ff;color:#1e40af;}
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sb-brand">
        <div class="sb-logo">
            <div class="sb-icon">🏔️</div>
            <div>
                <div class="sb-title">WISATA</div>
                <div class="sb-sub">Admin Panel</div>
            </div>
        </div>
    </div>
    <div class="sb-menu">
        <div class="menu-section">Menu Utama</div>
        <a href="admin_dashboard.php" class="nav-item active">
            <div class="ni-icon"><i class="fas fa-chart-line"></i></div> Dashboard
        </a>
        <a href="kelola_wisata.php" class="nav-item">
            <div class="ni-icon"><i class="fas fa-mountain-sun"></i></div> Kelola Wisata
        </a>
        <a href="kelola_user.php" class="nav-item">
            <div class="ni-icon"><i class="fas fa-users"></i></div> Kelola User
        </a>
        <a href="laporan_pesanan.php" class="nav-item">
            <div class="ni-icon"><i class="fas fa-receipt"></i></div> Laporan Pesanan
        </a>
        <a href="tracking.php" class="nav-item">
            <div class="ni-icon"><i class="fas fa-chart-bar"></i></div> Tracking Pengunjung
        </a>
        <div class="menu-section">Akun</div>
        <a href="profil.php" class="nav-item">
            <div class="ni-icon"><i class="fas fa-user-circle"></i></div> Profil Saya
        </a>
    </div>
    <div class="sb-footer">
        <div class="admin-tag">
            <div class="admin-ava"><?= strtoupper(substr($_SESSION['user'],0,1)) ?></div>
            <div>
                <div class="admin-name"><?= htmlspecialchars($_SESSION['user']) ?></div>
                <div class="admin-role">Administrator</div>
            </div>
        </div>
        <a href="logout.php" class="nav-item logout">
            <div class="ni-icon"><i class="fas fa-power-off"></i></div> Logout
        </a>
    </div>
</aside>

<main class="main">
    <div class="page-title">Selamat Datang, <?= htmlspecialchars($_SESSION['user']) ?> 👋</div>
    <div class="page-sub">Ini ringkasan sistem monitoring wisata Anda hari ini</div>

    <!-- STAT CARDS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-ico" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-users"></i></div>
            <div class="stat-num"><?= $data_user['total'] ?></div>
            <div class="stat-lbl">Total Pengguna</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#d1fae5;color:#059669;"><i class="fas fa-ticket-alt"></i></div>
            <div class="stat-num"><?= $total_pesanan ?></div>
            <div class="stat-lbl">Total Pesanan</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#fef3c7;color:#d97706;"><i class="fas fa-wallet"></i></div>
            <div class="stat-num-sm">Rp<?= number_format($total_pendapatan,0,',','.') ?></div>
            <div class="stat-lbl">Total Pendapatan</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-eye"></i></div>
            <div class="stat-num"><?= $kunjungan_hari_ini ?></div>
            <div class="stat-lbl">Kunjungan Hari Ini</div>
        </div>
    </div>

    <!-- BANNER TRACKING -->
    <div class="tracking-banner">
        <div>
            <h5>📊 Fitur Tracking Pengunjung Aktif</h5>
            <p>Pantau grafik kunjungan harian, jam ramai, dan destinasi paling diminati secara real-time</p>
        </div>
        <a href="tracking.php" class="btn-tracking">Lihat Detail Tracking →</a>
    </div>

    <div class="row g-3">
        <!-- DESTINASI TERLARIS -->
        <div class="col-md-6">
            <div class="card-box">
                <h5><i class="fas fa-fire" style="color:#ef4444;"></i> Destinasi Terlaris</h5>
                <?php
                $no = 1;
                $medals = ['🥇','🥈','🥉'];
                while ($row = mysqli_fetch_assoc($q_terlaris)):
                ?>
                <div class="terlaris-item">
                    <span style="font-weight:600;"><?= $medals[$no-1] ?? $no ?> <?= htmlspecialchars($row['nama_wisata']) ?></span>
                    <span style="background:#dbeafe;color:#1e40af;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">
                        <?= $row['total'] ?> tiket
                    </span>
                </div>
                <?php $no++; endwhile; ?>
                <?php if ($no === 1): ?>
                    <p style="color:var(--text-muted);font-size:.85rem;text-align:center;padding:20px 0;">Belum ada pesanan.</p>
                <?php endif; ?>
                <a href="tracking.php" style="display:block;margin-top:14px;font-size:.82rem;color:var(--accent);text-decoration:none;font-weight:600;">
                    Lihat semua →
                </a>
            </div>
        </div>

        <!-- STATUS SISTEM -->
        <div class="col-md-6">
            <div class="card-box">
                <h5><i class="fas fa-server" style="color:#059669;"></i> Status Sistem</h5>
                <div class="terlaris-item">
                    <span>Database TiDB</span>
                    <span style="background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">✓ Online</span>
                </div>
                <div class="terlaris-item">
                    <span>Tracking Pengunjung</span>
                    <span style="background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">✓ Aktif</span>
                </div>
                <div class="terlaris-item">
                    <span>API BPS</span>
                    <span style="background:#fef3c7;color:#92400e;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">⏳ Eksternal</span>
                </div>
                <div class="terlaris-item">
                    <span>Session / Auth</span>
                    <span style="background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">✓ Aktif</span>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>