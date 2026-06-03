<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";
include "catat_kunjungan.php";
catatKunjungan($koneksi);

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
    'alam'    => ['Parkir','Toilet','Warung Makan','Musholla','Area Bermain','Spot Foto','Shelter','Pemandu'],
    'budaya'  => ['Parkir','Toilet','Pemandu','Toko Souvenir','Area Foto','Musholla','Kafe'],
    'umum'    => ['Parkir','Toilet','Warung Makan','Musholla','Area Bermain','Spot Foto'],
];

if (isset($_POST['tambah'])) {
    $nama      = mysqli_real_escape_string($koneksi, $_POST['nama_wisata']);
    $lokasi    = mysqli_real_escape_string($koneksi, $_POST['lokasi'] ?? '');
    $harga     = (int)$_POST['harga'];
    $jenis     = mysqli_real_escape_string($koneksi, $_POST['jenis_wisata']);
    $status    = isset($_POST['status_buka']) ? 1 : 0;
    $jam_buka  = mysqli_real_escape_string($koneksi, $_POST['jam_buka']);
    $jam_tutup = mysqli_real_escape_string($koneksi, $_POST['jam_tutup']);
    $cuaca     = mysqli_real_escape_string($koneksi, $_POST['cuaca']);
    $musim     = mysqli_real_escape_string($koneksi, $_POST['musim_terbaik']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $foto_url  = mysqli_real_escape_string($koneksi, $_POST['foto_url']);
    $fasilitas = json_encode($_POST['fasilitas'] ?? []);

    $sql = "INSERT INTO destinasi (nama_wisata, lokasi, harga, jenis_wisata, status_buka, jam_buka, jam_tutup, cuaca, musim_terbaik, deskripsi, foto_url, fasilitas)
            VALUES ('$nama','$lokasi','$harga','$jenis','$status','$jam_buka','$jam_tutup','$cuaca','$musim','$deskripsi','$foto_url','$fasilitas')";
    if (mysqli_query($koneksi, $sql)) { $pesan_ok = "Destinasi berhasil ditambahkan!"; }
    else $pesan_err = "Gagal: " . mysqli_error($koneksi);
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM destinasi WHERE id_wisata='$id'");
    header("Location: kelola_wisata.php"); exit();
}

if (isset($_POST['edit'])) {
    $id        = (int)$_POST['id_wisata'];
    $lokasi    = mysqli_real_escape_string($koneksi, $_POST['lokasi'] ?? '');
    $harga     = (int)$_POST['harga'];
    $jenis     = mysqli_real_escape_string($koneksi, $_POST['jenis_wisata']);
    $status    = isset($_POST['status_buka']) ? 1 : 0;
    $jam_buka  = mysqli_real_escape_string($koneksi, $_POST['jam_buka']);
    $jam_tutup = mysqli_real_escape_string($koneksi, $_POST['jam_tutup']);
    $cuaca     = mysqli_real_escape_string($koneksi, $_POST['cuaca']);
    $musim     = mysqli_real_escape_string($koneksi, $_POST['musim_terbaik']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $foto_url  = mysqli_real_escape_string($koneksi, $_POST['foto_url']);
    $fasilitas = json_encode($_POST['fasilitas'] ?? []);

    $sql = "UPDATE destinasi SET lokasi='$lokasi', harga='$harga', jenis_wisata='$jenis', status_buka='$status',
            jam_buka='$jam_buka', jam_tutup='$jam_tutup', cuaca='$cuaca', musim_terbaik='$musim',
            deskripsi='$deskripsi', foto_url='$foto_url', fasilitas='$fasilitas' WHERE id_wisata='$id'";
    if (mysqli_query($koneksi, $sql)) $pesan_ok = "Data berhasil diperbarui!";
    else $pesan_err = "Gagal: " . mysqli_error($koneksi);
}

$search = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, $_GET['q']) : '';
$filter_kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';

$where = "WHERE 1=1";
if ($search) $where .= " AND (nama_wisata LIKE '%$search%' OR lokasi LIKE '%$search%')";
if ($filter_kategori) $where .= " AND jenis_wisata='$filter_kategori'";

$per_page = 10;
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$q_count = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM destinasi $where");
$total = mysqli_fetch_assoc($q_count)['c'];
$total_pages = ceil($total / $per_page);

$hasil = mysqli_query($koneksi, "SELECT * FROM destinasi $where ORDER BY id_wisata DESC LIMIT $per_page OFFSET $offset");
$all_rows = [];
while ($d = mysqli_fetch_assoc($hasil)) $all_rows[] = $d;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Wisata | WISATA Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
:root{
    --sidebar-bg:#0f172a;--accent:#2563eb;--border:#e2e8f0;
    --surface:#fff;--bg:#f1f5f9;--text-primary:#0f172a;--text-muted:#64748b;
    --success:#10b981;--warning:#f59e0b;--danger:#ef4444;--sidebar-w:240px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text-primary);display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);min-height:100vh;position:fixed;top:0;left:0;background:var(--sidebar-bg);display:flex;flex-direction:column;z-index:200;}
.sb-brand{padding:22px 18px 16px;border-bottom:1px solid rgba(255,255,255,0.06);}
.sb-logo{display:flex;align-items:center;gap:10px;}
.sb-icon{width:40px;height:40px;background:var(--accent);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.sb-title{color:#fff;font-weight:800;font-size:1rem;line-height:1.1;}
.sb-sub{color:rgba(255,255,255,0.35);font-size:0.68rem;font-weight:500;}
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
.admin-ava{width:32px;height:32px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;flex-shrink:0;}
.admin-name{color:#fff;font-size:.8rem;font-weight:600;}
.admin-role{color:rgba(255,255,255,.3);font-size:.65rem;}

/* MAIN */
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 30px;}
.page-title{font-size:1.45rem;font-weight:800;margin-bottom:2px;}
.page-sub{color:var(--text-muted);font-size:.85rem;margin-bottom:22px;}

/* ALERTS */
.alert-ok{background:#d1fae5;color:#065f46;border-radius:10px;padding:12px 18px;font-size:.85rem;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.alert-err{background:#fee2e2;color:#991b1b;border-radius:10px;padding:12px 18px;font-size:.85rem;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px;}

/* TOOLBAR */
.toolbar{background:var(--surface);border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:16px;}
.search-wrap{position:relative;flex:1;min-width:200px;}
.search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem;}
.search-wrap input{width:100%;border:1.5px solid var(--border);border-radius:9px;padding:8px 12px 8px 34px;font-size:.83rem;font-family:inherit;outline:none;transition:.2s;}
.search-wrap input:focus{border-color:var(--accent);}
.filter-select{border:1.5px solid var(--border);border-radius:9px;padding:8px 14px;font-size:.83rem;font-family:inherit;outline:none;cursor:pointer;color:var(--text-primary);}
.btn-primary-c{background:var(--accent);color:#fff;border:none;border-radius:9px;padding:8px 18px;font-size:.83rem;font-weight:600;font-family:inherit;cursor:pointer;display:flex;align-items:center;gap:7px;transition:.18s;white-space:nowrap;text-decoration:none;}
.btn-primary-c:hover{background:#1d4ed8;color:#fff;}

/* TABLE */
.table-card{background:var(--surface);border-radius:14px;box-shadow:0 1px 8px rgba(0,0,0,.06);overflow:hidden;}
table{width:100%;border-collapse:collapse;}
thead th{background:#f8fafc;color:var(--text-muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;font-weight:700;padding:12px 16px;border-bottom:1px solid var(--border);white-space:nowrap;}
tbody td{padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:.875rem;vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover td{background:#f8fafc;}
.foto-thumb{width:60px;height:48px;object-fit:cover;border-radius:8px;}
.no-foto{width:60px;height:48px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:1.1rem;}

/* BADGES */
.badge-kat{padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:700;display:inline-block;}
.kat-alam   {background:#d1fae5;color:#065f46;}
.kat-pantai {background:#dbeafe;color:#1d4ed8;}
.kat-gunung {background:#f0fdf4;color:#166534;}
.kat-museum {background:#fef3c7;color:#b45309;}
.kat-budaya {background:#ede9fe;color:#7c3aed;}
.kat-umum   {background:#f1f5f9;color:#475569;}
.status-buka {background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;display:inline-flex;align-items:center;gap:5px;}
.status-tutup{background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;display:inline-flex;align-items:center;gap:5px;}
.dot{width:5px;height:5px;border-radius:50%;display:inline-block;}
.dot-g{background:#16a34a;}.dot-r{background:#dc2626;}
.btn-act{width:30px;height:30px;border-radius:7px;border:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;transition:.15s;}
.btn-edit{background:#fef3c7;color:#d97706;}.btn-edit:hover{background:#fde68a;}
.btn-del{background:#fee2e2;color:#dc2626;}.btn-del:hover{background:#fecaca;}

/* PAGINATION */
.pagination-wrap{padding:14px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.pag-info{color:var(--text-muted);font-size:.8rem;}
.pag-btns{display:flex;gap:4px;}
.pag-btn{width:32px;height:32px;border-radius:7px;border:1.5px solid var(--border);background:#fff;color:var(--text-primary);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.8rem;text-decoration:none;transition:.15s;}
.pag-btn:hover{border-color:var(--accent);color:var(--accent);}
.pag-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;}
.empty-state{padding:60px 20px;text-align:center;color:var(--text-muted);}
.empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px;}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:500;align-items:flex-start;justify-content:center;overflow-y:auto;padding:30px 0;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:18px;padding:28px;width:100%;max-width:620px;animation:mIn .2s ease;margin:auto;}
@keyframes mIn{from{transform:scale(.95);opacity:0}to{transform:scale(1);opacity:1}}
.modal-title{font-weight:800;font-size:1.05rem;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:5px;}
.form-group input,.form-group select,.form-group textarea{width:100%;border:1.5px solid var(--border);border-radius:9px;padding:9px 13px;font-size:.85rem;font-family:inherit;outline:none;transition:.2s;resize:none;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--accent);}
.form-full{grid-column:1/-1;}
.fasilitas-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:7px;margin-top:6px;}
.fc input{display:none;}
.fc label{cursor:pointer;background:#f8fafc;border:1.5px solid var(--border);border-radius:8px;padding:6px 10px;font-size:.78rem;display:block;transition:.18s;text-align:center;}
.fc input:checked+label{background:#dbeafe;border-color:#3b82f6;color:#1d4ed8;font-weight:600;}
.toggle-row{display:flex;align-items:center;gap:8px;font-size:.84rem;font-weight:500;}
.toggle-row input[type=checkbox]{width:16px;height:16px;cursor:pointer;accent-color:var(--accent);}
.modal-footer{display:flex;gap:8px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);}
.btn-cancel{background:#f1f5f9;color:var(--text-muted);border:none;border-radius:9px;padding:9px 20px;font-family:inherit;font-size:.83rem;font-weight:600;cursor:pointer;}
.btn-save{background:var(--accent);color:#fff;border:none;border-radius:9px;padding:9px 22px;font-family:inherit;font-size:.83rem;font-weight:600;cursor:pointer;}
.btn-save-warn{background:var(--warning);color:#fff;}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sb-brand">
        <div class="sb-logo">
            <div class="sb-icon">🏔️</div>
            <div><div class="sb-title">WISATA</div><div class="sb-sub">Admin Panel</div></div>
        </div>
    </div>
    <div class="sb-menu">
        <div class="menu-section">Menu Utama</div>
        <a href="admin_dashboard.php" class="nav-item"><div class="ni-icon"><i class="fas fa-chart-line"></i></div> Dashboard</a>
        <a href="kelola_wisata.php" class="nav-item active"><div class="ni-icon"><i class="fas fa-mountain-sun"></i></div> Kelola Wisata</a>
        <a href="kelola_user.php" class="nav-item"><div class="ni-icon"><i class="fas fa-users"></i></div> Kelola User</a>
        <a href="laporan_pesanan.php" class="nav-item"><div class="ni-icon"><i class="fas fa-receipt"></i></div> Laporan Pesanan</a>
        <div class="menu-section" style="margin-top:6px;">Akun</div>
        <a href="profil.php" class="nav-item"><div class="ni-icon"><i class="fas fa-user-circle"></i></div> Profil Saya</a>
    </div>
    <div class="sb-footer">
        <div class="admin-tag">
            <div class="admin-ava"><?= strtoupper(substr($_SESSION['user'],0,1)) ?></div>
            <div><div class="admin-name"><?= htmlspecialchars($_SESSION['user']) ?></div><div class="admin-role">Administrator</div></div>
        </div>
        <a href="logout.php" class="nav-item logout"><div class="ni-icon"><i class="fas fa-power-off"></i></div> Logout</a>
    </div>
</aside>

<main class="main">
    <div class="page-title">Kelola Wisata</div>
    <div class="page-sub">Kelola data destinasi wisata yang tersedia</div>

    <?php if($pesan_ok): ?><div class="alert-ok"><i class="fas fa-check-circle"></i><?= $pesan_ok ?></div><?php endif; ?>
    <?php if($pesan_err): ?><div class="alert-err"><i class="fas fa-exclamation-circle"></i><?= $pesan_err ?></div><?php endif; ?>

    <!-- TOOLBAR -->
    <form method="GET" class="toolbar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari destinasi wisata...">
        </div>
        <select name="kategori" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Kategori</option>
            <option value="alam"   <?= $filter_kategori=='alam'?'selected':'' ?>>🌿 Alam</option>
            <option value="pantai" <?= $filter_kategori=='pantai'?'selected':'' ?>>🏖️ Pantai</option>
            <option value="gunung" <?= $filter_kategori=='gunung'?'selected':'' ?>>🏔️ Gunung</option>
            <option value="museum" <?= $filter_kategori=='museum'?'selected':'' ?>>🏛️ Museum</option>
            <option value="budaya" <?= $filter_kategori=='budaya'?'selected':'' ?>>🎭 Budaya</option>
            <option value="umum"   <?= $filter_kategori=='umum'?'selected':'' ?>>🏞️ Umum</option>
        </select>
        <button type="submit" class="btn-primary-c" style="background:#64748b;"><i class="fas fa-search"></i></button>
        <button type="button" class="btn-primary-c" onclick="document.getElementById('modalTambah').classList.add('show')">
            <i class="fas fa-plus"></i> Tambah Wisata
        </button>
    </form>

    <!-- TABLE -->
    <div class="table-card">
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th style="padding-left:20px;">No</th>
                    <th>Gambar</th>
                    <th>Nama Wisata</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Jam Operasional</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $jenis_labels = [
                'alam'=>['label'=>'Alam','cls'=>'kat-alam'],
                'pantai'=>['label'=>'Pantai','cls'=>'kat-pantai'],
                'gunung'=>['label'=>'Gunung','cls'=>'kat-gunung'],
                'museum'=>['label'=>'Museum','cls'=>'kat-museum'],
                'budaya'=>['label'=>'Budaya','cls'=>'kat-budaya'],
                'umum'=>['label'=>'Umum','cls'=>'kat-umum'],
            ];
            if (count($all_rows) > 0):
                foreach ($all_rows as $i => $d):
                    $jenis = $d['jenis_wisata'] ?? 'umum';
                    $jl = $jenis_labels[$jenis] ?? ['label'=>ucfirst($jenis),'cls'=>'kat-umum'];
                    $fasilitas_arr = json_decode($d['fasilitas'] ?? '[]', true) ?: [];
                    $jam_buka  = date('H:i', strtotime($d['jam_buka'] ?? '08:00'));
                    $jam_tutup = date('H:i', strtotime($d['jam_tutup'] ?? '17:00'));
            ?>
            <tr>
                <td style="padding-left:20px;color:var(--text-muted);"><?= $offset + $i + 1 ?></td>
                <td>
                    <?php if (!empty($d['foto_url'])): ?>
                        <img src="<?= htmlspecialchars($d['foto_url']) ?>" class="foto-thumb"
                             onerror="this.parentNode.innerHTML='<div class=no-foto><i class=fas\ fa-image></i></div>'">
                    <?php else: ?>
                        <div class="no-foto"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($d['nama_wisata']) ?></div>
                    <?php if(!empty($d['lokasi'])): ?>
                    <div style="font-size:.75rem;color:var(--text-muted);display:flex;align-items:center;gap:4px;margin-top:2px;">
                        <i class="fas fa-map-marker-alt" style="font-size:.65rem;"></i><?= htmlspecialchars($d['lokasi']) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td><span class="badge-kat <?= $jl['cls'] ?>"><?= $jl['label'] ?></span></td>
                <td style="font-weight:700;color:#0f172a;">Rp<?= number_format($d['harga'],0,',','.') ?></td>
                <td style="color:var(--text-muted);font-size:.82rem;">
                    <i class="fas fa-clock" style="font-size:.7rem;margin-right:4px;"></i>
                    <?= $jam_buka ?> – <?= $jam_tutup ?> WIB
                </td>
                <td>
                    <?php if($d['status_buka']): ?>
                        <span class="status-buka"><span class="dot dot-g"></span>Aktif</span>
                    <?php else: ?>
                        <span class="status-tutup"><span class="dot dot-r"></span>Nonaktif</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:5px;justify-content:center;">
                        <button type="button" class="btn-act btn-edit"
                            onclick="openEditModal(<?= htmlspecialchars(json_encode($d),ENT_QUOTES) ?>)"
                            title="Edit"><i class="fas fa-edit"></i></button>
                        <a href="?hapus=<?= $d['id_wisata'] ?>" class="btn-act btn-del"
                           onclick="return confirm('Hapus destinasi ini?')" title="Hapus">
                           <i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="8">
                <div class="empty-state"><i class="fas fa-mountain-sun"></i>Tidak ada destinasi ditemukan.</div>
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
        <div class="pagination-wrap">
            <div class="pag-info">Menampilkan <?= min($offset+1,$total) ?>–<?= min($offset+$per_page,$total) ?> dari <?= $total ?> data</div>
            <div class="pag-btns">
                <?php if($page>1): ?><a href="?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>&kategori=<?= urlencode($filter_kategori) ?>" class="pag-btn"><i class="fas fa-chevron-left" style="font-size:.7rem;"></i></a><?php endif; ?>
                <?php for($p=1;$p<=$total_pages;$p++): ?><a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&kategori=<?= urlencode($filter_kategori) ?>" class="pag-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a><?php endfor; ?>
                <?php if($page<$total_pages): ?><a href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>&kategori=<?= urlencode($filter_kategori) ?>" class="pag-btn"><i class="fas fa-chevron-right" style="font-size:.7rem;"></i></a><?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- MODAL TAMBAH -->
<div class="modal-overlay" id="modalTambah">
<div class="modal-box">
    <div class="modal-title"><i class="fas fa-plus-circle" style="color:var(--accent);"></i> Tambah Destinasi Baru</div>
    <form method="POST">
        <div class="form-row">
            <div class="form-group form-full">
                <label>Nama Wisata</label>
                <input type="text" name="nama_wisata" placeholder="contoh: Pantai Parangtritis" required>
            </div>
            <div class="form-group form-full">
                <label>Lokasi</label>
                <input type="text" name="lokasi" placeholder="contoh: Wonosobo, Jawa Tengah">
            </div>
            <div class="form-group">
                <label>Jenis / Kategori</label>
                <select name="jenis_wisata" onchange="updateFasilitas('Tambah', this.value)" required>
                    <option value="alam">🌿 Alam</option>
                    <option value="pantai">🏖️ Pantai</option>
                    <option value="gunung">🏔️ Gunung</option>
                    <option value="museum">🏛️ Museum</option>
                    <option value="budaya">🎭 Budaya</option>
                    <option value="umum">🏞️ Umum</option>
                </select>
            </div>
            <div class="form-group">
                <label>Harga Tiket (Rp)</label>
                <input type="number" name="harga" placeholder="25000" required>
            </div>
            <div class="form-group">
                <label>Jam Buka</label>
                <input type="time" name="jam_buka" value="08:00">
            </div>
            <div class="form-group">
                <label>Jam Tutup</label>
                <input type="time" name="jam_tutup" value="17:00">
            </div>
            <div class="form-group">
                <label>Kondisi Cuaca</label>
                <select name="cuaca">
                    <option>Cerah</option><option>Cerah Berawan</option>
                    <option>Berawan</option><option>Hujan Ringan</option><option>Hujan Lebat</option>
                </select>
            </div>
            <div class="form-group">
                <label>Musim Terbaik</label>
                <input type="text" name="musim_terbaik" placeholder="April – Oktober">
            </div>
            <div class="form-group form-full">
                <label>URL Foto</label>
                <input type="url" name="foto_url" placeholder="https://...">
            </div>
            <div class="form-group form-full">
                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="2" placeholder="Deskripsi singkat destinasi..."></textarea>
            </div>
            <div class="form-group form-full">
                <div class="toggle-row">
                    <input type="checkbox" name="status_buka" checked id="sbTambah">
                    <label for="sbTambah">Destinasi sedang Aktif / Buka</label>
                </div>
            </div>
            <div class="form-group form-full">
                <label>Fasilitas Tersedia</label>
                <div class="fasilitas-grid" id="fasilitasTambah" data-checked="[]"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="document.getElementById('modalTambah').classList.remove('show')">Batal</button>
            <button type="submit" name="tambah" class="btn-save"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
</div>

<!-- MODAL EDIT (dynamic) -->
<div class="modal-overlay" id="modalEdit">
<div class="modal-box">
    <div class="modal-title"><i class="fas fa-edit" style="color:var(--warning);"></i> Edit Destinasi</div>
    <form method="POST" id="formEdit">
        <input type="hidden" name="id_wisata" id="editId">
        <div class="form-row">
            <div class="form-group form-full">
                <label>Lokasi</label>
                <input type="text" name="lokasi" id="editLokasi" placeholder="contoh: Lombok, NTB">
            </div>
            <div class="form-group">
                <label>Jenis / Kategori</label>
                <select name="jenis_wisata" id="editJenis" onchange="updateFasilitas('Edit', this.value)">
                    <option value="alam">🌿 Alam</option>
                    <option value="pantai">🏖️ Pantai</option>
                    <option value="gunung">🏔️ Gunung</option>
                    <option value="museum">🏛️ Museum</option>
                    <option value="budaya">🎭 Budaya</option>
                    <option value="umum">🏞️ Umum</option>
                </select>
            </div>
            <div class="form-group">
                <label>Harga Tiket (Rp)</label>
                <input type="number" name="harga" id="editHarga" required>
            </div>
            <div class="form-group">
                <label>Jam Buka</label>
                <input type="time" name="jam_buka" id="editJamBuka">
            </div>
            <div class="form-group">
                <label>Jam Tutup</label>
                <input type="time" name="jam_tutup" id="editJamTutup">
            </div>
            <div class="form-group">
                <label>Kondisi Cuaca</label>
                <select name="cuaca" id="editCuaca">
                    <option>Cerah</option><option>Cerah Berawan</option>
                    <option>Berawan</option><option>Hujan Ringan</option><option>Hujan Lebat</option>
                </select>
            </div>
            <div class="form-group">
                <label>Musim Terbaik</label>
                <input type="text" name="musim_terbaik" id="editMusim">
            </div>
            <div class="form-group form-full">
                <label>URL Foto</label>
                <input type="url" name="foto_url" id="editFoto">
            </div>
            <div class="form-group form-full">
                <label>Deskripsi</label>
                <textarea name="deskripsi" id="editDeskripsi" rows="2"></textarea>
            </div>
            <div class="form-group form-full">
                <div class="toggle-row">
                    <input type="checkbox" name="status_buka" id="editStatus">
                    <label for="editStatus">Destinasi sedang Aktif / Buka</label>
                </div>
            </div>
            <div class="form-group form-full">
                <label>Fasilitas Tersedia</label>
                <div class="fasilitas-grid" id="fasilitasEdit" data-checked="[]"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="document.getElementById('modalEdit').classList.remove('show')">Batal</button>
            <button type="submit" name="edit" class="btn-save btn-save-warn"><i class="fas fa-save"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const fasilitasData = <?= json_encode($fasilitas_options) ?>;

function updateFasilitas(suffix, jenis) {
    const el = document.getElementById('fasilitas' + suffix);
    if (!el) return;
    let checked = [];
    try { checked = JSON.parse(el.dataset.checked || '[]'); } catch(e){}
    const opsi = fasilitasData[jenis] || fasilitasData['umum'];
    el.innerHTML = opsi.map((f,i) => `
        <div class="fc">
            <input type="checkbox" name="fasilitas[]" value="${f}" id="fc_${suffix}_${i}" ${checked.includes(f)?'checked':''}>
            <label for="fc_${suffix}_${i}">${f}</label>
        </div>`).join('');
}

function openEditModal(d) {
    document.getElementById('editId').value = d.id_wisata;
    document.getElementById('editLokasi').value = d.lokasi || '';
    document.getElementById('editHarga').value = d.harga;
    document.getElementById('editJamBuka').value = (d.jam_buka || '08:00').substring(0,5);
    document.getElementById('editJamTutup').value = (d.jam_tutup || '17:00').substring(0,5);
    document.getElementById('editCuaca').value = d.cuaca || 'Cerah';
    document.getElementById('editMusim').value = d.musim_terbaik || '';
    document.getElementById('editFoto').value = d.foto_url || '';
    document.getElementById('editDeskripsi').value = d.deskripsi || '';
    document.getElementById('editStatus').checked = d.status_buka == 1;
    const jenis = d.jenis_wisata || 'umum';
    document.getElementById('editJenis').value = jenis;
    let fas = [];
    try { fas = JSON.parse(d.fasilitas || '[]'); } catch(e){}
    const el = document.getElementById('fasilitasEdit');
    el.dataset.checked = JSON.stringify(fas);
    updateFasilitas('Edit', jenis);
    document.getElementById('modalEdit').classList.add('show');
}

document.addEventListener('DOMContentLoaded', () => {
    const elT = document.getElementById('fasilitasTambah');
    if (elT) { elT.dataset.checked = '[]'; updateFasilitas('Tambah', 'alam'); }
});

document.querySelectorAll('.modal-overlay').forEach(mo => {
    mo.addEventListener('click', e => { if(e.target === mo) mo.classList.remove('show'); });
});
</script>
</body>
</html>
<?php ob_end_flush(); ?>