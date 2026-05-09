<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";

// Hanya admin yang boleh akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Ambil semua pesanan dengan join ke tabel destinasi jika perlu
$query_pesanan = mysqli_query($koneksi, "SELECT * FROM pesanan ORDER BY id_pesanan DESC");

// Hitung total statistik
$total_pesanan  = mysqli_num_rows($query_pesanan);
$result_pendapatan = mysqli_query($koneksi, "SELECT SUM(harga) as total FROM pesanan");
$data_pendapatan   = mysqli_fetch_assoc($result_pendapatan);
$total_pendapatan  = $data_pendapatan['total'] ?? 0;

// Reset pointer hasil query
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
        body { background-color: #f8f9fa; }

        /* Sidebar (sama persis dengan admin_dashboard.php kamu) */
        .sidebar {
            height: 100vh; width: 250px; position: fixed;
            background: #212529; color: white; padding: 20px;
        }
        .content { margin-left: 250px; padding: 30px; }
        .nav-link { padding: 10px 15px; transition: 0.3s; color: rgba(255,255,255,0.7) !important; }
        .nav-link:hover { background: rgba(255,255,255,0.1); border-radius: 5px; color: white !important; }
        .nav-link.active { background: #0d6efd; color: white !important; border-radius: 5px; }

        /* Kartu statistik */
        .stat-card { border: none; border-radius: 16px; padding: 24px; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }

        /* Tabel pesanan */
        .table-card { border: none; border-radius: 16px; overflow: hidden; }
        .badge-lunas { background: #d1fae5; color: #065f46; font-weight: 600; padding: 6px 14px; border-radius: 20px; }

        /* Tombol ekspor */
        .btn-export { border-radius: 10px; font-weight: 600; letter-spacing: 0.5px; }

        /* Search bar */
        .search-wrapper { position: relative; }
        .search-wrapper input { border-radius: 12px; padding-left: 40px; border: 2px solid #e9ecef; }
        .search-wrapper input:focus { border-color: #0d6efd; box-shadow: none; }
        .search-wrapper .fa-search { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #adb5bd; }
    </style>
</head>
<body>

<!-- SIDEBAR (konsisten dengan file admin lainnya) -->
<div class="sidebar shadow">
    <h4 class="mb-5 fw-bold text-primary text-center">
        <i class="fas fa-shield-halved"></i> ADMIN PANEL
    </h4>
    <nav class="nav flex-column">
        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a>
        <a class="nav-link" href="kelola_wisata.php"><i class="fas fa-mountain-sun me-2"></i> Kelola Wisata</a>
        <a class="nav-link" href="kelola_user.php"><i class="fas fa-users me-2"></i> Kelola User</a>
        <a class="nav-link active" href="laporan_pesanan.php"><i class="fas fa-receipt me-2"></i> Laporan Pesanan</a>
        <hr class="bg-secondary">
        <a class="nav-link text-danger fw-bold" href="logout.php"><i class="fas fa-power-off me-2"></i> Logout</a>
    </nav>
</div>

<!-- KONTEN UTAMA -->
<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Laporan Pesanan</h2>
            <p class="text-muted small mb-0">Semua transaksi tiket wisata</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="cetakHalaman()" class="btn btn-outline-secondary btn-export">
                <i class="fas fa-print me-2"></i>Cetak
            </button>
            <button onclick="eksporCSV()" class="btn btn-success btn-export">
                <i class="fas fa-file-csv me-2"></i>Ekspor CSV
            </button>
        </div>
    </div>

    <!-- KARTU STATISTIK -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card shadow-sm bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-75 small fw-semibold">TOTAL PESANAN</p>
                        <h2 class="fw-bold mb-0"><?= $total_pesanan ?></h2>
                        <p class="mb-0 opacity-75 small">Transaksi</p>
                    </div>
                    <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card shadow-sm bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-75 small fw-semibold">TOTAL PENDAPATAN</p>
                        <h2 class="fw-bold mb-0">Rp<?= number_format($total_pendapatan, 0, ',', '.') ?></h2>
                        <p class="mb-0 opacity-75 small">Dari semua transaksi</p>
                    </div>
                    <i class="fas fa-wallet fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card shadow-sm bg-warning text-dark">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-50 small fw-semibold">RATA-RATA HARGA</p>
                        <h2 class="fw-bold mb-0">
                            Rp<?= $total_pesanan > 0 ? number_format($total_pendapatan / $total_pesanan, 0, ',', '.') : 0 ?>
                        </h2>
                        <p class="mb-0 opacity-50 small">Per tiket</p>
                    </div>
                    <i class="fas fa-tags fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL PESANAN -->
    <div class="card table-card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Daftar Semua Transaksi</h5>
            <div class="search-wrapper" style="width: 260px;">
                <i class="fas fa-search"></i>
                <input type="text" id="cariPesanan" class="form-control form-control-sm" placeholder="Cari username / wisata...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tabelPesanan">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Kode</th>
                            <th>Username</th>
                            <th>Destinasi Wisata</th>
                            <th>Harga</th>
                            <th>Tanggal Pesan</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_pesanan) > 0):
                            while ($row = mysqli_fetch_assoc($query_pesanan)): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary">#WDT-<?= $row['id_pesanan'] ?></td>
                            <td>
                                <i class="fas fa-user-circle text-secondary me-1"></i>
                                <?= htmlspecialchars($row['username']) ?>
                            </td>
                            <td><?= htmlspecialchars($row['nama_wisata']) ?></td>
                            <td class="fw-bold text-success">Rp<?= number_format($row['harga'], 0, ',', '.') ?></td>
                            <td class="text-muted small">
                                <?php
                                    if (!empty($row['tanggal_pesan'])) {
                                        echo date('d M Y, H:i', strtotime($row['tanggal_pesan']));
                                    } else {
                                        echo '<span class="text-muted fst-italic">-</span>';
                                    }
                                ?>
                            </td>
                            <td class="text-center">
                                <span class="badge-lunas">✓ Lunas</span>
                            </td>
                        </tr>
                        <?php endwhile;
                        else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                                Belum ada transaksi sama sekali.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Fitur pencarian real-time
document.getElementById('cariPesanan').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    const rows = document.querySelectorAll('#tabelPesanan tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
    });
});

// Cetak halaman
function cetakHalaman() {
    window.print();
}

// Ekspor ke CSV
function eksporCSV() {
    const rows = document.querySelectorAll('#tabelPesanan tr');
    let csv = [];
    rows.forEach(row => {
        const cols = row.querySelectorAll('th, td');
        let rowData = [];
        cols.forEach(col => rowData.push('"' + col.innerText.replace(/"/g, '""') + '"'));
        csv.push(rowData.join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'laporan_pesanan_wisata.csv';
    a.click();
}
</script>

</body>
</html>
<?php ob_end_flush(); ?>