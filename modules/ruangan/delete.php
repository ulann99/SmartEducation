
<?php
/**
 * HAPUS DATA RUANGAN
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

$id = filter_var($_POST['id_ruangan'] ?? null, FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('danger', 'ID ruangan tidak valid.');
    header('Location: ' . base_url('modules/ruangan/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id_ruangan, nama_ruangan
        FROM ruangan
        WHERE id_ruangan = ?
    ");
    $stmt->execute([$id]);
    $ruangan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ruangan) {
        set_flash('danger', 'Data ruangan tidak ditemukan.');
    } else {
        // Periksa apakah ruangan masih digunakan oleh kelas
        $cek = $pdo->prepare("
            SELECT COUNT(*)
            FROM kelas
            WHERE id_ruangan = ?
        ");
        $cek->execute([$id]);

        $jumlah_kelas = (int)$cek->fetchColumn();

        if ($jumlah_kelas > 0) {
            set_flash(
                'danger',
                'Ruangan tidak dapat dihapus karena masih digunakan oleh ' .
                $jumlah_kelas .
                ' kelas. Ubah status ruangan menjadi Non-Aktif jika tidak digunakan lagi.'
            );
        } else {
            $hapus = $pdo->prepare("
                DELETE FROM ruangan
                WHERE id_ruangan = ?
            ");
            $hapus->execute([$id]);

            set_flash(
                'success',
                'Ruangan ' . $ruangan['nama_ruangan'] . ' berhasil dihapus.'
            );
        }
    }

} catch (PDOException $e) {
    set_flash('danger', 'Ruangan gagal dihapus karena masih memiliki data terkait.');
}

header('Location: ' . base_url('modules/ruangan/index.php'));
exit;
