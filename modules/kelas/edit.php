
<?php
/**
 * EDIT KELAS
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$redirect = base_url('modules/kelas/index.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect");
    exit;
}

if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_kelas']) ||
    !hash_equals($_SESSION['csrf_kelas'], $_POST['csrf_token'])
) {
    set_flash('danger', 'Permintaan tidak valid.');
    header("Location: $redirect");
    exit;
}

$id = filter_var($_POST['id_kelas'] ?? null, FILTER_VALIDATE_INT);
$nama = trim($_POST['nama_kelas'] ?? '');
$id_tutor = !empty($_POST['id_tutor']) ? (int)$_POST['id_tutor'] : null;
$id_ruangan = !empty($_POST['id_ruangan']) ? (int)$_POST['id_ruangan'] : null;

if (!$id || $nama === '' || mb_strlen($nama) > 100) {
    set_flash('danger', 'Data kelas tidak valid.');
    header("Location: $redirect");
    exit;
}

try {
    $cek = $pdo->prepare("SELECT id_kelas FROM kelas WHERE id_kelas = ?");
    $cek->execute([$id]);

    if (!$cek->fetch()) {
        throw new Exception('Kelas tidak ditemukan.');
    }

    if ($id_tutor !== null) {
        $cek = $pdo->prepare("
            SELECT id_tutor FROM tutor
            WHERE id_tutor = ? AND status_tutor = 'Aktif'
        ");
        $cek->execute([$id_tutor]);

        if (!$cek->fetch()) {
            throw new Exception('Tutor tidak tersedia atau tidak aktif.');
        }
    }

    if ($id_ruangan !== null) {
        $cek = $pdo->prepare("
            SELECT id_ruangan FROM ruangan
            WHERE id_ruangan = ? AND status_ruangan = 'Aktif'
        ");
        $cek->execute([$id_ruangan]);

        if (!$cek->fetch()) {
            throw new Exception('Ruangan tidak tersedia atau tidak aktif.');
        }
    }

    $stmt = $pdo->prepare("
        UPDATE kelas
        SET nama_kelas = :nama,
            id_tutor = :tutor,
            id_ruangan = :ruangan
        WHERE id_kelas = :id
    ");

    $stmt->execute([
        ':nama' => $nama,
        ':tutor' => $id_tutor,
        ':ruangan' => $id_ruangan,
        ':id' => $id
    ]);

    set_flash('success', 'Data kelas berhasil diperbarui.');

} catch (Exception $e) {
    set_flash('danger', 'Gagal memperbarui kelas: ' . $e->getMessage());
}

header("Location: $redirect");
exit;
