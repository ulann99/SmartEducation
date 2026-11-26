
<?php
// =====================================
// INISIALISASI DAN KONEKSI
// =====================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config/koneksi.php";

if (!defined("BASEPATH")) {
    define("BASEPATH", __DIR__);
}

// =====================================
// PEMERIKSAAN LOGIN
// =====================================
if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: modules/auth/login.php");
    exit;
}

$nama = $_SESSION["nama_lengkap"] ?? "Administrator";
$role = $_SESSION["role"] ?? "admin";

if (!in_array($role, ["admin", "superadmin"], true)) {
    header("Location: portal_siswa/dashboard_siswa.php");
    exit;
}

// =====================================
// KONFIGURASI TEMPLATE
// =====================================
$current_page = "dashboard";
$page_title = "Dashboard";

// =====================================
// FUNGSI BANTUAN DATABASE
// =====================================
function dashboardQuery($conn, $sql)
{
    try {
        return mysqli_query($conn, $sql);
    } catch (Throwable $e) {
        error_log("Dashboard query error: " . $e->getMessage());
        return false;
    }
}

function jumlahData($conn, $tabel)
{
    $daftarTabel = ["siswa", "kelas", "tutor"];

    if (!in_array($tabel, $daftarTabel, true)) {
        return 0;
    }

    $query = dashboardQuery(
        $conn,
        "SELECT COUNT(*) AS total FROM `$tabel`"
    );

    if (!$query) {
        return 0;
    }

    $data = mysqli_fetch_assoc($query);

    return (int) ($data["total"] ?? 0);
}

function persentase($jumlah, $total)
{
    return $total > 0
        ? (int) round(($jumlah / $total) * 100)
        : 0;
}

// =====================================
// STATISTIK DASHBOARD
// =====================================
$totalSiswa = jumlahData($conn, "siswa");
$totalKelas = jumlahData($conn, "kelas");
$totalTutor = jumlahData($conn, "tutor");

$totalPembayaran = 0;

// Mengambil total pembayaran yang berhasil
$queryPembayaran = dashboardQuery(
    $conn,
    "SELECT COALESCE(SUM(jumlah_bayar), 0) AS total
     FROM pembayaran
     WHERE status_pembayaran = 'Berhasil'"
);

if ($queryPembayaran) {
    $dataPembayaran = mysqli_fetch_assoc($queryPembayaran);
    $totalPembayaran = (float) ($dataPembayaran["total"] ?? 0);
}

// =====================================
// GRAFIK PEMBAYARAN ENAM BULAN TERAKHIR
// =====================================
$labelBulan = [];
$dataGrafikPembayaran = [];

$queryGrafik = dashboardQuery(
    $conn,
    "SELECT
        DATE_FORMAT(tanggal_bayar, '%b %Y') AS bulan,
        SUM(jumlah_bayar) AS total
     FROM pembayaran
     WHERE status_pembayaran = 'Berhasil'
       AND tanggal_bayar >= DATE_FORMAT(
           DATE_SUB(CURDATE(), INTERVAL 5 MONTH),
           '%Y-%m-01'
       )
     GROUP BY YEAR(tanggal_bayar), MONTH(tanggal_bayar)
     ORDER BY YEAR(tanggal_bayar), MONTH(tanggal_bayar)"
);

if ($queryGrafik) {
    while ($row = mysqli_fetch_assoc($queryGrafik)) {
        $labelBulan[] = $row["bulan"] ?? "";
        $dataGrafikPembayaran[] = (float) ($row["total"] ?? 0);
    }
}

// =====================================
// SEGMENTASI RFM
// =====================================
$jumlahRutin = 0;
$jumlahPengingat = 0;
$jumlahBerisiko = 0;

$queryRFM = dashboardQuery(
    $conn,
    "SELECT segmentasi, COUNT(*) AS total
     FROM analisis_rfm
     GROUP BY segmentasi"
);

if ($queryRFM) {
    while ($row = mysqli_fetch_assoc($queryRFM)) {
        $segmentasi = strtolower((string) ($row["segmentasi"] ?? ""));
        $total = (int) ($row["total"] ?? 0);

        if (strpos($segmentasi, "rutin") !== false) {
            $jumlahRutin += $total;
        } elseif (strpos($segmentasi, "pengingat") !== false) {
            $jumlahPengingat += $total;
        } elseif (
            strpos($segmentasi, "risiko") !== false ||
            strpos($segmentasi, "berisiko") !== false
        ) {
            $jumlahBerisiko += $total;
        }
    }
}

// =====================================
// PERSENTASE RFM
// =====================================
$totalRFM = $jumlahRutin + $jumlahPengingat + $jumlahBerisiko;

$persenRutin = persentase($jumlahRutin, $totalRFM);
$persenPengingat = persentase($jumlahPengingat, $totalRFM);
$persenBerisiko = persentase($jumlahBerisiko, $totalRFM);

// =====================================
// DATA GRAFIK JSON
// =====================================
$labelBulanJSON = json_encode(
    $labelBulan,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

$dataPembayaranJSON = json_encode(
    $dataGrafikPembayaran,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

// =====================================
// TEMPLATE HEADER
// =====================================
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/sidebar.php";
require_once __DIR__ . "/includes/navbar.php";
?>

<style>
.dashboard-content {
    width: 100%;
}

.dashboard-welcome {
    margin-bottom: 24px;
}

.dashboard-welcome h2 {
    color: #173B38;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 6px;
}

.dashboard-welcome p {
    color: #718582;
    font-size: 13px;
    margin-bottom: 0;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}

.dashboard-stat-card {
    min-width: 0;
    background: #FFFFFF;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 19px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 4px 15px rgba(33, 105, 93, .045);
    transition: .2s ease;
}

.dashboard-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(33, 105, 93, .09);
}

.dashboard-stat-icon {
    width: 47px;
    height: 47px;
    min-width: 47px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #E8FDFF;
    color: #287E72;
    font-size: 21px;
}

.dashboard-stat-info {
    min-width: 0;
}

.dashboard-stat-info p {
    color: #718582;
    font-size: 12px;
    margin-bottom: 5px;
}

.dashboard-stat-info h3 {
    color: #173B38;
    font-size: 23px;
    font-weight: 700;
    margin: 0;
    overflow-wrap: anywhere;
}

.dashboard-stat-info h3.money {
    font-size: 16px;
}

.dashboard-chart-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
    gap: 20px;
    align-items: stretch;
}

.dashboard-panel {
    min-width: 0;
    background: #FFFFFF;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(33, 105, 93, .045);
}

.dashboard-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 22px;
}

.dashboard-panel-header h3 {
    color: #173B38;
    font-size: 15px;
    font-weight: 600;
    margin: 0;
}

.dashboard-panel-label {
    background: #E8FDFF;
    color: #287E72;
    font-size: 10px;
    padding: 6px 11px;
    border-radius: 20px;
    white-space: nowrap;
}

.dashboard-chart-container {
    position: relative;
    width: 100%;
    height: 285px;
}

.dashboard-rfm-list {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 25px;
    min-height: 285px;
}

.dashboard-rfm-item {
    display: grid;
    grid-template-columns: 11px minmax(0, 1fr) auto;
    align-items: center;
    gap: 11px;
}

.dashboard-rfm-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
}

.dashboard-rfm-name {
    color: #435B56;
    font-size: 12px;
}

.dashboard-rfm-percent {
    color: #173B38;
    font-size: 12px;
    font-weight: 700;
}

.dashboard-rfm-progress {
    grid-column: 2 / 4;
    height: 7px;
    margin-top: -3px;
    overflow: hidden;
    background: #EDF5F3;
    border-radius: 20px;
}

.dashboard-rfm-progress span {
    display: block;
    height: 100%;
    border-radius: 20px;
}

.rfm-orange {
    background: #F5A16F;
}

.rfm-purple {
    background: #AD91C8;
}

.rfm-green {
    background: #509F94;
}

.dashboard-empty {
    min-height: 285px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #718582;
    font-size: 12px;
}

@media (max-width: 1200px) {
    .dashboard-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-chart-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 575px) {
    .dashboard-welcome h2 {
        font-size: 19px;
    }

    .dashboard-welcome p {
        font-size: 11px;
    }

    .dashboard-stats {
        gap: 10px;
    }

    .dashboard-stat-card {
        padding: 13px 10px;
        gap: 9px;
    }

    .dashboard-stat-icon {
        width: 37px;
        height: 37px;
        min-width: 37px;
        font-size: 17px;
    }

    .dashboard-stat-info p {
        font-size: 10px;
    }

    .dashboard-stat-info h3 {
        font-size: 18px;
    }

    .dashboard-stat-info h3.money {
        font-size: 12px;
    }

    .dashboard-panel {
        padding: 16px 13px;
    }

    .dashboard-panel-header h3 {
        font-size: 13px;
    }

    .dashboard-chart-container {
        height: 240px;
    }

    .dashboard-rfm-name {
        font-size: 10px;
    }
}
</style>

<div class="dashboard-content">

    <!-- SAMBUTAN -->
    <section class="dashboard-welcome">
        <h2>
            Selamat Datang,
            <?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8'); ?>!
        </h2>

        <p>
            Kelola kegiatan bimbingan belajar Smart Education dengan mudah melalui dashboard ini.
        </p>
    </section>

    <!-- STATISTIK -->
    <section class="dashboard-stats">

        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon">
                <i class="bi bi-people"></i>
            </div>

            <div class="dashboard-stat-info">
                <p>Total Siswa</p>
                <h3><?= number_format($totalSiswa, 0, ',', '.'); ?></h3>
            </div>
        </div>

        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon">
                <i class="bi bi-easel"></i>
            </div>

            <div class="dashboard-stat-info">
                <p>Total Kelas</p>
                <h3><?= number_format($totalKelas, 0, ',', '.'); ?></h3>
            </div>
        </div>

        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon">
                <i class="bi bi-person-workspace"></i>
            </div>

            <div class="dashboard-stat-info">
                <p>Total Tutor</p>
                <h3><?= number_format($totalTutor, 0, ',', '.'); ?></h3>
            </div>
        </div>

        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon">
                <i class="bi bi-wallet2"></i>
            </div>

            <div class="dashboard-stat-info">
                <p>Total Pembayaran</p>
                <h3 class="money">
                    Rp <?= number_format($totalPembayaran, 0, ',', '.'); ?>
                </h3>
            </div>
        </div>

    </section>

    <!-- GRAFIK DAN RFM -->
    <section class="dashboard-chart-grid">

        <!-- GRAFIK PEMBAYARAN -->
        <div class="dashboard-panel">

            <div class="dashboard-panel-header">
                <h3>
                    <i class="bi bi-bar-chart-line me-2"></i>
                    Grafik Pembayaran
                </h3>

                <span class="dashboard-panel-label">
                    6 Bulan Terakhir
                </span>
            </div>

            <div class="dashboard-chart-container">
                <canvas id="grafikPembayaran"></canvas>
            </div>

        </div>

        <!-- SEGMENTASI RFM -->
        <div class="dashboard-panel">

            <div class="dashboard-panel-header">
                <h3>
                    <i class="bi bi-pie-chart me-2"></i>
                    Segmentasi Siswa (RFM)
                </h3>

                <span class="dashboard-panel-label">
                    Analisis Siswa
                </span>
            </div>

            <?php if ($totalRFM > 0): ?>

                <div class="dashboard-rfm-list">

                    <div class="dashboard-rfm-item">
                        <span class="dashboard-rfm-dot rfm-orange"></span>

                        <span class="dashboard-rfm-name">
                            Pembayar Rutin (8–9)
                        </span>

                        <span class="dashboard-rfm-percent">
                            <?= $persenRutin; ?>%
                        </span>

                        <div class="dashboard-rfm-progress">
                            <span
                                class="rfm-orange"
                                style="width: <?= $persenRutin; ?>%">
                            </span>
                        </div>
                    </div>

                    <div class="dashboard-rfm-item">
                        <span class="dashboard-rfm-dot rfm-purple"></span>

                        <span class="dashboard-rfm-name">
                            Perlu Pengingat (5–7)
                        </span>

                        <span class="dashboard-rfm-percent">
                            <?= $persenPengingat; ?>%
                        </span>

                        <div class="dashboard-rfm-progress">
                            <span
                                class="rfm-purple"
                                style="width: <?= $persenPengingat; ?>%">
                            </span>
                        </div>
                    </div>

                    <div class="dashboard-rfm-item">
                        <span class="dashboard-rfm-dot rfm-green"></span>

                        <span class="dashboard-rfm-name">
                            Berisiko (3–4)
                        </span>

                        <span class="dashboard-rfm-percent">
                            <?= $persenBerisiko; ?>%
                        </span>

                        <div class="dashboard-rfm-progress">
                            <span
                                class="rfm-green"
                                style="width: <?= $persenBerisiko; ?>%">
                            </span>
                        </div>
                    </div>

                </div>

            <?php else: ?>

                <div class="dashboard-empty">
                    <div>
                        <i class="bi bi-bar-chart fs-2 d-block mb-2"></i>
                        Belum ada data segmentasi RFM.
                    </div>
                </div>

            <?php endif; ?>

        </div>

    </section>

</div>

<!-- GRAFIK PEMBAYARAN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labelBulan = <?= $labelBulanJSON ?: "[]"; ?>;
const dataPembayaran = <?= $dataPembayaranJSON ?: "[]"; ?>;

const canvasPembayaran = document.getElementById("grafikPembayaran");

if (canvasPembayaran && typeof Chart !== "undefined") {

    new Chart(canvasPembayaran, {

        type: "bar",

        data: {
            labels: labelBulan,

            datasets: [{
                label: "Total Pembayaran",
                data: dataPembayaran,

                backgroundColor: "#68B7AA",
                hoverBackgroundColor: "#287E72",

                borderRadius: 8,
                barThickness: 35,
                maxBarThickness: 45
            }]
        },

        options: {

            responsive: true,
            maintainAspectRatio: false,

            plugins: {

                legend: {
                    display: false
                },

                tooltip: {

                    backgroundColor: "#173B38",
                    padding: 12,

                    callbacks: {

                        label: function(context) {
                            return "Rp " +
                                new Intl.NumberFormat("id-ID")
                                .format(context.raw);
                        }

                    }

                }

            },

            scales: {

                x: {

                    grid: {
                        display: false
                    },

                    ticks: {
                        color: "#718582",
                        font: {
                            size: 11
                        }
                    },

                    border: {
                        display: false
                    }

                },

                y: {

                    beginAtZero: true,

                    grid: {
                        color: "#E8F2EF"
                    },

                    ticks: {

                        color: "#718582",

                        font: {
                            size: 10
                        },

                        callback: function(value) {
                            return new Intl.NumberFormat("id-ID", {
                                notation: "compact"
                            }).format(value);
                        }

                    },

                    border: {
                        display: false
                    }

                }

            }

        }

    });

}
</script>

<?php
// =====================================
// TEMPLATE FOOTER
// =====================================
require_once __DIR__ . "/includes/footer.php";
?>
