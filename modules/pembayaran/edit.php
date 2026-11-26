
<?php
/**
 * Edit Pembayaran Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$redirect = base_url('modules/pembayaran/index.php?tab=kelola');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$id_pembayaran = (int)($_POST['id_pembayaran'] ?? 0);
$id_pendaftaran = (int)($_POST['id_pendaftaran'] ?? 0);
$jenis_pembayaran = trim($_POST['jenis_pembayaran'] ?? '');
$jumlah_bayar = (float)($_POST['jumlah_bayar'] ?? 0);
$tanggal_bayar = trim($_POST['tanggal_bayar'] ?? '');
$metode_pembayaran = trim($_POST['metode_pembayaran'] ?? '');
$status_pembayaran = trim($_POST['status_pembayaran'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

$jenis_valid = ['Pendaftaran', 'Bulanan'];
$metode_valid = ['Tunai', 'QRIS'];
$status_valid = ['Menunggu', 'Berhasil', 'Ditolak'];

$tanggal_obj = DateTime::createFromFormat('Y-m-d', $tanggal_bayar);
$tanggal_valid = $tanggal_obj && $tanggal_obj->format('Y-m-d') === $tanggal_bayar;

if (
    $id_pembayaran <= 0 ||
    $id_pendaftaran <= 0 ||
    !in_array($jenis_pembayaran, $jenis_valid, true) ||
    $jumlah_bayar <= 0 ||
    !$tanggal_valid ||
    !in_array($metode_pembayaran, $metode_valid, true) ||
    !in_array($status_pembayaran, $status_valid, true)
) {
    set_flash('danger', 'Data pembayaran tidak valid.');
    header('Location: ' . $redirect);
    exit;
}

try {
    $cek = $pdo->prepare("
        SELECT id_pembayaran
        FROM pembayaran
        WHERE id_pembayaran = :id
        LIMIT 1
    ");
    $cek->execute([':id' => $id_pembayaran]);

    if (!$cek->fetch()) {
        set_flash('danger', 'Data pembayaran tidak ditemukan.');
        header('Location: ' . $redirect);
        exit;
    }

    $cek_pendaftaran = $pdo->prepare("
        SELECT id_pendaftaran
        FROM pendaftaran
        WHERE id_pendaftaran = :id
        LIMIT 1
    ");
    $cek_pendaftaran->execute([':id' => $id_pendaftaran]);

    if (!$cek_pendaftaran->fetch()) {
        set_flash('danger', 'Data pendaftaran tidak ditemukan.');
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE pembayaran SET
            id_pendaftaran = :id_pendaftaran,
            jenis_pembayaran = :jenis_pembayaran,
            jumlah_bayar = :jumlah_bayar,
            tanggal_bayar = :tanggal_bayar,
            metode_pembayaran = :metode_pembayaran,
            status_pembayaran = :status_pembayaran,
            keterangan = :keterangan
        WHERE id_pembayaran = :id_pembayaran
    ");

    $stmt->execute([
        ':id_pendaftaran' => $id_pendaftaran,
        ':jenis_pembayaran' => $jenis_pembayaran,
        ':jumlah_bayar' => $jumlah_bayar,
        ':tanggal_bayar' => $tanggal_bayar,
        ':metode_pembayaran' => $metode_pembayaran,
        ':status_pembayaran' => $status_pembayaran,
        ':keterangan' => $keterangan !== '' ? $keterangan : null,
        ':id_pembayaran' => $id_pembayaran
    ]);

    set_flash('success', 'Data pembayaran berhasil diperbarui.');

} catch (PDOException $e) {
    set_flash('danger', 'Gagal memperbarui pembayaran: ' . $e->getMessage());
}

header('Location: ' . $redirect);
exit;
