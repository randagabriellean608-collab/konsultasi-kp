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
$query = "SELECT m.id FROM mahasiswa m WHERE m.user_id = '$user_id'";
$result = mysqli_query($conn, $query);
$mahasiswa = mysqli_fetch_assoc($result);
$mahasiswa_id = $mahasiswa['id'];

// Filter status
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$where = "WHERE k.mahasiswa_id = '$mahasiswa_id'";
if ($filter != 'semua') {
    $where .= " AND k.status = '$filter'";
}

// Ambil riwayat konsultasi
$query = "SELECT k.*, d.nidn, u.nama as nama_dosen 
          FROM konsultasi k 
          JOIN dosen d ON k.dosen_id = d.id
          JOIN users u ON d.user_id = u.id
          $where 
          ORDER BY k.tanggal_pengajuan DESC";
$riwayat = mysqli_query($conn, $query);

// Hitung statistik untuk badge
$total_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE mahasiswa_id = '$mahasiswa_id'"))['total'];
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE mahasiswa_id = '$mahasiswa_id' AND status='pending'"))['total'];
$total_disetujui = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE mahasiswa_id = '$mahasiswa_id' AND status='disetujui'"))['total'];
$total_selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE mahasiswa_id = '$mahasiswa_id' AND status='selesai'"))['total'];
$total_ditolak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM konsultasi WHERE mahasiswa_id = '$mahasiswa_id' AND status='ditolak'"))['total'];
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
        .navbar-brand span { color: #667eea; }
        .back-link { text-decoration: none; color: #667eea; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .header-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header-card h2 { color: #333; margin-bottom: 15px; }
        
        .filter-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .filter-btn { padding: 8px 16px; border: 1px solid #ddd; border-radius: 20px; text-decoration: none; color: #666; background: white; }
        .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
        .filter-btn:hover { background: #f0f0f0; }
        .filter-btn.active:hover { background: #5a67d8; }
        
        .stats-badge { display: inline-block; background: #f0f0f0; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 5px; }
        
        .table-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8f9fa; color: #555; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        
        .status-pending { background: #ffd700; color: #000; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .status-disetujui { background: #4caf50; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .status-ditolak { background: #f44336; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .status-selesai { background: #2196f3; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        
        .btn-small { padding: 5px 10px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; display: inline-block; }
        .btn-small:hover { background: #5a67d8; }
        
        .file-link { color: #667eea; text-decoration: none; }
        .file-link:hover { text-decoration: underline; }
        
        .feedback-text { background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 13px; color: #555; margin-top: 5px; }
        
        .no-data { text-align: center; padding: 40px; color: #666; }
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
            
            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <a href="?filter=semua" class="filter-btn <?php echo $filter == 'semua' ? 'active' : ''; ?>">
                    Semua <span class="stats-badge"><?php echo $total_all; ?></span>
                </a>
                <a href="?filter=pending" class="filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                    Pending <span class="stats-badge"><?php echo $total_pending; ?></span>
                </a>
                <a href="?filter=disetujui" class="filter-btn <?php echo $filter == 'disetujui' ? 'active' : ''; ?>">
                    Disetujui <span class="stats-badge"><?php echo $total_disetujui; ?></span>
                </a>
                <a href="?filter=selesai" class="filter-btn <?php echo $filter == 'selesai' ? 'active' : ''; ?>">
                    Selesai <span class="stats-badge"><?php echo $total_selesai; ?></span>
                </a>
                <a href="?filter=ditolak" class="filter-btn <?php echo $filter == 'ditolak' ? 'active' : ''; ?>">
                    Ditolak <span class="stats-badge"><?php echo $total_ditolak; ?></span>
                </a>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal Pengajuan</th>
                        <th>Jadwal</th>
                        <th>Dosen</th>
                        <th>Topik</th>
                        <th>File</th>
                        <th>Status</th>
                        <th>Feedback</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($riwayat) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($riwayat)): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($row['tanggal_konsultasi'])); ?><br>
                                <small><?php echo $row['waktu_konsultasi']; ?></small>
                            </td>
                            <td><?php echo $row['nama_dosen']; ?></td>
                            <td>
                                <strong><?php echo $row['topik']; ?></strong>
                                <?php if ($row['deskripsi']): ?>
                                    <br><small><?php echo substr($row['deskripsi'], 0, 50); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['file_path']): ?>
                                    <a href="../<?php echo $row['file_path']; ?>" target="_blank" class="file-link">📎 Lihat File</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['catatan_dosen']): ?>
                                    <div class="feedback-text">
                                        <?php echo nl2br(substr($row['catatan_dosen'], 0, 100)); ?>
                                        <?php if (strlen($row['catatan_dosen']) > 100): ?>
                                            ... <a href="detail.php?id=<?php echo $row['id']; ?>">selengkapnya</a>
                                        <?php endif; ?>
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
                            <td colspan="8" class="no-data">
                                <?php if ($filter == 'semua'): ?>
                                    Belum ada riwayat konsultasi. 
                                    <a href="ajukan.php" style="color: #667eea;">Ajukan konsultasi sekarang</a>
                                <?php else: ?>
                                    Tidak ada konsultasi dengan status <?php echo $filter; ?>.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>