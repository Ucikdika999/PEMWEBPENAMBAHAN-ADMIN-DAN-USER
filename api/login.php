<?php
ob_start(); // Memulai output buffering untuk mencegah error "headers already sent"
session_start();
include "koneksi.php";

        if (isset($_POST['login'])) {
             $user = mysqli_real_escape_string($koneksi, $_POST['username']);
             $pass = $_POST['password'];
    
    // Cari user di database
            $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$user'");
            $data = mysqli_fetch_assoc($query);

    // Cek apakah user ada dan password cocok
        if ($data && password_verify($pass, $data['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['user']  = $data['username'];
            $_SESSION['role']  = $data['role'];

    // DEBUG: Cek isi data sebelum loncat
    // echo "Role ditemukan: " . $data['role']; 
    // die(); 

        if (strtolower($data['role']) == 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        header("Location: user_dashboard.php");
        exit();
    }
} else {
    // Cek apakah user ketemu tapi password salah, atau user memang tidak ada
    if(!$data) {
        echo "Username tidak ditemukan di database!";
    } else {
        echo "Password salah! Pastikan database menggunakan password_hash.";
    }
    die();
}
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