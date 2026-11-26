
<?php
/**
 * Pengelolaan Data Siswa
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

$current_page = 'siswa';
$page_title = 'Pengelolaan Data Siswa';

// ========================================
// FUNGSI BANTU
// ========================================

function siswa_e($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// ========================================
// TOKEN CSRF
// ========================================

if (empty($_SESSION['csrf_siswa'])) {
    $_SESSION['csrf_siswa'] = bin2hex(random_bytes(32));
}

// ========================================
// PENCARIAN DAN FILTER
// ========================================

$search = trim($_GET['search'] ?? '');
$filter_paket = trim($_GET['paket'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$params = [];

$sql = "
    SELECT
        s.*,
        pd.id_pendaftaran,
        pd.nomor_registrasi,
        pd.tanggal_pendaftaran,
        pd.jenis_program,
        pd.pilihan_hari,
        pd.pilihan_jam,
        pd.id_paket,
        p.nama_paket,
        p.harga,
        ot.nama_ayah,
        ot.nama_ibu,
        ot.nama_pemberi_informasi,
        ot.alamat_rumah AS alamat_orang_tua,
        ot.no_hp_ayah,
        ot.no_hp_ibu

    FROM siswa s

    LEFT JOIN pendaftaran pd
        ON pd.id_pendaftaran = (
            SELECT MAX(p2.id_pendaftaran)
            FROM pendaftaran p2
            WHERE p2.id_siswa = s.id_siswa
        )

    LEFT JOIN paket_belajar p
        ON p.id_paket = pd.id_paket

    LEFT JOIN orang_tua ot
        ON ot.id_siswa = s.id_siswa

    WHERE 1=1
";

if ($search !== '') {
    $sql .= "
        AND (
            s.nama_lengkap LIKE :nama
            OR s.sekolah_asal LIKE :sekolah
            OR s.no_hp LIKE :hp
            OR s.kelas LIKE :kelas
            OR pd.nomor_registrasi LIKE :registrasi
        )
    ";

    $keyword = '%' . $search . '%';

    $params[':nama'] = $keyword;
    $params[':sekolah'] = $keyword;
    $params[':hp'] = $keyword;
    $params[':kelas'] = $keyword;
    $params[':registrasi'] = $keyword;
}

if ($filter_paket !== '' && ctype_digit($filter_paket)) {
    $sql .= " AND pd.id_paket = :id_paket";
    $params[':id_paket'] = (int)$filter_paket;
}

// FILTER STATUS SISWA
if (in_array($filter_status, ['Aktif', 'Nonaktif'], true)) {
    $sql .= " AND s.status_siswa = :status_siswa";
    $params[':status_siswa'] = $filter_status;
}

$sql .= " ORDER BY s.id_siswa DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// AMBIL PAKET BELAJAR AKTIF
// ========================================

$paket_list = $pdo->query("
    SELECT id_paket, nama_paket, jenjang, harga
    FROM paket_belajar
    WHERE status = 'aktif'
    ORDER BY nama_paket ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// HEADER
// ========================================

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';

?>

<style>
:root {
    --siswa-primary: #98EDE1;
    --siswa-primary-dark: #27877B;
    --siswa-mint: #E8FDFF;
    --siswa-text: #172B2A;
    --siswa-border: #DCEAE8;
}

.text-dark-mint,
.text-mint-deep {
    color: #20483F !important;
}

.text-mint {
    color: #27877B !important;
}

.text-mint-dark {
    color: #27877B !important;
}

.badge-mint {
    background: #DDF8F3;
    color: #23776D;
    border: 1px solid #BCE9E1;
    padding: 6px 10px;
    border-radius: 7px;
}

.stat-icon.mint {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--siswa-mint);
    color: var(--siswa-primary-dark);
    border-radius: 10px;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 24px;
}

.btn-siswa-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 17px;
    border: 1px solid #27877B !important;
    border-radius: 9px !important;
    background: #27877B !important;
    color: #fff !important;
    font-weight: 600;
}

.btn-siswa-primary:hover {
    background: #206C63 !important;
    color: #fff !important;
}

.btn-siswa-secondary {
    border: 1px solid #D6DEE3 !important;
    border-radius: 9px !important;
    background: #F1F4F6 !important;
    color: #47545D !important;
    font-weight: 600;
}

.btn-siswa-danger {
    border: 1px solid #C83D4D !important;
    border-radius: 9px !important;
    background: #C83D4D !important;
    color: #fff !important;
    font-weight: 600;
}

.siswa-modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid #D9F0ED;
    background: var(--siswa-mint);
    color: #20483F;
}

.siswa-modal-header .modal-title {
    font-weight: 700;
    font-size: 1.1rem;
}

.siswa-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding: 14px 20px;
    border-top: 1px solid #E7EEEA;
}

.modal-content {
    overflow: hidden;
    border: 0;
    border-radius: 15px;
    box-shadow: 0 15px 45px rgba(20,43,34,.15);
}

.modal-body {
    padding: 20px;
}

.modal-body .form-label {
    color: #30443A;
    font-weight: 600;
    font-size: .9rem;
}

.modal-body .form-control,
.modal-body .form-select {
    min-height: 42px;
    border-radius: 8px;
    border-color: #DCEAE8;
}

.modal-body .form-control:focus,
.modal-body .form-select:focus {
    border-color: #75CFC2;
    box-shadow: 0 0 0 3px rgba(152,237,225,.25);
}

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

.btn-action-edit {
    border: 1px solid #BFD3EF !important;
    color: #3975B9 !important;
}

.btn-action-delete {
    border: 1px solid #F0C4C8 !important;
    color: #C44755 !important;
}

.siswa-detail-label {
    color: #71817D;
    min-width: 125px;
}

.siswa-detail-value {
    color: #172B2A;
    font-weight: 600;
    text-align: right;
    overflow-wrap: anywhere;
}

@media (max-width: 767px) {
    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .page-header .btn-add-siswa {
        width: 100%;
    }

    .modal-body {
        padding: 16px;
    }
}
</style>

<!-- =====================================
     HEADER HALAMAN
===================================== -->

<div class="page-header">

    <div>
        <h1 class="h3 fw-bold text-dark-mint">
            Pengelolaan Data Siswa
        </h1>

        <p class="text-muted mb-0">
            Kelola informasi siswa dan pendaftaran Smart Education.
        </p>
    </div>

    <button
        type="button"
        class="btn btn-siswa-primary btn-add-siswa"
        data-bs-toggle="modal"
        data-bs-target="#modalTambahSiswa"
    >
        <i class="bi bi-plus-lg"></i>
        Tambah Siswa Baru
    </button>

</div>

<!-- =====================================
     PENCARIAN DAN FILTER
===================================== -->

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
                        placeholder="Cari nama, sekolah, kelas, nomor HP, atau registrasi..."
                        value="<?= siswa_e($search); ?>"
                    >
                </div>
            </div>

            <div class="col-md-3">
                <select name="paket" class="form-select">
                    <option value="">-- Semua Paket Belajar --</option>

                    <?php foreach ($paket_list as $pk): ?>
                        <option
                            value="<?= (int)$pk['id_paket']; ?>"
                            <?= ($filter_paket == $pk['id_paket']) ? 'selected' : ''; ?>
                        >
                            <?= siswa_e($pk['nama_paket']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">-- Semua Status --</option>
                    <option value="Aktif" <?= $filter_status === 'Aktif' ? 'selected' : ''; ?>>
                        Aktif
                    </option>
                    <option value="Nonaktif" <?= $filter_status === 'Nonaktif' ? 'selected' : ''; ?>>
                        Nonaktif
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

<!-- =====================================
     TABEL DATA SISWA
===================================== -->

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
                    <th style="width:50px;">No</th>
                    <th>Nama Siswa</th>
                    <th>Sekolah Asal</th>
                    <th>Kelas</th>
                    <th>Paket Belajar</th>
                    <th>No. HP</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" style="width:150px;">Aksi</th>
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
                                <div class="d-flex align-items-center gap-2">
                                    <div
                                        class="stat-icon mint mb-0"
                                        style="width:35px;height:35px;"
                                    >
                                        <i class="bi bi-person-fill"></i>
                                    </div>

                                    <strong class="text-dark">
                                        <?= siswa_e($s['nama_lengkap']); ?>
                                    </strong>
                                </div>
                            </td>

                            <td><?= siswa_e($s['sekolah_asal']); ?></td>

                            <td><?= siswa_e($s['kelas']); ?></td>

                            <td>
                                <span class="fw-medium text-dark-mint">
                                    <?= siswa_e($s['nama_paket'] ?? 'Belum Terdaftar'); ?>
                                </span>

                                <?php if (!empty($s['harga'])): ?>
                                    <div class="small text-muted">
                                        <?= format_rupiah($s['harga']); ?> / bulan
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td><?= siswa_e($s['no_hp']); ?></td>

                            <td class="text-center">
                                <?php if (($s['status_siswa'] ?? 'Aktif') === 'Aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">

                                <div class="btn-group btn-group-sm">

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

                                    <?php if (($s['status_siswa'] ?? 'Aktif') === 'Aktif'): ?>

                                        <button
                                            type="button"
                                            class="btn btn-action-delete"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalStatusSiswa<?= (int)$s['id_siswa']; ?>"
                                            title="Nonaktifkan Siswa"
                                        >
                                            <i class="bi bi-person-x"></i>
                                        </button>

                                    <?php else: ?>

                                        <button
                                            type="button"
                                            class="btn btn-action-view"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalStatusSiswa<?= (int)$s['id_siswa']; ?>"
                                            title="Aktifkan Kembali"
                                        >
                                            <i class="bi bi-person-check"></i>
                                        </button>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<!-- =====================================
     MODAL DETAIL, EDIT, DAN STATUS
===================================== -->

<?php foreach ($siswa_list as $s): ?>

    <?php $id = (int)$s['id_siswa']; ?>

    <!-- MODAL DETAIL -->

    <div
        class="modal fade"
        id="modalDetailSiswa<?= $id; ?>"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content">

                <div class="modal-header siswa-modal-header">

                    <h5 class="modal-title">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        Detail Siswa
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>

                <div class="modal-body">

                    <div class="text-center mb-4">

                        <div
                            class="stat-icon mint mx-auto mb-2"
                            style="width:60px;height:60px;font-size:1.8rem;"
                        >
                            <i class="bi bi-person"></i>
                        </div>

                        <h5 class="fw-bold mb-1">
                            <?= siswa_e($s['nama_lengkap']); ?>
                        </h5>

                        <span class="badge bg-light text-dark border">
                            <?= siswa_e($s['nomor_registrasi'] ?? 'Belum terdaftar'); ?>
                        </span>

                        <div class="mt-2">
                            <?php if (($s['status_siswa'] ?? 'Aktif') === 'Aktif'): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </div>

                    </div>

                    <h6 class="fw-bold text-mint-deep mb-2">
                        Data Siswa
                    </h6>

                    <ul class="list-group list-group-flush small mb-4">

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Alamat Rumah</span>
                            <span class="siswa-detail-value"><?= nl2br(siswa_e($s['alamat_rumah'])); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Sekolah Asal</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['sekolah_asal']); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Kelas</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['kelas']); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Agama</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['agama']); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Tempat Lahir</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['tempat_lahir']); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Tanggal Lahir</span>
                            <span class="siswa-detail-value"><?= format_tanggal($s['tanggal_lahir']); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">No. HP</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['no_hp']); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">No. Rumah</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['no_rumah'] ?: '-'); ?></span>
                        </li>

                    </ul>

                    <h6 class="fw-bold text-mint-deep mb-2">
                        Data Orang Tua
                    </h6>

                    <ul class="list-group list-group-flush small mb-4">

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Nama Ayah</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['nama_ayah'] ?? '-'); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Nama Ibu</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['nama_ibu'] ?? '-'); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Pemberi Informasi</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['nama_pemberi_informasi'] ?? '-'); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">No. HP Ayah</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['no_hp_ayah'] ?? '-'); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">No. HP Ibu</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['no_hp_ibu'] ?? '-'); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Alamat Orang Tua</span>
                            <span class="siswa-detail-value"><?= nl2br(siswa_e($s['alamat_orang_tua'] ?? '-')); ?></span>
                        </li>

                    </ul>

                    <h6 class="fw-bold text-mint-deep mb-2">
                        Data Pendaftaran
                    </h6>

                    <ul class="list-group list-group-flush small">

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Tanggal Daftar</span>
                            <span class="siswa-detail-value">
                                <?= !empty($s['tanggal_pendaftaran']) ? format_tanggal($s['tanggal_pendaftaran']) : '-'; ?>
                            </span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Paket Belajar</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['nama_paket'] ?? '-'); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Program</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['jenis_program'] ?? '-'); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Pilihan Hari</span>
                            <span class="siswa-detail-value"><?= siswa_e($s['pilihan_hari'] ?? '-'); ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="siswa-detail-label">Pilihan Jam</span>
                            <span class="siswa-detail-value">
                                <?= !empty($s['pilihan_jam']) ? date('H:i', strtotime($s['pilihan_jam'])) : '-'; ?>
                            </span>
                        </li>

                    </ul>

                </div>

                <div class="modal-footer siswa-modal-footer">

                    <button
                        type="button"
                        class="btn btn-siswa-secondary"
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

                    <input type="hidden" name="id_siswa" value="<?= $id; ?>">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= siswa_e($_SESSION['csrf_siswa']); ?>"
                    >

                    <div class="modal-header siswa-modal-header">

                        <h5 class="modal-title">
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

                        <h6 class="fw-bold text-mint-deep mb-3">
                            Data Siswa
                        </h6>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input
                                    type="text"
                                    name="nama_lengkap"
                                    class="form-control"
                                    maxlength="150"
                                    value="<?= siswa_e($s['nama_lengkap']); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Sekolah Asal</label>
                                <input
                                    type="text"
                                    name="sekolah_asal"
                                    class="form-control"
                                    maxlength="150"
                                    value="<?= siswa_e($s['sekolah_asal']); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Kelas</label>
                                <input
                                    type="text"
                                    name="kelas"
                                    class="form-control"
                                    maxlength="30"
                                    value="<?= siswa_e($s['kelas']); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Agama</label>
                                <input
                                    type="text"
                                    name="agama"
                                    class="form-control"
                                    maxlength="30"
                                    value="<?= siswa_e($s['agama']); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tempat Lahir</label>
                                <input
                                    type="text"
                                    name="tempat_lahir"
                                    class="form-control"
                                    maxlength="100"
                                    value="<?= siswa_e($s['tempat_lahir']); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir</label>
                                <input
                                    type="date"
                                    name="tanggal_lahir"
                                    class="form-control"
                                    value="<?= siswa_e($s['tanggal_lahir']); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">No. HP</label>
                                <input
                                    type="text"
                                    name="no_hp"
                                    class="form-control"
                                    maxlength="20"
                                    value="<?= siswa_e($s['no_hp']); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">No. Rumah</label>
                                <input
                                    type="text"
                                    name="no_rumah"
                                    class="form-control"
                                    maxlength="20"
                                    value="<?= siswa_e($s['no_rumah'] ?? ''); ?>"
                                >
                            </div>

                            <div class="col-12">
                                <label class="form-label">Alamat Rumah</label>
                                <textarea
                                    name="alamat_rumah"
                                    class="form-control"
                                    rows="2"
                                    required
                                ><?= siswa_e($s['alamat_rumah']); ?></textarea>
                            </div>

                        </div>

                        <hr>

                        <h6 class="fw-bold text-mint-deep mb-3">
                            Data Orang Tua
                        </h6>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Nama Ayah</label>
                                <input
                                    type="text"
                                    name="nama_ayah"
                                    class="form-control"
                                    value="<?= siswa_e($s['nama_ayah'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nama Ibu</label>
                                <input
                                    type="text"
                                    name="nama_ibu"
                                    class="form-control"
                                    value="<?= siswa_e($s['nama_ibu'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nama Pemberi Informasi</label>
                                <input
                                    type="text"
                                    name="nama_pemberi_informasi"
                                    class="form-control"
                                    value="<?= siswa_e($s['nama_pemberi_informasi'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">No. HP Ayah</label>
                                <input
                                    type="text"
                                    name="no_hp_ayah"
                                    class="form-control"
                                    maxlength="20"
                                    value="<?= siswa_e($s['no_hp_ayah'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">No. HP Ibu</label>
                                <input
                                    type="text"
                                    name="no_hp_ibu"
                                    class="form-control"
                                    maxlength="20"
                                    value="<?= siswa_e($s['no_hp_ibu'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="col-12">
                                <label class="form-label">Alamat Rumah Orang Tua</label>
                                <textarea
                                    name="alamat_orang_tua"
                                    class="form-control"
                                    rows="2"
                                    required
                                ><?= siswa_e($s['alamat_orang_tua'] ?? ''); ?></textarea>
                            </div>

                        </div>

                        <hr>

                        <h6 class="fw-bold text-mint-deep mb-3">
                            Data Pendaftaran
                        </h6>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Pendaftaran</label>
                                <input
                                    type="date"
                                    name="tanggal_pendaftaran"
                                    class="form-control"
                                    value="<?= siswa_e($s['tanggal_pendaftaran'] ?? date('Y-m-d')); ?>"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Paket Belajar</label>
                                <select name="id_paket" class="form-select">
                                    <option value="">-- Pilih Paket --</option>

                                    <?php foreach ($paket_list as $pk): ?>
                                        <option
                                            value="<?= (int)$pk['id_paket']; ?>"
                                            <?= ((string)($s['id_paket'] ?? '') === (string)$pk['id_paket']) ? 'selected' : ''; ?>
                                        >
                                            <?= siswa_e($pk['nama_paket']); ?>
                                            (<?= format_rupiah($pk['harga']); ?>)
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Jenis Program</label>
                                <select name="jenis_program" class="form-select">
                                    <option value="Reguler" <?= ($s['jenis_program'] ?? '') === 'Reguler' ? 'selected' : ''; ?>>
                                        Reguler
                                    </option>
                                    <option value="Privat" <?= ($s['jenis_program'] ?? '') === 'Privat' ? 'selected' : ''; ?>>
                                        Privat
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Pilihan Hari Belajar</label>
                                <input
                                    type="text"
                                    name="pilihan_hari"
                                    class="form-control"
                                    placeholder="Contoh: Senin, Rabu, Jumat"
                                    value="<?= siswa_e($s['pilihan_hari'] ?? ''); ?>"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Pilihan Jam Belajar</label>
                                <input
                                    type="time"
                                    name="pilihan_jam"
                                    class="form-control"
                                    value="<?= siswa_e($s['pilihan_jam'] ?? ''); ?>"
                                >
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
                            <i class="bi bi-save"></i>
                            Simpan Perubahan
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <!-- MODAL UBAH STATUS SISWA -->

    <?php $isAktif = (($s['status_siswa'] ?? 'Aktif') === 'Aktif'); ?>

    <div
        class="modal fade"
        id="modalStatusSiswa<?= $id; ?>"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <form
                    action="<?= base_url('modules/siswa/status.php'); ?>"
                    method="POST"
                >

                    <input type="hidden" name="id_siswa" value="<?= $id; ?>">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= siswa_e($_SESSION['csrf_siswa']); ?>"
                    >

                    <input
                        type="hidden"
                        name="aksi"
                        value="<?= $isAktif ? 'nonaktifkan' : 'aktifkan'; ?>"
                    >

                    <div class="modal-header siswa-modal-header">

                        <h5 class="modal-title">

                            <?php if ($isAktif): ?>
                                <i class="bi bi-person-x me-2 text-danger"></i>
                                Nonaktifkan Siswa
                            <?php else: ?>
                                <i class="bi bi-person-check me-2 text-success"></i>
                                Aktifkan Kembali Siswa
                            <?php endif; ?>

                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>

                    </div>

                    <div class="modal-body">

                        <?php if ($isAktif): ?>
                            Apakah kamu yakin ingin menonaktifkan siswa ini?
                        <?php else: ?>
                            Apakah kamu yakin ingin mengaktifkan kembali siswa ini?
                        <?php endif; ?>

                        <div class="mt-3 p-3 rounded bg-light">

                            <strong>
                                <?= siswa_e($s['nama_lengkap']); ?>
                            </strong>

                            <div class="small text-muted">
                                <?= siswa_e($s['sekolah_asal']); ?>
                            </div>

                            <div class="mt-2">

                                Status saat ini:

                                <?php if ($isAktif): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>

                            </div>

                        </div>

                        <p class="small text-muted mt-3 mb-0">

                            <?php if ($isAktif): ?>
                                Data siswa, riwayat pendaftaran, dan pembayaran
                                tetap tersimpan. Siswa hanya akan diubah
                                statusnya menjadi Nonaktif.
                            <?php else: ?>
                                Status siswa akan dikembalikan menjadi Aktif.
                                Data dan riwayat sebelumnya tetap tersimpan.
                            <?php endif; ?>

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

                        <?php if ($isAktif): ?>

                            <button type="submit" class="btn btn-siswa-danger">
                                <i class="bi bi-person-x me-1"></i>
                                Ya, Nonaktifkan
                            </button>

                        <?php else: ?>

                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-person-check me-1"></i>
                                Ya, Aktifkan
                            </button>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

        </div>

    </div>

<?php endforeach; ?>

<!-- =====================================
     MODAL TAMBAH SISWA
===================================== -->

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

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= siswa_e($_SESSION['csrf_siswa']); ?>"
                >

                <div class="modal-header siswa-modal-header">

                    <h5 class="modal-title">
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

                    <h6 class="fw-bold text-mint-deep mb-3">
                        Data Siswa
                    </h6>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input
                                type="text"
                                name="nama_lengkap"
                                class="form-control"
                                maxlength="150"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sekolah Asal</label>
                            <input
                                type="text"
                                name="sekolah_asal"
                                class="form-control"
                                maxlength="150"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Kelas</label>
                            <input
                                type="text"
                                name="kelas"
                                class="form-control"
                                maxlength="30"
                                placeholder="Contoh: Kelas 5 SD"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Agama</label>
                            <input
                                type="text"
                                name="agama"
                                class="form-control"
                                maxlength="30"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tempat Lahir</label>
                            <input
                                type="text"
                                name="tempat_lahir"
                                class="form-control"
                                maxlength="100"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tanggal Lahir</label>
                            <input
                                type="date"
                                name="tanggal_lahir"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">No. HP</label>
                            <input
                                type="text"
                                name="no_hp"
                                class="form-control"
                                maxlength="20"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">No. Rumah</label>
                            <input
                                type="text"
                                name="no_rumah"
                                class="form-control"
                                maxlength="20"
                            >
                        </div>

                        <div class="col-12">
                            <label class="form-label">Alamat Rumah</label>
                            <textarea
                                name="alamat_rumah"
                                class="form-control"
                                rows="2"
                                required
                            ></textarea>
                        </div>

                    </div>

                    <hr>

                    <h6 class="fw-bold text-mint-deep mb-3">
                        Data Orang Tua
                    </h6>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Nama Ayah</label>
                            <input
                                type="text"
                                name="nama_ayah"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Ibu</label>
                            <input
                                type="text"
                                name="nama_ibu"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Pemberi Informasi</label>
                            <input
                                type="text"
                                name="nama_pemberi_informasi"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">No. HP Ayah</label>
                            <input
                                type="text"
                                name="no_hp_ayah"
                                class="form-control"
                                maxlength="20"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">No. HP Ibu</label>
                            <input
                                type="text"
                                name="no_hp_ibu"
                                class="form-control"
                                maxlength="20"
                                required
                            >
                        </div>

                        <div class="col-12">
                            <label class="form-label">Alamat Rumah Orang Tua</label>
                            <textarea
                                name="alamat_orang_tua"
                                class="form-control"
                                rows="2"
                                required
                            ></textarea>
                        </div>

                    </div>

                    <hr>

                    <h6 class="fw-bold text-mint-deep mb-3">
                        Data Pendaftaran
                    </h6>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pendaftaran</label>
                            <input
                                type="date"
                                name="tanggal_pendaftaran"
                                class="form-control"
                                value="<?= date('Y-m-d'); ?>"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Paket Belajar</label>
                            <select name="id_paket" class="form-select" required>

                                <option value="">-- Pilih Paket Belajar --</option>

                                <?php foreach ($paket_list as $pk): ?>
                                    <option value="<?= (int)$pk['id_paket']; ?>">
                                        <?= siswa_e($pk['nama_paket']); ?>
                                        (<?= format_rupiah($pk['harga']); ?>)
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Jenis Program</label>
                            <select name="jenis_program" class="form-select" required>
                                <option value="">-- Pilih Program --</option>
                                <option value="Reguler">Reguler</option>
                                <option value="Privat">Privat</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pilihan Hari Belajar</label>
                            <input
                                type="text"
                                name="pilihan_hari"
                                class="form-control"
                                placeholder="Contoh: Senin, Rabu, Jumat"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pilihan Jam Belajar</label>
                            <input
                                type="time"
                                name="pilihan_jam"
                                class="form-control"
                                required
                            >
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
                        <i class="bi bi-save"></i>
                        Simpan Data Siswa
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php require_once BASEPATH . '/includes/footer.php'; ?>
