<?php
/**
 * Data Siswa
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

$current_page = 'siswa';
$page_title = 'Pengelolaan Data Siswa';

// ========================================
// FUNGSI ESCAPE
// ========================================

function siswa_e($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// ========================================
// PENCARIAN DAN FILTER
// ========================================

$search = trim($_GET['search'] ?? '');
$filter_paket = trim($_GET['paket'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$sql = "
    SELECT
        s.*,
        p.nama_paket,
        p.harga
    FROM siswa s
    LEFT JOIN paket_belajar p
        ON s.id_paket = p.id_paket
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            s.nama_siswa LIKE :search_nama
            OR s.nis LIKE :search_nis
            OR s.no_telepon LIKE :search_telepon
            OR s.email LIKE :search_email
        )
    ";

    $keyword = '%' . $search . '%';

    $params[':search_nama'] = $keyword;
    $params[':search_nis'] = $keyword;
    $params[':search_telepon'] = $keyword;
    $params[':search_email'] = $keyword;
}

if ($filter_paket !== '' && ctype_digit($filter_paket)) {
    $sql .= " AND s.id_paket = :id_paket";
    $params[':id_paket'] = (int)$filter_paket;
}

if (in_array($filter_status, ['Aktif', 'Alumni', 'Non-Aktif'], true)) {
    $sql .= " AND s.status_siswa = :status_siswa";
    $params[':status_siswa'] = $filter_status;
}

$sql .= " ORDER BY s.id_siswa DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// AMBIL DATA PAKET
// ========================================

$paket_list = $pdo->query("
    SELECT id_paket, nama_paket, harga
    FROM paket_belajar
    WHERE status = 'Aktif'
    ORDER BY nama_paket ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// TOKEN CSRF UNTUK HAPUS
// ========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_siswa_delete'])) {
    $_SESSION['csrf_siswa_delete'] = bin2hex(random_bytes(32));
}

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';

?>

<!-- HEADER HALAMAN -->

<div class="page-header">

    <div>
        <h1 class="h3 fw-bold text-dark-mint">
            
        </h1>

        <p class="text-muted mb-0">
            
        </p>
    </div>

    <button
        type="button"
        class="btn btn-siswa-primary btn-add-siswa"
        data-bs-toggle="modal"
        data-bs-target="#modalTambahSiswa"
    >
        <i class="bi bi-plus-lg"></i>
        <span>Tambah Siswa Baru</span>
    </button>

</div>

<!-- PENCARIAN DAN FILTER -->

<div class="card mb-4 border-0 shadow-sm">

    <div class="card-body p-3">

        <form method="GET" action="" class="row g-2 align-items-center">

            <div class="col-md-5">

                <div class="input-group">

                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>

                    <input
                        type="text"
                        name="search"
                        class="form-control border-start-0"
                        placeholder="Cari nama, NIS, nomor HP, atau email..."
                        value="<?= siswa_e($search); ?>"
                    >

                </div>

            </div>

            <div class="col-md-3">

                <select name="paket" class="form-select">

                    <option value="">-- Semua Paket Belajar --</option>

                    <?php foreach ($paket_list as $pk): ?>

                        <option
                            value="<?= siswa_e($pk['id_paket']); ?>"
                            <?= ($filter_paket == $pk['id_paket']) ? 'selected' : ''; ?>
                        >
                            <?= siswa_e($pk['nama_paket']); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="col-md-2">

                <select name="status" class="form-select">

                    <option value="">-- Status --</option>

                    <option
                        value="Aktif"
                        <?= ($filter_status === 'Aktif') ? 'selected' : ''; ?>
                    >
                        Aktif
                    </option>

                    <option
                        value="Alumni"
                        <?= ($filter_status === 'Alumni') ? 'selected' : ''; ?>
                    >
                        Alumni
                    </option>

                    <option
                        value="Non-Aktif"
                        <?= ($filter_status === 'Non-Aktif') ? 'selected' : ''; ?>
                    >
                        Non-Aktif
                    </option>

                </select>

            </div>

            <div class="col-md-2 d-flex gap-2">

                <button type="submit" class="btn btn-siswa-primary w-100">
                    <i class="bi bi-funnel"></i>
                    Filter
                </button>

                <?php if ($search !== '' || $filter_paket !== '' || $filter_status !== ''): ?>

                    <a
                        href="<?= base_url('modules/siswa/index.php'); ?>"
                        class="btn btn-outline-secondary"
                        title="Reset Filter"
                    >
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>

</div>

<!-- TABEL DATA SISWA -->

<div class="card shadow-sm border-0">

    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">

        <h5 class="card-title mb-0 fw-bold text-mint-deep">

            <i class="bi bi-people me-2 text-mint"></i>
            Daftar Siswa

            <span class="badge badge-mint ms-1">
                <?= count($siswa_list); ?>
            </span>

        </h5>

    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0">

            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Gender</th>
                    <th>Paket Belajar</th>
                    <th>No. Telepon</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($siswa_list)): ?>

                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Tidak ada data siswa ditemukan.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php $no = 1; ?>

                    <?php foreach ($siswa_list as $s): ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>
                                <span class="badge bg-light text-dark border font-monospace">
                                    <?= siswa_e($s['nis']); ?>
                                </span>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-2">

                                    <div
                                        class="stat-icon mint mb-0"
                                        style="width:32px;height:32px;font-size:0.9rem;"
                                    >
                                        <i class="bi bi-person-fill"></i>
                                    </div>

                                    <div>
                                        <strong class="text-dark">
                                            <?= siswa_e($s['nama_siswa']); ?>
                                        </strong>

                                        <div class="small text-muted">
                                            <?= siswa_e($s['email'] ?? '-'); ?>
                                        </div>
                                    </div>

                                </div>
                            </td>

                            <td>

                                <?php if ($s['jenis_kelamin'] === 'Laki-laki'): ?>

                                    <span class="badge bg-info-subtle text-info-emphasis">L</span>
                                    Laki-laki

                                <?php else: ?>

                                    <span class="badge bg-danger-subtle text-danger-emphasis">P</span>
                                    Perempuan

                                <?php endif; ?>

                            </td>

                            <td>
                                <span class="fw-medium text-dark-mint">
                                    <?= siswa_e($s['nama_paket'] ?? 'Belum Pilih Paket'); ?>
                                </span>

                                <?php if (!empty($s['harga'])): ?>
                                    <div class="small text-muted">
                                        <?= format_rupiah($s['harga']); ?> / bln
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= siswa_e($s['no_telepon'] ?? '-'); ?>
                            </td>

                            <td>

                                <?php if ($s['status_siswa'] === 'Aktif'): ?>

                                    <span class="badge badge-mint">Aktif</span>

                                <?php elseif ($s['status_siswa'] === 'Alumni'): ?>

                                    <span class="badge bg-primary-subtle text-primary">
                                        Alumni
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Non-Aktif
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="text-center">

                                <div class="btn-group btn-group-sm" role="group">

                                    <button
                                        type="button"
                                        class="btn btn-action-view"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDetailSiswa<?= (int)$s['id_siswa']; ?>"
                                        title="Detail Siswa"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-action-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditSiswa<?= (int)$s['id_siswa']; ?>"
                                        title="Edit Siswa"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-action-delete"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalHapusSiswa<?= (int)$s['id_siswa']; ?>"
                                        title="Hapus Siswa"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<!-- MODAL DETAIL, EDIT, DAN HAPUS -->

<?php foreach ($siswa_list as $s): ?>

    <?php $id = (int)$s['id_siswa']; ?>

    <!-- MODAL DETAIL -->

    <div
        class="modal fade"
        id="modalDetailSiswa<?= $id; ?>"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header siswa-modal-header">

                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        Detail Siswa
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>

                <div class="modal-body">

                    <div class="text-center mb-3">

                        <div
                            class="stat-icon mint mx-auto mb-2"
                            style="width:60px;height:60px;font-size:1.8rem;"
                        >
                            <i class="bi bi-person"></i>
                        </div>

                        <h5 class="fw-bold mb-0">
                            <?= siswa_e($s['nama_siswa']); ?>
                        </h5>

                        <span class="badge bg-light text-dark border">
                            NIS: <?= siswa_e($s['nis']); ?>
                        </span>

                    </div>

                    <ul class="list-group list-group-flush small">

                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Jenis Kelamin:</span>
                            <strong><?= siswa_e($s['jenis_kelamin']); ?></strong>
                        </li>

                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Paket Belajar:</span>
                            <strong class="text-mint-dark">
                                <?= siswa_e($s['nama_paket'] ?? '-'); ?>
                            </strong>
                        </li>

                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Nomor HP:</span>
                            <strong><?= siswa_e($s['no_telepon'] ?? '-'); ?></strong>
                        </li>

                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Email:</span>
                            <strong><?= siswa_e($s['email'] ?? '-'); ?></strong>
                        </li>

                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Alamat:</span>
                            <span class="text-end fw-medium">
                                <?= nl2br(siswa_e($s['alamat'] ?? '-')); ?>
                            </span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Tanggal Pendaftaran:</span>
                            <strong>
                                <?= format_tanggal($s['tanggal_daftar']); ?>
                            </strong>
                        </li>

                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Status:</span>
                            <strong><?= siswa_e($s['status_siswa']); ?></strong>
                        </li>

                    </ul>

                </div>

                <div class="modal-footer siswa-modal-footer">

                    <button
                        type="button"
                        class="btn btn-siswa-secondary btn-sm"
                        data-bs-dismiss="modal"
                    >
                        Tutup
                    </button>

                </div>

            </div>

        </div>

    </div>

    <!-- MODAL EDIT -->

    <div
        class="modal fade"
        id="modalEditSiswa<?= $id; ?>"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-lg modal-dialog-centered">

            <div class="modal-content">

                <form
                    action="<?= base_url('modules/siswa/edit.php'); ?>"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="id_siswa"
                        value="<?= $id; ?>"
                    >

                    <div class="modal-header siswa-modal-header">

                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-pencil-square me-2"></i>
                            Edit Data Siswa
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>

                    </div>

                    <div class="modal-body">

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    NIS <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="nis"
                                    class="form-control"
                                    maxlength="20"
                                    value="<?= siswa_e($s['nis']); ?>"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Nama Lengkap Siswa <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="nama_siswa"
                                    class="form-control"
                                    maxlength="100"
                                    value="<?= siswa_e($s['nama_siswa']); ?>"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Jenis Kelamin <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="jenis_kelamin"
                                    class="form-select"
                                    required
                                >

                                    <option
                                        value="Laki-laki"
                                        <?= ($s['jenis_kelamin'] === 'Laki-laki') ? 'selected' : ''; ?>
                                    >
                                        Laki-laki
                                    </option>

                                    <option
                                        value="Perempuan"
                                        <?= ($s['jenis_kelamin'] === 'Perempuan') ? 'selected' : ''; ?>
                                    >
                                        Perempuan
                                    </option>

                                </select>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">Paket Belajar</label>

                                <select name="id_paket" class="form-select">

                                    <option value="">-- Pilih Paket Belajar --</option>

                                    <?php foreach ($paket_list as $pk): ?>

                                        <option
                                            value="<?= siswa_e($pk['id_paket']); ?>"
                                            <?= ($s['id_paket'] == $pk['id_paket']) ? 'selected' : ''; ?>
                                        >
                                            <?= siswa_e($pk['nama_paket']); ?>
                                            (<?= format_rupiah($pk['harga']); ?>)
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">No. Telepon / WhatsApp</label>

                                <input
                                    type="text"
                                    name="no_telepon"
                                    class="form-control"
                                    maxlength="20"
                                    value="<?= siswa_e($s['no_telepon'] ?? ''); ?>"
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">Email</label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    maxlength="100"
                                    value="<?= siswa_e($s['email'] ?? ''); ?>"
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">Status Siswa</label>

                                <select name="status_siswa" class="form-select">

                                    <option
                                        value="Aktif"
                                        <?= ($s['status_siswa'] === 'Aktif') ? 'selected' : ''; ?>
                                    >
                                        Aktif
                                    </option>

                                    <option
                                        value="Alumni"
                                        <?= ($s['status_siswa'] === 'Alumni') ? 'selected' : ''; ?>
                                    >
                                        Alumni
                                    </option>

                                    <option
                                        value="Non-Aktif"
                                        <?= ($s['status_siswa'] === 'Non-Aktif') ? 'selected' : ''; ?>
                                    >
                                        Non-Aktif
                                    </option>

                                </select>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">Tanggal Pendaftaran</label>

                                <input
                                    type="date"
                                    name="tanggal_daftar"
                                    class="form-control"
                                    value="<?= siswa_e($s['tanggal_daftar']); ?>"
                                    required
                                >

                            </div>

                            <div class="col-12">

                                <label class="form-label">Alamat Lengkap</label>

                                <textarea
                                    name="alamat"
                                    class="form-control"
                                    rows="2"
                                ><?= siswa_e($s['alamat'] ?? ''); ?></textarea>

                            </div>

                        </div>

                    </div>

                    <div class="modal-footer siswa-modal-footer">

                        <button
                            type="button"
                            class="btn btn-siswa-secondary"
                            data-bs-dismiss="modal"
                        >
                            Batal
                        </button>

                        <button type="submit" class="btn btn-siswa-primary">
                            <i class="bi bi-save me-1"></i>
                            Simpan Perubahan
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <!-- MODAL HAPUS -->

    <div
        class="modal fade"
        id="modalHapusSiswa<?= $id; ?>"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <form
                    action="<?= base_url('modules/siswa/delete.php'); ?>"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="id_siswa"
                        value="<?= $id; ?>"
                    >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= siswa_e($_SESSION['csrf_siswa_delete']); ?>"
                    >

                    <div class="modal-header siswa-modal-header">

                        <h5 class="modal-title fw-bold text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Konfirmasi Penghapusan
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>

                    </div>

                    <div class="modal-body">

                        Apakah kamu yakin ingin menghapus data siswa:

                        <div class="mt-2 p-3 bg-light rounded">

                            <strong><?= siswa_e($s['nama_siswa']); ?></strong>

                            <div class="small text-muted">
                                NIS: <?= siswa_e($s['nis']); ?>
                            </div>

                        </div>

                        <p class="small text-muted mt-3 mb-0">
                            Jika siswa memiliki riwayat pembayaran atau kelas,
                            sistem akan mempertahankan datanya dan mengubah
                            status menjadi Non-Aktif.
                        </p>

                    </div>

                    <div class="modal-footer siswa-modal-footer">

                        <button
                            type="button"
                            class="btn btn-siswa-secondary"
                            data-bs-dismiss="modal"
                        >
                            Batal
                        </button>

                        <button type="submit" class="btn btn-siswa-danger">
                            <i class="bi bi-trash me-1"></i>
                            Ya, Lanjutkan
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

<?php endforeach; ?>

<!-- MODAL TAMBAH SISWA -->

<div
    class="modal fade"
    id="modalTambahSiswa"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form
                action="<?= base_url('modules/siswa/create.php'); ?>"
                method="POST"
            >

                <div class="modal-header siswa-modal-header">

                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus-fill me-2"></i>
                        Tambah Siswa Baru
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                NIS <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="nis"
                                class="form-control"
                                maxlength="20"
                                placeholder="Contoh: 2026011"
                                required
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Nama Lengkap Siswa <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="nama_siswa"
                                class="form-control"
                                maxlength="100"
                                placeholder="Masukkan nama siswa"
                                required
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Jenis Kelamin <span class="text-danger">*</span>
                            </label>

                            <select
                                name="jenis_kelamin"
                                class="form-select"
                                required
                            >
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">Paket Belajar</label>

                            <select name="id_paket" class="form-select">

                                <option value="">-- Pilih Paket Belajar --</option>

                                <?php foreach ($paket_list as $pk): ?>

                                    <option value="<?= siswa_e($pk['id_paket']); ?>">
                                        <?= siswa_e($pk['nama_paket']); ?>
                                        (<?= format_rupiah($pk['harga']); ?>)
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">No. Telepon / WhatsApp</label>

                            <input
                                type="text"
                                name="no_telepon"
                                class="form-control"
                                maxlength="20"
                                placeholder="0812-xxxx-xxxx"
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">Email</label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                maxlength="100"
                                placeholder="siswa@email.com"
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">Tanggal Pendaftaran</label>

                            <input
                                type="date"
                                name="tanggal_daftar"
                                class="form-control"
                                value="<?= date('Y-m-d'); ?>"
                                required
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">Status Siswa</label>

                            <select name="status_siswa" class="form-select">
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Alumni">Alumni</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>

                        </div>

                        <div class="col-12">

                            <label class="form-label">Alamat Lengkap</label>

                            <textarea
                                name="alamat"
                                class="form-control"
                                rows="2"
                                placeholder="Masukkan alamat siswa"
                            ></textarea>

                        </div>

                        <div class="col-12">

                            <div class="form-check p-3 bg-light rounded border">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="buat_akun"
                                    id="buat_akun"
                                    value="1"
                                    checked
                                >

                                <label
                                    class="form-check-label fw-medium text-dark"
                                    for="buat_akun"
                                >
                                    Buatkan Akun Login Siswa Secara Otomatis
                                </label>

                                <div class="form-text">
                                    Username menggunakan NIS.
                                    Password awal: <code>siswa123</code>.
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="modal-footer siswa-modal-footer">

                    <button
                        type="button"
                        class="btn btn-siswa-secondary"
                        data-bs-dismiss="modal"
                    >
                        Batal
                    </button>

                    <button type="submit" class="btn btn-siswa-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        Simpan Data Siswa
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<style>
/* =====================================================
   SMART EDUCATION - DATA SISWA
===================================================== */

:root {
    --siswa-primary: #246B4B;
    --siswa-primary-hover: #194F37;
    --siswa-mint: #E8FDFF;
    --siswa-border: #DCEBE4;
    --siswa-text: #26372E;
}

/* HEADER HALAMAN */

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}

.page-header h1 {
    color: var(--siswa-text);
    letter-spacing: -.35px;
}

/* TOMBOL UTAMA */

.btn-siswa-primary,
.btn-add-siswa {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 10px 18px;
    border: 1px solid var(--siswa-primary) !important;
    border-radius: 10px !important;
    background: var(--siswa-primary) !important;
    color: #fff !important;
    font-weight: 600;
    box-shadow: 0 4px 10px rgba(36,107,75,.13);
    transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
}

.btn-siswa-primary:hover,
.btn-add-siswa:hover {
    background: var(--siswa-primary-hover) !important;
    border-color: var(--siswa-primary-hover) !important;
    color: #fff !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(36,107,75,.2);
}

.btn-siswa-primary:focus-visible,
.btn-siswa-secondary:focus-visible,
.btn-siswa-danger:focus-visible {
    outline: 3px solid rgba(36,107,75,.2);
    outline-offset: 2px;
}

/* TOMBOL SEKUNDER */

.btn-siswa-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 40px;
    padding: 9px 17px;
    border: 1px solid #D6DEE3 !important;
    border-radius: 9px !important;
    background: #F1F4F6 !important;
    color: #47545D !important;
    font-weight: 600;
}

.btn-siswa-secondary:hover {
    background: #E2E8EC !important;
    color: #263238 !important;
}

/* TOMBOL HAPUS */

.btn-siswa-danger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 40px;
    padding: 9px 17px;
    border: 1px solid #C83D4D !important;
    border-radius: 9px !important;
    background: #C83D4D !important;
    color: #fff !important;
    font-weight: 600;
}

.btn-siswa-danger:hover {
    background: #A92D3B !important;
    color: #fff !important;
}

/* MODAL */

#modalTambahSiswa .modal-dialog,
[id^="modalEditSiswa"] .modal-dialog {
    max-width: 820px;
}

.modal-content {
    overflow: hidden;
    border: 0 !important;
    border-radius: 17px !important;
    box-shadow: 0 18px 55px rgba(20,43,34,.2);
}

/* HEADER MODAL */

.siswa-modal-header {
    min-height: 62px;
    padding: 16px 20px !important;
    border-bottom: 1px solid #D9F0ED !important;
    background: var(--siswa-mint) !important;
    color: #20483F;
}

.siswa-modal-header .modal-title {
    display: flex;
    align-items: center;
    gap: 3px;
    color: #20483F;
    font-size: 1.15rem;
    font-weight: 750 !important;
}

.siswa-modal-header .modal-title i {
    font-size: 1.15rem;
}

.siswa-modal-header .btn-close {
    opacity: .65;
}

.siswa-modal-header .btn-close:hover {
    opacity: 1;
}

/* FORM MODAL */

.modal-body {
    padding: 21px 20px !important;
}

.modal-body .form-label {
    display: block;
    margin-bottom: 7px;
    color: #29483E;
    font-size: .86rem;
    font-weight: 650;
}

.modal-body .form-control,
.modal-body .form-select {
    min-height: 41px;
    padding: 9px 12px;
    border: 1px solid var(--siswa-border);
    border-radius: 9px;
    background-color: #fff;
    color: #26372E;
    font-size: .88rem;
    box-shadow: none;
    transition: border-color .18s ease, box-shadow .18s ease;
}

.modal-body textarea.form-control {
    min-height: 76px;
    resize: vertical;
}

.modal-body .form-control::placeholder {
    color: #98A59E;
}

.modal-body .form-control:focus,
.modal-body .form-select:focus {
    border-color: #62A98A;
    box-shadow: 0 0 0 3px rgba(36,107,75,.11);
}

/* CHECKBOX AKUN */

.modal-body .form-check {
    padding: 14px 15px 13px 2.1rem !important;
    border: 1px solid #DCE8E2 !important;
    border-radius: 10px !important;
    background: #F7FAF8 !important;
}

.modal-body .form-check-input {
    margin-top: .22rem;
}

.modal-body .form-check-input:checked {
    border-color: var(--siswa-primary);
    background-color: var(--siswa-primary);
}

.modal-body .form-check-label {
    color: #30443A;
    font-size: .88rem;
}

.modal-body .form-text {
    margin-top: 6px;
    color: #74847B;
    font-size: .8rem;
}

.modal-body code {
    padding: 2px 6px;
    border-radius: 5px;
    background: #EAF5EF;
    color: var(--siswa-primary);
    font-size: .8rem;
}

/* FOOTER MODAL */

.siswa-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding: 14px 20px !important;
    border-top: 1px solid #E7EEEA !important;
    background: #fff;
}

.siswa-modal-footer .btn {
    min-width: 105px;
}

/* TOMBOL AKSI TABEL */

.btn-action-view,
.btn-action-edit,
.btn-action-delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 32px;
    margin: 0 2px;
    border-radius: 8px !important;
    background: #fff !important;
}

.btn-action-view {
    border: 1px solid #B7DCD7 !important;
    color: #27877B !important;
}

.btn-action-view:hover {
    background: #E8F8F5 !important;
}

.btn-action-edit {
    border: 1px solid #BFD3EF !important;
    color: #3975B9 !important;
}

.btn-action-edit:hover {
    background: #EEF5FF !important;
}

.btn-action-delete {
    border: 1px solid #F0C4C8 !important;
    color: #C44755 !important;
}

.btn-action-delete:hover {
    background: #FFF0F1 !important;
}

/* RESPONSIVE */

@media (max-width: 767.98px) {

    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .page-header .btn-add-siswa {
        width: 100%;
    }

    #modalTambahSiswa .modal-dialog,
    [id^="modalEditSiswa"] .modal-dialog {
        margin: 10px;
    }

    .modal-body {
        padding: 17px 15px !important;
    }

    .siswa-modal-header,
    .siswa-modal-footer {
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
}
</style>

<?php require_once BASEPATH . '/includes/footer.php'; ?>