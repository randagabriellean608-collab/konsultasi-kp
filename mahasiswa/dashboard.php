<?php
session_start();
require_once '../config/database.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'mahasiswa') {
    header('Location: ../login.php');
    exit();
}

// Ambil data mahasiswa
$user_id = $_SESSION['user_id'];
$query = "SELECT m.*, u.nama, d.id as dosen_id, du.nama as nama_dosen 
          FROM mahasiswa m 
          JOIN users u ON m.user_id = u.id 
          LEFT JOIN dosen d ON m.dosen_id = d.id
          LEFT JOIN users du ON d.user_id = du.id
          WHERE m.user_id = '$user_id'";
$result = mysqli_query($conn, $query);
$mahasiswa = mysqli_fetch_assoc($result);

// Hitung statistik
$mhs_id = $mahasiswa['id'];
$total_konsultasi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE mahasiswa_id = '$mhs_id'"))['total'];
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE mahasiswa_id = '$mhs_id' AND status = 'pending'"))['total'];
$disetujui = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE mahasiswa_id = '$mhs_id' AND status = 'disetujui'"))['total'];
$selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE mahasiswa_id = '$mhs_id' AND status = 'selesai'"))['total'];

// Ambil jadwal terdekat yang sudah disetujui
$jadwal_terdekat = mysqli_query($conn, "SELECT k.*, d.nidn, u.nama as nama_dosen 
                                        FROM konsultasi k 
                                        JOIN dosen d ON k.dosen_id = d.id
                                        JOIN users u ON d.user_id = u.id
                                        WHERE k.mahasiswa_id = '$mhs_id' 
                                        AND k.status = 'disetujui'
                                        AND k.tanggal_konsultasi >= CURDATE()
                                        ORDER BY k.tanggal_konsultasi ASC, k.waktu_konsultasi ASC
                                        LIMIT 5");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Mahasiswa - Sistem Konsultasi KP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        
        /* Navbar */
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 20px; font-weight: bold; color: #333; }
        .navbar-brand span { color: #667eea; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .user-info .nama { font-weight: 500; }
        .user-info .role { background: #667eea; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .logout { background: #ff4757; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        
        /* Container */
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        /* Welcome Card */
        .welcome-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
        .welcome-card h1 { margin-bottom: 10px; }
        .welcome-card p { opacity: 0.9; }
        
        /* Info Dosen */
        .dosen-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .dosen-card h3 { color: #333; margin-bottom: 10px; }
        .dosen-info { background: #f8f9fa; padding: 15px; border-radius: 8px; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #667eea; }
        .stat-card .label { color: #666; margin-top: 5px; }
        
        /* Action Buttons */
        .action-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .action-btn { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-decoration: none; color: #333; transition: transform 0.3s; }
        .action-btn:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .action-btn .icon { font-size: 40px; margin-bottom: 15px; display: block; }
        .action-btn h3 { margin-bottom: 10px; }
        .action-btn p { color: #666; font-size: 14px; }
        
        /* Table */
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .table-container h3 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8f9fa; color: #555; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .status-pending { background: #ffd700; color: #000; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .status-disetujui { background: #4caf50; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .status-ditolak { background: #f44336; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .status-selesai { background: #2196f3; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .btn-small { padding: 5px 10px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">Sistem <span>Konsultasi KP</span></div>
        <div class="user-info">
            <span class="nama"><?php echo $_SESSION['nama']; ?></span>
            <span class="role">Mahasiswa</span>
            <a href="../logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h1>Selamat Datang, <?php echo $_SESSION['nama']; ?>! 👋</h1>
            <p>Kelola konsultasi Kerja Praktik Anda dengan mudah di sini</p>
        </div>
        
        <!-- Info Dosen Pembimbing -->
        <div class="dosen-card">
            <h3>📌 Dosen Pembimbing</h3>
            <?php if ($mahasiswa['dosen_id']): ?>
                <div class="dosen-info">
                    <p><strong><?php echo $mahasiswa['nama_dosen']; ?></strong></p>
                    <p style="color: #666; font-size: 14px;">NIDN: <?php echo $mahasiswa['nidn'] ?? '-'; ?></p>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ Anda belum memiliki dosen pembimbing. Silakan hubungi koordinator KP.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?php echo $total_konsultasi; ?></div>
                <div class="label">Total Konsultasi</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo $pending; ?></div>
                <div class="label">Menunggu</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo $disetujui; ?></div>
                <div class="label">Disetujui</div>
            </div>
            <div class="stat-card">
                <div class="value"><?php echo $selesai; ?></div>
                <div class="label">Selesai</div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="ajukan.php" class="action-btn">
                <span class="icon">📅</span>
                <h3>Ajukan Konsultasi</h3>
                <p>Buat jadwal konsultasi baru dengan dosen pembimbing</p>
            </a>
            <a href="riwayat.php" class="action-btn">
                <span class="icon">📋</span>
                <h3>Riwayat Konsultasi</h3>
                <p>Lihat semua history konsultasi dan feedback dosen</p>
            </a>
            <a href="#" class="action-btn">
                <span class="icon">👤</span>
                <h3>Profil Saya</h3>
                <p>Lihat dan edit data diri Anda</p>
            </a>
        </div>
        
        <!-- Jadwal Terdekat -->
        <div class="table-container">
            <h3>📅 Jadwal Konsultasi Terdekat</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Dosen</th>
                        <th>Topik</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($jadwal_terdekat) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($jadwal_terdekat)): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_konsultasi'])); ?></td>
                            <td><?php echo $row['waktu_konsultasi']; ?></td>
                            <td><?php echo $row['nama_dosen']; ?></td>
                            <td><?php echo $row['topik']; ?></td>
                            <td>
                                <span class="status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn-small">Detail</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666;">Belum ada jadwal konsultasi terdekat</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>