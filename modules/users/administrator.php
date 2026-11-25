
<?php
/**
 * MANAJEMEN ADMINISTRATOR
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';

require_login();

// Hanya Super Admin yang boleh mengakses
if (($_SESSION['role'] ?? '') !== 'superadmin') {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$current_user = current_user();

// Token keamanan
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Pencarian
$keyword = trim($_GET['search'] ?? '');

$sql = "SELECT id, username, nama_lengkap, email, role, created_at
        FROM users
        WHERE role = 'admin'";

$params = [];

if ($keyword !== '') {
    $sql .= " AND (
        username LIKE :username
        OR nama_lengkap LIKE :nama
        OR email LIKE :email
    )";

    $search = '%' . $keyword . '%';

    $params = [
        ':username' => $search,
        ':nama' => $search,
        ':email' => $search
    ];
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$administrators = $stmt->fetchAll();

// Jumlah administrator
$totalAdmin = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
)->fetchColumn();

$page_title = 'Manajemen Administrator';

require_once $root . '/includes/header.php';
require_once $root . '/includes/sidebar.php';
require_once $root . '/includes/navbar.php';
?>

<div class="admin-page">

    <div class="admin-heading">
        <div>
            <h3>Manajemen Administrator</h3>
            <p>Kelola akun administrator sistem Smart Education Parepare.</p>
        </div>

        <a href="<?= base_url('modules/users/tambah.php'); ?>"
           class="btn-add">
            <i class="bi bi-plus-lg"></i>
            Tambah Administrator
        </a>
    </div>

    <!-- STATISTIK -->
    <div class="admin-stat-card">
        <div class="admin-stat-icon">
            <i class="bi bi-person-gear"></i>
        </div>

        <div>
            <span>Total Administrator</span>
            <h4><?= number_format($totalAdmin, 0, ',', '.'); ?></h4>
        </div>
    </div>

    <!-- DAFTAR ADMIN -->
    <div class="admin-card">

        <div class="admin-card-heading">
            <h5>Daftar Administrator</h5>
            <p>Informasi akun Admin yang terdaftar dalam sistem.</p>
        </div>

        <!-- PENCARIAN -->
        <form method="GET" class="admin-filter">

            <div class="admin-search">
                <i class="bi bi-search"></i>

                <input
                    type="text"
                    name="search"
                    placeholder="Cari nama, username, atau email..."
                    value="<?= htmlspecialchars($keyword); ?>">
            </div>

            <button type="submit" class="btn-filter">
                <i class="bi bi-search"></i>
                Cari
            </button>

            <a href="<?= base_url('modules/users/administrator.php'); ?>"
               class="btn-reset">
                Reset
            </a>

        </form>

        <div class="table-info">
            Menampilkan <strong><?= count($administrators); ?></strong>
            akun administrator.
        </div>

        <div class="table-responsive">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Administrator</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($administrators)): ?>

                    <?php $no = 1; ?>

                    <?php foreach ($administrators as $admin): ?>

                        <tr>
                            <td><?= $no++; ?></td>

                            <td>
                                <div class="admin-name">
                                    <div class="admin-avatar">
                                        <?= htmlspecialchars(
                                            strtoupper(substr($admin['nama_lengkap'], 0, 1))
                                        ); ?>
                                    </div>

                                    <strong>
                                        <?= htmlspecialchars($admin['nama_lengkap']); ?>
                                    </strong>
                                </div>
                            </td>

                            <td><?= htmlspecialchars($admin['username']); ?></td>

                            <td><?= htmlspecialchars($admin['email'] ?? '-'); ?></td>

                            <td>
                                <span class="admin-badge">Administrator</span>
                            </td>

                            <td>
                                <?= htmlspecialchars(format_tanggal($admin['created_at'])); ?>
                            </td>

                            <td>
                                <div class="admin-actions">

                                    <a href="<?= base_url(
                                        'modules/users/edit.php?id=' . (int)$admin['id']
                                    ); ?>"
                                       class="btn-action edit"
                                       title="Edit Administrator">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form method="POST"
                                          action="<?= base_url('modules/users/hapus.php'); ?>"
                                          onsubmit="return confirm('Yakin ingin menghapus akun administrator ini?');">

                                        <input type="hidden"
                                               name="id"
                                               value="<?= (int)$admin['id']; ?>">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                        <button type="submit"
                                                class="btn-action delete"
                                                title="Hapus Administrator">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty-data">
                            <i class="bi bi-person-x"></i>
                            <strong>Belum ada administrator.</strong>
                            <span>Tambahkan akun administrator untuk mulai mengelola sistem.</span>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>
</div>

<style>
.admin-page {
    padding: 24px 28px 35px;
    background: #f5fbfa;
    min-height: calc(100vh - 140px);
}

.admin-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 24px;
}

.admin-heading h3 {
    margin: 0 0 7px;
    color: #173b38;
    font-size: 22px;
    font-weight: 700;
}

.admin-heading p,
.admin-card-heading p {
    margin: 0;
    color: #718582;
    font-size: 12px;
}

.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 17px;
    border-radius: 9px;
    background: #287e72;
    color: white;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

.btn-add:hover {
    background: #176d63;
    color: white;
}

.admin-stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    margin-bottom: 22px;
    background: white;
    border: 1px solid #e4f0ed;
    border-radius: 14px;
}

.admin-stat-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #e8fdff;
    color: #287e72;
    font-size: 21px;
}

.admin-stat-card span {
    color: #718582;
    font-size: 11px;
}

.admin-stat-card h4 {
    margin: 4px 0 0;
    color: #173b38;
    font-size: 23px;
}

.admin-card {
    padding: 23px;
    background: white;
    border: 1px solid #e4f0ed;
    border-radius: 16px;
}

.admin-card-heading {
    margin-bottom: 20px;
}

.admin-card-heading h5 {
    margin: 0 0 5px;
    color: #173b38;
    font-size: 16px;
}

.admin-filter {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
}

.admin-search {
    position: relative;
    flex: 1;
}

.admin-search i {
    position: absolute;
    left: 13px;
    top: 13px;
    color: #81938f;
}

.admin-search input {
    width: 100%;
    height: 42px;
    padding: 0 12px 0 37px;
    border: 1px solid #e0ece9;
    border-radius: 9px;
    font-size: 12px;
    outline: none;
}

.admin-search input:focus {
    border-color: #72cbbb;
}

.btn-filter,
.btn-reset {
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 15px;
    border: none;
    border-radius: 9px;
    text-decoration: none;
    font-size: 12px;
    cursor: pointer;
}

.btn-filter {
    background: #287e72;
    color: white;
}

.btn-reset {
    background: #f0f6f4;
    color: #526c66;
}

.table-info {
    margin-bottom: 12px;
    color: #718582;
    font-size: 11px;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

.admin-table th {
    padding: 14px 12px;
    background: #f5fbfa;
    color: #52736c;
    font-size: 11px;
    text-align: left;
}

.admin-table td {
    padding: 14px 12px;
    color: #526b65;
    font-size: 11px;
    border-bottom: 1px solid #edf3f1;
}

.admin-name,
.admin-actions {
    display: flex;
    align-items: center;
    gap: 9px;
}

.admin-name strong {
    color: #173b38;
}

.admin-avatar {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #dff8f2;
    color: #287e72;
    font-weight: 700;
}

.admin-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    background: #eaf1ff;
    color: #4268a8;
    font-size: 10px;
    font-weight: 600;
}

.btn-action {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
}

.btn-action.edit {
    background: #e8fdff;
    color: #287e72;
}

.btn-action.delete {
    background: #fff0f0;
    color: #d65b5b;
}

.empty-data {
    padding: 40px 15px !important;
    text-align: center;
}

.empty-data i,
.empty-data strong,
.empty-data span {
    display: block;
}

.empty-data i {
    color: #91cfc3;
    font-size: 30px;
    margin-bottom: 10px;
}

.empty-data strong {
    color: #405c56;
    font-size: 13px;
}

.empty-data span {
    margin-top: 5px;
    color: #8a9a96;
}

@media (max-width: 768px) {
    .admin-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-filter {
        flex-wrap: wrap;
    }

    .admin-search {
        flex-basis: 100%;
    }

    .admin-page {
        padding: 20px 15px;
    }
}
</style>

<?php require_once $root . '/includes/footer.php'; ?>
