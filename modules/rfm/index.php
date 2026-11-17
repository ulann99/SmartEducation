<?php
/**
 * Analisis RFM & Segmentasi Siswa
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'rfm';
$page_title = 'Analisis RFM & Segmentasi Siswa';

$periode = trim($_GET['periode'] ?? 'Semua Waktu');

// Fetch RFM analysis results for this period
$sql = "
    SELECT r.*, s.nama_siswa, s.nis, s.no_telepon, p.nama_paket
    FROM analisis_rfm r
    JOIN siswa s ON r.id_siswa = s.id_siswa
    LEFT JOIN paket_belajar p ON s.id_paket = p.id_paket
    WHERE r.periode_analisis = :periode
    ORDER BY r.total_rfm_score DESC, r.monetary_amount DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':periode' => $periode]);
$rfm_data = $stmt->fetchAll();

// If table is empty for this period, automatically trigger calculation
if (empty($rfm_data)) {
    header('Location: ' . base_url('modules/rfm/hitung.php?periode=' . urlencode($periode)));
    exit;
}

// Segmentation Counts
$segment_counts = [
    'Champions' => 0,
    'Loyal Students' => 0,
    'Potential Loyalists' => 0,
    'Recent Customers' => 0,
    'Promising' => 0,
    'Need Attention' => 0,
    'At Risk' => 0,
    'Hibernating' => 0
];

$sum_r = 0; $sum_f = 0; $sum_m = 0;
foreach ($rfm_data as $row) {
    if (isset($segment_counts[$row['segmentasi']])) {
        $segment_counts[$row['segmentasi']]++;
    }
    $sum_r += $row['r_score'];
    $sum_f += $row['f_score'];
    $sum_m += $row['m_score'];
}

$total_siswa = count($rfm_data);
$avg_r = $total_siswa > 0 ? round($sum_r / $total_siswa, 1) : 0;
$avg_f = $total_siswa > 0 ? round($sum_f / $total_siswa, 1) : 0;
$avg_m = $total_siswa > 0 ? round($sum_m / $total_siswa, 1) : 0;

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="h3 fw-bold">Analisis RFM & Segmentasi Siswa</h1>
        <p class="text-muted">Segmentasi perilaku siswa berbasis Recency (Waktu), Frequency (Frekuensi), dan Monetary (Nominal Pembayaran).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('modules/rfm/hitung.php?periode=' . urlencode($periode)) ?>" class="btn btn-mint">
            <i class="bi bi-arrow-repeat me-1"></i> Hitung Ulang Analisis RFM
        </a>
        <a href="<?= base_url('modules/rfm/cetak.php?periode=' . urlencode($periode)) ?>" target="_blank" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Cetak Analisis
        </a>
    </div>
</div>

<!-- Period Filter Card -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted fw-semibold">Pilih Periode Analisis:</span>
                    <select name="periode" class="form-select" onchange="this.form.submit()">
                        <option value="Semua Waktu" <?= ($periode === 'Semua Waktu') ? 'selected' : '' ?>>Semua Waktu (All Time)</option>
                        <option value="3 Bulan Terakhir" <?= ($periode === '3 Bulan Terakhir') ? 'selected' : '' ?>>3 Bulan Terakhir</option>
                        <option value="6 Bulan Terakhir" <?= ($periode === '6 Bulan Terakhir') ? 'selected' : '' ?>>6 Bulan Terakhir</option>
                        <option value="1 Tahun Terakhir" <?= ($periode === '1 Tahun Terakhir') ? 'selected' : '' ?>>1 Tahun Terakhir</option>
                    </select>
                </div>
            </div>
            <div class="col-md-7 text-md-end text-muted small">
                Terakhir dihitung: <span class="fw-semibold text-dark"><?= !empty($rfm_data[0]['tanggal_analisis']) ? format_tanggal($rfm_data[0]['tanggal_analisis']) : '-' ?></span>
            </div>
        </form>
    </div>
</div>

<!-- RFM KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Siswa Dianalisis</div>
                    <div class="stat-value mt-1"><?= $total_siswa ?></div>
                    <small class="text-mint fw-medium"><i class="bi bi-people me-1"></i>Data Siswa Aktif</small>
                </div>
                <div class="stat-icon mint"><i class="bi bi-person-check-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Champions</div>
                    <div class="stat-value mt-1 text-emerald"><?= $segment_counts['Champions'] ?></div>
                    <small class="text-emerald fw-medium"><i class="bi bi-trophy-fill me-1"></i>Siswa Paling Loyal</small>
                </div>
                <div class="stat-icon emerald"><i class="bi bi-star-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Loyal & Potential</div>
                    <div class="stat-value mt-1 text-primary"><?= $segment_counts['Loyal Students'] + $segment_counts['Potential Loyalists'] ?></div>
                    <small class="text-primary fw-medium"><i class="bi bi-graph-up-arrow me-1"></i>Pelanggan Potensial</small>
                </div>
                <div class="stat-icon blue"><i class="bi bi-award-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">At Risk / Hibernating</div>
                    <div class="stat-value mt-1 text-danger"><?= $segment_counts['At Risk'] + $segment_counts['Hibernating'] ?></div>
                    <small class="text-danger fw-medium"><i class="bi bi-exclamation-triangle me-1"></i>Perlu Pendekatan</small>
                </div>
                <div class="stat-icon amber"><i class="bi bi-bell-fill"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-3 mb-4">
    <!-- Doughnut Segment Chart -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart-fill text-mint"></i> Komposisi Segmentasi Siswa
                </h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div style="position: relative; width: 100%; max-width: 320px;">
                    <canvas id="rfmDonutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Average RFM Radar / Bar Chart -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-fill text-mint"></i> Rata-rata Skor RFM (Skala 1 - 5)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="rfmBarChart" height="200"></canvas>
                <div class="mt-3 p-3 bg-light rounded-3 small">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Recency (R) Avg:</span>
                        <strong class="text-mint-dark"><?= $avg_r ?> / 5.0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Frequency (F) Avg:</span>
                        <strong class="text-primary"><?= $avg_f ?> / 5.0</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Monetary (M) Avg:</span>
                        <strong class="text-emerald"><?= $avg_m ?> / 5.0</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RFM Analysis Data Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold text-mint-deep">
            <i class="bi bi-table me-2 text-mint"></i>Tabel Hasil Penilaian & Segmentasi Siswa
        </h5>
        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalPanduanRFM">
            <i class="bi bi-info-circle me-1"></i> Panduan Aturan RFM
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nama Siswa & NIS</th>
                    <th>Paket Belajar</th>
                    <th>Recency (R)</th>
                    <th>Frequency (F)</th>
                    <th>Monetary (M)</th>
                    <th class="text-center">Skor RFM</th>
                    <th>Kategori Segmentasi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rfm_data)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada data analisis.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($rfm_data as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama_siswa']) ?></strong>
                                <div class="small text-muted">NIS: <?= htmlspecialchars($row['nis']) ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['nama_paket'] ?? '-') ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= $row['recency_days'] ?> hari lalu</div>
                                <span class="badge bg-secondary-subtle text-secondary small">Skor: <?= $row['r_score'] ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= $row['frequency_count'] ?> kali bayar</div>
                                <span class="badge bg-secondary-subtle text-secondary small">Skor: <?= $row['f_score'] ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold text-mint-deep"><?= format_rupiah($row['monetary_amount']) ?></div>
                                <span class="badge bg-secondary-subtle text-secondary small">Skor: <?= $row['m_score'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-mint text-dark fs-6 px-3 py-1 font-monospace fw-bold">
                                    <?= $row['total_rfm_score'] ?>
                                </span>
                                <div class="small text-muted font-monospace">(<?= $row['r_score'] ?><?= $row['f_score'] ?><?= $row['m_score'] ?>)</div>
                            </td>
                            <td>
                                <?php
                                $seg = $row['segmentasi'];
                                if ($seg === 'Champions') {
                                    echo '<span class="badge badge-mint fs-6 px-3 py-1"><i class="bi bi-star-fill me-1"></i>Champions</span>';
                                } elseif ($seg === 'Loyal Students') {
                                    echo '<span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1"><i class="bi bi-award me-1"></i>Loyal Students</span>';
                                } elseif ($seg === 'Potential Loyalists') {
                                    echo '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fs-6 px-3 py-1"><i class="bi bi-arrow-up-right-circle me-1"></i>Potential Loyalist</span>';
                                } elseif ($seg === 'Recent Customers') {
                                    echo '<span class="badge bg-success-subtle text-success border border-success-subtle fs-6 px-3 py-1"><i class="bi bi-person-plus me-1"></i>Recent Customer</span>';
                                } elseif ($seg === 'Promising') {
                                    echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6 px-3 py-1"><i class="bi bi-lightbulb me-1"></i>Promising</span>';
                                } elseif ($seg === 'Need Attention') {
                                    echo '<span class="badge badge-warning-soft fs-6 px-3 py-1"><i class="bi bi-exclamation-circle me-1"></i>Need Attention</span>';
                                } elseif ($seg === 'At Risk') {
                                    echo '<span class="badge badge-danger-soft fs-6 px-3 py-1"><i class="bi bi-exclamation-triangle me-1"></i>At Risk</span>';
                                } else {
                                    echo '<span class="badge bg-secondary fs-6 px-3 py-1"><i class="bi bi-moon-stars me-1"></i>Hibernating</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Panduan Aturan RFM -->
<div class="modal fade" id="modalPanduanRFM" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>Panduan Matriks Segmentasi RFM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body small">
                <p>Metode <strong>RFM (Recency, Frequency, Monetary)</strong> mengelompokkan siswa bimbingan belajar berdasarkan 3 dimensi kunci:</p>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <h6 class="fw-bold text-mint-dark"><i class="bi bi-calendar-event me-1"></i> Recency (R)</h6>
                            <p class="mb-0 text-muted">Jarak hari dari tanggal pembayaran terakhir siswa. Semakin baru, semakin tinggi skornya (1-5).</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <h6 class="fw-bold text-primary"><i class="bi bi-repeat me-1"></i> Frequency (F)</h6>
                            <p class="mb-0 text-muted">Frekuensi pembayaran lunas dalam periode analisis. Menunjukkan keaktifan rutin siswa.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded border h-100">
                            <h6 class="fw-bold text-success"><i class="bi bi-cash-coin me-1"></i> Monetary (M)</h6>
                            <p class="mb-0 text-muted">Total nilai nominal transaksi lunas yang telah dibayarkan oleh siswa.</p>
                        </div>
                    </div>
                </div>
                <h6 class="fw-bold mb-2">Strategi Tindakan Berdasarkan Segmentasi:</h6>
                <ul class="list-group list-group-flush border rounded">
                    <li class="list-group-item"><strong>Champions:</strong> Siswa teladan! Berikan apresiasi sertifikat dan pertahankan kenyamanan belajar.</li>
                    <li class="list-group-item"><strong>Loyal Students:</strong> Tawarkan program bimbingan lanjutan atau diskon teman belajar.</li>
                    <li class="list-group-item"><strong>Recent Customers:</strong> Siswa baru bergabung. Berikan sambutan hangat dan modul belajar terbaik.</li>
                    <li class="list-group-item"><strong>Need Attention / At Risk:</strong> Pembayaran tertunda atau kehadiran menurun. Hubungi siswa/orang tua untuk pendampingan.</li>
                    <li class="list-group-item"><strong>Hibernating:</strong> Berikan penawaran reaktivasi atau program intensif menjelang ujian sekolah.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Donut Chart Segmentasi
    const ctxDonut = document.getElementById('rfmDonutChart');
    if (ctxDonut) {
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: [
                    'Champions', 'Loyal Students', 'Potential Loyalists', 
                    'Recent Customers', 'Promising', 'Need Attention', 'At Risk', 'Hibernating'
                ],
                datasets: [{
                    data: [
                        <?= $segment_counts['Champions'] ?>,
                        <?= $segment_counts['Loyal Students'] ?>,
                        <?= $segment_counts['Potential Loyalists'] ?>,
                        <?= $segment_counts['Recent Customers'] ?>,
                        <?= $segment_counts['Promising'] ?>,
                        <?= $segment_counts['Need Attention'] ?>,
                        <?= $segment_counts['At Risk'] ?>,
                        <?= $segment_counts['Hibernating'] ?>
                    ],
                    backgroundColor: [
                        '#10b981', // Champions (Green)
                        '#0ea5e9', // Loyal (Sky Blue)
                        '#8b5cf6', // Potential (Purple)
                        '#98EDE1', // Recent (Mint)
                        '#f59e0b', // Promising (Amber)
                        '#f97316', // Need Attention (Orange)
                        '#ef4444', // At Risk (Red)
                        '#64748b'  // Hibernating (Gray)
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 10 } }
                    }
                }
            }
        });
    }

    // 2. Bar Chart R, F, M Average
    const ctxBar = document.getElementById('rfmBarChart');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Recency (R)', 'Frequency (F)', 'Monetary (M)'],
                datasets: [{
                    label: 'Rata-rata Skor (1-5)',
                    data: [<?= $avg_r ?>, <?= $avg_f ?>, <?= $avg_m ?>],
                    backgroundColor: ['#98EDE1', '#0ea5e9', '#10b981'],
                    borderColor: ['#0f766e', '#0369a1', '#047857'],
                    borderWidth: 1.5,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

<?php require_once BASEPATH . '/includes/footer.php'; ?>

