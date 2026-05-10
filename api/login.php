<?php
ob_start();
session_start();
include "koneksi.php";

if (isset($_POST['login'])) {
    $user = mysqli_real_escape_string($koneksi, $_POST['username']);
    $pass = $_POST['password'];
    
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$user'");
    $data = mysqli_fetch_assoc($query);

    if ($data && password_verify($pass, $data['password'])) {
        $_SESSION['login'] = true;
        $_SESSION['user']  = $data['username'];
        $_SESSION['role']  = strtolower(trim($data['role']));

        setcookie('user_id', $data['id'], time() + (7 * 24 * 3600), "/");
        setcookie('user_nama', $data['username'], time() + (7 * 24 * 3600), "/");
        setcookie('user_role', $_SESSION['role'], time() + (7 * 24 * 3600), "/");

        // ✅ PERBAIKAN: tambah ../ karena login.php ada di dalam folder api/
        if ($_SESSION['role'] == 'admin') {
    header("Location: admin_dashboard.php");
    exit();
} else {
    header("Location: user_dashboard.php");
    exit();
}
        }
    } else {
        $error_msg = "Username atau Password salah!";
    }
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            height: 100vh; display: flex; justify-content: center; 
            align-items: center; background: #198754; font-family: sans-serif;
        }
        .card { border-radius: 15px; width: 350px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
    <div class="card p-4 bg-white">
        <h3 class="text-center fw-bold mb-4">Login Petugas</h3>
        
        <?php if(isset($error_msg)): ?>
            <div class="alert alert-danger py-2 small text-center"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan Username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
            </div>
            <button class="btn btn-success w-100 fw-bold mb-3" name="login">MASUK SEKARANG</button>
        </form>
        <p class="text-center small mb-0">Belum punya akun? <a href="register.php" class="text-success">Daftar</a></p>
    </div>
</body>
</html>