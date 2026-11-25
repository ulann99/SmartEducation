
<?php
/**
 * HAPUS PAKET BELAJAR
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

if (!$id) {
    set_flash('danger', 'ID paket tidak valid.');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id_paket, nama_paket
        FROM paket_belajar
        WHERE id_paket = ?
    ");
    $stmt->execute([$id]);
    $paket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paket) {
        set_flash('danger', 'Paket belajar tidak ditemukan.');
    } else {
        // Jangan hapus paket yang masih digunakan oleh data siswa
        $cekSiswa = $pdo->prepare("
            SELECT COUNT(*)
            FROM siswa
            WHERE id_paket = ?
        ");
        $cekSiswa->execute([$id]);
        $jumlahSiswa = (int)$cekSiswa->fetchColumn();

        if ($jumlahSiswa > 0) {
            set_flash(
                'danger',
                'Paket tidak dapat dihapus karena masih digunakan oleh ' .
                $jumlahSiswa . ' siswa. Ubah statusnya menjadi Non-Aktif.'
            );
        } else {
            $hapus = $pdo->prepare("
                DELETE FROM paket_belajar
                WHERE id_paket = ?
            ");
            $hapus->execute([$id]);

            set_flash('success', 'Paket ' . $paket['nama_paket'] . ' berhasil dihapus.');
        }
    }
} catch (PDOException $e) {
    set_flash('danger', 'Paket belajar gagal dihapus karena masih memiliki data terkait.');
}

header('Location: ' . base_url('modules/paket/index.php'));
exit;
