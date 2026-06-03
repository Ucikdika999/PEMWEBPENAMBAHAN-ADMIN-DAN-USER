<?php
session_start();
include "koneksi.php";
$_hal = basename($_SERVER['PHP_SELF']);
$_usr = isset($_SESSION['user']) ? mysqli_real_escape_string($koneksi, $_SESSION['user']) : 'tamu';
$_ip  = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
mysqli_query($koneksi, "INSERT INTO log_kunjungan (halaman, username, ip_address) VALUES ('$_hal', '$_usr', '$_ip')");

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// HAPUS USER
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");
    header("Location: kelola_user.php"); exit;
}

// UBAH ROLE
if (isset($_GET['ubah_role'])) {
    $id = (int)$_GET['ubah_role'];
    $role_sekarang = $_GET['role'];
    $role_baru = ($role_sekarang == 'admin') ? 'user' : 'admin';
    mysqli_query($koneksi, "UPDATE users SET role='$role_baru' WHERE id='$id'");
    header("Location: kelola_user.php"); exit;
}

// TAMBAH USER
$pesan = '';
if (isset($_POST['tambah_user'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password, role) VALUES ('$username','$email','$password','$role')";
    if (mysqli_query($koneksi, $sql)) $pesan = 'success';
    else $pesan = 'error';
    header("Location: kelola_user.php?msg=$pesan"); exit;
}

$search = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, $_GET['q']) : '';
$filter_role = isset($_GET['role_filter']) ? mysqli_real_escape_string($koneksi, $_GET['role_filter']) : '';

$where = "WHERE 1=1";
if ($search) $where .= " AND (username LIKE '%$search%' OR email LIKE '%$search%')";
if ($filter_role) $where .= " AND role = '$filter_role'";

$query = mysqli_query($koneksi, "SELECT * FROM users $where ORDER BY id DESC");
$total = mysqli_num_rows($query);

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total / $per_page);
$query_paged = mysqli_query($koneksi, "SELECT * FROM users $where ORDER BY id DESC LIMIT $per_page OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola User | WISATA Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
:root {
    --sidebar-bg: #0f172a;
    --sidebar-hover: rgba(255,255,255,0.06);
    --sidebar-active: #2563eb;
    --accent: #2563eb;
    --accent-light: #eff6ff;
    --danger: #ef4444;
    --warning: #f59e0b;
    --success: #10b981;
    --text-primary: #0f172a;
    --text-muted: #64748b;
    --border: #e2e8f0;
    --surface: #ffffff;
    --bg: #f1f5f9;
    --sidebar-w: 240px;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--bg); color:var(--text-primary); display:flex; min-height:100vh; }

/* SIDEBAR */
.sidebar { width:var(--sidebar-w); min-height:100vh; position:fixed; top:0; left:0; background:var(--sidebar-bg); display:flex; flex-direction:column; z-index:200; }
.sb-brand { padding:22px 18px 16px; border-bottom:1px solid rgba(255,255,255,0.06); }
.sb-logo { display:flex; align-items:center; gap:10px; }
.sb-icon { width:40px; height:40px; background:var(--accent); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
.sb-title { color:#fff; font-weight:800; font-size:1rem; line-height:1.1; }
.sb-sub { color:rgba(255,255,255,0.35); font-size:0.68rem; font-weight:500; }
.sb-menu { padding:14px 10px; flex:1; overflow-y:auto; }
.menu-section { font-size:0.62rem; text-transform:uppercase; letter-spacing:1.5px; color:rgba(255,255,255,0.25); font-weight:700; padding:10px 10px 5px; margin-top:4px; }
.nav-item { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:9px; color:rgba(255,255,255,0.45); font-size:0.84rem; font-weight:500; text-decoration:none; transition:.18s; margin-bottom:2px; cursor:pointer; }
.nav-item .ni-icon { width:30px; height:30px; border-radius:7px; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; font-size:0.8rem; flex-shrink:0; transition:.18s; }
.nav-item:hover { background:var(--sidebar-hover); color:rgba(255,255,255,0.85); }
.nav-item:hover .ni-icon { background:rgba(255,255,255,0.1); }
.nav-item.active { background:var(--accent); color:#fff; box-shadow:0 4px 16px rgba(37,99,235,0.35); }
.nav-item.active .ni-icon { background:rgba(255,255,255,0.18); }
.nav-item.logout { color:rgba(239,100,100,0.7); }
.nav-item.logout:hover { background:rgba(239,68,68,0.1); color:#fca5a5; }
.sb-footer { padding:12px 10px; border-top:1px solid rgba(255,255,255,0.06); }
.admin-tag { display:flex; align-items:center; gap:9px; padding:9px 10px; background:rgba(255,255,255,0.04); border-radius:9px; margin-bottom:6px; }
.admin-ava { width:32px; height:32px; background:var(--accent); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:0.85rem; flex-shrink:0; }
.admin-name { color:#fff; font-size:0.8rem; font-weight:600; }
.admin-role { color:rgba(255,255,255,0.3); font-size:0.65rem; }

/* CONTENT */
.main { margin-left:var(--sidebar-w); flex:1; padding:28px 30px; }
.page-header { margin-bottom:24px; }
.page-title { font-size:1.45rem; font-weight:800; color:var(--text-primary); }
.page-sub { color:var(--text-muted); font-size:0.85rem; margin-top:2px; }

/* TOOLBAR */
.toolbar { background:var(--surface); border-radius:14px; padding:16px 18px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; box-shadow:0 1px 8px rgba(0,0,0,0.06); margin-bottom:16px; }
.search-wrap { position:relative; flex:1; min-width:200px; }
.search-wrap i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:0.8rem; }
.search-wrap input { width:100%; border:1.5px solid var(--border); border-radius:9px; padding:8px 12px 8px 34px; font-size:0.83rem; font-family:inherit; outline:none; transition:.2s; }
.search-wrap input:focus { border-color:var(--accent); }
.filter-select { border:1.5px solid var(--border); border-radius:9px; padding:8px 14px; font-size:0.83rem; font-family:inherit; outline:none; cursor:pointer; color:var(--text-primary); transition:.2s; }
.filter-select:focus { border-color:var(--accent); }
.btn-primary-c { background:var(--accent); color:#fff; border:none; border-radius:9px; padding:8px 18px; font-size:0.83rem; font-weight:600; font-family:inherit; cursor:pointer; display:flex; align-items:center; gap:7px; transition:.18s; white-space:nowrap; }
.btn-primary-c:hover { background:#1d4ed8; }

/* TABLE CARD */
.table-card { background:var(--surface); border-radius:14px; box-shadow:0 1px 8px rgba(0,0,0,0.06); overflow:hidden; }
.table-card table { width:100%; border-collapse:collapse; }
.table-card thead th { background:#f8fafc; color:var(--text-muted); font-size:0.72rem; text-transform:uppercase; letter-spacing:.6px; font-weight:700; padding:12px 16px; border-bottom:1px solid var(--border); white-space:nowrap; }
.table-card tbody td { padding:13px 16px; border-bottom:1px solid #f1f5f9; font-size:0.875rem; vertical-align:middle; }
.table-card tbody tr:last-child td { border-bottom:none; }
.table-card tbody tr:hover td { background:#f8fafc; }

/* AVATAR */
.ava { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0; }
.ava-blue  { background:#dbeafe; color:#1d4ed8; }
.ava-teal  { background:#ccfbf1; color:#0f766e; }
.ava-purple{ background:#ede9fe; color:#7c3aed; }
.ava-amber { background:#fef3c7; color:#d97706; }
.user-cell { display:flex; align-items:center; gap:10px; }
.user-info .name { font-weight:600; font-size:0.875rem; }
.user-info .email { font-size:0.75rem; color:var(--text-muted); }

/* BADGE */
.badge-role { padding:4px 11px; border-radius:20px; font-size:0.72rem; font-weight:700; }
.badge-admin   { background:#dbeafe; color:#1d4ed8; }
.badge-user    { background:#f0fdf4; color:#16a34a; }
.badge-petugas { background:#fef3c7; color:#b45309; }

.badge-status { padding:4px 11px; border-radius:20px; font-size:0.72rem; font-weight:700; display:inline-flex; align-items:center; gap:5px; }
.badge-aktif   { background:#f0fdf4; color:#16a34a; }
.badge-nonaktif{ background:#fef2f2; color:#dc2626; }
.dot { width:6px; height:6px; border-radius:50%; display:inline-block; }
.dot-green { background:#16a34a; }
.dot-red   { background:#dc2626; }

/* ACTION BUTTONS */
.btn-act { width:30px; height:30px; border-radius:7px; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; transition:.15s; }
.btn-edit { background:#fef3c7; color:#d97706; }
.btn-edit:hover { background:#fde68a; }
.btn-del  { background:#fee2e2; color:#dc2626; }
.btn-del:hover  { background:#fecaca; }
.btn-role { background:#dbeafe; color:#2563eb; }
.btn-role:hover { background:#bfdbfe; }

/* PAGINATION */
.pagination-wrap { padding:14px 18px; border-top:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
.pag-info { color:var(--text-muted); font-size:0.8rem; }
.pag-btns { display:flex; gap:4px; }
.pag-btn { width:32px; height:32px; border-radius:7px; border:1.5px solid var(--border); background:#fff; color:var(--text-primary); display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.8rem; text-decoration:none; transition:.15s; }
.pag-btn:hover { border-color:var(--accent); color:var(--accent); }
.pag-btn.active { background:var(--accent); border-color:var(--accent); color:#fff; }

/* MODAL */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.5); z-index:500; align-items:center; justify-content:center; }
.modal-overlay.show { display:flex; }
.modal-box { background:#fff; border-radius:18px; padding:28px; width:100%; max-width:480px; max-height:90vh; overflow-y:auto; animation:modalIn .2s ease; }
@keyframes modalIn { from { transform:scale(.95); opacity:0; } to { transform:scale(1); opacity:1; } }
.modal-title { font-weight:800; font-size:1.05rem; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
.form-group { margin-bottom:14px; }
.form-group label { display:block; font-size:0.8rem; font-weight:600; color:var(--text-muted); margin-bottom:5px; }
.form-group input, .form-group select { width:100%; border:1.5px solid var(--border); border-radius:9px; padding:9px 13px; font-size:0.875rem; font-family:inherit; outline:none; transition:.2s; }
.form-group input:focus, .form-group select:focus { border-color:var(--accent); }
.modal-footer { display:flex; gap:8px; justify-content:flex-end; margin-top:20px; }
.btn-cancel { background:#f1f5f9; color:var(--text-muted); border:none; border-radius:9px; padding:9px 20px; font-family:inherit; font-size:0.83rem; font-weight:600; cursor:pointer; }
.btn-save { background:var(--accent); color:#fff; border:none; border-radius:9px; padding:9px 22px; font-family:inherit; font-size:0.83rem; font-weight:600; cursor:pointer; }

/* TOAST */
.toast-wrap { position:fixed; top:20px; right:20px; z-index:999; }
.toast { background:#0f172a; color:#fff; padding:12px 20px; border-radius:10px; font-size:0.83rem; font-weight:600; display:flex; align-items:center; gap:8px; animation:toastIn .3s ease; }
.toast.success { border-left:4px solid var(--success); }
.toast.error   { border-left:4px solid var(--danger); }
@keyframes toastIn { from { transform:translateX(60px); opacity:0; } to { transform:translateX(0); opacity:1; } }

/* EMPTY */
.empty-state { padding:60px 20px; text-align:center; color:var(--text-muted); }
.empty-state i { font-size:2.5rem; opacity:.2; display:block; margin-bottom:12px; }
</style>
</head>
<body>

<!-- SIDEBAR -->
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
        <a href="kelola_user.php" class="nav-item active">
            <div class="ni-icon"><i class="fas fa-users"></i></div> Kelola User
        </a>
        <a href="laporan_pesanan.php" class="nav-item">
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

<!-- MAIN -->
<main class="main">
    <div class="page-header">
        <div class="page-title">Kelola User</div>
        <div class="page-sub">Kelola akun pengguna sistem</div>
    </div>

    <!-- TOOLBAR -->
    <form method="GET" class="toolbar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama / email / username...">
        </div>
        <select name="role_filter" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Role</option>
            <option value="admin" <?= $filter_role=='admin'?'selected':'' ?>>Administrator</option>
            <option value="user" <?= $filter_role=='user'?'selected':'' ?>>Pengunjung</option>
            <option value="petugas" <?= $filter_role=='petugas'?'selected':'' ?>>Petugas/Admin</option>
        </select>
        <button type="submit" class="btn-primary-c" style="background:#64748b;"><i class="fas fa-search"></i> Cari</button>
        <button type="button" class="btn-primary-c" onclick="document.getElementById('modalTambah').classList.add('show')">
            <i class="fas fa-plus"></i> Tambah User
        </button>
    </form>

    <!-- TABLE -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th style="padding-left:20px;">No</th>
                    <th>Avatar</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Terdaftar</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $colors = ['ava-blue','ava-teal','ava-purple','ava-amber'];
            $no = $offset + 1;
            if (mysqli_num_rows($query_paged) > 0):
                while ($row = mysqli_fetch_assoc($query_paged)):
                    $ci = ($row['id'] - 1) % 4;
                    $role_badge = match($row['role']) {
                        'admin'   => '<span class="badge-role badge-admin">Administrator</span>',
                        'petugas' => '<span class="badge-role badge-petugas">Petugas/Admin</span>',
                        default   => '<span class="badge-role badge-user">Pengunjung</span>',
                    };
                    $tgl = isset($row['created_at']) ? date('d M Y, H:i', strtotime($row['created_at'])) : '-';
                    $status = isset($row['status']) && $row['status'] == 0
                        ? '<span class="badge-status badge-nonaktif"><span class="dot dot-red"></span>Nonaktif</span>'
                        : '<span class="badge-status badge-aktif"><span class="dot dot-green"></span>Aktif</span>';
            ?>
            <tr>
                <td style="padding-left:20px;color:var(--text-muted);"><?= $no++ ?></td>
                <td>
                    <div class="ava <?= $colors[$ci] ?>"><?= strtoupper(substr($row['username'],0,1)) ?></div>
                </td>
                <td>
                    <div class="user-info">
                        <div class="name"><?= htmlspecialchars($row['username']) ?></div>
                    </div>
                </td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($row['username']) ?></td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                <td><?= $role_badge ?></td>
                <td><?= $status ?></td>
                <td style="color:var(--text-muted);font-size:0.8rem;"><?= $tgl ?></td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:5px;justify-content:center;">
                        <a href="?ubah_role=<?= $row['id'] ?>&role=<?= $row['role'] ?>" class="btn-act btn-role" title="Ganti Role">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                        <?php if($row['username'] != $_SESSION['user']): ?>
                        <a href="?hapus=<?= $row['id'] ?>" class="btn-act btn-del" title="Hapus"
                           onclick="return confirm('Yakin hapus user ini?')">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
                <td colspan="9">
                    <div class="empty-state"><i class="fas fa-users"></i>Tidak ada user ditemukan.</div>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination-wrap">
            <div class="pag-info">Menampilkan <?= min($offset+1,$total) ?>–<?= min($offset+$per_page,$total) ?> dari <?= $total ?> data</div>
            <div class="pag-btns">
                <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>&role_filter=<?= urlencode($filter_role) ?>" class="pag-btn"><i class="fas fa-chevron-left" style="font-size:.7rem;"></i></a>
                <?php endif; ?>
                <?php for($p=1;$p<=$total_pages;$p++): ?>
                <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&role_filter=<?= urlencode($filter_role) ?>" class="pag-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>&role_filter=<?= urlencode($filter_role) ?>" class="pag-btn"><i class="fas fa-chevron-right" style="font-size:.7rem;"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- MODAL TAMBAH USER -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal-box">
        <div class="modal-title"><i class="fas fa-user-plus" style="color:var(--accent);"></i> Tambah User Baru</div>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="contoh: john_doe" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="user">Pengunjung</option>
                    <option value="admin">Administrator</option>
                    <option value="petugas">Petugas/Admin</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalTambah').classList.remove('show')">Batal</button>
                <button type="submit" name="tambah_user" class="btn-save"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="toast-wrap">
    <div class="toast <?= $_GET['msg']=='success'?'success':'error' ?>">
        <i class="fas <?= $_GET['msg']=='success'?'fa-check-circle':'fa-exclamation-circle' ?>"></i>
        <?= $_GET['msg']=='success' ? 'User berhasil ditambahkan!' : 'Gagal menambahkan user.' ?>
    </div>
</div>
<script>setTimeout(()=>{ document.querySelector('.toast-wrap')?.remove(); }, 3500);</script>
<?php endif; ?>

<script>
document.querySelector('.modal-overlay')?.addEventListener('click', function(e){
    if(e.target === this) this.classList.remove('show');
});
</script>
</body>
</html>