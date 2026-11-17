<?php
/**
 * Delete Paket Belajar Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$id_paket = (int)($_GET['id'] ?? 0);

if (empty($id_paket)) {
    set_flash('danger', 'ID Paket tidak valid!');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

try {
    $stmt_name = $pdo->prepare("SELECT nama_paket FROM paket_belajar WHERE id_paket = :id LIMIT 1");
    $stmt_name->execute([':id' => $id_paket]);
    $nama = $stmt_name->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM paket_belajar WHERE id_paket = :id");
    $stmt->execute([':id' => $id_paket]);

    set_flash('success', 'Paket Belajar "' . htmlspecialchars($nama) . '" berhasil dihapus!');
} catch (PDOException $e) {
    set_flash('danger', 'Paket tidak dapat dihapus karena masih digunakan oleh data siswa atau kelas: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/paket/index.php'));
exit;

