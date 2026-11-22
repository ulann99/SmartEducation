<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="portal-sidebar">

    <!-- BRAND -->
    <div class="portal-brand">
        <img src="../assets/images/logo.png"
             alt="Smart Education"
             class="portal-logo">

        <h3>Smart Education</h3>
        <p>Bimbingan Belajar Parepare</p>
    </div>

    <!-- MENU -->
    <nav class="portal-menu">

        <!-- DASHBOARD -->
        <a href="dashboard_siswa.php"
           class="<?= $current_page == 'dashboard_siswa.php' ? 'active' : '' ?>">
            <i class="bi bi-house-door"></i>
            <span>Dashboard</span>
        </a>

        <!-- DATA PRIBADI -->
        <a href="datapribadi.php"
           class="<?= $current_page == 'datapribadi.php' ? 'active' : '' ?>">
            <i class="bi bi-person"></i>
            <span>Data Pribadi</span>
        </a>

        <!-- PAKET BELAJAR -->
        <a href="paketbelajar.php"
           class="<?= $current_page == 'paketbelajar.php' ? 'active' : '' ?>">
            <i class="bi bi-book"></i>
            <span>Paket Belajar</span>
        </a>

        <!-- INFORMASI JADWAL -->
        <a href="jadwal.php"
           class="<?= $current_page == 'jadwal.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar3"></i>
            <span>Informasi Jadwal</span>
        </a>

        <!-- PEMBAYARAN -->
        <a href="pembayaran.php"
           class="<?= $current_page == 'pembayaran.php' ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i>
            <span>Pembayaran</span>
        </a>

        <!-- RIWAYAT PEMBAYARAN -->
        <a href="riwayatpembayaran.php"
           class="<?= $current_page == 'riwayatpembayaran.php' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i>
            <span>Riwayat Pembayaran</span>
        </a>

    </nav>

    <!-- LOGOUT -->
    <div class="portal-sidebar-bottom">
        <a href="../modules/auth/logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>

</aside>
