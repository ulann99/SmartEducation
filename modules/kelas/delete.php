
<?php
/**
 * DELETE DATA KELAS
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

// Validasi CSRF
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_kelas']) ||
    !hash_equals($_SESSION['csrf_kelas'], $_POST['csrf_token'])
) {
    set_flash('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

$id_kelas = filter_var(
    $_POST['id_kelas'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id_kelas) {
    set_flash('danger', 'ID kelas tidak valid.');
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

try {

    // Periksa keberadaan kelas
    $cek = $pdo->prepare("
        SELECT nama_kelas
        FROM kelas
        WHERE id_kelas = ?
    ");

    $cek->execute([$id_kelas]);

    $kelas = $cek->fetch(PDO::FETCH_ASSOC);

    if (!$kelas) {

        set_flash('danger', 'Data kelas tidak ditemukan.');

    } else {

        // Hapus kelas
        $hapus = $pdo->prepare("
            DELETE FROM kelas
            WHERE id_kelas = ?
        ");

        $hapus->execute([$id_kelas]);

        set_flash(
            'success',
            'Kelas ' . $kelas['nama_kelas'] . ' berhasil dihapus.'
        );
    }

} catch (PDOException $e) {

    set_flash(
        'danger',
        'Kelas tidak dapat dihapus karena masih memiliki data anggota atau data terkait.'
    );

}

header('Location: ' . base_url('modules/kelas/index.php'));
exit;
