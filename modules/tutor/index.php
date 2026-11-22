<?php
/**
 * Data Tutor
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'tutor';
$page_title = 'Pengelolaan Data Tutor';

// Ambil data tutor beserta jumlah kelas yang diampu
$sql = "
    SELECT
        t.*,
        COUNT(k.id_kelas) AS total_kelas
    FROM tutor t
    LEFT JOIN kelas k ON t.id_tutor = k.id_tutor
    GROUP BY t.id_tutor
    ORDER BY t.id_tutor ASC
";

$tutor_list = $pdo->query($sql)->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<style>
/* ==============================
   TUTOR PAGE
============================== */

:root {
    --tutor-primary: #258879;
    --tutor-primary-dark: #1d7165;
    --tutor-mint: #98EDE1;
    --tutor-soft: #E8FDFF;
    --tutor-text: #173f3b;
    --tutor-border: #dcece8;
}

.tutor-page {
    padding: 22px 24px;
    color: var(--tutor-text);
}

.tutor-toolbar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: 22px;
}

.btn-tutor-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 12px 20px;
    min-height: 44px;
    border: 1px solid var(--tutor-primary);
    border-radius: 11px;
    background: var(--tutor-primary);
    color: #fff !important;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: .2s ease;
    box-shadow: 0 4px 12px rgba(37, 136, 121, .15);
}

.btn-tutor-primary:hover {
    background: var(--tutor-primary-dark);
    border-color: var(--tutor-primary-dark);
    transform: translateY(-1px);
}

.tutor-card {
    background: #fff;
    border: 1px solid #edf2f0;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 3px 12px rgba(20, 60, 50, .05);
}

.tutor-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 19px 22px;
    background: #fff;
    border-bottom: 1px solid var(--tutor-border);
}

.tutor-card-header h5 {
    margin: 0;
    color: var(--tutor-text);
    font-size: 16px;
    font-weight: 700;
}

.tutor-count {
    padding: 5px 10px;
    border-radius: 20px;
    background: var(--tutor-soft);
    color: var(--tutor-primary-dark);
    font-size: 12px;
    font-weight: 700;
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
    padding: 15px;
    color: #344b46;
    border-bottom: 1px solid #edf2f0;
    font-size: 12px;
    vertical-align: middle;
}

.tutor-table tbody tr:last-child td {
    border-bottom: none;
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
    background: #fff;
    font-size: 13px;
    transition: .2s ease;
}

.btn-tutor-edit {
    color: #258879;
    border: 1px solid #b9e4dc;
}

.btn-tutor-edit:hover {
    color: #fff;
    background: #258879;
    border-color: #258879;
}

.btn-tutor-delete {
    color: #dc3545;
    border: 1px solid #f2c5ca;
    text-decoration: none;
}

.btn-tutor-delete:hover {
    color: #fff;
    background: #dc3545;
    border-color: #dc3545;
}

/* ==============================
   MODAL
============================== */

.tutor-modal .modal-dialog {
    max-width: 600px;
}

.tutor-modal .modal-content {
    border: none;
    border-radius: 17px;
    overflow: hidden;
    box-shadow: 0 15px 45px rgba(0, 0, 0, .15);
}

.tutor-modal .modal-header {
    padding: 18px 22px;
    background: var(--tutor-soft);
    border-bottom: 1px solid var(--tutor-border);
}

.tutor-modal .modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #194b44;
    font-size: 19px;
    font-weight: 700;
}

.tutor-modal .modal-title i {
    color: var(--tutor-primary);
}

.tutor-modal .modal-body {
    padding: 23px;
}

.tutor-modal .form-label {
    display: block;
    margin-bottom: 8px;
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
    background-color: #fff;
    color: #344b46;
    font-size: 12px;
    box-shadow: none;
}

.tutor-modal textarea.form-control {
    min-height: 85px;
}

.tutor-modal .form-control:focus,
.tutor-modal .form-select:focus {
    border-color: var(--tutor-primary);
    box-shadow: 0 0 0 3px rgba(37, 136, 121, .10);
}

.tutor-modal .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding: 15px 22px;
    border-top: 1px solid var(--tutor-border);
    background: #fff;
}

.btn-tutor-cancel {
    min-width: 78px;
    padding: 10px 16px;
    border: 1px solid #737e85;
    border-radius: 9px;
    background: #737e85;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
}

.btn-tutor-cancel:hover {
    background: #5e686e;
    color: #fff;
}

.btn-tutor-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-width: 135px;
    padding: 10px 17px;
    border: 1px solid var(--tutor-primary);
    border-radius: 9px;
    background: var(--tutor-primary);
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    transition: .2s ease;
}

.btn-tutor-save:hover {
    background: var(--tutor-primary-dark);
    border-color: var(--tutor-primary-dark);
    color: #fff;
}

.tutor-empty {
    padding: 55px 20px !important;
    color: #84958f !important;
    text-align: center;
}

@media (max-width: 768px) {
    .tutor-page {
        padding: 15px 12px;
    }

    .tutor-modal .modal-dialog {
        margin: 12px;
    }

    .tutor-modal .modal-body {
        padding: 17px;
    }

    .tutor-modal .modal-title {
        font-size: 16px;
    }
}
</style>

<div class="tutor-page">

    <!-- TOMBOL TAMBAH -->
    <div class="tutor-toolbar">
        <button type="button"
                class="btn-tutor-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalTambahTutor">
            <i class="bi bi-plus-lg"></i>
            Tambah Tutor Baru
        </button>
    </div>

    <!-- DAFTAR TUTOR -->
    <div class="tutor-card">

        <div class="tutor-card-header">
            <h5>
                <i class="bi bi-person-badge me-2"></i>
                Daftar Tutor Pengajar
            </h5>

            <span class="tutor-count">
                <?= count($tutor_list) ?> Tutor
            </span>
        </div>

        <div class="table-responsive">
            <table class="table tutor-table align-middle">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>NIP / Kode</th>
                        <th>Nama Tutor</th>
                        <th>Bidang Keahlian</th>
                        <th>No. Telepon / Email</th>
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
                            Belum ada data tutor pengajar.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php $no = 1; ?>

                    <?php foreach ($tutor_list as $t): ?>

                    <tr>
                        <td><?= $no++ ?></td>

                        <td>
                            <span class="tutor-code">
                                <?= htmlspecialchars($t['nip']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="tutor-name">
                                <?= htmlspecialchars($t['nama_tutor']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="tutor-skill">
                                <?= htmlspecialchars($t['keahlian']) ?>
                            </span>
                        </td>

                        <td>
                            <div>
                                <i class="bi bi-whatsapp text-success me-1"></i>
                                <?= htmlspecialchars($t['no_telepon'] ?? '-') ?>
                            </div>
                            <small class="text-muted">
                                <?= htmlspecialchars($t['email'] ?? '-') ?>
                            </small>
                        </td>

                        <td>
                            <span class="badge bg-info-subtle text-info-emphasis">
                                <?= (int)$t['total_kelas'] ?> Kelas
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

                                <button type="button"
                                        class="btn-tutor-icon btn-tutor-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditTutor<?= (int)$t['id_tutor'] ?>"
                                        title="Edit Tutor">
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <a href="<?= base_url('modules/tutor/delete.php?id=' . (int)$t['id_tutor']) ?>"
                                   class="btn-tutor-icon btn-tutor-delete btn-confirm-delete"
                                   data-item-name="<?= htmlspecialchars($t['nama_tutor']) ?>"
                                   title="Hapus Tutor">
                                    <i class="bi bi-trash"></i>
                                </a>

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

<!-- ==============================
     MODAL TAMBAH TUTOR
============================== -->

<div class="modal fade tutor-modal"
     id="modalTambahTutor"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form action="<?= base_url('modules/tutor/create.php') ?>"
                  method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus-fill"></i>
                        Tambah Tutor Baru
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>
                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">
                                NIP / Kode Tutor <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="nip"
                                   class="form-control"
                                   placeholder="Contoh: TUTOR006"
                                   maxlength="30"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Nama Lengkap & Gelar <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="nama_tutor"
                                   class="form-control"
                                   placeholder="Nama lengkap tutor"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Bidang Keahlian / Mata Pelajaran
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="keahlian"
                                   class="form-control"
                                   placeholder="Contoh: Kimia Organik & TPS SNBT"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                No. Telepon / WhatsApp
                            </label>
                            <input type="text"
                                   name="no_telepon"
                                   class="form-control"
                                   placeholder="0852-xxxx-xxxx">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Status Tutor
                            </label>
                            <select name="status_tutor" class="form-select">
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Cuti">Cuti</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   placeholder="tutor@smarteducation.sch.id">
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn-tutor-cancel"
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

<!-- ==============================
     MODAL EDIT TUTOR
============================== -->

<?php foreach ($tutor_list as $t): ?>

<div class="modal fade tutor-modal"
     id="modalEditTutor<?= (int)$t['id_tutor'] ?>"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form action="<?= base_url('modules/tutor/edit.php') ?>"
                  method="POST">

                <input type="hidden"
                       name="id_tutor"
                       value="<?= (int)$t['id_tutor'] ?>">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i>
                        Edit Data Tutor
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>
                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">
                                NIP / Kode Tutor <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="nip"
                                   class="form-control"
                                   value="<?= htmlspecialchars($t['nip']) ?>"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Nama Lengkap & Gelar <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="nama_tutor"
                                   class="form-control"
                                   value="<?= htmlspecialchars($t['nama_tutor']) ?>"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Bidang Keahlian / Mata Pelajaran
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="keahlian"
                                   class="form-control"
                                   value="<?= htmlspecialchars($t['keahlian']) ?>"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                No. Telepon / WhatsApp
                            </label>
                            <input type="text"
                                   name="no_telepon"
                                   class="form-control"
                                   value="<?= htmlspecialchars($t['no_telepon'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Tutor</label>
                            <select name="status_tutor" class="form-select">
                                <option value="Aktif"
                                    <?= ($t['status_tutor'] === 'Aktif') ? 'selected' : '' ?>>
                                    Aktif
                                </option>

                                <option value="Cuti"
                                    <?= ($t['status_tutor'] === 'Cuti') ? 'selected' : '' ?>>
                                    Cuti
                                </option>

                                <option value="Non-Aktif"
                                    <?= ($t['status_tutor'] === 'Non-Aktif') ? 'selected' : '' ?>>
                                    Non-Aktif
                                </option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="<?= htmlspecialchars($t['email'] ?? '') ?>">
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn-tutor-cancel"
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

<?php require_once BASEPATH . '/includes/footer.php'; ?>
