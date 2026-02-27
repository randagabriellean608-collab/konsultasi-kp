<?php
// File: api/index.php
// Handler untuk semua request PHP di Vercel

// Dapatkan path yang diminta
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = strtok($request_uri, '?'); // Hapus query string

// Jika request ke file statis (css, js, gambar)
$static_extensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf'];
$extension = pathinfo($request_uri, PATHINFO_EXTENSION);

if (in_array($extension, $static_extensions)) {
    // Biarkan Vercel serve file statis
    return false;
}

// Mapping path ke file PHP
$base_path = __DIR__ . '/..';

// Handle root
if ($request_uri == '/' || $request_uri == '') {
    require $base_path . '/index.php';
    exit;
}

// Handle file PHP langsung
$php_file = $base_path . $request_uri;
if (file_exists($php_file) && pathinfo($php_file, PATHINFO_EXTENSION) == 'php') {
    require $php_file;
    exit;
}

// Handle path dengan folder (contoh: /mahasiswa/dashboard)
$path_parts = explode('/', trim($request_uri, '/'));
if (count($path_parts) >= 2) {
    $folder = $path_parts[0];
    $file = $path_parts[1];
    $possible_file = $base_path . '/' . $folder . '/' . $file . '.php';
    
    if (file_exists($possible_file)) {
        require $possible_file;
        exit;
    }
}

// Fallback ke index
require $base_path . '/index.php';
?>