<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Statistik
$total_all      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users"))['t'];
$total_users    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='user'"))['t'];
$total_admin    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='admin'"))['t'];
$total_wisata   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM destinasi"))['t'];
$total_pesanan  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM pesanan"))['t'];
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(harga) as t FROM pesanan"))['t'] ?? 0;

// 5 pesanan terbaru
$pesanan_terbaru = mysqli_query($koneksi, "SELECT * FROM pesanan ORDER BY id_pesanan DESC LIMIT 5");

// Wisata terbaru
$wisata_terbaru = mysqli_query($koneksi, "SELECT * FROM destinasi ORDER BY id_wisata DESC LIMIT 4");

// Wisata buka vs tutup
$wisata_buka  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM destinasi WHERE status_buka=1"))['t'] ?? 0;
$wisata_tutup = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM destinasi WHERE status_buka=0"))['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin | WISATA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f0f4f8;font-family:'Segoe UI',sans-serif;display:flex;min-height:100vh;}

/* ── SIDEBAR ── */
.sidebar{width:240px;min-height:100vh;position:fixed;top:0;left:0;background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);display:flex;flex-direction:column;box-shadow:4px 0 20px rgba(0,0,0,0.15);z-index:100;}
.sidebar-brand{padding:24px 20px 18px;border-bottom:1px solid rgba(255,255,255,0.07);text-align:center;}
.brand-icon{width:52px;height:52px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.5rem;box-shadow:0 4px 14px rgba(59,130,246,0.45);}
.brand-title{color:white;font-weight:800;font-size:1.05rem;}
.brand-sub{color:rgba(255,255,255,0.35);font-size:0.7rem;}
.sidebar-menu{padding:16px 10px;flex:1;}
.menu-label{font-size:0.63rem;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.28);font-weight:700;padding:10px 12px 4px;}
.nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:rgba(255,255,255,0.5)!important;font-size:0.87rem;font-weight:500;transition:0.2s;text-decoration:none;margin-bottom:2px;}
.nav-link .ico{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.82rem;background:rgba(255,255,255,0.06);flex-shrink:0;transition:0.2s;}
.nav-link:hover{background:rgba(255,255,255,0.07);color:rgba(255,255,255,0.9)!important;}
.nav-link:hover .ico{background:rgba(255,255,255,0.12);}
.nav-link.active{background:linear-gradient(135deg,#3b82f6,#2563eb);color:white!important;box-shadow:0 4px 14px rgba(59,130,246,0.4);}
.nav-link.active .ico{background:rgba(255,255,255,0.2);}
.nav-link.logout{color:rgba(255,100,100,0.75)!important;}
.nav-link.logout:hover{background:rgba(239,68,68,0.12);color:#fca5a5!important;}
.nav-link.logout .ico{background:rgba(239,68,68,0.12);}
.sidebar-footer{padding:12px 10px;border-top:1px solid rgba(255,255,255,0.07);}
.admin-chip{display:flex;align-items:center;gap:10px;padding:10px 12px;background:rgba(255,255,255,0.04);border-radius:10px;margin-bottom:6px;}
.admin-ava{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;font-weight:700;color:white;font-size:0.9rem;flex-shrink:0;}
.admin-name{color:white;font-size:0.82rem;font-weight:600;}
.admin-role{color:rgba(255,255,255,0.35);font-size:0.68rem;}

/* ── KONTEN ── */
.content{margin-left:240px;padding:32px;flex:1;}
.page-title{font-size:1.6rem;font-weight:800;color:#0f172a;}
.page-sub{color:#94a3b8;font-size:0.9rem;margin-top:2px;margin-bottom:28px;}

/* STAT CARDS */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{border-radius:18px;padding:22px;transition:0.3s;position:relative;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);}
.stat-card:hover{transform:translateY(-4px);}
.stat-card::before{content:'';position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.08);}
.stat-ico{width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:14px;}
.stat-num{font-size:2rem;font-weight:800;}
.stat-lbl{font-size:0.78rem;opacity:0.75;margin-top:2px;}

/* SHORTCUT MENU */
.shortcut-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:28px;}
.shortcut-card{background:white;border-radius:16px;padding:22px;text-align:center;text-decoration:none;transition:0.3s;box-shadow:0 2px 10px rgba(0,0,0,0.06);border:2px solid transparent;}
.shortcut-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,0.12);border-color:#3b82f6;}
.shortcut-card .sc-icon{font-size:2rem;margin-bottom:10px;}
.shortcut-card .sc-label{font-weight:700;color:#0f172a;font-size:0.88rem;}
.shortcut-card .sc-sub{color:#94a3b8;font-size:0.75rem;margin-top:3px;}

/* CARDS */
.dash-card{background:white;border-radius:18px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.07);margin-bottom:22px;}
.dash-card-title{font-weight:700;color:#0f172a;font-size:1rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.dash-card-title i{color:#3b82f6;}

/* TABEL PESANAN */
.mini-table{width:100%;border-collapse:collapse;}
.mini-table th{font-size:0.72rem;text-transform:uppercase;color:#94a3b8;font-weight:700;padding:8px 12px;background:#f8fafc;letter-spacing:0.5px;}
.mini-table td{padding:10px 12px;border-bottom:1px solid #f1f5f9;font-size:0.85rem;color:#334155;}
.mini-table tr:last-child td{border:none;}
.mini-table tr:hover td{background:#f8fafc;}
.kode{font-weight:700;color:#3b82f6;}
.badge-lunas{background:#d1fae5;color:#065f46;font-weight:700;padding:3px 10px;border-radius:20px;font-size:0.72rem;}

/* WISATA MINI CARDS */
.wisata-mini-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;}
.wisata-mini{border-radius:12px;overflow:hidden;background:#f8fafc;border:1px solid #e2e8f0;transition:0.2s;}
.wisata-mini:hover{box-shadow:0 4px 14px rgba(0,0,0,0.1);}
.wisata-mini-foto{width:100%;height:90px;object-fit:cover;}
.wisata-mini-placeholder{width:100%;height:90px;display:flex;align-items:center;justify-content:center;font-size:2rem;}
.bg-pantai{background:linear-gradient(135deg,#0ea5e9,#0284c7);}
.bg-gunung{background:linear-gradient(135deg,#16a34a,#15803d);}
.bg-museum{background:linear-gradient(135deg,#d97706,#b45309);}
.bg-umum{background:linear-gradient(135deg,#7c3aed,#6d28d9);}
.wisata-mini-body{padding:10px 12px;}
.wisata-mini-nama{font-weight:700;font-size:0.82rem;color:#0f172a;margin-bottom:4px;}
.wisata-mini-harga{font-size:0.78rem;color:#16a34a;font-weight:600;}
.status-dot{display:inline-block;width:7px;height:7px;border-radius:50%;margin-right:4px;}
.dot-buka{background:#16a34a;}
.dot-tutup{background:#dc2626;}

/* STATUS BAR */
.status-bar{display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap;}
.status-item{background:white;border-radius:14px;padding:14px 20px;flex:1;min-width:140px;box-shadow:0 2px 10px rgba(0,0,0,0.06);display:flex;align-items:center;gap:12px;}
.status-item .si-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
.status-item .si-num{font-size:1.4rem;font-weight:800;color:#0f172a;}
.status-item .si-lbl{font-size:0.75rem;color:#94a3b8;}

/* WELCOME BANNER */
.welcome-banner{background:linear-gradient(135deg,#1d4ed8,#1e40af);border-radius:20px;padding:28px 32px;margin-bottom:28px;color:white;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;}
.welcome-banner h2{font-size:1.4rem;font-weight:800;margin-bottom:4px;}
.welcome-banner p{opacity:0.75;font-size:0.9rem;}
.welcome-banner .wb-icon{font-size:3.5rem;opacity:0.3;}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🏔️</div>
        <div class="brand-title">WISATA</div>
        <div class="brand-sub">Admin Panel</div>
    </div>
    <div class="sidebar-menu">
        <div class="menu-label">Menu Utama</div>
        <a href="admin_dashboard.php" class="nav-link active">
            <div class="ico"><i class="fas fa-chart-line"></i></div> Dashboard
        </a>
        <a href="kelola_wisata.php" class="nav-link">
            <div class="ico"><i class="fas fa-mountain-sun"></i></div> Kelola Wisata
        </a>
        <a href="kelola_user.php" class="nav-link">
            <div class="ico"><i class="fas fa-users"></i></div> Kelola User
        </a>
        <a href="laporan_pesanan.php" class="nav-link">
            <div class="ico"><i class="fas fa-receipt"></i></div> Laporan Pesanan
        </a>
        <div class="menu-label" style="margin-top:10px;">Akun</div>
        <a href="profil.php" class="nav-link">
            <div class="ico"><i class="fas fa-user-circle"></i></div> Profil Saya
        </a>
    </div>
    <div class="sidebar-footer">
        <div class="admin-chip">
            <div class="admin-ava"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
            <div>
                <div class="admin-name"><?= htmlspecialchars($_SESSION['user']) ?></div>
                <div class="admin-role">Administrator</div>
            </div>
        </div>
        <a href="logout.php" class="nav-link logout">
            <div class="ico"><i class="fas fa-power-off"></i></div> Logout
        </a>
    </div>
</div>

<!-- KONTEN -->
<div class="content">

    <!-- WELCOME BANNER -->
    <div class="welcome-banner">
        <div>
            <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['user']) ?>! 👋</h2>
            <p>Panel Admin — Sistem Monitoring Tiket Wisata</p>
            <small style="opacity:0.5;"><?= date('l, d F Y — H:i') ?> WIB</small>
        </div>
        <div class="wb-icon">🏔️</div>
    </div>

    <!-- STAT CARDS -->
    <div class="stat-grid">
        <div class="stat-card bg-primary text-white">
            <div class="stat-ico"><i class="fas fa-users"></i></div>
            <div class="stat-num"><?= $total_all ?></div>
            <div class="stat-lbl">Total Pengguna</div>
        </div>
        <div class="stat-card bg-success text-white">
            <div class="stat-ico"><i class="fas fa-mountain-sun"></i></div>
            <div class="stat-num"><?= $total_wisata ?></div>
            <div class="stat-lbl">Destinasi Wisata</div>
        </div>
        <div class="stat-card bg-warning text-dark">
            <div class="stat-ico" style="background:rgba(0,0,0,0.1);"><i class="fas fa-ticket-alt"></i></div>
            <div class="stat-num"><?= $total_pesanan ?></div>
            <div class="stat-lbl">Total Pesanan</div>
        </div>
        <div class="stat-card text-white" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
            <div class="stat-ico"><i class="fas fa-wallet"></i></div>
            <div class="stat-num" style="font-size:1.3rem;">Rp<?= number_format($total_pendapatan,0,',','.') ?></div>
            <div class="stat-lbl">Total Pendapatan</div>
        </div>
        <div class="stat-card text-white" style="background:linear-gradient(135deg,#0891b2,#0e7490);">
            <div class="stat-ico"><i class="fas fa-user-tie"></i></div>
            <div class="stat-num"><?= $total_admin ?></div>
            <div class="stat-lbl">Petugas/Admin</div>
        </div>
        <div class="stat-card text-white" style="background:linear-gradient(135deg,#ea580c,#c2410c);">
            <div class="stat-ico"><i class="fas fa-user"></i></div>
            <div class="stat-num"><?= $total_users ?></div>
            <div class="stat-lbl">Pengunjung</div>
        </div>
    </div>

    <!-- STATUS WISATA -->
    <div class="status-bar">
        <div class="status-item">
            <div class="si-icon" style="background:#d1fae5;color:#16a34a;"><i class="fas fa-door-open"></i></div>
            <div>
                <div class="si-num"><?= $wisata_buka ?></div>
                <div class="si-lbl">Wisata Buka</div>
            </div>
        </div>
        <div class="status-item">
            <div class="si-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-door-closed"></i></div>
            <div>
                <div class="si-num"><?= $wisata_tutup ?></div>
                <div class="si-lbl">Wisata Tutup</div>
            </div>
        </div>
        <div class="status-item">
            <div class="si-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-server"></i></div>
            <div>
                <div class="si-num" style="font-size:1rem;color:#16a34a;">Online</div>
                <div class="si-lbl">Status Server</div>
            </div>
        </div>
        <div class="status-item">
            <div class="si-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-calendar-day"></i></div>
            <div>
                <div class="si-num" style="font-size:1rem;"><?= date('d M Y') ?></div>
                <div class="si-lbl">Hari Ini</div>
            </div>
        </div>
    </div>

    <!-- SHORTCUT MENU -->
    <div class="dash-card">
        <div class="dash-card-title"><i class="fas fa-th"></i> Menu Cepat</div>
        <div class="shortcut-grid">
            <a href="kelola_wisata.php" class="shortcut-card">
                <div class="sc-icon">🏝️</div>
                <div class="sc-label">Kelola Wisata</div>
                <div class="sc-sub">Tambah & edit destinasi</div>
            </a>
            <a href="kelola_user.php" class="shortcut-card">
                <div class="sc-icon">👥</div>
                <div class="sc-label">Kelola User</div>
                <div class="sc-sub">Manage akun pengguna</div>
            </a>
            <a href="laporan_pesanan.php" class="shortcut-card">
                <div class="sc-icon">📋</div>
                <div class="sc-label">Laporan Pesanan</div>
                <div class="sc-sub">Lihat semua transaksi</div>
            </a>
            <a href="profil.php" class="shortcut-card">
                <div class="sc-icon">👤</div>
                <div class="sc-label">Profil Saya</div>
                <div class="sc-sub">Ganti password akun</div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- PESANAN TERBARU -->
        <div class="col-md-7">
            <div class="dash-card">
                <div class="dash-card-title"><i class="fas fa-receipt"></i> 5 Pesanan Terbaru</div>
                <?php if ($pesanan_terbaru && mysqli_num_rows($pesanan_terbaru) > 0): ?>
                <div class="table-responsive">
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>User</th>
                                <th>Wisata</th>
                                <th>Harga</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($p = mysqli_fetch_assoc($pesanan_terbaru)): ?>
                            <tr>
                                <td><span class="kode">#WDT-<?= $p['id_pesanan'] ?></span></td>
                                <td><?= htmlspecialchars($p['username']) ?></td>
                                <td><?= htmlspecialchars(mb_substr($p['nama_wisata'],0,15)) ?>...</td>
                                <td class="text-success fw-bold">Rp<?= number_format($p['harga'],0,',','.') ?></td>
                                <td><span class="badge-lunas">✓ Lunas</span></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <a href="laporan_pesanan.php" style="font-size:0.82rem;color:#3b82f6;text-decoration:none;font-weight:600;display:block;margin-top:12px;">
                    Lihat semua pesanan →
                </a>
                <?php else: ?>
                <p class="text-muted text-center py-3" style="font-size:0.85rem;">Belum ada pesanan masuk.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- DESTINASI TERBARU -->
        <div class="col-md-5">
            <div class="dash-card">
                <div class="dash-card-title"><i class="fas fa-mountain-sun"></i> Destinasi Terbaru</div>
                <?php if ($wisata_terbaru && mysqli_num_rows($wisata_terbaru) > 0): ?>
                <div class="wisata-mini-grid">
                <?php while($w = mysqli_fetch_assoc($wisata_terbaru)):
                    $jenis = $w['jenis_wisata'] ?? 'umum';
                    $icon  = match($jenis) {'pantai'=>'🏖️','gunung'=>'🏔️','museum'=>'🏛️',default=>'🏞️'};
                ?>
                    <div class="wisata-mini">
                        <?php if (!empty($w['foto_url'])): ?>
                            <img src="<?= htmlspecialchars($w['foto_url']) ?>" class="wisata-mini-foto"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <div class="wisata-mini-placeholder bg-<?= $jenis ?>" style="display:none;"><?= $icon ?></div>
                        <?php else: ?>
                            <div class="wisata-mini-placeholder bg-<?= $jenis ?>"><?= $icon ?></div>
                        <?php endif; ?>
                        <div class="wisata-mini-body">
                            <div class="wisata-mini-nama"><?= htmlspecialchars($w['nama_wisata']) ?></div>
                            <div class="wisata-mini-harga">Rp<?= number_format($w['harga'],0,',','.') ?></div>
                            <div style="font-size:0.72rem;margin-top:4px;">
                                <span class="status-dot <?= $w['status_buka'] ? 'dot-buka' : 'dot-tutup' ?>"></span>
                                <?= $w['status_buka'] ? 'Buka' : 'Tutup' ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
                <a href="kelola_wisata.php" style="font-size:0.82rem;color:#3b82f6;text-decoration:none;font-weight:600;display:block;margin-top:12px;">
                    Kelola semua destinasi →
                </a>
                <?php else: ?>
                <p class="text-muted text-center py-3" style="font-size:0.85rem;">Belum ada destinasi.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

</body>
</html>
<?php ob_end_flush(); ?>