<?php
/**
 * Delete Pembayaran Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$id_pembayaran = (int)($_GET['id'] ?? 0);

if (empty($id_pembayaran)) {
    set_flash('danger', 'ID Pembayaran tidak valid!');
    header('Location: ' . base_url('modules/pembayaran/index.php'));
    exit;
}

try {
    $stmt_code = $pdo->prepare("SELECT kode_transaksi, bukti_pembayaran FROM pembayaran WHERE id_pembayaran = :id LIMIT 1");
    $stmt_code->execute([':id' => $id_pembayaran]);
    $pay = $stmt_code->fetch();

    if ($pay) {
        if (!empty($pay['bukti_pembayaran'])) {
            $file_path = BASEPATH . '/assets/images/uploads/' . $pay['bukti_pembayaran'];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM pembayaran WHERE id_pembayaran = :id");
        $stmt->execute([':id' => $id_pembayaran]);

        set_flash('success', 'Transaksi ' . htmlspecialchars($pay['kode_transaksi']) . ' berhasil dihapus!');
    } else {
        set_flash('danger', 'Data pembayaran tidak ditemukan.');
    }
} catch (PDOException $e) {
    set_flash('danger', 'Gagal menghapus pembayaran: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/pembayaran/index.php'));
exit;

