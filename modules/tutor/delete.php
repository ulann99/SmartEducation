
<?php
/**
 * HAPUS DATA TUTOR
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

// Validasi CSRF
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    set_flash('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

$id = filter_var($_POST['id_tutor'] ?? null, FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('danger', 'ID tutor tidak valid.');
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

try {
    // Periksa data tutor
    $stmt = $pdo->prepare("
        SELECT id_tutor, nama_tutor
        FROM tutor
        WHERE id_tutor = ?
    ");
    $stmt->execute([$id]);
    $tutor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tutor) {
        set_flash('danger', 'Data tutor tidak ditemukan.');
    } else {
        // Periksa apakah tutor masih digunakan oleh kelas
        $cekKelas = $pdo->prepare("
            SELECT COUNT(*)
            FROM kelas
            WHERE id_tutor = ?
        ");
        $cekKelas->execute([$id]);

        $jumlahKelas = (int)$cekKelas->fetchColumn();

        if ($jumlahKelas > 0) {
            set_flash(
                'danger',
                'Tutor tidak dapat dihapus karena masih mengampu ' .
                $jumlahKelas .
                ' kelas. Ubah status tutor menjadi Non-Aktif jika tidak lagi mengajar.'
            );
        } else {
            $hapus = $pdo->prepare("
                DELETE FROM tutor
                WHERE id_tutor = ?
            ");
            $hapus->execute([$id]);

            set_flash(
                'success',
                'Data tutor ' . $tutor['nama_tutor'] . ' berhasil dihapus.'
            );
        }
    }

} catch (PDOException $e) {
    set_flash('danger', 'Data tutor gagal dihapus karena masih memiliki data terkait.');
}

header('Location: ' . base_url('modules/tutor/index.php'));
exit;
