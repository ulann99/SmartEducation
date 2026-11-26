
<?php
/**
 * Pengelolaan Status Siswa
 * SMART EDUCATION PAREPARE
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__DIR__)));
}

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// ========================================
// FUNGSI REDIRECT
// ========================================

function status_siswa_redirect($type, $message)
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;

    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

// ========================================
// VALIDASI METHOD
// ========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    status_siswa_redirect(
        'danger',
        'Permintaan tidak valid.'
    );
}

// ========================================
// VALIDASI CSRF
// ========================================

$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_siswa']) ||
    !is_string($csrf_token) ||
    !hash_equals($_SESSION['csrf_siswa'], $csrf_token)
) {
    status_siswa_redirect(
        'danger',
        'Permintaan tidak valid. Silakan coba kembali.'
    );
}

// ========================================
// VALIDASI ID SISWA
// ========================================

$id_siswa = filter_var(
    $_POST['id_siswa'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $id_siswa === false ||
    $id_siswa === null ||
    $id_siswa <= 0
) {
    status_siswa_redirect(
        'danger',
        'ID siswa tidak valid.'
    );
}

// ========================================
// VALIDASI AKSI
// ========================================

$aksi = $_POST['aksi'] ?? '';

if (!in_array($aksi, ['nonaktifkan', 'aktifkan'], true)) {
    status_siswa_redirect(
        'danger',
        'Aksi tidak dikenal.'
    );
}

// ========================================
// PROSES PERUBAHAN STATUS
// ========================================

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id_siswa, nama_lengkap, status_siswa
        FROM siswa
        WHERE id_siswa = :id_siswa
        FOR UPDATE
    ");

    $stmt->execute([
        ':id_siswa' => $id_siswa
    ]);

    $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        throw new Exception('Data siswa tidak ditemukan.');
    }

    if (
        $aksi === 'nonaktifkan' &&
        $siswa['status_siswa'] === 'Nonaktif'
    ) {
        throw new Exception('Siswa sudah berstatus Nonaktif.');
    }

    if (
        $aksi === 'aktifkan' &&
        $siswa['status_siswa'] === 'Aktif'
    ) {
        throw new Exception('Siswa sudah berstatus Aktif.');
    }

    $status_baru = (
        $aksi === 'nonaktifkan'
    ) ? 'Nonaktif' : 'Aktif';

    $stmt_update = $pdo->prepare("
        UPDATE siswa
        SET status_siswa = :status
        WHERE id_siswa = :id_siswa
    ");

    $stmt_update->execute([
        ':status' => $status_baru,
        ':id_siswa' => $id_siswa
    ]);

    $nama_siswa = $siswa['nama_lengkap'];

    $pdo->commit();

    if ($aksi === 'nonaktifkan') {
        status_siswa_redirect(
            'success',
            'Siswa ' . $nama_siswa .
            ' berhasil dinonaktifkan. Riwayat pendaftaran dan pembayaran tetap tersimpan.'
        );
    }

    status_siswa_redirect(
        'success',
        'Siswa ' . $nama_siswa .
        ' berhasil diaktifkan kembali.'
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Gagal mengubah status siswa: ' . $e->getMessage()
    );

    $pesan = $e->getMessage();

    if (
        $pesan === 'Data siswa tidak ditemukan.' ||
        $pesan === 'Siswa sudah berstatus Aktif.' ||
        $pesan === 'Siswa sudah berstatus Nonaktif.'
    ) {
        status_siswa_redirect('warning', $pesan);
    }

    status_siswa_redirect(
        'danger',
        'Status siswa gagal diperbarui. Silakan coba kembali.'
    );
}
