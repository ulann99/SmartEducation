
<?php
/**
 * PENGELOLAAN DATA RUANGAN
 * SMART EDUCATION PAREPARE
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'ruangan';
$page_title = 'Pengelolaan Data Ruangan';

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// Filter pencarian
$cari = trim($_GET['cari'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

// Ambil data ruangan dan jumlah kelas
$sql = "
    SELECT
        r.*,
        (
            SELECT COUNT(*)
            FROM kelas k
            WHERE k.id_ruangan = r.id_ruangan
        ) AS total_kelas
    FROM ruangan r
    WHERE 1=1
";

$params = [];

if ($cari !== '') {
    $sql .= "
        AND (
            r.nama_ruangan LIKE :cari
            OR r.lokasi LIKE :cari
        )
    ";
    $params[':cari'] = '%' . $cari . '%';
}

if (in_array($filter_status, ['Aktif', 'Non-Aktif'], true)) {
    $sql .= " AND r.status_ruangan = :status";
    $params[':status'] = $filter_status;
}

$sql .= " ORDER BY r.id_ruangan DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ruangan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik ruangan
$total_ruangan = (int)$pdo->query("
    SELECT COUNT(*) FROM ruangan
")->fetchColumn();

$total_aktif = (int)$pdo->query("
    SELECT COUNT(*)
    FROM ruangan
    WHERE status_ruangan = 'Aktif'
")->fetchColumn();

$total_nonaktif = (int)$pdo->query("
    SELECT COUNT(*)
    FROM ruangan
    WHERE status_ruangan = 'Non-Aktif'
")->fetchColumn();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<style>
:root {
    --room-primary: #258879;
    --room-dark: #1d7165;
    --room-soft: #E8FDFF;
    --room-text: #173f3b;
    --room-border: #dcece8;
}

.room-page {
    padding: 22px 24px;
    color: var(--room-text);
}

.room-toolbar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 22px;
}

.btn-room-primary,
.btn-room-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 19px;
    border: 1px solid var(--room-primary);
    border-radius: 10px;
    background: var(--room-primary);
    color: white;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

.btn-room-primary:hover,
.btn-room-save:hover {
    background: var(--room-dark);
    color: white;
}

.room-stat {
    padding: 19px;
    background: white;
    border: 1px solid #edf2f0;
    border-radius: 14px;
    box-shadow: 0 3px 12px rgba(20,60,50,.04);
}

.room-stat small {
    color: #71827b;
}

.room-stat strong {
    display: block;
    margin-top: 5px;
    color: var(--room-text);
    font-size: 25px;
}

.room-card {
    height: 100%;
    overflow: hidden;
    background: white;
    border: 1px solid #edf2f0;
    border-radius: 15px;
    box-shadow: 0 3px 12px rgba(20,60,50,.05);
}

.room-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 17px 19px 10px;
}

.room-status {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

.status-active {
    background: #e3f8ed;
    color: #198754;
}

.status-inactive {
    background: #fce8e8;
    color: #c0392b;
}

.room-card-body {
    padding: 10px 19px 19px;
}

.room-heading {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 18px;
}

.room-icon {
    display: flex;
    flex-shrink: 0;
    width: 47px;
    height: 47px;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: var(--room-soft);
    color: var(--room-primary);
    font-size: 21px;
}

.room-name {
    margin: 0 0 5px;
    color: var(--room-text);
    font-size: 16px;
    font-weight: 700;
}

.room-info {
    margin-bottom: 7px;
    color: #71827b;
    font-size: 12px;
}

.room-facilities {
    min-height: 65px;
    margin-bottom: 17px;
    padding: 12px;
    border-radius: 10px;
    background: #f6f9f8;
}

.room-facilities small {
    display: block;
    margin-bottom: 5px;
    color: #71827b;
    font-weight: 700;
}

.room-facilities p {
    margin: 0;
    color: #40564f;
    font-size: 12px;
    line-height: 1.6;
}

.room-actions {
    display: flex;
    gap: 8px;
}

.btn-room-edit {
    flex: 1;
    min-height: 37px;
    border: 1px solid var(--room-primary);
    border-radius: 8px;
    background: white;
    color: var(--room-primary);
    font-size: 12px;
    font-weight: 600;
}

.btn-room-edit:hover {
    background: var(--room-primary);
    color: white;
}

.btn-room-delete {
    width: 39px;
    border: 1px solid #f2c5ca;
    border-radius: 8px;
    background: white;
    color: #dc3545;
}

.btn-room-delete:hover {
    background: #dc3545;
    color: white;
}

.room-modal .modal-content {
    overflow: hidden;
    border: 0;
    border-radius: 16px;
}

.room-modal .modal-header {
    padding: 17px 22px;
    background: var(--room-soft);
    border-bottom: 1px solid var(--room-border);
}

.room-modal .modal-title {
    color: #194b44;
    font-size: 18px;
    font-weight: 700;
}

.room-modal .modal-body {
    padding: 22px;
}

.room-modal .form-label {
    margin-bottom: 7px;
    color: #254b45;
    font-size: 12px;
    font-weight: 600;
}

.room-modal .form-control,
.room-modal .form-select {
    min-height: 42px;
    border: 1px solid #d5e7e2;
    border-radius: 9px;
    font-size: 13px;
    box-shadow: none;
}

.room-modal .form-control:focus,
.room-modal .form-select:focus {
    border-color: var(--room-primary);
    box-shadow: 0 0 0 3px rgba(37,136,121,.10);
}

.room-modal textarea.form-control {
    min-height: 85px;
}

.room-modal .modal-footer {
    padding: 14px 22px;
    border-top: 1px solid var(--room-border);
}

.btn-room-cancel {
    padding: 10px 17px;
    border: 0;
    border-radius: 9px;
    background: #737e85;
    color: white;
    font-size: 12px;
    font-weight: 600;
}

.room-empty {
    padding: 55px 20px;
    color: #84958f;
    text-align: center;
}

.room-empty i {
    display: block;
    margin-bottom: 12px;
    color: var(--room-primary);
    font-size: 35px;
}

@media(max-width:768px) {
    .room-page {
        padding: 15px 12px;
    }
}
</style>

<div class="room-page">

    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Manajemen Data Ruangan</h4>
            <p class="text-muted mb-0">
                Kelola informasi ruangan Smart Education Parepare.
            </p>
        </div>

        <div class="room-toolbar mb-0">
            <button type="button"
                    class="btn-room-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalTambahRuangan">
                <i class="bi bi-plus-lg"></i>
                Tambah Ruangan Baru
            </button>
        </div>
    </div>

    <!-- NOTIFIKASI -->
    <?php if ($flash = get_flash()): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message'] ?? '') ?>
            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="room-stat">
                <small>
                    <i class="bi bi-door-closed me-2"></i>
                    Total Ruangan
                </small>
                <strong><?= $total_ruangan ?></strong>
            </div>
        </div>

        <div class="col-md-4">
            <div class="room-stat">
                <small>
                    <i class="bi bi-check-circle me-2"></i>
                    Ruangan Aktif
                </small>
                <strong><?= $total_aktif ?></strong>
            </div>
        </div>

        <div class="col-md-4">
            <div class="room-stat">
                <small>
                    <i class="bi bi-pause-circle me-2"></i>
                    Ruangan Non-Aktif
                </small>
                <strong><?= $total_nonaktif ?></strong>
            </div>
        </div>

    </div>

    <!-- PENCARIAN DAN FILTER -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-center">

                <div class="col-md-6">
                    <input
                        type="text"
                        name="cari"
                        class="form-control"
                        value="<?= htmlspecialchars($cari) ?>"
                        placeholder="Cari nama atau lokasi ruangan..."
                    >
                </div>

                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Aktif"
                            <?= $filter_status === 'Aktif' ? 'selected' : '' ?>>
                            Aktif
                        </option>
                        <option value="Non-Aktif"
                            <?= $filter_status === 'Non-Aktif' ? 'selected' : '' ?>>
                            Non-Aktif
                        </option>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-search"></i> Cari
                    </button>

                    <a href="<?= base_url('modules/ruangan/index.php') ?>"
                       class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- DAFTAR RUANGAN -->
    <div class="row g-4">

        <?php if (empty($ruangan_list)): ?>

            <div class="col-12">
                <div class="room-card room-empty">
                    <i class="bi bi-door-closed"></i>
                    <strong>Belum Ada Data Ruangan</strong>
                    <p class="small mb-0 mt-2">
                        Silakan tambahkan data ruangan baru.
                    </p>
                </div>
            </div>

        <?php else: ?>

            <?php foreach ($ruangan_list as $r): ?>

                <div class="col-xl-4 col-md-6">
                    <div class="room-card">

                        <div class="room-card-header">

                            <span class="badge bg-light text-dark">
                                Ruangan #<?= (int)$r['id_ruangan'] ?>
                            </span>

                            <?php if ($r['status_ruangan'] === 'Aktif'): ?>
                                <span class="room-status status-active">
                                    Aktif
                                </span>
                            <?php else: ?>
                                <span class="room-status status-inactive">
                                    Non-Aktif
                                </span>
                            <?php endif; ?>

                        </div>

                        <div class="room-card-body">

                            <div class="room-heading">

                                <div class="room-icon">
                                    <i class="bi bi-door-closed-fill"></i>
                                </div>

                                <div>
                                    <h5 class="room-name">
                                        <?= htmlspecialchars($r['nama_ruangan']) ?>
                                    </h5>

                                    <div class="room-info">
                                        <i class="bi bi-people me-1"></i>
                                        Kapasitas:
                                        <strong>
                                            <?= (int)$r['kapasitas'] ?> Siswa
                                        </strong>
                                    </div>

                                    <div class="room-info">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <?= htmlspecialchars($r['lokasi']) ?>
                                    </div>

                                    <div class="room-info">
                                        <i class="bi bi-calendar-check me-1"></i>
                                        <?= (int)$r['total_kelas'] ?> Kelas
                                    </div>
                                </div>

                            </div>

                            <div class="room-facilities">
                                <small>Fasilitas Ruangan</small>
                                <p>
                                    <?= htmlspecialchars(
                                        $r['fasilitas'] ?: 'Belum ada informasi fasilitas'
                                    ) ?>
                                </p>
                            </div>

                            <div class="room-actions">

                                <button
                                    type="button"
                                    class="btn-room-edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditRuangan<?= (int)$r['id_ruangan'] ?>"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                    Edit Ruangan
                                </button>

                                <button
                                    type="button"
                                    class="btn-room-delete"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalHapusRuangan<?= (int)$r['id_ruangan'] ?>"
                                    title="Hapus Ruangan"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>

                            </div>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

<!-- MODAL TAMBAH RUANGAN -->
<div class="modal fade room-modal"
     id="modalTambahRuangan"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form action="<?= base_url('modules/ruangan/create.php') ?>"
                  method="POST">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-door-open me-2"></i>
                        Tambah Ruangan Baru
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">
                            Nama Ruangan *
                        </label>

                        <input
                            type="text"
                            name="nama_ruangan"
                            class="form-control"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">
                                Kapasitas Siswa *
                            </label>

                            <input
                                type="number"
                                name="kapasitas"
                                class="form-control"
                                min="1"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Status Ruangan
                            </label>

                            <select
                                name="status_ruangan"
                                class="form-select"
                                required
                            >
                                <option value="Aktif" selected>
                                    Aktif
                                </option>
                                <option value="Non-Aktif">
                                    Non-Aktif
                                </option>
                            </select>
                        </div>

                    </div>

                    <div class="mt-3">
                        <label class="form-label">
                            Lokasi *
                        </label>

                        <input
                            type="text"
                            name="lokasi"
                            class="form-control"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="mt-3">
                        <label class="form-label">
                            Fasilitas Ruangan
                        </label>

                        <textarea
                            name="fasilitas"
                            class="form-control"
                            rows="3"
                        ></textarea>
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn-room-cancel"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn-room-save">
                        <i class="bi bi-check-circle"></i>
                        Simpan Ruangan
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<!-- MODAL EDIT DAN HAPUS -->
<?php foreach ($ruangan_list as $r): ?>

    <!-- MODAL EDIT -->
    <div class="modal fade room-modal"
         id="modalEditRuangan<?= (int)$r['id_ruangan'] ?>"
         tabindex="-1">

        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <form action="<?= base_url('modules/ruangan/edit.php') ?>"
                      method="POST">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($csrf_token) ?>">

                    <input type="hidden"
                           name="id_ruangan"
                           value="<?= (int)$r['id_ruangan'] ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil-square me-2"></i>
                            Edit Data Ruangan
                        </h5>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">
                                Nama Ruangan *
                            </label>

                            <input
                                type="text"
                                name="nama_ruangan"
                                class="form-control"
                                maxlength="100"
                                value="<?= htmlspecialchars($r['nama_ruangan']) ?>"
                                required
                            >
                        </div>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">
                                    Kapasitas Siswa *
                                </label>

                                <input
                                    type="number"
                                    name="kapasitas"
                                    class="form-control"
                                    min="1"
                                    value="<?= (int)$r['kapasitas'] ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Status Ruangan
                                </label>

                                <select
                                    name="status_ruangan"
                                    class="form-select"
                                    required
                                >
                                    <option value="Aktif"
                                        <?= $r['status_ruangan'] === 'Aktif' ? 'selected' : '' ?>>
                                        Aktif
                                    </option>

                                    <option value="Non-Aktif"
                                        <?= $r['status_ruangan'] === 'Non-Aktif' ? 'selected' : '' ?>>
                                        Non-Aktif
                                    </option>
                                </select>
                            </div>

                        </div>

                        <div class="mt-3">
                            <label class="form-label">
                                Lokasi *
                            </label>

                            <input
                                type="text"
                                name="lokasi"
                                class="form-control"
                                maxlength="150"
                                value="<?= htmlspecialchars($r['lokasi']) ?>"
                                required
                            >
                        </div>

                        <div class="mt-3">
                            <label class="form-label">
                                Fasilitas Ruangan
                            </label>

                            <textarea
                                name="fasilitas"
                                class="form-control"
                                rows="3"
                            ><?= htmlspecialchars($r['fasilitas'] ?? '') ?></textarea>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                                class="btn-room-cancel"
                                data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn-room-save">
                            <i class="bi bi-save"></i>
                            Simpan Perubahan
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- MODAL HAPUS -->
    <div class="modal fade"
         id="modalHapusRuangan<?= (int)$r['id_ruangan'] ?>"
         tabindex="-1">

        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">

                <form action="<?= base_url('modules/ruangan/delete.php') ?>"
                      method="POST">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($csrf_token) ?>">

                    <input type="hidden"
                           name="id_ruangan"
                           value="<?= (int)$r['id_ruangan'] ?>">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            Konfirmasi Hapus Ruangan
                        </h5>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus ruangan
                        <strong>
                            <?= htmlspecialchars($r['nama_ruangan']) ?>
                        </strong>?

                        <p class="text-muted small mt-2 mb-0">
                            Data yang sudah dihapus tidak dapat dikembalikan.
                        </p>
                    </div>

                    <div class="modal-footer">

                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i>
                            Ya, Hapus
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

<?php endforeach; ?>

<?php require_once BASEPATH . '/includes/footer.php'; ?>
