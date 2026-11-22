
<?php
/**
 * Delete Tutor Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// Tombol hapus pada halaman tutor mengirim ID melalui GET
$id_tutor = (int)($_GET['id'] ?? 0);

if ($id_tutor <= 0) {
    set_flash('danger', 'ID tutor tidak valid!');
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Periksa apakah tutor tersedia
    $stmt_tutor = $pdo->prepare("
        SELECT id_tutor, nama_tutor
        FROM tutor
        WHERE id_tutor = :id_tutor
        LIMIT 1
        FOR UPDATE
    ");

    $stmt_tutor->execute([
        ':id_tutor' => $id_tutor
    ]);

    $tutor = $stmt_tutor->fetch(PDO::FETCH_ASSOC);

    if (!$tutor) {
        throw new Exception('Data tutor tidak ditemukan!');
    }

    $nama_tutor = $tutor['nama_tutor'];

    // 2. Periksa apakah tutor masih mengampu kelas
    $stmt_kelas = $pdo->prepare("
        SELECT COUNT(*)
        FROM kelas
        WHERE id_tutor = :id_tutor
    ");

    $stmt_kelas->execute([
        ':id_tutor' => $id_tutor
    ]);

    $jumlah_kelas = (int) $stmt_kelas->fetchColumn();

    // 3. Jika masih mengampu kelas, jangan hapus
    if ($jumlah_kelas > 0) {

        $stmt_nonaktif = $pdo->prepare("
            UPDATE tutor
            SET status_tutor = 'Non-Aktif'
            WHERE id_tutor = :id_tutor
        ");

        $stmt_nonaktif->execute([
            ':id_tutor' => $id_tutor
        ]);

        $pdo->commit();

        set_flash(
            'warning',
            'Tutor "' . $nama_tutor .
            '" masih mengampu ' . $jumlah_kelas .
            ' kelas. Data tidak dihapus dan status diubah menjadi Non-Aktif.'
        );

    } else {

        // 4. Jika tidak memiliki kelas, hapus data tutor
        $stmt_delete = $pdo->prepare("
            DELETE FROM tutor
            WHERE id_tutor = :id_tutor
        ");

        $stmt_delete->execute([
            ':id_tutor' => $id_tutor
        ]);

        if ($stmt_delete->rowCount() === 0) {
            throw new Exception('Data tutor gagal dihapus!');
        }

        $pdo->commit();

        set_flash(
            'success',
            'Data tutor "' . $nama_tutor .
            '" berhasil dihapus!'
        );
    }

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash(
        'danger',
        'Gagal menghapus tutor: ' . $e->getMessage()
    );
}

// 5. Kembali ke halaman Data Tutor
header('Location: ' . base_url('modules/tutor/index.php'));
exit;
