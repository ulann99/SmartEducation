
<?php
/**
 * Navbar Template
 * SMART EDUCATION
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__));
}

require_once __DIR__ . '/../config/helper.php';

$current_user = current_user();
$flash = get_flash();

$nama_user = $current_user['nama_lengkap'] ?? 'Administrator';
$role_user = $current_user['role'] ?? 'admin';

$page_heading = $page_title ?? 'Dashboard';

$inisial = strtoupper(substr(trim($nama_user), 0, 1));

$is_siswa = in_array($role_user, ['siswa', 'siswa_orang_tua'], true);

$label_role = ($role_user === 'superadmin')
    ? 'Super Admin'
    : (($role_user === 'admin') ? 'Admin' : 'Siswa');
?>

<div class="app-main">

    <header class="app-navbar">

        <!-- JUDUL HALAMAN -->
        <div class="d-flex align-items-center gap-3">

            <button
                class="btn btn-outline-secondary d-lg-none"
                id="sidebarToggle"
                type="button"
                aria-label="Toggle Navigation"
            >
                <i class="bi bi-list fs-5"></i>
            </button>

            <h6 class="mb-0 fw-bold text-mint-deep">
                <?= htmlspecialchars($page_heading, ENT_QUOTES, 'UTF-8'); ?>
            </h6>

        </div>

        <!-- BAGIAN KANAN -->
        <div class="d-flex align-items-center gap-3">

            <!-- NOTIFIKASI -->
            <a
                href="#"
                class="navbar-notification"
                aria-label="Notifikasi"
                title="Notifikasi"
            >
                <i class="bi bi-bell"></i>
            </a>

            <!-- PROFIL PENGGUNA -->
            <div class="dropdown">

                <button
                    type="button"
                    class="user-profile dropdown-toggle border-0 bg-transparent"
                    id="userMenuDropdown"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >

                    <!-- INISIAL PROFIL -->
                    <span class="user-initial">
                        <?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8'); ?>
                    </span>

                    <!-- INFORMASI PENGGUNA -->
                    <span class="user-profile-info text-start">

                        <span class="user-profile-name">
                            <?= htmlspecialchars($nama_user, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <small class="user-profile-role">
                            <?= htmlspecialchars(
                                ($role_user === 'admin')
                                    ? 'Administrator Akademik'
                                    : $label_role,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </small>

                    </span>

                </button>

                <!-- DROPDOWN -->
                <ul
                    class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2"
                    aria-labelledby="userMenuDropdown"
                >

                    <!-- PROFIL AKUN ADMIN / SUPERADMIN -->
                    <?php if ($role_user === 'admin' || $role_user === 'superadmin'): ?>

                        <li>
                            <a
                                class="dropdown-item py-2"
                                href="<?= base_url('modules/users/profil.php'); ?>"
                            >
                                <i class="bi bi-person-circle me-2 text-primary"></i>
                                Profil Akun
                            </a>
                        </li>

                    <?php endif; ?>

                    <!-- DROPDOWN SISWA -->
                    <?php if ($is_siswa): ?>

                        <li>
                            <a
                                class="dropdown-item py-2"
                                href="<?= base_url('modules/siswa/datapribadi.php'); ?>"
                            >
                                <i class="bi bi-person me-2 text-primary"></i>
                                Data Pribadi
                            </a>
                        </li>

                    <?php endif; ?>

                </ul>

            </div>

        </div>

    </header>

    <main class="content-body">

        <?php if ($flash): ?>

            <div
                class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show shadow-sm rounded-3 border-0 d-flex align-items-center"
                role="alert"
            >

                <i class="bi bi-<?= (
                    $flash['type'] === 'success'
                    ? 'check-circle-fill text-success'
                    : (
                        $flash['type'] === 'danger'
                        ? 'exclamation-octagon-fill text-danger'
                        : 'info-circle-fill text-info'
                    )
                ); ?> fs-5 me-2"></i>

                <div class="flex-grow-1">
                    <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>

            </div>

        <?php endif; ?>

<style>
/* NAVBAR */
.app-navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 70px;
}

/* NOTIFIKASI */
.navbar-notification {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #287E72;
    font-size: 20px;
    text-decoration: none;
    border-radius: 50%;
    transition: .2s ease;
}

.navbar-notification:hover {
    background: #E8FDFF;
    color: #173B38;
}

/* PROFIL */
.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    text-decoration: none;
    padding: 5px;
    border-radius: 10px;
}

.user-profile:hover {
    background: #F5FBFA;
}

/* INISIAL FOTO */
.user-initial {
    width: 42px;
    height: 42px;
    min-width: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #98EDE1;
    color: #173B38;
    font-size: 16px;
    font-weight: 700;
}

/* INFORMASI PENGGUNA */
.user-profile-info {
    display: flex;
    flex-direction: column;
    line-height: 1.4;
}

.user-profile-name {
    color: #173B38;
    font-size: 13px;
    font-weight: 600;
}

.user-profile-role {
    color: #718582;
    font-size: 11px;
}

/* DROPDOWN */
.app-navbar .dropdown-menu {
    min-width: 190px;
    padding: 8px;
    margin-top: 10px !important;
    border: 1px solid #E2EFEC;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(23, 59, 56, .08);
}

.app-navbar .dropdown-item {
    color: #173B38;
    font-size: 13px;
    padding: 10px 12px;
    border-radius: 8px;
}

.app-navbar .dropdown-item:hover {
    background: #E8FDFF;
}

/* RESPONSIVE */
@media (max-width: 575px) {

    .user-profile-name {
        font-size: 11px;
    }

    .user-profile-role {
        font-size: 10px;
    }

    .user-initial {
        width: 34px;
        height: 34px;
        min-width: 34px;
    }
}
</style>
