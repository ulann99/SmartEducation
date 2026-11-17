<?php
/**
 * Delete Tutor Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$id_tutor = (int)($_GET['id'] ?? 0);

if (empty($id_tutor)) {
    set_flash('danger', 'ID Tutor tidak valid!');
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

try {
    $stmt_name = $pdo->prepare("SELECT nama_tutor FROM tutor WHERE id_tutor = :id LIMIT 1");
    $stmt_name->execute([':id' => $id_tutor]);
    $nama = $stmt_name->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM tutor WHERE id_tutor = :id");
    $stmt->execute([':id' => $id_tutor]);

    set_flash('success', 'Tutor "' . htmlspecialchars($nama) . '" berhasil dihapus dari sistem!');
} catch (PDOException $e) {
    set_flash('danger', 'Tutor tidak dapat dihapus karena masih mengampu kelas atau terjadwal: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/tutor/index.php'));
exit;

