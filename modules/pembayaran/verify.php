
<?php
/**
 * Verify Pembayaran
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$redirect = base_url('modules/pembayaran/index.php?tab=kelola');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$id_pembayaran = (int)($_POST['id_pembayaran'] ?? 0);
$status_pembayaran = trim($_POST['status_pembayaran'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

if (
    $id_pembayaran <= 0 ||
    !in_array($status_pembayaran, ['Berhasil', 'Ditolak'], true)
) {
    set_flash('danger', 'Data pembayaran atau status tidak valid.');
    header('Location: ' . $redirect);
    exit;
}

try {
    $cek = $pdo->prepare("
        SELECT id_pembayaran, status_pembayaran
        FROM pembayaran
        WHERE id_pembayaran = :id
        LIMIT 1
    ");
    $cek->execute([':id' => $id_pembayaran]);
    $pembayaran = $cek->fetch(PDO::FETCH_ASSOC);

    if (!$pembayaran) {
        set_flash('danger', 'Data pembayaran tidak ditemukan.');
        header('Location: ' . $redirect);
        exit;
    }

    if ($pembayaran['status_pembayaran'] !== 'Menunggu') {
        set_flash('warning', 'Pembayaran ini sudah diproses sebelumnya.');
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE pembayaran
        SET
            status_pembayaran = :status,
            keterangan = :keterangan
        WHERE id_pembayaran = :id
    ");

    $stmt->execute([
        ':status' => $status_pembayaran,
        ':keterangan' => $keterangan !== ''
            ? $keterangan
            : null,
        ':id' => $id_pembayaran
    ]);

    set_flash(
        'success',
        'Status pembayaran berhasil diubah menjadi ' . $status_pembayaran . '.'
    );

} catch (PDOException $e) {
    set_flash('danger', 'Gagal memverifikasi pembayaran: ' . $e->getMessage());
}

header('Location: ' . $redirect);
exit;
