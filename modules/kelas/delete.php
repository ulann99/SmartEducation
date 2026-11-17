<?php
/**
 * Delete Kelas Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$id_kelas = (int)($_GET['id'] ?? 0);

if (empty($id_kelas)) {
    set_flash('danger', 'ID Kelas tidak valid!');
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

try {
    $stmt_name = $pdo->prepare("SELECT nama_kelas FROM kelas WHERE id_kelas = :id LIMIT 1");
    $stmt_name->execute([':id' => $id_kelas]);
    $nama = $stmt_name->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM kelas WHERE id_kelas = :id");
    $stmt->execute([':id' => $id_kelas]);

    set_flash('success', 'Kelas "' . htmlspecialchars($nama) . '" berhasil dihapus!');
} catch (PDOException $e) {
    set_flash('danger', 'Kelas tidak dapat dihapus: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/kelas/index.php'));
exit;

