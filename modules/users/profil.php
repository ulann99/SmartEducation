```php
<?php
/**
 * PROFIL AKUN ADMIN
 * SMART EDUCATION
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';

$current_user = current_user();

if (!$current_user) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

$page_title = "Profil Akun";

$nama_user = $current_user['nama_lengkap']
    ?? $_SESSION['nama_lengkap']
    ?? $_SESSION['username']
    ?? 'Administrator';

$email_user = $current_user['email']
    ?? $_SESSION['email']
    ?? '-';

$role_user = $current_user['role']
    ?? $_SESSION['role']
    ?? 'admin';

$label_role = match ($role_user) {
    'superadmin' => 'Super Administrator',
    'admin'      => 'Administrator Akademik',
    default      => ucfirst($role_user)
};

$inisial = strtoupper(substr(trim($nama_user), 0, 1));
?>

<?php require_once $root . '/includes/header.php'; ?>

<?php require_once $root . '/includes/sidebar.php'; ?>

<?php require_once $root . '/includes/navbar.php'; ?>

<!-- ================= PROFIL AKUN ================= -->

<div class="profile-page">

    <!-- HEADER -->
    <div class="profile-heading">
        <div>
            <h3>Profil Akun</h3>
            <p>Informasi akun administrator Smart Education.</p>
        </div>
    </div>

    <!-- PROFIL UTAMA -->
    <div class="profile-card">

        <div class="profile-card-header">

            <div class="profile-avatar">
                <?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <div class="profile-main-info">
                <h4><?= htmlspecialchars($nama_user, ENT_QUOTES, 'UTF-8'); ?></h4>

                <span class="profile-role-badge">
                    <i class="bi bi-shield-check"></i>
                    <?= htmlspecialchars($label_role, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>

        </div>

        <div class="profile-divider"></div>

        <!-- INFORMASI AKUN -->
        <div class="profile-information">

            <div class="profile-information-item">
                <div class="profile-icon">
                    <i class="bi bi-person"></i>
                </div>

                <div>
                    <span class="profile-label">Nama Lengkap</span>
                    <strong><?= htmlspecialchars($nama_user, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>

            <div class="profile-information-item">
                <div class="profile-icon">
                    <i class="bi bi-envelope"></i>
                </div>

                <div>
                    <span class="profile-label">Email</span>
                    <strong><?= htmlspecialchars($email_user, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>

            <div class="profile-information-item">
                <div class="profile-icon">
                    <i class="bi bi-person-badge"></i>
                </div>

                <div>
                    <span class="profile-label">Role Akun</span>
                    <strong><?= htmlspecialchars($label_role, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>

            <div class="profile-information-item">
                <div class="profile-icon">
                    <i class="bi bi-lock"></i>
                </div>

                <div>
                    <span class="profile-label">Status Akun</span>
                    <strong class="account-active">
                        <span class="active-dot"></span>
                        Aktif
                    </strong>
                </div>
            </div>

        </div>

    </div>

    <!-- KEAMANAN AKUN -->
    <div class="security-card">

        <div class="security-card-heading">

            <div class="security-heading-icon">
                <i class="bi bi-shield-lock"></i>
            </div>

            <div>
                <h5>Keamanan Akun</h5>
                <p>Kelola keamanan akses akun administrator.</p>
            </div>

        </div>

        <div class="security-information">

            <div class="security-info-item">
                <i class="bi bi-check-circle-fill"></i>
                <div>
                    <strong>Akun Administrator</strong>
                    <span>Akun digunakan untuk mengelola sistem Smart Education.</span>
                </div>
            </div>

            <div class="security-info-item">
                <i class="bi bi-shield-check"></i>
                <div>
                    <strong>Akses Terverifikasi</strong>
                    <span>Anda masuk menggunakan akun administrator yang terdaftar.</span>
                </div>
            </div>

        </div>

    </div>

</div>

<style>

/* =========================
   HALAMAN PROFIL
========================= */

.profile-page {
    padding: 24px 28px 35px;
    background: #f5fbfa;
    min-height: calc(100vh - 140px);
}

/* HEADER */

.profile-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.profile-heading h3 {
    margin: 0 0 7px;
    color: #173b38;
    font-size: 22px;
    font-weight: 700;
}

.profile-heading p {
    margin: 0;
    color: #718582;
    font-size: 13px;
}

/* CARD PROFIL */

.profile-card {
    background: #ffffff;
    border: 1px solid #e4f0ed;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 4px 18px rgba(31, 92, 81, 0.04);
    margin-bottom: 22px;
}

.profile-card-header {
    display: flex;
    align-items: center;
    gap: 20px;
}

/* AVATAR */

.profile-avatar {
    width: 78px;
    height: 78px;
    min-width: 78px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #98EDE1;
    color: #176e61;
    font-size: 30px;
    font-weight: 700;
}

/* INFORMASI HEADER */

.profile-main-info h4 {
    margin: 0 0 10px;
    color: #173b38;
    font-size: 20px;
    font-weight: 700;
}

.profile-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 20px;
    background: #e8fdff;
    color: #278577;
    font-size: 12px;
    font-weight: 600;
}

/* PEMISAH */

.profile-divider {
    height: 1px;
    background: #edf3f1;
    margin: 25px 0;
}

/* INFORMASI */

.profile-information {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.profile-information-item {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 17px;
    border: 1px solid #edf3f1;
    border-radius: 12px;
    background: #ffffff;
}

.profile-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 12px;
    background: #e8fdff;
    color: #278577;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.profile-information-item > div:last-child {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.profile-label {
    font-size: 11px;
    color: #80918f;
}

.profile-information-item strong {
    font-size: 13px;
    color: #173b38;
    font-weight: 600;
    word-break: break-word;
}

/* STATUS */

.account-active {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #278577 !important;
}

.active-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #39b891;
}

/* =========================
   KEAMANAN
========================= */

.security-card {
    background: #ffffff;
    border: 1px solid #e4f0ed;
    border-radius: 18px;
    padding: 25px 28px;
    box-shadow: 0 4px 18px rgba(31, 92, 81, 0.04);
}

.security-card-heading {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 22px;
}

.security-heading-icon {
    width: 45px;
    height: 45px;
    border-radius: 13px;
    background: #98EDE1;
    color: #217c70;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.security-card-heading h5 {
    margin: 0 0 5px;
    font-size: 16px;
    color: #173b38;
    font-weight: 700;
}

.security-card-heading p {
    margin: 0;
    color: #718582;
    font-size: 12px;
}

.security-information {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.security-info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    border-radius: 12px;
    background: #f8fcfb;
}

.security-info-item > i {
    color: #32a78b;
    font-size: 18px;
    margin-top: 2px;
}

.security-info-item div {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.security-info-item strong {
    color: #173b38;
    font-size: 13px;
}

.security-info-item span {
    color: #718582;
    font-size: 12px;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 768px) {

    .profile-page {
        padding: 20px 15px;
    }

    .profile-card,
    .security-card {
        padding: 20px;
    }

    .profile-information {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .profile-avatar {
        width: 65px;
        height: 65px;
        min-width: 65px;
        font-size: 25px;
    }

    .profile-main-info h4 {
        font-size: 17px;
    }
}

@media (max-width: 480px) {

    .profile-card-header {
        gap: 13px;
    }

    .profile-information-item {
        padding: 13px;
    }

}

</style>

<?php require_once $root . '/includes/footer.php'; ?>
```