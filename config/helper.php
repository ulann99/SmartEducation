<?php
/**
 * Helper Functions
 * SMART EDUCATION PAREPARE
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

/**
 * Base URL generator
 */
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Check script directory
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Normalize path to root project if inside a subfolder
    $base = rtrim($protocol . $host . $script_dir, '/');
    
    // Adjust if script_dir is inside /modules/...
    if (preg_match('#/modules(/.*)?$#', $base)) {
        $base = preg_replace('#/modules(/.*)?$#', '', $base);
    }
    
    $clean_path = ltrim($path, '/');
    return empty($clean_path) ? $base : $base . '/' . $clean_path;
}

/**
 * Format currency into Rupiah (Rp)
 */
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format date to Indonesian format
 */
function format_tanggal($tanggal, $tampilkan_hari = false) {
    if (empty($tanggal) || $tanggal === '0000-00-00' || $tanggal === '0000-00-00 00:00:00') {
        return '-';
    }

    $hari_arr = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];

    $bulan_arr = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $timestamp = strtotime($tanggal);
    $hari = $hari_arr[date('l', $timestamp)] ?? '';
    $tgl = date('d', $timestamp);
    $bln = $bulan_arr[(int)date('m', $timestamp)] ?? '';
    $thn = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);

    $hasil = "$tgl $bln $thn";
    if ($tampilkan_hari) {
        $hasil = "$hari, $hasil";
    }
    if (strlen($tanggal) > 10 && $jam !== '00:00') {
        $hasil .= " pukul $jam WITA";
    }

    return $hasil;
}

/**
 * Flash message helper
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Authentication helpers
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap'],
        'role' => $_SESSION['role'],
        'email' => $_SESSION['email'] ?? '',
        'id_siswa' => $_SESSION['id_siswa'] ?? null,
        'foto' => $_SESSION['foto'] ?? 'default.png'
    ];
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses sistem.');
        header('Location: ' . base_url('modules/auth/login.php'));
        exit;
    }
}

function require_roles(array $allowed_roles) {
    require_login();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $allowed_roles)) {
        set_flash('danger', 'Akses ditolak. Anda tidak memiliki izin untuk membuka halaman tersebut.');
        // Redirect to appropriate dashboard
        if ($role === 'siswa') {
            header('Location: ' . base_url('modules/dashboard/siswa.php'));
        } elseif ($role === 'admin') {
            header('Location: ' . base_url('modules/dashboard/admin.php'));
        } else {
            header('Location: ' . base_url('modules/dashboard/superadmin.php'));
        }
        exit;
    }
}

/**
 * Sanitize string input
 */
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * System Settings
 */
function get_setting($key = null) {
    global $pdo;
    static $settings = null;
    
    if ($settings === null) {
        try {
            $stmt = $pdo->query("SELECT * FROM pengaturan LIMIT 1");
            $settings = $stmt->fetch() ?: [];
        } catch (Exception $e) {
            $settings = [];
        }
    }

    if ($key !== null) {
        return $settings[$key] ?? null;
    }
    return $settings;
}

/**
 * File Upload Helper
 */
function upload_image($file, $subfolder = 'uploads', $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'], $max_size = 3145728) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Parameter berkas tidak valid.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Gagal mengunggah berkas. Kode kesalahan: ' . $file['error']];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Ukuran berkas terlalu besar! Maksimal 3MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed_types)) {
        return ['success' => false, 'message' => 'Format berkas tidak diizinkan! Hanya JPG, PNG, atau WEBP.'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('file_', true) . '.' . strtolower($ext);
    
    $target_dir = __DIR__ . '/../assets/images/' . trim($subfolder, '/') . '/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $target_path = $target_dir . $new_filename;
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => false, 'message' => 'Gagal memindahkan berkas yang diunggah.'];
    }

    return [
        'success' => true,
        'filename' => $new_filename,
        'relative_path' => 'assets/images/' . trim($subfolder, '/') . '/' . $new_filename
    ];
}

