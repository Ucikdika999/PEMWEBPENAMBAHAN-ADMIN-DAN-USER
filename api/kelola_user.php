<?php
session_start();
include "koneksi.php"; // Menggunakan file koneksi kamu

// Keamanan: Hanya admin yang boleh mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// --- PROSES ACTION ---

// 1. HAPUS USER
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");
    header("Location: kelola_user.php");
}

// 2. UBAH ROLE (Admin ke User atau sebaliknya)
if (isset($_GET['ubah_role'])) {
    $id = $_GET['ubah_role'];
    $role_sekarang = $_GET['role'];
    $role_baru = ($role_sekarang == 'admin') ? 'user' : 'admin';
    
    mysqli_query($koneksi, "UPDATE users SET role='$role_baru' WHERE id='$id'");
    header("Location: kelola_user.php");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola User | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { height: 100vh; width: 250px; position: fixed; background: #212529; color: white; padding: 20px; }
        .content { margin-left: 250px; padding: 30px; }
        .nav-link { color: white; margin-bottom: 10px; }
        .nav-link:hover { background: rgba(255,255,255,0.1); border-radius: 5px; }
        .active-link { background: #0d6efd; border-radius: 5px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4 class="mb-5 fw-bold text-primary"><i class="fas fa-shield-halved"></i> ADMIN PANEL</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a>
            <a class="nav-link" href="kelola_wisata.php"><i class="fas fa-mountain-sun me-2"></i> Kelola Wisata</a>
            <a class="nav-link active-link" href="kelola_user.php"><i class="fas fa-users me-2"></i> Kelola User</a>
            <hr>
            <a class="nav-link text-danger" href="logout.php"><i class="fas fa-power-off me-2"></i> Logout</a>
        </nav>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Daftar Pengguna Sistem</h2>
        </div>

        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $query = mysqli_query($koneksi, "SELECT * FROM users");
                        while ($row = mysqli_fetch_assoc($query)) :
                        ?>
                        <tr>
                            <td class="ps-4"><?php echo $no++; ?></td>
                            <td><strong><?php echo $row['username']; ?></strong></td>
                            <td>
                                <span class="badge <?php echo ($row['role'] == 'admin') ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo strtoupper($row['role']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="?ubah_role=<?php echo $row['id']; ?>&role=<?php echo $row['role']; ?>" 
                                   class="btn btn-sm btn-outline-primary me-2" 
                                   title="Ubah Role">
                                   <i class="fas fa-sync-alt"></i> Ganti Role
                                </a>
                                
                                <?php if($row['username'] != $_SESSION['user']): ?>
                                <a href="?hapus=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Yakin ingin menghapus user ini?')"
                                   title="Hapus User">
                                   <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>