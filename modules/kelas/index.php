
<?php
/**
 * Data Kelas
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'kelas';
$page_title = 'Pengelolaan Data Kelas';

/* =========================
   AMBIL DATA KELAS
========================= */

$sql = "
    SELECT
        k.*,
        p.nama_paket,
        p.harga,
        t.nama_tutor,
        t.keahlian,
        r.nama_ruangan,
        r.kapasitas AS kapasitas_ruangan,
        (
            SELECT COUNT(*)
            FROM anggota_kelas ak
            WHERE ak.id_kelas = k.id_kelas
        ) AS total_siswa_terdaftar
    FROM kelas k
    JOIN paket_belajar p ON k.id_paket = p.id_paket
    JOIN tutor t ON k.id_tutor = t.id_tutor
    LEFT JOIN ruangan r ON k.id_ruangan = r.id_ruangan
    ORDER BY k.id_kelas ASC
";

$kelas_list = $pdo->query($sql)->fetchAll();

/* =========================
   DATA DROPDOWN
========================= */

$paket_list = $pdo->query("
    SELECT *
    FROM paket_belajar
    WHERE status = 'Aktif'
    ORDER BY nama_paket ASC
")->fetchAll();

$tutor_list = $pdo->query("
    SELECT *
    FROM tutor
    WHERE status_tutor = 'Aktif'
    ORDER BY nama_tutor ASC
")->fetchAll();

$ruangan_list = $pdo->query("
    SELECT *
    FROM ruangan
    WHERE status_ruangan = 'Tersedia'
    ORDER BY nama_ruangan ASC
")->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<style>
:root {
    --kelas-primary: #258879;
    --kelas-primary-dark: #1d7165;
    --kelas-mint: #98EDE1;
    --kelas-soft: #E8FDFF;
    --kelas-text: #173f3b;
    --kelas-muted: #778982;
    --kelas-border: #dcece8;
}

/* =========================
   HALAMAN
========================= */

.kelas-page {
    padding: 22px 24px;
    color: var(--kelas-text);
}

.kelas-toolbar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 22px;
}

.btn-kelas-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 44px;
    padding: 11px 20px;
    border: 1px solid var(--kelas-primary);
    border-radius: 10px;
    background: var(--kelas-primary);
    color: #fff !important;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(37, 136, 121, .15);
    transition: .2s ease;
}

.btn-kelas-primary:hover {
    background: var(--kelas-primary-dark);
    border-color: var(--kelas-primary-dark);
    transform: translateY(-1px);
}

/* =========================
   TABEL KELAS
========================= */

.kelas-table-card {
    overflow: hidden;
    border: 1px solid #edf2f0;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 3px 12px rgba(20, 60, 50, .05);
}

.kelas-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    border-bottom: 1px solid var(--kelas-border);
}

.kelas-table-header h5 {
    margin: 0;
    color: var(--kelas-text);
    font-size: 16px;
    font-weight: 700;
}

.kelas-total {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 27px;
    height: 25px;
    margin-left: 7px;
    padding: 0 8px;
    border-radius: 7px;
    background: var(--kelas-soft);
    color: var(--kelas-primary);
    font-size: 12px;
    font-weight: 700;
}

.kelas-table {
    min-width: 1050px;
    margin: 0;
    vertical-align: middle;
}

.kelas-table thead th {
    padding: 14px 13px;
    border-bottom: 1px solid var(--kelas-border);
    background: #effbf8;
    color: #315a52;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.kelas-table tbody td {
    padding: 14px 13px;
    border-bottom: 1px solid #edf2f0;
    color: #40564f;
    font-size: 11px;
}

.kelas-table tbody tr:last-child td {
    border-bottom: none;
}

.kelas-table tbody tr:hover {
    background: #f9fdfc;
}

.kelas-name {
    display: block;
    margin-bottom: 5px;
    color: #183f39;
    font-size: 12px;
    font-weight: 700;
}

.kelas-code {
    display: inline-block;
    padding: 4px 7px;
    border: 1px solid var(--kelas-border);
    border-radius: 5px;
    background: #f7faf9;
    color: #61736c;
    font-family: monospace;
    font-size: 10px;
}

.kelas-package {
    display: inline-block;
    max-width: 170px;
    padding: 5px 8px;
    border-radius: 6px;
    background: var(--kelas-soft);
    color: #28796d;
    font-size: 10px;
    font-weight: 600;
}

.kelas-badge {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 6px;
    background: #f1f5f4;
    color: #53665e;
    font-size: 10px;
    font-weight: 600;
}

.kelas-status-active {
    background: #e3f8ed;
    color: #198754;
}

.kelas-status-other {
    background: #f1f3f5;
    color: #65717a;
}

.kelas-progress {
    width: 75px;
    height: 5px;
    margin-top: 6px;
    overflow: hidden;
    border-radius: 10px;
    background: #e9f0ed;
}

.kelas-progress-bar {
    height: 100%;
    border-radius: 10px;
    background: var(--kelas-primary);
}

.kelas-actions {
    display: flex;
    justify-content: center;
    gap: 6px;
}

.btn-kelas-icon {
    display: inline-flex;
    width: 33px;
    height: 33px;
    align-items: center;
    justify-content: center;
    border: 1px solid;
    border-radius: 7px;
    background: #fff;
    font-size: 13px;
    text-decoration: none;
    transition: .2s ease;
}

.btn-kelas-members {
    border-color: #b9e6d3;
    color: #198754;
}

.btn-kelas-members:hover {
    background: #198754;
    color: #fff;
}

.btn-kelas-edit {
    border-color: #b9d8f5;
    color: #2878bd;
}

.btn-kelas-edit:hover {
    background: #2878bd;
    color: #fff;
}

.btn-kelas-delete {
    border-color: #f1c5ca;
    color: #dc3545;
}

.btn-kelas-delete:hover {
    background: #dc3545;
    color: #fff;
}

.kelas-empty {
    padding: 55px 20px !important;
    color: #84958f !important;
    text-align: center;
}

/* =========================
   MODAL TAMBAH DAN EDIT
========================= */

.kelas-modal .modal-dialog {
    max-width: 850px;
}

.kelas-modal .modal-content {
    overflow: hidden;
    border: none;
    border-radius: 17px;
    box-shadow: 0 15px 45px rgba(0, 0, 0, .15);
}

.kelas-modal .modal-header {
    padding: 17px 22px;
    border-bottom: 1px solid var(--kelas-border);
    background: var(--kelas-soft);
}

.kelas-modal .modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #194b44;
    font-size: 18px;
    font-weight: 700;
}

.kelas-modal .modal-title i {
    color: var(--kelas-primary);
}

.kelas-modal .modal-body {
    padding: 22px;
}

.kelas-modal .form-label {
    display: block;
    margin-bottom: 7px;
    color: #254b45;
    font-size: 11px;
    font-weight: 600;
}

.kelas-modal .form-control,
.kelas-modal .form-select {
    min-height: 41px;
    padding: 10px 12px;
    border: 1px solid #d5e7e2;
    border-radius: 9px;
    background-color: #fff;
    color: #344b46;
    font-size: 12px;
    box-shadow: none;
}

.kelas-modal .form-control:focus,
.kelas-modal .form-select:focus {
    border-color: var(--kelas-primary);
    box-shadow: 0 0 0 3px rgba(37, 136, 121, .10);
}

.kelas-modal .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding: 14px 22px;
    border-top: 1px solid var(--kelas-border);
    background: #fff;
}

.btn-kelas-cancel {
    min-width: 78px;
    padding: 10px 16px;
    border: 1px solid #737e85;
    border-radius: 9px;
    background: #737e85;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
}

.btn-kelas-cancel:hover {
    background: #5e686e;
    color: #fff;
}

.btn-kelas-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-width: 135px;
    padding: 10px 17px;
    border: 1px solid var(--kelas-primary);
    border-radius: 9px;
    background: var(--kelas-primary);
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    transition: .2s ease;
}

.btn-kelas-save:hover {
    border-color: var(--kelas-primary-dark);
    background: var(--kelas-primary-dark);
    color: #fff;
}

@media (max-width: 768px) {
    .kelas-page {
        padding: 15px 12px;
    }

    .kelas-modal .modal-dialog {
        margin: 12px;
    }

    .kelas-modal .modal-body {
        padding: 17px;
    }

    .kelas-table-header {
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<div class="kelas-page">

    <!-- TOMBOL TAMBAH -->
    <div class="kelas-toolbar">
        <button type="button"
                class="btn-kelas-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalTambahKelas">
            <i class="bi bi-plus-lg"></i>
            Buat Kelas Baru
        </button>
    </div>

    <!-- TABEL DATA KELAS -->
    <div class="kelas-table-card">

        <div class="kelas-table-header">
            <h5>
                <i class="bi bi-collection me-2" style="color:#258879;"></i>
                Daftar Kelas Bimbingan
                <span class="kelas-total"><?= count($kelas_list) ?></span>
            </h5>
        </div>

        <div class="table-responsive">

            <table class="table table-hover kelas-table">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode & Nama Kelas</th>
                        <th>Paket Belajar</th>
                        <th>Tutor Pengajar</th>
                        <th>Ruangan Default</th>
                        <th>Waktu / Sesi</th>
                        <th>Kapasitas / Siswa</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($kelas_list)): ?>

                    <tr>
                        <td colspan="9" class="kelas-empty">
                            <i class="bi bi-collection fs-3 d-block mb-2"></i>
                            Belum ada data kelas yang terdaftar.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php $no = 1; ?>

                    <?php foreach ($kelas_list as $k): ?>

                        <?php
                        $total_siswa = (int)$k['total_siswa_terdaftar'];
                        $maks_siswa = max(1, (int)$k['max_siswa']);
                        $persentase = min(100, round(($total_siswa / $maks_siswa) * 100));
                        ?>

                        <tr>

                            <td><?= $no++ ?></td>

                            <td>
                                <strong class="kelas-name">
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </strong>

                                <span class="kelas-code">
                                    <?= htmlspecialchars($k['kode_kelas']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="kelas-package">
                                    <?= htmlspecialchars($k['nama_paket']) ?>
                                </span>
                            </td>

                            <td>
                                <strong class="d-block text-dark">
                                    <?= htmlspecialchars($k['nama_tutor']) ?>
                                </strong>

                                <small class="text-muted">
                                    <?= htmlspecialchars($k['keahlian']) ?>
                                </small>
                            </td>

                            <td>
                                <?= htmlspecialchars($k['nama_ruangan'] ?? 'Belum ditentukan') ?>
                            </td>

                            <td>
                                <?php if (!empty($k['hari'])): ?>

                                    <span class="kelas-badge">
                                        <?= htmlspecialchars($k['hari']) ?>
                                    </span>

                                    <div class="small text-muted mt-1">
                                        <?= htmlspecialchars(substr($k['jam_mulai'] ?? '', 0, 5)) ?>
                                        -
                                        <?= htmlspecialchars(substr($k['jam_selesai'] ?? '', 0, 5)) ?>
                                    </div>

                                <?php else: ?>

                                    <span class="text-muted small">
                                        Via Penjadwalan CSP
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <strong style="color:#258879;">
                                    <?= $total_siswa ?>
                                </strong>

                                <span class="text-muted">
                                    / <?= (int)$k['max_siswa'] ?> Siswa
                                </span>

                                <div class="kelas-progress">
                                    <div class="kelas-progress-bar"
                                         style="width:<?= $persentase ?>%;">
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="kelas-badge <?= ($k['status_kelas'] === 'Aktif') ? 'kelas-status-active' : 'kelas-status-other' ?>">
                                    <?= htmlspecialchars($k['status_kelas']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="kelas-actions">

                                    <a href="<?= base_url('modules/kelas/anggota.php?id=' . (int)$k['id_kelas']) ?>"
                                       class="btn-kelas-icon btn-kelas-members"
                                       title="Kelola Siswa Kelas">
                                        <i class="bi bi-people"></i>
                                    </a>

                                    <button type="button"
                                            class="btn-kelas-icon btn-kelas-edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditKelas<?= (int)$k['id_kelas'] ?>"
                                            title="Edit Kelas">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <a href="<?= base_url('modules/kelas/delete.php?id=' . (int)$k['id_kelas']) ?>"
                                       class="btn-kelas-icon btn-kelas-delete btn-confirm-delete"
                                       data-item-name="<?= htmlspecialchars($k['nama_kelas']) ?>"
                                       title="Hapus Kelas">
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

<!-- =========================
     MODAL EDIT KELAS
========================= -->

<?php foreach ($kelas_list as $k): ?>

<div class="modal fade kelas-modal"
     id="modalEditKelas<?= (int)$k['id_kelas'] ?>"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form action="<?= base_url('modules/kelas/edit.php') ?>"
                  method="POST">

                <input type="hidden"
                       name="id_kelas"
                       value="<?= (int)$k['id_kelas'] ?>">

                <div class="modal-header">

                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i>
                        Edit Data Kelas
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-5">
                            <label class="form-label">
                                Kode Kelas <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="kode_kelas"
                                   class="form-control"
                                   value="<?= htmlspecialchars($k['kode_kelas']) ?>"
                                   required>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label">
                                Nama Kelas <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="nama_kelas"
                                   class="form-control"
                                   value="<?= htmlspecialchars($k['nama_kelas']) ?>"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Paket Belajar <span class="text-danger">*</span>
                            </label>

                            <select name="id_paket" class="form-select" required>

                                <?php foreach ($paket_list as $pk): ?>
                                    <option value="<?= $pk['id_paket'] ?>"
                                        <?= ($k['id_paket'] == $pk['id_paket']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pk['nama_paket']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Tutor Pengajar <span class="text-danger">*</span>
                            </label>

                            <select name="id_tutor" class="form-select" required>

                                <?php foreach ($tutor_list as $t): ?>
                                    <option value="<?= $t['id_tutor'] ?>"
                                        <?= ($k['id_tutor'] == $t['id_tutor']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['nama_tutor']) ?>
                                        (<?= htmlspecialchars($t['keahlian']) ?>)
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Ruangan
                            </label>

                            <select name="id_ruangan" class="form-select">

                                <option value="">
                                    -- Ditentukan Otomatis oleh CSP --
                                </option>

                                <?php foreach ($ruangan_list as $r): ?>
                                    <option value="<?= $r['id_ruangan'] ?>"
                                        <?= ($k['id_ruangan'] == $r['id_ruangan']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['nama_ruangan']) ?>
                                        (Kapasitas: <?= (int)$r['kapasitas'] ?>)
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Kapasitas Maksimal Siswa
                            </label>

                            <input type="number"
                                   name="max_siswa"
                                   class="form-control"
                                   value="<?= (int)$k['max_siswa'] ?>"
                                   min="1"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Hari Belajar
                            </label>

                            <select name="hari" class="form-select">

                                <option value="">
                                    -- Alokasikan via CSP --
                                </option>

                                <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h): ?>
                                    <option value="<?= $h ?>"
                                        <?= ($k['hari'] === $h) ? 'selected' : '' ?>>
                                        <?= $h ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Jam Mulai
                            </label>

                            <input type="time"
                                   name="jam_mulai"
                                   class="form-control"
                                   value="<?= htmlspecialchars($k['jam_mulai'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Jam Selesai
                            </label>

                            <input type="time"
                                   name="jam_selesai"
                                   class="form-control"
                                   value="<?= htmlspecialchars($k['jam_selesai'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Status Kelas
                            </label>

                            <select name="status_kelas" class="form-select">

                                <option value="Aktif"
                                    <?= ($k['status_kelas'] === 'Aktif') ? 'selected' : '' ?>>
                                    Aktif
                                </option>

                                <option value="Selesai"
                                    <?= ($k['status_kelas'] === 'Selesai') ? 'selected' : '' ?>>
                                    Selesai
                                </option>

                                <option value="Batal"
                                    <?= ($k['status_kelas'] === 'Batal') ? 'selected' : '' ?>>
                                    Batal
                                </option>

                            </select>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn-kelas-cancel"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn-kelas-save">
                        <i class="bi bi-save"></i>
                        Simpan Perubahan
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<?php endforeach; ?>

<!-- =========================
     MODAL TAMBAH KELAS
========================= -->

<div class="modal fade kelas-modal"
     id="modalTambahKelas"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form action="<?= base_url('modules/kelas/create.php') ?>"
                  method="POST">

                <div class="modal-header">

                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle"></i>
                        Buat Kelas Belajar Baru
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-5">
                            <label class="form-label">
                                Kode Kelas <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="kode_kelas"
                                   class="form-control"
                                   placeholder="Contoh: KLS-2026-007"
                                   required>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label">
                                Nama Kelas <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="nama_kelas"
                                   class="form-control"
                                   placeholder="Contoh: Kelas Kedinasan Bravo"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Paket Belajar <span class="text-danger">*</span>
                            </label>

                            <select name="id_paket" class="form-select" required>

                                <option value="">
                                    -- Pilih Paket Belajar --
                                </option>

                                <?php foreach ($paket_list as $pk): ?>
                                    <option value="<?= $pk['id_paket'] ?>">
                                        <?= htmlspecialchars($pk['nama_paket']) ?>
                                        (<?= format_rupiah($pk['harga']) ?>)
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Tutor Pengajar <span class="text-danger">*</span>
                            </label>

                            <select name="id_tutor" class="form-select" required>

                                <option value="">
                                    -- Pilih Tutor Pengajar --
                                </option>

                                <?php foreach ($tutor_list as $t): ?>
                                    <option value="<?= $t['id_tutor'] ?>">
                                        <?= htmlspecialchars($t['nama_tutor']) ?>
                                        (<?= htmlspecialchars($t['keahlian']) ?>)
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Ruangan Awal
                            </label>

                            <select name="id_ruangan" class="form-select">

                                <option value="">
                                    -- Otomatis Ditentukan CSP --
                                </option>

                                <?php foreach ($ruangan_list as $r): ?>
                                    <option value="<?= $r['id_ruangan'] ?>">
                                        <?= htmlspecialchars($r['nama_ruangan']) ?>
                                        (Kapasitas: <?= (int)$r['kapasitas'] ?>)
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Maksimal Siswa
                            </label>

                            <input type="number"
                                   name="max_siswa"
                                   class="form-control"
                                   value="15"
                                   min="1"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Hari Belajar
                            </label>

                            <select name="hari" class="form-select">

                                <option value="">
                                    -- Alokasikan via CSP --
                                </option>

                                <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h): ?>
                                    <option value="<?= $h ?>">
                                        <?= $h ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Jam Mulai
                            </label>

                            <input type="time"
                                   name="jam_mulai"
                                   class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Jam Selesai
                            </label>

                            <input type="time"
                                   name="jam_selesai"
                                   class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Status Kelas
                            </label>

                            <select name="status_kelas" class="form-select">
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Batal">Batal</option>
                            </select>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn-kelas-cancel"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn-kelas-save">
                        <i class="bi bi-check-circle"></i>
                        Simpan Kelas
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<?php require_once BASEPATH . '/includes/footer.php'; ?>
