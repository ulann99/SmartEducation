
<?php
/**
 * Delete Pembayaran Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$id_pembayaran = (int)($_GET['id'] ?? 0);
$redirect = base_url('modules/pembayaran/index.php?tab=kelola');

if ($id_pembayaran <= 0) {
    set_flash('danger', 'ID pembayaran tidak valid.');
    header('Location: ' . $redirect);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id_pembayaran, bukti_pembayaran
        FROM pembayaran
        WHERE id_pembayaran = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id_pembayaran]);
    $pembayaran = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pembayaran) {
        set_flash('danger', 'Data pembayaran tidak ditemukan.');
        header('Location: ' . $redirect);
        exit;
    }

    $hapus = $pdo->prepare("
        DELETE FROM pembayaran
        WHERE id_pembayaran = :id
    ");
    $hapus->execute([':id' => $id_pembayaran]);

    // Hapus file bukti jika memang tersimpan
    if (!empty($pembayaran['bukti_pembayaran'])) {
        $file_path = BASEPATH . '/assets/images/uploads/' .
            basename($pembayaran['bukti_pembayaran']);

        if (is_file($file_path)) {
            @unlink($file_path);
        }
    }

    set_flash('success', 'Data pembayaran berhasil dihapus.');

} catch (PDOException $e) {
    set_flash('danger', 'Gagal menghapus pembayaran: ' . $e->getMessage());
}

header('Location: ' . $redirect);
exit;
