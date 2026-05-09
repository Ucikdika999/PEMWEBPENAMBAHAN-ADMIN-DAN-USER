<?php
ob_start();
session_start();
include "auth_check.php"; 
include "koneksi.php";

if(!isset($_SESSION['login'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_login = $_SESSION['user'];

// JOIN ke tabel destinasi untuk ambil foto_url dan jenis_wisata
$query_tiket = mysqli_query($koneksi, "
    SELECT p.*, d.foto_url, d.jenis_wisata, d.jam_buka, d.jam_tutup
    FROM pesanan p
    LEFT JOIN destinasi d ON p.nama_wisata = d.nama_wisata
    WHERE p.username = '$user_login'
    ORDER BY p.id_pesanan DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Saya | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }

        /* NAVBAR */
        .topbar {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            padding: 14px 24px; display: flex; align-items: center;
            justify-content: space-between; box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }
        .topbar a { color: white; text-decoration: none; font-weight: 600; font-size: 0.95rem; }
        .topbar a:hover { opacity: 0.8; }
        .topbar .brand { font-size: 1.1rem; font-weight: 800; }

        .container { max-width: 1000px; margin: auto; padding: 30px 20px; }
        .page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
        .page-sub { color: #94a3b8; font-size: 0.9rem; margin-bottom: 28px; }

        /* TIKET CARD */
        .tiket-wrap { display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 20px; }

        .tiket-card {
            background: white; border-radius: 20px;
            overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: 0.3s;
        }
        .tiket-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.13); }

        /* FOTO BAGIAN ATAS */
        .tiket-foto {
            width: 100%; height: 180px; object-fit: cover;
            display: block;
        }
        .tiket-foto-placeholder {
            width: 100%; height: 180px;
            display: flex; align-items: center; justify-content: center;
            font-size: 4rem;
        }
        .bg-pantai { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
        .bg-gunung { background: linear-gradient(135deg, #16a34a, #15803d); }
        .bg-museum { background: linear-gradient(135deg, #d97706, #b45309); }
        .bg-umum   { background: linear-gradient(135deg, #7c3aed, #6d28d9); }

        /* BODY TIKET */
        .tiket-body { padding: 20px; }

        .tiket-kode {
            font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; color: #94a3b8; margin-bottom: 6px;
        }
        .tiket-nama {
            font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 10px;
        }

        .tiket-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
        .meta-item {
            display: flex; align-items: center; gap: 6px;
            font-size: 0.82rem; color: #64748b;
        }
        .meta-item i { color: #3b82f6; width: 14px; }

        /* GARIS PUTUS-PUTUS TIKET */
        .tiket-divider {
            border: none; border-top: 2px dashed #e2e8f0;
            margin: 14px 0; position: relative;
        }
        .tiket-divider::before {
            content: ''; position: absolute; width: 20px; height: 20px;
            background: #f0f4f8; border-radius: 50%;
            top: -11px; left: -30px;
        }
        .tiket-divider::after {
            content: ''; position: absolute; width: 20px; height: 20px;
            background: #f0f4f8; border-radius: 50%;
            top: -11px; right: -30px;
        }

        /* FOOTER TIKET */
        .tiket-footer {
            display: flex; justify-content: space-between; align-items: center;
        }
        .tiket-harga { font-size: 1.3rem; font-weight: 800; color: #16a34a; }
        .tiket-harga small { font-size: 0.72rem; color: #94a3b8; font-weight: 500; display: block; }
        .badge-lunas {
            background: #d1fae5; color: #065f46; font-weight: 700;
            padding: 6px 16px; border-radius: 20px; font-size: 0.8rem;
        }

        /* BARCODE SIMULASI */
        .tiket-barcode {
            margin-top: 14px; padding: 10px;
            background: #f8fafc; border-radius: 10px; text-align: center;
        }
        .barcode-lines {
            display: flex; justify-content: center; align-items: flex-end;
            gap: 2px; height: 36px; margin-bottom: 6px;
        }
        .barcode-lines span {
            display: inline-block; width: 3px; background: #0f172a;
            border-radius: 1px;
        }
        .barcode-text { font-size: 0.7rem; color: #94a3b8; letter-spacing: 2px; font-weight: 600; }

        /* KOSONG */
        .empty-state {
            text-align: center; padding: 60px 20px;
            background: white; border-radius: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .empty-state i { font-size: 4rem; color: #cbd5e1; margin-bottom: 16px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="topbar">
    <a href="user_dashboard.php"><i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard</a>
    <span class="brand">🎫 Tiket Saya</span>
    <a href="batalkan_tiket.php"><i class="fas fa-times-circle me-1"></i>Batalkan Tiket</a>
</div>

<div class="container">
    <div class="page-title">Riwayat Pembelian Tiket</div>
    <div class="page-sub">Tunjukkan tiket ini kepada petugas di gerbang masuk</div>

    <?php if($query_tiket && mysqli_num_rows($query_tiket) > 0): ?>
    <div class="tiket-wrap">
        <?php while($row = mysqli_fetch_assoc($query_tiket)):
            $jenis     = $row['jenis_wisata'] ?? 'umum';
            $foto      = $row['foto_url'] ?? '';
            $icon      = match($jenis) {
                'pantai' => '🏖️', 'gunung' => '🏔️', 'museum' => '🏛️', default => '🏞️'
            };
            $bg_class  = 'bg-' . $jenis;

            // Data tambahan (form dinamis)
            $data_tambahan = json_decode($row['data_tambahan'] ?? '{}', true) ?: [];

            // Buat barcode simulasi dari id
            $seed = $row['id_pesanan'] * 7;
            $bars = [];
            for ($i = 0; $i < 30; $i++) {
                $bars[] = (($seed * ($i+3)) % 28) + 10;
                $seed = ($seed * 31 + $i) % 100;
            }
        ?>
        <div class="tiket-card">

            <!-- FOTO / PLACEHOLDER -->
            <?php if (!empty($foto)): ?>
                <img src="<?= htmlspecialchars($foto) ?>"
                     class="tiket-foto"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="tiket-foto-placeholder <?= $bg_class ?>" style="display:none;"><?= $icon ?></div>
            <?php else: ?>
                <div class="tiket-foto-placeholder <?= $bg_class ?>"><?= $icon ?></div>
            <?php endif; ?>

            <!-- BODY -->
            <div class="tiket-body">

                <div class="tiket-kode">
                    🎟️ Kode Booking: #WDT-<?= $row['id_pesanan'] ?>
                </div>
                <div class="tiket-nama"><?= htmlspecialchars($row['nama_wisata']) ?></div>

                <!-- META INFO -->
                <div class="tiket-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <?= !empty($row['tanggal_kunjungan'])
                            ? date('d M Y', strtotime($row['tanggal_kunjungan']))
                            : (isset($row['tanggal_pesan']) ? date('d M Y', strtotime($row['tanggal_pesan'])) : 'Tanggal tidak tersedia') ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-ticket-alt"></i>
                        <?= $row['jumlah_tiket'] ?? 1 ?> Tiket
                    </div>
                    <?php if (!empty($row['jam_buka'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <?= date('H:i', strtotime($row['jam_buka'])) ?> – <?= date('H:i', strtotime($row['jam_tutup'])) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- DATA TAMBAHAN DINAMIS -->
                <?php if (!empty($data_tambahan)): ?>
                <div style="background:#f8fafc; border-radius:10px; padding:10px 14px; margin-bottom:12px; font-size:0.82rem; color:#475569;">
                    <?php foreach($data_tambahan as $key => $val): ?>
                        <?php if (!empty($val)): ?>
                        <div style="margin-bottom:4px;">
                            <strong><?= ucwords(str_replace('_', ' ', $key)) ?>:</strong>
                            <?= htmlspecialchars($val) ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- GARIS TIKET -->
                <hr class="tiket-divider">

                <!-- FOOTER -->
                <div class="tiket-footer">
                    <div class="tiket-harga">
                        <small>Total Harga</small>
                        Rp<?= number_format($row['harga'], 0, ',', '.') ?>
                    </div>
                    <span class="badge-lunas">✓ Lunas</span>
                </div>

                <!-- BARCODE SIMULASI -->
                <div class="tiket-barcode">
                    <div class="barcode-lines">
                        <?php foreach($bars as $h): ?>
                            <span style="height:<?= $h ?>px;"></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="barcode-text">WDT-<?= str_pad($row['id_pesanan'], 6, '0', STR_PAD_LEFT) ?></div>
                </div>

            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-ticket-alt d-block"></i>
        <h5 class="fw-bold text-dark">Belum ada tiket</h5>
        <p class="text-muted">Kamu belum membeli tiket wisata apapun.</p>
        <a href="user_dashboard.php" class="btn btn-primary px-4 fw-bold rounded-pill">
            Cari Destinasi Sekarang
        </a>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
<?php ob_end_flush(); ?>