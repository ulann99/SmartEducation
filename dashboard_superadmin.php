
<?php
session_start();
require_once "config/koneksi.php";

// Pastikan hanya Super Admin yang dapat mengakses
if (
    !isset($_SESSION["login"]) ||
    $_SESSION["role"] !== "superadmin"
) {
    header("Location: modules/auth/login.php");
    exit;
}

// Logout
if (isset($_GET["logout"])) {
    session_unset();
    session_destroy();
    header("Location: modules/auth/login.php");
    exit;
}

// Fungsi mengambil jumlah data
function jumlahData($conn, $query) {
    $result = mysqli_query($conn, $query);

    if (!$result) {
        return 0;
    }

    $data = mysqli_fetch_assoc($result);
    return $data["total"] ?? 0;
}

// Statistik dashboard
$totalSiswa = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total FROM siswa"
);

$totalAdmin = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'"
);

$totalTutor = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total FROM tutor"
);

$totalKelas = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total FROM kelas"
);

$totalPaket = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total FROM paket_belajar"
);

$resultPembayaran = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(nominal), 0) AS total
     FROM pembayaran
     WHERE status_pembayaran = 'Lunas'"
);

$totalPembayaran = 0;

if ($resultPembayaran) {
    $dataPembayaran = mysqli_fetch_assoc($resultPembayaran);
    $totalPembayaran = $dataPembayaran["total"] ?? 0;
}

$namaAdmin = $_SESSION["nama_lengkap"] ?? "Super Admin";
$inisial = strtoupper(substr($namaAdmin, 0, 1));
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Super Admin - Smart Education</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #E8FDFF;
    color: #000;
}

/* SIDEBAR */
.sidebar {
    width: 280px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: #98EDE1;
    overflow-y: auto;
    border-radius: 0 20px 20px 0;
}

.brand {
    text-align: center;
    padding: 30px 15px 25px;
}

.brand img {
    width: 55px;
    height: 55px;
    object-fit: contain;
    border-radius: 50%;
    margin-bottom: 8px;
}

.brand h2 {
    font-size: 19px;
    font-weight: 700;
}

.brand p {
    font-size: 10px;
}

.profile {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 20px 28px 30px;
}

.avatar {
    width: 55px;
    height: 55px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    font-weight: 700;
}

.profile strong {
    font-size: 16px;
}

.menu {
    padding-bottom: 20px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 28px;
    color: #000;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: .2s;
}

.menu a:hover,
.menu a.active {
    background: #E8FDFF;
}

.menu .icon {
    width: 25px;
    text-align: center;
    font-size: 19px;
}

/* MAIN */
.main {
    margin-left: 280px;
    padding: 30px;
    min-height: 100vh;
}

.topbar {
    height: 75px;
    background: #98EDE1;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 30px;
    margin-bottom: 30px;
}

.notification {
    font-size: 24px;
}

.welcome h1 {
    font-size: 30px;
    font-weight: 700;
    margin-bottom: 5px;
}

.welcome p {
    font-size: 15px;
    color: #333;
    margin-bottom: 28px;
}

/* STATISTICS */
.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    background: #98EDE1;
    padding: 25px 15px;
    border-radius: 18px;
    text-align: center;
}

.stat-card p {
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
}

.stat-card h2 {
    font-size: 27px;
    font-weight: 700;
}

.stat-card .money {
    font-size: 19px;
}

/* CONTENT */
.content-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 18px;
}

.panel {
    background: #98EDE1;
    padding: 25px;
    border-radius: 18px;
    min-height: 260px;
}

.panel h3 {
    font-size: 18px;
    margin-bottom: 20px;
}

.quick-links {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.quick-links a {
    background: rgba(255,255,255,.55);
    padding: 15px;
    border-radius: 12px;
    text-decoration: none;
    color: #000;
    font-size: 13px;
    font-weight: 500;
}

.quick-links a:hover {
    background: white;
}

.info-text {
    font-size: 13px;
    color: #333;
    line-height: 1.8;
}

/* RESPONSIVE */
@media (max-width: 1000px) {
    .sidebar {
        width: 230px;
    }

    .main {
        margin-left: 230px;
        padding: 20px;
    }

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 650px) {
    .sidebar {
        width: 75px;
    }

    .brand h2,
    .brand p,
    .profile strong,
    .menu .menu-text {
        display: none;
    }

    .profile {
        justify-content: center;
        padding: 15px 5px;
    }

    .menu a {
        justify-content: center;
        padding: 15px 5px;
    }

    .main {
        margin-left: 75px;
        padding: 15px;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .welcome h1 {
        font-size: 22px;
    }
}
</style>
</head>

<body>

<aside class="sidebar">

    <div class="brand">
        <img src="assets/images/logo.png" alt="Logo Smart Education">
        <h2>Smart Education</h2>
        <p>Bimbingan Belajar Pare-pare</p>
    </div>

    <div class="profile">
        <div class="avatar">
            <?= htmlspecialchars($inisial); ?>
        </div>
        <strong>Super Admin</strong>
    </div>

    <nav class="menu">
        <a href="dashboard_superadmin.php" class="active">
            <span class="icon">⌂</span>
            <span class="menu-text">Dashboard</span>
        </a>

        <a href="modules/siswa/">
            <span class="icon">♙</span>
            <span class="menu-text">Data Siswa</span>
        </a>

        <a href="modules/paket/">
            <span class="icon">▱</span>
            <span class="menu-text">Paket Belajar</span>
        </a>

        <a href="modules/tutor/">
            <span class="icon">♙</span>
            <span class="menu-text">Data Tutor</span>
        </a>

        <a href="modules/ruangan/">
            <span class="icon">▯</span>
            <span class="menu-text">Ruangan</span>
        </a>

        <a href="modules/kelas/">
            <span class="icon">⌂</span>
            <span class="menu-text">Kelas</span>
        </a>

        <a href="modules/pembayaran/">
            <span class="icon">▣</span>
            <span class="menu-text">Pembayaran</span>
        </a>

        <a href="modules/rfm/">
            <span class="icon">▥</span>
            <span class="menu-text">Analisis RFM</span>
        </a>

        <a href="modules/jadwal/">
            <span class="icon">◷</span>
            <span class="menu-text">Penjadwalan</span>
        </a>

        <a href="dashboard_superadmin.php?logout=1">
            <span class="icon">⇥</span>
            <span class="menu-text">Logout</span>
        </a>
    </nav>

</aside>

<main class="main">

    <div class="topbar">
        <span class="notification">♧</span>
    </div>

    <section class="welcome">
        <h1>Selamat Datang, Super Admin</h1>
        <p>
            Kelola seluruh data dan aktivitas Bimbingan Belajar
            Smart Education di sini.
        </p>
    </section>

    <section class="stats">

        <div class="stat-card">
            <p>Total Siswa</p>
            <h2><?= number_format($totalSiswa, 0, ',', '.'); ?></h2>
        </div>

        <div class="stat-card">
            <p>Total Admin</p>
            <h2><?= number_format($totalAdmin, 0, ',', '.'); ?></h2>
        </div>

        <div class="stat-card">
            <p>Total Tutor</p>
            <h2><?= number_format($totalTutor, 0, ',', '.'); ?></h2>
        </div>

        <div class="stat-card">
            <p>Total Kelas</p>
            <h2><?= number_format($totalKelas, 0, ',', '.'); ?></h2>
        </div>

        <div class="stat-card">
            <p>Total Paket Belajar</p>
            <h2><?= number_format($totalPaket, 0, ',', '.'); ?></h2>
        </div>

        <div class="stat-card">
            <p>Total Pembayaran Lunas</p>
            <h2 class="money">
                Rp <?= number_format($totalPembayaran, 0, ',', '.'); ?>
            </h2>
        </div>

    </section>

    <section class="content-grid">

        <div class="panel">
            <h3>Akses Pengelolaan</h3>

            <div class="quick-links">
                <a href="modules/siswa/">Kelola Data Siswa</a>
                <a href="modules/tutor/">Kelola Data Tutor</a>
                <a href="modules/paket/">Kelola Paket Belajar</a>
                <a href="modules/kelas/">Kelola Data Kelas</a>
                <a href="modules/ruangan/">Kelola Ruangan</a>
                <a href="modules/pembayaran/">Kelola Pembayaran</a>
            </div>
        </div>

        <div class="panel">
            <h3>Informasi Sistem</h3>

            <p class="info-text">
                Dashboard Super Admin digunakan untuk memantau
                keseluruhan data Smart Education Parepare.
                Super Admin dapat melihat jumlah siswa, admin,
                tutor, kelas, paket belajar, serta total pembayaran
                yang telah lunas.
            </p>
        </div>

    </section>

</main>

</body>
</html>
