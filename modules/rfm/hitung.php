
<?php
/**
 * ENGINE PERHITUNGAN ANALISIS RFM
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// ========================================
// 1. MENENTUKAN PERIODE ANALISIS
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
        AND pb.tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
    ";
} elseif ($periode === '6 Bulan Terakhir') {
    $date_condition = "
        AND pb.tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ";
} elseif ($periode === '1 Tahun Terakhir') {
    $date_condition = "
        AND pb.tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
    ";
}

// ========================================
// 3. PROSES PERHITUNGAN RFM
// ========================================

try {

    /*
     * Mengambil seluruh siswa beserta transaksi
     * pembayaran yang sudah berhasil.
     *
     * Relasi:
     * siswa.id_siswa
     *   -> pendaftaran.id_siswa
     *   -> pembayaran.id_pendaftaran
     */

    $sql = "
        SELECT
            s.id_siswa,
            s.nama_lengkap,

            COALESCE(
                DATEDIFF(
                    CURDATE(),
                    MAX(pb.tanggal_bayar)
                ),
                999
            ) AS recency_days,

            COUNT(pb.id_pembayaran) AS frequency_count,

            COALESCE(
                SUM(pb.jumlah_bayar),
                0
            ) AS monetary_amount

        FROM siswa s

        LEFT JOIN pendaftaran pd
            ON s.id_siswa = pd.id_siswa

        LEFT JOIN pembayaran pb
            ON pd.id_pendaftaran = pb.id_pendaftaran
            AND pb.status_pembayaran = 'Berhasil'
            {$date_condition}

        GROUP BY
            s.id_siswa,
            s.nama_lengkap

        ORDER BY s.nama_lengkap ASC
    ";

    $stmt = $pdo->query($sql);

    $students_rfm = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ========================================
    // 4. MEMULAI TRANSAKSI DATABASE
    // ========================================

    $pdo->beginTransaction();

    // ========================================
    // 5. MENGHAPUS HASIL ANALISIS PERIODE LAMA
    // ========================================

    $stmt_delete = $pdo->prepare("
        DELETE FROM analisis_rfm
        WHERE periode_analisis = :periode
    ");

    $stmt_delete->execute([
        ':periode' => $periode
    ]);

    // ========================================
    // 6. MENYIAPKAN QUERY PENYIMPANAN
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
    // 7. MENGHITUNG SKOR RFM SETIAP SISWA
    // ========================================

    foreach ($students_rfm as $st) {

        $r_days = (int) $st['recency_days'];
        $f_count = (int) $st['frequency_count'];
        $m_amount = (float) $st['monetary_amount'];

        // ------------------------------------
        // RECENCY SCORE
        // Semakin baru pembayaran, semakin tinggi skor.
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
        // Berdasarkan jumlah transaksi berhasil.
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
        // Berdasarkan total nominal pembayaran.
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
        // MENYIMPAN HASIL ANALISIS
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
    // 8. MENYIMPAN PERUBAHAN
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

    // Membatalkan transaksi jika terjadi kesalahan.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash(
        'danger',
        'Gagal menghitung Analisis RFM: ' . $e->getMessage()
    );
}

// ========================================
// 9. KEMBALI KE HALAMAN ANALISIS RFM
// ========================================

header(
    'Location: ' .
    base_url(
        'modules/rfm/index.php?periode=' .
        urlencode($periode)
    )
);

exit;
