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

// Ambil daftar mahasiswa bimbingan
$query = "SELECT m.*, u.nama, u.email,
          (SELECT COUNT(*) FROM konsultasi WHERE mahasiswa_id = m.id) as total_konsultasi,
          (SELECT COUNT(*) FROM konsultasi WHERE mahasiswa_id = m.id AND status = 'pending') as pending,
          (SELECT MAX(tanggal_konsultasi) FROM konsultasi WHERE mahasiswa_id = m.id AND status = 'selesai') as terakhir_konsultasi
          FROM mahasiswa m
          JOIN users u ON m.user_id = u.id
          WHERE m.dosen_id = '$dosen_id'
          ORDER BY m.nim ASC";
$mahasiswa = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mahasiswa Bimbingan - Sistem Konsultasi KP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 20px; font-weight: bold; color: #333; }
        .navbar-brand span { color: #48bb78; }
        .back-link { text-decoration: none; color: #48bb78; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .header-card { background: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header-card h2 { color: #333; margin-bottom: 10px; }
        .header-card p { color: #666; }
        
        .stats-card { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .stats-card .total { font-size: 36px; font-weight: bold; }
        
        .table-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8f9fa; color: #555; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        
        .badge { background: #e2e8f0; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .badge-pending { background: #fed7d7; color: #c53030; }
        
        .btn-small { padding: 5px 10px; background: #48bb78; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; display: inline-block; }
        .btn-small:hover { background: #38a169; }
        
        .search-box { margin-bottom: 20px; }
        .search-box input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">Sistem <span>Konsultasi KP</span></div>
        <a href="dashboard.php" class="back-link">← Kembali ke Dashboard</a>
    </div>
    
    <div class="container">
        <div class="header-card">
            <h2>Mahasiswa Bimbingan</h2>
            <p>Daftar mahasiswa yang Anda bimbing untuk Kerja Praktik</p>
        </div>
        
        <?php 
        $total = mysqli_num_rows($mahasiswa);
        mysqli_data_seek($mahasiswa, 0); // Reset pointer
        ?>
        
        <div class="stats-card">
            <div>Total Mahasiswa Bimbingan</div>
            <div class="total"><?php echo $total; ?> Orang</div>
        </div>
        
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Cari mahasiswa berdasarkan NIM atau nama...">
        </div>
        
        <div class="table-container">
            <table id="mahasiswaTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIM</th>
                        <th>Nama</th>
                        <th>Jurusan</th>
                        <th>Angkatan</th>
                        <th>Total Konsultasi</th>
                        <th>Pending</th>
                        <th>Konsultasi Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total > 0): ?>
                        <?php $no = 1; ?>
                        <?php while($row = mysqli_fetch_assoc($mahasiswa)): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo $row['nim']; ?></strong></td>
                            <td><?php echo $row['nama']; ?></td>
                            <td><?php echo $row['jurusan']; ?></td>
                            <td><?php echo $row['angkatan']; ?></td>
                            <td style="text-align: center;"><?php echo $row['total_konsultasi']; ?></td>
                            <td>
                                <?php if ($row['pending'] > 0): ?>
                                    <span class="badge badge-pending"><?php echo $row['pending']; ?> pending</span>
                                <?php else: ?>
                                    <span class="badge">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($row['terakhir_konsultasi']) {
                                    echo date('d/m/Y', strtotime($row['terakhir_konsultasi']));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="riwayat-mahasiswa.php?nim=<?php echo $row['nim']; ?>" class="btn-small">Lihat Riwayat</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #666;">
                                Belum ada mahasiswa bimbingan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    // Fitur pencarian sederhana
    document.getElementById('searchInput').addEventListener('keyup', function() {
        var searchText = this.value.toLowerCase();
        var tableRows = document.querySelectorAll('#mahasiswaTable tbody tr');
        
        tableRows.forEach(function(row) {
            var nim = row.cells[1].textContent.toLowerCase();
            var nama = row.cells[2].textContent.toLowerCase();
            
            if (nim.includes(searchText) || nama.includes(searchText)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>