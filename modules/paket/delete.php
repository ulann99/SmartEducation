
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

// Validasi ID
$id = filter_var($_POST['id_paket'] ?? null, FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('danger', 'ID paket tidak valid.');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

try {
    // Periksa paket
    $stmt = $pdo->prepare("
        SELECT id_paket, nama_paket
        FROM paket_belajar
        WHERE id_paket = ?
    ");

    $stmt->execute([$id]);
    $paket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paket) {
        set_flash('danger', 'Paket belajar tidak ditemukan.');
        header('Location: ' . base_url('modules/paket/index.php'));
        exit;
    }

    // Periksa apakah paket masih digunakan pendaftaran
    $cek = $pdo->prepare("
        SELECT COUNT(*)
        FROM pendaftaran
        WHERE id_paket = ?
    ");

    $cek->execute([$id]);
    $jumlahPendaftaran = (int) $cek->fetchColumn();

    if ($jumlahPendaftaran > 0) {

        set_flash(
            'danger',
            'Paket tidak dapat dihapus karena sudah digunakan oleh ' .
            $jumlahPendaftaran .
            ' data pendaftaran. Ubah status paket menjadi Nonaktif jika tidak ingin digunakan lagi.'
        );

    } else {

        // Hapus jika belum memiliki relasi pendaftaran
        $hapus = $pdo->prepare("
            DELETE FROM paket_belajar
            WHERE id_paket = ?
        ");

        $hapus->execute([$id]);

        set_flash(
            'success',
            'Paket ' . $paket['nama_paket'] . ' berhasil dihapus.'
        );
    }

} catch (PDOException $e) {

    set_flash(
        'danger',
        'Paket gagal dihapus karena masih memiliki data terkait.'
    );
}

header('Location: ' . base_url('modules/paket/index.php'));
exit;
