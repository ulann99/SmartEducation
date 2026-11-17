<?php
/**
 * Create Ruangan Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/ruangan/index.php'));
    exit;
}

$kode_ruangan = trim($_POST['kode_ruangan'] ?? '');
$nama_ruangan = trim($_POST['nama_ruangan'] ?? '');
$kapasitas = (int)($_POST['kapasitas'] ?? 15);
$status_ruangan = trim($_POST['status_ruangan'] ?? 'Tersedia');
$fasilitas = trim($_POST['fasilitas'] ?? '');

if (empty($kode_ruangan) || empty($nama_ruangan) || $kapasitas <= 0) {
    set_flash('danger', 'Kode Ruangan, Nama Ruangan, dan Kapasitas harus diisi dengan benar!');
    header('Location: ' . base_url('modules/ruangan/index.php'));
    exit;
}

try {
    $stmt_check = $pdo->prepare("SELECT id_ruangan FROM ruangan WHERE kode_ruangan = :kode LIMIT 1");
    $stmt_check->execute([':kode' => $kode_ruangan]);
    if ($stmt_check->fetch()) {
        set_flash('danger', 'Kode Ruangan "' . htmlspecialchars($kode_ruangan) . '" sudah digunakan!');
        header('Location: ' . base_url('modules/ruangan/index.php'));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO ruangan (kode_ruangan, nama_ruangan, kapasitas, fasilitas, status_ruangan)
        VALUES (:kode_ruangan, :nama_ruangan, :kapasitas, :fasilitas, :status_ruangan)
    ");
    $stmt->execute([
        ':kode_ruangan' => $kode_ruangan,
        ':nama_ruangan' => $nama_ruangan,
        ':kapasitas' => $kapasitas,
        ':fasilitas' => $fasilitas,
        ':status_ruangan' => $status_ruangan
    ]);

    set_flash('success', 'Ruangan "' . htmlspecialchars($nama_ruangan) . '" berhasil ditambahkan!');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal menambahkan ruangan: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/ruangan/index.php'));
exit;

