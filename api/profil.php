<?php
ob_start();
session_start();
include "auth_check.php";
include "koneksi.php";
$_hal = basename($_SERVER['PHP_SELF']);
$_usr = isset($_SESSION['user']) ? mysqli_real_escape_string($koneksi, $_SESSION['user']) : 'tamu';
$_ip  = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
mysqli_query($koneksi, "INSERT INTO log_kunjungan (halaman, username, ip_address) VALUES ('$_hal', '$_usr', '$_ip')");

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit();
}

$username  = $_SESSION['user'];
$role      = $_SESSION['role'];
$pesan_ok  = '';
$pesan_err = '';

// Proses ganti password
if (isset($_POST['ganti_password'])) {
    $pass_lama = $_POST['password_lama'];
    $pass_baru = $_POST['password_baru'];
    $pass_ulang = $_POST['password_ulang'];

    // Ambil data user dari DB
    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    $data = mysqli_fetch_assoc($cek);

    if (!password_verify($pass_lama, $data['password'])) {
        $pesan_err = "Password lama salah!";
    } elseif (strlen($pass_baru) < 6) {
        $pesan_err = "Password baru minimal 6 karakter.";
    } elseif ($pass_baru !== $pass_ulang) {
        $pesan_err = "Konfirmasi password tidak cocok!";
    } else {
        $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
        mysqli_query($koneksi, "UPDATE users SET password='$hash_baru' WHERE username='$username'");
        $pesan_ok = "Password berhasil diperbarui!";
    }
}

// Tentukan link kembali sesuai role
$link_kembali = ($role === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(rgba(0,0,0,0.78), rgba(0,0,0,0.78)),
                        url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1350&q=80');
            background-size: cover; background-attachment: fixed;
            min-height: 100vh; color: white;
            font-family: 'Segoe UI', sans-serif;
            display: flex; flex-direction: column;
        }

        .glass-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 24px; padding: 40px;
        }

        .avatar-circle {
            width: 90px; height: 90px;
            background: linear-gradient(135deg, #0d6efd, #6ea8fe);
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-size: 2.2rem;
            font-weight: bold; color: white;
            margin: 0 auto 16px;
            box-shadow: 0 8px 24px rgba(13,110,253,0.5);
        }

        .info-item {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px; padding: 14px 18px;
            margin-bottom: 10px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .info-label { opacity: 0.6; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .info-value { font-weight: 700; font-size: 1rem; }

        .form-control-glass {
            background: rgba(255,255,255,0.1);
            border: 1.5px solid rgba(255,255,255,0.2);
            color: white; border-radius: 12px;
            padding: 12px 16px;
            transition: 0.3s;
        }
        .form-control-glass:focus {
            background: rgba(255,255,255,0.15);
            border-color: #6ea8fe;
            color: white; box-shadow: 0 0 0 3px rgba(13,110,253,0.25);
        }
        .form-control-glass::placeholder { color: rgba(255,255,255,0.4); }

        .form-label-glass { font-weight: 600; font-size: 0.9rem; opacity: 0.85; }

        .btn-simpan {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border: none; border-radius: 14px;
            font-weight: 700; padding: 12px;
            letter-spacing: 0.5px; transition: 0.3s;
            box-shadow: 0 6px 20px rgba(13,110,253,0.4);
        }
        .btn-simpan:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(13,110,253,0.5); }

        .divider { border-color: rgba(255,255,255,0.15); }

        .badge-role {
            background: <?= $role === 'admin' ? 'rgba(220,53,69,0.25)' : 'rgba(25,135,84,0.25)' ?>;
            border: 1px solid <?= $role === 'admin' ? '#dc3545' : '#198754' ?>;
            color: <?= $role === 'admin' ? '#f8d7da' : '#d1e7dd' ?>;
            padding: 6px 14px; border-radius: 20px;
            font-weight: 700; font-size: 0.85rem;
        }

        /* Indikator kekuatan password */
        #kekuatan-bar { height: 6px; border-radius: 3px; transition: 0.4s; background: #555; }
        #kekuatan-label { font-size: 0.78rem; opacity: 0.75; }

        /* Toggle show password */
        .pass-wrapper { position: relative; }
        .pass-wrapper .toggle-pass {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%); cursor: pointer;
            opacity: 0.6; color: white;
        }
        .pass-wrapper .toggle-pass:hover { opacity: 1; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-transparent pt-3 px-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold fs-4" href="<?= $link_kembali ?>">
            <i class="fas fa-arrow-left me-2"></i>Kembali
        </a>
        <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
            <i class="fas fa-power-off me-1"></i> Logout
        </a>
    </div>
</nav>

<div class="container my-4 pb-5" style="max-width: 860px;">
    <div class="glass-card shadow-lg">

        <!-- AVATAR & INFO USER -->
        <div class="text-center mb-4">
            <div class="avatar-circle"><?= strtoupper(substr($username, 0, 1)) ?></div>
            <h4 class="fw-bold mb-1"><?= htmlspecialchars($username) ?></h4>
            <span class="badge-role">
                <?= $role === 'admin' ? '🔑 Petugas / Admin' : '🌿 Pengunjung' ?>
            </span>
        </div>

        <hr class="divider my-4">

        <!-- INFO AKUN -->
        <h6 class="fw-bold mb-3 opacity-75"><i class="fas fa-id-card me-2"></i>Informasi Akun</h6>
        <div class="info-item">
            <span class="info-label">Username</span>
            <span class="info-value"><?= htmlspecialchars($username) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Role</span>
            <span class="info-value"><?= $role === 'admin' ? 'Admin / Petugas' : 'Pengunjung' ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Status</span>
            <span class="info-value text-success">✓ Aktif</span>
        </div>

        <hr class="divider my-4">

        <!-- FORM GANTI PASSWORD -->
        <h6 class="fw-bold mb-3 opacity-75"><i class="fas fa-lock me-2"></i>Ganti Password</h6>

        <!-- ALERT -->
        <?php if ($pesan_ok): ?>
            <div class="alert border-0 rounded-3 fw-semibold mb-3"
                 style="background: rgba(25,135,84,0.25); color: #d1e7dd; border: 1px solid #198754 !important;">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($pesan_ok) ?>
            </div>
        <?php endif; ?>
        <?php if ($pesan_err): ?>
            <div class="alert border-0 rounded-3 fw-semibold mb-3"
                 style="background: rgba(220,53,69,0.25); color: #f8d7da; border: 1px solid #dc3545 !important;">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($pesan_err) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Password Lama -->
            <div class="mb-3">
                <label class="form-label form-label-glass">Password Lama</label>
                <div class="pass-wrapper">
                    <input type="password" name="password_lama" id="passLama"
                           class="form-control form-control-glass" placeholder="Masukkan password lama" required>
                    <span class="toggle-pass" onclick="togglePass('passLama', this)">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <!-- Password Baru -->
            <div class="mb-3">
                <label class="form-label form-label-glass">Password Baru</label>
                <div class="pass-wrapper">
                    <input type="password" name="password_baru" id="passBaru"
                           class="form-control form-control-glass" placeholder="Minimal 6 karakter" required
                           oninput="cekKekuatan(this.value)">
                    <span class="toggle-pass" onclick="togglePass('passBaru', this)">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <!-- Indikator kekuatan -->
                <div class="mt-2">
                    <div id="kekuatan-bar" style="width: 0%;"></div>
                    <small id="kekuatan-label" class="text-white-50"></small>
                </div>
            </div>

            <!-- Konfirmasi Password Baru -->
            <div class="mb-4">
                <label class="form-label form-label-glass">Ulangi Password Baru</label>
                <div class="pass-wrapper">
                    <input type="password" name="password_ulang" id="passUlang"
                           class="form-control form-control-glass" placeholder="Ketik ulang password baru" required>
                    <span class="toggle-pass" onclick="togglePass('passUlang', this)">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" name="ganti_password" class="btn btn-simpan btn-primary w-100 text-white">
                <i class="fas fa-save me-2"></i>Simpan Password Baru
            </button>
        </form>

    </div>
</div>

<script>
// Toggle show/hide password
function togglePass(id, icon) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    icon.innerHTML = isHidden ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
}

// Indikator kekuatan password
function cekKekuatan(pass) {
    const bar   = document.getElementById('kekuatan-bar');
    const label = document.getElementById('kekuatan-label');
    let skor = 0;
    if (pass.length >= 6)  skor++;
    if (pass.length >= 10) skor++;
    if (/[A-Z]/.test(pass)) skor++;
    if (/[0-9]/.test(pass)) skor++;
    if (/[^A-Za-z0-9]/.test(pass)) skor++;

    const level = [
        { w: '0%',   bg: '#555',    txt: '' },
        { w: '25%',  bg: '#dc3545', txt: 'Lemah' },
        { w: '50%',  bg: '#fd7e14', txt: 'Cukup' },
        { w: '75%',  bg: '#ffc107', txt: 'Kuat' },
        { w: '100%', bg: '#198754', txt: 'Sangat Kuat' },
    ];
    const lv = level[Math.min(skor, 4)];
    bar.style.width = lv.w;
    bar.style.background = lv.bg;
    label.textContent = lv.txt;
}
</script>

</body>
</html>
<?php ob_end_flush(); ?>