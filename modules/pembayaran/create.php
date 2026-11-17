<?php
/**
 * Create Pembayaran Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/pembayaran/index.php'));
    exit;
}

$id_siswa = (int)($_POST['id_siswa'] ?? 0);
$id_paket = (int)($_POST['id_paket'] ?? 0);
$nominal = (float)($_POST['nominal'] ?? 0);
$tanggal_bayar = trim($_POST['tanggal_bayar'] ?? date('Y-m-d'));
$metode_pembayaran = trim($_POST['metode_pembayaran'] ?? 'Tunai');
$status_pembayaran = trim($_POST['status_pembayaran'] ?? 'Lunas');
$keterangan = trim($_POST['keterangan'] ?? '');

if (empty($id_siswa) || empty($id_paket) || $nominal <= 0) {
    set_flash('danger', 'Siswa, Paket, dan Nominal pembayaran harus valid!');
    header('Location: ' . base_url('modules/pembayaran/index.php'));
    exit;
}

// Generate Unique Transaction Code
$kode_transaksi = 'TRX-' . date('Ymd', strtotime($tanggal_bayar)) . '-' . strtoupper(substr(uniqid(), -5));

$bukti_filename = null;
if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
    $upload = upload_image($_FILES['bukti_pembayaran'], 'uploads');
    if ($upload['success']) {
        $bukti_filename = $upload['filename'];
    } else {
        set_flash('danger', $upload['message']);
        header('Location: ' . base_url('modules/pembayaran/index.php'));
        exit;
    }
}

try {
    $cur_user = current_user();
    $diverifikasi_oleh = ($status_pembayaran === 'Lunas') ? $cur_user['id'] : null;
    $tanggal_verifikasi = ($status_pembayaran === 'Lunas') ? date('Y-m-d H:i:s') : null;

    $stmt = $pdo->prepare("
        INSERT INTO pembayaran (kode_transaksi, id_siswa, id_paket, nominal, tanggal_bayar, metode_pembayaran, status_pembayaran, bukti_pembayaran, keterangan, diverifikasi_oleh, tanggal_verifikasi)
        VALUES (:kode, :id_siswa, :id_paket, :nominal, :tanggal_bayar, :metode, :status, :bukti, :keterangan, :verifikator, :tgl_verif)
    ");
    $stmt->execute([
        ':kode' => $kode_transaksi,
        ':id_siswa' => $id_siswa,
        ':id_paket' => $id_paket,
        ':nominal' => $nominal,
        ':tanggal_bayar' => $tanggal_bayar,
        ':metode' => $metode_pembayaran,
        ':status' => $status_pembayaran,
        ':bukti' => $bukti_filename,
        ':keterangan' => $keterangan,
        ':verifikator' => $diverifikasi_oleh,
        ':tgl_verif' => $tanggal_verifikasi
    ]);

    set_flash('success', 'Pembayaran transaksi ' . $kode_transaksi . ' sebesar ' . format_rupiah($nominal) . ' berhasil dicatat!');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal mencatat pembayaran: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/pembayaran/index.php'));
exit;

