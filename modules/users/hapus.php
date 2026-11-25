<?php
/**
 * HAPUS PENGGUNA
 * SMART EDUCATION
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';

require_login();

// Hanya Super Admin yang boleh menghapus pengguna
if (($_SESSION['role'] ?? '') !== 'superadmin') {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

// Pastikan metode request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/users/index.php'));
    exit;
}

// Validasi CSRF
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    set_flash('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    header('Location: ' . base_url('modules/users/index.php'));
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('danger', 'ID pengguna tidak valid.');
    header('Location: ' . base_url('modules/users/index.php'));
    exit;
}

// Mencegah Super Admin menghapus akunnya sendiri
if ($id == ($_SESSION['user_id'] ?? 0)) {
    set_flash('danger', 'Anda tidak dapat menghapus akun sendiri.');
    header('Location: ' . base_url('modules/users/index.php'));
    exit;
}

try {
    // Pastikan pengguna tersedia
    $stmt = $pdo->prepare(
        "SELECT id, nama_lengkap, role FROM users WHERE id = ?"
    );
    $stmt->execute([$id]);
    $pengguna = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pengguna) {
        set_flash('danger', 'Data pengguna tidak ditemukan.');
    } elseif ($pengguna['role'] === 'superadmin') {
        set_flash('danger', 'Akun Super Admin tidak dapat dihapus melalui fitur ini.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        set_flash('success', 'Pengguna ' . $pengguna['nama_lengkap'] . ' berhasil dihapus.');
    }
} catch (PDOException $e) {
    set_flash('danger', 'Pengguna gagal dihapus karena masih memiliki data terkait.');
}

header('Location: ' . base_url('modules/users/index.php'));
exit;