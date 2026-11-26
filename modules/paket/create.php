
<?php
/**
 * TAMBAH PAKET BELAJAR
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';
require_login();

// Hak akses
if (!in_array($_SESSION['role'] ?? '', ['superadmin', 'admin'], true)) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

// Validasi CSRF
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    set_flash('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

// Ambil data
$nama_paket = trim($_POST['nama_paket'] ?? '');
$jenjang = trim($_POST['jenjang'] ?? '');
$harga = trim($_POST['harga'] ?? '');
$status = strtolower(trim($_POST['status'] ?? 'aktif'));
$deskripsi = trim($_POST['deskripsi'] ?? '');

// Validasi jenjang sesuai enum database
$jenjang_valid = ['TK', 'SD', 'SMP', 'SMA', 'Semua'];

if (
    $nama_paket === '' ||
    !in_array($jenjang, $jenjang_valid, true) ||
    $harga === '' ||
    !is_numeric($harga) ||
    (float) $harga <= 0 ||
    !in_array($status, ['aktif', 'nonaktif'], true)
) {
    set_flash('danger', 'Data paket tidak valid. Periksa kembali isian Anda.');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

// Validasi panjang teks
if (mb_strlen($nama_paket) > 100) {
    set_flash('danger', 'Nama paket maksimal 100 karakter.');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO paket_belajar
            (nama_paket, jenjang, harga, deskripsi, status)
        VALUES
            (:nama, :jenjang, :harga, :deskripsi, :status)
    ");

    $stmt->execute([
        ':nama' => $nama_paket,
        ':jenjang' => $jenjang,
        ':harga' => $harga,
        ':deskripsi' => $deskripsi !== '' ? $deskripsi : null,
        ':status' => $status
    ]);

    set_flash('success', 'Paket belajar berhasil ditambahkan.');

} catch (PDOException $e) {
    set_flash('danger', 'Paket belajar gagal ditambahkan. Silakan periksa kembali data Anda.');
}

header('Location: ' . base_url('modules/paket/index.php'));
exit;
