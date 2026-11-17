<?php
/**
 * Create Tutor Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

$nip = trim($_POST['nip'] ?? '');
$nama_tutor = trim($_POST['nama_tutor'] ?? '');
$keahlian = trim($_POST['keahlian'] ?? '');
$no_telepon = trim($_POST['no_telepon'] ?? '');
$email = trim($_POST['email'] ?? '');
$status_tutor = trim($_POST['status_tutor'] ?? 'Aktif');

if (empty($nip) || empty($nama_tutor) || empty($keahlian)) {
    set_flash('danger', 'NIP, Nama Tutor, dan Bidang Keahlian wajib diisi!');
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

try {
    $stmt_check = $pdo->prepare("SELECT id_tutor FROM tutor WHERE nip = :nip LIMIT 1");
    $stmt_check->execute([':nip' => $nip]);
    if ($stmt_check->fetch()) {
        set_flash('danger', 'NIP / Kode "' . htmlspecialchars($nip) . '" sudah digunakan!');
        header('Location: ' . base_url('modules/tutor/index.php'));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO tutor (nip, nama_tutor, no_telepon, email, keahlian, status_tutor)
        VALUES (:nip, :nama_tutor, :no_telepon, :email, :keahlian, :status_tutor)
    ");
    $stmt->execute([
        ':nip' => $nip,
        ':nama_tutor' => $nama_tutor,
        ':no_telepon' => $no_telepon,
        ':email' => $email,
        ':keahlian' => $keahlian,
        ':status_tutor' => $status_tutor
    ]);

    set_flash('success', 'Data tutor "' . htmlspecialchars($nama_tutor) . '" berhasil ditambahkan!');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal menambahkan tutor: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/tutor/index.php'));
exit;

