<?php
session_start();
require_once '../config/database.php';

// CEK LOGIN DAN ROLE - INI YANG MEMBEDAKAN DENGAN VERSI MAHASISWA
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dosen') {
    header('Location: ../login.php');
    exit();
}

$id = $_GET['id'] ?? 0;

// Ambil data konsultasi
$query = "SELECT k.*, m.nim, m.jurusan, m.angkatan, u.nama as nama_mahasiswa, u.email,
          d.nidn, du.nama as nama_dosen 
          FROM konsultasi k
          JOIN mahasiswa m ON k.mahasiswa_id = m.id
          JOIN users u ON m.user_id = u.id
          JOIN dosen d ON k.dosen_id = d.id
          JOIN users du ON d.user_id = du.id
          WHERE k.id = '$id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header('Location: permintaan.php');
    exit();
}

$konsultasi = mysqli_fetch_assoc($result);

// Ambil file-file yang diupload
$files_query = "SELECT * FROM konsultasi_files WHERE konsultasi_id = '$id' ORDER BY uploaded_at ASC";
$files_result = mysqli_query($conn, $files_query);
$total_files = mysqli_num_rows($files_result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Konsultasi - Sistem Konsultasi KP</title>
    <style>
        /* COPY SEMUA CSS DARI VERSI MAHASISWA TAPI GANTI WARNA JADI HIJAU (#48bb78) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 20px; font-weight: bold; color: #333; }
        .navbar-brand span { color: #48bb78; } /* GANTI WARNA JADI HIJAU */
        .user-info { display: flex; align-items: center; gap: 20px; }
        .user-info .role { background: #48bb78; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .logout { background: #ff4757; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        .back-link { text-decoration: none; color: #48bb78; }
        
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        
        .detail-card { background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h2 { color: #333; }
        .status-badge { padding: 8px 16px; border-radius: 20px; font-weight: 500; }
        .status-pending { background: #ffd700; color: #000; }
        .status-disetujui { background: #4caf50; color: white; }
        .status-ditolak { background: #f44336; color: white; }
        .status-selesai { background: #2196f3; color: white; }
        
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
        .info-item { background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .info-item .label { font-size: 12px; color: #666; margin-bottom: 5px; }
        .info-item .value { font-size: 16px; font-weight: 500; color: #333; }
        
        .section { margin-bottom: 30px; }
        .section h3 { color: #333; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #48bb78; } /* GANTI WARNA */
        
        .files-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 15px; }
        .file-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; text-align: center; transition: transform 0.3s; }
        .file-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .file-icon { font-size: 48px; margin-bottom: 10px; }
        .file-name { font-weight: 500; word-break: break-word; margin-bottom: 5px; }
        .file-size { font-size: 12px; color: #666; margin-bottom: 10px; }
        .file-actions { display: flex; gap: 5px; justify-content: center; }
        .btn-small { padding: 5px 10px; background: #48bb78; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; }
        .btn-small:hover { background: #38a169; }
        
        .preview-section { margin-top: 30px; border-top: 2px dashed #dee2e6; padding-top: 30px; }
        .pdf-preview { width: 100%; height: 500px; border: 1px solid #dee2e6; border-radius: 8px; }
        .image-preview { max-width: 100%; max-height: 400px; object-fit: contain; border: 1px solid #dee2e6; border-radius: 8px; }
        
        .feedback-box { background: #f8f9fa; padding: 20px; border-radius: 8px; }
        .feedback-text { white-space: pre-line; line-height: 1.6; }
        
        .btn { background: #48bb78; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #38a169; }
        
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">Sistem <span>Konsultasi KP</span></div>
        <div class="user-info">
            <span><?php echo $_SESSION['nama']; ?></span>
            <span class="role">Dosen</span>
            <a href="../logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="permintaan.php" class="back-link">← Kembali ke Permintaan</a>
        </div>
        
        <div class="detail-card">
            <div class="header">
                <h2>Detail Konsultasi</h2>
                <span class="status-badge status-<?php echo $konsultasi['status']; ?>">
                    <?php echo ucfirst($konsultasi['status']); ?>
                </span>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Tanggal Pengajuan</div>
                    <div class="value"><?php echo date('d/m/Y H:i', strtotime($konsultasi['tanggal_pengajuan'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Jadwal Konsultasi</div>
                    <div class="value">
                        <?php echo date('d/m/Y', strtotime($konsultasi['tanggal_konsultasi'])); ?>
                        <br>
                        <small>Pukul <?php echo $konsultasi['waktu_konsultasi']; ?></small>
                    </div>
                </div>
                <div class="info-item">
                    <div class="label">Mahasiswa</div>
                    <div class="value">
                        <?php echo $konsultasi['nama_mahasiswa']; ?>
                        <br>
                        <small>NIM: <?php echo $konsultasi['nim']; ?></small>
                    </div>
                </div>
                <div class="info-item">
                    <div class="label">Dosen Pembimbing</div>
                    <div class="value">
                        <?php echo $konsultasi['nama_dosen']; ?>
                        <br>
                        <small>NIDN: <?php echo $konsultasi['nidn']; ?></small>
                    </div>
                </div>
                <div class="info-item">
                    <div class="label">Jurusan</div>
                    <div class="value"><?php echo $konsultasi['jurusan']; ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Angkatan</div>
                    <div class="value"><?php echo $konsultasi['angkatan']; ?></div>
                </div>
            </div>
            
            <div class="section">
                <h3>Topik Konsultasi</h3>
                <p style="font-size: 16px; margin-bottom: 10px;"><strong><?php echo $konsultasi['topik']; ?></strong></p>
                <?php if ($konsultasi['deskripsi']): ?>
                    <p style="color: #666; line-height: 1.6;"><?php echo nl2br($konsultasi['deskripsi']); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if ($total_files > 0): ?>
            <div class="section">
                <h3>File Lampiran (<?php echo $total_files; ?> file)</h3>
                <div class="files-grid">
                    <?php while($file = mysqli_fetch_assoc($files_result)): 
                        $ext = $file['file_type'];
                        $icon = [
                            'pdf' => '📕',
                            'doc' => '📘', 'docx' => '📘',
                            'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️',
                            'xls' => '📊', 'xlsx' => '📊',
                            'ppt' => '📽️', 'pptx' => '📽️'
                        ][$ext] ?? '📄';
                        
                        $size = round($file['file_size'] / 1024, 2);
                    ?>
                    <div class="file-card">
                        <div class="file-icon"><?php echo $icon; ?></div>
                        <div class="file-name"><?php echo $file['file_name']; ?></div>
                        <div class="file-size"><?php echo $size; ?> KB</div>
                        <div class="file-actions">
                            <a href="../<?php echo $file['file_path']; ?>" target="_blank" class="btn-small">Lihat</a>
                            <a href="../<?php echo $file['file_path']; ?>" download class="btn-small">Download</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Preview Section untuk file tertentu -->
                <?php 
                // Reset pointer files_result
                mysqli_data_seek($files_result, 0);
                
                // Cari file PDF atau gambar untuk preview
                $preview_file = null;
                while($file = mysqli_fetch_assoc($files_result)) {
                    if (in_array($file['file_type'], ['pdf', 'jpg', 'jpeg', 'png'])) {
                        $preview_file = $file;
                        break;
                    }
                }
                
                if ($preview_file): 
                ?>
                <div class="preview-section">
                    <h4>Preview File: <?php echo $preview_file['file_name']; ?></h4>
                    <?php if ($preview_file['file_type'] == 'pdf'): ?>
                        <iframe src="../<?php echo $preview_file['file_path']; ?>" class="pdf-preview"></iframe>
                    <?php elseif (in_array($preview_file['file_type'], ['jpg', 'jpeg', 'png'])): ?>
                        <img src="../<?php echo $preview_file['file_path']; ?>" class="image-preview">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($konsultasi['catatan_dosen']): ?>
            <div class="section">
                <h3>Feedback yang Diberikan</h3>
                <div class="feedback-box">
                    <div class="feedback-text">
                        <?php echo nl2br($konsultasi['catatan_dosen']); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 30px;">
                <?php if ($konsultasi['status'] == 'disetujui'): ?>
                    <a href="feedback.php?id=<?php echo $konsultasi['id']; ?>" class="btn">Beri Feedback</a>
                <?php endif; ?>
                <a href="permintaan.php" class="btn">Kembali</a>
            </div>
        </div>
    </div>
</body>
</html>