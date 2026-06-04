<?php
session_start();
include "auth_check.php";
include "koneksi.php";
$_hal = basename($_SERVER['PHP_SELF']);
$_usr = isset($_SESSION['user']) ? mysqli_real_escape_string($koneksi, $_SESSION['user']) : 'tamu';
$_ip  = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
mysqli_query($koneksi, "INSERT INTO log_kunjungan (halaman, username, ip_address) VALUES ('$_hal', '$_usr', '$_ip')");

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Total pengguna
$q_user      = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users");
$data_user   = mysqli_fetch_assoc($q_user);

// Total pesanan
$q_pesanan     = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan");
$total_pesanan = mysqli_fetch_assoc($q_pesanan)['total'] ?? 0;

// Total pendapatan
$q_pend          = mysqli_query($koneksi, "SELECT SUM(harga) as total FROM pesanan");
$total_pendapatan = mysqli_fetch_assoc($q_pend)['total'] ?? 0;

// Kunjungan hari ini
$q_hari             = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_kunjungan WHERE DATE(waktu) = CURDATE()");
$kunjungan_hari_ini = mysqli_fetch_assoc($q_hari)['total'] ?? 0;

// Destinasi terlaris
$q_terlaris = mysqli_query($koneksi,
    "SELECT nama_wisata, COUNT(*) as total FROM pesanan GROUP BY nama_wisata ORDER BY total DESC LIMIT 3");

// ── ULASAN: statistik ─────────────────────────────────────────────────
$q_ul_total    = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM ulasan");
$ul_total      = mysqli_fetch_assoc($q_ul_total)['c'] ?? 0;

$q_ul_pending  = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM ulasan WHERE status='pending'");
$ul_pending    = mysqli_fetch_assoc($q_ul_pending)['c'] ?? 0;

$q_ul_approved = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM ulasan WHERE status='approved'");
$ul_approved   = mysqli_fetch_assoc($q_ul_approved)['c'] ?? 0;

$q_ul_avg      = mysqli_query($koneksi, "SELECT ROUND(AVG(rating),1) as avg_r FROM ulasan WHERE status='approved'");
$ul_avg        = mysqli_fetch_assoc($q_ul_avg)['avg_r'] ?? 0;

// ── ULASAN: 5 terbaru (semua status) ─────────────────────────────────
$q_ul_recent = mysqli_query($koneksi,
    "SELECT u.*, d.nama_wisata
     FROM ulasan u
     LEFT JOIN destinasi d ON u.id_wisata = d.id_wisata
     ORDER BY u.created_at DESC LIMIT 5");
$ul_recent = [];
while ($r = mysqli_fetch_assoc($q_ul_recent)) $ul_recent[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | WISATA</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    :root {
        --sidebar-bg:#0f172a; --accent:#2563eb; --border:#e2e8f0;
        --surface:#fff; --bg:#f1f5f9; --text-primary:#0f172a;
        --text-muted:#64748b; --sidebar-w:240px;
        --success:#10b981; --warning:#f59e0b; --danger:#ef4444;
    }
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text-primary);display:flex;min-height:100vh;}

    /* SIDEBAR */
    .sidebar{width:var(--sidebar-w);min-height:100vh;position:fixed;top:0;left:0;background:var(--sidebar-bg);display:flex;flex-direction:column;z-index:200;}
    .sb-brand{padding:22px 18px 16px;border-bottom:1px solid rgba(255,255,255,0.06);}
    .sb-logo{display:flex;align-items:center;gap:10px;}
    .sb-icon{width:40px;height:40px;background:var(--accent);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
    .sb-title{color:#fff;font-weight:800;font-size:1rem;line-height:1.1;}
    .sb-sub{color:rgba(255,255,255,0.35);font-size:0.68rem;}
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
    .admin-ava{width:32px;height:32px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;}
    .admin-name{color:#fff;font-size:.8rem;font-weight:600;}
    .admin-role{color:rgba(255,255,255,.3);font-size:.65rem;}
    .notif-badge{margin-left:auto;background:#ef4444;color:#fff;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:20px;line-height:1.4;}

    /* MAIN */
    .main{margin-left:var(--sidebar-w);flex:1;padding:28px 30px;}
    .page-title{font-size:1.45rem;font-weight:800;margin-bottom:4px;}
    .page-sub{color:var(--text-muted);font-size:.85rem;margin-bottom:24px;}

    /* STAT CARDS */
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px;}
    .stat-card{background:var(--surface);border-radius:14px;padding:20px;box-shadow:0 1px 8px rgba(0,0,0,.06);}
    .stat-ico{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:.95rem;margin-bottom:14px;}
    .stat-num{font-size:1.7rem;font-weight:800;line-height:1;}
    .stat-num-sm{font-size:1.1rem;font-weight:800;line-height:1;}
    .stat-lbl{font-size:.75rem;color:var(--text-muted);margin-top:5px;}

    /* ULASAN STAT GRID */
    .ulasan-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;}
    .ul-stat{background:var(--surface);border-radius:12px;padding:16px 18px;box-shadow:0 1px 8px rgba(0,0,0,.06);display:flex;align-items:center;gap:12px;}
    .ul-stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
    .ul-stat-num{font-size:1.35rem;font-weight:800;line-height:1;}
    .ul-stat-lbl{font-size:.72rem;color:var(--text-muted);font-weight:600;margin-top:2px;}

    /* CARD BOX */
    .card-box{background:var(--surface);border-radius:14px;padding:22px;box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:18px;}
    .card-box h5{font-weight:700;font-size:.95rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
    .card-header-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
    .card-header-row h5{margin-bottom:0;}
    .link-sm{font-size:.78rem;color:var(--accent);text-decoration:none;font-weight:600;}
    .link-sm:hover{text-decoration:underline;}

    /* TERLARIS */
    .terlaris-item{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);}
    .terlaris-item:last-child{border-bottom:none;}

    /* ULASAN TABLE */
    .ul-table{width:100%;border-collapse:collapse;}
    .ul-table thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:700;padding:8px 12px;border-bottom:1px solid var(--border);background:#f8fafc;white-space:nowrap;}
    .ul-table tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;font-size:.83rem;vertical-align:middle;}
    .ul-table tbody tr:last-child td{border-bottom:none;}
    .ul-table tbody tr:hover td{background:#f8fafc;}
    .stars-sm .fa-star{color:#f59e0b;font-size:.72rem;}
    .stars-sm .fa-star-empty{color:#e2e8f0;font-size:.72rem;}
    .badge-status{padding:3px 9px;border-radius:20px;font-size:.68rem;font-weight:700;display:inline-block;white-space:nowrap;}
    .s-pending{background:#fef3c7;color:#92400e;}
    .s-approved{background:#d1fae5;color:#065f46;}
    .s-rejected{background:#fee2e2;color:#991b1b;}
    .user-ava-sm{width:26px;height:26px;background:var(--accent);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.72rem;flex-shrink:0;}
    .komentar-clip{max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#334155;}
    .btn-approve-sm{background:#d1fae5;color:#065f46;border:none;border-radius:6px;width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;cursor:pointer;transition:.15s;text-decoration:none;}
    .btn-approve-sm:hover{background:#a7f3d0;}
    .pending-alert{background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px;margin-bottom:18px;border:1.5px solid #fcd34d;}
    .pending-alert-icon{width:36px;height:36px;background:#f59e0b;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;flex-shrink:0;}
    .pending-alert-text{font-size:.85rem;font-weight:600;color:#78350f;}
    .pending-alert-sub{font-size:.76rem;color:#92400e;font-weight:400;}
    .btn-review{background:#f59e0b;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:.78rem;font-weight:700;font-family:inherit;cursor:pointer;text-decoration:none;white-space:nowrap;transition:.15s;}
    .btn-review:hover{background:#d97706;color:#fff;}
    .empty-ul{text-align:center;padding:30px;color:var(--text-muted);font-size:.85rem;}

    /* BANNER TRACKING */
    .tracking-banner{background:linear-gradient(135deg,#1e40af,#7c3aed);border-radius:14px;padding:22px 28px;color:white;display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;}
    .tracking-banner h5{font-weight:800;font-size:1.1rem;margin:0 0 4px;}
    .tracking-banner p{font-size:.85rem;opacity:.8;margin:0;}
    .btn-tracking{background:white;color:#1e40af;border:none;border-radius:9px;padding:10px 20px;font-weight:700;font-size:.85rem;cursor:pointer;text-decoration:none;}
    .btn-tracking:hover{background:#f0f4ff;color:#1e40af;}
    </style>
</head>
<body>

<!-- ══════════ SIDEBAR ══════════ -->
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
        <a href="admin_dashboard.php" class="nav-item active">
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
        <a href="tracking.php" class="nav-item">
            <div class="ni-icon"><i class="fas fa-chart-bar"></i></div> Tracking Pengunjung
        </a>
        <a href="kelola_ulasan.php" class="nav-item">
            <div class="ni-icon"><i class="fas fa-star"></i></div> Kelola Ulasan
            <?php if ($ul_pending > 0): ?>
                <span class="notif-badge"><?= $ul_pending ?></span>
            <?php endif; ?>
        </a>
        <div class="menu-section">Akun</div>
        <a href="profil.php" class="nav-item">
            <div class="ni-icon"><i class="fas fa-user-circle"></i></div> Profil Saya
        </a>
    </div>
    <div class="sb-footer">
        <div class="admin-tag">
            <div class="admin-ava"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
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

<!-- ══════════ MAIN ══════════ -->
<main class="main">
    <div class="page-title">Selamat Datang, <?= htmlspecialchars($_SESSION['user']) ?> 👋</div>
    <div class="page-sub">Ini ringkasan sistem monitoring wisata Anda hari ini</div>

    <!-- STAT CARDS UTAMA -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-ico" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-users"></i></div>
            <div class="stat-num"><?= $data_user['total'] ?></div>
            <div class="stat-lbl">Total Pengguna</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#d1fae5;color:#059669;"><i class="fas fa-ticket-alt"></i></div>
            <div class="stat-num"><?= $total_pesanan ?></div>
            <div class="stat-lbl">Total Pesanan</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#fef3c7;color:#d97706;"><i class="fas fa-wallet"></i></div>
            <div class="stat-num-sm">Rp<?= number_format($total_pendapatan, 0, ',', '.') ?></div>
            <div class="stat-lbl">Total Pendapatan</div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-eye"></i></div>
            <div class="stat-num"><?= $kunjungan_hari_ini ?></div>
            <div class="stat-lbl">Kunjungan Hari Ini</div>
        </div>
    </div>

    <!-- STAT ULASAN -->
    <div class="ulasan-stats">
        <div class="ul-stat">
            <div class="ul-stat-icon" style="background:#f1f5f9;color:#475569;"><i class="fas fa-comments"></i></div>
            <div><div class="ul-stat-num"><?= $ul_total ?></div><div class="ul-stat-lbl">Total Ulasan</div></div>
        </div>
        <div class="ul-stat">
            <div class="ul-stat-icon" style="background:#fef3c7;color:#92400e;"><i class="fas fa-clock"></i></div>
            <div><div class="ul-stat-num"><?= $ul_pending ?></div><div class="ul-stat-lbl">Menunggu Review</div></div>
        </div>
        <div class="ul-stat">
            <div class="ul-stat-icon" style="background:#d1fae5;color:#065f46;"><i class="fas fa-check-circle"></i></div>
            <div><div class="ul-stat-num"><?= $ul_approved ?></div><div class="ul-stat-lbl">Disetujui</div></div>
        </div>
        <div class="ul-stat">
            <div class="ul-stat-icon" style="background:#fef9c3;color:#854d0e;"><i class="fas fa-star"></i></div>
            <div><div class="ul-stat-num"><?= $ul_avg > 0 ? number_format($ul_avg, 1) : '—' ?></div><div class="ul-stat-lbl">Rata-rata Rating</div></div>
        </div>
    </div>

    <!-- ALERT PENDING -->
    <?php if ($ul_pending > 0): ?>
    <div class="pending-alert">
        <div class="pending-alert-icon"><i class="fas fa-bell"></i></div>
        <div style="flex:1;">
            <div class="pending-alert-text">Ada <?= $ul_pending ?> ulasan menunggu persetujuan</div>
            <div class="pending-alert-sub">Segera tinjau agar ulasan pengunjung bisa tampil di halaman destinasi</div>
        </div>
        <a href="kelola_ulasan.php?status=pending" class="btn-review">Tinjau Sekarang →</a>
    </div>
    <?php endif; ?>

    <!-- BANNER TRACKING -->
    <div class="tracking-banner">
        <div>
            <h5>📊 Fitur Tracking Pengunjung Aktif</h5>
            <p>Pantau grafik kunjungan harian, jam ramai, dan destinasi paling diminati secara real-time</p>
        </div>
        <a href="tracking.php" class="btn-tracking">Lihat Detail Tracking →</a>
    </div>

    <div class="row g-3">
        <!-- DESTINASI TERLARIS -->
        <div class="col-md-6">
            <div class="card-box">
                <h5><i class="fas fa-fire" style="color:#ef4444;"></i> Destinasi Terlaris</h5>
                <?php
                $no = 1;
                $medals = ['🥇','🥈','🥉'];
                while ($row = mysqli_fetch_assoc($q_terlaris)):
                ?>
                <div class="terlaris-item">
                    <span style="font-weight:600;"><?= $medals[$no-1] ?? $no ?> <?= htmlspecialchars($row['nama_wisata']) ?></span>
                    <span style="background:#dbeafe;color:#1e40af;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">
                        <?= $row['total'] ?> tiket
                    </span>
                </div>
                <?php $no++; endwhile; ?>
                <?php if ($no === 1): ?>
                    <p style="color:var(--text-muted);font-size:.85rem;text-align:center;padding:20px 0;">Belum ada pesanan.</p>
                <?php endif; ?>
                <a href="tracking.php" style="display:block;margin-top:14px;font-size:.82rem;color:var(--accent);text-decoration:none;font-weight:600;">
                    Lihat semua →
                </a>
            </div>
        </div>

        <!-- STATUS SISTEM -->
        <div class="col-md-6">
            <div class="card-box">
                <h5><i class="fas fa-server" style="color:#059669;"></i> Status Sistem</h5>
                <div class="terlaris-item">
                    <span>Database TiDB</span>
                    <span style="background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">✓ Online</span>
                </div>
                <div class="terlaris-item">
                    <span>Tracking Pengunjung</span>
                    <span style="background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">✓ Aktif</span>
                </div>
                <div class="terlaris-item">
                    <span>API Cuaca (OWM)</span>
                    <span style="background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">✓ Aktif</span>
                </div>
                <div class="terlaris-item">
                    <span>Sistem Ulasan</span>
                    <span style="background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">✓ Aktif</span>
                </div>
                <div class="terlaris-item">
                    <span>Session / Auth</span>
                    <span style="background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;">✓ Aktif</span>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL ULASAN TERBARU -->
    <div class="card-box">
        <div class="card-header-row">
            <h5><i class="fas fa-star" style="color:#f59e0b;"></i> Ulasan Terbaru</h5>
            <a href="kelola_ulasan.php" class="link-sm">Lihat semua →</a>
        </div>

        <?php if (count($ul_recent) > 0): ?>
        <div style="overflow-x:auto;">
        <table class="ul-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Destinasi</th>
                    <th>Rating</th>
                    <th>Komentar</th>
                    <th>Waktu</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ul_recent as $u):
                $stars = '';
                for ($s = 1; $s <= 5; $s++) {
                    $stars .= '<i class="fas fa-star' . ($s <= $u['rating'] ? '' : '-empty') . '"></i>';
                }
                $sc = ['pending'=>'s-pending','approved'=>'s-approved','rejected'=>'s-rejected'];
                $sl = ['pending'=>'⏳ Pending','approved'=>'✅ Disetujui','rejected'=>'❌ Ditolak'];
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:7px;">
                        <div class="user-ava-sm"><?= strtoupper(substr($u['username'], 0, 1)) ?></div>
                        <span style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></span>
                    </div>
                </td>
                <td style="font-weight:600;font-size:.8rem;"><?= htmlspecialchars($u['nama_wisata'] ?? '-') ?></td>
                <td><span class="stars-sm"><?= $stars ?></span></td>
                <td><div class="komentar-clip"><?= htmlspecialchars($u['komentar']) ?></div></td>
                <td style="color:var(--text-muted);font-size:.78rem;white-space:nowrap;">
                    <?= date('d M Y', strtotime($u['created_at'])) ?><br>
                    <?= date('H:i', strtotime($u['created_at'])) ?>
                </td>
                <td>
                    <span class="badge-status <?= $sc[$u['status']] ?? 's-pending' ?>">
                        <?= $sl[$u['status']] ?? $u['status'] ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <?php if ($u['status'] === 'pending'): ?>
                        <a href="ulasan_action.php?action=approve&id=<?= $u['id'] ?>"
                           class="btn-approve-sm" title="Setujui"
                           onclick="return confirm('Setujui ulasan ini?')">
                           <i class="fas fa-check"></i>
                        </a>
                    <?php else: ?>
                        <a href="kelola_ulasan.php?status=<?= $u['status'] ?>"
                           style="font-size:.75rem;color:var(--accent);text-decoration:none;font-weight:600;">
                           Detail
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
            <div class="empty-ul"><i class="fas fa-comment-slash" style="font-size:1.8rem;opacity:.2;display:block;margin-bottom:8px;"></i>Belum ada ulasan masuk.</div>
        <?php endif; ?>
    </div>

</main>
</body>
</html>