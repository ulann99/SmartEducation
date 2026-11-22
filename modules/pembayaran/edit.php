<?php
/**
 * Edit Data Transaksi Pembayaran
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/pembayaran/index.php?tab=kelola'));
    exit;
}

$id_pembayaran = (int) ($_POST['id_pembayaran'] ?? 0);
$nominal = filter_var($_POST['nominal'] ?? null, FILTER_VALIDATE_FLOAT);
$tanggal_bayar = trim($_POST['tanggal_bayar'] ?? '');
$metode_pembayaran = trim($_POST['metode_pembayaran'] ?? '');
$status_pembayaran = trim($_POST['status_pembayaran'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

$metode_valid = ['Tunai', 'QRIS', 'Transfer Bank'];
$status_valid = ['Belum Lunas', 'Menunggu Konfirmasi', 'Lunas', 'Ditolak'];

$tanggal_obj = DateTime::createFromFormat('Y-m-d', $tanggal_bayar);
$tanggal_valid = $tanggal_obj && $tanggal_obj->format('Y-m-d') === $tanggal_bayar;

if (
    $id_pembayaran <= 0 ||
    $nominal === false || $nominal <= 0 ||
    !$tanggal_valid ||
    !in_array($metode_pembayaran, $metode_valid, true) ||
    !in_array($status_pembayaran, $status_valid, true)
) {
    set_flash('danger', 'Data edit pembayaran tidak valid. Periksa kembali seluruh isian.');
    header('Location: ' . base_url('modules/pembayaran/index.php?tab=kelola'));
    exit;
}

try {
    $cek = $pdo->prepare("
        SELECT id_pembayaran, status_pembayaran
        FROM pembayaran
        WHERE id_pembayaran = :id
        LIMIT 1
    ");
    $cek->execute([':id' => $id_pembayaran]);
    $data_lama = $cek->fetch(PDO::FETCH_ASSOC);

    if (!$data_lama) {
        set_flash('danger', 'Transaksi pembayaran tidak ditemukan.');
        header('Location: ' . base_url('modules/pembayaran/index.php?tab=kelola'));
        exit;
    }

    $cur_user = current_user();
    $status_verifikasi = in_array($status_pembayaran, ['Lunas', 'Ditolak'], true);

    if ($status_verifikasi) {
        $stmt = $pdo->prepare("
            UPDATE pembayaran SET
                nominal = :nominal,
                tanggal_bayar = :tanggal_bayar,
                metode_pembayaran = :metode,
                status_pembayaran = :status,
                keterangan = :keterangan,
                diverifikasi_oleh = :verifikator,
                tanggal_verifikasi = :tanggal_verifikasi
            WHERE id_pembayaran = :id
        ");

        $stmt->execute([
            ':nominal' => $nominal,
            ':tanggal_bayar' => $tanggal_bayar,
            ':metode' => $metode_pembayaran,
            ':status' => $status_pembayaran,
            ':keterangan' => $keterangan,
            ':verifikator' => $cur_user['id'],
            ':tanggal_verifikasi' => date('Y-m-d H:i:s'),
            ':id' => $id_pembayaran
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE pembayaran SET
                nominal = :nominal,
                tanggal_bayar = :tanggal_bayar,
                metode_pembayaran = :metode,
                status_pembayaran = :status,
                keterangan = :keterangan,
                diverifikasi_oleh = NULL,
                tanggal_verifikasi = NULL
            WHERE id_pembayaran = :id
        ");

        $stmt->execute([
            ':nominal' => $nominal,
            ':tanggal_bayar' => $tanggal_bayar,
            ':metode' => $metode_pembayaran,
            ':status' => $status_pembayaran,
            ':keterangan' => $keterangan,
            ':id' => $id_pembayaran
        ]);
    }

    set_flash('success', 'Data transaksi pembayaran berhasil diperbarui.');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal memperbarui transaksi: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/pembayaran/index.php?tab=kelola'));
exit;
