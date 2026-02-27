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

// Proses approve/tolak
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        mysqli_query($conn, "UPDATE konsultasi SET status = 'disetujui' WHERE id = '$id' AND dosen_id = '$dosen_id'");
        $message = "Konsultasi berhasil disetujui!";
    } elseif ($action == 'reject') {
        mysqli_query($conn, "UPDATE konsultasi SET status = 'ditolak' WHERE id = '$id' AND dosen_id = '$dosen_id'");
        $message = "Konsultasi ditolak.";
    }
}

// Ambil daftar permintaan
$query = "SELECT k.*, m.nim, u.nama as nama_mahasiswa 
          FROM konsultasi k 
          JOIN mahasiswa m ON k.mahasiswa_id = m.id
          JOIN users u ON m.user_id = u.id
          WHERE k.dosen_id = '$dosen_id' 
          ORDER BY k.tanggal_pengajuan DESC";
$permintaan = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Permintaan Konsultasi - Sistem Konsultasi KP</title>
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
        .back-link { text-decoration: none; color: #48bb78; margin-right: 20px; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .header-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header-card h2 { color: #333; margin-bottom: 15px; }
        
        .message { background: #c6f6d5; color: #22543d; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        
        .table-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8f9fa; color: #555; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        
        .status-pending { background: #ffd700; color: #000; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .status-disetujui { background: #4caf50; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .status-ditolak { background: #f44336; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .status-selesai { background: #2196f3; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        
        .btn-small { padding: 5px 10px; background: #48bb78; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; display: inline-block; margin: 0 2px; }
        .btn-small-danger { background: #f56565; }
        .btn-small-warning { background: #ed8936; }
        .btn-small-info { background: #4299e1; }
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
        <div style="margin-bottom: 20px;">
            <a href="dashboard.php" class="back-link">← Kembali ke Dashboard</a>
        </div>
        
        <div class="header-card">
            <h2>Daftar Permintaan Konsultasi</h2>
            
            <?php if (isset($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Mahasiswa</th>
                        <th>NIM</th>
                        <th>Topik</th>
                        <th>Jadwal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($permintaan) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($permintaan)): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                            <td><?php echo $row['nama_mahasiswa']; ?></td>
                            <td><?php echo $row['nim']; ?></td>
                            <td><?php echo $row['topik']; ?></td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($row['tanggal_konsultasi'])); ?>
                                <br>
                                <small><?php echo $row['waktu_konsultasi']; ?></small>
                            </td>
                            <td>
                                <span class="status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn-small btn-small-info">Detail</a>
                                
                                <?php if ($row['status'] == 'pending'): ?>
                                    <a href="?action=approve&id=<?php echo $row['id']; ?>" 
                                       class="btn-small" onclick="return confirm('Setujui konsultasi ini?')">Setujui</a>
                                    <a href="?action=reject&id=<?php echo $row['id']; ?>" 
                                       class="btn-small btn-small-danger" onclick="return confirm('Tolak konsultasi ini?')">Tolak</a>
                                <?php elseif ($row['status'] == 'disetujui'): ?>
                                    <a href="feedback.php?id=<?php echo $row['id']; ?>" class="btn-small btn-small-warning">Beri Feedback</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                Belum ada permintaan konsultasi
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>