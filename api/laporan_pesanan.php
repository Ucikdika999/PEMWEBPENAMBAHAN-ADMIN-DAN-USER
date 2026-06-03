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

// Filter tanggal & status
$dari    = isset($_GET['dari'])   ? $_GET['dari']   : '';
$sampai  = isset($_GET['sampai']) ? $_GET['sampai'] : '';
$f_status= isset($_GET['status']) ? $_GET['status'] : '';

$where = "WHERE 1=1";
if ($dari && $sampai) {
    $d = mysqli_real_escape_string($koneksi, $dari);
    $s = mysqli_real_escape_string($koneksi, $sampai);
    $where .= " AND DATE(tanggal_pesan) BETWEEN '$d' AND '$s'";
}

// Stats
$q_total  = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM pesanan $where");
$total_pesanan = mysqli_fetch_assoc($q_total)['c'];

$q_pend   = mysqli_query($koneksi, "SELECT SUM(harga) as t FROM pesanan $where");
$total_pendapatan = mysqli_fetch_assoc($q_pend)['t'] ?? 0;

$q_selesai = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM pesanan $where AND status='selesai'");
$total_selesai = mysqli_fetch_assoc($q_selesai)['c'] ?? 0;

$q_pending = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM pesanan $where AND status='pending'");
$total_pending = mysqli_fetch_assoc($q_pending)['c'] ?? 0;

$pct_selesai = $total_pesanan > 0 ? round($total_selesai/$total_pesanan*100) : 0;
$pct_pending = $total_pesanan > 0 ? round($total_pending/$total_pesanan*100) : 0;

// Table query with status filter
$where2 = $where;
if ($f_status) {
    $fs = mysqli_real_escape_string($koneksi, $f_status);
    $where2 .= " AND status='$fs'";
}

$per_page = 10;
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$q_count = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM pesanan $where2");
$total_filtered = mysqli_fetch_assoc($q_count)['c'];
$total_pages = ceil($total_filtered / $per_page);

$query_pesanan = mysqli_query($koneksi, "SELECT * FROM pesanan $where2 ORDER BY id_pesanan DESC LIMIT $per_page OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Pesanan | WISATA Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
:root {
    --sidebar-bg:#0f172a; --accent:#2563eb; --border:#e2e8f0;
    --surface:#fff; --bg:#f1f5f9; --text-primary:#0f172a; --text-muted:#64748b;
    --success:#10b981; --warning:#f59e0b; --danger:#ef4444; --sidebar-w:240px;
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
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;}
.page-title{font-size:1.45rem;font-weight:800;}
.page-sub{color:var(--text-muted);font-size:.85rem;margin-top:2px;}

/* STAT CARDS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;}
.stat-card{background:var(--surface);border-radius:14px;padding:20px;box-shadow:0 1px 8px rgba(0,0,0,.06);position:relative;overflow:hidden;}
.stat-card::after{content:'';position:absolute;top:-10px;right:-10px;width:60px;height:60px;border-radius:50%;background:rgba(0,0,0,.03);}
.stat-ico{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;margin-bottom:14px;}
.stat-ico-blue{background:#dbeafe;color:var(--accent);}
.stat-ico-green{background:#d1fae5;color:#059669;}
.stat-ico-check{background:#cffafe;color:#0891b2;}
.stat-ico-warn{background:#fef3c7;color:#d97706;}
.stat-num{font-size:1.6rem;font-weight:800;line-height:1;}
.stat-num-sm{font-size:1.2rem;font-weight:800;line-height:1;}
.stat-lbl{font-size:.75rem;color:var(--text-muted);margin-top:4px;}
.stat-pct{font-size:.75rem;color:var(--text-muted);margin-top:6px;}

/* TOOLBAR */
.toolbar{background:var(--surface);border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:16px;}
.toolbar label{font-size:.78rem;color:var(--text-muted);font-weight:600;white-space:nowrap;}
.toolbar input[type=date]{border:1.5px solid var(--border);border-radius:9px;padding:7px 12px;font-size:.83rem;font-family:inherit;outline:none;transition:.2s;}
.toolbar input[type=date]:focus{border-color:var(--accent);}
.filter-select{border:1.5px solid var(--border);border-radius:9px;padding:7px 12px;font-size:.83rem;font-family:inherit;outline:none;cursor:pointer;color:var(--text-primary);}
.btn-filter{background:var(--accent);color:#fff;border:none;border-radius:9px;padding:8px 16px;font-size:.83rem;font-weight:600;font-family:inherit;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.18s;}
.btn-filter:hover{background:#1d4ed8;}
.btn-exp{background:#f1f5f9;color:var(--text-primary);border:none;border-radius:9px;padding:8px 16px;font-size:.83rem;font-weight:600;font-family:inherit;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.18s;white-space:nowrap;}
.btn-exp:hover{background:#e2e8f0;}
.btn-exp-green{background:#d1fae5;color:#065f46;}
.btn-exp-green:hover{background:#a7f3d0;}
.spacer{flex:1;}

/* TABLE */
.table-card{background:var(--surface);border-radius:14px;box-shadow:0 1px 8px rgba(0,0,0,.06);overflow:hidden;}
.table-head-bar{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.table-head-bar h5{font-weight:700;font-size:.95rem;display:flex;align-items:center;gap:8px;}
table{width:100%;border-collapse:collapse;}
thead th{background:#f8fafc;color:var(--text-muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;font-weight:700;padding:12px 16px;border-bottom:1px solid var(--border);white-space:nowrap;}
tbody td{padding:13px 16px;border-bottom:1px solid #f1f5f9;font-size:.875rem;vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover td{background:#f8fafc;}
.kode{font-weight:700;color:var(--accent);}
.user-chip{display:flex;align-items:center;gap:8px;}
.user-ava{width:28px;height:28px;background:#dbeafe;border-radius:7px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#1d4ed8;font-size:.75rem;flex-shrink:0;}
.badge-selesai{background:#d1fae5;color:#065f46;padding:4px 11px;border-radius:20px;font-size:.72rem;font-weight:700;}
.badge-pending{background:#fef3c7;color:#b45309;padding:4px 11px;border-radius:20px;font-size:.72rem;font-weight:700;}
.badge-batal{background:#fee2e2;color:#991b1b;padding:4px 11px;border-radius:20px;font-size:.72rem;font-weight:700;}
.btn-view{width:28px;height:28px;border-radius:7px;border:none;background:#dbeafe;color:#2563eb;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:.75rem;transition:.15s;}
.btn-view:hover{background:#bfdbfe;}
.pagination-wrap{padding:14px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.pag-info{color:var(--text-muted);font-size:.8rem;}
.pag-btns{display:flex;gap:4px;}
.pag-btn{width:32px;height:32px;border-radius:7px;border:1.5px solid var(--border);background:#fff;color:var(--text-primary);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.8rem;text-decoration:none;transition:.15s;}
.pag-btn:hover{border-color:var(--accent);color:var(--accent);}
.pag-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;}
.empty-state{padding:60px 20px;text-align:center;color:var(--text-muted);}
.empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px;}
.action-btns{display:flex;gap:8px;flex-wrap:wrap;}
@media print{.sidebar,.toolbar,.action-btns,.pagination-wrap{display:none!important;}.main{margin-left:0!important;padding:10px!important;}}
</style>
</head>
<body>

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
        <a href="laporan_pesanan.php" class="nav-item active">
            <div class="ni-icon"><i class="fas fa-receipt"></i></div> Laporan Pesanan
        </a>
        <div class="menu-section" style="margin-top:6px;">Akun</div>
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

<main class="main">
    <div class="page-header">
        <div>
            <div class="page-title">Laporan Pesanan</div>
            <div class="page-sub">Lihat dan kelola semua transaksi pemesanan tiket</div>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-ico stat-ico-blue"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-num"><?= $total_pesanan ?></div>
            <div class="stat-lbl">Total Pesanan</div>
            <div class="stat-pct">Semua transaksi</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico stat-ico-green"><i class="fas fa-wallet"></i></div>
            <div class="stat-num-sm">Rp<?= number_format($total_pendapatan,0,',','.') ?></div>
            <div class="stat-lbl">Total Pendapatan</div>
            <div class="stat-pct">Dari semua pesanan</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico stat-ico-check"><i class="fas fa-check-circle"></i></div>
            <div class="stat-num"><?= $total_selesai ?></div>
            <div class="stat-lbl">Pesanan Selesai</div>
            <div class="stat-pct"><?= $pct_selesai ?>% dari total</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico stat-ico-warn"><i class="fas fa-clock"></i></div>
            <div class="stat-num"><?= $total_pending ?></div>
            <div class="stat-lbl">Pesanan Pending</div>
            <div class="stat-pct"><?= $pct_pending ?>% dari total</div>
        </div>
    </div>

    <!-- TOOLBAR FILTER -->
    <form method="GET" class="toolbar">
        <label>Dari Tanggal</label>
        <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>">
        <label>Sampai Tanggal</label>
        <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
        <select name="status" class="filter-select">
            <option value="">Semua Status</option>
            <option value="selesai" <?= $f_status=='selesai'?'selected':'' ?>>Selesai</option>
            <option value="pending" <?= $f_status=='pending'?'selected':'' ?>>Pending</option>
            <option value="batal"   <?= $f_status=='batal'?'selected':'' ?>>Batal</option>
        </select>
        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
        <a href="laporan_pesanan.php" class="btn-exp">Reset</a>
        <div class="spacer"></div>
        <button type="button" class="btn-exp" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
        <button type="button" class="btn-exp btn-exp-green" onclick="eksporCSV()"><i class="fas fa-file-csv"></i> Ekspor CSV</button>
    </form>

    <!-- TABLE -->
    <div class="table-card">
        <div class="table-head-bar">
            <h5><i class="fas fa-list" style="color:var(--accent);"></i> Daftar Semua Transaksi</h5>
            <span style="font-size:.8rem;color:var(--text-muted);"><?= $total_filtered ?> transaksi ditemukan</span>
        </div>
        <div style="overflow-x:auto;">
        <table id="tabelPesanan">
            <thead>
                <tr>
                    <th style="padding-left:20px;">No</th>
                    <th>Kode Pesanan</th>
                    <th>User</th>
                    <th>Wisata</th>
                    <th>Tanggal</th>
                    <th>Jumlah Tiket</th>
                    <th>Total</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = $offset + 1;
            if (mysqli_num_rows($query_pesanan) > 0):
                while ($row = mysqli_fetch_assoc($query_pesanan)):
                    $status_badge = match($row['status'] ?? 'selesai') {
                        'pending' => '<span class="badge-pending">⏳ Menunggu</span>',
                        'batal'   => '<span class="badge-batal">✗ Batal</span>',
                        default   => '<span class="badge-selesai">✓ Selesai</span>',
                    };
                    $tgl = !empty($row['tanggal_pesan']) ? date('d M Y', strtotime($row['tanggal_pesan'])) : '-';
            ?>
            <tr>
                <td style="padding-left:20px;color:var(--text-muted);"><?= $no++ ?></td>
                <td><span class="kode">#WDT-<?= str_pad($row['id_pesanan'],4,'0',STR_PAD_LEFT) ?></span></td>
                <td>
                    <div class="user-chip">
                        <div class="user-ava"><?= strtoupper(substr($row['username'],0,1)) ?></div>
                        <?= htmlspecialchars($row['username']) ?>
                    </div>
                </td>
                <td><?= htmlspecialchars($row['nama_wisata']) ?></td>
                <td style="color:var(--text-muted);font-size:.82rem;"><?= $tgl ?></td>
                <td style="text-align:center;"><?= $row['jumlah_tiket'] ?? 1 ?></td>
                <td><strong style="color:#059669;">Rp<?= number_format($row['harga'],0,',','.') ?></strong></td>
                <td style="text-align:center;"><?= $status_badge ?></td>
                <td style="text-align:center;">
                    <button class="btn-view" title="Lihat Detail"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="9">
                <div class="empty-state"><i class="fas fa-inbox"></i>Belum ada transaksi.</div>
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
        <div class="pagination-wrap">
            <div class="pag-info">Menampilkan <?= min($offset+1,$total_filtered) ?>–<?= min($offset+$per_page,$total_filtered) ?> dari <?= $total_filtered ?> data</div>
            <div class="pag-btns">
                <?php if($page>1): ?>
                <a href="?page=<?= $page-1 ?>&dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&status=<?= urlencode($f_status) ?>" class="pag-btn"><i class="fas fa-chevron-left" style="font-size:.7rem;"></i></a>
                <?php endif; ?>
                <?php for($p=1;$p<=$total_pages;$p++): ?>
                <a href="?page=<?= $p ?>&dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&status=<?= urlencode($f_status) ?>" class="pag-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if($page<$total_pages): ?>
                <a href="?page=<?= $page+1 ?>&dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&status=<?= urlencode($f_status) ?>" class="pag-btn"><i class="fas fa-chevron-right" style="font-size:.7rem;"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function eksporCSV(){
    const rows = document.querySelectorAll('#tabelPesanan tr');
    let csv = [];
    rows.forEach(r => {
        let cols = [...r.querySelectorAll('th,td')].map(c => '"' + c.innerText.replace(/"/g,'""') + '"');
        csv.push(cols.join(','));
    });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv.join('\n')],{type:'text/csv;charset=utf-8;'}));
    a.download = 'laporan_pesanan_<?= date('Ymd') ?>.csv';
    a.click();
}
</script>
</body>
</html>
<?php ob_end_flush(); ?>