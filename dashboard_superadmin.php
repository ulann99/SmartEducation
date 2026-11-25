<?php
session_start();
require_once "config/koneksi.php";

// ==========================================
// KEAMANAN HALAMAN
// ==========================================

if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: modules/auth/login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "superadmin") {
    header("Location: dashboard.php");
    exit;
}

$nama = $_SESSION["nama_lengkap"] ?? "Super Administrator";
$inisial = strtoupper(substr($nama, 0, 1));

// ==========================================
// MENGHITUNG JUMLAH PENGGUNA BERDASARKAN ROLE
// ==========================================

$totalPengguna = 0;
$totalAdmin = 0;
$totalSiswa = 0;
$totalSuperAdmin = 0;

$queryPengguna = mysqli_query(
    $conn,
    "SELECT role, COUNT(*) AS total
     FROM users
     GROUP BY role"
);

if ($queryPengguna) {
    while ($row = mysqli_fetch_assoc($queryPengguna)) {

        $role = strtolower(trim($row["role"] ?? ""));
        $jumlah = (int)$row["total"];

        $totalPengguna += $jumlah;

        if ($role === "admin") {
            $totalAdmin = $jumlah;
        } elseif ($role === "siswa") {
            $totalSiswa = $jumlah;
        } elseif ($role === "superadmin") {
            $totalSuperAdmin = $jumlah;
        }
    }
}

// ==========================================
// DATA GRAFIK PENGGUNA
// ==========================================

$labelRole = ["Super Admin", "Admin", "Siswa/Orang Tua"];

$dataRole = [
    $totalSuperAdmin,
    $totalAdmin,
    $totalSiswa
];

$labelJSON = json_encode($labelRole);
$dataJSON = json_encode($dataRole);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Super Admin - Smart Education</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Poppins", sans-serif;
}

:root {
    --primary: #98EDE1;
    --secondary: #E8FDFF;
    --dark: #173B38;
    --accent: #287E72;
    --text: #172B2A;
    --muted: #718582;
    --border: #E2EFEC;
    --shadow: 0 5px 22px rgba(33, 105, 93, .06);
}

body {
    min-height: 100vh;
    background: #F6FBFA;
    color: var(--text);
}

/* SIDEBAR */

/* SIDEBAR */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 270px;
    height: 100vh;
    background: var(--primary);
    padding: 24px 0;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 100;
    border-radius: 0 18px 18px 0;
}

.brand {
    text-align: center;
    padding: 4px 15px 30px;
}

.brand img {
    width: 58px;
    height: 58px;
    object-fit: contain;
    margin-bottom: 8px;
}

.brand h2 {
    color: var(--dark);
    font-size: 18px;
    font-weight: 800;
}

.brand p {
    color: #315E57;
    font-size: 10px;
    margin-top: 3px;
}

.menu {
    padding: 0 10px;
    width: 100%;
}

.menu-title {
    padding: 12px 14px 8px;
    color: #47746D;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.menu a {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: flex-start;
    width: 100%;
    min-height: 45px;
    padding: 10px 12px;
    margin-bottom: 5px;
    border-radius: 9px;
    gap: 12px;
    color: #173B38;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
    transition: .2s;
}

.menu a span:not(.menu-icon) {
    display: block;
    flex: 1;
    white-space: nowrap;
    word-break: normal;
    overflow-wrap: normal;
    text-align: left;
}

.menu-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    min-width: 22px;
    flex: 0 0 22px;
    text-align: center;
    font-size: 18px;
}

.menu a:hover,
.menu a.active {
    background: var(--secondary);
    color: #176D63;
}

.menu a.disabled {
    opacity: .65;
    cursor: default;
}

.menu a.logout {
    color: #A93232;
    margin-top: 18px;
}

/* KONTEN */

.main-content {
    width: calc(100% - 270px);
    min-height: 100vh;
    margin-left: 270px;
    padding: 0 30px 35px;
}

/* HEADER */

.topbar {
    height: 82px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 25px;
    margin: 0 -30px 28px;
    background: white;
    border-bottom: 1px solid var(--border);
    box-shadow: 0 3px 14px rgba(30, 80, 72, .035);
}

.topbar-title {
    color: var(--dark);
    font-size: 19px;
    font-weight: 700;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 18px;
}

.top-profile {
    display: flex;
    align-items: center;
    gap: 10px;
}

.top-avatar {
    width: 39px;
    height: 39px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #DDF7EF;
    color: var(--accent);
    font-weight: 700;
}

.top-profile-text strong {
    display: block;
    color: var(--dark);
    font-size: 11px;
}

.top-profile-text span {
    display: block;
    color: var(--muted);
    font-size: 10px;
}

/* SAMBUTAN */

.welcome {
    margin-bottom: 25px;
}

.welcome h1 {
    color: var(--dark);
    font-size: clamp(23px, 2vw, 29px);
    margin-bottom: 6px;
}

.welcome p {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
}

/* STATISTIK */

.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 23px;
}

.stat-card {
    min-height: 135px;
    padding: 20px 14px;
    border-radius: 16px;
    background: white;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    transition: .2s;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 9px;
    border-radius: 10px;
    background: var(--secondary);
    color: var(--accent);
    font-size: 19px;
}

.stat-card h3 {
    margin-bottom: 5px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 500;
}

.number {
    color: var(--dark);
    font-size: 25px;
    font-weight: 700;
}

/* BAGIAN GRAFIK DAN INFORMASI */

.content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}

.content-card {
    min-width: 0;
    min-height: 360px;
    padding: 24px;
    border-radius: 17px;
    background: white;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.card-heading {
    margin-bottom: 22px;
}

.card-heading h2 {
    color: var(--dark);
    font-size: 15px;
    font-weight: 600;
}

.card-heading p {
    margin-top: 5px;
    color: var(--muted);
    font-size: 11px;
}

.chart-container {
    position: relative;
    width: 100%;
    height: 270px;
}

/* HAK AKSES */

.role-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.role-item {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    padding: 14px;
    background: #F7FCFA;
    border: 1px solid var(--border);
    border-radius: 12px;
}

.role-icon {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--secondary);
    color: var(--accent);
    font-size: 18px;
}

.role-info h3 {
    color: var(--dark);
    font-size: 12px;
    margin-bottom: 4px;
}

.role-info p {
    color: var(--muted);
    font-size: 10px;
    line-height: 1.7;
}

/* INFORMASI SISTEM */

.info-card {
    padding: 21px 23px;
    border-radius: 16px;
    background: white;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.info-card h2 {
    color: var(--dark);
    font-size: 15px;
    margin-bottom: 10px;
}

.info-card p {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.8;
}

.footer {
    margin-top: 25px;
    color: var(--muted);
    text-align: center;
    font-size: 10px;
}

/* RESPONSIVE */

/* RESPONSIVE */

@media (max-width: 1150px) {
    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 650px) {
    .sidebar {
        width: 68px;
    }

    .brand {
        padding: 4px 5px 22px;
    }

    .brand img {
        width: 42px;
        height: 42px;
    }

    .brand h2,
    .brand p,
    .menu a span:not(.menu-icon),
    .menu-title {
        display: none;
    }

    .menu {
        padding: 0 7px;
    }

    .menu a {
        justify-content: center;
        padding: 10px 4px;
    }

    .main-content {
        width: calc(100% - 68px);
        margin-left: 68px;
        padding: 0 12px 24px;
    }

    .topbar {
        height: 68px;
        margin: 0 -12px 22px;
        padding: 0 12px;
    }

    .topbar-title {
        font-size: 14px;
    }

    .top-profile-text strong,
    .top-profile-text span {
        font-size: 9px;
    }

    .stats {
        gap: 10px;
    }

    .stat-card {
        min-height: 110px;
        padding: 13px 7px;
    }

    .stat-card h3 {
        font-size: 10px;
    }

    .number {
        font-size: 21px;
    }

    .content-card {
        padding: 17px 13px;
    }
}

</style>

</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="brand">
        <img src="assets/images/logo.png" alt="Logo Smart Education">
        <h2>Smart Education</h2>
        <p>Bimbingan Belajar Parepare</p>
    </div>

    <nav class="menu">

    <div class="menu-title">Menu Utama</div>

    <a href="dashboard_superadmin.php" class="active">
        <span class="menu-icon">⌂</span>
        <span>Dashboard</span>
    </a>

    <div class="menu-title">Pengelolaan Sistem</div>

    <a href="modules/users/index.php">
        <span class="menu-icon">♙</span>
        <span>Manajemen Pengguna</span>
    </a>

    <a href="modules/users/administrator.php">
        <span class="menu-icon">♧</span>
        <span>Manajemen Administrator</span>
    </a>

    <a href="modules/users/hak_akses.php">
        <span class="menu-icon">⚙</span>
        <span>Pengaturan Hak Akses</span>
    </a>

    <div class="menu-title">Akun</div>

    <a href="modules/auth/logout.php" class="logout">
        <span class="menu-icon">⇥</span>
        <span>Logout</span>
    </a>

</nav>

</aside>


<!-- KONTEN UTAMA -->

<main class="main-content">

    <!-- HEADER -->

    <header class="topbar">

        <div class="topbar-title">
            Dashboard Super Admin
        </div>

        <div class="topbar-right">

            <div class="top-profile">

                <div class="top-avatar">
                    <?= htmlspecialchars($inisial); ?>
                </div>

                <div class="top-profile-text">
                    <strong><?= htmlspecialchars($nama); ?></strong>
                    <span>Super Admin</span>
                </div>

            </div>

        </div>

    </header>


    <!-- SAMBUTAN -->

    <section class="welcome">

        <h1>Selamat Datang, Super Admin!</h1>

        <p>
            Kelola akun pengguna, atur hak akses, dan pantau
            pengelolaan sistem Smart Education Parepare.
        </p>

    </section>


    <!-- STATISTIK PENGGUNA -->

    <section class="stats">

        <div class="stat-card">

            <div class="stat-icon">♙</div>

            <h3>Total Pengguna</h3>

            <div class="number">
                <?= number_format($totalPengguna, 0, ',', '.'); ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">♧</div>

            <h3>Total Administrator</h3>

            <div class="number">
                <?= number_format($totalAdmin, 0, ',', '.'); ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">♙</div>

            <h3>Total Siswa/Orang Tua</h3>

            <div class="number">
                <?= number_format($totalSiswa, 0, ',', '.'); ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">★</div>

            <h3>Total Super Admin</h3>

            <div class="number">
                <?= number_format($totalSuperAdmin, 0, ',', '.'); ?>
            </div>

        </div>

    </section>


    <!-- GRAFIK DAN HAK AKSES -->

    <section class="content-grid">

        <!-- GRAFIK PENGGUNA -->

        <div class="content-card">

            <div class="card-heading">

                <h2>Distribusi Pengguna</h2>

                <p>
                    Perbandingan jumlah akun berdasarkan peran pengguna.
                </p>

            </div>

            <div class="chart-container">
                <canvas id="grafikPengguna"></canvas>
            </div>

        </div>


        <!-- INFORMASI HAK AKSES -->

        <div class="content-card">

            <div class="card-heading">

                <h2>Pengawasan Hak Akses</h2>

                <p>
                    Ringkasan tanggung jawab setiap peran dalam sistem.
                </p>

            </div>

            <div class="role-list">

                <div class="role-item">

                    <div class="role-icon">★</div>

                    <div class="role-info">

                        <h3>Super Admin</h3>

                        <p>
                            Mengelola akun pengguna, mengatur hak akses,
                            dan mengawasi keseluruhan sistem.
                        </p>

                    </div>

                </div>


                <div class="role-item">

                    <div class="role-icon">♧</div>

                    <div class="role-info">

                        <h3>Admin</h3>

                        <p>
                            Mengelola data siswa, paket belajar, tutor,
                            ruangan, kelas, pembayaran, RFM, dan jadwal.
                        </p>

                    </div>

                </div>


                <div class="role-item">

                    <div class="role-icon">♙</div>

                    <div class="role-info">

                        <h3>Siswa/Orang Tua</h3>

                        <p>
                            Mengakses informasi data pribadi, paket belajar,
                            jadwal, tagihan, dan riwayat pembayaran.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- INFORMASI PENGELOLAAN -->

    <section class="info-card">

        <h2>Informasi Pengelolaan Sistem</h2>

        <p>
            Dashboard ini digunakan oleh Super Admin untuk memantau
            jumlah akun yang terdaftar dan pembagian peran pengguna.
            Pengelolaan akun, perubahan informasi pengguna, serta
            pengaturan hak akses dilakukan melalui modul manajemen
            pengguna yang akan dikembangkan sesuai kebutuhan sistem.
        </p>

    </section>


    <div class="footer">
        © <?= date("Y"); ?> Smart Education Parepare. All Rights Reserved.
    </div>

</main>


<!-- GRAFIK -->

<script>

const labelRole = <?= $labelJSON ?: "[]"; ?>;
const dataRole = <?= $dataJSON ?: "[]"; ?>;

new Chart(document.getElementById("grafikPengguna"), {

    type: "doughnut",

    data: {

        labels: labelRole,

        datasets: [{

            data: dataRole,

            backgroundColor: [
                "#287E72",
                "#68B7AA",
                "#B5E9DF"
            ],

            borderWidth: 3,
            borderColor: "#FFFFFF",
            hoverOffset: 7

        }]

    },

    options: {

        responsive: true,
        maintainAspectRatio: false,

        plugins: {

            legend: {

                position: "bottom",

                labels: {
                    usePointStyle: true,
                    padding: 20,
                    color: "#173B38",
                    font: {
                        family: "Poppins",
                        size: 11
                    }
                }

            },

            tooltip: {

                backgroundColor: "#173B38",
                padding: 12

            }

        },

        cutout: "65%"

    }

});

</script>

</body>
</html>
