<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";

if (!isset($_SESSION['login'])) { header("Location: login.php"); exit(); }

// ── PROSES BELI (POST dari form konfirmasi) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['beli'])) {
    $id_wisata        = (int) $_POST['id_wisata'];
    $username         = $_SESSION['user'];
    $jumlah           = max(1, (int) ($_POST['jumlah_tiket'] ?? 1));
    $tgl_kunjungan    = mysqli_real_escape_string($koneksi, $_POST['tanggal_kunjungan'] ?? '');

    // Field dinamis tambahan (per jenis wisata)
    $data_tambahan = [];
    if (!empty($_POST['jalur_pendakian']))  $data_tambahan['jalur_pendakian']  = $_POST['jalur_pendakian'];
    if (!empty($_POST['kategori_pengunjung'])) $data_tambahan['kategori_pengunjung'] = $_POST['kategori_pengunjung'];
    if (!empty($_POST['nama_kapal']))       $data_tambahan['nama_kapal']       = $_POST['nama_kapal'];
    if (!empty($_POST['jenis_tiket']))      $data_tambahan['jenis_tiket']      = $_POST['jenis_tiket'];
    if (!empty($_POST['catatan']))          $data_tambahan['catatan']          = $_POST['catatan'];
    $data_json = json_encode($data_tambahan);

    // Ambil data wisata
    $qw = mysqli_query($koneksi, "SELECT * FROM destinasi WHERE id_wisata='$id_wisata'");
    $wisata = mysqli_fetch_assoc($qw);

    if ($wisata) {
        $nama_wisata  = mysqli_real_escape_string($koneksi, $wisata['nama_wisata']);
        $harga_satuan = $wisata['harga'];
        $total_harga  = $harga_satuan * $jumlah;

        // Simpan semua tiket (1 baris per tiket)
        for ($i = 0; $i < $jumlah; $i++) {
            $sql = "INSERT INTO pesanan (username, nama_wisata, harga, jumlah_tiket, tanggal_kunjungan, data_tambahan)
                    VALUES ('$username', '$nama_wisata', '$total_harga', '$jumlah', '$tgl_kunjungan', '$data_json')";
            mysqli_query($koneksi, $sql);
        }
        // Redirect ke tiket_saya hanya 1x
        echo "<script>alert('✅ Tiket berhasil dipesan! Total: Rp" . number_format($total_harga,0,',','.') . "'); window.location='tiket_saya.php';</script>";
        exit();
    }
}

// ── TAMPIL FORM (GET) ──
if (!isset($_GET['id'])) { header("Location: user_dashboard.php"); exit(); }

$id_wisata = (int) $_GET['id'];
$qw = mysqli_query($koneksi, "SELECT * FROM destinasi WHERE id_wisata='$id_wisata'");
$wisata = mysqli_fetch_assoc($qw);

if (!$wisata) {
    echo "<script>alert('Wisata tidak ditemukan!'); window.location='user_dashboard.php';</script>";
    exit();
}

$jenis       = $wisata['jenis_wisata'] ?? 'umum';
$fasilitas   = json_decode($wisata['fasilitas'] ?? '[]', true) ?: [];
$buka        = (bool) $wisata['status_buka'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesan Tiket | WISATA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body {
    background: linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.75)),
                url('<?= !empty($wisata['foto_url']) ? htmlspecialchars($wisata['foto_url']) : 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1350&q=80' ?>');
    background-size:cover; background-position:center; background-attachment:fixed;
    min-height:100vh; color:white; font-family:'Segoe UI',sans-serif;
}
.glass {
    background:rgba(255,255,255,0.09); backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,0.15); border-radius:24px; padding:36px;
}
.form-control,.form-select {
    background:rgba(255,255,255,0.12); border:1.5px solid rgba(255,255,255,0.2);
    color:white; border-radius:12px; padding:12px 16px;
}
.form-control:focus,.form-select:focus {
    background:rgba(255,255,255,0.18); border-color:#6ea8fe; color:white; box-shadow:none;
}
.form-control::placeholder { color:rgba(255,255,255,0.4); }
.form-select option { background:#1e293b; color:white; }
.form-label { font-weight:600; font-size:0.9rem; opacity:0.85; }

/* Info kondisi wisata */
.kondisi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:12px; margin-bottom:24px; }
.kondisi-item {
    background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12);
    border-radius:14px; padding:14px; text-align:center;
}
.kondisi-item .val { font-size:1.1rem; font-weight:700; margin-top:4px; }
.kondisi-item .lbl { font-size:0.75rem; opacity:0.6; text-transform:uppercase; }

/* Fasilitas badges */
.fasilitas-wrap { display:flex; flex-wrap:wrap; gap:8px; }
.fasilitas-badge {
    background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
    border-radius:20px; padding:5px 14px; font-size:0.82rem;
}

/* Status tutup */
.alert-tutup { background:rgba(220,53,69,0.25); border:1px solid #dc3545; border-radius:14px; padding:16px 20px; }

/* Harga preview */
#previewHarga { font-size:1.8rem; font-weight:800; color:#6ee7b7; transition:0.3s; }

/* Tombol */
.btn-pesan {
    background:linear-gradient(135deg,#0d6efd,#0a58ca); border:none;
    border-radius:14px; padding:14px; font-weight:700;
    box-shadow:0 6px 20px rgba(13,110,253,0.4); transition:0.3s;
}
.btn-pesan:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 10px 28px rgba(13,110,253,0.5); }
.btn-pesan:disabled { opacity:0.5; cursor:not-allowed; }

.divider { border-color:rgba(255,255,255,0.15); }
.section-title { font-size:0.8rem; text-transform:uppercase; letter-spacing:1px; opacity:0.6; margin-bottom:12px; }
</style>
</head>
<body>

<nav class="navbar navbar-dark bg-transparent pt-3 px-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold fs-4" href="user_dashboard.php">
            <i class="fas fa-arrow-left me-2"></i>Kembali
        </a>
        <span class="badge bg-light text-dark px-3 py-2">
            <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['user']) ?>
        </span>
    </div>
</nav>

<div class="container mt-3 pb-5" style="max-width:800px;">
    <div class="glass shadow-lg">

        <!-- HEADER DESTINASI -->
        <div class="mb-4">
            <div class="d-flex align-items-center gap-3 mb-2">
                <span class="badge bg-primary px-3 py-2 rounded-pill fs-6">
                    <?= match($jenis) {
                        'pantai'=>'🏖️ Pantai','gunung'=>'🏔️ Gunung','museum'=>'🏛️ Museum',default=>'🏞️ Umum'
                    } ?>
                </span>
                <?php if ($buka): ?>
                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(16,185,129,0.25);color:#6ee7b7;border:1px solid #10b981;">✓ Sedang Buka</span>
                <?php else: ?>
                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(239,68,68,0.25);color:#fca5a5;border:1px solid #ef4444;">✗ Tutup</span>
                <?php endif; ?>
            </div>
            <h2 class="fw-bold mb-1"><?= htmlspecialchars($wisata['nama_wisata']) ?></h2>
            <?php if (!empty($wisata['deskripsi'])): ?>
                <p class="opacity-75"><?= htmlspecialchars($wisata['deskripsi']) ?></p>
            <?php endif; ?>
        </div>

        <!-- KONDISI WISATA -->
        <p class="section-title"><i class="fas fa-info-circle me-1"></i>Kondisi Saat Ini</p>
        <div class="kondisi-grid">
            <div class="kondisi-item">
                <div style="font-size:1.6rem;">
                    <?= match($wisata['cuaca']??'') {
                        'Cerah'=>'☀️','Cerah Berawan'=>'⛅','Berawan'=>'☁️',
                        'Hujan Ringan'=>'🌦️','Hujan Lebat'=>'🌧️',default=>'🌤️'
                    } ?>
                </div>
                <div class="val"><?= htmlspecialchars($wisata['cuaca']??'Cerah') ?></div>
                <div class="lbl">Cuaca</div>
            </div>
            <div class="kondisi-item">
                <div style="font-size:1.6rem;">🕐</div>
                <div class="val"><?= date('H:i',strtotime($wisata['jam_buka']??'08:00')) ?> – <?= date('H:i',strtotime($wisata['jam_tutup']??'17:00')) ?></div>
                <div class="lbl">Jam Operasional</div>
            </div>
            <div class="kondisi-item">
                <div style="font-size:1.6rem;">🌿</div>
                <div class="val" style="font-size:0.9rem;"><?= htmlspecialchars($wisata['musim_terbaik']??'Sepanjang tahun') ?></div>
                <div class="lbl">Musim Terbaik</div>
            </div>
            <div class="kondisi-item">
                <div style="font-size:1.6rem;">💰</div>
                <div class="val">Rp<?= number_format($wisata['harga'],0,',','.') ?></div>
                <div class="lbl">Harga/Tiket</div>
            </div>
        </div>

        <!-- FASILITAS -->
        <?php if (!empty($fasilitas)): ?>
        <p class="section-title mt-3"><i class="fas fa-concierge-bell me-1"></i>Fasilitas Tersedia</p>
        <div class="fasilitas-wrap mb-4">
            <?php foreach ($fasilitas as $f): ?>
                <span class="fasilitas-badge">✓ <?= htmlspecialchars($f) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <hr class="divider my-4">

        <!-- PERINGATAN JIKA TUTUP -->
        <?php if (!$buka): ?>
        <div class="alert-tutup mb-4">
            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
            <strong>Destinasi ini sedang tutup.</strong> Kamu tetap bisa memesan tiket untuk kunjungan di tanggal lain.
        </div>
        <?php endif; ?>

        <!-- FORM PESAN -->
        <p class="section-title"><i class="fas fa-ticket-alt me-1"></i>Form Pemesanan Tiket</p>

        <form method="POST">
            <input type="hidden" name="id_wisata" value="<?= $wisata['id_wisata'] ?>">
            <div class="row g-3">

                <!-- Field UMUM untuk semua jenis wisata -->
                <div class="col-md-6">
                    <label class="form-label">📅 Tanggal Kunjungan</label>
                    <input type="date" name="tanggal_kunjungan" class="form-control"
                           min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">🎟️ Jumlah Tiket</label>
                    <input type="number" name="jumlah_tiket" class="form-control" id="jumlahTiket"
                           min="1" max="20" value="1" oninput="updateHarga(this.value)" required>
                </div>

                <!-- ══ FIELD DINAMIS SESUAI JENIS WISATA ══ -->

                <?php if ($jenis === 'gunung'): ?>
                <!-- GUNUNG: jalur pendakian + kategori pengunjung -->
                <div class="col-md-6">
                    <label class="form-label">🥾 Jalur Pendakian</label>
                    <select name="jalur_pendakian" class="form-select" required>
                        <option value="" disabled selected>Pilih jalur...</option>
                        <option>Jalur Utama (Mudah)</option>
                        <option>Jalur Alternatif (Sedang)</option>
                        <option>Jalur Ekspedisi (Sulit)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">👥 Kategori Pengunjung</label>
                    <select name="kategori_pengunjung" class="form-select" required>
                        <option value="" disabled selected>Pilih kategori...</option>
                        <option>Pemula (belum pernah mendaki)</option>
                        <option>Menengah (1-5 kali)</option>
                        <option>Berpengalaman (5+ kali)</option>
                    </select>
                </div>

                <?php elseif ($jenis === 'pantai'): ?>
                <!-- PANTAI: aktivitas + jenis tiket -->
                <div class="col-md-6">
                    <label class="form-label">🏄 Jenis Tiket / Aktivitas</label>
                    <select name="jenis_tiket" class="form-select" required>
                        <option value="" disabled selected>Pilih aktivitas...</option>
                        <option>Tiket Masuk Biasa</option>
                        <option>Paket Snorkeling</option>
                        <option>Paket Banana Boat</option>
                        <option>Paket Lengkap (All Activity)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">⛵ Nama Kapal (jika ada)</label>
                    <input type="text" name="nama_kapal" class="form-control" placeholder="Opsional, cth: Kapal Biru">
                </div>

                <?php elseif ($jenis === 'museum'): ?>
                <!-- MUSEUM: jenis tiket + sesi -->
                <div class="col-md-6">
                    <label class="form-label">🎫 Jenis Tiket</label>
                    <select name="jenis_tiket" class="form-select" required>
                        <option value="" disabled selected>Pilih jenis...</option>
                        <option>Tiket Reguler</option>
                        <option>Tiket Pelajar / Mahasiswa</option>
                        <option>Tiket Keluarga (max 5 orang)</option>
                        <option>Tiket Grup (min 10 orang)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">🕐 Sesi Kunjungan</label>
                    <select name="kategori_pengunjung" class="form-select" required>
                        <option value="" disabled selected>Pilih sesi...</option>
                        <option>Sesi Pagi (08.00 – 11.00)</option>
                        <option>Sesi Siang (12.00 – 15.00)</option>
                        <option>Sesi Sore (15.00 – 17.00)</option>
                    </select>
                </div>

                <?php else: /* umum */ ?>
                <!-- UMUM: kategori pengunjung -->
                <div class="col-md-12">
                    <label class="form-label">👥 Kategori Pengunjung</label>
                    <select name="kategori_pengunjung" class="form-select" required>
                        <option value="" disabled selected>Pilih kategori...</option>
                        <option>Dewasa</option>
                        <option>Anak-anak (di bawah 12 tahun)</option>
                        <option>Lansia (di atas 60 tahun)</option>
                        <option>Rombongan (min 10 orang)</option>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Catatan tambahan (semua jenis) -->
                <div class="col-12">
                    <label class="form-label">📝 Catatan Tambahan <small class="opacity-50">(opsional)</small></label>
                    <textarea name="catatan" class="form-control" rows="2"
                              placeholder="cth: ada anggota yang difabel, butuh kursi roda, dll..."></textarea>
                </div>

                <!-- PREVIEW HARGA -->
                <div class="col-12">
                    <div class="p-4 rounded-4 text-center" style="background:rgba(255,255,255,0.06);border:1px dashed rgba(255,255,255,0.2);">
                        <p class="opacity-60 mb-1 small">TOTAL YANG HARUS DIBAYAR</p>
                        <div id="previewHarga">Rp<?= number_format($wisata['harga'],0,',','.') ?></div>
                        <p class="opacity-50 small mt-1" id="previewDetail">1 tiket × Rp<?= number_format($wisata['harga'],0,',','.') ?></p>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" name="beli" class="btn btn-pesan btn-primary w-100 text-white fs-5" <?= !$buka ? '' : '' ?>>
                        <i class="fas fa-ticket-alt me-2"></i>Konfirmasi & Pesan Sekarang
                    </button>
                    <p class="text-center small opacity-50 mt-2">
                        <i class="fas fa-shield-alt me-1"></i>Pemesanan aman & tersimpan di akun kamu
                    </p>
                </div>
            </div>
        </form>

    </div>
</div>

<script>
const hargaSatuan = <?= $wisata['harga'] ?>;
function updateHarga(jml) {
    jml = parseInt(jml) || 1;
    const total = hargaSatuan * jml;
    document.getElementById('previewHarga').textContent =
        'Rp' + total.toLocaleString('id-ID');
    document.getElementById('previewDetail').textContent =
        jml + ' tiket × Rp' + hargaSatuan.toLocaleString('id-ID');
}
</script>
</body>
</html>
<?php ob_end_flush(); ?>