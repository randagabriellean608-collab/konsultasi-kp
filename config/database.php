<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_konsultasi';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');
?>