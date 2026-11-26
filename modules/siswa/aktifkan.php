
<?php
/**
 * Aktifkan Kembali Data Siswa
 * SMART EDUCATION PAREPARE
 */

// ========================================
// INISIALISASI
// ========================================

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__DIR__)));
}

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// ========================================
// FUNGSI REDIRECT
// ========================================

function aktifkan_siswa_redirect($type, $message)
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;

    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

// ========================================
// VALIDASI METHOD POST
// ========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

// ========================================
// VALIDASI CSRF TOKEN
// ========================================

$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_siswa']) ||
    !is_string($csrf_token) ||
    !hash_equals($_SESSION['csrf_siswa'], $csrf_token)
) {
    aktifkan_siswa_redirect(
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
    aktifkan_siswa_redirect(
        'danger',
        'ID siswa tidak valid.'
    );
}

// ========================================
// PROSES AKTIFKAN SISWA
// ========================================

try {

    $pdo->beginTransaction();

    // ------------------------------------
    // 1. PERIKSA DATA SISWA
    // ------------------------------------

    $stmt_siswa = $pdo->prepare("
        SELECT id_siswa, nama_lengkap, status_siswa
        FROM siswa
        WHERE id_siswa = :id_siswa
        FOR UPDATE
    ");

    $stmt_siswa->execute([
        ':id_siswa' => $id_siswa
    ]);

    $siswa = $stmt_siswa->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        throw new Exception(
            'Data siswa tidak ditemukan.'
        );
    }

    // ------------------------------------
    // 2. PERIKSA STATUS SISWA
    // ------------------------------------

    if ($siswa['status_siswa'] === 'Aktif') {
        throw new Exception(
            'Siswa tersebut sudah berstatus Aktif.'
        );
    }

    // ------------------------------------
    // 3. UBAH STATUS MENJADI AKTIF
    // ------------------------------------

    $stmt_update = $pdo->prepare("
        UPDATE siswa
        SET status_siswa = 'Aktif'
        WHERE id_siswa = :id_siswa
          AND status_siswa = 'Nonaktif'
    ");

    $stmt_update->execute([
        ':id_siswa' => $id_siswa
    ]);

    if ($stmt_update->rowCount() === 0) {
        throw new Exception(
            'Status siswa gagal diperbarui.'
        );
    }

    $nama_siswa = $siswa['nama_lengkap'];

    // ------------------------------------
    // 4. SELESAIKAN TRANSAKSI
    // ------------------------------------

    $pdo->commit();

    aktifkan_siswa_redirect(
        'success',
        'Siswa ' . $nama_siswa .
        ' berhasil diaktifkan kembali. Seluruh riwayat pendaftaran dan pembayaran tetap tersimpan.'
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Gagal mengaktifkan siswa: ' . $e->getMessage()
    );

    $pesan = $e->getMessage();

    if (
        $pesan === 'Data siswa tidak ditemukan.' ||
        $pesan === 'Siswa tersebut sudah berstatus Aktif.'
    ) {
        aktifkan_siswa_redirect(
            'warning',
            $pesan
        );
    }

    aktifkan_siswa_redirect(
        'danger',
        'Siswa gagal diaktifkan. Silakan coba kembali.'
    );
}
