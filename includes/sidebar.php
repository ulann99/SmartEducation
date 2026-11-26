
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
            <small>Bimbingan Belajar Parepare</small>
        </div>

    </div>

    <!-- MENU NAVIGASI -->
    <div class="sidebar-menu">

        <?php if ($role === 'superadmin' || $role === 'admin'): ?>

            <!-- MENU UTAMA -->
            <div class="sidebar-section-title">
                Menu Utama
            </div>

            <a href="<?= base_url($dash_url) ?>"
               class="sidebar-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-house"></i>
                <span>Dashboard</span>
            </a>

            <!-- MENU KHUSUS SUPER ADMIN -->
            <?php if ($role === 'superadmin'): ?>

                <div class="sidebar-section-title">
                    Pengelolaan Sistem
                </div>

                <a href="<?= base_url('modules/users/index.php') ?>"
                   class="sidebar-link <?= ($current_page === 'users') ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    <span>Manajemen Pengguna</span>
                </a>

                <a href="<?= base_url('modules/users/administrator.php') ?>"
                   class="sidebar-link <?= ($current_page === 'administrator') ? 'active' : '' ?>">
                    <i class="bi bi-person-gear"></i>
                    <span>Manajemen Administrator</span>
                </a>

                <a href="<?= base_url('modules/users/hak_akses.php') ?>"
                   class="sidebar-link <?= ($current_page === 'hak_akses') ? 'active' : '' ?>">
                    <i class="bi bi-gear"></i>
                    <span>Pengaturan Hak Akses</span>
                </a>

            <?php endif; ?>

            <a href="<?= base_url('modules/siswa/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'siswa') ? 'active' : '' ?>">
                <i class="bi bi-person"></i>
                <span>Data Siswa</span>
            </a>

            <a href="<?= base_url('modules/paket/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'paket') ? 'active' : '' ?>">
                <i class="bi bi-journal"></i>
                <span>Paket Belajar</span>
            </a>

            <a href="<?= base_url('modules/tutor/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'tutor') ? 'active' : '' ?>">
                <i class="bi bi-person-badge"></i>
                <span>Data Tutor</span>
            </a>

            <a href="<?= base_url('modules/ruangan/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'ruangan') ? 'active' : '' ?>">
                <i class="bi bi-door-closed"></i>
                <span>Ruangan</span>
            </a>

            <a href="<?= base_url('modules/kelas/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'kelas') ? 'active' : '' ?>">
                <i class="bi bi-house-door"></i>
                <span>Kelas</span>
            </a>

            <a href="<?= base_url('modules/pembayaran/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'pembayaran') ? 'active' : '' ?>">
                <i class="bi bi-wallet"></i>
                <span>Pembayaran</span>
            </a>

            <a href="<?= base_url('modules/rfm/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'rfm') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart"></i>
                <span>Analisis RFM</span>
            </a>

            <a href="<?= base_url('modules/jadwal/index.php') ?>"
               class="sidebar-link <?= ($current_page === 'penjadwalan') ? 'active' : '' ?>">
                <i class="bi bi-clock"></i>
                <span>Penjadwalan Otomatis</span>
            </a>

            <a href="<?= base_url('modules/jadwal/informasi.php') ?>"
               class="sidebar-link <?= ($current_page === 'informasi_jadwal') ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i>
                <span>Informasi Jadwal</span>
            </a>

        <?php elseif ($role === 'siswa' || $role === 'siswa_orang_tua'): ?>

            <!-- MENU UTAMA SISWA -->
            <div class="sidebar-section-title">
                Menu Utama
            </div>

            <a href="<?= base_url('dashboard_siswa.php') ?>"
               class="sidebar-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-house"></i>
                <span>Dashboard</span>
            </a>

            <div class="sidebar-section-title">
                Informasi Siswa
            </div>

            <a href="<?= base_url('modules/siswa/datapribadi.php') ?>"
               class="sidebar-link <?= ($current_page === 'datapribadi') ? 'active' : '' ?>">
                <i class="bi bi-person"></i>
                <span>Data Pribadi</span>
            </a>

            <a href="<?= base_url('modules/siswa/paketbelajar.php') ?>"
               class="sidebar-link <?= ($current_page === 'paketbelajar') ? 'active' : '' ?>">
                <i class="bi bi-journal"></i>
                <span>Paket Belajar</span>
            </a>

            <a href="<?= base_url('modules/siswa/jadwal.php') ?>"
               class="sidebar-link <?= ($current_page === 'jadwal') ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i>
                <span>Informasi Jadwal</span>
            </a>

            <a href="<?= base_url('modules/siswa/pembayaran.php') ?>"
               class="sidebar-link <?= ($current_page === 'pembayaran_siswa') ? 'active' : '' ?>">
                <i class="bi bi-wallet"></i>
                <span>Pembayaran</span>
            </a>

            <a href="<?= base_url('modules/siswa/riwayatpembayaran.php') ?>"
               class="sidebar-link <?= ($current_page === 'riwayatpembayaran') ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i>
                <span>Riwayat Pembayaran</span>
            </a>

        <?php endif; ?>

        <!-- MENU AKUN -->
        <div class="sidebar-section-title account-title">
            Akun
        </div>

        <a href="<?= base_url('modules/auth/logout.php') ?>"
           class="sidebar-link logout-link">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>

    </div>
</aside>

<style>

/* =====================================
   SIDEBAR BRAND
===================================== */

.app-sidebar .sidebar-brand {
    text-align: center;
    padding: 12px 10px 28px;
}

.app-sidebar .sidebar-brand-icon {
    width: 58px;
    height: 58px;
    margin: 0 auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.app-sidebar .sidebar-brand-icon .brand-logo {
    width: 52px;
    height: 52px;
    object-fit: contain;
    border-radius: 50%;
}

.app-sidebar .sidebar-brand-text h5 {
    margin: 0 0 5px;
    color: #173B38;
    font-size: 17px;
    font-weight: 800;
}

.app-sidebar .sidebar-brand-text small {
    color: #315E57;
    font-size: 11px;
}

/* =====================================
   SIDEBAR MENU
===================================== */

.app-sidebar .sidebar-menu {
    display: flex !important;
    flex-direction: column !important;
    justify-content: flex-start !important;
    align-items: stretch !important;
    gap: 3px !important;
    width: 100%;
    height: auto !important;
    padding: 0 12px 25px;
}

/* JUDUL KELOMPOK MENU */

.app-sidebar .sidebar-section-title {
    display: block;
    width: 100%;
    padding: 14px 12px 9px;
    color: #47746D;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* LINK MENU */

.app-sidebar .sidebar-menu > a.sidebar-link {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: flex-start !important;
    width: 100% !important;
    height: 42px !important;
    min-height: 42px !important;
    flex: 0 0 42px !important;
    padding: 8px 12px !important;
    margin: 0 !important;
    gap: 12px !important;
    border: none;
    border-radius: 10px;
    background: transparent;
    color: #173B38;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap !important;
    transition: background .2s ease, color .2s ease;
}

/* IKON MENU */

.app-sidebar .sidebar-menu > a.sidebar-link > i {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;

    width: 22px !important;
    min-width: 22px !important;
    height: auto !important;

    flex: 0 0 22px !important;

    padding: 0 !important;
    margin: 0 !important;

    background: transparent !important;

    font-size: 17px;
    line-height: 1;
}

/* TULISAN MENU */

.app-sidebar .sidebar-menu > a.sidebar-link > span {
    display: block !important;

    flex: 1 1 auto !important;
    min-width: 0;

    margin: 0 !important;
    padding: 0 !important;

    white-space: nowrap !important;
    overflow: visible;

    line-height: 1.4;
    text-align: left;
}

/* HOVER DAN MENU AKTIF */

.app-sidebar .sidebar-menu > a.sidebar-link:hover,
.app-sidebar .sidebar-menu > a.sidebar-link.active {
    background: #E8FDFF !important;
    color: #176D63 !important;
}

/* LOGOUT */

.app-sidebar .sidebar-section-title.account-title {
    margin-top: 12px;
}

.app-sidebar .sidebar-menu > a.sidebar-link.logout-link {
    margin-top: 8px !important;
    color: #C83232;
}

.app-sidebar .sidebar-menu > a.sidebar-link.logout-link:hover {
    background: #FFF0F0 !important;
    color: #A93232 !important;
}

/* =====================================
   RESPONSIVE
===================================== */

@media (max-width: 650px) {

    .app-sidebar .sidebar-brand {
        padding: 8px 5px 20px;
    }

    .app-sidebar .sidebar-brand-text {
        display: none;
    }

    .app-sidebar .sidebar-section-title {
        display: none;
    }

    .app-sidebar .sidebar-menu {
        padding: 0 7px;
    }

    .app-sidebar .sidebar-menu > a.sidebar-link {
        justify-content: center !important;
        padding: 10px 4px !important;
    }

    .app-sidebar .sidebar-menu > a.sidebar-link > span {
        display: none !important;
    }
}

</style>
