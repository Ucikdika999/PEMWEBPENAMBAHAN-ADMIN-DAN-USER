<?php
session_start();
echo "Role: " . $_SESSION['role'] . " | User: " . $_SESSION['user'];
exit();
?>
<?php
ob_start();
include "auth_check.php";
include "koneksi.php";

if ($_SESSION['role'] !== 'user') {
    header("Location: admin_dashboard.php");
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengunjung | WISATA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; }

        /* NAVBAR */
        .navbar {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white; padding: 15px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .navbar h1 { font-size: 1.4rem; }
        .nav-links { display: flex; gap: 10px; }
        .nav-btn {
            background: rgba(255,255,255,0.15); color: white;
            border: none; padding: 8px 16px; border-radius: 8px;
            cursor: pointer; text-decoration: none; font-size: 0.9rem;
            transition: 0.2s;
        }
        .nav-btn:hover { background: rgba(255,255,255,0.25); color: white; }
        .nav-btn.danger { background: rgba(255,80,80,0.3); }

        .container { padding: 30px; max-width: 1000px; margin: auto; }

        /* CARD */
        .card {
            background: white; border-radius: 16px;
            padding: 25px; margin-bottom: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .card h3 { color: #2e7d32; margin-bottom: 15px; font-size: 1.1rem; }

        /* PROFIL */
        .info-row {
            display: flex; justify-content: space-between;
            padding: 10px 0; border-bottom: 1px solid #f0f4f8;
        }
        .info-label { color: #666; font-weight: 500; }
        .info-value { color: #333; font-weight: 600; }

        /* TIKET */
        .ticket {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white; border-radius: 16px; padding: 30px;
            text-align: center; margin-top: 10px;
        }
        .ticket h2 { font-size: 1.2rem; opacity: 0.9; margin-bottom: 10px; }
        .ticket .ticket-id { font-size: 2rem; font-weight: bold; letter-spacing: 3px; }
        .ticket .ticket-date { opacity: 0.8; margin-top: 10px; font-size: 0.9rem; }

        /* DESTINASI */
        .destinasi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px; margin-top: 10px;
        }
        .destinasi-card {
            border-radius: 14px; overflow: hidden;
            border: 1px solid #e8f5e9;
            transition: 0.3s; background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .destinasi-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        .destinasi-foto {
            width: 100%; height: 130px; object-fit: cover; background: #e8f5e9;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem;
        }
        .destinasi-foto img { width: 100%; height: 130px; object-fit: cover; }
        .destinasi-body { padding: 14px; }
        .destinasi-nama { font-weight: 700; color: #1b5e20; margin-bottom: 4px; }
        .destinasi-jenis {
            font-size: 0.75rem; font-weight: 600; padding: 3px 10px;
            border-radius: 20px; display: inline-block; margin-bottom: 8px;
        }
        .jenis-pantai { background: #dbeafe; color: #1d4ed8; }
        .jenis-gunung { background: #dcfce7; color: #15803d; }
        .jenis-museum { background: #fef3c7; color: #b45309; }
        .jenis-umum   { background: #f3e8ff; color: #7c3aed; }
        .destinasi-harga { font-weight: 700; color: #2e7d32; font-size: 1rem; margin-bottom: 4px; }
        .destinasi-info { font-size: 0.8rem; color: #888; margin-bottom: 10px; }
        .status-buka  { color: #16a34a; font-weight: 600; }
        .status-tutup { color: #dc2626; font-weight: 600; }
        .btn-beli {
            display: block; text-align: center; background: #2e7d32;
            color: white; padding: 9px; border-radius: 8px;
            text-decoration: none; font-weight: 600; font-size: 0.9rem;
            transition: 0.2s;
        }
        .btn-beli:hover { background: #1b5e20; color: white; }
        .btn-beli.tutup { background: #ccc; cursor: not-allowed; }

        /* BPS CARD */
        .bps-card {
            background: linear-gradient(135deg, #0f172a, #1e3a5f);
            color: white; border-radius: 16px; padding: 24px;
            margin-bottom: 25px; cursor: pointer; transition: 0.3s;
            text-decoration: none; display: block;
        }
        .bps-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,0.2); }
        .bps-top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .bps-label { font-size: 0.8rem; opacity: 0.6; text-transform: uppercase; letter-spacing: 1px; }
        .bps-title { font-size: 1.1rem; font-weight: 700; margin-top: 4px; }
        .bps-sub { font-size: 0.85rem; opacity: 0.6; margin-top: 2px; }
        .bps-nilai { font-size: 2.2rem; font-weight: 800; color: #38bdf8; }
        .bps-badge {
            background: rgba(56,189,248,0.15); border: 1px solid rgba(56,189,248,0.3);
            color: #38bdf8; padding: 4px 12px; border-radius: 20px;
            font-size: 0.8rem; font-weight: 600; margin-top: 8px; display: inline-block;
        }

        /* TOMBOL */
        .btn {
            display: inline-block; padding: 10px 20px; border-radius: 8px;
            font-weight: 600; cursor: pointer; border: none; font-size: 0.95rem;
            text-decoration: none; margin-top: 10px; text-align: center;
        }
        .btn-primary { background: #1a73e8; color: white; }
        .btn-primary:hover { background: #1558b0; }
        .btn-full { display: block; width: 100%; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <h1>🌿 WISATA Pengunjung</h1>
    <div class="nav-links">
        <a href="tiket_saya.php" class="nav-btn">🎫 Tiket Saya</a>
        <a href="batalkan_tiket.php" class="nav-btn">❌ Batalkan</a>
        <a href="profil.php" class="nav-btn">👤 Profil</a>
        <a href="logout.php" class="nav-btn danger">🚪 Logout</a>
    </div>
</nav>

<div class="container">

    <!-- PROFIL -->
    <div class="card">
        <h3>👤 Profil Saya</h3>
        <div class="info-row">
            <span class="info-label">Username</span>
            <span class="info-value"><?= htmlspecialchars($_SESSION['user']) ?></span>
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

    <!-- TIKET IDENTITAS -->
    <div class="card">
        <h3>🎫 Tiket Identitas Saya</h3>
        <div class="ticket">
            <h2>🏔️ Tiket Masuk Wisata</h2>
            <div class="ticket-id">WST-<?= strtoupper(substr(md5($_SESSION['user']), 0, 8)) ?></div>
            <div class="ticket-date">Berlaku: <?= date('d M Y') ?></div>
        </div>
        <a href="tiket_saya.php" class="btn btn-primary btn-full" style="margin-top:15px; text-align:center;">
            📋 Lihat Semua Tiket Pembelian
        </a>
    </div>

    <!-- STATISTIK BPS -->
    <a href="detail_statistik.php?subjek=pariwisata&nilai=<?= $nilai_bps ?>" class="bps-card">
        <div class="bps-top">
            <div>
                <div class="bps-label"><i class="fas fa-chart-line"></i> Statistik Nasional BPS</div>
                <div class="bps-title"><?= htmlspecialchars($label_bps) ?></div>
                <div class="bps-sub">Klik untuk lihat detail data dari API BPS Pusat</div>
                <span class="bps-badge">📊 Lihat Detail</span>
            </div>
            <div style="text-align:right;">
                <div class="bps-nilai"><?= number_format($nilai_bps, 0, ',', '.') ?></div>
                <div style="font-size:0.8rem;opacity:0.5;margin-top:4px;">Indeks Kunjungan Wisman</div>
            </div>
        </div>
    </a>

    <!-- DESTINASI WISATA -->
    <div class="card">
        <h3>🏝️ Destinasi Wisata Tersedia</h3>
        <div class="destinasi-grid">
        <?php
        $query_wisata = mysqli_query($koneksi, "SELECT * FROM destinasi ORDER BY id_wisata ASC");
        if (mysqli_num_rows($query_wisata) > 0):
            while ($row = mysqli_fetch_assoc($query_wisata)):
                $jenis = $row['jenis_wisata'] ?? 'umum';
                $icon_jenis = match($jenis) {
                    'pantai' => '🏖️', 'gunung' => '🏔️', 'museum' => '🏛️', default => '🏞️'
                };
                $label_jenis = match($jenis) {
                    'pantai' => 'Pantai', 'gunung' => 'Gunung', 'museum' => 'Museum', default => 'Umum'
                };
                $buka = $row['status_buka'] ?? 1;
                $icon_cuaca = match($row['cuaca'] ?? '') {
                    'Cerah' => '☀️', 'Cerah Berawan' => '⛅', 'Berawan' => '☁️',
                    'Hujan Ringan' => '🌦️', 'Hujan Lebat' => '🌧️', default => '🌤️'
                };
        ?>
        <div class="destinasi-card">
            <?php if (!empty($row['foto_url'])): ?>
                <div class="destinasi-foto"><img src="<?= htmlspecialchars($row['foto_url']) ?>" alt="foto" onerror="this.parentNode.innerHTML='<?= $icon_jenis ?>'"></div>
            <?php else: ?>
                <div class="destinasi-foto"><?= $icon_jenis ?></div>
            <?php endif; ?>
            <div class="destinasi-body">
                <div class="destinasi-nama"><?= htmlspecialchars($row['nama_wisata']) ?></div>
                <span class="destinasi-jenis jenis-<?= $jenis ?>"><?= $icon_jenis ?> <?= $label_jenis ?></span>
                <div class="destinasi-harga">Rp<?= number_format($row['harga'], 0, ',', '.') ?></div>
                <div class="destinasi-info">
                    <?= $icon_cuaca ?> <?= htmlspecialchars($row['cuaca'] ?? 'Cerah') ?> &nbsp;|&nbsp;
                    🕐 <?= date('H:i', strtotime($row['jam_buka'] ?? '08:00')) ?>–<?= date('H:i', strtotime($row['jam_tutup'] ?? '17:00')) ?>
                    <br>
                    <span class="<?= $buka ? 'status-buka' : 'status-tutup' ?>">
                        <?= $buka ? '✓ Buka' : '✗ Tutup' ?>
                    </span>
                </div>
                <?php if ($buka): ?>
                    <a href="proses_beli.php?id=<?= $row['id_wisata'] ?>" class="btn-beli">🎟️ Beli Tiket</a>
                <?php else: ?>
                    <span class="btn-beli tutup">🚫 Sedang Tutup</span>
                <?php endif; ?>
            </div>
        </div>
        <?php
            endwhile;
        else:
        ?>
        <p style="color:#888;grid-column:1/-1;text-align:center;padding:20px;">
            Belum ada destinasi tersedia. Tunggu update dari admin ya!
        </p>
        <?php endif; ?>
        </div>
    </div>

</div>
</body>
</html>
<?php ob_end_flush(); ?>