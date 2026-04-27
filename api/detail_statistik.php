<?php
ob_start();
session_start();

// 1. GUNAKAN AUTH CHECK (Sangat Penting!)
// Ini akan memastikan jika Session hilang, Cookie akan memulihkannya otomatis
include "auth_check.php"; 
include "koneksi.php";

// 2. Proteksi Halaman (Gunakan session yang sudah dipastikan ada oleh auth_check)
if(!isset($_SESSION['login'])) { 
    header("Location: login.php"); 
    exit; 
}

// 3. Ambil data dari Dashboard (Nilai 561 dari API)
$subjek = isset($_GET['subjek']) ? $_GET['subjek'] : 'Pariwisata';
$nilai = isset($_GET['nilai']) ? $_GET['nilai'] : '561';

// Logika analisis sederhana
$status_kunjungan = ($nilai > 500) ? "Tren Meningkat" : "Stabil";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Data BPS | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(rgba(0,0,0,0.85), rgba(0,0,0,0.85)), url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1350&q=80');
            background-size: cover; background-attachment: fixed; color: white; min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px; padding: 40px; margin-top: 50px;
        }
        .table-bps { color: #e0e0e0; border-collapse: separate; border-spacing: 0 10px; }
        .table-bps thead th { border: none; color: #0dcaf0; text-transform: uppercase; font-size: 0.85rem; }
        .table-bps tbody tr { background: rgba(255, 255, 255, 0.03); transition: 0.3s; }
        .table-bps tbody tr:hover { background: rgba(255, 255, 255, 0.1); }
        .table-bps td { border: none; padding: 15px; vertical-align: middle; }
        .label-cell { border-left: 4px solid #0dcaf0 !important; border-radius: 8px 0 0 8px; }
    </style>
</head>
<body>

<div class="container pb-5">
    <div class="glass-card shadow-lg">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold"><i class="fas fa-file-invoice-chart text-info me-3"></i>Laporan Statistik Kunjungan</h2>
                <p class="text-info opacity-75 mb-0">Subjek: Jumlah Kunjungan Wisman per Bulan menurut Kebangsaan (BPS)</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="user_dashboard.php" class="btn btn-outline-light rounded-pill px-4"><i class="fas fa-arrow-left me-2"></i>Dashboard</a>
            </div>
        </div>

        <div class="alert alert-dark border-secondary text-white-50 small mb-5">
            <i class="fas fa-info-circle me-2"></i> Menampilkan rincian indikator utama dari tabel statis BPS (ID: 1470). Nilai dikonversi berdasarkan data kumulatif terkini.
        </div>

        <div class="table-responsive">
            <table class="table table-bps">
                <thead>
                    <tr>
                        <th width="40%">Kategori / Indikator</th>
                        <th>Rincian Data</th>
                        <th>Nilai / Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="label-cell fw-bold">Variabel Utama</td>
                        <td>Jumlah Kunjungan Wisatawan Mancanegara</td>
                        <td><span class="badge bg-info text-dark">Aktif</span></td>
                    </tr>
                    <tr>
                        <td class="label-cell fw-bold">Negara Fokus Utama</td>
                        <td>Malaysia, Singapura, Australia, Tiongkok</td>
                        <td><i class="fas fa-globe-asia text-warning"></i></td>
                    </tr>
                    <tr>
                        <td class="label-cell fw-bold">Indeks Kunjungan (Nilai)</td>
                        <td>Angka perbandingan periode terpilih</td>
                        <td class="fs-4 fw-bold text-info"><?php echo number_format($nilai, 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td class="label-cell fw-bold">Analisis Tren</td>
                        <td>Perkembangan bulanan kumulatif</td>
                        <td class="text-success fw-bold"><?php echo $status_kunjungan; ?> <i class="fas fa-chart-line ms-1"></i></td>
                    </tr>
                    <tr>
                        <td class="label-cell fw-bold">Sumber Referensi</td>
                        <td>Badan Pusat Statistik (BPS)</td>
                        <td><code class="text-info">webapi.bps.go.id</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-5 p-4 rounded-4" style="background: rgba(13, 202, 240, 0.05); border: 1px dashed rgba(13, 202, 240, 0.3);">
            <h6 class="fw-bold text-info"><i class="fas fa-lightbulb me-2"></i>Insight Informatika:</h6>
            <p class="small mb-0">
                Sesuai dengan data tabel BPS (1470), nilai <strong><?php echo $nilai; ?></strong> mencerminkan intensitas pergerakan wisatawan asing. 
                Data ini ditarik secara otomatis menggunakan metode JSON parsing untuk mendukung sistem monitoring wisata real-time yang sedang Anda kembangkan.
            </p>
        </div>
    </div>
</div>

</body>
</html>