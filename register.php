<?php
include "koneksi.php";

if(isset($_POST['daftar'])){
    $user = mysqli_real_escape_string($koneksi, $_POST['username']);
    $pass = $_POST['password'];
    $role = $_POST['role']; 

    if($user == "" || $pass == "" || $role == ""){
        echo "<script>alert('Isi semua field!');</script>";
    } else {
        $pass_enkrip = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role) VALUES ('$user', '$pass_enkrip', '$role')";
        
        if(mysqli_query($koneksi, $sql)){
            echo "<script>alert('Registrasi Berhasil!'); window.location='login.php';</script>";
        } else {
            echo "Error: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register | WIDATA</title>
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
    <div class="card p-4 bg-white">
        <h3 class="text-center fw-bold mb-4">Buat Akun</h3>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Input Username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Input Password" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Daftar Sebagai</label>
                <select name="role" class="form-select" required>
                    <option value="" disabled selected>Pilih Role...</option>
                    <option value="user">Pengunjung (User)</option>
                    <option value="admin">Petugas (Admin)</option>
                </select>
            </div>
            <button class="btn btn-primary w-100 fw-bold" name="daftar">DAFTAR SEKARANG</button>
        </form>
        <p class="text-center mt-3 small">Sudah punya akun? <a href="login.php">Login</a></p>
    </div>
</body>
</html>