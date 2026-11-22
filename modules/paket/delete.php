<?php
/**
 * Delete Paket Belajar Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

$id_paket = (int) ($_GET['id'] ?? 0);

// Validasi ID
if ($id_paket <= 0) {
    set_flash('danger', 'ID Paket tidak valid!');
    header('Location: ' . base_url('modules/paket/index.php'));
    exit;
}

try {

    // 1. Ambil data paket
    $stmt = $pdo->prepare("
        SELECT id_paket, nama_paket
        FROM paket_belajar
        WHERE id_paket = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id_paket]);

    $paket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paket) {
        set_flash('danger', 'Paket belajar tidak ditemukan!');
        header('Location: ' . base_url('modules/paket/index.php'));
        exit;
    }

    // 2. Coba hapus paket
    try {

        $stmt_delete = $pdo->prepare("
            DELETE FROM paket_belajar
            WHERE id_paket = :id
        ");

        $stmt_delete->execute([':id' => $id_paket]);

        if ($stmt_delete->rowCount() > 0) {
            set_flash(
                'success',
                'Paket Belajar "' . $paket['nama_paket'] . '" berhasil dihapus!'
            );
        } else {
            set_flash('danger', 'Paket gagal dihapus!');
        }

    } catch (PDOException $delete_error) {

        // 3. Jika masih digunakan oleh tabel lain,
        // ubah status paket menjadi Nonaktif.
        $error_code = $delete_error->errorInfo[1] ?? null;

        if ((int) $error_code === 1451) {

            $stmt_update = $pdo->prepare("
                UPDATE paket_belajar
                SET status = 'Nonaktif'
                WHERE id_paket = :id
            ");

            $stmt_update->execute([':id' => $id_paket]);

            set_flash(
                'success',
                'Paket "' . $paket['nama_paket'] .
                '" masih digunakan dalam transaksi atau data lainnya. ' .
                'Paket berhasil dinonaktifkan agar riwayat data tetap aman.'
            );

        } else {
            throw $delete_error;
        }
    }

} catch (PDOException $e) {

    set_flash(
        'danger',
        'Detail error: ' . $e->getMessage()
    );
}

header('Location: ' . base_url('modules/paket/index.php'));
exit;
