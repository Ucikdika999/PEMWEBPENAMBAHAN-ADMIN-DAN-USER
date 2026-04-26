<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "koneksi.php";

// Fungsi untuk mengambil data BPS
function ambilDataBPS() {
    $url = "https://webapi.bps.go.id/v1/api/list/model/data/lang/ind/domain/0000/var/1470/th/126/key/279bfd3333f47740fe54cd482719d5f6";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Eksekusi pengambilan data
$data_bps = ambilDataBPS();
$nilai_bps = ($data_bps && isset($data_bps['status']) && $data_bps['status'] == 'OK') ? $data_bps['subject'][0]['val'] : "0";
$label_bps = ($data_bps && isset($data_bps['status']) && $data_bps['status'] == 'OK') ? $data_bps['subject'][0]['label'] : "Data Pariwisata";

// Proteksi Login
if(!isset($_SESSION['login'])) { 
    header("Location: login.php"); 
    exit; 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pengunjung | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1350&q=80');
            background-size: cover; background-attachment: fixed; color: white; min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px; padding: 40px; margin-top: 40px;
        }
        /* Tambahan style agar teks destinasi terbaca jelas */
        .destinasi-card { background: rgba(255,255,255,0.9); color: black; border-radius: 15px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-transparent pt-4 px-5">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold fs-3" href="#"><i class="fas fa-mountain-sun me-2"></i>WISATA</a>
        <a href="proses_beli.php?id=<?php echo $row['id_wisata']; ?>" class="btn btn-primary btn-sm w-100 rounded-pill">Beli Tiket</a>
        <a href="logout.php" class="btn btn-outline-light rounded-pill px-4">Logout</a>
        <a href="tiket_saya.php" class="btn btn-warning rounded-pill px-4 me-2 text-white">
    <i class="fas fa-ticket-alt me-2"></i>Tiket Saya
</a>
    </div>
</nav>

<div class="container mb-5">
    <div class="glass-card shadow-lg">
        <div class="row align-items-center mb-5">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold mb-0">Halo, <?php echo $_SESSION['user']; ?>!</h1>
                <p class="lead opacity-75 mt-2">Cek status tiket dan jadwal liburanmu di sini.</p>
            </div>
            <div class="col-md-4 text-md-end text-center">
                <div class="p-3 bg-white text-dark rounded-4 fw-bold">PENGUNJUNG VIP</div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-12">
                <h4 class="mb-4"><i class="fas fa-mountain-sun text-warning me-2"></i>Destinasi Wisata Hari Ini</h4>
                <div class="row">
                    <?php
                    // Logika CRUD (Read) untuk menampilkan wisata dari database
                    $query_wisata = mysqli_query($koneksi, "SELECT * FROM destinasi");
                    while($row = mysqli_fetch_assoc($query_wisata)):
                    ?>
                    <div class="col-md-4 mb-3">
                        <div class="card destinasi-card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <h5 class="fw-bold"><?php echo $row['nama_wisata']; ?></h5>
                                <p class="text-success fw-bold mb-3">Rp<?php echo number_format($row['harga']); ?></p>
                                <a href="proses_beli.php?id=<?php echo $row['id_wisata']; ?>" class="btn btn-primary btn-sm w-100 rounded-pill">Beli Tiket</a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <a href="detail_statistik.php?subjek=pariwisata&nilai=<?php echo $nilai_bps; ?>" class="text-decoration-none">
                    <div class="p-4 rounded-4 shadow-hover" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); transition: 0.3s;">
                        <div class="row align-items-center">
                            <div class="col-md-1 text-center">
                                <i class="fas fa-chart-line fa-2x text-info"></i>
                            </div>
                            <div class="col-md-8 text-white">
                                <h5 class="mb-0 fw-bold">Statistik Nasional: <?php echo $label_bps; ?></h5>
                                <p class="small mb-0 opacity-75">Klik untuk melihat detail data dari API BPS Pusat.</p>
                            </div>
                            <div class="col-md-3 text-md-end mt-3 mt-md-0">
                                <h3 class="fw-bold text-info mb-0"><?php echo number_format($nilai_bps, 0, ',', '.'); ?></h3>
                                <span class="badge bg-info text-dark">Lihat Detail</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        </div> </div> <style>
    .shadow-hover:hover {
        background: rgba(255, 255, 255, 0.15) !important;
        transform: translateY(-5px);
    }
</style>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row mt-5">
            <div class="col-md-12">
                <div class="p-4 rounded-4" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);">
                    <div class="row align-items-center">
                        <div class="col-md-1 text-center">
                            <i class="fas fa-chart-line fa-2x text-info"></i>
                        </div>
                        <div class="col-md-8">
                            <h5 class="mb-0 fw-bold">Statistik Nasional: <?php echo $label_bps; ?></h5>
                            <p class="small mb-0 opacity-75">Data ini ditarik secara real-time dari API BPS Pusat untuk referensi liburanmu.</p>
                        </div>
                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                            <h3 class="fw-bold text-info mb-0"><?php echo number_format($nilai_bps, 0, ',', '.'); ?></h3>
                            <span class="badge bg-info text-dark">Data Terkini</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div> </div> </body>
</html>

</body>
</html>