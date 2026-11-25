
<?php
/**
 * MANAJEMEN DATA TUTOR
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'tutor';
$page_title = 'Pengelolaan Data Tutor';

$user = current_user();
$is_admin = in_array($_SESSION['role'] ?? '', ['superadmin', 'admin'], true);

// Token keamanan
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Pencarian dan filter
$keyword = trim($_GET['keyword'] ?? '');
$status_filter = $_GET['status'] ?? '';

$allowed_status = ['Aktif', 'Cuti', 'Non-Aktif'];

// Query daftar tutor
$sql = "
    SELECT
        t.id_tutor,
        t.nip,
        t.nama_tutor,
        t.no_telepon,
        t.email,
        t.keahlian,
        t.status_tutor,
        t.created_at,
        COUNT(DISTINCT k.id_kelas) AS total_kelas
    FROM tutor t
    LEFT JOIN kelas k ON t.id_tutor = k.id_tutor
    WHERE 1=1
";

$params = [];

if ($keyword !== '') {
    $sql .= " AND (
        t.nip LIKE :keyword_nip
        OR t.nama_tutor LIKE :keyword_nama
        OR t.keahlian LIKE :keyword_keahlian
        OR t.email LIKE :keyword_email
    )";

    $search = '%' . $keyword . '%';

    $params[':keyword_nip'] = $search;
    $params[':keyword_nama'] = $search;
    $params[':keyword_keahlian'] = $search;
    $params[':keyword_email'] = $search;
}

if (in_array($status_filter, $allowed_status, true)) {
    $sql .= " AND t.status_tutor = :status";
    $params[':status'] = $status_filter;
}

$sql .= "
    GROUP BY t.id_tutor
    ORDER BY t.id_tutor DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tutor_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik seluruh tutor
$stat = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status_tutor = 'Aktif') AS aktif,
        SUM(status_tutor = 'Cuti') AS cuti,
        SUM(status_tutor = 'Non-Aktif') AS nonaktif
    FROM tutor
")->fetch(PDO::FETCH_ASSOC);

$totalTutor = (int)($stat['total'] ?? 0);
$totalAktif = (int)($stat['aktif'] ?? 0);
$totalCuti = (int)($stat['cuti'] ?? 0);
$totalNonAktif = (int)($stat['nonaktif'] ?? 0);

require_once $root . '/includes/header.php';
require_once $root . '/includes/sidebar.php';
require_once $root . '/includes/navbar.php';
?>

<style>
:root {
    --tutor-primary: #258879;
    --tutor-dark: #1d7165;
    --tutor-mint: #98EDE1;
    --tutor-soft: #E8FDFF;
    --tutor-text: #173f3b;
    --tutor-border: #dcece8;
}

.tutor-page {
    padding: 22px 24px 35px;
    color: var(--tutor-text);
}

.tutor-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 22px;
}

.tutor-heading h3 {
    margin: 0 0 5px;
    color: var(--tutor-text);
    font-size: 21px;
    font-weight: 750;
}

.tutor-heading p {
    margin: 0;
    color: #718582;
    font-size: 12px;
}

.btn-tutor-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 18px;
    border: 1px solid var(--tutor-primary);
    border-radius: 10px;
    background: var(--tutor-primary);
    color: white !important;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: .2s;
}

.btn-tutor-primary:hover {
    background: var(--tutor-dark);
    border-color: var(--tutor-dark);
}

.tutor-statistics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}

.tutor-stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 17px;
    background: white;
    border: 1px solid #edf2f0;
    border-radius: 13px;
    box-shadow: 0 3px 12px rgba(20, 60, 50, .04);
}

.tutor-stat-icon {
    width: 43px;
    height: 43px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 11px;
    background: var(--tutor-soft);
    color: var(--tutor-primary);
    font-size: 19px;
}

.tutor-stat-card span {
    display: block;
    margin-bottom: 4px;
    color: #718582;
    font-size: 11px;
}

.tutor-stat-card strong {
    color: var(--tutor-text);
    font-size: 21px;
    font-weight: 750;
}

.tutor-card {
    overflow: hidden;
    background: white;
    border: 1px solid #edf2f0;
    border-radius: 14px;
    box-shadow: 0 3px 12px rgba(20, 60, 50, .04);
}

.tutor-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 21px;
    border-bottom: 1px solid var(--tutor-border);
}

.tutor-card-header h5 {
    margin: 0;
    color: var(--tutor-text);
    font-size: 15px;
    font-weight: 700;
}

.tutor-count {
    padding: 5px 10px;
    border-radius: 20px;
    background: var(--tutor-soft);
    color: var(--tutor-dark);
    font-size: 11px;
    font-weight: 700;
}

.tutor-filter {
    display: flex;
    align-items: center;
    gap: 9px;
    flex-wrap: wrap;
    padding: 16px 20px;
    border-bottom: 1px solid #edf2f0;
}

.tutor-filter .form-control,
.tutor-filter .form-select {
    min-height: 39px;
    border: 1px solid var(--tutor-border);
    border-radius: 8px;
    font-size: 12px;
    box-shadow: none;
}

.tutor-filter .form-control:focus,
.tutor-filter .form-select:focus {
    border-color: var(--tutor-primary);
    box-shadow: 0 0 0 3px rgba(37, 136, 121, .09);
}

.btn-filter {
    min-height: 39px;
    padding: 8px 15px;
    border: 0;
    border-radius: 8px;
    background: var(--tutor-primary);
    color: white;
    font-size: 12px;
    font-weight: 600;
}

.btn-reset {
    display: inline-flex;
    align-items: center;
    min-height: 39px;
    padding: 8px 13px;
    border: 1px solid #dce7e4;
    border-radius: 8px;
    color: #61736e;
    font-size: 12px;
    text-decoration: none;
}

.tutor-table {
    min-width: 950px;
    margin: 0;
}

.tutor-table thead th {
    padding: 14px 15px;
    background: #effbf8;
    color: #315b54;
    border-bottom: 1px solid var(--tutor-border);
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.tutor-table tbody td {
    padding: 14px 15px;
    color: #344b46;
    border-bottom: 1px solid #edf2f0;
    font-size: 12px;
    vertical-align: middle;
}

.tutor-table tbody tr:hover {
    background: #f8fdfb;
}

.tutor-code {
    display: inline-block;
    padding: 5px 9px;
    border: 1px solid var(--tutor-border);
    border-radius: 6px;
    background: #f7faf9;
    color: #315b54;
    font-size: 11px;
    font-weight: 600;
}

.tutor-name {
    color: #183f39;
    font-weight: 700;
}

.tutor-skill {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 7px;
    background: var(--tutor-soft);
    color: #28776b;
    font-size: 11px;
    font-weight: 600;
}

.tutor-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

.status-active {
    background: #e3f8ed;
    color: #198754;
}

.status-leave {
    background: #fff3cd;
    color: #946c00;
}

.status-inactive {
    background: #f1f2f3;
    color: #6c757d;
}

.tutor-actions {
    display: flex;
    justify-content: center;
    gap: 7px;
}

.btn-tutor-icon {
    display: inline-flex;
    width: 34px;
    height: 34px;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: white;
    font-size: 13px;
    cursor: pointer;
    transition: .2s;
}

.btn-tutor-edit {
    color: var(--tutor-primary);
    border: 1px solid #b9e4dc;
}

.btn-tutor-edit:hover {
    color: white;
    background: var(--tutor-primary);
}

.btn-tutor-delete {
    color: #dc3545;
    border: 1px solid #f2c5ca;
}

.btn-tutor-delete:hover {
    color: white;
    background: #dc3545;
}

.tutor-empty {
    padding: 50px 20px !important;
    color: #84958f !important;
    text-align: center;
}

/* MODAL */

.tutor-modal .modal-dialog {
    max-width: 600px;
}

.tutor-modal .modal-content {
    max-height: calc(100vh - 30px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: none;
    border-radius: 16px;
}

.tutor-modal .modal-content > form {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    max-height: calc(100vh - 30px);
    overflow: hidden;
}

.tutor-modal .modal-header {
    flex: 0 0 auto;
    padding: 18px 22px;
    background: var(--tutor-soft);
    border-bottom: 1px solid var(--tutor-border);
}

.tutor-modal .modal-title {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #194b44;
    font-size: 17px;
    font-weight: 700;
}

.tutor-modal .modal-title i {
    color: var(--tutor-primary);
}

.tutor-modal .modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    padding: 22px;
}

.tutor-modal .modal-body::-webkit-scrollbar {
    width: 7px;
}

.tutor-modal .modal-body::-webkit-scrollbar-thumb {
    background: #a8dcd2;
    border-radius: 10px;
}

.tutor-modal .modal-footer {
    flex: 0 0 auto;
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding: 14px 22px;
    border-top: 1px solid var(--tutor-border);
    background: white;
}

.tutor-modal .form-label {
    display: block;
    margin-bottom: 7px;
    color: #254b45;
    font-size: 12px;
    font-weight: 600;
}

.tutor-modal .form-control,
.tutor-modal .form-select {
    min-height: 41px;
    padding: 10px 12px;
    border: 1px solid #d5e7e2;
    border-radius: 9px;
    color: #344b46;
    font-size: 12px;
    box-shadow: none;
}

.tutor-modal .form-control:focus,
.tutor-modal .form-select:focus {
    border-color: var(--tutor-primary);
    box-shadow: 0 0 0 3px rgba(37, 136, 121, .10);
}

.btn-tutor-cancel {
    padding: 10px 16px;
    border: 1px solid #737e85;
    border-radius: 9px;
    background: #737e85;
    color: white;
    font-size: 12px;
    font-weight: 600;
}

.btn-tutor-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 10px 17px;
    border: 1px solid var(--tutor-primary);
    border-radius: 9px;
    background: var(--tutor-primary);
    color: white;
    font-size: 12px;
    font-weight: 600;
}

.btn-tutor-save:hover {
    background: var(--tutor-dark);
    color: white;
}

@media (max-width: 900px) {
    .tutor-statistics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .tutor-page {
        padding: 15px 12px;
    }

    .tutor-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .tutor-statistics {
        grid-template-columns: 1fr;
    }

    .tutor-heading .btn-tutor-primary {
        width: 100%;
    }

    .tutor-modal .modal-dialog {
        margin: 10px;
    }

    .tutor-modal .modal-body {
        padding: 16px;
    }
}
</style>

<div class="tutor-page">

    <!-- HEADER -->
    <div class="tutor-heading">
        <div>
            <h3>Manajemen Data Tutor</h3>
            <p>Kelola informasi tutor pengajar Smart Education Parepare.</p>
        </div>

        <button
            type="button"
            class="btn-tutor-primary"
            data-bs-toggle="modal"
            data-bs-target="#modalTambahTutor"
        >
            <i class="bi bi-plus-lg"></i>
            Tambah Tutor Baru
        </button>
    </div>

    <!-- STATISTIK -->
    <div class="tutor-statistics">

        <div class="tutor-stat-card">
            <div class="tutor-stat-icon">
                <i class="bi bi-people"></i>
            </div>
            <div>
                <span>Total Tutor</span>
                <strong><?= $totalTutor; ?></strong>
            </div>
        </div>

        <div class="tutor-stat-card">
            <div class="tutor-stat-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <span>Tutor Aktif</span>
                <strong><?= $totalAktif; ?></strong>
            </div>
        </div>

        <div class="tutor-stat-card">
            <div class="tutor-stat-icon">
                <i class="bi bi-calendar-minus"></i>
            </div>
            <div>
                <span>Tutor Cuti</span>
                <strong><?= $totalCuti; ?></strong>
            </div>
        </div>

        <div class="tutor-stat-card">
            <div class="tutor-stat-icon">
                <i class="bi bi-person-x"></i>
            </div>
            <div>
                <span>Tutor Non-Aktif</span>
                <strong><?= $totalNonAktif; ?></strong>
            </div>
        </div>

    </div>

    <!-- DAFTAR TUTOR -->
    <div class="tutor-card">

        <div class="tutor-card-header">
            <h5>
                <i class="bi bi-person-badge me-2"></i>
                Daftar Tutor Pengajar
            </h5>

            <span class="tutor-count">
                <?= count($tutor_list); ?> Data Ditampilkan
            </span>
        </div>

        <!-- PENCARIAN DAN FILTER -->
        <form method="GET" class="tutor-filter">

            <input
                type="text"
                name="keyword"
                class="form-control"
                style="max-width:300px;"
                placeholder="Cari NIP, nama, keahlian..."
                value="<?= htmlspecialchars($keyword); ?>"
            >

            <select name="status" class="form-select" style="max-width:180px;">
                <option value="">Semua Status</option>
                <option value="Aktif" <?= $status_filter === 'Aktif' ? 'selected' : ''; ?>>
                    Aktif
                </option>
                <option value="Cuti" <?= $status_filter === 'Cuti' ? 'selected' : ''; ?>>
                    Cuti
                </option>
                <option value="Non-Aktif" <?= $status_filter === 'Non-Aktif' ? 'selected' : ''; ?>>
                    Non-Aktif
                </option>
            </select>

            <button type="submit" class="btn-filter">
                <i class="bi bi-search me-1"></i>
                Cari
            </button>

            <a href="<?= base_url('modules/tutor/index.php'); ?>" class="btn-reset">
                <i class="bi bi-arrow-counterclockwise me-1"></i>
                Reset
            </a>

        </form>

        <div class="table-responsive">

            <table class="table tutor-table align-middle">

                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>NIP / Kode</th>
                        <th>Nama Tutor</th>
                        <th>Bidang Keahlian</th>
                        <th>Kontak</th>
                        <th>Kelas Diampu</th>
                        <th>Status</th>
                        <th class="text-center" width="100">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($tutor_list)): ?>

                    <tr>
                        <td colspan="8" class="tutor-empty">
                            <i class="bi bi-person-badge fs-2 d-block mb-2"></i>
                            Tidak ada data tutor yang ditemukan.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($tutor_list as $no => $t): ?>

                    <tr>
                        <td><?= $no + 1; ?></td>

                        <td>
                            <span class="tutor-code">
                                <?= htmlspecialchars($t['nip']); ?>
                            </span>
                        </td>

                        <td>
                            <span class="tutor-name">
                                <?= htmlspecialchars($t['nama_tutor']); ?>
                            </span>
                        </td>

                        <td>
                            <span class="tutor-skill">
                                <?= htmlspecialchars($t['keahlian']); ?>
                            </span>
                        </td>

                        <td>
                            <div>
                                <i class="bi bi-telephone text-success me-1"></i>
                                <?= htmlspecialchars($t['no_telepon'] ?: '-'); ?>
                            </div>
                            <small class="text-muted">
                                <?= htmlspecialchars($t['email'] ?: '-'); ?>
                            </small>
                        </td>

                        <td>
                            <span class="badge bg-info-subtle text-info-emphasis">
                                <?= (int)$t['total_kelas']; ?> Kelas
                            </span>
                        </td>

                        <td>
                            <?php if ($t['status_tutor'] === 'Aktif'): ?>
                                <span class="tutor-status status-active">Aktif</span>
                            <?php elseif ($t['status_tutor'] === 'Cuti'): ?>
                                <span class="tutor-status status-leave">Cuti</span>
                            <?php else: ?>
                                <span class="tutor-status status-inactive">Non-Aktif</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div class="tutor-actions">

                                <button
                                    type="button"
                                    class="btn-tutor-icon btn-tutor-edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditTutor<?= (int)$t['id_tutor']; ?>"
                                    title="Edit Tutor"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <form
                                    action="<?= base_url('modules/tutor/delete.php'); ?>"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus tutor <?= htmlspecialchars($t['nama_tutor'], ENT_QUOTES, 'UTF-8'); ?>?');"
                                >
                                    <input type="hidden" name="id_tutor"
                                           value="<?= (int)$t['id_tutor']; ?>">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                    <input type="hidden" name="keyword"
                                           value="<?= htmlspecialchars($keyword); ?>">

                                    <input type="hidden" name="status"
                                           value="<?= htmlspecialchars($status_filter); ?>">

                                    <button
                                        type="submit"
                                        class="btn-tutor-icon btn-tutor-delete"
                                        title="Hapus Tutor"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH TUTOR -->
<div
    class="modal fade tutor-modal"
    id="modalTambahTutor"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form action="<?= base_url('modules/tutor/create.php'); ?>" method="POST">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus-fill"></i>
                        Tambah Tutor Baru
                    </h5>

                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">
                                NIP / Kode Tutor <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nip"
                                   class="form-control"
                                   placeholder="Contoh: TUTOR006"
                                   maxlength="20" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_tutor"
                                   class="form-control"
                                   maxlength="100"
                                   placeholder="Nama lengkap tutor"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Bidang Keahlian <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="keahlian"
                                   class="form-control"
                                   maxlength="100"
                                   placeholder="Contoh: Matematika dan Fisika"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="no_telepon"
                                   class="form-control"
                                   maxlength="20"
                                   placeholder="08xxxxxxxxxx">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Tutor</label>
                            <select name="status_tutor" class="form-select">
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Cuti">Cuti</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email"
                                   class="form-control"
                                   maxlength="100"
                                   placeholder="tutor@email.com">
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-tutor-cancel"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn-tutor-save">
                        <i class="bi bi-check-circle"></i>
                        Simpan Tutor
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT TUTOR -->
<?php foreach ($tutor_list as $t): ?>

<div
    class="modal fade tutor-modal"
    id="modalEditTutor<?= (int)$t['id_tutor']; ?>"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form action="<?= base_url('modules/tutor/edit.php'); ?>" method="POST">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                <input type="hidden" name="id_tutor"
                       value="<?= (int)$t['id_tutor']; ?>">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i>
                        Edit Data Tutor
                    </h5>

                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">NIP / Kode Tutor *</label>
                            <input type="text" name="nip"
                                   class="form-control"
                                   maxlength="20"
                                   value="<?= htmlspecialchars($t['nip']); ?>"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="nama_tutor"
                                   class="form-control"
                                   maxlength="100"
                                   value="<?= htmlspecialchars($t['nama_tutor']); ?>"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Bidang Keahlian *</label>
                            <input type="text" name="keahlian"
                                   class="form-control"
                                   maxlength="100"
                                   value="<?= htmlspecialchars($t['keahlian']); ?>"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="no_telepon"
                                   class="form-control"
                                   maxlength="20"
                                   value="<?= htmlspecialchars($t['no_telepon'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Tutor</label>
                            <select name="status_tutor" class="form-select">
                                <option value="Aktif"
                                    <?= $t['status_tutor'] === 'Aktif' ? 'selected' : ''; ?>>
                                    Aktif
                                </option>
                                <option value="Cuti"
                                    <?= $t['status_tutor'] === 'Cuti' ? 'selected' : ''; ?>>
                                    Cuti
                                </option>
                                <option value="Non-Aktif"
                                    <?= $t['status_tutor'] === 'Non-Aktif' ? 'selected' : ''; ?>>
                                    Non-Aktif
                                </option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email"
                                   class="form-control"
                                   maxlength="100"
                                   value="<?= htmlspecialchars($t['email'] ?? ''); ?>">
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-tutor-cancel"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn-tutor-save">
                        <i class="bi bi-save"></i>
                        Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php endforeach; ?>

<?php require_once $root . '/includes/footer.php'; ?>
