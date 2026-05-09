<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$query_pesanan     = mysqli_query($koneksi, "SELECT * FROM pesanan ORDER BY id_pesanan DESC");
$total_pesanan     = mysqli_num_rows($query_pesanan);
$result_pendapatan = mysqli_query($koneksi, "SELECT SUM(harga) as total FROM pesanan");
$data_pendapatan   = mysqli_fetch_assoc($result_pendapatan);
$total_pendapatan  = $data_pendapatan['total'] ?? 0;
mysqli_data_seek($query_pesanan, 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Pesanan | Admin WISATA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f0f4f8;font-family:'Segoe UI',sans-serif;display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:240px;min-height:100vh;position:fixed;top:0;left:0;background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);display:flex;flex-direction:column;box-shadow:4px 0 20px rgba(0,0,0,0.15);z-index:100;}
.sidebar-brand{padding:24px 20px 18px;border-bottom:1px solid rgba(255,255,255,0.07);text-align:center;}
.brand-icon{width:52px;height:52px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.5rem;box-shadow:0 4px 14px rgba(59,130,246,0.45);}
.brand-title{color:white;font-weight:800;font-size:1.05rem;margin:0;}
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

/* KONTEN */
.content{margin-left:240px;padding:32px;flex:1;}
.page-title{font-size:1.6rem;font-weight:800;color:#0f172a;}
.page-sub{color:#94a3b8;font-size:0.9rem;margin-top:2px;}

/* STAT CARDS */
.stat-card{border:none;border-radius:18px;padding:22px;transition:0.3s;position:relative;overflow:hidden;}
.stat-card:hover{transform:translateY(-4px);}
.stat-card::before{content:'';position:absolute;top:-15px;right:-15px;width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,0.08);}
.stat-ico{width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:14px;}
.stat-num{font-size:1.8rem;font-weight:800;}
.stat-lbl{font-size:0.78rem;opacity:0.7;margin-top:2px;}

/* TABEL */
.table-wrap{background:white;border-radius:18px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.07);}
.table-topbar{padding:18px 22px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9;}
.table-topbar h5{font-weight:700;color:#0f172a;margin:0;font-size:1rem;}
.search-box{position:relative;}
.search-box input{border-radius:10px;border:2px solid #e2e8f0;padding:8px 14px 8px 36px;font-size:0.83rem;width:210px;outline:none;transition:0.2s;}
.search-box input:focus{border-color:#3b82f6;}
.search-box i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.78rem;}
.table thead th{background:#f8fafc;color:#64748b;font-size:0.75rem;text-transform:uppercase;font-weight:700;letter-spacing:0.5px;border:none;padding:12px 16px;}
.table tbody td{border-color:#f1f5f9;padding:13px 16px;vertical-align:middle;font-size:0.88rem;}
.table tbody tr:hover{background:#f8fafc;}
.kode{font-weight:700;color:#3b82f6;}
.badge-lunas{background:#d1fae5;color:#065f46;font-weight:700;padding:4px 12px;border-radius:20px;font-size:0.75rem;}
.user-chip{display:flex;align-items:center;gap:8px;}
.user-ava{width:28px;height:28px;background:#e0f2fe;border-radius:7px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#0284c7;font-size:0.75rem;flex-shrink:0;}
.btn-exp{border-radius:10px;font-weight:600;font-size:0.82rem;padding:8px 16px;}
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
        <a href="admin_dashboard.php" class="nav-link">
            <div class="ico"><i class="fas fa-chart-line"></i></div> Dashboard
        </a>
        <a href="kelola_wisata.php" class="nav-link">
            <div class="ico"><i class="fas fa-mountain-sun"></i></div> Kelola Wisata
        </a>
        <a href="kelola_user.php" class="nav-link">
            <div class="ico"><i class="fas fa-users"></i></div> Kelola User
        </a>
        <a href="laporan_pesanan.php" class="nav-link active">
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
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="page-title">Laporan Pesanan</div>
            <div class="page-sub">Semua transaksi tiket wisata tercatat di sini</div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="cetakHalaman()" class="btn btn-outline-secondary btn-exp">
                <i class="fas fa-print me-1"></i>Cetak
            </button>
            <button onclick="eksporCSV()" class="btn btn-success btn-exp">
                <i class="fas fa-file-csv me-1"></i>Ekspor CSV
            </button>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card bg-primary text-white shadow-sm">
                <div class="stat-ico"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-num"><?= $total_pesanan ?></div>
                <div class="stat-lbl">Total Pesanan</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-success text-white shadow-sm">
                <div class="stat-ico"><i class="fas fa-wallet"></i></div>
                <div class="stat-num" style="font-size:1.4rem;">Rp<?= number_format($total_pendapatan,0,',','.') ?></div>
                <div class="stat-lbl">Total Pendapatan</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-warning text-dark shadow-sm">
                <div class="stat-ico" style="background:rgba(0,0,0,0.1);"><i class="fas fa-tags"></i></div>
                <div class="stat-num" style="font-size:1.4rem;">Rp<?= $total_pesanan > 0 ? number_format($total_pendapatan/$total_pesanan,0,',','.') : 0 ?></div>
                <div class="stat-lbl">Rata-rata Per Tiket</div>
            </div>
        </div>
    </div>

    <!-- TABEL -->
    <div class="table-wrap">
        <div class="table-topbar">
            <h5><i class="fas fa-list text-primary me-2"></i>Daftar Semua Transaksi</h5>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="cariPesanan" placeholder="Cari username / wisata...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabelPesanan">
                <thead>
                    <tr>
                        <th>Kode</th><th>Username</th><th>Destinasi</th>
                        <th>Harga</th><th>Tanggal</th><th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($query_pesanan) > 0):
                    while ($row = mysqli_fetch_assoc($query_pesanan)): ?>
                <tr>
                    <td><span class="kode">#WDT-<?= $row['id_pesanan'] ?></span></td>
                    <td>
                        <div class="user-chip">
                            <div class="user-ava"><?= strtoupper(substr($row['username'],0,1)) ?></div>
                            <?= htmlspecialchars($row['username']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['nama_wisata']) ?></td>
                    <td><strong class="text-success">Rp<?= number_format($row['harga'],0,',','.') ?></strong></td>
                    <td class="text-muted">
                        <?= !empty($row['tanggal_pesan']) ? date('d M Y, H:i', strtotime($row['tanggal_pesan'])) : '-' ?>
                    </td>
                    <td class="text-center"><span class="badge-lunas">✓ Lunas</span></td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x d-block mb-3 opacity-25"></i>
                        Belum ada transaksi.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('cariPesanan').addEventListener('keyup', function(){
    const kw = this.value.toLowerCase();
    document.querySelectorAll('#tabelPesanan tbody tr').forEach(r=>{
        r.style.display = r.textContent.toLowerCase().includes(kw) ? '' : 'none';
    });
});
function cetakHalaman(){ window.print(); }
function eksporCSV(){
    const rows = document.querySelectorAll('#tabelPesanan tr');
    let csv = [];
    rows.forEach(r=>{
        let cols=[...r.querySelectorAll('th,td')].map(c=>'"'+c.innerText.replace(/"/g,'""')+'"');
        csv.push(cols.join(','));
    });
    const a=document.createElement('a');
    a.href=URL.createObjectURL(new Blob([csv.join('\n')],{type:'text/csv'}));
    a.download='laporan_pesanan.csv'; a.click();
}
</script>
</body>
</html>
<?php ob_end_flush(); ?>