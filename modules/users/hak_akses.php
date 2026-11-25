
<?php
/**
 * PENGATURAN HAK AKSES
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';

require_login();

if (($_SESSION['role'] ?? '') !== 'superadmin') {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$page_title = 'Pengaturan Hak Akses';

require_once $root . '/includes/header.php';
require_once $root . '/includes/sidebar.php';
require_once $root . '/includes/navbar.php';

// Matriks hak akses
$permissions = [
    [
        'module' => 'Dashboard',
        'superadmin' => 'Kelola',
        'admin' => 'Kelola',
        'siswa' => 'Lihat'
    ],
    [
        'module' => 'Manajemen Pengguna',
        'superadmin' => 'Kelola',
        'admin' => '-',
        'siswa' => '-'
    ],
    [
        'module' => 'Manajemen Administrator',
        'superadmin' => 'Kelola',
        'admin' => '-',
        'siswa' => '-'
    ],
    [
        'module' => 'Data Siswa',
        'superadmin' => 'Pantau',
        'admin' => 'Kelola',
        'siswa' => 'Lihat Data Sendiri'
    ],
    [
        'module' => 'Paket Belajar',
        'superadmin' => 'Pantau',
        'admin' => 'Kelola',
        'siswa' => 'Lihat'
    ],
    [
        'module' => 'Data Tutor',
        'superadmin' => 'Pantau',
        'admin' => 'Kelola',
        'siswa' => 'Lihat'
    ],
    [
        'module' => 'Data Ruangan',
        'superadmin' => 'Pantau',
        'admin' => 'Kelola',
        'siswa' => '-'
    ],
    [
        'module' => 'Data Kelas',
        'superadmin' => 'Pantau',
        'admin' => 'Kelola',
        'siswa' => 'Lihat Jadwal'
    ],
    [
        'module' => 'Pembayaran',
        'superadmin' => 'Pantau',
        'admin' => 'Kelola',
        'siswa' => 'Lihat Data Sendiri'
    ],
    [
        'module' => 'Riwayat Pembayaran',
        'superadmin' => 'Pantau',
        'admin' => 'Kelola',
        'siswa' => 'Lihat Data Sendiri'
    ],
    [
        'module' => 'Analisis RFM',
        'superadmin' => 'Pantau',
        'admin' => 'Kelola',
        'siswa' => '-'
    ],
    [
        'module' => 'Penjadwalan Otomatis',
        'superadmin' => 'Pantau',
        'admin' => 'Kelola',
        'siswa' => '-'
    ],
    [
        'module' => 'Informasi Jadwal',
        'superadmin' => 'Pantau',
        'admin' => 'Kelola',
        'siswa' => 'Lihat'
    ],
];
?>

<div class="access-page">

    <div class="access-heading">
        <div>
            <h3>Pengaturan Hak Akses</h3>
            <p>
                Informasi pembagian kewenangan pengguna dalam sistem
                Smart Education Parepare.
            </p>
        </div>
    </div>

    <!-- RINGKASAN ROLE -->
    <div class="role-cards">

        <div class="role-card">
            <div class="role-icon super">
                <i class="bi bi-shield-lock"></i>
            </div>
            <div>
                <h5>Super Admin</h5>
                <p>Mengelola akun dan mengawasi sistem.</p>
            </div>
        </div>

        <div class="role-card">
            <div class="role-icon admin">
                <i class="bi bi-person-gear"></i>
            </div>
            <div>
                <h5>Admin</h5>
                <p>Mengelola operasional bimbingan belajar.</p>
            </div>
        </div>

        <div class="role-card">
            <div class="role-icon siswa">
                <i class="bi bi-mortarboard"></i>
            </div>
            <div>
                <h5>Siswa/Orang Tua</h5>
                <p>Mengakses informasi dan data pribadi.</p>
            </div>
        </div>

    </div>

    <!-- TABEL HAK AKSES -->
    <div class="access-card">

        <div class="access-card-heading">
            <div>
                <h5>Matriks Hak Akses Pengguna</h5>
                <p>
                    Pembagian kewenangan berdasarkan role pengguna.
                </p>
            </div>
        </div>

        <div class="table-responsive">

            <table class="access-table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Modul Sistem</th>
                        <th>Super Admin</th>
                        <th>Admin</th>
                        <th>Siswa/Orang Tua</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($permissions as $index => $permission): ?>
                        <tr>
                            <td><?= $index + 1; ?></td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($permission['module']); ?>
                                </strong>
                            </td>

                            <?php foreach (['superadmin', 'admin', 'siswa'] as $role): ?>
                                <td>
                                    <?php
                                    $value = $permission[$role];

                                    $class = 'permission-none';

                                    if ($value === 'Kelola') {
                                        $class = 'permission-manage';
                                    } elseif ($value === 'Pantau') {
                                        $class = 'permission-monitor';
                                    } elseif ($value !== '-') {
                                        $class = 'permission-view';
                                    }
                                    ?>

                                    <span class="permission-badge <?= $class; ?>">
                                        <?= htmlspecialchars($value); ?>
                                    </span>
                                </td>
                            <?php endforeach; ?>

                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>

        </div>

        <!-- KETERANGAN -->
        <div class="legend">
            <h6>Keterangan Hak Akses</h6>

            <div class="legend-items">
                <span>
                    <i class="legend-dot manage"></i>
                    Kelola: Mengelola data
                </span>

                <span>
                    <i class="legend-dot monitor"></i>
                    Pantau: Melihat dan mengawasi
                </span>

                <span>
                    <i class="legend-dot view"></i>
                    Lihat: Melihat informasi
                </span>

                <span>
                    <i class="legend-dot none"></i>
                    - : Tidak memiliki akses
                </span>
            </div>
        </div>

    </div>

    <div class="access-note">
        <i class="bi bi-info-circle"></i>
        <div>
            <strong>Informasi</strong>
            <p>
                Matriks ini merupakan ringkasan pembagian kewenangan
                setiap role. Hak akses aktual tetap mengikuti pemeriksaan
                role dan pembatasan akses pada masing-masing halaman sistem.
            </p>
        </div>
    </div>

</div>

<style>
.access-page {
    padding: 24px 28px 35px;
    background: #f5fbfa;
    min-height: calc(100vh - 140px);
}

.access-heading {
    margin-bottom: 23px;
}

.access-heading h3 {
    margin: 0 0 7px;
    color: #173b38;
    font-size: 22px;
    font-weight: 700;
}

.access-heading p {
    margin: 0;
    color: #718582;
    font-size: 12px;
}

.role-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 22px;
}

.role-card {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 19px;
    background: white;
    border: 1px solid #e4f0ed;
    border-radius: 14px;
}

.role-icon {
    width: 45px;
    height: 45px;
    min-width: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    font-size: 21px;
}

.role-icon.super {
    background: #e8fdff;
    color: #287e72;
}

.role-icon.admin {
    background: #eaf1ff;
    color: #4268a8;
}

.role-icon.siswa {
    background: #eff8e9;
    color: #648b43;
}

.role-card h5 {
    margin: 0 0 5px;
    color: #173b38;
    font-size: 13px;
    font-weight: 700;
}

.role-card p {
    margin: 0;
    color: #718582;
    font-size: 10px;
    line-height: 1.6;
}

.access-card {
    padding: 23px;
    background: white;
    border: 1px solid #e4f0ed;
    border-radius: 16px;
    box-shadow: 0 4px 18px rgba(31, 92, 81, .04);
}

.access-card-heading {
    margin-bottom: 20px;
}

.access-card-heading h5 {
    margin: 0 0 5px;
    color: #173b38;
    font-size: 16px;
    font-weight: 700;
}

.access-card-heading p {
    margin: 0;
    color: #718582;
    font-size: 12px;
}

.access-table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

.access-table th {
    padding: 14px 12px;
    background: #f5fbfa;
    color: #52736c;
    font-size: 11px;
    text-align: left;
}

.access-table td {
    padding: 14px 12px;
    color: #526b65;
    font-size: 11px;
    border-bottom: 1px solid #edf3f1;
}

.access-table td strong {
    color: #173b38;
    font-weight: 600;
}

.permission-badge {
    display: inline-block;
    min-width: 65px;
    padding: 6px 9px;
    text-align: center;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}

.permission-manage {
    background: #e3f7f1;
    color: #20765f;
}

.permission-monitor {
    background: #e8fdff;
    color: #287e72;
}

.permission-view {
    background: #edf2ff;
    color: #4968a5;
}

.permission-none {
    background: #f1f3f3;
    color: #87938f;
}

.legend {
    margin-top: 23px;
    padding-top: 18px;
    border-top: 1px solid #edf3f1;
}

.legend h6 {
    margin-bottom: 13px;
    color: #173b38;
    font-size: 12px;
    font-weight: 700;
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 15px 22px;
}

.legend-items span {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #718582;
    font-size: 10px;
}

.legend-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
}

.legend-dot.manage {
    background: #20765f;
}

.legend-dot.monitor {
    background: #287e72;
}

.legend-dot.view {
    background: #4968a5;
}

.legend-dot.none {
    background: #aab4b0;
}

.access-note {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-top: 18px;
    padding: 17px;
    background: #e8fdff;
    border: 1px solid #d5f1ed;
    border-radius: 12px;
    color: #287e72;
}

.access-note > i {
    font-size: 19px;
}

.access-note strong {
    display: block;
    margin-bottom: 4px;
    font-size: 12px;
}

.access-note p {
    margin: 0;
    color: #55766f;
    font-size: 11px;
    line-height: 1.7;
}

@media (max-width: 900px) {
    .role-cards {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .access-page {
        padding: 20px 15px;
    }

    .access-card {
        padding: 16px;
    }
}
</style>

<?php require_once $root . '/includes/footer.php'; ?>
