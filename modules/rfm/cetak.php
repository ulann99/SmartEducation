<?php
/**
 * Cetak Laporan Analisis RFM
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$periode = trim($_GET['periode'] ?? 'Semua Waktu');

$sql = "
    SELECT r.*, s.nama_siswa, s.nis, p.nama_paket
    FROM analisis_rfm r
    JOIN siswa s ON r.id_siswa = s.id_siswa
    LEFT JOIN paket_belajar p ON s.id_paket = p.id_paket
    WHERE r.periode_analisis = :periode
    ORDER BY r.total_rfm_score DESC, r.monetary_amount DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':periode' => $periode]);
$rows = $stmt->fetchAll();

$setting = get_setting();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisis RFM Siswa - Smart Education Parepare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #ffffff;
            color: #1e293b;
            font-size: 12px;
            padding: 30px;
        }
        .report-header {
            border-bottom: 3px double #0f172a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .table th {
            background-color: #f1f5f9 !important;
            font-size: 11px;
            text-transform: uppercase;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="javascript:window.history.back()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <button onclick="window.print()" class="btn btn-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Cetak Laporan (PDF)
        </button>
    </div>

    <div class="report-header text-center">
        <h3 class="fw-bold mb-1 text-uppercase"><?= htmlspecialchars($setting['nama_instansi'] ?? 'SMART EDUCATION PAREPARE') ?></h3>
        <p class="mb-0 text-muted small">
            <?= htmlspecialchars($setting['alamat'] ?? 'Jl. Bau Massepe No. 142, Kota Parepare') ?> | Telp: <?= htmlspecialchars($setting['telepon'] ?? '0812-4455-6677') ?>
        </p>
        <p class="mb-0 text-muted small">Email: <?= htmlspecialchars($setting['email'] ?? 'admin@smarteducation.sch.id') ?></p>
        <div class="mt-3">
            <h5 class="fw-bold mb-0 text-decoration-underline">LAPORAN HASIL ANALISIS RFM & SEGMENTASI SISWA</h5>
            <small class="text-muted">Periode Analisis: <strong><?= htmlspecialchars($periode) ?></strong></small>
        </div>
    </div>

    <table class="table table-bordered table-striped align-middle mb-4">
        <thead>
            <tr class="text-center">
                <th style="width: 40px;">No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Paket Belajar</th>
                <th>Recency (Hari / Skor)</th>
                <th>Frequency (Kali / Skor)</th>
                <th>Monetary (Rp / Skor)</th>
                <th>Total RFM</th>
                <th>Segmentasi Siswa</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="text-center py-4 text-muted">Belum ada data analisis pada periode ini.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center font-monospace"><?= htmlspecialchars($r['nis']) ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($r['nama_siswa']) ?></td>
                        <td><?= htmlspecialchars($r['nama_paket'] ?? '-') ?></td>
                        <td class="text-center"><?= $r['recency_days'] ?> hr (R: <?= $r['r_score'] ?>)</td>
                        <td class="text-center"><?= $r['frequency_count'] ?>x (F: <?= $r['f_score'] ?>)</td>
                        <td class="text-end"><?= format_rupiah($r['monetary_amount']) ?> (M: <?= $r['m_score'] ?>)</td>
                        <td class="text-center fw-bold font-monospace"><?= $r['total_rfm_score'] ?></td>
                        <td class="text-center fw-semibold"><?= htmlspecialchars($r['segmentasi']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="row pt-4">
        <div class="col-8"></div>
        <div class="col-4 text-center">
            <div class="small text-muted mb-1">Kota Parepare, <?= format_tanggal(date('Y-m-d')) ?></div>
            <div class="small fw-semibold">Kepala Bagian Akademik & Analisis,</div>
            <div style="height: 65px;"></div>
            <div class="fw-bold text-decoration-underline">Administrator Smart Edu</div>
            <div class="small text-muted">Smart Education Parepare</div>
        </div>
    </div>
</div>

</body>
</html>

