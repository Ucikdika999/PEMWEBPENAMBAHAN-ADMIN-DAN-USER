<?php
ob_start();
include "auth_check.php";
include "koneksi.php";
// Catat kunjungan
$_hal = basename($_SERVER['PHP_SELF']);
$_usr = isset($_SESSION['user']) ? mysqli_real_escape_string($koneksi, $_SESSION['user']) : 'tamu';
$_ip  = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
mysqli_query($koneksi, "INSERT INTO log_kunjungan (halaman, username, ip_address) VALUES ('$_hal', '$_usr', '$_ip')");

if ($_SESSION['role'] !== 'user') {
    header("Location: /api/admin_dashboard.php");
    exit();
}

// Ambil data BPS
function ambilDataBPS() {
    $url = "https://webapi.bps.go.id/v1/api/list/model/data/lang/ind/domain/0000/var/1470/th/126/key/279bfd3333f47740fe54cd482719d5f6";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

$data_bps  = ambilDataBPS();
$nilai_bps = ($data_bps && isset($data_bps['status']) && $data_bps['status'] == 'OK') ? $data_bps['subject'][0]['val'] : "0";
$label_bps = ($data_bps && isset($data_bps['status']) && $data_bps['status'] == 'OK') ? $data_bps['subject'][0]['label'] : "Data Pariwisata";

// Statistik dari database
$total_destinasi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM destinasi"))['t'] ?? 0;
$total_pesanan_user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM pesanan WHERE username='" . mysqli_real_escape_string($koneksi, $_SESSION['user']) . "'"))['t'] ?? 0;

// Destinasi terbaru (list)
$query_wisata = mysqli_query($koneksi, "SELECT * FROM destinasi WHERE status_buka=1 ORDER BY id_wisata DESC");

// Grafik 7 hari: pakai data pesanan per hari
$grafik_data = [];
$grafik_label = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $tgl_label = date('d M', strtotime("-$i days"));
    $res = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM pesanan WHERE DATE(tanggal_pesan)='$tgl'"));
    $grafik_data[] = $res['t'] ?? 0;
    $grafik_label[] = $tgl_label;
}
$max_grafik = max(array_merge($grafik_data, [1]));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Pengunjung | WISATA</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f4f6fb;color:#1a1a2e;}

/* NAVBAR */
.navbar{
    background:linear-gradient(135deg,#1a2e1a,#2d4a2d);
    padding:14px 32px;display:flex;justify-content:space-between;align-items:center;
    box-shadow:0 2px 16px rgba(0,0,0,0.15);
}
.navbar-brand{display:flex;align-items:center;gap:10px;color:white;font-size:1.2rem;font-weight:800;}
.navbar-brand .leaf{font-size:1.4rem;}
.nav-links{display:flex;gap:8px;}
.nav-btn{
    display:flex;align-items:center;gap:6px;
    background:rgba(255,255,255,0.12);color:white;
    border:none;padding:8px 16px;border-radius:8px;
    cursor:pointer;text-decoration:none;font-size:0.85rem;font-weight:600;
    transition:0.2s;
}
.nav-btn:hover{background:rgba(255,255,255,0.22);color:white;}
.nav-btn.danger{background:rgba(180,50,50,0.5);}
.nav-btn.danger:hover{background:rgba(180,50,50,0.7);}

/* LAYOUT */
.container{max-width:1200px;margin:auto;padding:28px 24px;}

/* TOP GRID */
.top-grid{display:grid;grid-template-columns:320px 1fr 1fr;gap:18px;margin-bottom:20px;}

/* PROFIL CARD */
.profil-card{
    background:linear-gradient(160deg,#1a2e1a,#2d4a2d);
    border-radius:18px;padding:24px;color:white;
    display:flex;flex-direction:column;gap:16px;
}
.profil-avatar{
    width:60px;height:60px;border-radius:50%;
    background:rgba(255,255,255,0.15);
    display:flex;align-items:center;justify-content:center;
    font-size:1.6rem;margin-bottom:4px;
}
.profil-label{font-size:0.72rem;opacity:0.55;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;}
.profil-value{font-weight:700;font-size:0.95rem;}
.profil-role-badge{
    display:inline-flex;align-items:center;gap:5px;
    background:rgba(255,255,255,0.12);border-radius:20px;
    padding:3px 12px;font-size:0.78rem;font-weight:600;
}
.profil-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.08);}
.profil-row:last-child{border:none;}
.profil-row i{width:16px;opacity:0.6;font-size:0.8rem;}

/* TIKET CARD */
.tiket-card{
    background:linear-gradient(135deg,#1a3a2a,#2d5a3d);
    border-radius:18px;padding:24px;color:white;
    position:relative;overflow:hidden;
    display:flex;flex-direction:column;justify-content:space-between;
}
.tiket-card::after{
    content:'';position:absolute;right:-20px;top:-20px;
    width:160px;height:160px;border-radius:50%;
    background:rgba(255,255,255,0.05);
}
.tiket-card::before{
    content:'';position:absolute;right:40px;bottom:-30px;
    width:120px;height:120px;border-radius:50%;
    background:rgba(255,255,255,0.04);
}
.tiket-label{font-size:0.78rem;opacity:0.6;font-weight:600;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.tiket-id{font-size:2rem;font-weight:900;letter-spacing:2px;margin-bottom:6px;}
.tiket-date{font-size:0.82rem;opacity:0.6;}
.tiket-illustration{position:absolute;right:20px;top:50%;transform:translateY(-50%);font-size:4rem;opacity:0.15;}
.tiket-link{
    display:inline-flex;align-items:center;gap:6px;
    background:rgba(255,255,255,0.12);color:white;
    padding:8px 16px;border-radius:8px;font-size:0.82rem;
    font-weight:600;text-decoration:none;margin-top:16px;
    transition:0.2s;width:fit-content;
}
.tiket-link:hover{background:rgba(255,255,255,0.22);color:white;}

/* BPS CARD */
.bps-card{
    background:linear-gradient(135deg,#0f172a,#1e3a5f);
    border-radius:18px;padding:24px;color:white;
    text-decoration:none;display:flex;flex-direction:column;
    justify-content:space-between;transition:0.3s;
}
.bps-card:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(0,0,0,0.2);}
.bps-header{font-size:0.72rem;opacity:0.5;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;display:flex;align-items:center;gap:6px;}
.bps-title{font-size:1rem;font-weight:700;margin-bottom:4px;}
.bps-sub{font-size:0.78rem;opacity:0.5;margin-bottom:12px;}
.bps-nilai{font-size:2.4rem;font-weight:900;color:#38bdf8;}
.bps-badge{
    display:inline-block;background:rgba(56,189,248,0.15);
    border:1px solid rgba(56,189,248,0.3);color:#38bdf8;
    padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;
    margin-top:10px;
}

/* TIKET LINK CARD */
.tiket-link-card{
    background:white;border-radius:18px;padding:20px 24px;
    display:flex;align-items:center;justify-content:space-between;
    box-shadow:0 2px 12px rgba(0,0,0,0.06);text-decoration:none;
    transition:0.3s;margin-bottom:18px;border:2px solid transparent;
}
.tiket-link-card:hover{border-color:#2d4a2d;box-shadow:0 6px 20px rgba(0,0,0,0.1);}
.tlc-icon{width:42px;height:42px;background:#f0f4f8;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-right:14px;flex-shrink:0;}
.tlc-title{font-weight:700;color:#1a1a2e;font-size:0.95rem;}
.tlc-sub{color:#94a3b8;font-size:0.78rem;margin-top:2px;}
.tlc-arrow{width:34px;height:34px;background:#f0f4f8;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;}

/* STAT MINI */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;}
.stat-mini{background:white;border-radius:16px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,0.06);}
.stat-mini-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;}
.stat-mini-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;}
.stat-mini-num{font-size:1.8rem;font-weight:800;color:#1a1a2e;}
.stat-mini-label{font-size:0.78rem;color:#94a3b8;margin-top:2px;}
.stat-mini-trend{font-size:0.72rem;font-weight:600;margin-top:6px;}
.trend-up{color:#16a34a;}
.trend-neutral{color:#64748b;}

/* MAIN GRID BAWAH */
.bottom-grid{display:grid;grid-template-columns:1fr 380px;gap:18px;}

/* GRAFIK */
.chart-card{background:white;border-radius:18px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.06);}
.chart-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.chart-title{font-weight:700;font-size:1rem;color:#1a1a2e;}
.chart-filter{background:#f0f4f8;border:none;border-radius:8px;padding:6px 12px;font-size:0.8rem;font-weight:600;color:#64748b;cursor:pointer;}
.chart-wrap{position:relative;height:200px;display:flex;align-items:flex-end;gap:0;}
.chart-yaxis{display:flex;flex-direction:column;justify-content:space-between;height:200px;padding-right:8px;align-items:flex-end;}
.chart-yaxis span{font-size:0.68rem;color:#94a3b8;}
.chart-bars-area{flex:1;display:flex;flex-direction:column;}
.chart-lines{position:relative;flex:1;display:flex;align-items:flex-end;}
/* SVG Chart */
.chart-svg-wrap{width:100%;height:200px;}

/* DESTINASI LIST */
.destinasi-card-list{background:white;border-radius:18px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);}
.dest-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.dest-header h4{font-weight:700;font-size:1rem;color:#1a1a2e;}
.dest-lihat-semua{font-size:0.78rem;color:#2d4a2d;font-weight:600;text-decoration:none;}
.dest-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9;}
.dest-item:last-child{border:none;padding-bottom:0;}
.dest-foto{width:70px;height:58px;border-radius:10px;object-fit:cover;background:#e8f5e9;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.4rem;}
.dest-foto img{width:70px;height:58px;border-radius:10px;object-fit:cover;}
.dest-info{flex:1;}
.dest-nama{font-weight:700;font-size:0.88rem;color:#1a1a2e;margin-bottom:3px;}
.dest-jenis{font-size:0.72rem;padding:2px 8px;border-radius:12px;display:inline-block;margin-bottom:4px;}
.jenis-pantai{background:#dbeafe;color:#1d4ed8;}
.jenis-gunung{background:#dcfce7;color:#15803d;}
.jenis-museum{background:#fef3c7;color:#b45309;}
.jenis-umum{background:#f3e8ff;color:#7c3aed;}
.dest-meta{font-size:0.72rem;color:#94a3b8;display:flex;align-items:center;gap:6px;}
.dest-harga{font-weight:800;color:#1a1a2e;font-size:0.9rem;white-space:nowrap;margin-right:8px;}
.btn-beli-sm{
    background:#1a2e1a;color:white;border:none;
    padding:7px 14px;border-radius:8px;font-size:0.78rem;
    font-weight:700;cursor:pointer;text-decoration:none;
    display:inline-flex;align-items:center;gap:4px;white-space:nowrap;
    transition:0.2s;
}
.btn-beli-sm:hover{background:#2d4a2d;color:white;}
.btn-tutup-sm{background:#e2e8f0;color:#94a3b8;padding:7px 14px;border-radius:8px;font-size:0.78rem;font-weight:700;cursor:not-allowed;}

/* FOOTER INFO */
.footer-info{
    background:white;border-radius:16px;padding:16px 24px;
    display:grid;grid-template-columns:repeat(4,1fr);gap:16px;
    margin-top:18px;box-shadow:0 2px 10px rgba(0,0,0,0.05);
}
.fi-item{display:flex;align-items:center;gap:10px;}
.fi-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:0.9rem;flex-shrink:0;}
.fi-label{font-size:0.7rem;color:#94a3b8;margin-bottom:2px;}
.fi-value{font-size:0.82rem;font-weight:700;color:#1a1a2e;}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-brand">
        <span class="leaf">🌿</span> WISATA Pengunjung
    </div>
    <div class="nav-links">
        <a href="tiket_saya.php" class="nav-btn">🎫 Tiket Saya</a>
        <a href="batalkan_tiket.php" class="nav-btn">❌ Batalkan</a>
        <a href="profil.php" class="nav-btn">👤 Profil</a>
        <a href="logout.php" class="nav-btn danger">🚪 Logout</a>
    </div>
</nav>

<div class="container">

    <!-- TOP GRID: Profil | Tiket | BPS -->
    <div class="top-grid">

        <!-- PROFIL -->
        <div class="profil-card">
            <div>
                <div class="profil-avatar">👤</div>
                <div class="profil-label">Profil Saya</div>
            </div>
            <div>
                <div class="profil-row">
                    <i class="fas fa-user"></i>
                    <div>
                        <div class="profil-label">Username</div>
                        <div class="profil-value"><?= htmlspecialchars($_SESSION['user']) ?></div>
                    </div>
                </div>
                <div class="profil-row">
                    <i class="fas fa-id-badge"></i>
                    <div>
                        <div class="profil-label">Role</div>
                        <div><span class="profil-role-badge">🌿 Pengunjung</span></div>
                    </div>
                </div>
                <div class="profil-row">
                    <i class="fas fa-calendar"></i>
                    <div>
                        <div class="profil-label">Login Pada</div>
                        <div class="profil-value" style="font-size:0.82rem;"><?= date('d M Y, H:i') ?> WIB</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TIKET IDENTITAS -->
        <div class="tiket-card">
            <div>
                <div class="tiket-label">🎫 Tiket Masuk Wisata</div>
                <div class="tiket-id">WST-<?= strtoupper(substr(md5($_SESSION['user']), 0, 8)) ?></div>
                <div class="tiket-date">Berlaku: <?= date('d M Y') ?></div>
                <div class="tiket-illustration">🏔️</div>
            </div>
            <a href="tiket_saya.php" class="tiket-link">
                <i class="fas fa-list"></i> Lihat Semua Tiket Pembelian
            </a>
        </div>

        <!-- BPS -->
        <a href="detail_statistik.php?subjek=pariwisata&nilai=<?= $nilai_bps ?>" class="bps-card">
            <div>
                <div class="bps-header"><i class="fas fa-chart-line"></i> Statistik Nasional BPS</div>
                <div class="bps-title"><?= htmlspecialchars($label_bps) ?></div>
                <div class="bps-sub">Klik untuk lihat detail dari API BPS Pusat</div>
                <div class="bps-nilai"><?= number_format($nilai_bps, 0, ',', '.') ?></div>
                <span class="bps-badge">📊 Lihat Detail</span>
            </div>
            <div style="font-size:0.7rem;opacity:0.4;margin-top:8px;">Indeks Kunjungan Wisman</div>
        </a>
    </div>

    <!-- TIKET LINK -->
    <a href="tiket_saya.php" class="tiket-link-card">
        <div style="display:flex;align-items:center;">
            <div class="tlc-icon">🎟️</div>
            <div>
                <div class="tlc-title">Lihat Semua Tiket Pembelian</div>
                <div class="tlc-sub">Cek riwayat semua tiket yang pernah Anda beli.</div>
            </div>
        </div>
        <div class="tlc-arrow"><i class="fas fa-arrow-right"></i></div>
    </a>

    <!-- STAT MINI -->
    <div class="stat-grid">
        <div class="stat-mini">
            <div class="stat-mini-top">
                <div>
                    <div class="stat-mini-num"><?= number_format($total_pesanan_user) ?></div>
                    <div class="stat-mini-label">Total Tiket Saya</div>
                </div>
                <div class="stat-mini-icon" style="background:#e8f5e9;color:#2d4a2d;"><i class="fas fa-ticket-alt"></i></div>
            </div>
            <div class="stat-mini-trend trend-up">🎫 Tiket dimiliki</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-top">
                <div>
                    <div class="stat-mini-num"><?= $total_destinasi ?></div>
                    <div class="stat-mini-label">Destinasi Tersedia</div>
                </div>
                <div class="stat-mini-icon" style="background:#fef3c7;color:#b45309;"><i class="fas fa-map-marked-alt"></i></div>
            </div>
            <div class="stat-mini-trend trend-neutral">🏝️ Wisata tersedia</div>
        </div>
        <?php
        // Total pesanan hari ini semua user (buat info publik)
        $pesanan_hari_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM pesanan WHERE DATE(tanggal_pesan)=CURDATE()"))['t'] ?? 0;
        $pesanan_kemarin  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM pesanan WHERE DATE(tanggal_pesan)=DATE_SUB(CURDATE(),INTERVAL 1 DAY)"))['t'] ?? 0;
        ?>
        <div class="stat-mini">
            <div class="stat-mini-top">
                <div>
                    <div class="stat-mini-num"><?= $pesanan_hari_ini ?></div>
                    <div class="stat-mini-label">Kunjungan Hari Ini</div>
                </div>
                <div class="stat-mini-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-calendar-day"></i></div>
            </div>
            <div class="stat-mini-trend <?= $pesanan_hari_ini >= $pesanan_kemarin ? 'trend-up' : 'trend-neutral' ?>">
                <?= $pesanan_hari_ini >= $pesanan_kemarin ? '↑' : '↓' ?> dari kemarin
            </div>
        </div>
        <?php
        $rata_7hari = array_sum($grafik_data) > 0 ? round(array_sum($grafik_data) / 7) : 0;
        ?>
        <div class="stat-mini">
            <div class="stat-mini-top">
                <div>
                    <div class="stat-mini-num"><?= $rata_7hari ?></div>
                    <div class="stat-mini-label">Rata-rata/Hari</div>
                </div>
                <div class="stat-mini-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-chart-bar"></i></div>
            </div>
            <div class="stat-mini-trend trend-neutral">📊 Selama 7 hari terakhir</div>
        </div>
    </div>

    <!-- BOTTOM GRID: Grafik | Destinasi -->
    <div class="bottom-grid">

        <!-- GRAFIK -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">📈 Grafik Kunjungan (7 Hari Terakhir)</div>
                <span class="chart-filter">7 Hari Terakhir</span>
            </div>
            <?php
            $max_val = max(array_merge($grafik_data, [1]));
            $svg_w = 520; $svg_h = 200; $pad_l = 40; $pad_b = 30; $pad_t = 10; $pad_r = 20;
            $chart_w = $svg_w - $pad_l - $pad_r;
            $chart_h = $svg_h - $pad_b - $pad_t;
            $n = count($grafik_data);
            $step = $chart_w / ($n - 1);

            // Buat titik-titik
            $points = [];
            for ($i = 0; $i < $n; $i++) {
                $x = $pad_l + $i * $step;
                $y = $pad_t + $chart_h - ($grafik_data[$i] / max($max_val,1)) * $chart_h;
                $points[] = "$x,$y";
            }
            $polyline = implode(' ', $points);

            // Area fill
            $first = $points[0]; $last = $points[$n-1];
            list($fx, $fy) = explode(',', $first);
            list($lx, $ly) = explode(',', $last);
            $area = $polyline . " $lx," . ($pad_t + $chart_h) . " $fx," . ($pad_t + $chart_h);
            ?>
            <svg viewBox="0 0 <?= $svg_w ?> <?= $svg_h ?>" style="width:100%;height:220px;">
                <defs>
                    <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#2d4a2d" stop-opacity="0.3"/>
                        <stop offset="100%" stop-color="#2d4a2d" stop-opacity="0.02"/>
                    </linearGradient>
                </defs>
                <!-- Grid lines -->
                <?php for ($g = 0; $g <= 4; $g++):
                    $gy = $pad_t + ($chart_h / 4) * $g;
                    $gval = round($max_val - ($max_val / 4) * $g);
                ?>
                <line x1="<?= $pad_l ?>" y1="<?= $gy ?>" x2="<?= $svg_w - $pad_r ?>" y2="<?= $gy ?>"
                      stroke="#f0f0f0" stroke-width="1"/>
                <text x="<?= $pad_l - 6 ?>" y="<?= $gy + 4 ?>" text-anchor="end"
                      font-size="10" fill="#94a3b8"><?= $gval ?></text>
                <?php endfor; ?>

                <!-- Area -->
                <polygon points="<?= $area ?>" fill="url(#chartGrad)"/>

                <!-- Line -->
                <polyline points="<?= $polyline ?>" fill="none"
                          stroke="#2d4a2d" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>

                <!-- Dots & Labels -->
                <?php for ($i = 0; $i < $n; $i++):
                    list($px, $py) = explode(',', $points[$i]);
                ?>
                <circle cx="<?= $px ?>" cy="<?= $py ?>" r="4" fill="#2d4a2d" stroke="white" stroke-width="2"/>
                <?php if ($grafik_data[$i] > 0): ?>
                <text x="<?= $px ?>" y="<?= $py - 10 ?>" text-anchor="middle"
                      font-size="10" font-weight="700" fill="#2d4a2d"><?= $grafik_data[$i] ?></text>
                <?php endif; ?>
                <text x="<?= $px ?>" y="<?= $pad_t + $chart_h + 20 ?>" text-anchor="middle"
                      font-size="10" fill="#94a3b8"><?= $grafik_label[$i] ?></text>
                <?php endfor; ?>
            </svg>
        </div>

        <!-- DESTINASI LIST -->
        <div class="destinasi-card-list">
            <div class="dest-header">
                <h4>🏝️ Destinasi Wisata Tersedia</h4>
                <a href="#" class="dest-lihat-semua">Lihat Semua</a>
            </div>
            <?php
            mysqli_data_seek($query_wisata, 0);
            $count_dest = 0;
            while ($row = mysqli_fetch_assoc($query_wisata)):
                if ($count_dest >= 4) break;
                $count_dest++;
                $jenis = $row['jenis_wisata'] ?? 'umum';
                $icon_jenis = match($jenis) {'pantai'=>'🏖️','gunung'=>'🏔️','museum'=>'🏛️',default=>'🏞️'};
                $label_jenis = match($jenis) {'pantai'=>'Pantai','gunung'=>'Gunung','museum'=>'Museum',default=>'Umum'};
                $buka = $row['status_buka'] ?? 1;
                $icon_cuaca = match($row['cuaca']??'') {
                    'Cerah'=>'☀️','Cerah Berawan'=>'⛅','Berawan'=>'☁️',
                    'Hujan Ringan'=>'🌦️','Hujan Lebat'=>'🌧️',default=>'🌤️'
                };
            ?>
            <div class="dest-item">
                <div class="dest-foto">
                    <?php if (!empty($row['foto_url'])): ?>
                        <img src="<?= htmlspecialchars($row['foto_url']) ?>" alt="foto"
                             onerror="this.parentNode.innerHTML='<?= $icon_jenis ?>'">
                    <?php else: ?>
                        <?= $icon_jenis ?>
                    <?php endif; ?>
                </div>
                <div class="dest-info">
                    <div class="dest-nama"><?= htmlspecialchars($row['nama_wisata']) ?></div>
                    <span class="dest-jenis jenis-<?= $jenis ?>">🏝️ <?= $label_jenis ?></span>
                    <div class="dest-meta">
                        <span><?= $icon_cuaca ?> <?= htmlspecialchars($row['cuaca']??'Cerah') ?></span>
                        <span>|</span>
                        <span>🕐 <?= date('H:i',strtotime($row['jam_buka']??'08:00')) ?>–<?= date('H:i',strtotime($row['jam_tutup']??'17:00')) ?></span>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                    <div class="dest-harga">Rp<?= number_format($row['harga'],0,',','.') ?></div>
                    <?php if ($buka): ?>
                        <a href="proses_beli.php?id=<?= $row['id_wisata'] ?>" class="btn-beli-sm">🎫 Beli Tiket</a>
                    <?php else: ?>
                        <span class="btn-tutup-sm">🚫 Tutup</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- FOOTER INFO -->
    <div class="footer-info">
        <div class="fi-item">
            <div class="fi-icon" style="background:#e8f5e9;color:#2d4a2d;"><i class="fas fa-clock"></i></div>
            <div>
                <div class="fi-label">Jam Operasional</div>
                <div class="fi-value">08:00 – 17:00 WIB</div>
            </div>
        </div>
        <div class="fi-item">
            <div class="fi-icon" style="background:#fef3c7;color:#b45309;"><i class="fas fa-sun"></i></div>
            <div>
                <div class="fi-label">Cuaca Hari ini</div>
                <div class="fi-value">☀️ Cerah, 28°C</div>
            </div>
        </div>
        <div class="fi-item">
            <div class="fi-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-map-marker-alt"></i></div>
            <div>
                <div class="fi-label">Lokasi</div>
                <div class="fi-value">Indonesia</div>
            </div>
        </div>
        <div class="fi-item">
            <div class="fi-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-phone"></i></div>
            <div>
                <div class="fi-label">Kontak</div>
                <div class="fi-value">+62 812-3456-7890</div>
            </div>
        </div>
    </div>

</div>
</body>
</html>
<?php ob_end_flush(); ?>