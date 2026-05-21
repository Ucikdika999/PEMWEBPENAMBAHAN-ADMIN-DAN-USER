<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /api/login.php");
    exit();
}

// Statistik
$total_all        = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users"))['t'];
$total_users      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='user'"))['t'];
$total_admin      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='admin'"))['t'];
$total_wisata     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM destinasi"))['t'];
$total_pesanan    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM pesanan"))['t'];
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(harga) as t FROM pesanan"))['t'] ?? 0;
$wisata_buka      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM destinasi WHERE status_buka=1"))['t'] ?? 0;
$wisata_tutup     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM destinasi WHERE status_buka=0"))['t'] ?? 0;

// Pesanan terbaru
$pesanan_terbaru = mysqli_query($koneksi, "SELECT * FROM pesanan ORDER BY id_pesanan DESC LIMIT 5");

// Grafik 7 hari - pesanan & pendapatan
$grafik_pesanan = []; $grafik_pendapatan = []; $grafik_label = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $grafik_label[] = date('d M', strtotime("-$i days"));
    $r1 = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM pesanan WHERE DATE(tanggal_pesan)='$tgl'"));
    $r2 = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(harga),0) as t FROM pesanan WHERE DATE(tanggal_pesan)='$tgl'"));
    $grafik_pesanan[]    = (int)($r1['t'] ?? 0);
    $grafik_pendapatan[] = (int)($r2['t'] ?? 0);
}

// Kategori destinasi (jenis_wisata)
$kategori_res = mysqli_query($koneksi, "SELECT jenis_wisata, COUNT(*) as jml FROM destinasi GROUP BY jenis_wisata");
$kategori_data = [];
while ($kr = mysqli_fetch_assoc($kategori_res)) {
    $kategori_data[$kr['jenis_wisata'] ?? 'umum'] = (int)$kr['jml'];
}
$total_kat = array_sum($kategori_data) ?: 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin | WISATA</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f4f6fb;display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:210px;min-height:100vh;position:fixed;top:0;left:0;
    background:linear-gradient(180deg,#0d1f0d 0%,#1a3a1a 60%,#0d1f0d 100%);
    display:flex;flex-direction:column;z-index:100;overflow:hidden;}
.sidebar::after{content:'';position:absolute;bottom:0;left:0;right:0;height:220px;
    background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 210 220'%3E%3Cpath d='M0 180 L30 120 L60 150 L90 90 L120 130 L150 70 L180 110 L210 80 L210 220 L0 220Z' fill='%23162e16' opacity='0.8'/%3E%3Cpath d='M0 200 L40 160 L70 180 L100 140 L130 165 L160 130 L190 155 L210 135 L210 220 L0 220Z' fill='%231a3a1a' opacity='0.9'/%3E%3C/svg%3E") bottom center/cover no-repeat;
    pointer-events:none;z-index:0;}
.sidebar-content{position:relative;z-index:1;display:flex;flex-direction:column;height:100%;}
.sidebar-brand{padding:22px 18px 16px;border-bottom:1px solid rgba(255,255,255,0.08);}
.brand-wrap{display:flex;align-items:center;gap:10px;}
.brand-ico{width:38px;height:38px;background:white;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;}
.brand-name{color:white;font-weight:800;font-size:1rem;line-height:1;}
.brand-sub{color:rgba(255,255,255,0.4);font-size:0.68rem;margin-top:2px;}
.sidebar-menu{padding:14px 10px;flex:1;}
.menu-label{font-size:0.6rem;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.25);font-weight:700;padding:10px 10px 4px;}
.nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;
    color:rgba(255,255,255,0.5)!important;font-size:0.85rem;font-weight:500;
    transition:0.2s;text-decoration:none;margin-bottom:2px;}
.nav-link .ico{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;
    justify-content:center;font-size:0.8rem;background:rgba(255,255,255,0.06);flex-shrink:0;}
.nav-link:hover{background:rgba(255,255,255,0.08);color:white!important;}
.nav-link.active{background:rgba(255,255,255,0.15);color:white!important;border-left:3px solid #4ade80;}
.nav-link.active .ico{background:rgba(74,222,128,0.2);}
.nav-link.logout{color:rgba(255,120,120,0.7)!important;}
.nav-link.logout:hover{background:rgba(239,68,68,0.1);color:#fca5a5!important;}
.sidebar-footer{padding:12px 10px;border-top:1px solid rgba(255,255,255,0.07);position:relative;z-index:1;}
.admin-chip{display:flex;align-items:center;gap:10px;padding:10px 12px;
    background:rgba(255,255,255,0.06);border-radius:10px;margin-bottom:6px;cursor:pointer;}
.admin-ava{width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#4ade80,#16a34a);
    display:flex;align-items:center;justify-content:center;font-weight:800;color:white;font-size:0.85rem;flex-shrink:0;}
.admin-name{color:white;font-size:0.8rem;font-weight:700;}
.admin-role{color:rgba(255,255,255,0.35);font-size:0.65rem;}

/* MAIN CONTENT */
.content{margin-left:210px;flex:1;padding:0;}

/* TOP BAR */
.topbar{background:white;padding:16px 28px;display:flex;justify-content:space-between;align-items:center;
    border-bottom:1px solid #e8ecf0;box-shadow:0 1px 8px rgba(0,0,0,0.05);}
.topbar-left h2{font-size:1.3rem;font-weight:800;color:#0f172a;}
.topbar-left p{color:#94a3b8;font-size:0.82rem;margin-top:2px;}
.topbar-right{display:flex;align-items:center;gap:20px;}
.topbar-date{display:flex;align-items:center;gap:8px;}
.topbar-date .ico{width:34px;height:34px;background:#f0f4f8;border-radius:9px;
    display:flex;align-items:center;justify-content:center;color:#64748b;font-size:0.85rem;}
.topbar-date .val{font-weight:700;font-size:0.88rem;color:#0f172a;}
.topbar-date .sub{font-size:0.72rem;color:#94a3b8;}
.status-online{display:flex;align-items:center;gap:8px;}
.dot-online{width:8px;height:8px;border-radius:50%;background:#16a34a;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.4;}}
.status-online .val{font-weight:700;color:#16a34a;font-size:0.88rem;}
.status-online .sub{font-size:0.72rem;color:#94a3b8;}

/* MAIN WRAP */
.main{padding:24px 28px;}

/* STAT CARDS ROW 1 */
.stat-row{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:16px;}
.stat-card{background:white;border-radius:16px;padding:18px 20px;
    box-shadow:0 2px 10px rgba(0,0,0,0.06);transition:0.3s;position:relative;overflow:hidden;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,0.1);}
.stat-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;}
.stat-ico{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;}
.stat-trend{font-size:0.7rem;font-weight:700;color:#16a34a;display:flex;align-items:center;gap:3px;}
.stat-num{font-size:1.7rem;font-weight:900;color:#0f172a;line-height:1;}
.stat-lbl{font-size:0.75rem;color:#94a3b8;margin-top:4px;}
.stat-trend-text{font-size:0.7rem;color:#16a34a;font-weight:600;margin-top:6px;}

/* STAT ROW 2 (status bar) */
.status-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;}
.status-card{background:white;border-radius:14px;padding:16px 18px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);display:flex;align-items:center;gap:12px;}
.si-ico{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:0.9rem;flex-shrink:0;}
.si-num{font-size:1.4rem;font-weight:800;color:#0f172a;}
.si-lbl{font-size:0.72rem;color:#94a3b8;margin-top:1px;}

/* CHART ROW */
.chart-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px;}
.chart-card{background:white;border-radius:16px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.06);}
.chart-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.chart-title{font-weight:700;font-size:0.9rem;color:#0f172a;display:flex;align-items:center;gap:6px;}
.chart-title i{color:#16a34a;}
.chart-filter{background:#f0f4f8;border:none;border-radius:8px;padding:5px 10px;
    font-size:0.75rem;font-weight:600;color:#64748b;cursor:pointer;}

/* DONUT CHART */
.donut-wrap{display:flex;align-items:center;gap:16px;}
.donut-svg{flex-shrink:0;}
.donut-legend{flex:1;}
.legend-item{display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:0.78rem;}
.legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
.legend-name{color:#64748b;flex:1;}
.legend-val{font-weight:700;color:#0f172a;}
.legend-pct{color:#94a3b8;font-size:0.7rem;}

/* BOTTOM ROW */
.bottom-row{display:grid;grid-template-columns:1fr 380px;gap:16px;}

/* TABEL */
.table-card{background:white;border-radius:16px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.06);}
.table-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.table-title{font-weight:700;font-size:0.9rem;color:#0f172a;display:flex;align-items:center;gap:6px;}
.table-title i{color:#16a34a;}
.tbl{width:100%;border-collapse:collapse;}
.tbl th{font-size:0.68rem;text-transform:uppercase;color:#94a3b8;font-weight:700;
    padding:8px 10px;background:#f8fafc;letter-spacing:0.5px;text-align:left;}
.tbl td{padding:10px;border-bottom:1px solid #f1f5f9;font-size:0.82rem;color:#334155;}
.tbl tr:last-child td{border:none;}
.tbl tr:hover td{background:#f8fafc;}
.kode-badge{font-weight:700;color:#0f172a;font-family:monospace;}
.badge-selesai{background:#d1fae5;color:#065f46;font-weight:700;padding:3px 10px;border-radius:20px;font-size:0.7rem;}
.badge-menunggu{background:#fef3c7;color:#b45309;font-weight:700;padding:3px 10px;border-radius:20px;font-size:0.7rem;}
.see-all{font-size:0.8rem;color:#16a34a;text-decoration:none;font-weight:700;display:flex;align-items:center;gap:4px;margin-top:14px;}
.see-all:hover{color:#15803d;}

/* MENU CEPAT */
.menu-card{background:white;border-radius:16px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.06);}
.menu-title{font-weight:700;font-size:0.9rem;color:#0f172a;display:flex;align-items:center;gap:6px;margin-bottom:16px;}
.menu-title i{color:#16a34a;}
.menu-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.menu-item{display:flex;align-items:center;gap:10px;padding:14px;border-radius:12px;
    background:#f8fafc;text-decoration:none;transition:0.2s;border:1.5px solid transparent;}
.menu-item:hover{background:#f0fdf4;border-color:#bbf7d0;}
.menu-item-ico{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;
    justify-content:center;font-size:1.1rem;flex-shrink:0;}
.menu-item-name{font-weight:700;font-size:0.82rem;color:#0f172a;}
.menu-item-sub{font-size:0.7rem;color:#94a3b8;margin-top:1px;}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
<div class="sidebar-content">
    <div class="sidebar-brand">
        <div class="brand-wrap">
            <div class="brand-ico">🏔️</div>
            <div>
                <div class="brand-name">WISATA</div>
                <div class="brand-sub">Admin Panel</div>
            </div>
        </div>
    </div>
    <div class="sidebar-menu">
        <div class="menu-label">Menu Utama</div>
        <a href="admin_dashboard.php" class="nav-link active">
            <div class="ico"><i class="fas fa-home"></i></div> Dashboard
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
        <div class="menu-label" style="margin-top:8px;">Akun</div>
        <a href="profil.php" class="nav-link">
            <div class="ico"><i class="fas fa-user-circle"></i></div> Profil Saya
        </a>
    </div>
    <div class="sidebar-footer">
        <div class="admin-chip">
            <div class="admin-ava"><?= strtoupper(substr($_SESSION['user'],0,1)) ?></div>
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
</div>

<!-- CONTENT -->
<div class="content">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['user']) ?>! 👋</h2>
            <p>Panel Admin — Sistem Monitoring Tiket Wisata</p>
        </div>
        <div class="topbar-right">
            <div class="topbar-date">
                <div class="ico"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <div class="val"><?= date('d M Y') ?></div>
                    <div class="sub"><?= date('l, H:i') ?> WIB</div>
                </div>
            </div>
            <div class="status-online">
                <div class="dot-online"></div>
                <div>
                    <div class="val">Online</div>
                    <div class="sub">Status Server</div>
                </div>
            </div>
        </div>
    </div>

    <div class="main">

        <!-- STAT ROW 1 -->
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-ico" style="background:#e8f5e9;color:#16a34a;"><i class="fas fa-users"></i></div>
                    <span class="stat-trend"><i class="fas fa-arrow-up"></i> 12.5%</span>
                </div>
                <div class="stat-num"><?= $total_all ?></div>
                <div class="stat-lbl">Total Pengguna</div>
                <div class="stat-trend-text">↑ dari bulan lalu</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-ico" style="background:#fef3c7;color:#b45309;"><i class="fas fa-mountain-sun"></i></div>
                    <span class="stat-trend"><i class="fas fa-arrow-up"></i> 8.3%</span>
                </div>
                <div class="stat-num"><?= $total_wisata ?></div>
                <div class="stat-lbl">Destinasi Wisata</div>
                <div class="stat-trend-text">↑ dari bulan lalu</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-ico" style="background:#fce8e6;color:#c62828;"><i class="fas fa-ticket-alt"></i></div>
                    <span class="stat-trend"><i class="fas fa-arrow-up"></i> 25%</span>
                </div>
                <div class="stat-num"><?= $total_pesanan ?></div>
                <div class="stat-lbl">Total Pesanan</div>
                <div class="stat-trend-text">↑ dari bulan lalu</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-ico" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-wallet"></i></div>
                    <span class="stat-trend"><i class="fas fa-arrow-up"></i> 18.6%</span>
                </div>
                <div class="stat-num" style="font-size:1.1rem;">Rp<?= number_format($total_pendapatan,0,',','.') ?></div>
                <div class="stat-lbl">Total Pendapatan</div>
                <div class="stat-trend-text">↑ dari bulan lalu</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-ico" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-user"></i></div>
                    <span class="stat-trend"><i class="fas fa-arrow-up"></i> 15.2%</span>
                </div>
                <div class="stat-num"><?= $total_users ?></div>
                <div class="stat-lbl">Pengunjung</div>
                <div class="stat-trend-text">↑ dari minggu lalu</div>
            </div>
        </div>

        <!-- STATUS ROW -->
        <div class="status-row">
            <div class="status-card">
                <div class="si-ico" style="background:#e8f5e9;color:#16a34a;"><i class="fas fa-door-open"></i></div>
                <div>
                    <div class="si-num"><?= $wisata_buka ?></div>
                    <div class="si-lbl">Wisata Buka</div>
                </div>
            </div>
            <div class="status-card">
                <div class="si-ico" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-door-closed"></i></div>
                <div>
                    <div class="si-num"><?= $wisata_tutup ?></div>
                    <div class="si-lbl">Wisata Tutup</div>
                </div>
            </div>
            <div class="status-card">
                <div class="si-ico" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-server"></i></div>
                <div>
                    <div class="si-num" style="font-size:1rem;color:#16a34a;">Online</div>
                    <div class="si-lbl">Status Server</div>
                </div>
            </div>
            <div class="status-card">
                <div class="si-ico" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-calendar-day"></i></div>
                <div>
                    <div class="si-num" style="font-size:0.95rem;"><?= date('d M Y') ?></div>
                    <div class="si-lbl">Hari Ini</div>
                </div>
            </div>
        </div>

        <!-- CHART ROW -->
        <div class="chart-row">

            <!-- GRAFIK GARIS PENGUNJUNG -->
            <div class="chart-card">
                <div class="chart-head">
                    <div class="chart-title"><i class="fas fa-chart-line"></i> Grafik Pengunjung (7 Hari)</div>
                    <span class="chart-filter">7 Hari Terakhir ▾</span>
                </div>
                <?php
                $max_p = max(array_merge($grafik_pesanan,[1]));
                $sw=380; $sh=160; $pl=30; $pb=25; $pt=10; $pr=10;
                $cw=$sw-$pl-$pr; $ch=$sh-$pb-$pt; $n=count($grafik_pesanan);
                $step=$cw/($n-1);
                $pts=[];
                for($i=0;$i<$n;$i++){
                    $x=$pl+$i*$step;
                    $y=$pt+$ch-($grafik_pesanan[$i]/max($max_p,1))*$ch;
                    $pts[]="$x,$y";
                }
                $poly=implode(' ',$pts);
                list($fx,$fy)=explode(',',$pts[0]);
                list($lx,$ly)=explode(',',$pts[$n-1]);
                $area=$poly." $lx,".($pt+$ch)." $fx,".($pt+$ch);
                ?>
                <svg viewBox="0 0 <?=$sw?> <?=$sh?>" style="width:100%;height:170px;">
                    <defs>
                        <linearGradient id="g1" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#16a34a" stop-opacity="0.25"/>
                            <stop offset="100%" stop-color="#16a34a" stop-opacity="0.02"/>
                        </linearGradient>
                    </defs>
                    <?php for($g=0;$g<=4;$g++): $gy=$pt+($ch/4)*$g; $gv=round($max_p-($max_p/4)*$g); ?>
                    <line x1="<?=$pl?>" y1="<?=$gy?>" x2="<?=$sw-$pr?>" y2="<?=$gy?>" stroke="#f0f0f0" stroke-width="1"/>
                    <text x="<?=$pl-4?>" y="<?=$gy+4?>" text-anchor="end" font-size="9" fill="#94a3b8"><?=$gv?></text>
                    <?php endfor; ?>
                    <polygon points="<?=$area?>" fill="url(#g1)"/>
                    <polyline points="<?=$poly?>" fill="none" stroke="#16a34a" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                    <?php for($i=0;$i<$n;$i++): list($px,$py)=explode(',',$pts[$i]); ?>
                    <circle cx="<?=$px?>" cy="<?=$py?>" r="3.5" fill="#16a34a" stroke="white" stroke-width="1.5"/>
                    <?php if($grafik_pesanan[$i]>0): ?>
                    <text x="<?=$px?>" y="<?=$py-8?>" text-anchor="middle" font-size="9" font-weight="700" fill="#16a34a"><?=$grafik_pesanan[$i]?></text>
                    <?php endif; ?>
                    <text x="<?=$px?>" y="<?=$pt+$ch+18?>" text-anchor="middle" font-size="9" fill="#94a3b8"><?=$grafik_label[$i]?></text>
                    <?php endfor; ?>
                </svg>
            </div>

            <!-- DONUT KATEGORI DESTINASI -->
            <div class="chart-card">
                <div class="chart-head">
                    <div class="chart-title"><i class="fas fa-chart-pie"></i> Kategori Destinasi</div>
                </div>
                <?php
                $colors = ['pantai'=>'#3b82f6','gunung'=>'#16a34a','museum'=>'#f59e0b','umum'=>'#8b5cf6'];
                $labels = ['pantai'=>'Pantai','gunung'=>'Gunung','museum'=>'Museum','umum'=>'Umum'];
                // Buat donut SVG
                $radius=50; $cx=65; $cy=65; $stroke=22;
                $circ=2*M_PI*$radius;
                $offset=0; $donut_parts=[];
                foreach($colors as $key=>$color){
                    $val=$kategori_data[$key]??0;
                    $pct=$val/$total_kat;
                    $dash=$pct*$circ;
                    $donut_parts[]=[$dash,$circ-$dash,$offset,$color,$key,$val];
                    $offset+=$dash;
                }
                ?>
                <div class="donut-wrap">
                    <svg width="130" height="130" viewBox="0 0 130 130" class="donut-svg">
                        <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$radius?>" fill="none" stroke="#f1f5f9" stroke-width="<?=$stroke?>"/>
                        <?php foreach($donut_parts as $dp): ?>
                        <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$radius?>" fill="none"
                            stroke="<?=$dp[3]?>" stroke-width="<?=$stroke?>"
                            stroke-dasharray="<?=$dp[0]?> <?=$dp[1]?>"
                            stroke-dashoffset="-<?=$dp[2]?>"
                            transform="rotate(-90 <?=$cx?> <?=$cy?>)"/>
                        <?php endforeach; ?>
                        <text x="<?=$cx?>" y="<?=$cy+4?>" text-anchor="middle" font-size="16" font-weight="800" fill="#0f172a"><?=$total_wisata?></text>
                        <text x="<?=$cx?>" y="<?=$cy+16?>" text-anchor="middle" font-size="8" fill="#94a3b8">Total</text>
                    </svg>
                    <div class="donut-legend">
                        <?php foreach($colors as $key=>$color):
                            $val=$kategori_data[$key]??0;
                            $pct=$total_kat>0?round($val/$total_kat*100):0;
                        ?>
                        <div class="legend-item">
                            <div class="legend-dot" style="background:<?=$color?>;"></div>
                            <span class="legend-name"><?=$labels[$key]?></span>
                            <span class="legend-val"><?=$val?></span>
                            <span class="legend-pct">(<?=$pct?>%)</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- GRAFIK BAR PENDAPATAN -->
            <div class="chart-card">
                <div class="chart-head">
                    <div class="chart-title"><i class="fas fa-chart-bar"></i> Penjualan Tiket (7 Hari)</div>
                    <span class="chart-filter">7 Hari Terakhir ▾</span>
                </div>
                <?php
                $max_d=max(array_merge($grafik_pendapatan,[1]));
                $bsw=320; $bsh=160; $bpl=36; $bpb=25; $bpt=10; $bpr=10;
                $bcw=$bsw-$bpl-$bpr; $bch=$bsh-$bpb-$bpt;
                $bar_w=$bcw/$n*0.55; $bar_gap=$bcw/$n;
                ?>
                <svg viewBox="0 0 <?=$bsw?> <?=$bsh?>" style="width:100%;height:170px;">
                    <?php for($g=0;$g<=4;$g++):
                        $gy=$bpt+($bch/4)*$g;
                        $gv=round(($max_d-($max_d/4)*$g)/1000);
                    ?>
                    <line x1="<?=$bpl?>" y1="<?=$gy?>" x2="<?=$bsw-$bpr?>" y2="<?=$gy?>" stroke="#f0f0f0" stroke-width="1"/>
                    <text x="<?=$bpl-4?>" y="<?=$gy+4?>" text-anchor="end" font-size="8" fill="#94a3b8"><?=$gv?>K</text>
                    <?php endfor; ?>
                    <?php for($i=0;$i<$n;$i++):
                        $bx=$bpl+$i*$bar_gap+($bar_gap-$bar_w)/2;
                        $bh_real=($grafik_pendapatan[$i]/max($max_d,1))*$bch;
                        $by=$bpt+$bch-$bh_real;
                        $lbl_d=$grafik_pendapatan[$i]>0?round($grafik_pendapatan[$i]/1000).'K':0;
                    ?>
                    <rect x="<?=$bx?>" y="<?=$by?>" width="<?=$bar_w?>" height="<?=$bh_real?>"
                          rx="4" fill="#16a34a" opacity="0.85"/>
                    <?php if($grafik_pendapatan[$i]>0): ?>
                    <text x="<?=$bx+$bar_w/2?>" y="<?=$by-5?>" text-anchor="middle" font-size="8" font-weight="700" fill="#16a34a"><?=$lbl_d?></text>
                    <?php endif; ?>
                    <text x="<?=$bx+$bar_w/2?>" y="<?=$bpt+$bch+18?>" text-anchor="middle" font-size="8" fill="#94a3b8"><?=$grafik_label[$i]?></text>
                    <?php endfor; ?>
                </svg>
            </div>
        </div>

        <!-- BOTTOM ROW -->
        <div class="bottom-row">

            <!-- TABEL PESANAN -->
            <div class="table-card">
                <div class="table-head">
                    <div class="table-title"><i class="fas fa-receipt"></i> 5 Pesanan Terbaru</div>
                </div>
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Kode</th><th>User</th><th>Wisata</th>
                            <th>Tanggal</th><th>Harga</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if($pesanan_terbaru && mysqli_num_rows($pesanan_terbaru)>0):
                        $no=0;
                        while($p=mysqli_fetch_assoc($pesanan_terbaru)):
                            $no++;
                            $status_badge = ($no==4) ? '<span class="badge-menunggu">⏳ Menunggu</span>' : '<span class="badge-selesai">✓ Selesai</span>';
                    ?>
                    <tr>
                        <td><span class="kode-badge">#WDT-<?= str_pad($p['id_pesanan'],4,'0',STR_PAD_LEFT) ?></span></td>
                        <td><?= htmlspecialchars($p['username']) ?></td>
                        <td><?= htmlspecialchars(mb_substr($p['nama_wisata'],0,18)) ?></td>
                        <td style="color:#94a3b8;font-size:0.78rem;">
                            <?= !empty($p['tanggal_pesan']) ? date('d M Y', strtotime($p['tanggal_pesan'])) : '-' ?>
                        </td>
                        <td style="font-weight:700;color:#0f172a;">Rp<?= number_format($p['harga'],0,',','.') ?></td>
                        <td><?= $status_badge ?></td>
                    </tr>
                    <?php endwhile;
                    else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">Belum ada pesanan.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <a href="laporan_pesanan.php" class="see-all">Lihat Semua Pesanan <i class="fas fa-arrow-right"></i></a>
            </div>

            <!-- MENU CEPAT -->
            <div class="menu-card">
                <div class="menu-title"><i class="fas fa-th"></i> Menu Cepat</div>
                <div class="menu-grid">
                    <a href="kelola_wisata.php" class="menu-item">
                        <div class="menu-item-ico" style="background:#e8f5e9;">🏝️</div>
                        <div>
                            <div class="menu-item-name">Kelola Wisata</div>
                            <div class="menu-item-sub">Tambah & edit destinasi</div>
                        </div>
                    </a>
                    <a href="kelola_user.php" class="menu-item">
                        <div class="menu-item-ico" style="background:#dbeafe;">👥</div>
                        <div>
                            <div class="menu-item-name">Kelola User</div>
                            <div class="menu-item-sub">Manage akun pengguna</div>
                        </div>
                    </a>
                    <a href="laporan_pesanan.php" class="menu-item">
                        <div class="menu-item-ico" style="background:#fef3c7;">📋</div>
                        <div>
                            <div class="menu-item-name">Laporan Pesanan</div>
                            <div class="menu-item-sub">Lihat semua transaksi</div>
                        </div>
                    </a>
                    <a href="profil.php" class="menu-item">
                        <div class="menu-item-ico" style="background:#f3e8ff;">👤</div>
                        <div>
                            <div class="menu-item-name">Profil Saya</div>
                            <div class="menu-item-sub">Ganti password akun</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>
<?php ob_end_flush(); ?>