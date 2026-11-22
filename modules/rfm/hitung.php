<?php
/**
 * Engine Hitung Analisis RFM
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// ========================================
// 1. PERIODE ANALISIS
// ========================================

$periode = trim(
    $_POST['periode'] ??
    $_GET['periode'] ??
    'Semua Waktu'
);

$periode_valid = [
    'Semua Waktu',
    '3 Bulan Terakhir',
    '6 Bulan Terakhir',
    '1 Tahun Terakhir'
];

if (!in_array($periode, $periode_valid, true)) {
    $periode = 'Semua Waktu';
}

// ========================================
// 2. FILTER TANGGAL PEMBAYARAN
// ========================================

$date_condition = '';

if ($periode === '3 Bulan Terakhir') {
    $date_condition = "
        AND p.tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
    ";
} elseif ($periode === '6 Bulan Terakhir') {
    $date_condition = "
        AND p.tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ";
} elseif ($periode === '1 Tahun Terakhir') {
    $date_condition = "
        AND p.tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
    ";
}

// ========================================
// 3. PROSES PERHITUNGAN RFM
// ========================================

try {

    // Mengambil data seluruh siswa dan transaksi lunas
    $sql = "
        SELECT
            s.id_siswa,
            s.nama_siswa,

            COALESCE(
                DATEDIFF(CURDATE(), MAX(p.tanggal_bayar)),
                999
            ) AS recency_days,

            COUNT(p.id_pembayaran) AS frequency_count,

            COALESCE(
                SUM(p.nominal),
                0
            ) AS monetary_amount

        FROM siswa s

        LEFT JOIN pembayaran p
            ON s.id_siswa = p.id_siswa
            AND p.status_pembayaran = 'Lunas'
            {$date_condition}

        GROUP BY
            s.id_siswa,
            s.nama_siswa

        ORDER BY s.nama_siswa ASC
    ";

    $students_rfm = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Memulai transaksi database
    $pdo->beginTransaction();

    // ========================================
    // 4. HAPUS HASIL ANALISIS PERIODE LAMA
    // ========================================

    $stmt_del = $pdo->prepare("
        DELETE FROM analisis_rfm
        WHERE periode_analisis = :periode
    ");

    $stmt_del->execute([
        ':periode' => $periode
    ]);

    // ========================================
    // 5. SIAPKAN QUERY PENYIMPANAN
    // ========================================

    $stmt_insert = $pdo->prepare("
        INSERT INTO analisis_rfm (
            id_siswa,
            periode_analisis,
            recency_days,
            frequency_count,
            monetary_amount,
            r_score,
            f_score,
            m_score,
            total_rfm_score,
            segmentasi,
            tanggal_analisis
        )
        VALUES (
            :id_siswa,
            :periode,
            :recency,
            :frequency,
            :monetary,
            :r_score,
            :f_score,
            :m_score,
            :total_score,
            :segmentasi,
            NOW()
        )
    ");

    // ========================================
    // 6. HITUNG SKOR SETIAP SISWA
    // ========================================

    foreach ($students_rfm as $st) {

        $r_days = (int) $st['recency_days'];
        $f_count = (int) $st['frequency_count'];
        $m_amount = (float) $st['monetary_amount'];

        // ------------------------------------
        // RECENCY SCORE
        // ------------------------------------

        if ($r_days <= 30) {
            $r_score = 3;
        } elseif ($r_days <= 60) {
            $r_score = 2;
        } else {
            $r_score = 1;
        }

        // ------------------------------------
        // FREQUENCY SCORE
        // ------------------------------------

        if ($f_count >= 4) {
            $f_score = 3;
        } elseif ($f_count >= 2) {
            $f_score = 2;
        } else {
            $f_score = 1;
        }

        // ------------------------------------
        // MONETARY SCORE
        // ------------------------------------

        if ($m_amount >= 1000000) {
            $m_score = 3;
        } elseif ($m_amount >= 400000) {
            $m_score = 2;
        } else {
            $m_score = 1;
        }

        // ------------------------------------
        // TOTAL SKOR RFM
        // ------------------------------------

        $total_rfm = $r_score + $f_score + $m_score;

        // ------------------------------------
        // SEGMENTASI SISWA
        // ------------------------------------

        if ($total_rfm >= 8) {

            $segment = 'Pembayar Rutin';

        } elseif ($total_rfm >= 5) {

            $segment = 'Perlu Pengingat';

        } else {

            $segment = 'Berisiko';
        }

        // ------------------------------------
        // SIMPAN HASIL ANALISIS
        // ------------------------------------

        $stmt_insert->execute([
            ':id_siswa' => $st['id_siswa'],
            ':periode' => $periode,
            ':recency' => $r_days,
            ':frequency' => $f_count,
            ':monetary' => $m_amount,
            ':r_score' => $r_score,
            ':f_score' => $f_score,
            ':m_score' => $m_score,
            ':total_score' => $total_rfm,
            ':segmentasi' => $segment
        ]);
    }

    // ========================================
    // 7. SIMPAN PERUBAHAN
    // ========================================

    $pdo->commit();

    set_flash(
        'success',
        'Analisis RFM berhasil diperbarui untuk periode "' .
        $periode .
        '". Total siswa dianalisis: ' .
        count($students_rfm)
    );

} catch (Throwable $e) {

    // Batalkan transaksi jika terjadi kesalahan
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash(
        'danger',
        'Gagal menghitung Analisis RFM: ' . $e->getMessage()
    );
}

// ========================================
// 8. KEMBALI KE HALAMAN ANALISIS RFM
// ========================================

header(
    'Location: ' .
    base_url(
        'modules/rfm/index.php?periode=' .
        urlencode($periode)
    )
);

exit;
