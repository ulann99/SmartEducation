
<?php
/**
 * Nonaktifkan Data Siswa
 * SMART EDUCATION PAREPARE
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__DIR__)));
}

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// ========================================
// REDIRECT NOTIFIKASI
// ========================================

function delete_siswa_redirect($type, $message)
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
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
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
    delete_siswa_redirect(
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

if ($id_siswa === false || $id_siswa === null || $id_siswa <= 0) {
    delete_siswa_redirect(
        'danger',
        'ID siswa tidak valid.'
    );
}

// ========================================
// PROSES NONAKTIFKAN SISWA
// ========================================

try {

    $pdo->beginTransaction();

    // Periksa dan kunci data siswa
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
        throw new Exception(
            'Data siswa tidak ditemukan.'
        );
    }

    if ($siswa['status_siswa'] === 'Nonaktif') {
        throw new Exception(
            'Siswa tersebut sudah berstatus Nonaktif.'
        );
    }

    // Ubah status menjadi Nonaktif
    $stmt_update = $pdo->prepare("
        UPDATE siswa
        SET status_siswa = 'Nonaktif'
        WHERE id_siswa = :id_siswa
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

    $pdo->commit();

    delete_siswa_redirect(
        'success',
        'Siswa ' . $nama_siswa .
        ' berhasil dinonaktifkan. Riwayat pendaftaran dan pembayaran tetap tersimpan.'
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Gagal menonaktifkan siswa: ' . $e->getMessage()
    );

    $pesan = $e->getMessage();

    if (
        $pesan === 'Data siswa tidak ditemukan.' ||
        $pesan === 'Siswa tersebut sudah berstatus Nonaktif.'
    ) {
        delete_siswa_redirect('warning', $pesan);
    }

    delete_siswa_redirect(
        'danger',
        'Siswa gagal dinonaktifkan. Silakan coba kembali.'
    );
}
