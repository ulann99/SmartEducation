<?php
/**
 * Create Kelas Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

$kode_kelas = trim($_POST['kode_kelas'] ?? '');
$nama_kelas = trim($_POST['nama_kelas'] ?? '');
$id_paket = (int)($_POST['id_paket'] ?? 0);
$id_tutor = (int)($_POST['id_tutor'] ?? 0);
$id_ruangan = !empty($_POST['id_ruangan']) ? (int)$_POST['id_ruangan'] : null;
$max_siswa = (int)($_POST['max_siswa'] ?? 15);
$hari = !empty($_POST['hari']) ? trim($_POST['hari']) : null;
$jam_mulai = !empty($_POST['jam_mulai']) ? trim($_POST['jam_mulai']) : null;
$jam_selesai = !empty($_POST['jam_selesai']) ? trim($_POST['jam_selesai']) : null;
$status_kelas = trim($_POST['status_kelas'] ?? 'Aktif');

if (empty($kode_kelas) || empty($nama_kelas) || empty($id_paket) || empty($id_tutor)) {
    set_flash('danger', 'Kode Kelas, Nama Kelas, Paket, dan Tutor wajib diisi!');
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

try {
    $stmt_check = $pdo->prepare("SELECT id_kelas FROM kelas WHERE kode_kelas = :kode LIMIT 1");
    $stmt_check->execute([':kode' => $kode_kelas]);
    if ($stmt_check->fetch()) {
        set_flash('danger', 'Kode Kelas "' . htmlspecialchars($kode_kelas) . '" sudah digunakan!');
        header('Location: ' . base_url('modules/kelas/index.php'));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO kelas (kode_kelas, nama_kelas, id_paket, id_tutor, id_ruangan, max_siswa, hari, jam_mulai, jam_selesai, status_kelas)
        VALUES (:kode_kelas, :nama_kelas, :id_paket, :id_tutor, :id_ruangan, :max_siswa, :hari, :jam_mulai, :jam_selesai, :status_kelas)
    ");
    $stmt->execute([
        ':kode_kelas' => $kode_kelas,
        ':nama_kelas' => $nama_kelas,
        ':id_paket' => $id_paket,
        ':id_tutor' => $id_tutor,
        ':id_ruangan' => $id_ruangan,
        ':max_siswa' => $max_siswa,
        ':hari' => $hari,
        ':jam_mulai' => $jam_mulai,
        ':jam_selesai' => $jam_selesai,
        ':status_kelas' => $status_kelas
    ]);

    set_flash('success', 'Kelas "' . htmlspecialchars($nama_kelas) . '" berhasil dibuat!');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal membuat kelas: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/kelas/index.php'));
exit;

