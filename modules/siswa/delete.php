<?php
/**
 * Delete Siswa Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// ========================================
// PASTIKAN REQUEST POST
// ========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

// ========================================
// AMBIL ID SISWA
// ========================================

$id_siswa = (int)($_POST['id_siswa'] ?? 0);

if ($id_siswa <= 0) {
    set_flash('danger', 'ID siswa tidak valid!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

// ========================================
// PROSES PENGHAPUSAN
// ========================================

try {

    $pdo->beginTransaction();

    // ------------------------------------
    // 1. CEK DATA SISWA
    // ------------------------------------

    $stmt_siswa = $pdo->prepare("
        SELECT id_siswa, nama_siswa
        FROM siswa
        WHERE id_siswa = :id_siswa
        LIMIT 1
    ");

    $stmt_siswa->execute([
        ':id_siswa' => $id_siswa
    ]);

    $siswa = $stmt_siswa->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        throw new Exception('Data siswa tidak ditemukan!');
    }

    $nama_siswa = $siswa['nama_siswa'];

    // ------------------------------------
    // 2. CEK RIWAYAT PEMBAYARAN
    // ------------------------------------

    $stmt_pembayaran = $pdo->prepare("
        SELECT COUNT(*)
        FROM pembayaran
        WHERE id_siswa = :id_siswa
    ");

    $stmt_pembayaran->execute([
        ':id_siswa' => $id_siswa
    ]);

    $jumlah_pembayaran = (int)$stmt_pembayaran->fetchColumn();

    // ------------------------------------
    // 3. CEK KEANGGOTAAN KELAS
    // ------------------------------------

    $stmt_kelas = $pdo->prepare("
        SELECT COUNT(*)
        FROM anggota_kelas
        WHERE id_siswa = :id_siswa
    ");

    $stmt_kelas->execute([
        ':id_siswa' => $id_siswa
    ]);

    $jumlah_kelas = (int)$stmt_kelas->fetchColumn();

    // ------------------------------------
    // 4. JIKA MEMILIKI RIWAYAT
    // ------------------------------------

    if ($jumlah_pembayaran > 0 || $jumlah_kelas > 0) {

        $stmt_nonaktif = $pdo->prepare("
            UPDATE siswa
            SET status_siswa = 'Non-Aktif'
            WHERE id_siswa = :id_siswa
        ");

        $stmt_nonaktif->execute([
            ':id_siswa' => $id_siswa
        ]);

        $pdo->commit();

        set_flash(
            'warning',
            'Siswa "' . $nama_siswa .
            '" memiliki riwayat pembayaran atau kelas sehingga tidak dihapus. Status siswa berhasil diubah menjadi Non-Aktif.'
        );

        header('Location: ' . base_url('modules/siswa/index.php'));
        exit;
    }

    // ------------------------------------
    // 5. HAPUS AKUN SISWA
    // ------------------------------------

    $stmt_user = $pdo->prepare("
        DELETE FROM users
        WHERE id_siswa = :id_siswa
          AND role = 'siswa'
    ");

    $stmt_user->execute([
        ':id_siswa' => $id_siswa
    ]);

    // ------------------------------------
    // 6. HAPUS DATA SISWA
    // ------------------------------------

    $stmt_delete = $pdo->prepare("
        DELETE FROM siswa
        WHERE id_siswa = :id_siswa
    ");

    $stmt_delete->execute([
        ':id_siswa' => $id_siswa
    ]);

    // ------------------------------------
    // 7. SIMPAN TRANSAKSI
    // ------------------------------------

    $pdo->commit();

    set_flash(
        'success',
        'Data siswa "' . $nama_siswa .
        '" berhasil dihapus dari sistem!'
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash(
        'danger',
        'Gagal menghapus data siswa: ' . $e->getMessage()
    );
}

// ========================================
// KEMBALI KE DATA SISWA
// ========================================

header('Location: ' . base_url('modules/siswa/index.php'));
exit;
