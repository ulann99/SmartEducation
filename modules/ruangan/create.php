
<?php
/**
 * TAMBAH DATA RUANGAN
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/ruangan/index.php'));
    exit;
}

if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    set_flash('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    header('Location: ' . base_url('modules/ruangan/index.php'));
    exit;
}

$nama_ruangan = trim($_POST['nama_ruangan'] ?? '');
$kapasitas = filter_var($_POST['kapasitas'] ?? null, FILTER_VALIDATE_INT);
$lokasi = trim($_POST['lokasi'] ?? '');
$fasilitas = trim($_POST['fasilitas'] ?? '');
$status = $_POST['status_ruangan'] ?? 'Aktif';

if (
    $nama_ruangan === '' ||
    $lokasi === '' ||
    $kapasitas === false ||
    $kapasitas === null ||
    $kapasitas < 1 ||
    !in_array($status, ['Aktif', 'Non-Aktif'], true)
) {
    set_flash('danger', 'Data ruangan tidak valid. Periksa kembali isian Anda.');
    header('Location: ' . base_url('modules/ruangan/index.php'));
    exit;
}

if (mb_strlen($nama_ruangan) > 100 || mb_strlen($lokasi) > 150) {
    set_flash('danger', 'Nama ruangan maksimal 100 karakter dan lokasi maksimal 150 karakter.');
    header('Location: ' . base_url('modules/ruangan/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO ruangan
            (nama_ruangan, kapasitas, lokasi, fasilitas, status_ruangan)
        VALUES
            (:nama, :kapasitas, :lokasi, :fasilitas, :status)
    ");

    $stmt->execute([
        ':nama' => $nama_ruangan,
        ':kapasitas' => $kapasitas,
        ':lokasi' => $lokasi,
        ':fasilitas' => $fasilitas !== '' ? $fasilitas : null,
        ':status' => $status
    ]);

    set_flash('success', 'Ruangan berhasil ditambahkan.');

} catch (PDOException $e) {
    set_flash('danger', 'Ruangan gagal ditambahkan. Silakan periksa kembali data Anda.');
}

header('Location: ' . base_url('modules/ruangan/index.php'));
exit;
