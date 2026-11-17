<?php
/**
 * Engine Hitung Analisis RFM
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$periode = trim($_POST['periode'] ?? $_GET['periode'] ?? 'Semua Waktu');

// Calculate date filter based on selected period
$date_condition = "";
$date_param = [];

if ($periode === '3 Bulan Terakhir') {
    $date_condition = "AND p.tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
} elseif ($periode === '6 Bulan Terakhir') {
    $date_condition = "AND p.tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
} elseif ($periode === '1 Tahun Terakhir') {
    $date_condition = "AND p.tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
}

try {
    // 1. Fetch all active students and calculate raw Recency, Frequency, Monetary
    $sql = "
        SELECT 
            s.id_siswa,
            s.nama_siswa,
            COALESCE(DATEDIFF(CURDATE(), MAX(p.tanggal_bayar)), 999) AS recency_days,
            COUNT(CASE WHEN p.status_pembayaran = 'Lunas' THEN p.id_pembayaran END) AS frequency_count,
            COALESCE(SUM(CASE WHEN p.status_pembayaran = 'Lunas' THEN p.nominal ELSE 0 END), 0) AS monetary_amount
        FROM siswa s
        LEFT JOIN pembayaran p ON s.id_siswa = p.id_siswa AND p.status_pembayaran = 'Lunas' {$date_condition}
        GROUP BY s.id_siswa, s.nama_siswa
    ";
    $students_rfm = $pdo->query($sql)->fetchAll();

    // Begin transaction for database sync
    $pdo->beginTransaction();

    // Delete existing records for this period
    $stmt_del = $pdo->prepare("DELETE FROM analisis_rfm WHERE periode_analisis = :periode");
    $stmt_del->execute([':periode' => $periode]);

    $stmt_insert = $pdo->prepare("
        INSERT INTO analisis_rfm 
        (id_siswa, periode_analisis, recency_days, frequency_count, monetary_amount, r_score, f_score, m_score, total_rfm_score, segmentasi, tanggal_analisis)
        VALUES (:id_siswa, :periode, :recency, :frequency, :monetary, :r_score, :f_score, :m_score, :total_score, :segmentasi, NOW())
    ");

    foreach ($students_rfm as $st) {
        $r_days = (int)$st['recency_days'];
        $f_count = (int)$st['frequency_count'];
        $m_amount = (float)$st['monetary_amount'];

        // Scoring Logic:
        // R Score (Recency: Lower days = Higher Score)
        if ($r_days <= 15) {
            $r_score = 5;
        } elseif ($r_days <= 30) {
            $r_score = 4;
        } elseif ($r_days <= 60) {
            $r_score = 3;
        } elseif ($r_days <= 90) {
            $r_score = 2;
        } else {
            $r_score = 1;
        }

        // F Score (Frequency: Higher = Higher Score)
        if ($f_count >= 4) {
            $f_score = 5;
        } elseif ($f_count == 3) {
            $f_score = 4;
        } elseif ($f_count == 2) {
            $f_score = 3;
        } elseif ($f_count == 1) {
            $f_score = 2;
        } else {
            $f_score = 1;
        }

        // M Score (Monetary: Higher = Higher Score)
        if ($m_amount >= 2500000) {
            $m_score = 5;
        } elseif ($m_amount >= 1800000) {
            $m_score = 4;
        } elseif ($m_amount >= 1000000) {
            $m_score = 3;
        } elseif ($m_amount >= 400000) {
            $m_score = 2;
        } else {
            $m_score = 1;
        }

        $total_rfm = $r_score + $f_score + $m_score;

        // Segmentation Rule Matrix
        if ($r_score >= 4 && $f_score >= 4 && $m_score >= 4) {
            $segment = 'Champions';
        } elseif ($r_score >= 3 && $f_score >= 3 && $m_score >= 3) {
            $segment = 'Loyal Students';
        } elseif ($r_score >= 4 && $f_score >= 2) {
            $segment = 'Potential Loyalists';
        } elseif ($r_score >= 4 && $f_score <= 2) {
            $segment = 'Recent Customers';
        } elseif ($r_score == 3 && $f_score >= 1) {
            $segment = 'Promising';
        } elseif ($r_score == 2 && $f_score >= 2) {
            $segment = 'Need Attention';
        } elseif ($r_score <= 2 && $f_score >= 2 && $m_score >= 2) {
            $segment = 'At Risk';
        } else {
            $segment = 'Hibernating';
        }

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

    $pdo->commit();
    set_flash('success', 'Perhitungan Analisis RFM periode "' . htmlspecialchars($periode) . '" berhasil diperbarui!');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Gagal memproses analisis RFM: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/rfm/index.php?periode=' . urlencode($periode)));
exit;

