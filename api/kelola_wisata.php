<?php
session_start();
include "koneksi.php"; // Menggunakan file koneksi kamu

// Keamanan: Hanya admin yang boleh masuk
if($_SESSION['role'] != 'admin') { header("Location: login.php"); exit; }

// --- PROSES CRUD ---
// 1. TAMBAH DATA
if(isset($_POST['tambah'])){
    $nama = $_POST['nama_wisata'];
    $harga = $_POST['harga'];
    mysqli_query($koneksi, "INSERT INTO destinasi (nama_wisata, harga) VALUES ('$nama', '$harga')");
    header("Location: kelola_wisata.php");
}

// 2. HAPUS DATA
if(isset($_GET['hapus'])){
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM destinasi WHERE id_wisata='$id'");
    header("Location: kelola_wisata.php");
}

// 3. EDIT DATA (Sederhana)
if(isset($_POST['edit'])){
    $id = $_POST['id_wisata'];
    $harga = $_POST['harga'];
    mysqli_query($koneksi, "UPDATE destinasi SET harga='$harga' WHERE id_wisata='$id'");
    header("Location: kelola_wisata.php");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kelola Wisata | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between mb-4">
            <h3>Panel Kelola Wisata</h3>
            <a href="admin_dashboard.php" class="btn btn-secondary">Kembali</a>
        </div>

        <div class="card p-4 mb-4 shadow-sm border-0">
            <h5>Tambah Wisata Baru</h5>
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
            <table class="table table-white table-hover shadow-sm rounded">
                <thead class="table-primary">
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
                            <td><?php echo $d['nama_wisata']; ?></td>
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