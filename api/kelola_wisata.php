<?php
ob_start(); // Memulai output buffering untuk mencegah error "headers already sent"
session_start();

// 1. Gunakan auth_check agar pengecekan login aman dan terpusat
include "auth_check.php"; 
include "koneksi.php";

// 2. Keamanan: Pastikan hanya admin yang bisa akses
// Kita gunakan isset() untuk mencegah "Undefined array key"
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php?pesan=bukan_admin"); 
    exit; 
}

// --- PROSES CRUD ---
// 1. TAMBAH DATA
if(isset($_POST['tambah'])){
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_wisata']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);
    mysqli_query($koneksi, "INSERT INTO destinasi (nama_wisata, harga) VALUES ('$nama', '$harga')");
    header("Location: kelola_wisata.php");
    exit;
}

// 2. HAPUS DATA
if(isset($_GET['hapus'])){
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM destinasi WHERE id_wisata='$id'");
    header("Location: kelola_wisata.php");
    exit;
}

// 3. EDIT DATA
if(isset($_POST['edit'])){
    $id = mysqli_real_escape_string($koneksi, $_POST['id_wisata']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);
    mysqli_query($koneksi, "UPDATE destinasi SET harga='$harga' WHERE id_wisata='$id'");
    header("Location: kelola_wisata.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Wisata | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-map-marked-alt"></i> Panel Kelola Wisata</h3>
            <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <div class="card p-4 mb-4 shadow-sm border-0">
            <h5 class="fw-bold mb-3">Tambah Wisata Baru</h5>
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="nama_wisata" class="form-control" placeholder="Nama Wisata" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="harga" class="form-control" placeholder="Harga" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="tambah" class="btn btn-success w-100">Tambah</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-white table-hover shadow-sm rounded overflow-hidden">
                <thead class="table-dark">
                    <tr>
                        <th>Nama Wisata</th>
                        <th>Harga (Rp)</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = mysqli_query($koneksi, "SELECT * FROM destinasi");
                    while($d = mysqli_fetch_assoc($res)):
                    ?>
                    <tr>
                        <form method="POST">
                            <td class="align-middle"><?php echo htmlspecialchars($d['nama_wisata']); ?></td>
                            <td>
                                <input type="hidden" name="id_wisata" value="<?php echo $d['id_wisata']; ?>">
                                <input type="number" name="harga" class="form-control form-control-sm" value="<?php echo $d['harga']; ?>">
                            </td>
                            <td class="text-center">
                                <button type="submit" name="edit" class="btn btn-warning btn-sm">Update Harga</button>
                                <a href="?hapus=<?php echo $d['id_wisata']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus wisata ini?')">Hapus</a>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>