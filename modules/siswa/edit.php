<?php
/**
 * Edit Siswa Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

$id_siswa = (int)($_POST['id_siswa'] ?? 0);
$nis = trim($_POST['nis'] ?? '');
$nama_siswa = trim($_POST['nama_siswa'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'Laki-laki');
$id_paket = !empty($_POST['id_paket']) ? (int)$_POST['id_paket'] : null;
$no_telepon = trim($_POST['no_telepon'] ?? '');
$email = trim($_POST['email'] ?? '');
$tanggal_daftar = trim($_POST['tanggal_daftar'] ?? date('Y-m-d'));
$status_siswa = trim($_POST['status_siswa'] ?? 'Aktif');
$alamat = trim($_POST['alamat'] ?? '');

if (empty($id_siswa) || empty($nis) || empty($nama_siswa)) {
    set_flash('danger', 'ID Siswa, NIS, dan Nama Siswa wajib diisi!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

try {
    // Check if NIS is used by another student
    $stmt_check = $pdo->prepare("SELECT id_siswa FROM siswa WHERE nis = :nis AND id_siswa != :id_siswa LIMIT 1");
    $stmt_check->execute([':nis' => $nis, ':id_siswa' => $id_siswa]);
    if ($stmt_check->fetch()) {
        set_flash('danger', 'NIS "' . htmlspecialchars($nis) . '" sudah digunakan oleh siswa lain!');
        header('Location: ' . base_url('modules/siswa/index.php'));
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE siswa SET
            nis = :nis,
            nama_siswa = :nama_siswa,
            jenis_kelamin = :jenis_kelamin,
            alamat = :alamat,
            no_telepon = :no_telepon,
            email = :email,
            id_paket = :id_paket,
            status_siswa = :status_siswa,
            tanggal_daftar = :tanggal_daftar
        WHERE id_siswa = :id_siswa
    ");
    $stmt->execute([
        ':nis' => $nis,
        ':nama_siswa' => $nama_siswa,
        ':jenis_kelamin' => $jenis_kelamin,
        ':alamat' => $alamat,
        ':no_telepon' => $no_telepon,
        ':email' => $email,
        ':id_paket' => $id_paket,
        ':status_siswa' => $status_siswa,
        ':tanggal_daftar' => $tanggal_daftar,
        ':id_siswa' => $id_siswa
    ]);

    // Also update linked user profile if exists
    $stmt_user = $pdo->prepare("
        UPDATE users SET
            nama_lengkap = :nama_lengkap,
            email = :email
        WHERE id_siswa = :id_siswa
    ");
    $stmt_user->execute([
        ':nama_lengkap' => $nama_siswa,
        ':email' => !empty($email) ? $email : null,
        ':id_siswa' => $id_siswa
    ]);

    set_flash('success', 'Data siswa "' . htmlspecialchars($nama_siswa) . '" berhasil diperbarui!');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal memperbarui data siswa: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/siswa/index.php'));
exit;

