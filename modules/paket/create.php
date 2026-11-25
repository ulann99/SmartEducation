
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

if (!in_array($_SESSION['role'] ?? '', ['superadmin', 'admin'], true)) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

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

$nama_paket = trim($_POST['nama_paket'] ?? '');
$jenjang = trim($_POST['jenjang'] ?? '');
$harga = trim($_POST['harga'] ?? '');
$durasi = filter_var($_POST['durasi_bulan'] ?? null, FILTER_VALIDATE_INT);
$pertemuan = filter_var($_POST['pertemuan_per_minggu'] ?? null, FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? 'Aktif';
$deskripsi = trim($_POST['deskripsi'] ?? '');

if (
    $nama_paket === '' ||
    $jenjang === '' ||
    $harga === '' ||
    !is_numeric($harga) ||
    (float)$harga <= 0 ||
    !$durasi || $durasi < 1 ||
    !$pertemuan || $pertemuan < 1 || $pertemuan > 7 ||
    !in_array($status, ['Aktif', 'Non-Aktif'], true)
) {
    set_flash('danger', 'Data paket tidak valid. Periksa kembali isian Anda.');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

if (mb_strlen($nama_paket) > 100 || mb_strlen($jenjang) > 50) {
    set_flash('danger', 'Nama paket maksimal 100 karakter dan jenjang maksimal 50 karakter.');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO paket_belajar
            (nama_paket, jenjang, harga, durasi_bulan,
             pertemuan_per_minggu, deskripsi, status)
        VALUES
            (:nama, :jenjang, :harga, :durasi,
             :pertemuan, :deskripsi, :status)
    ");

    $stmt->execute([
        ':nama' => $nama_paket,
        ':jenjang' => $jenjang,
        ':harga' => $harga,
        ':durasi' => $durasi,
        ':pertemuan' => $pertemuan,
        ':deskripsi' => $deskripsi !== '' ? $deskripsi : null,
        ':status' => $status
    ]);

    set_flash('success', 'Paket belajar berhasil ditambahkan.');
} catch (PDOException $e) {
    set_flash('danger', 'Paket belajar gagal ditambahkan. Silakan periksa kembali data Anda.');
}

header('Location: ' . base_url('modules/paket/index.php'));
exit;
