<?php
ob_start();
session_start();
include "koneksi.php";

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$res = mysqli_query($koneksi, "SELECT * FROM destinasi WHERE id_wisata='$id'");
$w   = mysqli_fetch_assoc($res);
if (!$w) { header("Location: index.php"); exit; }

// Log kunjungan
$_hal = basename($_SERVER['PHP_SELF']);
$_usr = isset($_SESSION['user']) ? mysqli_real_escape_string($koneksi, $_SESSION['user']) : 'tamu';
$_ip  = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
mysqli_query($koneksi, "INSERT INTO log_kunjungan (halaman, username, ip_address) VALUES ('$_hal','$_usr','$_ip')");

// Ambil ulasan approved
$ulasan_res  = mysqli_query($koneksi, "SELECT * FROM ulasan WHERE id_wisata='$id' AND status='approved' ORDER BY created_at DESC");
$ulasan_list = [];
while ($u = mysqli_fetch_assoc($ulasan_res)) $ulasan_list[] = $u;

// Rata-rata rating
$avg_res = mysqli_query($koneksi, "SELECT AVG(rating) as avg_r, COUNT(*) as total FROM ulasan WHERE id_wisata='$id' AND status='approved'");
$avg_row = mysqli_fetch_assoc($avg_res);
$avg_rating = round((float)($avg_row['avg_r'] ?? 0), 1);
$total_ulasan = (int)($avg_row['total'] ?? 0);

// Cek apakah user sudah beri ulasan
$sudah_ulasan = false;
if (isset($_SESSION['user'])) {
    $usr_esc = mysqli_real_escape_string($koneksi, $_SESSION['user']);
    $cek = mysqli_query($koneksi, "SELECT id FROM ulasan WHERE id_wisata='$id' AND username='$usr_esc'");
    $sudah_ulasan = mysqli_num_rows($cek) > 0;
}

$fasilitas_arr = json_decode($w['fasilitas'] ?? '[]', true) ?: [];
$msg = $_GET['msg'] ?? '';

function starHtml($r, $interactive = false, $name = 'rating') {
    if ($interactive) {
        $html = '<div class="star-input">';
        for ($i = 5; $i >= 1; $i--) {
            $html .= "<input type='radio' name='$name' id='star$i' value='$i' required>
                      <label for='star$i' title='$i bintang'><i class='fas fa-star'></i></label>";
        }
        $html .= '</div>';
        return $html;
    }
    $html = '<span class="stars-display">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="fas fa-star' . ($i <= $r ? '' : '-empty') . '"></i>';
    }
    $html .= '</span>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($w['nama_wisata']) ?> | Detail Wisata</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
:root {
    --accent:#2563eb; --border:#e2e8f0; --surface:#fff;
    --bg:#f1f5f9; --text:#0f172a; --muted:#64748b;
    --success:#10b981; --warning:#f59e0b; --danger:#ef4444;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);}

/* NAVBAR */
.topbar{background:#0f172a;padding:14px 30px;display:flex;align-items:center;justify-content:space-between;}
.topbar-brand{color:#fff;font-weight:800;font-size:1.1rem;text-decoration:none;display:flex;align-items:center;gap:8px;}
.topbar-nav{display:flex;align-items:center;gap:12px;}
.topbar-nav a{color:rgba(255,255,255,.6);text-decoration:none;font-size:.85rem;font-weight:500;transition:.15s;}
.topbar-nav a:hover{color:#fff;}
.btn-login{background:var(--accent);color:#fff!important;padding:7px 16px;border-radius:8px;font-weight:600!important;}

/* HERO */
.hero{position:relative;height:380px;overflow:hidden;}
.hero-img{width:100%;height:100%;object-fit:cover;}
.hero-placeholder{width:100%;height:100%;background:linear-gradient(135deg,#1e3a5f,#2563eb);display:flex;align-items:center;justify-content:center;font-size:4rem;}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7) 0%,transparent 60%);}
.hero-info{position:absolute;bottom:28px;left:32px;right:32px;}
.hero-badge{display:inline-block;background:var(--accent);color:#fff;font-size:.72rem;font-weight:700;padding:4px 12px;border-radius:20px;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;}
.hero-title{color:#fff;font-size:2rem;font-weight:800;line-height:1.2;margin-bottom:6px;}
.hero-loc{color:rgba(255,255,255,.75);font-size:.9rem;display:flex;align-items:center;gap:6px;}

/* LAYOUT */
.content-wrap{max-width:1000px;margin:0 auto;padding:28px 20px;}
.two-col{display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;}

/* CARDS */
.card-section{background:var(--surface);border-radius:14px;padding:22px;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:20px;}
.section-title{font-weight:800;font-size:1rem;margin-bottom:14px;display:flex;align-items:center;gap:8px;}

/* INFO GRID */
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.info-item{background:#f8fafc;border-radius:10px;padding:12px 14px;}
.info-label{font-size:.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
.info-val{font-size:.9rem;font-weight:700;}

/* FASILITAS */
.fasil-list{display:flex;flex-wrap:wrap;gap:8px;}
.fasil-tag{background:#f1f5f9;border:1.5px solid var(--border);border-radius:8px;padding:5px 12px;font-size:.78rem;font-weight:600;display:flex;align-items:center;gap:5px;}

/* RATING SUMMARY */
.rating-big{display:flex;align-items:center;gap:16px;margin-bottom:16px;}
.rating-num{font-size:3rem;font-weight:800;line-height:1;color:var(--text);}
.rating-stars .fas.fa-star{color:#f59e0b;font-size:1rem;}
.rating-stars .fa-star-empty{color:#e2e8f0;font-size:1rem;}
.rating-count{font-size:.82rem;color:var(--muted);margin-top:4px;}

/* ULASAN CARD */
.ulasan-card{border:1.5px solid var(--border);border-radius:12px;padding:16px;margin-bottom:12px;}
.ulasan-header{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.ulasan-ava{width:36px;height:36px;background:var(--accent);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem;flex-shrink:0;}
.ulasan-user{font-weight:700;font-size:.88rem;}
.ulasan-date{font-size:.74rem;color:var(--muted);}
.stars-display .fa-star{color:#f59e0b;}
.stars-display .fa-star-empty{color:#e2e8f0;}
.ulasan-text{font-size:.875rem;color:#334155;line-height:1.6;margin-top:6px;}
.ulasan-foto{margin-top:10px;border-radius:9px;max-width:200px;max-height:150px;object-fit:cover;cursor:pointer;}
.empty-ulasan{text-align:center;padding:30px;color:var(--muted);}
.empty-ulasan i{font-size:2rem;opacity:.25;display:block;margin-bottom:8px;}

/* FORM ULASAN */
.star-input{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:4px;margin-bottom:14px;}
.star-input input{display:none;}
.star-input label{font-size:1.6rem;color:#e2e8f0;cursor:pointer;transition:.15s;}
.star-input label:hover,
.star-input label:hover ~ label,
.star-input input:checked ~ label{color:#f59e0b;}
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:5px;}
.form-group input,
.form-group textarea{width:100%;border:1.5px solid var(--border);border-radius:9px;padding:9px 13px;font-size:.85rem;font-family:inherit;outline:none;transition:.2s;resize:none;}
.form-group input:focus,
.form-group textarea:focus{border-color:var(--accent);}
.btn-submit{background:var(--accent);color:#fff;border:none;border-radius:9px;padding:10px 22px;font-size:.85rem;font-weight:700;font-family:inherit;cursor:pointer;width:100%;display:flex;align-items:center;justify-content:center;gap:6px;transition:.18s;}
.btn-submit:hover{background:#1d4ed8;}

/* SIDEBAR CARD */
.price-card{background:var(--surface);border-radius:14px;padding:22px;box-shadow:0 1px 8px rgba(0,0,0,.06);position:sticky;top:20px;}
.price-num{font-size:1.8rem;font-weight:800;color:var(--accent);margin-bottom:4px;}
.price-sub{font-size:.78rem;color:var(--muted);}
.status-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:700;margin:12px 0;}
.pill-open{background:#d1fae5;color:#065f46;}
.pill-closed{background:#fee2e2;color:#991b1b;}
.dot{width:6px;height:6px;border-radius:50%;display:inline-block;}
.dot-g{background:#16a34a;}.dot-r{background:#dc2626;}
.detail-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:.83rem;}
.detail-row:last-child{border-bottom:none;}
.detail-row span:first-child{color:var(--muted);}
.detail-row span:last-child{font-weight:600;}
.cuaca-badge{display:inline-flex;align-items:center;gap:5px;font-weight:600;}
.alert-msg{border-radius:10px;padding:12px 16px;font-size:.83rem;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#d1fae5;color:#065f46;}
.alert-warning{background:#fef3c7;color:#92400e;}
.alert-info{background:#dbeafe;color:#1e40af;}
</style>
</head>
<body>

<!-- NAVBAR -->
<div class="topbar">
    <a href="index.php" class="topbar-brand">🏔️ WISATA</a>
    <div class="topbar-nav">
        <a href="index.php">Beranda</a>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="profil.php"><?= htmlspecialchars($_SESSION['user']) ?></a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn-login">Login</a>
        <?php endif; ?>
    </div>
</div>

<!-- HERO -->
<div class="hero">
    <?php if (!empty($w['foto_url'])): ?>
        <img src="<?= htmlspecialchars($w['foto_url']) ?>" class="hero-img"
             onerror="this.style.display='none';document.getElementById('heroPlaceholder').style.display='flex';">
        <div id="heroPlaceholder" class="hero-placeholder" style="display:none;position:absolute;inset:0;">🏞️</div>
    <?php else: ?>
        <div class="hero-placeholder">🏞️</div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-info">
        <div class="hero-badge"><?= htmlspecialchars($w['jenis_wisata'] ?? 'Wisata') ?></div>
        <div class="hero-title"><?= htmlspecialchars($w['nama_wisata']) ?></div>
        <?php if (!empty($w['lokasi'])): ?>
        <div class="hero-loc"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($w['lokasi']) ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- CONTENT -->
<div class="content-wrap">

    <?php if ($msg === 'success'): ?>
        <div class="alert-msg alert-success"><i class="fas fa-check-circle"></i> Ulasan berhasil dikirim! Menunggu persetujuan admin.</div>
    <?php elseif ($msg === 'already'): ?>
        <div class="alert-msg alert-warning"><i class="fas fa-exclamation-triangle"></i> Kamu sudah pernah memberikan ulasan untuk destinasi ini.</div>
    <?php elseif ($msg === 'error_empty'): ?>
        <div class="alert-msg alert-warning"><i class="fas fa-exclamation-triangle"></i> Rating dan komentar wajib diisi.</div>
    <?php endif; ?>

    <div class="two-col">
        <!-- KOLOM KIRI -->
        <div>
            <!-- DESKRIPSI -->
            <?php if (!empty($w['deskripsi'])): ?>
            <div class="card-section">
                <div class="section-title"><i class="fas fa-info-circle" style="color:var(--accent);"></i> Tentang Destinasi</div>
                <p style="font-size:.88rem;color:#334155;line-height:1.7;"><?= nl2br(htmlspecialchars($w['deskripsi'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- INFO -->
            <div class="card-section">
                <div class="section-title"><i class="fas fa-clipboard-list" style="color:var(--accent);"></i> Informasi Wisata</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Jam Buka</div>
                        <div class="info-val">🕗 <?= date('H:i', strtotime($w['jam_buka'] ?? '08:00')) ?> – <?= date('H:i', strtotime($w['jam_tutup'] ?? '17:00')) ?> WIB</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Musim Terbaik</div>
                        <div class="info-val">📅 <?= htmlspecialchars($w['musim_terbaik'] ?? '-') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Harga Tiket</div>
                        <div class="info-val" style="color:var(--accent);">Rp<?= number_format($w['harga'], 0, ',', '.') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Kondisi Cuaca</div>
                        <div class="info-val">
                            <?php
                            $c = strtolower($w['cuaca'] ?? '');
                            $ci = str_contains($c,'hujan lebat') ? '🌧️' : (str_contains($c,'hujan') ? '🌦️' : (str_contains($c,'berawan') ? '⛅' : '☀️'));
                            echo "$ci " . htmlspecialchars(ucfirst($w['cuaca'] ?? '-'));
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FASILITAS -->
            <?php if (!empty($fasilitas_arr)): ?>
            <div class="card-section">
                <div class="section-title"><i class="fas fa-concierge-bell" style="color:var(--accent);"></i> Fasilitas</div>
                <div class="fasil-list">
                    <?php foreach ($fasilitas_arr as $f): ?>
                        <div class="fasil-tag"><i class="fas fa-check" style="color:var(--success);font-size:.7rem;"></i><?= htmlspecialchars($f) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ULASAN -->
            <div class="card-section">
                <div class="section-title"><i class="fas fa-star" style="color:#f59e0b;"></i> Ulasan Pengunjung</div>

                <?php if ($total_ulasan > 0): ?>
                <div class="rating-big">
                    <div class="rating-num"><?= number_format($avg_rating, 1) ?></div>
                    <div>
                        <div class="rating-stars"><?= starHtml($avg_rating) ?></div>
                        <div class="rating-count"><?= $total_ulasan ?> ulasan</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (count($ulasan_list) > 0): ?>
                    <?php foreach ($ulasan_list as $u): ?>
                    <div class="ulasan-card">
                        <div class="ulasan-header">
                            <div class="ulasan-ava"><?= strtoupper(substr($u['username'], 0, 1)) ?></div>
                            <div>
                                <div class="ulasan-user"><?= htmlspecialchars($u['username']) ?></div>
                                <div class="ulasan-date"><?= date('d M Y', strtotime($u['created_at'])) ?></div>
                            </div>
                            <div style="margin-left:auto;"><?= starHtml((int)$u['rating']) ?></div>
                        </div>
                        <div class="ulasan-text"><?= nl2br(htmlspecialchars($u['komentar'])) ?></div>
                        <?php if (!empty($u['foto_url'])): ?>
                            <img src="<?= htmlspecialchars($u['foto_url']) ?>" class="ulasan-foto"
                                 onclick="window.open(this.src,'_blank')"
                                 onerror="this.style.display='none'">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-ulasan">
                        <i class="fas fa-comment-slash"></i>
                        Belum ada ulasan untuk destinasi ini.
                    </div>
                <?php endif; ?>
            </div>

            <!-- FORM ULASAN -->
            <?php if (!isset($_SESSION['user'])): ?>
                <div class="card-section">
                    <div class="alert-msg alert-info" style="margin:0;">
                        <i class="fas fa-lock"></i>
                        <span><a href="login.php" style="color:var(--accent);font-weight:700;">Login</a> terlebih dahulu untuk memberikan ulasan.</span>
                    </div>
                </div>
            <?php elseif ($sudah_ulasan): ?>
                <div class="card-section">
                    <div class="alert-msg alert-warning" style="margin:0;">
                        <i class="fas fa-check-circle"></i> Kamu sudah memberikan ulasan untuk destinasi ini.
                    </div>
                </div>
            <?php else: ?>
                <div class="card-section">
                    <div class="section-title"><i class="fas fa-pen" style="color:var(--accent);"></i> Tulis Ulasan</div>
                    <form method="POST" action="ulasan_action.php">
                        <input type="hidden" name="action" value="submit">
                        <input type="hidden" name="id_wisata" value="<?= $id ?>">

                        <div class="form-group">
                            <label>Rating</label>
                            <?= starHtml(0, true) ?>
                        </div>

                        <div class="form-group">
                            <label>Komentar</label>
                            <textarea name="komentar" rows="3" placeholder="Bagikan pengalaman kamu di sini..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label>URL Foto <span style="font-weight:400;color:#94a3b8;">(opsional)</span></label>
                            <input type="url" name="foto_url" placeholder="https://...">
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Kirim Ulasan
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- KOLOM KANAN (SIDEBAR) -->
        <div>
            <div class="price-card">
                <div style="font-size:.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Harga Tiket</div>
                <div class="price-num">Rp<?= number_format($w['harga'], 0, ',', '.') ?></div>
                <div class="price-sub">per orang</div>

                <?php if ($w['status_buka']): ?>
                    <div class="status-pill pill-open"><span class="dot dot-g"></span> Sedang Buka</div>
                <?php else: ?>
                    <div class="status-pill pill-closed"><span class="dot dot-r"></span> Sedang Tutup</div>
                <?php endif; ?>

                <div class="detail-row">
                    <span>Jam Operasional</span>
                    <span><?= date('H:i', strtotime($w['jam_buka'] ?? '08:00')) ?> – <?= date('H:i', strtotime($w['jam_tutup'] ?? '17:00')) ?></span>
                </div>
                <div class="detail-row">
                    <span>Kategori</span>
                    <span><?= htmlspecialchars(ucfirst($w['jenis_wisata'] ?? '-')) ?></span>
                </div>
                <div class="detail-row">
                    <span>Cuaca</span>
                    <span class="cuaca-badge">
                        <?php echo $ci . ' ' . htmlspecialchars(ucfirst($w['cuaca'] ?? '-')); ?>
                    </span>
                </div>
                <?php if ($total_ulasan > 0): ?>
                <div class="detail-row">
                    <span>Rating</span>
                    <span>⭐ <?= number_format($avg_rating, 1) ?> / 5 (<?= $total_ulasan ?>)</span>
                </div>
                <?php endif; ?>

                <div style="margin-top:14px;">
                    <a href="index.php" style="display:flex;align-items:center;justify-content:center;gap:6px;background:#f1f5f9;color:var(--text);border-radius:9px;padding:10px;font-size:.83rem;font-weight:600;text-decoration:none;">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php ob_end_flush(); ?>