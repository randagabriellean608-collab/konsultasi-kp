<?php
session_start();
require_once '../config/database.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dosen') {
    header('Location: ../login.php');
    exit();
}

$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Ambil data konsultasi
$query = "SELECT k.*, m.nim, m.jurusan, u.nama as nama_mahasiswa 
          FROM konsultasi k
          JOIN mahasiswa m ON k.mahasiswa_id = m.id
          JOIN users u ON m.user_id = u.id
          WHERE k.id = '$id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header('Location: permintaan.php');
    exit();
}

$konsultasi = mysqli_fetch_assoc($result);

// Proses submit feedback
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $catatan_dosen = mysqli_real_escape_string($conn, $_POST['catatan_dosen']);
    
    $update = "UPDATE konsultasi SET catatan_dosen = '$catatan_dosen', status = 'selesai' WHERE id = '$id'";
    if (mysqli_query($conn, $update)) {
        header('Location: permintaan.php?filter=selesai&msg=feedback_success');
        exit();
    } else {
        $error = "Gagal menyimpan feedback: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Beri Feedback - Sistem Konsultasi KP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 20px; font-weight: bold; color: #333; }
        .navbar-brand span { color: #48bb78; }
        .back-link { text-decoration: none; color: #48bb78; }
        
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        
        .form-card { background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px; }
        .form-card h2 { margin-bottom: 30px; color: #333; border-bottom: 2px solid #48bb78; padding-bottom: 10px; }
        
        .info-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .info-section h3 { color: #333; margin-bottom: 15px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .info-item .label { font-size: 12px; color: #666; margin-bottom: 5px; }
        .info-item .value { font-size: 16px; font-weight: 500; color: #333; }
        
        .file-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px; }
        .file-box a { color: #48bb78; text-decoration: none; }
        .file-box a:hover { text-decoration: underline; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
        textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; resize: vertical; min-height: 200px; }
        
        .btn { background: #48bb78; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #38a169; }
        
        .error { background: #fed7d7; color: #9b2c2c; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">Sistem <span>Konsultasi KP</span></div>
        <a href="permintaan.php" class="back-link">← Kembali ke Permintaan</a>
    </div>
    
    <div class="container">
        <div class="form-card">
            <h2>Beri Feedback Konsultasi</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="info-section">
                <h3>Informasi Konsultasi</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Mahasiswa</div>
                        <div class="value"><?php echo $konsultasi['nama_mahasiswa']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">NIM</div>
                        <div class="value"><?php echo $konsultasi['nim']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Jurusan</div>
                        <div class="value"><?php echo $konsultasi['jurusan']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Tanggal Konsultasi</div>
                        <div class="value">
                            <?php echo date('d/m/Y', strtotime($konsultasi['tanggal_konsultasi'])); ?>
                            <br>
                            <small><?php echo $konsultasi['waktu_konsultasi']; ?></small>
                        </div>
                    </div>
                    <div class="info-item" style="grid-column: span 2;">
                        <div class="label">Topik</div>
                        <div class="value"><?php echo $konsultasi['topik']; ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($konsultasi['file_path']): ?>
            <div class="file-box">
                <strong>File Lampiran:</strong><br>
                <a href="../<?php echo $konsultasi['file_path']; ?>" target="_blank">
                    📎 <?php echo basename($konsultasi['file_path']); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Catatan / Feedback untuk Mahasiswa</label>
                    <textarea name="catatan_dosen" placeholder="Tuliskan masukan, koreksi, atau catatan untuk mahasiswa..." required></textarea>
                </div>
                
                <button type="submit" class="btn">Simpan Feedback & Selesaikan Konsultasi</button>
            </form>
        </div>
    </div>
</body>
</html>