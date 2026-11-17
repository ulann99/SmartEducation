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
?>

<aside class="app-sidebar">
    <!-- Brand / Logo -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div class="sidebar-brand-text">
            <h5>SMART EDU</h5>
            <small>PAREPARE BIMBEL</small>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-menu">
        <?php if ($role === 'superadmin' || $role === 'admin'): ?>
            <!-- ADMIN & SUPER ADMIN MENU -->
            <div class="sidebar-heading">UTAMA</div>
            
            <?php 
            $dash_url = ($role === 'superadmin') ? 'modules/dashboard/superadmin.php' : 'modules/dashboard/admin.php'; 
            ?>
            <a href="<?= base_url($dash_url) ?>" class="sidebar-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>1. Dashboard</span>
            </a>

            <div class="sidebar-heading">DATA MASTER</div>
            
            <a href="<?= base_url('modules/siswa/index.php') ?>" class="sidebar-link <?= ($current_page === 'siswa') ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>2. Data Siswa</span>
            </a>

            <a href="<?= base_url('modules/paket/index.php') ?>" class="sidebar-link <?= ($current_page === 'paket') ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>3. Paket Belajar</span>
            </a>

            <a href="<?= base_url('modules/tutor/index.php') ?>" class="sidebar-link <?= ($current_page === 'tutor') ? 'active' : '' ?>">
                <i class="bi bi-person-badge-fill"></i>
                <span>4. Data Tutor</span>
            </a>

            <a href="<?= base_url('modules/ruangan/index.php') ?>" class="sidebar-link <?= ($current_page === 'ruangan') ? 'active' : '' ?>">
                <i class="bi bi-door-open-fill"></i>
                <span>5. Data Ruangan</span>
            </a>

            <a href="<?= base_url('modules/kelas/index.php') ?>" class="sidebar-link <?= ($current_page === 'kelas') ? 'active' : '' ?>">
                <i class="bi bi-collection-fill"></i>
                <span>6. Data Kelas</span>
            </a>

            <div class="sidebar-heading">KEUANGAN</div>

            <a href="<?= base_url('modules/pembayaran/index.php') ?>" class="sidebar-link <?= ($current_page === 'pembayaran') ? 'active' : '' ?>">
                <i class="bi bi-cash-stack"></i>
                <span>7. Pembayaran</span>
            </a>

            <a href="<?= base_url('modules/riwayat_pembayaran/index.php') ?>" class="sidebar-link <?= ($current_page === 'riwayat_pembayaran') ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>
                <span>8. Riwayat Pembayaran</span>
            </a>

            <div class="sidebar-heading">ANALISIS & JADWAL</div>

            <a href="<?= base_url('modules/rfm/index.php') ?>" class="sidebar-link <?= ($current_page === 'rfm') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>9. Analisis RFM</span>
            </a>

            <a href="<?= base_url('modules/penjadwalan/index.php') ?>" class="sidebar-link <?= ($current_page === 'penjadwalan') ? 'active' : '' ?>">
                <i class="bi bi-cpu-fill"></i>
                <span>10. Penjadwalan Otomatis</span>
            </a>

            <a href="<?= base_url('modules/jadwal/index.php') ?>" class="sidebar-link <?= ($current_page === 'jadwal') ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i>
                <span>11. Informasi Jadwal</span>
            </a>

            <?php if ($role === 'superadmin'): ?>
                <div class="sidebar-heading">SISTEM & PENGGUNA</div>
                <a href="<?= base_url('modules/users/index.php') ?>" class="sidebar-link <?= ($current_page === 'users') ? 'active' : '' ?>">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>12. Pengguna</span>
                </a>
            <?php endif; ?>

        <?php elseif ($role === 'siswa'): ?>
            <!-- SISWA MENU -->
            <div class="sidebar-heading">SISWA</div>

            <a href="<?= base_url('modules/dashboard/siswa.php') ?>" class="sidebar-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>1. Dashboard</span>
            </a>

            <a href="<?= base_url('modules/profil/index.php') ?>" class="sidebar-link <?= ($current_page === 'profil') ? 'active' : '' ?>">
                <i class="bi bi-person-circle"></i>
                <span>2. Profil Saya</span>
            </a>

            <a href="<?= base_url('modules/paket/index.php') ?>" class="sidebar-link <?= ($current_page === 'paket') ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>3. Paket Belajar</span>
            </a>

            <a href="<?= base_url('modules/jadwal/index.php') ?>" class="sidebar-link <?= ($current_page === 'jadwal') ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i>
                <span>4. Informasi Jadwal</span>
            </a>

            <a href="<?= base_url('modules/pembayaran/bayar_siswa.php') ?>" class="sidebar-link <?= ($current_page === 'pembayaran') ? 'active' : '' ?>">
                <i class="bi bi-wallet2"></i>
                <span>5. Pembayaran</span>
            </a>

            <a href="<?= base_url('modules/riwayat_pembayaran/index.php') ?>" class="sidebar-link <?= ($current_page === 'riwayat_pembayaran') ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i>
                <span>6. Riwayat Pembayaran</span>
            </a>

        <?php endif; ?>

        <div class="sidebar-heading">AKUN</div>
        <a href="<?= base_url('modules/auth/logout.php') ?>" class="sidebar-link text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <span><?= ($role === 'superadmin' ? '13' : ($role === 'admin' ? '12' : '7')) ?>. Logout</span>
        </a>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between text-muted small">
            <div>
                <i class="bi bi-geo-alt-fill text-mint me-1"></i>
                <span>Parepare, Sulsel</span>
            </div>
            <span class="badge bg-mint text-dark">v2.0</span>
        </div>
    </div>
</aside>

