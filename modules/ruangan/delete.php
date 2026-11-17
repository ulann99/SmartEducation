<?php
/**
 * Delete Ruangan Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$id_ruangan = (int)($_GET['id'] ?? 0);

if (empty($id_ruangan)) {
    set_flash('danger', 'ID Ruangan tidak valid!');
    header('Location: ' . base_url('modules/ruangan/index.php'));
    exit;
}

try {
    $stmt_name = $pdo->prepare("SELECT nama_ruangan FROM ruangan WHERE id_ruangan = :id LIMIT 1");
    $stmt_name->execute([':id' => $id_ruangan]);
    $nama = $stmt_name->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM ruangan WHERE id_ruangan = :id");
    $stmt->execute([':id' => $id_ruangan]);

    set_flash('success', 'Ruangan "' . htmlspecialchars($nama) . '" berhasil dihapus!');
} catch (PDOException $e) {
    set_flash('danger', 'Ruangan tidak dapat dihapus karena masih digunakan dalam kelas atau jadwal: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/ruangan/index.php'));
exit;

