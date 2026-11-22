<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$namaNavbar = $_SESSION['nama_lengkap']
    ?? $_SESSION['nama']
    ?? $_SESSION['username']
    ?? 'Siswa';

$roleNavbar = $_SESSION['role'] ?? 'siswa';

$halamanAktif = basename($_SERVER['PHP_SELF']);

$judulNavbar = match ($halamanAktif) {
    'dashboard_siswa.php' => 'Dashboard',
    'datapribadi.php' => 'Data Pribadi',
    'paketbelajar.php' => 'Paket Belajar',
    'jadwal.php' => 'Informasi Jadwal',
    'pembayaran.php' => 'Pembayaran',
    'riwayatpembayaran.php' => 'Riwayat Pembayaran',
    'profilakun.php' => 'Profil Akun',
    'notifikasi.php' => 'Notifikasi',
    default => 'Smart Education'
};

// FOTO PROFIL
$fotoNavbar = "img/profilsiswa.jpg";

if (!empty($fotoNavbar)) {
    if (strpos($fotoNavbar, 'uploads/') === 0) {
        $fotoNavbar = '../' . $fotoNavbar;
    }
}

$inisialNavbar = strtoupper(substr($namaNavbar, 0, 1));
?>

<nav class="portal-navbar">

    <!-- JUDUL HALAMAN -->
    <div class="portal-navbar-title">
        <h5><?= htmlspecialchars($judulNavbar, ENT_QUOTES, 'UTF-8'); ?></h5>
    </div>

    <!-- BAGIAN KANAN -->
    <div class="portal-navbar-actions">

        <!-- NOTIFIKASI -->
        <a href="notifikasi.php"
           class="portal-notif-btn"
           title="Notifikasi"
           aria-label="Buka Notifikasi">
            <i class="bi bi-bell"></i>
        </a>

        <!-- PROFIL PENGGUNA -->
        <div class="portal-profile">

            <!-- FOTO PROFIL -->
            <div class="portal-avatar-wrapper">

                <?php if (!empty($fotoNavbar)): ?>

                    <img
                        src="<?= htmlspecialchars($fotoNavbar, ENT_QUOTES, 'UTF-8'); ?>"
                        class="portal-avatar"
                        alt="Foto Profil"
                    >

                    <div class="portal-avatar portal-avatar-initial" style="display:none;">
                        <?= htmlspecialchars($inisialNavbar, ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                <?php else: ?>

                    <div class="portal-avatar portal-avatar-initial">
                        <?= htmlspecialchars($inisialNavbar, ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                <?php endif; ?>

            </div>

            <!-- INFORMASI PENGGUNA -->
            <div class="portal-profile-info">
                <strong>
                    <?= htmlspecialchars($namaNavbar, ENT_QUOTES, 'UTF-8'); ?>
                </strong>

                <span>Siswa / Orang Tua</span>
            </div>

            <!-- DROPDOWN PROFIL -->
            <div class="dropdown">

                <button
                    class="portal-profile-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <i class="bi bi-chevron-down"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end portal-profile-menu">

                    <li>
                        <a class="dropdown-item" href="profilakun.php">
                            <i class="bi bi-person-circle me-2"></i>
                            Profil Akun
                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </div>

</nav>

<style>

/* NAVBAR */
.portal-navbar {
    height: 75px;
    width: 100%;
    background: #ffffff;
    border-bottom: 1px solid #e5efec;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28px;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-sizing: border-box;
}

/* JUDUL */
.portal-navbar-title {
    display: flex;
    align-items: center;
}

.portal-navbar-title h5 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: #173b38;
}

/* BAGIAN KANAN */
.portal-navbar-actions {
    display: flex;
    align-items: center;
    gap: 24px;
}

/* NOTIFIKASI */
.portal-notif-btn {
    width: 42px;
    height: 42px;
    border: none;
    background: transparent;
    color: #287e72;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 50%;
    transition: .2s;
    text-decoration: none;
}

.portal-notif-btn:hover {
    background: #e8fdff;
    color: #205f56;
}

.portal-notif-btn:focus-visible {
    outline: 2px solid #98ede1;
    outline-offset: 3px;
}

/* PROFIL */
.portal-profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* WRAPPER FOTO */
.portal-avatar-wrapper {
    width: 46px;
    height: 46px;
    min-width: 46px;
    position: relative;
}

/* FOTO */
.portal-avatar {
    width: 46px;
    height: 46px;
    min-width: 46px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
}

/* AVATAR INISIAL */
.portal-avatar-initial {
    background: #98ede1;
    color: #187769;
    font-size: 17px;
    font-weight: 700;
    align-items: center;
    justify-content: center;
}

/* NAMA */
.portal-profile-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.portal-profile-info strong {
    color: #173b38;
    font-size: 13px;
    font-weight: 600;
}

.portal-profile-info span {
    color: #718582;
    font-size: 11px;
}

/* DROPDOWN TOGGLE */
.portal-profile-toggle {
    border: none;
    background: transparent;
    color: #718582;
    padding: 8px;
    cursor: pointer;
}

.portal-profile-toggle:hover {
    color: #287e72;
}

/* DROPDOWN MENU */
.portal-profile-menu {
    border: 1px solid #e2efec;
    border-radius: 12px;
    padding: 8px;
    min-width: 190px;
    box-shadow: 0 8px 25px rgba(23, 59, 56, .08);
    margin-top: 10px !important;
}

.portal-profile-menu .dropdown-item {
    color: #173b38;
    font-size: 13px;
    padding: 10px 12px;
    border-radius: 8px;
}

.portal-profile-menu .dropdown-item:hover {
    background: #e8fdff;
}

/* RESPONSIVE */
@media (max-width: 768px) {

    .portal-navbar {
        height: 65px;
        padding: 0 16px;
    }

    .portal-navbar-actions {
        gap: 12px;
    }

    .portal-avatar-wrapper,
    .portal-avatar {
        width: 38px;
        height: 38px;
        min-width: 38px;
    }
}

@media (max-width: 480px) {

    .portal-profile-info {
        display: none;
    }

    .portal-navbar-title h5 {
        font-size: 15px;
    }
}

</style>