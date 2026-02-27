<?php
session_start();
require_once '../config/database.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dosen') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT id FROM dosen WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);
$dosen = mysqli_fetch_assoc($result);
$dosen_id = $dosen['id'];

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$where = "WHERE k.dosen_id = '$dosen_id' AND k.status IN ('selesai', 'ditolak')";
if ($filter != 'semua') {
    $where .= " AND k.status = '$filter'";
}

$query = "SELECT k.*, m.nim, u.nama as nama_mahasiswa 
          FROM konsultasi k 
          JOIN mahasiswa m ON k.mahasiswa_id = m.id
          JOIN users u ON m.user_id = u.id
          $where 
          ORDER BY k.tanggal_konsultasi DESC";
$riwayat = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Konsultasi - Sistem Konsultasi KP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 20px; font-weight: bold; color: #333; }
        .navbar-brand span { color: #48bb78; }
        .back-link { text-decoration: none; color: #48bb78; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .header-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header-card h2 { color: #333; margin-bottom: 15px; }
        
        .filter-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .filter-btn { padding: 8px 16px; border: 1px solid #ddd; border-radius: 20px; text-decoration: none; color: #666; background: white; }
        .filter-btn.active { background: #48bb78; color: white; border-color: #48bb78; }
        
        .table-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8f9fa; color: #555; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        
        .status-selesai { background: #4caf50; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .status-ditolak { background: #f44336; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        
        .feedback-preview { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #666; }
        
        .btn-small { padding: 5px 10px; background: #48bb78; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; display: inline-block; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">Sistem <span>Konsultasi KP</span></div>
        <a href="dashboard.php" class="back-link">← Kembali ke Dashboard</a>
    </div>
    
    <div class="container">
        <div class="header-card">
            <h2>Riwayat Konsultasi</h2>
            
            <div class="filter-buttons">
                <a href="?filter=semua" class="filter-btn <?php echo $filter == 'semua' ? 'active' : ''; ?>">Semua</a>
                <a href="?filter=selesai" class="filter-btn <?php echo $filter == 'selesai' ? 'active' : ''; ?>">Selesai</a>
                <a href="?filter=ditolak" class="filter-btn <?php echo $filter == 'ditolak' ? 'active' : ''; ?>">Ditolak</a>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Mahasiswa</th>
                        <th>NIM</th>
                        <th>Topik</th>
                        <th>Status</th>
                        <th>Feedback</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($riwayat) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($riwayat)): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_konsultasi'])); ?></td>
                            <td><?php echo $row['nama_mahasiswa']; ?></td>
                            <td><?php echo $row['nim']; ?></td>
                            <td><?php echo $row['topik']; ?></td>
                            <td>
                                <span class="status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['catatan_dosen']): ?>
                                    <div class="feedback-preview">
                                        <?php echo substr($row['catatan_dosen'], 0, 50); ?>...
                                    </div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn-small">Detail</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                Belum ada riwayat konsultasi
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>