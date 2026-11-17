<?php
/**
 * Verify Pembayaran Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/pembayaran/index.php'));
    exit;
}

$id_pembayaran = (int)($_POST['id_pembayaran'] ?? 0);
$status_pembayaran = trim($_POST['status_pembayaran'] ?? 'Lunas');
$keterangan = trim($_POST['keterangan'] ?? '');

if (empty($id_pembayaran)) {
    set_flash('danger', 'ID Pembayaran tidak valid!');
    header('Location: ' . base_url('modules/pembayaran/index.php'));
    exit;
}

try {
    $cur_user = current_user();
    $diverifikasi_oleh = $cur_user['id'];
    $tanggal_verifikasi = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        UPDATE pembayaran SET
            status_pembayaran = :status,
            keterangan = :keterangan,
            diverifikasi_oleh = :verifikator,
            tanggal_verifikasi = :tgl_verif
        WHERE id_pembayaran = :id
    ");
    $stmt->execute([
        ':status' => $status_pembayaran,
        ':keterangan' => $keterangan,
        ':verifikator' => $diverifikasi_oleh,
        ':tgl_verif' => $tanggal_verifikasi,
        ':id' => $id_pembayaran
    ]);

    set_flash('success', 'Status pembayaran berhasil diperbarui menjadi: ' . $status_pembayaran);
} catch (PDOException $e) {
    set_flash('danger', 'Gagal memverifikasi pembayaran: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/pembayaran/index.php'));
exit;

