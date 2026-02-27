<?php
session_start();
require_once '../config/database.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dosen') {
    header('Location: ../login.php');
    exit();
}

// Ambil data dosen
$user_id = $_SESSION['user_id'];
$query = "SELECT d.*, u.nama FROM dosen d JOIN users u ON d.user_id = u.id WHERE d.user_id = '$user_id'";
$result = mysqli_query($conn, $query);
$dosen = mysqli_fetch_assoc($result);
$dosen_id = $dosen['id'];

// Hitung statistik
$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mahasiswa WHERE dosen_id = '$dosen_id'"))['total'];
$total_konsultasi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE dosen_id = '$dosen_id'"))['total'];
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE dosen_id = '$dosen_id' AND status = 'pending'"))['total'];
$selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE dosen_id = '$dosen_id' AND status = 'selesai'"))['total'];

// Ambil permintaan terbaru
$permintaan_terbaru = mysqli_query($conn, "SELECT k.*, m.nim, u.nama as nama_mahasiswa 
                                          FROM konsultasi k 
                                          JOIN mahasiswa m ON k.mahasiswa_id = m.id
                                          JOIN users u ON m.user_id = u.id
                                          WHERE k.dosen_id = '$dosen_id' 
                                          AND k.status = 'pending'
                                          ORDER BY k.tanggal_pengajuan DESC
                                          LIMIT 5");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Dosen - Sistem Konsultasi KP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 20px; font-weight: bold; color: #333; }
        .navbar-brand span { color: #48bb78; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .user-info .nama { font-weight: 500; }
        .user-info .role { background: #48bb78; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .logout { background: #ff4757; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .welcome-card { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
        .welcome-card h1 { margin-bottom: 10px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #48bb78; }
        
        .action-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .action-btn { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-decoration: none; color: #333; text-align: center; transition: transform 0.3s; }
        .action-btn:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .action-btn .icon { font-size: 40px; margin-bottom: 15px; display: block; }
        
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .table-container h3 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8f9fa; color: #555; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .status-pending { background: #ffd700; color: #000; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .btn-small { padding: 5px 10px; background: #48bb78; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">Sistem <span>Konsultasi KP</span></div>
        <div class="user-info">
            <span class="nama"><?php echo $_SESSION['nama']; ?></span>
            <span class="role">Dosen</span>
            <a href="../logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h1>Selamat Datang, <?php echo $_SESSION['nama']; ?>! 👋</h1>
            <p>Kelola bimbingan Kerja Praktik mahasiswa Anda di sini</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?php echo $total_mahasiswa; ?></div>
                <div class="label">Mahasiswa Bimbingan</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo $pending; ?></div>
                <div class="label">Permintaan Baru</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo $total_konsultasi; ?></div>
                <div class="label">Total Konsultasi</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo $selesai; ?></div>
                <div class="label">Selesai</div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="permintaan.php" class="action-btn">
                <span class="icon">📋</span>
                <h3>Permintaan Konsultasi</h3>
                <p><?php echo $pending; ?> permintaan baru</p>
            </a>
            <a href="mahasiswa-bimbingan.php" class="action-btn">
                <span class="icon">👥</span>
                <h3>Mahasiswa Bimbingan</h3>
                <p><?php echo $total_mahasiswa; ?> mahasiswa</p>
            </a>
            <a href="riwayat.php" class="action-btn">
                <span class="icon">📚</span>
                <h3>Riwayat Konsultasi</h3>
                <p>Lihat history</p>
            </a>
        </div>
        
        <?php if ($pending > 0): ?>
        <div class="table-container">
            <h3>📌 Permintaan Konsultasi Baru</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Mahasiswa</th>
                        <th>NIM</th>
                        <th>Topik</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($permintaan_terbaru)): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                        <td><?php echo $row['nama_mahasiswa']; ?></td>
                        <td><?php echo $row['nim']; ?></td>
                        <td><?php echo $row['topik']; ?></td>
                        <td>
                            <a href="permintaan.php" class="btn-small">Detail</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>