
<?php
session_start();
require_once "config/koneksi.php";

// Pemeriksaan login
if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: modules/auth/login.php");
    exit;
}

$nama = $_SESSION["nama_lengkap"] ?? "Admin";
$role = $_SESSION["role"] ?? "admin";

// Fungsi menghitung jumlah data
function jumlahData($conn, $tabel) {
    $daftarTabel = ["siswa", "kelas", "tutor", "pembayaran"];

    if (!in_array($tabel, $daftarTabel)) {
        return 0;
    }

    $query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `$tabel`");

    if (!$query) {
        return 0;
    }

    $data = mysqli_fetch_assoc($query);
    return (int) $data["total"];
}

// Statistik
$totalSiswa = jumlahData($conn, "siswa");
$totalKelas = jumlahData($conn, "kelas");
$totalTutor = jumlahData($conn, "tutor");

// Total pembayaran yang sudah lunas
$totalPembayaran = 0;

$queryPembayaran = mysqli_query(
    $conn,
    "SELECT SUM(nominal) AS total
     FROM pembayaran
     WHERE status_pembayaran = 'Lunas'"
);

if ($queryPembayaran) {
    $dataPembayaran = mysqli_fetch_assoc($queryPembayaran);
    $totalPembayaran = (float) ($dataPembayaran["total"] ?? 0);
}

// Grafik pembayaran enam bulan terakhir
$labelBulan = [];
$dataGrafikPembayaran = [];



$queryGrafik = mysqli_query(
    $conn,
    "SELECT
        DATE_FORMAT(MIN(tanggal_bayar), '%b %Y') AS bulan,
        SUM(nominal) AS total
    FROM pembayaran
    WHERE status_pembayaran = 'Lunas'
        AND tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY YEAR(tanggal_bayar), MONTH(tanggal_bayar)
    ORDER BY MIN(tanggal_bayar)"
);


if ($queryGrafik) {
    while ($row = mysqli_fetch_assoc($queryGrafik)) {
        $labelBulan[] = $row["bulan"];
        $dataGrafikPembayaran[] = (float) $row["total"];
    }
}

// Data segmentasi RFM
$jumlahRutin = 0;
$jumlahPengingat = 0;
$jumlahBerisiko = 0;

$queryRFM = mysqli_query(
    $conn,
    "SELECT segmentasi, COUNT(*) AS total
     FROM analisis_rfm
     GROUP BY segmentasi"
);

if ($queryRFM) {
    while ($row = mysqli_fetch_assoc($queryRFM)) {
        $segmentasi = strtolower($row["segmentasi"]);
        $total = (int) $row["total"];

        if (strpos($segmentasi, "rutin") !== false) {
            $jumlahRutin += $total;
        } elseif (strpos($segmentasi, "pengingat") !== false) {
            $jumlahPengingat += $total;
        } elseif (strpos($segmentasi, "risiko") !== false ||
                  strpos($segmentasi, "berisiko") !== false) {
            $jumlahBerisiko += $total;
        }
    }
}

$totalRFM = $jumlahRutin + $jumlahPengingat + $jumlahBerisiko;

function persentase($jumlah, $total) {
    return $total > 0 ? round(($jumlah / $total) * 100) : 0;
}

$persenRutin = persentase($jumlahRutin, $totalRFM);
$persenPengingat = persentase($jumlahPengingat, $totalRFM);
$persenBerisiko = persentase($jumlahBerisiko, $totalRFM);

// Data untuk grafik
$labelBulanJSON = json_encode($labelBulan);
$dataPembayaranJSON = json_encode($dataGrafikPembayaran);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - Smart Education</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    min-height: 100vh;
    background: #E8FDFF;
    color: #000;
}

/* SIDEBAR */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 468px;
    height: 100vh;
    background: #98EDE1;
    border-radius: 0 22px 22px 0;
    padding: 24px 0 16px;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 100;
}

.brand {
    text-align: center;
    padding: 5px 15px 20px;
}

.brand img {
    width: 58px;
    height: 58px;
    object-fit: contain;
    margin-bottom: 6px;
}

.brand h2 {
    font-size: 19px;
    font-weight: 800;
}

.brand p {
    font-size: 10px;
    margin-top: 2px;
}

.admin-profile {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 8px 42px 22px;
}

.avatar {
    width: 60px;
    height: 60px;
    flex: 0 0 60px;
    border-radius: 50%;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 700;
}

.admin-profile h3 {
    font-size: 19px;
    font-weight: 600;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 17px;
    min-height: 49px;
    padding: 8px 26px 8px 72px;
    color: #000;
    text-decoration: none;
    font-size: 17px;
    font-weight: 500;
    transition: background .2s ease;
}

.menu a:hover,
.menu a.active {
    background: #E8FDFF;
}

.menu-icon {
    width: 27px;
    flex: 0 0 27px;
    text-align: center;
    font-size: 25px;
    line-height: 1;
    color: #173B48;
}

.menu a.logout {
    color: #9b2929;
    margin-top: 4px;
}

/* AREA UTAMA */
.main-content {
    width: calc(100% - 468px);
    min-height: 100vh;
    margin-left: 468px;
    padding: 30px 38px 45px 54px;
}

.navbar {
    height: 95px;
    width: 100%;
    background: #98EDE1;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 42px;
    margin-bottom: 35px;
}

.notification {
    font-size: 30px;
    color: #163f47;
    text-decoration: none;
    line-height: 1;
}

.welcome {
    margin: 0 0 30px 16px;
}

.welcome h1 {
    font-size: clamp(26px, 2.5vw, 38px);
    line-height: 1.3;
    font-weight: 700;
    margin-bottom: 10px;
}

.welcome p {
    font-size: 17px;
    line-height: 1.6;
}

/* KARTU RINGKASAN */
.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 28px;
}

.stat-card {
    min-width: 0;
    min-height: 130px;
    padding: 22px 10px;
    border-radius: 20px;
    background: #98EDE1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.stat-card h3 {
    font-size: 17px;
    font-weight: 600;
    margin-bottom: 5px;
}

.stat-card .number {
    font-size: 30px;
    font-weight: 700;
}

.stat-card .money {
    font-size: clamp(14px, 1.25vw, 19px);
    font-weight: 600;
    overflow-wrap: anywhere;
}

/* PANEL GRAFIK: KEDUANYA SAMA LEBAR */
.chart-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 18px;
    width: 100%;
    align-items: stretch;
}

.chart-card {
    min-width: 0;
    width: 100%;
    min-height: 420px;
    padding: 30px;
    border-radius: 20px;
    background: #98EDE1;
    overflow: hidden;
}

.chart-card h2 {
    text-align: center;
    font-size: 21px;
    font-weight: 600;
    margin-bottom: 28px;
}

.chart-container {
    position: relative;
    width: 100%;
    max-width: 100%;
    height: 300px;
    min-width: 0;
}

.chart-container canvas {
    display: block;
    max-width: 100%;
}

.rfm-content {
    min-width: 0;
    min-height: 300px;
    padding: 0 5px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 30px;
}

.rfm-item {
    display: grid;
    grid-template-columns: 22px minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    font-size: 13px;
}

.rfm-dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

.orange { background: #FF9B6A; }
.purple { background: #B69AC8; }
.green { background: #509F94; }

.rfm-percent {
    font-weight: 500;
}

.empty-rfm {
    text-align: center;
    font-size: 14px;
    color: #315e55;
}

/* LAYAR LAPTOP KECIL */
@media (max-width: 1200px) {
    .sidebar {
        width: 270px;
    }

    .main-content {
        width: calc(100% - 270px);
        margin-left: 270px;
        padding: 25px 24px;
    }

    .menu a {
        padding-left: 35px;
    }

    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .chart-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}

/* LAYAR HP */
@media (max-width: 650px) {
    .sidebar {
        width: 75px;
        padding: 20px 0;
    }

    .brand h2,
    .brand p,
    .admin-profile h3,
    .menu a span:not(.menu-icon) {
        display: none;
    }

    .brand img {
        width: 42px;
        height: 42px;
    }

    .admin-profile {
        justify-content: center;
        padding: 10px 5px 25px;
    }

    .avatar {
        width: 43px;
        height: 43px;
        flex-basis: 43px;
        font-size: 17px;
    }

    .menu a {
        justify-content: center;
        padding: 14px 5px;
        min-height: 50px;
    }

    .main-content {
        width: calc(100% - 75px);
        margin-left: 75px;
        padding: 15px 12px 25px;
    }

    .navbar {
        height: 60px;
        margin-bottom: 23px;
        border-radius: 14px;
        padding: 0 20px;
    }

    .welcome {
        margin-left: 3px;
    }

    .welcome h1 {
        font-size: 21px;
    }

    .welcome p {
        font-size: 12px;
    }

    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .stat-card {
        min-height: 105px;
        padding: 14px 7px;
        border-radius: 15px;
    }

    .stat-card h3 { font-size: 13px; }
    .stat-card .number { font-size: 23px; }
    .stat-card .money { font-size: 12px; }

    .chart-card {
        min-height: 340px;
        padding: 20px 14px;
        border-radius: 15px;
    }

    .chart-card h2 { font-size: 16px; }
    .chart-container { height: 250px; }
    .rfm-item { font-size: 11px; }
}

    </style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="brand">
        <img src="assets/images/logo.png" alt="Logo Smart Education">
        <h2>Smart Education</h2>
        <p>Bimbingan Belajar Pare-pare</p>
    </div>

    <div class="admin-profile">
        <div class="avatar">
            <?= strtoupper(substr($nama, 0, 1)); ?>
        </div>
        <h3><?= htmlspecialchars(ucwords(str_replace("_", " ", $role))); ?></h3>
    </div>

    <nav class="menu">

        <a href="dashboard.php" class="active">
            <span class="menu-icon">⌂</span>
            <span>Dashboard</span>
        </a>

        <a href="modules/siswa/index.php">
            <span class="menu-icon">♙</span>
            <span>Data Siswa</span>
        </a>

        <a href="modules/paket/index.php">
            <span class="menu-icon">▱</span>
            <span>Paket Belajar</span>
        </a>

        <a href="modules/tutor/index.php">
            <span class="menu-icon">♙</span>
            <span>Data Tutor</span>
        </a>

        <a href="modules/ruangan/index.php">
            <span class="menu-icon">▯</span>
            <span>Ruangan</span>
        </a>

        <a href="modules/kelas/index.php">
            <span class="menu-icon">⌂</span>
            <span>Kelas</span>
        </a>

        <a href="modules/pembayaran/index.php">
            <span class="menu-icon">▣</span>
            <span>Pembayaran</span>
        </a>

        <a href="modules/pembayaran/riwayat.php">
            <span class="menu-icon">▤</span>
            <span>Riwayat Pembayaran</span>
        </a>

        <a href="modules/rfm/index.php">
            <span class="menu-icon">▥</span>
            <span>Analisis RFM</span>
        </a>

        <a href="modules/jadwal/otomatis.php">
            <span class="menu-icon">◷</span>
            <span>Penjadwalan Otomatis</span>
        </a>

        <a href="modules/jadwal/index.php">
            <span class="menu-icon">▦</span>
            <span>Informasi Jadwal</span>
        </a>

        <a href="modules/auth/logout.php" class="logout">
            <span class="menu-icon">⇥</span>
            <span>Logout</span>
        </a>

    </nav>

</aside>

<!-- KONTEN DASHBOARD -->
<main class="main-content">

    <div class="navbar">
        <a href="#" class="notification" title="Notifikasi">♧</a>
    </div>

    <section class="welcome">
        <h1>
            Selamat Datang, <?= htmlspecialchars(ucwords(str_replace("_", " ", $role))); ?>
        </h1>

        <p>
            Kelola seluruh data bimbingan belajar Smart Education di sini.
        </p>
    </section>

    <!-- STATISTIK -->
    <section class="stats">

        <div class="stat-card">
            <h3>Total Siswa</h3>
            <div class="number"><?= number_format($totalSiswa, 0, ',', '.'); ?></div>
        </div>

        <div class="stat-card">
            <h3>Total Kelas</h3>
            <div class="number"><?= number_format($totalKelas, 0, ',', '.'); ?></div>
        </div>

        <div class="stat-card">
            <h3>Total Tutor</h3>
            <div class="number"><?= number_format($totalTutor, 0, ',', '.'); ?></div>
        </div>

        <div class="stat-card">
            <h3>Pembayaran</h3>
            <div class="money">
                Rp. <?= number_format($totalPembayaran, 0, ',', '.'); ?>
            </div>
        </div>

    </section>

    <!-- GRAFIK -->
    <section class="chart-grid">

        <div class="chart-card">
            <h2>Grafik Pembayaran</h2>

            <div class="chart-container">
                <canvas id="grafikPembayaran"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h2>Segmentasi Siswa (RFM)</h2>

            <?php if ($totalRFM > 0): ?>

                <div class="rfm-content">

                    <div class="rfm-item">
                        <div class="rfm-dot orange"></div>
                        <span>Pembayar Rutin (8–9)</span>
                        <span class="rfm-percent"><?= $persenRutin; ?>%</span>
                    </div>

                    <div class="rfm-item">
                        <div class="rfm-dot purple"></div>
                        <span>Perlu Pengingat (5–7)</span>
                        <span class="rfm-percent"><?= $persenPengingat; ?>%</span>
                    </div>

                    <div class="rfm-item">
                        <div class="rfm-dot green"></div>
                        <span>Berisiko (3–4)</span>
                        <span class="rfm-percent"><?= $persenBerisiko; ?>%</span>
                    </div>

                </div>

            <?php else: ?>

                <div class="rfm-content">
                    <p class="empty-rfm">
                        Belum ada data segmentasi RFM.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<script>
    const labelBulan = <?= $labelBulanJSON ?: "[]"; ?>;
    const dataPembayaran = <?= $dataPembayaranJSON ?: "[]"; ?>;

    const canvas = document.getElementById("grafikPembayaran");

    new Chart(canvas, {
        type: "bar",

        data: {
            labels: labelBulan,

            datasets: [{
                label: "Total Pembayaran",
                data: dataPembayaran,
                backgroundColor: "#68B7AA",
                borderRadius: 6,
                barThickness: 45
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 100,

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return "Rp " +
                                new Intl.NumberFormat("id-ID").format(context.raw);
                        }
                    }
                }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat("id-ID", {
                                notation: "compact"
                            }).format(value);
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>
