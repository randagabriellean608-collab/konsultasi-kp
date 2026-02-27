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
$query = "SELECT m.* FROM mahasiswa m WHERE m.user_id = '$user_id'";
$result = mysqli_query($conn, $query);
$mahasiswa = mysqli_fetch_assoc($result);

// Cek apakah punya dosen pembimbing
if (!$mahasiswa['dosen_id']) {
    $_SESSION['error'] = "Anda belum memiliki dosen pembimbing. Tidak bisa mengajukan konsultasi.";
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal_konsultasi = $_POST['tanggal_konsultasi'];
    $waktu_konsultasi = $_POST['waktu_konsultasi'];
    $topik = $_POST['topik'];
    $deskripsi = $_POST['deskripsi'];
    
    // Validasi
    if (empty($tanggal_konsultasi) || empty($waktu_konsultasi) || empty($topik)) {
        $error = "Semua field wajib diisi!";
    } else {
        $mahasiswa_id = $mahasiswa['id'];
        $dosen_id = $mahasiswa['dosen_id'];
        $tanggal_pengajuan = date('Y-m-d H:i:s');
        
        // Insert ke tabel konsultasi
        $query = "INSERT INTO konsultasi (mahasiswa_id, dosen_id, tanggal_pengajuan, tanggal_konsultasi, waktu_konsultasi, topik, deskripsi, status) 
                  VALUES ('$mahasiswa_id', '$dosen_id', '$tanggal_pengajuan', '$tanggal_konsultasi', '$waktu_konsultasi', '$topik', '$deskripsi', 'pending')";
        
        if (mysqli_query($conn, $query)) {
            $konsultasi_id = mysqli_insert_id($conn);
            
            // Proses upload multiple files
            if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
                $target_dir = "../uploads/konsultasi_" . $konsultasi_id . "/";
                
                // Buat folder khusus untuk konsultasi ini
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_count = count($_FILES['files']['name']);
                $upload_success = 0;
                
                for ($i = 0; $i < $file_count; $i++) {
                    $file_name = $_FILES['files']['name'][$i];
                    $file_tmp = $_FILES['files']['tmp_name'][$i];
                    $file_size = $_FILES['files']['size'][$i];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Validasi tipe file
                    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx', 'ppt', 'pptx'];
                    if (in_array($file_ext, $allowed)) {
                        // Buat nama file unik
                        $new_file_name = time() . "_" . $i . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
                        $target_file = $target_dir . $new_file_name;
                        
                        if (move_uploaded_file($file_tmp, $target_file)) {
                            $file_path = 'uploads/konsultasi_' . $konsultasi_id . '/' . $new_file_name;
                            
                            // Simpan ke database
                            $query_file = "INSERT INTO konsultasi_files (konsultasi_id, file_path, file_name, file_size, file_type) 
                                          VALUES ('$konsultasi_id', '$file_path', '$file_name', '$file_size', '$file_ext')";
                            mysqli_query($conn, $query_file);
                            $upload_success++;
                        }
                    }
                }
                
                $success = "Konsultasi berhasil diajukan dengan $upload_success file terupload!";
            } else {
                $success = "Konsultasi berhasil diajukan (tanpa file)!";
            }
        } else {
            $error = "Gagal mengajukan konsultasi: " . mysqli_error($conn);
        }
    }
}

// Ambil informasi dosen
$dosen_id = $mahasiswa['dosen_id'];
$query_dosen = "SELECT d.*, u.nama FROM dosen d JOIN users u ON d.user_id = u.id WHERE d.id = '$dosen_id'";
$result_dosen = mysqli_query($conn, $query_dosen);
$dosen = mysqli_fetch_assoc($result_dosen);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajukan Konsultasi - Sistem Konsultasi KP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 20px; font-weight: bold; color: #333; }
        .navbar-brand span { color: #667eea; }
        .back-link { text-decoration: none; color: #667eea; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .user-info .role { background: #667eea; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .logout { background: #ff4757; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        
        .form-card { background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px; }
        .form-card h2 { margin-bottom: 30px; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        
        .dosen-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .dosen-info h3 { color: #333; margin-bottom: 10px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        textarea { resize: vertical; }
        
        /* Styling untuk upload area */
        .upload-area {
            border: 2px dashed #667eea;
            background: #f8f9ff;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            background: #e8ecff;
            border-color: #5a67d8;
        }
        .upload-area i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 10px;
        }
        .upload-area p {
            color: #666;
            margin: 5px 0;
        }
        .upload-area small {
            color: #999;
        }
        
        .file-list {
            margin-top: 15px;
        }
        .file-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .file-item .file-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .file-item .file-icon {
            font-size: 20px;
        }
        .file-item .file-name {
            font-weight: 500;
        }
        .file-item .file-size {
            color: #666;
            font-size: 12px;
        }
        .file-item .remove-file {
            color: #ff4757;
            cursor: pointer;
            font-size: 18px;
        }
        
        .btn { background: #667eea; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #5a67d8; }
        
        .error { background: #fed7d7; color: #9b2c2c; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #c6f6d5; color: #22543d; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        
        .info-text { font-size: 12px; color: #666; margin-top: 5px; }
        
        .preview-box {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">Sistem <span>Konsultasi KP</span></div>
        <div class="user-info">
            <span><?php echo $_SESSION['nama']; ?></span>
            <span class="role">Mahasiswa</span>
            <a href="../logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="dashboard.php" class="back-link">← Kembali ke Dashboard</a>
        </div>
        
        <div class="form-card">
            <h2>Ajukan Konsultasi Baru</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">
                    <?php echo $success; ?>
                    <p style="margin-top: 10px;">
                        <a href="riwayat.php" style="color: #22543d; font-weight: bold;">Lihat Riwayat Konsultasi →</a>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Informasi Dosen -->
            <div class="dosen-info">
                <h3>Dosen Pembimbing</h3>
                <p><strong><?php echo $dosen['nama']; ?></strong> (<?php echo $dosen['nidn']; ?>)</p>
                <p>Keahlian: <?php echo $dosen['keahlian'] ?? '-'; ?></p>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data" id="formKonsultasi">
                <div class="form-group">
                    <label>Tanggal Konsultasi</label>
                    <input type="date" name="tanggal_konsultasi" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Waktu Konsultasi</label>
                    <input type="time" name="waktu_konsultasi" required>
                </div>
                
                <div class="form-group">
                    <label>Topik Konsultasi</label>
                    <input type="text" name="topik" placeholder="Contoh: Konsultasi BAB 1, Revisi Proposal, dll" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi (Opsional)</label>
                    <textarea name="deskripsi" rows="4" placeholder="Jelaskan secara singkat apa yang ingin dikonsultasikan..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Upload File (Bisa pilih banyak file)</label>
                    <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                        <div>📁</div>
                        <p>Klik di sini untuk pilih file</p>
                        <p style="font-size: 12px; color: #999;">Format: PDF, DOC, DOCX, JPG, PNG (Max per file 5MB)</p>
                        <p style="font-size: 11px; color: #999;">Bisa pilih banyak file sekaligus (Ctrl+klik atau Shift+klik)</p>
                    </div>
                    <input type="file" id="fileInput" name="files[]" multiple style="display: none;" 
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    
                    <!-- Daftar file yang dipilih -->
                    <div id="fileList" class="file-list"></div>
                    
                    <!-- Preview area -->
                    <div id="previewArea" class="preview-box" style="display: none;">
                        <h4>Preview File:</h4>
                        <div id="previewContent"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn">Ajukan Konsultasi</button>
            </form>
        </div>
    </div>
    
    <script>
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    const previewArea = document.getElementById('previewArea');
    const previewContent = document.getElementById('previewContent');
    let selectedFiles = [];
    
    fileInput.addEventListener('change', function(e) {
        selectedFiles = Array.from(this.files);
        displayFileList();
        previewFiles();
    });
    
    function displayFileList() {
        if (selectedFiles.length === 0) {
            fileList.innerHTML = '';
            return;
        }
        
        let html = '<h4>File yang dipilih (' + selectedFiles.length + '):</h4>';
        
        selectedFiles.forEach((file, index) => {
            let size = (file.size / 1024).toFixed(2);
            let icon = getFileIcon(file.name);
            
            html += `
                <div class="file-item" data-index="${index}">
                    <div class="file-info">
                        <span class="file-icon">${icon}</span>
                        <span class="file-name">${file.name}</span>
                        <span class="file-size">(${size} KB)</span>
                    </div>
                    <span class="remove-file" onclick="removeFile(${index})">❌</span>
                </div>
            `;
        });
        
        fileList.innerHTML = html;
    }
    
    function getFileIcon(filename) {
        let ext = filename.split('.').pop().toLowerCase();
        const icons = {
            'pdf': '📕',
            'doc': '📘', 'docx': '📘',
            'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️',
            'xls': '📊', 'xlsx': '📊',
            'ppt': '📽️', 'pptx': '📽️'
        };
        return icons[ext] || '📄';
    }
    
    function removeFile(index) {
        selectedFiles.splice(index, 1);
        
        // Update file input
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
        
        displayFileList();
        previewFiles();
    }
    
    function previewFiles() {
        let imageFiles = selectedFiles.filter(file => file.type.startsWith('image/'));
        
        if (imageFiles.length === 0) {
            previewArea.style.display = 'none';
            return;
        }
        
        previewArea.style.display = 'block';
        let html = '';
        
        imageFiles.forEach(file => {
            let reader = new FileReader();
            reader.onload = function(e) {
                html += `<img src="${e.target.result}" style="max-width: 200px; max-height: 200px; margin: 5px; border: 1px solid #ddd; border-radius: 5px;">`;
                previewContent.innerHTML = html;
            };
            reader.readAsDataURL(file);
        });
    }
    </script>
</body>
</html>