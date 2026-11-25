
<?php
/**
 * EDIT PAKET BELAJAR
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

if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    set_flash('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

$id = filter_var($_POST['id_paket'] ?? null, FILTER_VALIDATE_INT);
$nama_paket = trim($_POST['nama_paket'] ?? '');
$jenjang = trim($_POST['jenjang'] ?? '');
$harga = trim($_POST['harga'] ?? '');
$durasi = filter_var($_POST['durasi_bulan'] ?? null, FILTER_VALIDATE_INT);
$pertemuan = filter_var($_POST['pertemuan_per_minggu'] ?? null, FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? '';
$deskripsi = trim($_POST['deskripsi'] ?? '');

if (
    !$id ||
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
    $cek = $pdo->prepare("SELECT id_paket FROM paket_belajar WHERE id_paket = ?");
    $cek->execute([$id]);

    if (!$cek->fetch()) {
        set_flash('danger', 'Paket belajar tidak ditemukan.');
        header('Location: ' . base_url('modules/paket/index.php'));
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE paket_belajar
        SET nama_paket = :nama,
            jenjang = :jenjang,
            harga = :harga,
            durasi_bulan = :durasi,
            pertemuan_per_minggu = :pertemuan,
            deskripsi = :deskripsi,
            status = :status
        WHERE id_paket = :id
    ");

    $stmt->execute([
        ':nama' => $nama_paket,
        ':jenjang' => $jenjang,
        ':harga' => $harga,
        ':durasi' => $durasi,
        ':pertemuan' => $pertemuan,
        ':deskripsi' => $deskripsi !== '' ? $deskripsi : null,
        ':status' => $status,
        ':id' => $id
    ]);

    set_flash('success', 'Paket belajar berhasil diperbarui.');
} catch (PDOException $e) {
    set_flash('danger', 'Paket belajar gagal diperbarui.');
}

header('Location: ' . base_url('modules/paket/index.php'));
exit;
