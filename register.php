<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    // Cek email sudah terdaftar atau belum
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email sudah terdaftar!";
    } else {
        // Insert ke tabel users
        $query = "INSERT INTO users (nama, email, password, role) VALUES ('$nama', '$email', '$password', '$role')";
        if (mysqli_query($conn, $query)) {
            $user_id = mysqli_insert_id($conn);
            
            // Insert data tambahan sesuai role
            if ($role == 'mahasiswa') {
                $nim = $_POST['nim'];
                $jurusan = $_POST['jurusan'];
                $angkatan = $_POST['angkatan'];
                $dosen_id = $_POST['dosen_id'];
                
                $query2 = "INSERT INTO mahasiswa (user_id, nim, jurusan, angkatan, dosen_id) 
                          VALUES ('$user_id', '$nim', '$jurusan', '$angkatan', '$dosen_id')";
                mysqli_query($conn, $query2);
                
                // SETELAH REGISTER, LANGSUNG KE LOGIN DENGAN PESAN SUKSES
                $_SESSION['register_success'] = "Registrasi berhasil! Silakan login dengan akun Anda.";
                header('Location: login.php');
                exit();
                
            } else {
                $nidn = $_POST['nidn'];
                $keahlian = $_POST['keahlian'];
                
                $query2 = "INSERT INTO dosen (user_id, nidn, keahlian) VALUES ('$user_id', '$nidn', '$keahlian')";
                mysqli_query($conn, $query2);
                
                // SETELAH REGISTER, LANGSUNG KE LOGIN DENGAN PESAN SUKSES
                $_SESSION['register_success'] = "Registrasi berhasil! Silakan login dengan akun Anda.";
                header('Location: login.php');
                exit();
            }
        } else {
            $error = "Registrasi gagal: " . mysqli_error($conn);
        }
    }
}

// Ambil daftar dosen untuk dropdown
$dosen_list = mysqli_query($conn, "SELECT d.id, u.nama FROM dosen d JOIN users u ON d.user_id = u.id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Sistem Konsultasi KP</title>
    <style>
        body { font-family: Arial; background: #f0f2f5; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #1877f2; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #166fe5; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
        .login-link { text-align: center; margin-top: 15px; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registrasi Sistem Konsultasi KP</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Daftar Sebagai</label>
                <select name="role" id="role" required onchange="toggleForm()">
                    <option value="">Pilih Role</option>
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="dosen">Dosen</option>
                </select>
            </div>
            
            <!-- Form Mahasiswa -->
            <div id="form-mahasiswa" class="hidden">
                <h3>Data Mahasiswa</h3>
                <div class="form-group">
                    <label>NIM</label>
                    <input type="text" name="nim">
                </div>
                <div class="form-group">
                    <label>Jurusan</label>
                    <input type="text" name="jurusan">
                </div>
                <div class="form-group">
                    <label>Angkatan</label>
                    <input type="text" name="angkatan">
                </div>
                <div class="form-group">
                    <label>Pilih Dosen Pembimbing</label>
                    <select name="dosen_id">
                        <option value="">Pilih Dosen</option>
                        <?php while($dosen = mysqli_fetch_assoc($dosen_list)): ?>
                            <option value="<?php echo $dosen['id']; ?>">
                                <?php echo $dosen['nama']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <!-- Form Dosen -->
            <div id="form-dosen" class="hidden">
                <h3>Data Dosen</h3>
                <div class="form-group">
                    <label>NIDN</label>
                    <input type="text" name="nidn">
                </div>
                <div class="form-group">
                    <label>Keahlian</label>
                    <textarea name="keahlian" rows="3"></textarea>
                </div>
            </div>
            
            <button type="submit">Daftar</button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
    
    <script>
    function toggleForm() {
        var role = document.getElementById('role').value;
        document.getElementById('form-mahasiswa').classList.add('hidden');
        document.getElementById('form-dosen').classList.add('hidden');
        
        if (role === 'mahasiswa') {
            document.getElementById('form-mahasiswa').classList.remove('hidden');
        } else if (role === 'dosen') {
            document.getElementById('form-dosen').classList.remove('hidden');
        }
    }
    </script>
</body>
</html>