
<?php
/**
 * Verify dan Update Status Pembayaran
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/pembayaran/index.php'));
    exit;
}

$id_pembayaran = (int) ($_POST['id_pembayaran'] ?? 0);
$status_pembayaran = trim($_POST['status_pembayaran'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

if ($id_pembayaran <= 0 || !in_array($status_pembayaran, ['Lunas', 'Ditolak'], true)) {
    set_flash('danger', 'Data pembayaran atau status tidak valid!');
    header('Location: ' . base_url('modules/pembayaran/index.php?tab=kelola'));
    exit;
}

try {
    $cur_user = current_user();
    $diverifikasi_oleh = $cur_user['id'];
    $tanggal_verifikasi = date('Y-m-d H:i:s');

    // Pastikan transaksi memang masih perlu diproses
    $cek = $pdo->prepare("
        SELECT id_pembayaran, status_pembayaran
        FROM pembayaran
        WHERE id_pembayaran = :id
    ");
    $cek->execute([':id' => $id_pembayaran]);
    $pembayaran = $cek->fetch(PDO::FETCH_ASSOC);

    if (!$pembayaran) {
        set_flash('danger', 'Transaksi pembayaran tidak ditemukan!');
        header('Location: ' . base_url('modules/pembayaran/index.php?tab=kelola'));
        exit;
    }

    if (!in_array($pembayaran['status_pembayaran'], ['Menunggu Konfirmasi', 'Belum Lunas'], true)) {
        set_flash('warning', 'Transaksi ini sudah diproses dan tidak dapat diverifikasi kembali.');
        header('Location: ' . base_url('modules/pembayaran/index.php?tab=kelola'));
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE pembayaran SET
            status_pembayaran = :status,
            keterangan = :keterangan,
            diverifikasi_oleh = :verifikator,
            tanggal_verifikasi = :tgl_verif
        WHERE id_pembayaran = :id
    ");

    $stmt->execute([
        ':status' => $status_pembayaran,
        ':keterangan' => $keterangan,
        ':verifikator' => $diverifikasi_oleh,
        ':tgl_verif' => $tanggal_verifikasi,
        ':id' => $id_pembayaran
    ]);

    set_flash(
        'success',
        'Status pembayaran berhasil diperbarui menjadi: ' . $status_pembayaran
    );

} catch (PDOException $e) {
    set_flash('danger', 'Gagal memperbarui pembayaran: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/pembayaran/index.php?tab=kelola'));
exit;
