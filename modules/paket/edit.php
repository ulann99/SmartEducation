<?php
/**
 * Edit Paket Belajar Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

$id_paket = (int)($_POST['id_paket'] ?? 0);
$nama_paket = trim($_POST['nama_paket'] ?? '');
$jenjang = trim($_POST['jenjang'] ?? '');
$harga = (float)($_POST['harga'] ?? 0);
$pertemuan_per_minggu = (int)($_POST['pertemuan_per_minggu'] ?? 3);
$durasi_bulan = (int)($_POST['durasi_bulan'] ?? 1);
$status = trim($_POST['status'] ?? 'Aktif');
$deskripsi = trim($_POST['deskripsi'] ?? '');

if (empty($id_paket) || empty($nama_paket) || empty($jenjang) || $harga <= 0) {
    set_flash('danger', 'Data paket tidak lengkap!');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE paket_belajar SET
            nama_paket = :nama_paket,
            jenjang = :jenjang,
            harga = :harga,
            durasi_bulan = :durasi_bulan,
            pertemuan_per_minggu = :pertemuan_per_minggu,
            deskripsi = :deskripsi,
            status = :status
        WHERE id_paket = :id_paket
    ");
    $stmt->execute([
        ':nama_paket' => $nama_paket,
        ':jenjang' => $jenjang,
        ':harga' => $harga,
        ':durasi_bulan' => $durasi_bulan,
        ':pertemuan_per_minggu' => $pertemuan_per_minggu,
        ':deskripsi' => $deskripsi,
        ':status' => $status,
        ':id_paket' => $id_paket
    ]);

    set_flash('success', 'Paket Belajar "' . htmlspecialchars($nama_paket) . '" berhasil diperbarui!');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal memperbarui paket: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/paket/index.php'));
exit;

