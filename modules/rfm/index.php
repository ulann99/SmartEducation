<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/**
 * ANALISIS RFM & SEGMENTASI SISWA
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

$current_page = 'rfm';
$page_title = 'Analisis RFM & Segmentasi Siswa';

$periode = trim($_GET['periode'] ?? 'Semua Waktu');

/* =====================================================
   PEMETAAN NAMA SEGMENTASI
===================================================== */

$segment_mapping = [
    'Champions'            => 'Pembayar Unggulan',
    'Loyal Students'       => 'Siswa Loyal',
    'Potential Loyalists'  => 'Berpotensi Loyal',
    'Promising'            => 'Berpotensi Berkembang',
    'Recent Customers'     => 'Siswa Baru Aktif',
    'At Risk'              => 'Berisiko',
    'Hibernating'          => 'Tidak Aktif',
    'Pembayar Rutin'       => 'Pembayar Rutin',
    'Perlu Pengingat'      => 'Perlu Pengingat',
    'Berisiko'             => 'Berisiko',
];

$segment_colors = [
    'Pembayar Unggulan'       => '#198754',
    'Siswa Loyal'             => '#20a779',
    'Berpotensi Loyal'        => '#0d9bb5',
    'Berpotensi Berkembang'   => '#e5a000',
    'Siswa Baru Aktif'        => '#6c9bd2',
    'Berisiko'                => '#e65b5b',
    'Tidak Aktif'             => '#8b9299',
    'Pembayar Rutin'          => '#198754',
    'Perlu Pengingat'         => '#e5a000',
];

/* =====================================================
   MENGAMBIL DATA RFM DAN TAGIHAN
===================================================== */

$sql = "
    SELECT
        r.*,
        s.nama_lengkap AS nama_siswa,
        paket.nama_paket,
        COALESCE(tagihan.jumlah_belum_lunas, 0) AS jumlah_belum_lunas,
        COALESCE(tagihan.total_belum_lunas, 0) AS total_belum_lunas

    FROM analisis_rfm r

    INNER JOIN siswa s
        ON r.id_siswa = s.id_siswa

    LEFT JOIN (
        SELECT
            pd.id_siswa,
            MAX(pd.id_pendaftaran) AS id_pendaftaran_terakhir
        FROM pendaftaran pd
        GROUP BY pd.id_siswa
    ) pendaftaran_terakhir
        ON pendaftaran_terakhir.id_siswa = s.id_siswa

    LEFT JOIN pendaftaran pd
        ON pd.id_pendaftaran = pendaftaran_terakhir.id_pendaftaran_terakhir

    LEFT JOIN paket_belajar paket
        ON paket.id_paket = pd.id_paket

    LEFT JOIN (
        SELECT
            pd2.id_siswa,
            COUNT(pb2.id_pembayaran) AS jumlah_belum_lunas,
            SUM(pb2.jumlah_bayar) AS total_belum_lunas

        FROM pendaftaran pd2

        INNER JOIN pembayaran pb2
            ON pb2.id_pendaftaran = pd2.id_pendaftaran

        WHERE pb2.status_pembayaran = 'Menunggu'

        GROUP BY pd2.id_siswa
    ) tagihan
        ON tagihan.id_siswa = r.id_siswa

    WHERE r.periode_analisis = :periode

    ORDER BY
        r.total_rfm_score DESC,
        r.monetary_amount DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':periode' => $periode
]);

$rfm_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   STATISTIK DAN GRAFIK
===================================================== */

$total_siswa = count($rfm_data);

$sum_r = 0;
$sum_f = 0;
$sum_m = 0;

$segment_counts = [];

foreach ($rfm_data as $row) {

    $nama_segment = $segment_mapping[$row['segmentasi']]
        ?? $row['segmentasi'];

    $segment_counts[$nama_segment] =
        ($segment_counts[$nama_segment] ?? 0) + 1;

    $sum_r += (int) $row['r_score'];
    $sum_f += (int) $row['f_score'];
    $sum_m += (int) $row['m_score'];
}

$avg_r = $total_siswa > 0
    ? round($sum_r / $total_siswa, 1) : 0;

$avg_f = $total_siswa > 0
    ? round($sum_f / $total_siswa, 1) : 0;

$avg_m = $total_siswa > 0
    ? round($sum_m / $total_siswa, 1) : 0;

$pembayar_unggulan =
    ($segment_counts['Pembayar Unggulan'] ?? 0) +
    ($segment_counts['Siswa Loyal'] ?? 0) +
    ($segment_counts['Pembayar Rutin'] ?? 0);

$perlu_perhatian =
    ($segment_counts['Berpotensi Loyal'] ?? 0) +
    ($segment_counts['Berpotensi Berkembang'] ?? 0) +
    ($segment_counts['Siswa Baru Aktif'] ?? 0) +
    ($segment_counts['Perlu Pengingat'] ?? 0);

$siswa_berisiko =
    ($segment_counts['Berisiko'] ?? 0) +
    ($segment_counts['Tidak Aktif'] ?? 0);

$tanggal_analisis = !empty($rfm_data)
    ? format_tanggal($rfm_data[0]['tanggal_analisis'])
    : '-';

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';

/* =====================================================
   DATA PERIODE ANALISIS
===================================================== */

$daftar_periode = [
    'Semua Waktu',
    '3 Bulan Terakhir',
    '6 Bulan Terakhir',
    '1 Tahun Terakhir'
];
?>

<style>
.rfm-page {
    width: 100%;
    max-width: 100%;
    padding: 24px;
    margin: 0;
    box-sizing: border-box;
    color: #173d32;
}

.rfm-page * {
    box-sizing: border-box;
}

.rfm-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    width: 100%;
    margin-bottom: 24px;
    text-align: left;
}

.rfm-page-header h1 {
    margin: 0 0 6px;
    color: #173d32;
    font-size: 25px;
    font-weight: 750;
}

.rfm-page-header p {
    margin: 0;
    color: #718078;
    font-size: 13px;
}

.rfm-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.rfm-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 10px 17px;
    border: 1px solid transparent;
    border-radius: 9px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.4;
    text-decoration: none !important;
    cursor: pointer;
    transition: .2s ease;
}

.rfm-btn-primary {
    background: #287f73;
    color: #ffffff !important;
    border-color: #287f73;
    box-shadow: 0 3px 8px rgba(40, 127, 115, .15);
}

.rfm-btn-primary:hover {
    background: #206d63;
    border-color: #206d63;
    color: #ffffff !important;
    transform: translateY(-1px);
}

.rfm-btn-secondary {
    background: #ffffff;
    color: #287f73 !important;
    border-color: #287f73;
}

.rfm-btn-secondary:hover {
    background: #287f73;
    color: #ffffff !important;
}

.rfm-btn-small {
    min-height: 36px;
    padding: 7px 13px;
    font-size: 12px;
}

.rfm-filter-card,
.rfm-stat-card,
.rfm-card {
    background: #ffffff;
    border: 1px solid #e8efec;
    border-radius: 13px;
    box-shadow: 0 2px 8px rgba(20, 60, 45, .045);
}

.rfm-filter-card {
    padding: 17px 19px;
    margin-bottom: 20px;
}

.rfm-stat-card {
    height: 100%;
    padding: 18px;
}

.rfm-stat-label {
    color: #718078;
    font-size: 12px;
    font-weight: 600;
}

.rfm-stat-value {
    margin: 7px 0;
    color: #173d32;
    font-size: 27px;
    font-weight: 750;
}

.rfm-stat-icon {
    display: flex;
    width: 43px;
    height: 43px;
    align-items: center;
    justify-content: center;
    border-radius: 11px;
    background: #e8fdff;
    color: #287f73;
    font-size: 19px;
}

.rfm-card {
    height: 100%;
    overflow: hidden;
}

.rfm-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
    padding: 15px 18px;
    background: #ffffff;
    border-bottom: 1px solid #edf2ef;
}

.rfm-card-header h5 {
    margin: 0;
    color: #173d32;
    font-size: 15px;
    font-weight: 700;
}

.rfm-card-body {
    padding: 18px;
}

.rfm-filter-card label {
    display: block;
    margin-bottom: 7px;
    color: #34483f;
    font-size: 12px;
    font-weight: 650;
}

.rfm-filter-card .form-select {
    min-height: 42px;
    border: 1px solid #d8e8e2;
    border-radius: 8px;
    font-size: 13px;
}

.rfm-filter-card .form-select:focus {
    border-color: #287f73;
    box-shadow: 0 0 0 3px rgba(40, 127, 115, .1);
}

.rfm-table {
    width: 100%;
    min-width: 1180px;
    margin: 0;
}

.rfm-table thead th {
    padding: 14px 12px;
    background: #e8f8f5;
    color: #173d32;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    border-bottom: 1px solid #e1eeea;
}

.rfm-table tbody td {
    padding: 15px 12px;
    color: #34483f;
    font-size: 12px;
    vertical-align: middle;
    border-bottom: 1px solid #edf2ef;
}

.rfm-table tbody tr:last-child td {
    border-bottom: 0;
}

.rfm-table tbody tr:hover {
    background: #f8fcfa;
}

.rfm-table .student-name {
    color: #173d32;
    font-weight: 700;
}

.rfm-table .student-nis {
    margin-top: 4px;
    color: #829087;
    font-size: 10px;
}

.rfm-score {
    display: inline-flex;
    min-width: 39px;
    min-height: 35px;
    align-items: center;
    justify-content: center;
    padding: 5px 9px;
    border-radius: 8px;
    background: #98ede1;
    color: #174d48;
    font-size: 15px;
    font-weight: 750;
}

.rfm-score-detail {
    margin-top: 5px;
    color: #829087;
    font-size: 10px;
    text-align: center;
}

.rfm-segment-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 11px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 650;
    white-space: nowrap;
}

.rfm-empty {
    padding: 55px 20px !important;
    text-align: center;
}

.rfm-empty-icon {
    margin-bottom: 12px;
    color: #7b9d91;
    font-size: 40px;
}

.rfm-empty h6 {
    color: #173d32;
    font-weight: 700;
}

.rfm-empty p {
    color: #84938b;
    font-size: 12px;
}

.rfm-average-box {
    padding: 14px;
    margin-top: 15px;
    border-radius: 9px;
    background: #f3faf7;
}

.rfm-average-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 9px;
    font-size: 12px;
}

.rfm-average-row:last-child {
    margin-bottom: 0;
}

#modalPanduanRFM .modal-content,
#modalKonfirmasiPengingat .modal-content {
    overflow: hidden;
    border: 0;
    border-radius: 14px;
}

#modalPanduanRFM .modal-header,
#modalKonfirmasiPengingat .modal-header {
    background: #e8fdff;
    border-bottom: 1px solid #e3efeb;
}

#modalPanduanRFM .modal-title,
#modalKonfirmasiPengingat .modal-title {
    color: #173d32;
    font-weight: 700;
}

#modalPanduanRFM .modal-body,
#modalKonfirmasiPengingat .modal-body {
    color: #34483f;
    font-size: 13px;
    line-height: 1.7;
}

#modalPanduanRFM .list-group-item {
    padding: 13px 15px;
    border-color: #e8efec;
}

#modalKonfirmasiPengingat .modal-footer {
    background: #fafcfb;
    border-top: 1px solid #edf2ef;
}

@media (max-width: 768px) {
    .rfm-page {
        padding: 15px;
    }

    .rfm-page-header {
        align-items: flex-start;
    }

    .rfm-page-header h1 {
        font-size: 21px;
    }

    .rfm-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .rfm-actions .rfm-btn {
        flex: 1;
    }
}
</style>

<?php
$chart_labels = array_keys($segment_counts);
$chart_values = array_values($segment_counts);
?>

<div class="rfm-page">

    <!-- HEADER HALAMAN -->
    <div class="rfm-page-header">

        <div>
            <h1>
                <i class="bi bi-bar-chart-line me-2"></i>
                Analisis RFM &amp; Segmentasi Siswa
            </h1>

            <p>
                Analisis pola pembayaran siswa berdasarkan Recency,
                Frequency, dan Monetary.
            </p>
        </div>

        <div class="rfm-actions">

            <button type="button"
                    class="rfm-btn rfm-btn-secondary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalPanduanRFM">

                <i class="bi bi-info-circle"></i>
                Panduan RFM

            </button>

            <a href="<?= base_url('modules/rfm/cetak.php?periode=' . urlencode($periode)) ?>"
               target="_blank"
               class="rfm-btn rfm-btn-primary">

                <i class="bi bi-printer"></i>
                Cetak Laporan

            </a>

        </div>

    </div>

    <!-- FILTER PERIODE -->
    <div class="rfm-filter-card">

        <form method="GET"
              action="<?= base_url('modules/rfm/hitung.php') ?>">

            <div class="row align-items-end g-3">

                <div class="col-md-5">

                    <label for="periode">Periode Analisis</label>

                    <select name="periode"
                            id="periode"
                            class="form-select">

                        <?php foreach ($daftar_periode as $item_periode): ?>

                            <option value="<?= htmlspecialchars($item_periode) ?>"
                                <?= $periode === $item_periode ? 'selected' : '' ?>>

                                <?= htmlspecialchars($item_periode) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-3">

                    <button type="submit"
                            class="rfm-btn rfm-btn-primary">

                        <i class="bi bi-funnel"></i>
                        Terapkan Filter

                    </button>

                </div>

                <div class="col-md-4 text-md-end">

                    <small class="text-muted">
                        Tanggal Analisis:
                        <strong><?= htmlspecialchars($tanggal_analisis) ?></strong>
                    </small>

                </div>

            </div>

        </form>

    </div>

    <!-- KARTU STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="rfm-stat-card">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <div class="rfm-stat-label">
                            Total Siswa Dianalisis
                        </div>

                        <div class="rfm-stat-value">
                            <?= number_format($total_siswa) ?>
                        </div>

                        <small class="text-muted">
                            Data hasil analisis RFM
                        </small>
                    </div>

                    <div class="rfm-stat-icon">
                        <i class="bi bi-people"></i>
                    </div>

                </div>

            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="rfm-stat-card">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <div class="rfm-stat-label">
                            Pembayar Unggulan
                        </div>

                        <div class="rfm-stat-value">
                            <?= number_format($pembayar_unggulan) ?>
                        </div>

                        <small class="text-success">
                            Siswa dengan pembayaran baik
                        </small>
                    </div>

                    <div class="rfm-stat-icon">
                        <i class="bi bi-award"></i>
                    </div>

                </div>

            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="rfm-stat-card">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <div class="rfm-stat-label">
                            Perlu Perhatian
                        </div>

                        <div class="rfm-stat-value">
                            <?= number_format($perlu_perhatian) ?>
                        </div>

                        <small class="text-warning">
                            Perlu pemantauan pembayaran
                        </small>
                    </div>

                    <div class="rfm-stat-icon">
                        <i class="bi bi-bell"></i>
                    </div>

                </div>

            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="rfm-stat-card">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <div class="rfm-stat-label">
                            Siswa Berisiko
                        </div>

                        <div class="rfm-stat-value">
                            <?= number_format($siswa_berisiko) ?>
                        </div>

                        <small class="text-danger">
                            Memerlukan perhatian khusus
                        </small>
                    </div>

                    <div class="rfm-stat-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                </div>

            </div>
        </div>

    </div>

    <!-- GRAFIK SEGMENTASI -->
    <div class="row g-3 mb-4">

        <div class="col-lg-7">
            <div class="rfm-card">

                <div class="rfm-card-header">
                    <h5>
                        <i class="bi bi-pie-chart me-2"></i>
                        Distribusi Segmentasi Siswa
                    </h5>
                </div>

                <div class="rfm-card-body">

                    <?php if ($total_siswa > 0): ?>

                        <?php foreach ($segment_counts as $nama_segment => $jumlah_segment): ?>

                            <?php
                            $warna_segment = $segment_colors[$nama_segment] ?? '#8b9299';
                            $persentase = ($jumlah_segment / $total_siswa) * 100;
                            ?>

                            <div class="mb-3">

                                <div class="d-flex justify-content-between mb-2">

                                    <span class="fw-semibold small">
                                        <?= htmlspecialchars($nama_segment) ?>
                                    </span>

                                    <span class="small text-muted">
                                        <?= $jumlah_segment ?> siswa
                                        (<?= number_format($persentase, 1) ?>%)
                                    </span>

                                </div>

                                <div class="progress"
                                     style="height: 11px; border-radius: 20px; background: #edf3f1;">

                                    <div class="progress-bar"
                                         role="progressbar"
                                         style="width: <?= $persentase ?>%; background-color: <?= htmlspecialchars($warna_segment) ?>; border-radius: 20px;">
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="text-center text-muted py-5">
                            Belum terdapat data segmentasi siswa.
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- RATA-RATA RFM -->
        <div class="col-lg-5">
            <div class="rfm-card">

                <div class="rfm-card-header">
                    <h5>
                        <i class="bi bi-graph-up me-2"></i>
                        Rata-rata Skor RFM
                    </h5>
                </div>

                <div class="rfm-card-body">

                    <div class="rfm-average-box">

                        <div class="rfm-average-row">
                            <strong>Recency (R)</strong>
                            <strong><?= number_format($avg_r, 1) ?></strong>
                        </div>

                        <div class="progress mb-3" style="height: 9px;">
                            <div class="progress-bar bg-success"
                                 style="width: <?= ($avg_r / 3) * 100 ?>%">
                            </div>
                        </div>

                        <div class="rfm-average-row">
                            <strong>Frequency (F)</strong>
                            <strong><?= number_format($avg_f, 1) ?></strong>
                        </div>

                        <div class="progress mb-3" style="height: 9px;">
                            <div class="progress-bar bg-info"
                                 style="width: <?= ($avg_f / 3) * 100 ?>%">
                            </div>
                        </div>

                        <div class="rfm-average-row">
                            <strong>Monetary (M)</strong>
                            <strong><?= number_format($avg_m, 1) ?></strong>
                        </div>

                        <div class="progress" style="height: 9px;">
                            <div class="progress-bar bg-warning"
                                 style="width: <?= ($avg_m / 3) * 100 ?>%">
                            </div>
                        </div>

                    </div>

                    <small class="text-muted d-block mt-3">
                        Rata-rata skor setiap komponen RFM pada periode analisis terpilih.
                    </small>

                </div>
            </div>
        </div>

    </div>

    <!-- TABEL HASIL ANALISIS -->
    <div class="rfm-card">

        <div class="rfm-card-header">

            <div>
                <h5>
                    <i class="bi bi-table me-2"></i>
                    Hasil Analisis RFM Siswa
                </h5>

                <small class="text-muted">
                    Daftar hasil perhitungan dan segmentasi pembayaran siswa.
                </small>
            </div>

            <span class="badge text-bg-light">
                <?= number_format($total_siswa) ?> Siswa
            </span>

        </div>

        <div class="rfm-card-body">

            <div class="table-responsive">

                <table class="table rfm-table align-middle">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Siswa</th>
                            <th>Paket Belajar</th>
                            <th>Recency</th>
                            <th>Frequency</th>
                            <th>Monetary</th>
                            <th>Total Skor</th>
                            <th>Segmentasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($rfm_data)): ?>

                        <tr>
                            <td colspan="9" class="rfm-empty">

                                <div class="rfm-empty-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>

                                <h6>Belum Ada Data Analisis RFM</h6>

                                <p>
                                    Silakan lakukan perhitungan RFM terlebih dahulu.
                                </p>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php $no = 1; ?>

                        <?php foreach ($rfm_data as $row): ?>

                            <?php
                            $nama_segment = $segment_mapping[$row['segmentasi']]
                                ?? $row['segmentasi'];

                            $warna_segment = $segment_colors[$nama_segment]
                                ?? '#8b9299';

                            $jumlah_belum_lunas = (int) $row['jumlah_belum_lunas'];
                            $total_belum_lunas = (float) $row['total_belum_lunas'];
                            ?>

                            <tr>

                                <td><?= $no++ ?></td>

                                <td>
                                    <div class="student-name">
                                        <?= htmlspecialchars($row['nama_siswa']) ?>
                                    </div>

                                    <div class="student-nis">
                                        ID Siswa: #<?= (int) $row['id_siswa'] ?>
                                    </div>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['nama_paket'] ?? '-') ?>
                                </td>

                                <td class="text-center">
                                    <span class="rfm-score">
                                        <?= (int) $row['r_score'] ?>
                                    </span>

                                    <div class="rfm-score-detail">
                                        <?= (int) $row['recency_days'] ?> hari
                                    </div>
                                </td>

                                <td class="text-center">
                                    <span class="rfm-score">
                                        <?= (int) $row['f_score'] ?>
                                    </span>

                                    <div class="rfm-score-detail">
                                        <?= (int) $row['frequency_count'] ?> transaksi
                                    </div>
                                </td>

                                <td class="text-center">
                                    <span class="rfm-score">
                                        <?= (int) $row['m_score'] ?>
                                    </span>

                                    <div class="rfm-score-detail">
                                        <?= format_rupiah($row['monetary_amount']) ?>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <span class="rfm-score">
                                        <?= (int) $row['total_rfm_score'] ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="rfm-segment-badge"
                                          style="background: <?= htmlspecialchars($warna_segment) ?>20; color: <?= htmlspecialchars($warna_segment) ?>;">

                                        <?= htmlspecialchars($nama_segment) ?>

                                    </span>
                                </td>

                                <td>
                                    <div class="d-flex flex-column align-items-center gap-2">

                                        <a href="<?= base_url(
                                            'modules/pembayaran/index.php?tab=riwayat&search='
                                            . urlencode($row['nama_siswa'])
                                            . '&from=rfm&period='
                                            . urlencode($periode)
                                        ) ?>"
                                           class="rfm-btn rfm-btn-secondary rfm-btn-small">

                                            <i class="bi bi-receipt"></i>
                                            Detail Pembayaran

                                        </a>

                                        <?php if ($jumlah_belum_lunas > 0): ?>

                                            <!-- TOMBOL MEMBUKA MODAL PENGINGAT -->
                                            <button type="button"
                                                    class="rfm-btn rfm-btn-primary rfm-btn-small"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalKonfirmasiPengingat"
                                                    data-id-siswa="<?= (int) $row['id_siswa'] ?>"
                                                    data-nama-siswa="<?= htmlspecialchars($row['nama_siswa'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-jumlah-tagihan="<?= $jumlah_belum_lunas ?>"
                                                    data-total-tagihan="<?= htmlspecialchars(format_rupiah($total_belum_lunas), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-periode="<?= htmlspecialchars($periode, ENT_QUOTES, 'UTF-8') ?>">

                                                <i class="bi bi-bell"></i>
                                                Kirim Pengingat

                                            </button>

                                            <small class="text-warning">
                                                <?= $jumlah_belum_lunas ?> tagihan ·
                                                <?= format_rupiah($total_belum_lunas) ?>
                                            </small>

                                        <?php endif; ?>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>


<!-- =========================================
     MODAL PANDUAN RFM
========================================= -->

<div class="modal fade"
     id="modalPanduanRFM"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2"></i>
                    Panduan Analisis RFM
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <p>
                    Analisis RFM digunakan untuk mengetahui pola pembayaran
                    siswa berdasarkan tiga komponen.
                </p>

                <div class="list-group">

                    <div class="list-group-item">
                        <strong>Recency (R)</strong>
                        <p class="mb-0">
                            Mengukur jarak waktu sejak pembayaran terakhir siswa.
                        </p>
                    </div>

                    <div class="list-group-item">
                        <strong>Frequency (F)</strong>
                        <p class="mb-0">
                            Mengukur jumlah transaksi pembayaran siswa.
                        </p>
                    </div>

                    <div class="list-group-item">
                        <strong>Monetary (M)</strong>
                        <p class="mb-0">
                            Mengukur total nominal pembayaran siswa.
                        </p>
                    </div>

                    <div class="list-group-item">
                        <strong>Segmentasi Siswa</strong>
                        <p class="mb-0">
                            Hasil pengelompokan siswa berdasarkan skor RFM
                            untuk membantu Admin memahami pola pembayaran.
                        </p>
                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="rfm-btn rfm-btn-primary"
                        data-bs-dismiss="modal">

                    Tutup

                </button>

            </div>

        </div>

    </div>

</div>


<!-- =========================================
     MODAL KONFIRMASI PENGINGAT PEMBAYARAN
========================================= -->

<div class="modal fade"
     id="modalKonfirmasiPengingat"
     tabindex="-1"
     aria-labelledby="judulModalPengingat"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content"
             style="border: none; border-radius: 16px; overflow: hidden;">

            <!-- HEADER MODAL -->

            <div class="modal-header"
                 style="background: #e8fdff; border-bottom: 1px solid #dcefeb;">

                <h5 class="modal-title fw-bold"
                    id="judulModalPengingat"
                    style="color: #173d32;">

                    <i class="bi bi-bell me-2"
                       style="color: #287f73;"></i>

                    Konfirmasi Pengingat Pembayaran

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Tutup">
                </button>

            </div>

            <!-- BODY MODAL -->

            <div class="modal-body p-4">

                <div class="text-center mb-3">

                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                         style="width: 65px; height: 65px; background: #e8fdff; border-radius: 50%;">

                        <i class="bi bi-bell-fill"
                           style="font-size: 27px; color: #287f73;">
                        </i>

                    </div>

                    <h5 class="fw-bold mb-2"
                        style="color: #173d32;">

                        Kirim Pengingat Tagihan?

                    </h5>

                    <p class="text-muted mb-0"
                       style="font-size: 13px;">

                        Apakah kamu yakin ingin mengirim pengingat pembayaran kepada:

                    </p>

                    <h6 class="fw-bold mt-2 mb-0"
                        id="namaSiswaPengingat"
                        style="color: #287f73;">

                        Nama Siswa

                    </h6>

                </div>

                <!-- INFORMASI TAGIHAN -->

                <div class="p-3"
                     style="background: #f3faf7; border: 1px solid #e2efea; border-radius: 10px;">

                    <div class="d-flex justify-content-between mb-2">

                        <span class="text-muted small">
                            Jumlah Tagihan
                        </span>

                        <strong id="jumlahTagihanPengingat">
                            0 tagihan
                        </strong>

                    </div>

                    <div class="d-flex justify-content-between">

                        <span class="text-muted small">
                            Total Tagihan
                        </span>

                        <strong id="totalTagihanPengingat"
                                style="color: #287f73;">

                            Rp0

                        </strong>

                    </div>

                </div>

                <div class="mt-3 text-muted"
                     style="font-size: 11px;">

                    <i class="bi bi-info-circle me-1"></i>

                    Pengingat akan disimpan sebagai notifikasi internal
                    pada akun Siswa/Orang Tua.

                </div>

            </div>

            <!-- FOOTER MODAL -->

            <div class="modal-footer"
                 style="background: #fafcfb; border-top: 1px solid #edf2ef;">

                <button type="button"
                        class="btn btn-light border"
                        data-bs-dismiss="modal">

                    Batal

                </button>

                <form action="<?= base_url('modules/rfm/kirimpengingat.php') ?>"
                      method="POST"
                      id="formKonfirmasiPengingat">

                    <input type="hidden"
                           name="id_siswa"
                           id="idSiswaPengingat">

                    <input type="hidden"
                           name="periode"
                           id="periodePengingat">

                    <button type="submit"
                            class="rfm-btn rfm-btn-primary">

                        <i class="bi bi-bell me-1"></i>

                        Ya, Kirim Pengingat

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


<!-- =========================================
     JAVASCRIPT MODAL PENGINGAT
========================================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const modalPengingat = document.getElementById(
        'modalKonfirmasiPengingat'
    );

    if (!modalPengingat) {
        return;
    }

    modalPengingat.addEventListener('show.bs.modal', function (event) {

        const tombol = event.relatedTarget;

        if (!tombol) {
            return;
        }

        const idSiswa = tombol.getAttribute('data-id-siswa');
        const namaSiswa = tombol.getAttribute('data-nama-siswa');
        const jumlahTagihan = tombol.getAttribute('data-jumlah-tagihan');
        const totalTagihan = tombol.getAttribute('data-total-tagihan');
        const periode = tombol.getAttribute('data-periode');

        document.getElementById('idSiswaPengingat').value = idSiswa;

        document.getElementById('periodePengingat').value = periode;

        document.getElementById('namaSiswaPengingat').textContent =
            namaSiswa;

        document.getElementById('jumlahTagihanPengingat').textContent =
            jumlahTagihan + ' tagihan';

        document.getElementById('totalTagihanPengingat').textContent =
            totalTagihan;

    });

});

</script>

<?php require_once BASEPATH . '/includes/footer.php'; ?>