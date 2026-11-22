
<?php
/**
 * Sidebar Navigation
 * SMART EDUCATION PAREPARE
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__));
}

require_once __DIR__ . '/../config/helper.php';

$role = $_SESSION['role'] ?? '';
$current_page = $current_page ?? '';

$dash_url = ($role === 'superadmin')
    ? 'dashboard_superadmin.php'
    : (($role === 'admin')
        ? 'dashboard.php'
        : 'dashboard_siswa.php');
?>

<aside class="app-sidebar">

    <!-- LOGO DAN IDENTITAS -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <img
                src="<?= base_url('assets/images/logo.png') ?>"
                alt="Logo Smart Education"
                class="brand-logo"
            >
        </div>

        <div class="sidebar-brand-text">
            <h5>Smart Education</h5>
            <small>Bimbingan Belajar Pare-pare</small>
        </div>
    </div>

    <!-- MENU NAVIGASI -->
    <div class="sidebar-menu">

        <?php if ($role === 'superadmin' || $role === 'admin'): ?>

            <!-- DASHBOARD -->
            <a href="<?= base_url($dash_url) ?>"
               class="sidebar-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-house"></i>
                <span>Dashboard</span>
            </a>

            <!-- DATA SISWA -->
            <a href="<?= base_url('modules/siswa/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'siswa') ? 'active' : '' ?>">
                <i class="bi bi-person"></i>
                <span>Data Siswa</span>
            </a>

            <!-- PAKET BELAJAR -->
            <a href="<?= base_url('modules/paket/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'paket') ? 'active' : '' ?>">
                <i class="bi bi-journal"></i>
                <span>Paket Belajar</span>
            </a>

            <!-- DATA TUTOR -->
            <a href="<?= base_url('modules/tutor/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'tutor') ? 'active' : '' ?>">
                <i class="bi bi-person-badge"></i>
                <span>Data Tutor</span>
            </a>

            <!-- RUANGAN -->
            <a href="<?= base_url('modules/ruangan/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'ruangan') ? 'active' : '' ?>">
                <i class="bi bi-door-closed"></i>
                <span>Ruangan</span>
            </a>

            <!-- KELAS -->
            <a href="<?= base_url('modules/kelas/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'kelas') ? 'active' : '' ?>">
                <i class="bi bi-house-door"></i>
                <span>Kelas</span>
            </a>

            <!-- PEMBAYARAN -->
            <a href="<?= base_url('modules/pembayaran/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'pembayaran') ? 'active' : '' ?>">
                <i class="bi bi-wallet"></i>
                <span>Pembayaran</span>
            </a>

            <!-- ANALISIS RFM -->
            <a href="<?= base_url('modules/rfm/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'rfm') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart"></i>
                <span>Analisis RFM</span>
            </a>

            <!-- PENJADWALAN OTOMATIS -->
            <a href="<?= base_url('modules/jadwal/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'penjadwalan') ? 'active' : '' ?>">
                <i class="bi bi-clock"></i>
                <span>Penjadwalan Otomatis</span>
            </a>

            <!-- INFORMASI JADWAL -->
            <a href="<?= base_url('modules/jadwal/informasi.php') ?>"
               class="sidebar-link <?= ($current_page === 'informasi_jadwal') ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i>
                <span>Informasi Jadwal</span>
            </a>

            <!-- MENU KHUSUS SUPERADMIN -->
            <?php if ($role === 'superadmin'): ?>

                <a href="<?= base_url('modules/users/index.php') ?>"
                   class="sidebar-link <?= ($current_page === 'users') ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    <span>Pengguna</span>
                </a>

            <?php endif; ?>

        <?php elseif ($role === 'siswa' || $role === 'siswa_orang_tua'): ?>

            <!-- DASHBOARD SISWA -->
            <a href="<?= base_url('dashboard_siswa.php') ?>"
               class="sidebar-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-house"></i>
                <span>Dashboard</span>
            </a>

            <!-- DATA PRIBADI -->
            <a href="<?= base_url('modules/siswa/datapribadi.php') ?>"
               class="sidebar-link <?= ($current_page === 'datapribadi') ? 'active' : '' ?>">
                <i class="bi bi-person"></i>
                <span>Data Pribadi</span>
            </a>

            <!-- PAKET BELAJAR -->
            <a href="<?= base_url('modules/siswa/paketbelajar.php') ?>"
               class="sidebar-link <?= ($current_page === 'paketbelajar') ? 'active' : '' ?>">
                <i class="bi bi-journal"></i>
                <span>Paket Belajar</span>
            </a>

            <!-- INFORMASI JADWAL -->
            <a href="<?= base_url('modules/siswa/jadwal.php') ?>"
               class="sidebar-link <?= ($current_page === 'jadwal') ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i>
                <span>Informasi Jadwal</span>
            </a>

            <!-- PEMBAYARAN -->
            <a href="<?= base_url('modules/siswa/pembayaran.php') ?>"
               class="sidebar-link <?= ($current_page === 'pembayaran_siswa') ? 'active' : '' ?>">
                <i class="bi bi-wallet"></i>
                <span>Pembayaran</span>
            </a>

            <!-- RIWAYAT PEMBAYARAN -->
            <a href="<?= base_url('modules/siswa/riwayatpembayaran.php') ?>"
               class="sidebar-link <?= ($current_page === 'riwayatpembayaran') ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i>
                <span>Riwayat Pembayaran</span>
            </a>

        <?php endif; ?>

        <!-- LOGOUT (PALING BAWAH) -->
        <a href="<?= base_url('modules/auth/logout.php') ?>"
           class="sidebar-link text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>

    </div>
</aside>

<style>
.sidebar-brand {
    text-align: center;
}

.sidebar-brand-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
}

.sidebar-brand-icon .brand-logo {
    width: 42px;
    height: 42px;
    object-fit: contain;
    border-radius: 50%;
}

.sidebar-brand-text h5 {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 3px;
}

.sidebar-brand-text small {
    font-size: 10px;
}
</style>
