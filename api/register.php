<?php
ob_start();
session_start();

include "koneksi.php";

if(isset($_POST['daftar'])){
    $user = mysqli_real_escape_string($koneksi, $_POST['username']);
    $pass = $_POST['password'];
    $role = 'user'; // ← Role selalu user, tidak bisa dipilih manual

    if($user == "" || $pass == ""){
        echo "<script>alert('Isi semua field!');</script>";
    } else {
        $pass_enkrip = password_hash($pass, PASSWORD_DEFAULT);
        
        $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$user'");
        if(mysqli_num_rows($cek_user) > 0) {
            echo "<script>alert('Username sudah terdaftar!');</script>";
        } else {
            $sql = "INSERT INTO users (username, password, role) VALUES ('$user', '$pass_enkrip', '$role')";
            
            if(mysqli_query($koneksi, $sql)){
                echo "<script>alert('Registrasi Berhasil! Silakan Login.'); window.location='/api/login.php';</script>";
                exit();
            } else {
                echo "Error: " . mysqli_error($koneksi);
            }
        }
    }
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | WISATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            height: 100vh; display: flex; justify-content: center; 
            align-items: center; background: #0d6efd; font-family: sans-serif;
        }
        .card { border-radius: 15px; width: 380px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
    <div class="card p-4 bg-white border-0">
        <h3 class="text-center fw-bold mb-4 text-primary">Buat Akun</h3>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Input Username" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Input Password" required>
            </div>
            <!-- Role dipilih otomatis sebagai 'user', tidak ditampilkan -->
            <button class="btn btn-primary w-100 fw-bold shadow-sm" name="daftar">DAFTAR SEKARANG</button>
        </form>
        <p class="text-center mt-3 small">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold">Login</a></p>
    </div>
</body>
</html>