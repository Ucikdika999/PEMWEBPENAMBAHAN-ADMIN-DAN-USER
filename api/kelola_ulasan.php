<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$_hal = basename($_SERVER['PHP_SELF']);
$_usr = mysqli_real_escape_string($koneksi, $_SESSION['user']);
$_ip  = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
mysqli_query($koneksi, "INSERT INTO log_kunjungan (halaman, username, ip_address) VALUES ('$_hal','$_usr','$_ip')");

$msg = $_GET['msg'] ?? '';

// Filter & search
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$search        = isset($_GET['q'])      ? mysqli_real_escape_string($koneksi, $_GET['q']) : '';

$where = "WHERE 1=1";
if (in_array($filter_status, ['pending','approved','rejected'])) $where .= " AND u.status='$filter_status'";
if ($search) $where .= " AND (u.username LIKE '%$search%' OR u.komentar LIKE '%$search%' OR d.nama_wisata LIKE '%$search%')";

$per_page = 10;
$page     = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

$q_count     = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM ulasan u LEFT JOIN destinasi d ON u.id_wisata=d.id_wisata $where");
$total       = mysqli_fetch_assoc($q_count)['c'];
$total_pages = ceil($total / $per_page);

$hasil    = mysqli_query($koneksi,
    "SELECT u.*, d.nama_wisata FROM ulasan u
     LEFT JOIN destinasi d ON u.id_wisata=d.id_wisata
     $where ORDER BY u.created_at DESC LIMIT $per_page OFFSET $offset");
$rows = [];
while ($r = mysqli_fetch_assoc($hasil)) $rows[] = $r;

// Count per status
$cnt = [];
foreach (['pending','approved','rejected',''] as $s) {
    $w2 = $s ? "WHERE status='$s'" : '';
    $r2 = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM ulasan $w2");
    $cnt[$s ?: 'all'] = mysqli_fetch_assoc($r2)['c'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Ulasan | WISATA Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
:root{--sidebar-bg:#0f172a;--accent:#2563eb;--border:#e2e8f0;--surface:#fff;--bg:#f1f5f9;--text-primary:#0f172a;--text-muted:#64748b;--success:#10b981;--warning:#f59e0b;--danger:#ef4444;--sidebar-w:240px;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text-primary);display:flex;min-height:100vh;}
.sidebar{width:var(--sidebar-w);min-height:100vh;position:fixed;top:0;left:0;background:var(--sidebar-bg);display:flex;flex-direction:column;z-index:200;}
.sb-brand{padding:22px 18px 16px;border-bottom:1px solid rgba(255,255,255,.06);}
.sb-logo{display:flex;align-items:center;gap:10px;}
.sb-icon{width:40px;height:40px;background:var(--accent);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.sb-title{color:#fff;font-weight:800;font-size:1rem;line-height:1.1;}
.sb-sub{color:rgba(255,255,255,.35);font-size:.68rem;font-weight:500;}
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
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 30px;}
.page-title{font-size:1.45rem;font-weight:800;margin-bottom:2px;}
.page-sub{color:var(--text-muted);font-size:.85rem;margin-bottom:22px;}
.alert-ok{background:#d1fae5;color:#065f46;border-radius:10px;padding:12px 18px;font-size:.85rem;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.alert-err{background:#fee2e2;color:#991b1b;border-radius:10px;padding:12px 18px;font-size:.85rem;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px;}

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;}
.stat-card{background:var(--surface);border-radius:12px;padding:16px 18px;box-shadow:0 1px 8px rgba(0,0,0,.06);display:flex;align-items:center;gap:12px;}
.stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
.stat-num{font-size:1.4rem;font-weight:800;line-height:1;}
.stat-label{font-size:.72rem;color:var(--text-muted);font-weight:600;margin-top:2px;}

/* TOOLBAR */
.toolbar{background:var(--surface);border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:16px;}
.search-wrap{position:relative;flex:1;min-width:200px;}
.search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem;}
.search-wrap input{width:100%;border:1.5px solid var(--border);border-radius:9px;padding:8px 12px 8px 34px;font-size:.83rem;font-family:inherit;outline:none;transition:.2s;}
.search-wrap input:focus{border-color:var(--accent);}
.filter-select{border:1.5px solid var(--border);border-radius:9px;padding:8px 14px;font-size:.83rem;font-family:inherit;outline:none;cursor:pointer;}
.btn-c{border:none;border-radius:9px;padding:8px 18px;font-size:.83rem;font-weight:600;font-family:inherit;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:.18s;text-decoration:none;white-space:nowrap;}
.btn-accent{background:var(--accent);color:#fff;}.btn-accent:hover{background:#1d4ed8;color:#fff;}
.btn-success{background:#d1fae5;color:#065f46;}.btn-success:hover{background:#a7f3d0;}
.btn-danger{background:#fee2e2;color:#dc2626;}.btn-danger:hover{background:#fecaca;}
.btn-muted{background:#f1f5f9;color:var(--text-muted);}

/* TABLE */
.table-card{background:var(--surface);border-radius:14px;box-shadow:0 1px 8px rgba(0,0,0,.06);overflow:hidden;}
table{width:100%;border-collapse:collapse;}
thead th{background:#f8fafc;color:var(--text-muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;font-weight:700;padding:12px 16px;border-bottom:1px solid var(--border);white-space:nowrap;}
tbody td{padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:.875rem;vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover td{background:#f8fafc;}
.stars .fa-star{color:#f59e0b;font-size:.8rem;}
.stars .fa-star-empty{color:#e2e8f0;font-size:.8rem;}
.badge-status{padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;}
.s-pending{background:#fef3c7;color:#92400e;}
.s-approved{background:#d1fae5;color:#065f46;}
.s-rejected{background:#fee2e2;color:#991b1b;}
.komentar-text{max-width:260px;font-size:.82rem;color:#334155;line-height:1.5;word-break:break-word;}
.foto-preview{width:50px;height:40px;object-fit:cover;border-radius:6px;cursor:pointer;}
.no-foto-sm{width:50px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:.8rem;}
.btn-act{width:30px;height:30px;border-radius:7px;border:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;transition:.15s;text-decoration:none;}
.btn-approve{background:#d1fae5;color:#065f46;}.btn-approve:hover{background:#a7f3d0;}
.btn-reject{background:#fef3c7;color:#92400e;}.btn-reject:hover{background:#fde68a;}
.btn-del{background:#fee2e2;color:#dc2626;}.btn-del:hover{background:#fecaca;}
.empty-state{padding:60px 20px;text-align:center;color:var(--text-muted);}
.empty-state i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:12px;}
.pagination-wrap{padding:14px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.pag-info{color:var(--text-muted);font-size:.8rem;}
.pag-btns{display:flex;gap:4px;flex-wrap:wrap;}
.pag-btn{width:32px;height:32px;border-radius:7px;border:1.5px solid var(--border);background:#fff;color:var(--text-primary);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.8rem;text-decoration:none;transition:.15s;}
.pag-btn:hover{border-color:var(--accent);color:var(--accent);}
.pag-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;}
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
        <a href="kelola_wisata.php"   class="nav-item"><div class="ni-icon"><i class="fas fa-mountain-sun"></i></div> Kelola Wisata</a>
        <a href="kelola_user.php"     class="nav-item"><div class="ni-icon"><i class="fas fa-users"></i></div> Kelola User</a>
        <a href="laporan_pesanan.php" class="nav-item"><div class="ni-icon"><i class="fas fa-receipt"></i></div> Laporan Pesanan</a>
        <a href="kelola_ulasan.php"   class="nav-item active"><div class="ni-icon"><i class="fas fa-star"></i></div> Kelola Ulasan
            <?php if ($cnt['pending'] > 0): ?>
                <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:20px;"><?= $cnt['pending'] ?></span>
            <?php endif; ?>
        </a>
        <div class="menu-section" style="margin-top:6px;">Akun</div>
        <a href="profil.php" class="nav-item"><div class="ni-icon"><i class="fas fa-user-circle"></i></div> Profil Saya</a>
    </div>
    <div class="sb-footer">
        <div class="admin-tag">
            <div class="admin-ava"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
            <div><div class="admin-name"><?= htmlspecialchars($_SESSION['user']) ?></div><div class="admin-role">Administrator</div></div>
        </div>
        <a href="logout.php" class="nav-item logout"><div class="ni-icon"><i class="fas fa-power-off"></i></div> Logout</a>
    </div>
</aside>

<main class="main">
    <div class="page-title">Kelola Ulasan</div>
    <div class="page-sub">Moderasi ulasan dari pengunjung destinasi wisata</div>

    <?php if ($msg === 'approved'): ?><div class="alert-ok"><i class="fas fa-check-circle"></i> Ulasan berhasil disetujui!</div><?php endif; ?>
    <?php if ($msg === 'rejected'): ?><div class="alert-ok" style="background:#fef3c7;color:#92400e;"><i class="fas fa-times-circle"></i> Ulasan ditolak.</div><?php endif; ?>
    <?php if ($msg === 'deleted'):  ?><div class="alert-err"><i class="fas fa-trash"></i> Ulasan berhasil dihapus.</div><?php endif; ?>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f1f5f9;color:#475569;"><i class="fas fa-comments"></i></div>
            <div><div class="stat-num"><?= $cnt['all'] ?></div><div class="stat-label">Total Ulasan</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7;color:#92400e;"><i class="fas fa-clock"></i></div>
            <div><div class="stat-num"><?= $cnt['pending'] ?></div><div class="stat-label">Menunggu</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;color:#065f46;"><i class="fas fa-check-circle"></i></div>
            <div><div class="stat-num"><?= $cnt['approved'] ?></div><div class="stat-label">Disetujui</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;color:#991b1b;"><i class="fas fa-times-circle"></i></div>
            <div><div class="stat-num"><?= $cnt['rejected'] ?></div><div class="stat-label">Ditolak</div></div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <form method="GET" class="toolbar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari user, komentar, atau destinasi...">
        </div>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="pending"  <?= $filter_status==='pending'  ?'selected':'' ?>>⏳ Menunggu</option>
            <option value="approved" <?= $filter_status==='approved' ?'selected':'' ?>>✅ Disetujui</option>
            <option value="rejected" <?= $filter_status==='rejected' ?'selected':'' ?>>❌ Ditolak</option>
            <option value=""         <?= $filter_status===''         ?'selected':'' ?>>📋 Semua</option>
        </select>
        <button type="submit" class="btn-c btn-accent"><i class="fas fa-search"></i></button>
    </form>

    <!-- TABLE -->
    <div class="table-card">
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th style="padding-left:20px;">No</th>
                    <th>Destinasi</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Komentar</th>
                    <th>Foto</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($rows) > 0): foreach ($rows as $i => $r):
                $stars = '';
                for ($s = 1; $s <= 5; $s++) {
                    $stars .= '<i class="fas fa-star' . ($s <= $r['rating'] ? '' : '-empty') . '"></i>';
                }
            ?>
            <tr>
                <td style="padding-left:20px;color:var(--text-muted);"><?= $offset + $i + 1 ?></td>
                <td>
                    <div style="font-weight:600;font-size:.83rem;"><?= htmlspecialchars($r['nama_wisata'] ?? '-') ?></div>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:7px;">
                        <div style="width:28px;height:28px;background:var(--accent);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.75rem;flex-shrink:0;">
                            <?= strtoupper(substr($r['username'], 0, 1)) ?>
                        </div>
                        <span style="font-weight:600;font-size:.83rem;"><?= htmlspecialchars($r['username']) ?></span>
                    </div>
                </td>
                <td><span class="stars"><?= $stars ?></span></td>
                <td><div class="komentar-text"><?= htmlspecialchars(mb_strimwidth($r['komentar'], 0, 100, '...')) ?></div></td>
                <td>
                    <?php if (!empty($r['foto_url'])): ?>
                        <img src="<?= htmlspecialchars($r['foto_url']) ?>" class="foto-preview"
                             onclick="window.open('<?= htmlspecialchars($r['foto_url']) ?>','_blank')"
                             onerror="this.parentNode.innerHTML='<div class=no-foto-sm><i class=fas\ fa-image></i></div>'">
                    <?php else: ?>
                        <span style="color:#94a3b8;font-size:.75rem;">—</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted);font-size:.8rem;white-space:nowrap;">
                    <?= date('d M Y', strtotime($r['created_at'])) ?><br>
                    <span style="font-size:.72rem;"><?= date('H:i', strtotime($r['created_at'])) ?></span>
                </td>
                <td>
                    <?php
                    $sc = ['pending'=>'s-pending','approved'=>'s-approved','rejected'=>'s-rejected'];
                    $sl = ['pending'=>'⏳ Menunggu','approved'=>'✅ Disetujui','rejected'=>'❌ Ditolak'];
                    $sc2 = $sc[$r['status']] ?? 's-pending';
                    $sl2 = $sl[$r['status']] ?? $r['status'];
                    ?>
                    <span class="badge-status <?= $sc2 ?>"><?= $sl2 ?></span>
                </td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:5px;justify-content:center;">
                        <?php if ($r['status'] !== 'approved'): ?>
                        <a href="ulasan_action.php?action=approve&id=<?= $r['id'] ?>"
                           class="btn-act btn-approve" title="Setujui"
                           onclick="return confirm('Setujui ulasan ini?')">
                           <i class="fas fa-check"></i></a>
                        <?php endif; ?>
                        <?php if ($r['status'] !== 'rejected'): ?>
                        <a href="ulasan_action.php?action=reject&id=<?= $r['id'] ?>"
                           class="btn-act btn-reject" title="Tolak"
                           onclick="return confirm('Tolak ulasan ini?')">
                           <i class="fas fa-times"></i></a>
                        <?php endif; ?>
                        <a href="ulasan_action.php?action=hapus&id=<?= $r['id'] ?>"
                           class="btn-act btn-del" title="Hapus"
                           onclick="return confirm('Hapus ulasan ini permanen?')">
                           <i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="9">
                <div class="empty-state"><i class="fas fa-star"></i>Tidak ada ulasan ditemukan.</div>
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>

        <div class="pagination-wrap">
            <div class="pag-info">Menampilkan <?= min($offset+1,$total) ?>–<?= min($offset+$per_page,$total) ?> dari <?= $total ?> data</div>
            <div class="pag-btns">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>" class="pag-btn"><i class="fas fa-chevron-left" style="font-size:.7rem;"></i></a>
                <?php endif; ?>
                <?php for ($p=1; $p<=$total_pages; $p++): ?>
                    <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>" class="pag-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>" class="pag-btn"><i class="fas fa-chevron-right" style="font-size:.7rem;"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>