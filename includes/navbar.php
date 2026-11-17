<?php
/**
 * Navbar Template
 * SMART EDUCATION PAREPARE
 */
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__));
}
require_once __DIR__ . '/../config/helper.php';

$current_user = current_user();
$flash = get_flash();
?>

<div class="app-main">
    <!-- Navbar Header -->
    <header class="app-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle Navigation">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="d-none d-md-block">
                <h6 class="mb-0 fw-bold text-mint-deep">Smart Education Parepare</h6>
                <small class="text-muted"><i class="bi bi-calendar-event me-1 text-mint"></i><?= format_tanggal(date('Y-m-d'), true) ?></small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Role Badge -->
            <span class="badge <?= ($current_user['role'] === 'superadmin' ? 'bg-danger text-white' : ($current_user['role'] === 'admin' ? 'bg-dark-mint text-white' : 'badge-mint')) ?> px-3 py-2 rounded-pill">
                <i class="bi bi-person-fill-gear me-1"></i>
                <?= strtoupper($current_user['role']) ?>
            </span>

            <!-- User Dropdown -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($current_user['nama_lengkap']) ?>&background=98EDE1&color=042f2e&bold=true" alt="Avatar" class="user-avatar me-2">
                    <div class="d-none d-sm-block text-start">
                        <span class="fw-semibold text-dark d-block fs-6"><?= htmlspecialchars($current_user['nama_lengkap']) ?></span>
                        <small class="text-muted">@<?= htmlspecialchars($current_user['username']) ?></small>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2" aria-labelledby="userMenuDropdown">
                    <li class="px-3 py-2 border-bottom">
                        <p class="mb-0 fw-bold small text-dark"><?= htmlspecialchars($current_user['nama_lengkap']) ?></p>
                        <small class="text-muted"><?= htmlspecialchars($current_user['email'] ?? '') ?></small>
                    </li>
                    <?php if ($current_user['role'] === 'siswa'): ?>
                        <li><a class="dropdown-item py-2" href="<?= base_url('modules/profil/index.php') ?>"><i class="bi bi-person me-2 text-primary"></i>Profil Saya</a></li>
                        <li><a class="dropdown-item py-2" href="<?= base_url('modules/pembayaran/bayar_siswa.php') ?>"><i class="bi bi-wallet2 me-2 text-success"></i>Tagihan & Pembayaran</a></li>
                    <?php endif; ?>
                    <?php if ($current_user['role'] === 'superadmin'): ?>
                        <li><a class="dropdown-item py-2" href="<?= base_url('modules/users/index.php') ?>"><i class="bi bi-people me-2 text-primary"></i>Kelola Pengguna</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= base_url('modules/auth/logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Keluar / Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="content-body">
        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm rounded-3 border-0 d-flex align-items-center" role="alert">
                <i class="bi bi-<?= ($flash['type'] === 'success' ? 'check-circle-fill text-success' : ($flash['type'] === 'danger' ? 'exclamation-octagon-fill text-danger' : 'info-circle-fill text-info')) ?> fs-5 me-2"></i>
                <div class="flex-grow-1">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

