<?php
/**
 * Simpan Hasil Jadwal CSP ke Database
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if (empty($_SESSION['last_csp_result']) || empty($_SESSION['last_csp_result']['solution'])) {
    set_flash('danger', 'Tidak ada data solusi jadwal CSP yang siap diterapkan.');
    header('Location: ' . base_url('modules/penjadwalan/index.php'));
    exit;
}

$solution = $_SESSION['last_csp_result']['solution'];

try {
    $pdo->beginTransaction();

    // 1. Clear previous active schedules
    $pdo->exec("DELETE FROM jadwal");

    // 2. Prepare insert statement for jadwal
    $stmt_jadwal = $pdo->prepare("
        INSERT INTO jadwal (id_kelas, id_tutor, id_ruangan, hari, jam_mulai, jam_selesai, status_jadwal, keterangan, metode_pembuatan)
        VALUES (:id_kelas, :id_tutor, :id_ruangan, :hari, :jam_mulai, :jam_selesai, 'Aktif', 'Dihasilkan oleh Algoritma CSP Backtracking', 'CSP_Backtracking')
    ");

    // 3. Prepare update statement for kelas
    $stmt_kelas = $pdo->prepare("
        UPDATE kelas SET
            hari = :hari,
            jam_mulai = :jam_mulai,
            jam_selesai = :jam_selesai,
            id_ruangan = :id_ruangan
        WHERE id_kelas = :id_kelas
    ");

    foreach ($solution as $sol) {
        // Insert into jadwal
        $stmt_jadwal->execute([
            ':id_kelas' => $sol['id_kelas'],
            ':id_tutor' => $sol['id_tutor'],
            ':id_ruangan' => $sol['id_ruangan'],
            ':hari' => $sol['hari'],
            ':jam_mulai' => $sol['jam_mulai'],
            ':jam_selesai' => $sol['jam_selesai']
        ]);

        // Update kelas
        $stmt_kelas->execute([
            ':hari' => $sol['hari'],
            ':jam_mulai' => $sol['jam_mulai'],
            ':jam_selesai' => $sol['jam_selesai'],
            ':id_ruangan' => $sol['id_ruangan'],
            ':id_kelas' => $sol['id_kelas']
        ]);
    }

    $pdo->commit();

    // Clear session solver solution
    unset($_SESSION['last_csp_result']);

    set_flash('success', 'Selamat! Seluruh jadwal hasil komputasi CSP Backtracking berhasil diterapkan secara resmi ke sistem.');
    header('Location: ' . base_url('modules/jadwal/index.php'));
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Gagal menyimpan jadwal ke database: ' . $e->getMessage());
    header('Location: ' . base_url('modules/penjadwalan/index.php'));
    exit;
}

