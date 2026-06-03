<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";
include "catat_kunjungan.php";
catatKunjungan($koneksi);

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit();
}

$username  = $_SESSION['user'];
$pesan_ok  = '';
$pesan_err = '';

// Proses pembatalan
if (isset($_GET['batal']) && is_numeric($_GET['batal'])) {
    $id = (int) $_GET['batal'];

    // Pastikan tiket milik user yang login (keamanan penting!)
    $cek = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id_pesanan='$id' AND username='$username'");

    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($koneksi, "DELETE FROM pesanan WHERE id_pesanan='$id' AND username='$username'");
        $pesan_ok = "Tiket #WDT-$id berhasil dibatalkan.";
    } else {
        $pesan_err = "Tiket tidak ditemukan atau bukan milik Anda.";
    }
}

// Ambil semua tiket milik user
$query_tiket = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE username='$username' ORDER BY id_pesanan DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batalkan Tiket | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.75)),
                        url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1350&q=80');
            background-size: cover; background-attachment: fixed;
            min-height: 100vh; color: white;
            font-family: 'Segoe UI', sans-serif;
        }

        .glass-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px; padding: 30px;
        }

        .tiket-card {
            background: rgba(255,255,255,0.95);
            color: #333; border-radius: 16px;
            border-left: 6px solid #0d6efd;
            transition: 0.3s;
        }
        .tiket-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.2); }

        .btn-batal {
            background: #dc3545; color: white;
            border: none; border-radius: 10px;
            padding: 8px 20px; font-weight: 600;
            transition: 0.2s;
        }
        .btn-batal:hover { background: #b02a37; color: white; }

        .badge-lunas {
            background: #d1fae5; color: #065f46;
            font-weight: 600; padding: 6px 14px;
            border-radius: 20px; font-size: 0.8rem;
        }

        .modal-confirm .modal-content {
            border-radius: 20px; border: none;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-transparent pt-3 px-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold fs-4" href="user_dashboard.php">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
        </a>
        <span class="badge bg-light text-dark px-3 py-2 fw-semibold">
            <i class="fas fa-user me-1"></i> <?= htmlspecialchars($username) ?>
        </span>
    </div>
</nav>

<div class="container mt-4 pb-5">
    <div class="glass-card shadow-lg">

        <!-- HEADER -->
        <div class="mb-4">
            <h3 class="fw-bold"><i class="fas fa-ticket-alt text-warning me-2"></i>Kelola Tiket Saya</h3>
            <p class="opacity-75 small mb-0">Kamu bisa membatalkan tiket yang sudah dibeli di sini.</p>
        </div>

        <!-- ALERT PESAN -->
        <?php if ($pesan_ok): ?>
            <div class="alert alert-success border-0 rounded-3 fw-semibold">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($pesan_ok) ?>
            </div>
        <?php endif; ?>
        <?php if ($pesan_err): ?>
            <div class="alert alert-danger border-0 rounded-3 fw-semibold">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($pesan_err) ?>
            </div>
        <?php endif; ?>

        <!-- DAFTAR TIKET -->
        <?php if ($query_tiket && mysqli_num_rows($query_tiket) > 0): ?>
            <div class="row g-3">
                <?php while ($row = mysqli_fetch_assoc($query_tiket)): ?>
                <div class="col-md-6">
                    <div class="tiket-card p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <small class="text-muted fw-bold text-uppercase">Kode Booking</small>
                                <h6 class="fw-bold text-primary mb-0">#WDT-<?= $row['id_pesanan'] ?></h6>
                            </div>
                            <span class="badge-lunas">✓ Lunas</span>
                        </div>

                        <h5 class="fw-bold text-dark mb-1">
                            <i class="fas fa-mountain-sun text-success me-2"></i>
                            <?= htmlspecialchars($row['nama_wisata']) ?>
                        </h5>

                        <p class="text-success fw-bold fs-5 mb-1">
                            Rp<?= number_format($row['harga'], 0, ',', '.') ?>
                        </p>

                        <p class="text-muted small mb-3">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?= !empty($row['tanggal_pesan'])
                                ? date('d M Y, H:i', strtotime($row['tanggal_pesan']))
                                : 'Tanggal tidak tersedia' ?>
                        </p>

                        <hr class="my-2">

                        <!-- Tombol batal dengan konfirmasi modal -->
                        <button class="btn btn-batal w-100"
                            onclick="konfirmasiBatal(<?= $row['id_pesanan'] ?>, '<?= htmlspecialchars($row['nama_wisata']) ?>')">
                            <i class="fas fa-times-circle me-2"></i>Batalkan Tiket Ini
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-ticket-alt fa-4x opacity-25 mb-3 d-block"></i>
                <p class="lead opacity-75">Kamu belum memiliki tiket apapun.</p>
                <a href="user_dashboard.php" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">
                    Cari Destinasi Sekarang
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL KONFIRMASI -->
<div class="modal fade modal-confirm" id="modalBatal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4 text-center">
            <div class="mb-3">
                <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
            </div>
            <h5 class="fw-bold">Batalkan Tiket?</h5>
            <p class="text-muted" id="namaTiketModal"></p>
            <p class="small text-danger fw-semibold">Tindakan ini tidak bisa dibatalkan.</p>
            <div class="d-flex gap-2 justify-content-center mt-3">
                <button class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tidak Jadi</button>
                <a href="#" id="linkBatal" class="btn btn-danger rounded-pill px-4 fw-bold">Ya, Batalkan</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function konfirmasiBatal(id, nama) {
    document.getElementById('namaTiketModal').textContent = 'Kamu akan membatalkan tiket untuk: ' + nama;
    document.getElementById('linkBatal').href = 'batalkan_tiket.php?batal=' + id;
    new bootstrap.Modal(document.getElementById('modalBatal')).show();
}
</script>

</body>
</html>
<?php ob_end_flush(); ?>