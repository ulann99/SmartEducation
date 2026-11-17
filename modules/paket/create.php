<?php
/**
 * Create Paket Belajar Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

$nama_paket = trim($_POST['nama_paket'] ?? '');
$jenjang = trim($_POST['jenjang'] ?? '');
$harga = (float)($_POST['harga'] ?? 0);
$pertemuan_per_minggu = (int)($_POST['pertemuan_per_minggu'] ?? 3);
$durasi_bulan = (int)($_POST['durasi_bulan'] ?? 1);
$status = trim($_POST['status'] ?? 'Aktif');
$deskripsi = trim($_POST['deskripsi'] ?? '');

if (empty($nama_paket) || empty($jenjang) || $harga <= 0) {
    set_flash('danger', 'Nama Paket, Jenjang, dan Harga harus valid!');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO paket_belajar (nama_paket, jenjang, harga, durasi_bulan, pertemuan_per_minggu, deskripsi, status)
        VALUES (:nama_paket, :jenjang, :harga, :durasi_bulan, :pertemuan_per_minggu, :deskripsi, :status)
    ");
    $stmt->execute([
        ':nama_paket' => $nama_paket,
        ':jenjang' => $jenjang,
        ':harga' => $harga,
        ':durasi_bulan' => $durasi_bulan,
        ':pertemuan_per_minggu' => $pertemuan_per_minggu,
        ':deskripsi' => $deskripsi,
        ':status' => $status
    ]);

    set_flash('success', 'Paket Belajar "' . htmlspecialchars($nama_paket) . '" berhasil ditambahkan!');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal menambahkan paket: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/paket/index.php'));
exit;

