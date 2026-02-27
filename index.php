<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Sistem Konsultasi Kerja Praktik</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; padding: 50px 20px; text-align: center; color: white; }
        h1 { font-size: 48px; margin-bottom: 20px; }
        p { font-size: 18px; line-height: 1.6; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 15px 30px; margin: 10px; background: white; color: #667eea; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn:hover { background: #f0f0f0; }
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 50px; }
        .feature { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; backdrop-filter: blur(10px); }
        .feature h3 { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistem Konsultasi Kerja Praktik</h1>
        <p>Platform untuk memudahkan proses konsultasi antara mahasiswa dan dosen pembimbing dalam pelaksanaan Kerja Praktik</p>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div>
                <a href="login.php" class="btn">Login</a>
                <a href="register.php" class="btn">Register</a>
            </div>
        <?php else: ?>
            <div>
                <?php if ($_SESSION['role'] == 'mahasiswa'): ?>
                    <a href="mahasiswa/dashboard.php" class="btn">Dashboard Mahasiswa</a>
                <?php else: ?>
                    <a href="dosen/dashboard.php" class="btn">Dashboard Dosen</a>
                <?php endif; ?>
                <a href="logout.php" class="btn" style="background: #ff6b6b; color: white;">Logout</a>
            </div>
        <?php endif; ?>
        
        <div class="features">
            <div class="feature">
                <h3>📅 Jadwal Konsultasi</h3>
                <p>Atur jadwal konsultasi dengan mudah</p>
            </div>
            <div class="feature">
                <h3>📁 Upload File</h3>
                <p>Upload laporan dan dokumen KP</p>
            </div>
            <div class="feature">
                <h3>💬 Feedback</h3>
                <p>Dapatkan masukan dari dosen pembimbing</p>
            </div>
        </div>
    </div>
</body>
</html>