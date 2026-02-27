<?php
session_start();
require_once 'config/database.php';

$error = '';

// CEK APAKAH ADA PESAN SUKSES DARI REGISTER
$success = '';
if (isset($_SESSION['register_success'])) {
    $success = $_SESSION['register_success'];
    unset($_SESSION['register_success']); // Hapus session agar tidak muncul terus
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] == 'mahasiswa') {
                header('Location: mahasiswa/dashboard.php');
            } else {
                header('Location: dosen/dashboard.php');
            }
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Sistem Konsultasi KP</title>
    <style>
        body { font-family: Arial; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); width: 350px; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #1877f2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #166fe5; }
        .error { color: red; margin-bottom: 10px; text-align: center; }
        .success { color: green; margin-bottom: 10px; text-align: center; background: #e8f5e9; padding: 10px; border-radius: 4px; }
        .register-link { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login Sistem Konsultasi KP</h2>
        
        <!-- TAMPILKAN PESAN SUKSES JIKA ADA -->
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>
</body>
</html>