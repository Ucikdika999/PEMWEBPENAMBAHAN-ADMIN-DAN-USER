<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";
$_hal = basename($_SERVER['PHP_SELF']);
$_usr = isset($_SESSION['user']) ? mysqli_real_escape_string($koneksi, $_SESSION['user']) : 'tamu';
$_ip  = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
mysqli_query($koneksi, "INSERT INTO log_kunjungan (halaman, username, ip_address) VALUES ('$_hal', '$_usr', '$_ip')");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ── STATISTIK KUNJUNGAN HARI INI ──────────────────────────────────────
$q_hari_ini = mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM log_kunjungan WHERE DATE(waktu) = CURDATE()");
$kunjungan_hari_ini = mysqli_fetch_assoc($q_hari_ini)['total'] ?? 0;

// ── TOTAL KUNJUNGAN KESELURUHAN ───────────────────────────────────────
$q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_kunjungan");
$total_kunjungan = mysqli_fetch_assoc($q_total)['total'] ?? 0;

// ── PENGUNJUNG UNIK (IP BERBEDA) HARI INI ────────────────────────────
$q_unik = mysqli_query($koneksi,
    "SELECT COUNT(DISTINCT ip_address) as total FROM log_kunjungan WHERE DATE(waktu) = CURDATE()");
$pengunjung_unik = mysqli_fetch_assoc($q_unik)['total'] ?? 0;

// ── KUNJUNGAN 7 HARI TERAKHIR (untuk grafik garis) ───────────────────
$q_7hari = mysqli_query($koneksi,
    "SELECT DATE(waktu) as tgl, COUNT(*) as total
     FROM log_kunjungan
     WHERE waktu >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(waktu)
     ORDER BY tgl ASC");

$label_7hari = [];
$data_7hari  = [];
// Isi default 7 hari agar tidak ada tanggal yang hilang
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $label_7hari[$tgl] = 0;
}
while ($r = mysqli_fetch_assoc($q_7hari)) {
    $label_7hari[$r['tgl']] = (int)$r['total'];
}
$chart_labels = array_map(fn($d) => date('d M', strtotime($d)), array_keys($label_7hari));
$chart_data   = array_values($label_7hari);

// ── HALAMAN PALING BANYAK DIKUNJUNGI ─────────────────────────────────
$q_halaman = mysqli_query($koneksi,
    "SELECT halaman, COUNT(*) as total
     FROM log_kunjungan
     GROUP BY halaman
     ORDER BY total DESC
     LIMIT 6");
$data_halaman = [];
while ($r = mysqli_fetch_assoc($q_halaman)) {
    $data_halaman[] = $r;
}

// ── DESTINASI PALING DIMINATI (dari tabel pesanan) ───────────────────
$q_destinasi = mysqli_query($koneksi,
    "SELECT nama_wisata, COUNT(*) as total_beli, SUM(harga) as total_pendapatan
     FROM pesanan
     GROUP BY nama_wisata
     ORDER BY total_beli DESC
     LIMIT 5");
$data_destinasi = [];
while ($r = mysqli_fetch_assoc($q_destinasi)) {
    $data_destinasi[] = $r;
}

// ── JAM RAMAI PEMBELIAN ───────────────────────────────────────────────
$q_jam = mysqli_query($koneksi,
    "SELECT HOUR(tanggal_pesan) as jam, COUNT(*) as total
     FROM pesanan
     GROUP BY HOUR(tanggal_pesan)
     ORDER BY jam ASC");
$jam_labels = [];
$jam_data   = [];
// Default semua jam = 0
for ($h = 0; $h < 24; $h++) {
    $jam_labels[] = sprintf('%02d:00', $h);
    $jam_data[$h] = 0;
}
while ($r = mysqli_fetch_assoc($q_jam)) {
    $jam_data[(int)$r['jam']] = (int)$r['total'];
}
$jam_data = array_values($jam_data);

// ── LOG KUNJUNGAN TERBARU ─────────────────────────────────────────────
$q_log = mysqli_query($koneksi,
    "SELECT * FROM log_kunjungan ORDER BY waktu DESC LIMIT 15");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tracking Pengunjung | WISATA Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root {
    --sidebar-bg:#0f172a; --accent:#2563eb; --border:#e2e8f0;
    --surface:#fff; --bg:#f1f5f9; --text-primary:#0f172a;
    --text-muted:#64748b; --success:#10b981; --warning:#f59e0b;
    --danger:#ef4444; --sidebar-w:240px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text-primary);display:flex;min-height:100vh;}

/* SIDEBAR — sama seperti laporan_pesanan.php */
.sidebar{width:var(--sidebar-w);min-height:100vh;position:fixed;top:0;left:0;background:var(--sidebar-bg);display:flex;flex-direction:column;z-index:200;}
.sb-brand{padding:22px 18px 16px;border-bottom:1px solid rgba(255,255,255,0.06);}
.sb-logo{display:flex;align-items:center;gap:10px;}
.sb-icon{width:40px;height:40px;background:var(--accent);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.sb-title{color:#fff;font-weight:800;font-size:1rem;line-height:1.1;}
.sb-sub{color:rgba(255,255,255,0.35);font-size:0.68rem;font-weight:500;}
.sb-menu{padding:14px 10px;flex:1;}
.menu-section{font-size:.62rem;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,.25);font-weight:700;padding:10px 10px 5px;margin-top:4px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;color:rgba(255,255,255,.45);font-size:.84rem;font-weight:500;text-decoration:none;transition:.18s;margin-bottom:2px;}
.nav-item .ni-icon{width:30px;height:30px;border-radius:7px;background:rgba(255,255,255,.05);display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;transition:.18s;}
.nav-item:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.85);}
.nav-item:hover .ni-icon{background:rgba(255,255,255,.1);}
.nav-item.active{background:var(--accent);color:#fff;box-shadow:0 4px 16px rgba(37,99,235,.35);}
.nav-item.active .ni-icon{background:rgba(255,255,255,.18);}
.nav-item.logout{color:rgba(239,100,100,.7);}
.nav-item.logout:hover{background:rgba(239,68,68,.1);color:#fca5a5;}
.sb-footer{padding:12px 10px;border-top:1px solid rgba(255,255,255,.06);}
.admin-tag{display:flex;align-items:center;gap:9px;padding:9px 10px;background:rgba(255,255,255,.04);border-radius:9px;margin-bottom:6px;}
.admin-ava{width:32px;height:32px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;flex-shrink:0;}
.admin-name{color:#fff;font-size:.8rem;font-weight:600;}
.admin-role{color:rgba(255,255,255,.3);font-size:.65rem;}

/* MAIN */
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 30px;}
.page-title{font-size:1.45rem;font-weight:800;margin-bottom:4px;}
.page-sub{color:var(--text-muted);font-size:.85rem;margin-bottom:24px;}

/* STAT CARDS */
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px;}
.stat-card{background:var(--surface);border-radius:14px;padding:20px;box-shadow:0 1px 8px rgba(0,0,0,.06);}
.stat-ico{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:.95rem;margin-bottom:14px;}
.stat-num{font-size:1.8rem;font-weight:800;line-height:1;}
.stat-lbl{font-size:.75rem;color:var(--text-muted);margin-top:5px;}

/* CHART CARDS */
.chart-card{background:var(--surface);border-radius:14px;padding:22px;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:18px;}
.chart-title{font-size:.95rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;}

/* TABEL */
.table-card{background:var(--surface);border-radius:14px;box-shadow:0 1px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:18px;}
.table-head-bar{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.table-head-bar h5{font-weight:700;font-size:.95rem;margin:0;display:flex;align-items:center;gap:8px;}
table{width:100%;border-collapse:collapse;}
thead th{background:#f8fafc;color:var(--text-muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;font-weight:700;padding:11px 16px;border-bottom:1px solid var(--border);}
tbody td{padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:.875rem;vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover td{background:#f8fafc;}

/* BADGE */
.badge-halaman{background:#dbeafe;color:#1e40af;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;}

/* PROGRESS BAR DESTINASI */
.prog-bar{height:8px;border-radius:4px;background:#e2e8f0;overflow:hidden;margin-top:4px;}
.prog-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,#2563eb,#7c3aed);}

@media print{.sidebar{display:none!important;}.main{margin-left:0!important;}}
</style>
</head>
<body>

<!-- SIDEBAR -->
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
        <a href="admin_dashboard.php" class="nav-item">
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
        <a href="tracking.php" class="nav-item active">
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

<!-- MAIN -->
<main class="main">
    <div class="page-title">📊 Tracking Pengunjung</div>
    <div class="page-sub">Pantau aktivitas pengunjung website dan destinasi paling diminati</div>

    <!-- STAT CARDS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-ico" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-eye"></i></div>
            <div class="stat-num"><?= number_format($kunjungan_hari_ini) ?></div>
            <div class="stat-lbl">Kunjungan Hari Ini</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#d1fae5;color:#059669;"><i class="fas fa-users"></i></div>
            <div class="stat-num"><?= number_format($pengunjung_unik) ?></div>
            <div class="stat-lbl">Pengunjung Unik Hari Ini</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#fef3c7;color:#d97706;"><i class="fas fa-globe"></i></div>
            <div class="stat-num"><?= number_format($total_kunjungan) ?></div>
            <div class="stat-lbl">Total Kunjungan (Semua Waktu)</div>
        </div>
    </div>

    <!-- GRAFIK KUNJUNGAN 7 HARI -->
    <div class="chart-card">
        <div class="chart-title"><i class="fas fa-chart-line" style="color:#2563eb;"></i> Kunjungan 7 Hari Terakhir</div>
        <canvas id="chartKunjungan" height="90"></canvas>
    </div>

    <div class="row g-3 mb-3">
        <!-- GRAFIK JAM RAMAI -->
        <div class="col-md-8">
            <div class="chart-card mb-0">
                <div class="chart-title"><i class="fas fa-clock" style="color:#7c3aed;"></i> Jam Ramai Pembelian Tiket</div>
                <canvas id="chartJam" height="120"></canvas>
            </div>
        </div>

        <!-- HALAMAN PALING DIKUNJUNGI -->
        <div class="col-md-4">
            <div class="chart-card mb-0" style="height:100%;">
                <div class="chart-title"><i class="fas fa-file-alt" style="color:#059669;"></i> Halaman Populer</div>
                <?php
                $max_halaman = !empty($data_halaman) ? $data_halaman[0]['total'] : 1;
                foreach ($data_halaman as $h):
                    $pct = round($h['total'] / $max_halaman * 100);
                ?>
                <div style="margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;font-size:.82rem;font-weight:600;margin-bottom:4px;">
                        <span><?= htmlspecialchars($h['halaman']) ?></span>
                        <span style="color:var(--text-muted);"><?= $h['total'] ?>x</span>
                    </div>
                    <div class="prog-bar"><div class="prog-fill" style="width:<?= $pct ?>%;"></div></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($data_halaman)): ?>
                    <p style="color:var(--text-muted);font-size:.85rem;text-align:center;padding:20px 0;">Belum ada data kunjungan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- DESTINASI PALING DIMINATI -->
    <div class="table-card">
        <div class="table-head-bar">
            <h5><i class="fas fa-mountain-sun" style="color:#2563eb;"></i> Destinasi Paling Diminati</h5>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th style="padding-left:20px;">No</th>
                    <th>Nama Destinasi</th>
                    <th>Tiket Terjual</th>
                    <th>Total Pendapatan</th>
                    <th>Popularitas</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $max_beli = !empty($data_destinasi) ? $data_destinasi[0]['total_beli'] : 1;
            $no = 1;
            foreach ($data_destinasi as $d):
                $pct = round($d['total_beli'] / $max_beli * 100);
            ?>
            <tr>
                <td style="padding-left:20px;color:var(--text-muted);"><?= $no++ ?></td>
                <td><strong><?= htmlspecialchars($d['nama_wisata']) ?></strong></td>
                <td><?= $d['total_beli'] ?> tiket</td>
                <td style="color:#059669;font-weight:700;">Rp<?= number_format($d['total_pendapatan'],0,',','.') ?></td>
                <td style="width:200px;">
                    <div class="prog-bar"><div class="prog-fill" style="width:<?= $pct ?>%;"></div></div>
                    <small style="color:var(--text-muted);"><?= $pct ?>%</small>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data_destinasi)): ?>
            <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">Belum ada data pembelian.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- LOG KUNJUNGAN TERBARU -->
    <div class="table-card">
        <div class="table-head-bar">
            <h5><i class="fas fa-list" style="color:#2563eb;"></i> Log Kunjungan Terbaru</h5>
            <span style="font-size:.8rem;color:var(--text-muted);">15 aktivitas terakhir</span>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th style="padding-left:20px;">No</th>
                    <th>Halaman</th>
                    <th>Username</th>
                    <th>IP Address</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
            <?php $no = 1; while ($row = mysqli_fetch_assoc($q_log)): ?>
            <tr>
                <td style="padding-left:20px;color:var(--text-muted);"><?= $no++ ?></td>
                <td><span class="badge-halaman"><?= htmlspecialchars($row['halaman']) ?></span></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td style="font-size:.8rem;color:var(--text-muted);"><?= htmlspecialchars($row['ip_address']) ?></td>
                <td style="font-size:.8rem;color:var(--text-muted);"><?= date('d M Y H:i', strtotime($row['waktu'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</main>

<script>
// Grafik kunjungan 7 hari
new Chart(document.getElementById('chartKunjungan'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Jumlah Kunjungan',
            data: <?= json_encode($chart_data) ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#2563eb',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// Grafik jam ramai
new Chart(document.getElementById('chartJam'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($jam_labels) ?>,
        datasets: [{
            label: 'Pembelian',
            data: <?= json_encode($jam_data) ?>,
            backgroundColor: 'rgba(124,58,237,0.75)',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
</script>
</body>
</html>
<?php ob_end_flush(); ?>