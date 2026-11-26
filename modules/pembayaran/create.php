
<?php
/**
 * Create Pembayaran Action
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

$id_pendaftaran = (int)($_POST['id_pendaftaran'] ?? 0);
$jenis_pembayaran = trim($_POST['jenis_pembayaran'] ?? '');
$jumlah_bayar = (float)($_POST['jumlah_bayar'] ?? 0);
$tanggal_bayar = trim($_POST['tanggal_bayar'] ?? date('Y-m-d'));
$metode_pembayaran = trim($_POST['metode_pembayaran'] ?? 'Tunai');
$status_pembayaran = trim($_POST['status_pembayaran'] ?? 'Menunggu');
$keterangan = trim($_POST['keterangan'] ?? '');

$jenis_valid = ['Pendaftaran', 'Bulanan'];
$metode_valid = ['Tunai', 'QRIS'];
$status_valid = ['Menunggu', 'Berhasil', 'Ditolak'];

$tanggal_obj = DateTime::createFromFormat('Y-m-d', $tanggal_bayar);
$tanggal_valid = $tanggal_obj && $tanggal_obj->format('Y-m-d') === $tanggal_bayar;

if (
    $id_pendaftaran <= 0 ||
    !in_array($jenis_pembayaran, $jenis_valid, true) ||
    $jumlah_bayar <= 0 ||
    !$tanggal_valid ||
    !in_array($metode_pembayaran, $metode_valid, true) ||
    !in_array($status_pembayaran, $status_valid, true)
) {
    set_flash('danger', 'Data pembayaran tidak valid. Periksa kembali formulir.');
    header('Location: ' . $redirect);
    exit;
}

$bukti_filename = null;

if (isset($_FILES['bukti_pembayaran']) &&
    $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
        set_flash('danger', 'Upload bukti pembayaran gagal.');
        header('Location: ' . $redirect);
        exit;
    }

    $upload = upload_image($_FILES['bukti_pembayaran'], 'uploads');

    if (!$upload['success']) {
        set_flash('danger', $upload['message']);
        header('Location: ' . $redirect);
        exit;
    }

    $bukti_filename = $upload['filename'];
}

try {
    // Pastikan pendaftaran tersedia
    $cek = $pdo->prepare("
        SELECT id_pendaftaran
        FROM pendaftaran
        WHERE id_pendaftaran = :id
        LIMIT 1
    ");
    $cek->execute([':id' => $id_pendaftaran]);

    if (!$cek->fetch()) {
        set_flash('danger', 'Data pendaftaran tidak ditemukan.');
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO pembayaran (
            id_pendaftaran,
            jenis_pembayaran,
            jumlah_bayar,
            tanggal_bayar,
            metode_pembayaran,
            bukti_pembayaran,
            status_pembayaran,
            keterangan
        ) VALUES (
            :id_pendaftaran,
            :jenis_pembayaran,
            :jumlah_bayar,
            :tanggal_bayar,
            :metode_pembayaran,
            :bukti_pembayaran,
            :status_pembayaran,
            :keterangan
        )
    ");

    $stmt->execute([
        ':id_pendaftaran' => $id_pendaftaran,
        ':jenis_pembayaran' => $jenis_pembayaran,
        ':jumlah_bayar' => $jumlah_bayar,
        ':tanggal_bayar' => $tanggal_bayar,
        ':metode_pembayaran' => $metode_pembayaran,
        ':bukti_pembayaran' => $bukti_filename,
        ':status_pembayaran' => $status_pembayaran,
        ':keterangan' => $keterangan !== '' ? $keterangan : null
    ]);

    set_flash('success', 'Data pembayaran berhasil ditambahkan.');

} catch (PDOException $e) {
    set_flash('danger', 'Gagal menambahkan pembayaran: ' . $e->getMessage());
}

header('Location: ' . $redirect);
exit;
