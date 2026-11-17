<?php
/**
 * Delete Siswa Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$id_siswa = (int)($_GET['id'] ?? 0);

if (empty($id_siswa)) {
    set_flash('danger', 'ID Siswa tidak valid!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

try {
    // Get student name first
    $stmt_name = $pdo->prepare("SELECT nama_siswa FROM siswa WHERE id_siswa = :id LIMIT 1");
    $stmt_name->execute([':id' => $id_siswa]);
    $nama = $stmt_name->fetchColumn();

    if (!$nama) {
        set_flash('danger', 'Data siswa tidak ditemukan!');
        header('Location: ' . base_url('modules/siswa/index.php'));
        exit;
    }

    // Delete linked user if any
    $stmt_del_user = $pdo->prepare("DELETE FROM users WHERE id_siswa = :id");
    $stmt_del_user->execute([':id' => $id_siswa]);

    // Delete student
    $stmt = $pdo->prepare("DELETE FROM siswa WHERE id_siswa = :id");
    $stmt->execute([':id' => $id_siswa]);

    set_flash('success', 'Data siswa "' . htmlspecialchars($nama) . '" berhasil dihapus dari sistem!');
} catch (PDOException $e) {
    set_flash('danger', 'Tidak dapat menghapus siswa karena memiliki relasi dengan data pembayaran atau kelas: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/siswa/index.php'));
exit;

