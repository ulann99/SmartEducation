
<?php
/**
 * MANAJEMEN PENGGUNA
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';

// Keamanan halaman
require_login();

if (($_SESSION['role'] ?? '') !== 'superadmin') {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

// Informasi pengguna yang login
$current_user = current_user();

// Membuat token keamanan untuk proses hapus
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Filter pencarian
$keyword = trim($_GET['search'] ?? '');
$filter_role = trim($_GET['role'] ?? '');

$allowed_roles = ['superadmin', 'admin', 'siswa'];

if (!in_array($filter_role, $allowed_roles, true)) {
    $filter_role = '';
}

// Query daftar pengguna
$sql = "SELECT id, username, nama_lengkap, email, role,
               id_siswa, created_at
        FROM users
        WHERE 1=1";

$params = [];

if ($keyword !== '') {
    $sql .= " AND (
        username LIKE :username
        OR nama_lengkap LIKE :nama
        OR email LIKE :email
    )";

    $search_value = '%' . $keyword . '%';

    $params[':username'] = $search_value;
    $params[':nama'] = $search_value;
    $params[':email'] = $search_value;
}

if ($filter_role !== '') {
    $sql .= " AND role = :role";
    $params[':role'] = $filter_role;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$users = $stmt->fetchAll();

// Menghitung jumlah pengguna berdasarkan role
$stmtCount = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(role = 'superadmin') AS superadmin,
        SUM(role = 'admin') AS admin,
        SUM(role = 'siswa') AS siswa
    FROM users
");

$jumlah = $stmtCount->fetch();

$totalPengguna = (int)($jumlah['total'] ?? 0);
$totalSuperAdmin = (int)($jumlah['superadmin'] ?? 0);
$totalAdmin = (int)($jumlah['admin'] ?? 0);
$totalSiswa = (int)($jumlah['siswa'] ?? 0);

$page_title = 'Manajemen Pengguna';

require_once $root . '/includes/header.php';
require_once $root . '/includes/sidebar.php';
require_once $root . '/includes/navbar.php';
?>

<div class="users-page">

    <!-- HEADER HALAMAN -->
    <div class="users-heading">
        <div>
            <h3>Manajemen Pengguna</h3>
            <p>Kelola akun pengguna sistem Smart Education Parepare.</p>
        </div>

        <a href="<?= base_url('modules/users/tambah.php'); ?>"
           class="btn-add">
            <i class="bi bi-plus-lg"></i>
            Tambah Pengguna
        </a>
    </div>

    <!-- RINGKASAN PENGGUNA -->
    <div class="user-statistics">

        <div class="user-stat-card">
            <div class="user-stat-icon">
                <i class="bi bi-people"></i>
            </div>
            <div>
                <span>Total Pengguna</span>
                <h4><?= number_format($totalPengguna, 0, ',', '.'); ?></h4>
            </div>
        </div>

        <div class="user-stat-card">
            <div class="user-stat-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <div>
                <span>Super Admin</span>
                <h4><?= number_format($totalSuperAdmin, 0, ',', '.'); ?></h4>
            </div>
        </div>

        <div class="user-stat-card">
            <div class="user-stat-icon">
                <i class="bi bi-person-gear"></i>
            </div>
            <div>
                <span>Administrator</span>
                <h4><?= number_format($totalAdmin, 0, ',', '.'); ?></h4>
            </div>
        </div>

        <div class="user-stat-card">
            <div class="user-stat-icon">
                <i class="bi bi-mortarboard"></i>
            </div>
            <div>
                <span>Siswa/Orang Tua</span>
                <h4><?= number_format($totalSiswa, 0, ',', '.'); ?></h4>
            </div>
        </div>

    </div>

    <!-- TABEL PENGGUNA -->
    <div class="users-card">

        <div class="users-card-heading">
            <div>
                <h5>Daftar Pengguna</h5>
                <p>Informasi seluruh akun yang terdaftar dalam sistem.</p>
            </div>
        </div>

        <!-- PENCARIAN DAN FILTER -->
        <form method="GET" class="filter-form">

            <div class="search-box">
                <i class="bi bi-search"></i>
                <input
                    type="text"
                    name="search"
                    placeholder="Cari nama, username, atau email..."
                    value="<?= htmlspecialchars($keyword); ?>">
            </div>

            <select name="role" class="role-filter">
                <option value="">Semua Role</option>
                <option value="superadmin"
                    <?= $filter_role === 'superadmin' ? 'selected' : ''; ?>>
                    Super Admin
                </option>
                <option value="admin"
                    <?= $filter_role === 'admin' ? 'selected' : ''; ?>>
                    Admin
                </option>
                <option value="siswa"
                    <?= $filter_role === 'siswa' ? 'selected' : ''; ?>>
                    Siswa/Orang Tua
                </option>
            </select>

            <button type="submit" class="btn-filter">
                <i class="bi bi-funnel"></i>
                Filter
            </button>

            <a href="<?= base_url('modules/users/index.php'); ?>"
               class="btn-reset">
                Reset
            </a>

        </form>

        <!-- INFORMASI JUMLAH DATA -->
        <div class="table-information">
            Menampilkan <strong><?= count($users); ?></strong> akun pengguna.
        </div>

        <!-- TABEL -->
        <div class="table-responsive">

            <table class="users-table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Pengguna</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($users)): ?>

                    <?php $no = 1; ?>

                    <?php foreach ($users as $user): ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>
                                <div class="user-name">
                                    <div class="user-avatar">
                                        <?= htmlspecialchars(
                                            strtoupper(substr($user['nama_lengkap'], 0, 1))
                                        ); ?>
                                    </div>

                                    <strong>
                                        <?= htmlspecialchars($user['nama_lengkap']); ?>
                                    </strong>
                                </div>
                            </td>

                            <td>
                                <?= htmlspecialchars($user['username']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($user['email'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php
                                $roleLabel = [
                                    'superadmin' => 'Super Admin',
                                    'admin' => 'Admin',
                                    'siswa' => 'Siswa/Orang Tua'
                                ];

                                $roleClass = [
                                    'superadmin' => 'badge-superadmin',
                                    'admin' => 'badge-admin',
                                    'siswa' => 'badge-siswa'
                                ];
                                ?>

                                <span class="role-badge <?= $roleClass[$user['role']] ?? ''; ?>">
                                    <?= $roleLabel[$user['role']] ?? htmlspecialchars($user['role']); ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    format_tanggal($user['created_at'])
                                ); ?>
                            </td>

                            <td>
                                <div class="action-buttons">

                                    <a href="<?= base_url('modules/users/edit.php?id=' . (int)$user['id']); ?>"
                                       class="btn-action edit"
                                       title="Edit Pengguna">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <?php if ((int)$user['id'] !== (int)$current_user['id']): ?>

                                        <form method="POST"
                                                action="<?= base_url('modules/users/hapus.php'); ?>"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna <?= htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8'); ?>?');"
                                                style="display: inline;">

                                                <input type="hidden" name="id"
                                                    value="<?= (int)$user['id']; ?>">

                                                <input type="hidden" name="csrf_token"
                                                    value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                                <button type="submit"
                                                        class="btn-action delete"
                                                        title="Hapus Pengguna">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>

                                    <?php else: ?>

                                        <span class="self-account" title="Akun yang sedang digunakan">
                                            Akun Anda
                                        </span>

                                    <?php endif; ?>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty-data">
                            <i class="bi bi-inbox"></i>
                            <strong>Data pengguna tidak ditemukan.</strong>
                            <span>Coba gunakan kata kunci atau filter lainnya.</span>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<style>

/* HALAMAN MANAJEMEN PENGGUNA */

.users-page {
    padding: 24px 28px 35px;
    background: #f5fbfa;
    min-height: calc(100vh - 140px);
}

.users-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 24px;
}

.users-heading h3 {
    margin: 0 0 7px;
    color: #173b38;
    font-size: 22px;
    font-weight: 700;
}

.users-heading p {
    margin: 0;
    color: #718582;
    font-size: 13px;
}

.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 17px;
    border-radius: 10px;
    background: #287e72;
    color: white;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: .2s;
}

.btn-add:hover {
    background: #176d63;
    color: white;
    transform: translateY(-2px);
}

/* STATISTIK */

.user-statistics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 23px;
}

.user-stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    background: white;
    border: 1px solid #e4f0ed;
    border-radius: 15px;
    box-shadow: 0 4px 18px rgba(31, 92, 81, .04);
}

.user-stat-icon {
    width: 45px;
    height: 45px;
    min-width: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #e8fdff;
    color: #287e72;
    font-size: 21px;
}

.user-stat-card span {
    display: block;
    color: #718582;
    font-size: 11px;
    margin-bottom: 5px;
}

.user-stat-card h4 {
    margin: 0;
    color: #173b38;
    font-size: 23px;
    font-weight: 700;
}

/* KARTU TABEL */

.users-card {
    padding: 23px;
    background: white;
    border: 1px solid #e4f0ed;
    border-radius: 17px;
    box-shadow: 0 4px 18px rgba(31, 92, 81, .04);
}

.users-card-heading {
    margin-bottom: 20px;
}

.users-card-heading h5 {
    margin: 0 0 5px;
    color: #173b38;
    font-size: 16px;
    font-weight: 700;
}

.users-card-heading p {
    margin: 0;
    color: #718582;
    font-size: 12px;
}

/* FILTER */

.filter-form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
}

.search-box {
    flex: 1;
    min-width: 220px;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #81938f;
}

.search-box input {
    width: 100%;
    height: 42px;
    padding: 0 12px 0 37px;
    border: 1px solid #e0ece9;
    border-radius: 9px;
    outline: none;
    font-size: 12px;
}

.search-box input:focus,
.role-filter:focus {
    border-color: #72cbbb;
    box-shadow: 0 0 0 3px rgba(152, 237, 225, .2);
}

.role-filter {
    height: 42px;
    min-width: 150px;
    padding: 0 12px;
    border: 1px solid #e0ece9;
    border-radius: 9px;
    color: #405c56;
    background: white;
    font-size: 12px;
    outline: none;
}

.btn-filter,
.btn-reset {
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 15px;
    border: none;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}

.btn-filter {
    background: #287e72;
    color: white;
}

.btn-filter:hover {
    background: #176d63;
}

.btn-reset {
    background: #f0f6f4;
    color: #526c66;
}

.btn-reset:hover {
    background: #e4f1ed;
    color: #176d63;
}

/* TABEL */

.table-information {
    margin-bottom: 12px;
    color: #718582;
    font-size: 11px;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

.users-table thead th {
    padding: 14px 12px;
    background: #f5fbfa;
    color: #52736c;
    font-size: 11px;
    font-weight: 700;
    text-align: left;
    border-bottom: 1px solid #e5efec;
}

.users-table tbody td {
    padding: 14px 12px;
    color: #526b65;
    font-size: 11px;
    border-bottom: 1px solid #edf3f1;
}

.users-table tbody tr:hover {
    background: #f9fdfc;
}

.users-table tbody tr:last-child td {
    border-bottom: none;
}

/* IDENTITAS PENGGUNA */

.user-name {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-name strong {
    color: #173b38;
    font-size: 11px;
    font-weight: 600;
}

.user-avatar {
    width: 34px;
    height: 34px;
    min-width: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #dff8f2;
    color: #287e72;
    font-size: 13px;
    font-weight: 700;
}

/* ROLE */

.role-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}

.badge-superadmin {
    background: #e8fdff;
    color: #287e72;
}

.badge-admin {
    background: #eaf1ff;
    color: #4268a8;
}

.badge-siswa {
    background: #eff8e9;
    color: #648b43;
}

/* AKSI */

.action-buttons {
    display: flex;
    align-items: center;
    gap: 7px;
}

.btn-action {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}

.btn-action.edit {
    background: #e8fdff;
    color: #287e72;
}

.btn-action.delete {
    background: #fff0f0;
    color: #d65b5b;
}

.btn-action:hover {
    opacity: .75;
}

.self-account {
    color: #287e72;
    font-size: 10px;
    font-weight: 600;
}

/* DATA KOSONG */

.empty-data {
    padding: 40px 15px !important;
    text-align: center;
}

.empty-data i {
    display: block;
    margin-bottom: 10px;
    color: #91cfc3;
    font-size: 30px;
}

.empty-data strong {
    display: block;
    color: #405c56;
    font-size: 13px;
}

.empty-data span {
    display: block;
    margin-top: 5px;
    color: #8a9a96;
    font-size: 11px;
}

/* RESPONSIVE */

@media (max-width: 1100px) {
    .user-statistics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .users-page {
        padding: 20px 15px;
    }

    .users-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .users-card {
        padding: 16px;
    }

    .filter-form {
        align-items: stretch;
    }

    .search-box {
        flex-basis: 100%;
    }
}

@media (max-width: 480px) {
    .user-statistics {
        grid-template-columns: 1fr;
    }

    .btn-add {
        width: 100%;
        justify-content: center;
    }
}

</style>

<?php require_once $root . '/includes/footer.php'; ?>
