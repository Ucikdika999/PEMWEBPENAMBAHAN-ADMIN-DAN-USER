<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$pesan_ok  = '';
$pesan_err = '';

$fasilitas_options = [
    'pantai'  => ['Parkir','Toilet','Gazebo','Penyewaan Pelampung','Warung Makan','Shower','Kamar Ganti','Snorkeling'],
    'gunung'  => ['Parkir','Toilet','Pos Pendakian','Pemandu Wisata','Area Camping','Warung','P3K','Shelter'],
    'museum'  => ['Parkir','Toilet','Ruang AC','Pemandu','Kafe','Toko Souvenir','Auditorium','WiFi'],
    'umum'    => ['Parkir','Toilet','Warung Makan','Musholla','Area Bermain','Spot Foto'],
];

if (isset($_POST['tambah'])) {
    $nama      = mysqli_real_escape_string($koneksi, $_POST['nama_wisata']);
    $harga     = (int) $_POST['harga'];
    $jenis     = mysqli_real_escape_string($koneksi, $_POST['jenis_wisata']);
    $status    = isset($_POST['status_buka']) ? 1 : 0;
    $jam_buka  = mysqli_real_escape_string($koneksi, $_POST['jam_buka']);
    $jam_tutup = mysqli_real_escape_string($koneksi, $_POST['jam_tutup']);
    $cuaca     = mysqli_real_escape_string($koneksi, $_POST['cuaca']);
    $musim     = mysqli_real_escape_string($koneksi, $_POST['musim_terbaik']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $foto_url  = mysqli_real_escape_string($koneksi, $_POST['foto_url']);
    $fasilitas = json_encode($_POST['fasilitas'] ?? []);

    $sql = "INSERT INTO destinasi (nama_wisata, harga, jenis_wisata, status_buka, jam_buka, jam_tutup, cuaca, musim_terbaik, deskripsi, foto_url, fasilitas)
            VALUES ('$nama','$harga','$jenis','$status','$jam_buka','$jam_tutup','$cuaca','$musim','$deskripsi','$foto_url','$fasilitas')";
    if (mysqli_query($koneksi, $sql)) $pesan_ok = "Destinasi berhasil ditambahkan!";
    else $pesan_err = "Gagal: " . mysqli_error($koneksi);
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM destinasi WHERE id_wisata='$id'");
    header("Location: kelola_wisata.php"); exit();
}

if (isset($_POST['edit'])) {
    $id        = (int) $_POST['id_wisata'];
    $harga     = (int) $_POST['harga'];
    $jenis     = mysqli_real_escape_string($koneksi, $_POST['jenis_wisata']);
    $status    = isset($_POST['status_buka']) ? 1 : 0;
    $jam_buka  = mysqli_real_escape_string($koneksi, $_POST['jam_buka']);
    $jam_tutup = mysqli_real_escape_string($koneksi, $_POST['jam_tutup']);
    $cuaca     = mysqli_real_escape_string($koneksi, $_POST['cuaca']);
    $musim     = mysqli_real_escape_string($koneksi, $_POST['musim_terbaik']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $foto_url  = mysqli_real_escape_string($koneksi, $_POST['foto_url']);
    $fasilitas = json_encode($_POST['fasilitas'] ?? []);

    $sql = "UPDATE destinasi SET harga='$harga', jenis_wisata='$jenis', status_buka='$status',
            jam_buka='$jam_buka', jam_tutup='$jam_tutup', cuaca='$cuaca',
            musim_terbaik='$musim', deskripsi='$deskripsi', foto_url='$foto_url', fasilitas='$fasilitas'
            WHERE id_wisata='$id'";
    if (mysqli_query($koneksi, $sql)) $pesan_ok = "Data berhasil diperbarui!";
    else $pesan_err = "Gagal: " . mysqli_error($koneksi);
}

$hasil = mysqli_query($koneksi, "SELECT * FROM destinasi ORDER BY id_wisata DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Wisata | Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body{background:#f0f2f5;font-family:'Segoe UI',sans-serif;}
.sidebar{height:100vh;width:250px;position:fixed;background:#212529;color:white;padding:20px;top:0;left:0;z-index:100;}
.content{margin-left:250px;padding:30px;}
.nav-link{padding:10px 15px;transition:0.3s;color:rgba(255,255,255,0.7)!important;border-radius:8px;margin-bottom:4px;}
.nav-link:hover{background:rgba(255,255,255,0.1);color:white!important;}
.nav-link.active{background:#0d6efd;color:white!important;}
.section-card{background:white;border-radius:20px;padding:28px;box-shadow:0 2px 16px rgba(0,0,0,0.07);margin-bottom:24px;}
.badge-pantai{background:#dbeafe;color:#1d4ed8;}
.badge-gunung{background:#dcfce7;color:#15803d;}
.badge-museum{background:#fef3c7;color:#b45309;}
.badge-umum{background:#f3e8ff;color:#7c3aed;}
.badge-jenis{padding:5px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;}
.status-buka{background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;}
.status-tutup{background:#fee2e2;color:#991b1b;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;}
.form-control,.form-select{border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;}
.form-control:focus,.form-select:focus{border-color:#0d6efd;box-shadow:none;}
.fasilitas-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;margin-top:8px;}
.fasilitas-check input{display:none;}
.fasilitas-check label{cursor:pointer;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:8px 12px;font-size:0.85rem;display:block;transition:0.2s;text-align:center;}
.fasilitas-check input:checked+label{background:#dbeafe;border-color:#3b82f6;color:#1d4ed8;font-weight:600;}
.foto-thumb{width:55px;height:45px;object-fit:cover;border-radius:8px;}
.no-foto{width:55px;height:45px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:1.2rem;}
</style>
</head>
<body>

<div class="sidebar shadow">
    <h4 class="mb-5 fw-bold text-primary text-center"><i class="fas fa-shield-halved"></i> ADMIN PANEL</h4>
    <nav class="nav flex-column">
        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a>
        <a class="nav-link active" href="kelola_wisata.php"><i class="fas fa-mountain-sun me-2"></i> Kelola Wisata</a>
        <a class="nav-link" href="kelola_user.php"><i class="fas fa-users me-2"></i> Kelola User</a>
        <a class="nav-link" href="laporan_pesanan.php"><i class="fas fa-receipt me-2"></i> Laporan Pesanan</a>
        <hr class="bg-secondary">
        <a class="nav-link text-danger fw-bold" href="logout.php"><i class="fas fa-power-off me-2"></i> Logout</a>
    </nav>
</div>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Kelola Destinasi Wisata</h2>
            <p class="text-muted small mb-0">Tambah, edit kondisi, dan kelola semua destinasi</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="collapse" data-bs-target="#formTambah">
            <i class="fas fa-plus me-2"></i>Tambah Destinasi
        </button>
    </div>

    <?php if ($pesan_ok): ?>
        <div class="alert alert-success border-0 rounded-3 fw-semibold"><i class="fas fa-check-circle me-2"></i><?= $pesan_ok ?></div>
    <?php endif; ?>
    <?php if ($pesan_err): ?>
        <div class="alert alert-danger border-0 rounded-3 fw-semibold"><i class="fas fa-exclamation-triangle me-2"></i><?= $pesan_err ?></div>
    <?php endif; ?>

    <!-- FORM TAMBAH -->
    <div class="collapse mb-4" id="formTambah">
        <div class="section-card border border-primary border-opacity-25">
            <h5 class="fw-bold mb-4"><i class="fas fa-map-marked-alt text-primary me-2"></i>Form Tambah Destinasi Baru</h5>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Nama Wisata</label>
                        <input type="text" name="nama_wisata" class="form-control" placeholder="cth: Pantai Parangtritis" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Jenis Wisata</label>
                        <select name="jenis_wisata" class="form-select" onchange="updateFasilitas('Tambah', this.value)" required>
                            <option value="umum">🏞️ Umum</option>
                            <option value="pantai">🏖️ Pantai</option>
                            <option value="gunung">🏔️ Gunung</option>
                            <option value="museum">🏛️ Museum</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control" placeholder="25000" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end pb-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status_buka" checked>
                            <label class="form-check-label fw-semibold">Sedang Buka</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Jam Buka</label>
                        <input type="time" name="jam_buka" class="form-control" value="08:00">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Jam Tutup</label>
                        <input type="time" name="jam_tutup" class="form-control" value="17:00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kondisi Cuaca</label>
                        <select name="cuaca" class="form-select">
                            <option>Cerah</option><option>Cerah Berawan</option>
                            <option>Berawan</option><option>Hujan Ringan</option><option>Hujan Lebat</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Musim Terbaik Berkunjung</label>
                        <input type="text" name="musim_terbaik" class="form-control" placeholder="cth: April - Oktober">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">URL Foto</label>
                        <input type="url" name="foto_url" class="form-control" placeholder="https://...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2" placeholder="Deskripsi singkat..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">✅ Fasilitas Tersedia <small class="text-muted fw-normal">(pilih sesuai jenis wisata)</small></label>
                        <div class="fasilitas-grid" id="fasilitasTambah"></div>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" name="tambah" class="btn btn-success px-5 fw-bold rounded-pill">
                            <i class="fas fa-plus me-2"></i>Simpan Destinasi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL -->
    <div class="section-card">
        <h5 class="fw-bold mb-4"><i class="fas fa-list text-primary me-2"></i>Daftar Destinasi</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Foto</th><th>Nama Wisata</th><th>Jenis</th><th>Harga</th>
                        <th>Status</th><th>Jam</th><th>Cuaca</th><th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $rows = [];
                while ($d = mysqli_fetch_assoc($hasil)) $rows[] = $d;
                foreach ($rows as $d):
                    $jenis = $d['jenis_wisata'] ?? 'umum';
                    $fasilitas_arr = json_decode($d['fasilitas'] ?? '[]', true) ?: [];
                    $icon_cuaca = match($d['cuaca'] ?? '') {
                        'Cerah'=>'☀️','Cerah Berawan'=>'⛅','Berawan'=>'☁️',
                        'Hujan Ringan'=>'🌦️','Hujan Lebat'=>'🌧️',default=>'🌤️'
                    };
                    $icon_jenis = match($jenis) {
                        'pantai'=>'🏖️ Pantai','gunung'=>'🏔️ Gunung','museum'=>'🏛️ Museum',default=>'🏞️ Umum'
                    };
                ?>
                <tr>
                    <td>
                        <?php if (!empty($d['foto_url'])): ?>
                            <img src="<?= htmlspecialchars($d['foto_url']) ?>" class="foto-thumb" onerror="this.parentNode.innerHTML='<div class=no-foto><i class=fas fa-image></i></div>'">
                        <?php else: ?>
                            <div class="no-foto"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($d['nama_wisata']) ?></strong>
                        <?php if (!empty($d['deskripsi'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(mb_substr($d['deskripsi'],0,45)) ?>...</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge-jenis badge-<?= $jenis ?>"><?= $icon_jenis ?></span></td>
                    <td class="fw-bold text-success">Rp<?= number_format($d['harga'],0,',','.') ?></td>
                    <td><span class="<?= $d['status_buka'] ? 'status-buka' : 'status-tutup' ?>"><?= $d['status_buka'] ? '✓ Buka' : '✗ Tutup' ?></span></td>
                    <td class="small text-muted"><?= date('H:i',strtotime($d['jam_buka']??'08:00')) ?> – <?= date('H:i',strtotime($d['jam_tutup']??'17:00')) ?></td>
                    <td><?= $icon_cuaca ?> <?= htmlspecialchars($d['cuaca']??'-') ?></td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm rounded-pill me-1"
                            data-bs-toggle="modal" data-bs-target="#modalEdit<?= $d['id_wisata'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?hapus=<?= $d['id_wisata'] ?>" class="btn btn-danger btn-sm rounded-pill"
                           onclick="return confirm('Hapus destinasi ini?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>

                <!-- MODAL EDIT -->
                <div class="modal fade" id="modalEdit<?= $d['id_wisata'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content" style="border-radius:20px;border:none;">
                            <div class="modal-header border-0">
                                <h5 class="modal-title fw-bold"><i class="fas fa-edit text-warning me-2"></i>Edit: <?= htmlspecialchars($d['nama_wisata']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST">
                                    <input type="hidden" name="id_wisata" value="<?= $d['id_wisata'] ?>">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Jenis Wisata</label>
                                            <select name="jenis_wisata" class="form-select" id="jenisEdit<?= $d['id_wisata'] ?>"
                                                onchange="updateFasilitas('Edit<?= $d['id_wisata'] ?>', this.value)">
                                                <option value="umum"   <?= $jenis=='umum'  ?'selected':'' ?>>🏞️ Umum</option>
                                                <option value="pantai" <?= $jenis=='pantai'?'selected':'' ?>>🏖️ Pantai</option>
                                                <option value="gunung" <?= $jenis=='gunung'?'selected':'' ?>>🏔️ Gunung</option>
                                                <option value="museum" <?= $jenis=='museum'?'selected':'' ?>>🏛️ Museum</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Harga Tiket</label>
                                            <input type="number" name="harga" class="form-control" value="<?= $d['harga'] ?>" required>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end pb-1">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="status_buka" <?= $d['status_buka']?'checked':'' ?>>
                                                <label class="form-check-label fw-semibold">Sedang Buka</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Jam Buka</label>
                                            <input type="time" name="jam_buka" class="form-control" value="<?= date('H:i',strtotime($d['jam_buka']??'08:00')) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Jam Tutup</label>
                                            <input type="time" name="jam_tutup" class="form-control" value="<?= date('H:i',strtotime($d['jam_tutup']??'17:00')) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Cuaca</label>
                                            <select name="cuaca" class="form-select">
                                                <?php foreach(['Cerah','Cerah Berawan','Berawan','Hujan Ringan','Hujan Lebat'] as $c): ?>
                                                    <option <?= ($d['cuaca']==$c)?'selected':'' ?>><?= $c ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Musim Terbaik</label>
                                            <input type="text" name="musim_terbaik" class="form-control" value="<?= htmlspecialchars($d['musim_terbaik']??'') ?>">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label fw-semibold">URL Foto</label>
                                            <input type="url" name="foto_url" class="form-control" value="<?= htmlspecialchars($d['foto_url']??'') ?>">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label fw-semibold">Deskripsi</label>
                                            <textarea name="deskripsi" class="form-control" rows="2"><?= htmlspecialchars($d['deskripsi']??'') ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">✅ Fasilitas</label>
                                            <div class="fasilitas-grid" id="fasilitasEdit<?= $d['id_wisata'] ?>"
                                                 data-checked='<?= htmlspecialchars(json_encode($fasilitas_arr),ENT_QUOTES) ?>'></div>
                                        </div>
                                        <div class="col-12 text-end">
                                            <button type="button" class="btn btn-secondary rounded-pill me-2" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="edit" class="btn btn-warning rounded-pill px-4 fw-bold">
                                                <i class="fas fa-save me-1"></i>Simpan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const fasilitasData = <?= json_encode($fasilitas_options) ?>;

function updateFasilitas(suffix, jenis) {
    const el = document.getElementById('fasilitas' + suffix);
    if (!el) return;
    const checked = JSON.parse(el.dataset.checked || '[]');
    const opsi = fasilitasData[jenis] || fasilitasData['umum'];
    el.innerHTML = opsi.map((f,i) => `
        <div class="fasilitas-check">
            <input type="checkbox" name="fasilitas[]" value="${f}"
                   id="fc_${suffix}_${i}" ${checked.includes(f)?'checked':''}>
            <label for="fc_${suffix}_${i}">${f}</label>
        </div>`).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    // Init form tambah
    const elT = document.getElementById('fasilitasTambah');
    if (elT) { elT.dataset.checked = '[]'; updateFasilitas('Tambah', 'umum'); }

    // Init semua form edit saat modal dibuka
    document.querySelectorAll('[id^="modalEdit"]').forEach(modal => {
        modal.addEventListener('show.bs.modal', () => {
            const id = modal.id.replace('modalEdit','');
            const jenis = document.getElementById('jenisEdit'+id)?.value || 'umum';
            updateFasilitas('Edit'+id, jenis);
        });
    });
});
</script>
</body>
</html>
<?php ob_end_flush(); ?>