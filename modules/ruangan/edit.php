<?php
/**
 * Edit Ruangan Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/ruangan/index.php'));
    exit;
}

$id_ruangan = (int)($_POST['id_ruangan'] ?? 0);
$kode_ruangan = trim($_POST['kode_ruangan'] ?? '');
$nama_ruangan = trim($_POST['nama_ruangan'] ?? '');
$kapasitas = (int)($_POST['kapasitas'] ?? 15);
$status_ruangan = trim($_POST['status_ruangan'] ?? 'Tersedia');
$fasilitas = trim($_POST['fasilitas'] ?? '');

if (empty($id_ruangan) || empty($kode_ruangan) || empty($nama_ruangan) || $kapasitas <= 0) {
    set_flash('danger', 'Data ruangan tidak lengkap!');
    header('Location: ' . base_url('modules/ruangan/index.php'));
    exit;
}

try {
    $stmt_check = $pdo->prepare("SELECT id_ruangan FROM ruangan WHERE kode_ruangan = :kode AND id_ruangan != :id LIMIT 1");
    $stmt_check->execute([':kode' => $kode_ruangan, ':id' => $id_ruangan]);
    if ($stmt_check->fetch()) {
        set_flash('danger', 'Kode Ruangan "' . htmlspecialchars($kode_ruangan) . '" sudah digunakan ruangan lain!');
        header('Location: ' . base_url('modules/ruangan/index.php'));
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE ruangan SET
            kode_ruangan = :kode_ruangan,
            nama_ruangan = :nama_ruangan,
            kapasitas = :kapasitas,
            fasilitas = :fasilitas,
            status_ruangan = :status_ruangan
        WHERE id_ruangan = :id_ruangan
    ");
    $stmt->execute([
        ':kode_ruangan' => $kode_ruangan,
        ':nama_ruangan' => $nama_ruangan,
        ':kapasitas' => $kapasitas,
        ':fasilitas' => $fasilitas,
        ':status_ruangan' => $status_ruangan,
        ':id_ruangan' => $id_ruangan
    ]);

    set_flash('success', 'Ruangan "' . htmlspecialchars($nama_ruangan) . '" berhasil diperbarui!');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal memperbarui ruangan: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/ruangan/index.php'));
exit;

