<?php
/**
 * Paket Belajar
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';
require_login();

$current_page = 'paket';
$page_title = 'Paket Bimbingan Belajar';

$user = current_user();
$is_admin = in_array($user['role'], ['superadmin', 'admin'], true);

// Ambil data paket dan jumlah siswa terdaftar
$sql = "
    SELECT
        p.*,
        COUNT(s.id_siswa) AS jumlah_siswa
    FROM paket_belajar p
    LEFT JOIN siswa s ON p.id_paket = s.id_paket
    GROUP BY p.id_paket
    ORDER BY p.id_paket ASC
";

$paket_list = $pdo->query($sql)->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<style>
/* =========================================
   WARNA DAN TAMPILAN PAKET
========================================= */

:root {
    --paket-primary: #98EDE1;
    --paket-primary-dark: #278779;
    --paket-primary-hover: #207568;
    --paket-soft: #E8FDFF;
    --paket-text: #173F3B;
    --paket-muted: #718783;
    --paket-border: #DCECE9;
}

/* Header halaman */

.paket-page-header {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin: 18px 0 26px;
}

/* Tombol utama */

.btn-paket-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 12px 21px;
    border: none;
    border-radius: 11px;
    background: var(--paket-primary-dark);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
    box-shadow: 0 4px 12px rgba(39, 135, 121, .13);
}

.btn-paket-primary:hover {
    background: var(--paket-primary-hover);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 15px rgba(39, 135, 121, .2);
}

/* Tombol batal */

.btn-paket-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 19px;
    border: none;
    border-radius: 9px;
    background: #77818A;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    transition: .2s ease;
}

.btn-paket-secondary:hover {
    background: #626C75;
    color: #fff;
}

/* Tombol edit */

.btn-paket-edit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    flex: 1;
    padding: 9px 12px;
    border: 1px solid var(--paket-primary-dark);
    border-radius: 9px;
    background: #fff;
    color: var(--paket-primary-dark);
    font-size: 12px;
    font-weight: 600;
    transition: .2s ease;
}

.btn-paket-edit:hover {
    background: var(--paket-primary-dark);
    color: #fff;
}

/* Tombol hapus */

.btn-paket-delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    min-width: 42px;
    border: 1px solid #F1A5A5;
    border-radius: 9px;
    background: #fff;
    color: #DC4545;
    text-decoration: none;
    transition: .2s ease;
}

.btn-paket-delete:hover {
    background: #DC4545;
    color: #fff;
}

/* Kartu paket */

.paket-card {
    height: 100%;
    overflow: hidden;
    border: 1px solid #EDF2F1;
    border-radius: 15px;
    background: #fff;
    box-shadow: 0 3px 12px rgba(20, 60, 55, .045);
    transition: .2s ease;
}

.paket-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 7px 18px rgba(20, 60, 55, .09);
}

.paket-card .card-header {
    padding: 18px 18px 10px;
}

.paket-card .card-body {
    padding: 8px 18px 18px;
}

.paket-card-title {
    color: var(--paket-text);
    font-size: 17px;
    font-weight: 700;
    line-height: 1.45;
}

.paket-price {
    color: #172F2C;
    font-size: 22px;
    font-weight: 800;
}

.paket-description {
    min-height: 48px;
    color: var(--paket-muted);
    font-size: 12px;
    line-height: 1.7;
}

.badge-paket-active {
    padding: 5px 9px;
    border-radius: 20px;
    background: var(--paket-soft);
    color: #207568;
    font-size: 10px;
    font-weight: 700;
}

.paket-info {
    padding: 13px;
    border-radius: 10px;
    background: #F6F9F8;
    font-size: 12px;
}

.paket-info strong {
    color: #243F3B;
}

/* =========================================
   MODAL TAMBAH DAN EDIT
========================================= */

.paket-modal .modal-dialog {
    max-width: 620px;
}

.paket-modal .modal-content {
    overflow: hidden;
    border: none;
    border-radius: 17px;
    box-shadow: 0 15px 45px rgba(0, 0, 0, .15);
}

.paket-modal .modal-header {
    padding: 19px 23px;
    border-bottom: 1px solid #DCEFEB;
    background: var(--paket-soft);
}

.paket-modal .modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--paket-text);
    font-size: 19px;
    font-weight: 750;
}

.paket-modal .modal-title i {
    color: var(--paket-primary-dark);
    font-size: 21px;
}

.paket-modal .modal-body {
    padding: 22px 23px;
}

.paket-modal .form-label {
    margin-bottom: 7px;
    color: #244A45;
    font-size: 12px;
    font-weight: 650;
}

.paket-modal .form-control,
.paket-modal .form-select {
    min-height: 43px;
    padding: 10px 12px;
    border: 1px solid var(--paket-border);
    border-radius: 9px;
    background-color: #fff;
    color: #263A37;
    font-size: 12px;
    box-shadow: none;
    transition: .2s ease;
}

.paket-modal textarea.form-control {
    min-height: 85px;
    resize: vertical;
}

.paket-modal .form-control:focus,
.paket-modal .form-select:focus {
    border-color: var(--paket-primary-dark);
    box-shadow: 0 0 0 3px rgba(39, 135, 121, .10);
}

.paket-modal .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 23px;
    border-top: 1px solid #E6EEEC;
    background: #fff;
}

.paket-modal .btn-close {
    opacity: .65;
}

.paket-modal .btn-close:hover {
    opacity: 1;
}

/* Responsif */

@media (max-width: 576px) {
    .paket-page-header {
        margin: 14px 0 20px;
    }

    .btn-paket-primary {
        width: 100%;
    }

    .paket-modal .modal-dialog {
        margin: 12px;
    }

    .paket-modal .modal-header,
    .paket-modal .modal-body,
    .paket-modal .modal-footer {
        padding-left: 16px;
        padding-right: 16px;
    }

    .paket-modal .modal-title {
        font-size: 16px;
    }

    .paket-modal .modal-footer {
        flex-wrap: wrap;
    }
}
</style>

<!-- HEADER HALAMAN -->
<div class="paket-page-header">
    <?php if ($is_admin): ?>
        <button
            type="button"
            class="btn-paket-primary"
            data-bs-toggle="modal"
            data-bs-target="#modalTambahPaket"
        >
            <i class="bi bi-plus-lg"></i>
            Tambah Paket Baru
        </button>
    <?php endif; ?>
</div>

<!-- DAFTAR KARTU PAKET -->
<div class="row g-4 mb-4">

    <?php foreach ($paket_list as $pk): ?>

        <div class="col-lg-4 col-md-6">
            <div class="card paket-card">

                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">
                        <?= htmlspecialchars($pk['jenjang']) ?>
                    </span>

                    <span class="<?= $pk['status'] === 'Aktif' ? 'badge-paket-active' : 'badge bg-secondary' ?>">
                        <?= htmlspecialchars($pk['status']) ?>
                    </span>
                </div>

                <div class="card-body">

                    <h5 class="paket-card-title mb-2">
                        <?= htmlspecialchars($pk['nama_paket']) ?>
                    </h5>

                    <div class="d-flex align-items-baseline mb-3">
                        <span class="paket-price">
                            <?= format_rupiah($pk['harga']) ?>
                        </span>
                        <span class="text-muted small ms-1">/ bulan</span>
                    </div>

                    <p class="paket-description mb-3">
                        <?= nl2br(htmlspecialchars($pk['deskripsi'] ?? 'Program bimbingan belajar intensif.')) ?>
                    </p>

                    <div class="paket-info mb-3">

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">
                                <i class="bi bi-clock me-1"></i> Pertemuan:
                            </span>
                            <strong>
                                <?= (int)$pk['pertemuan_per_minggu'] ?>x / Minggu
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">
                                <i class="bi bi-calendar3 me-1"></i> Durasi:
                            </span>
                            <strong>
                                <?= (int)$pk['durasi_bulan'] ?> Bulan
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">
                                <i class="bi bi-people me-1"></i> Siswa Terdaftar:
                            </span>
                            <strong>
                                <?= (int)$pk['jumlah_siswa'] ?> Siswa
                            </strong>
                        </div>

                    </div>

                    <?php if ($is_admin): ?>
                        <div class="d-flex gap-2">

                            <button
                                type="button"
                                class="btn-paket-edit"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEditPaket<?= (int)$pk['id_paket'] ?>"
                            >
                                <i class="bi bi-pencil"></i>
                                Edit Paket
                            </button>

                            <a
                                href="<?= base_url('modules/paket/delete.php?id=' . (int)$pk['id_paket']) ?>"
                                class="btn-paket-delete btn-confirm-delete"
                                data-item-name="<?= htmlspecialchars($pk['nama_paket']) ?>"
                                title="Hapus Paket"
                            >
                                <i class="bi bi-trash"></i>
                            </a>

                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <?php if ($is_admin): ?>

            <!-- MODAL EDIT PAKET -->
            <div
                class="modal fade paket-modal"
                id="modalEditPaket<?= (int)$pk['id_paket'] ?>"
                tabindex="-1"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">

                        <form action="<?= base_url('modules/paket/edit.php') ?>" method="POST">

                            <input
                                type="hidden"
                                name="id_paket"
                                value="<?= (int)$pk['id_paket'] ?>"
                            >

                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-pencil-square"></i>
                                    Edit Paket Belajar
                                </h5>

                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                                ></button>
                            </div>

                            <div class="modal-body">

                                <div class="mb-3">
                                    <label class="form-label">
                                        Nama Paket Belajar <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="nama_paket"
                                        class="form-control"
                                        value="<?= htmlspecialchars($pk['nama_paket']) ?>"
                                        required
                                    >
                                </div>

                                <div class="row g-3 mb-3">

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Jenjang Pendidikan <span class="text-danger">*</span>
                                        </label>

                                        <input
                                            type="text"
                                            name="jenjang"
                                            class="form-control"
                                            value="<?= htmlspecialchars($pk['jenjang']) ?>"
                                            required
                                        >
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Harga per Bulan (Rp) <span class="text-danger">*</span>
                                        </label>

                                      <input
                                            type="number"
                                            name="harga"
                                            id="harga"
                                            min="1"
                                            step="1"
                                            required
                                        >
                                    </div>

                                </div>

                                <div class="row g-3 mb-3">

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Pertemuan / Minggu
                                        </label>

                                        <input
                                            type="number"
                                            name="pertemuan_per_minggu"
                                            class="form-control"
                                            value="<?= (int)$pk['pertemuan_per_minggu'] ?>"
                                            min="1"
                                            max="7"
                                            required
                                        >
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Durasi (Bulan)
                                        </label>

                                        <input
                                            type="number"
                                            name="durasi_bulan"
                                            class="form-control"
                                            value="<?= (int)$pk['durasi_bulan'] ?>"
                                            min="1"
                                            required
                                        >
                                    </div>

                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status Paket</label>

                                    <select name="status" class="form-select">
                                        <option value="Aktif" <?= $pk['status'] === 'Aktif' ? 'selected' : '' ?>>
                                            Aktif
                                        </option>

                                        <option value="Non-Aktif" <?= $pk['status'] === 'Non-Aktif' ? 'selected' : '' ?>>
                                            Non-Aktif
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">
                                        Deskripsi & Keunggulan Paket
                                    </label>

                                    <textarea
                                        name="deskripsi"
                                        class="form-control"
                                        rows="3"
                                        placeholder="Fasilitas, kurikulum pengajaran, dan target kelulusan..."
                                    ><?= htmlspecialchars($pk['deskripsi'] ?? '') ?></textarea>
                                </div>

                            </div>

                            <div class="modal-footer">

                                <button
                                    type="button"
                                    class="btn-paket-secondary"
                                    data-bs-dismiss="modal"
                                >
                                    Batal
                                </button>

                                <button type="submit" class="btn-paket-primary">
                                    <i class="bi bi-check-circle"></i>
                                    Simpan Perubahan
                                </button>

                            </div>

                        </form>

                    </div>
                </div>
            </div>

        <?php endif; ?>

    <?php endforeach; ?>

</div>

<?php if ($is_admin): ?>

    <!-- MODAL TAMBAH PAKET -->
    <div
        class="modal fade paket-modal"
        id="modalTambahPaket"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

                <form action="<?= base_url('modules/paket/create.php') ?>" method="POST">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle"></i>
                            Tambah Paket Belajar Baru
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>

                    </div>

                    <div class="modal-body">

                        <!-- NAMA PAKET -->
                        <div class="mb-3">
                            <label class="form-label">
                                Nama Paket Belajar <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="nama_paket"
                                class="form-control"
                                placeholder="Contoh: Paket Intensif Kedinasan 2026"
                                maxlength="150"
                                required
                            >
                        </div>

                        <!-- JENJANG DAN HARGA -->
                        <div class="row g-3 mb-3">

                            <div class="col-md-6">
                                <label class="form-label">
                                    Jenjang Pendidikan <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="jenjang"
                                    class="form-control"
                                    placeholder="SD / SMP / SMA / Umum"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Harga per Bulan (Rp) <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="number"
                                    step="1000"
                                    min="1"
                                    name="harga"
                                    class="form-control"
                                    placeholder="500000"
                                    required
                                >
                            </div>

                        </div>

                        <!-- PERTEMUAN DAN DURASI -->
                        <div class="row g-3 mb-3">

                            <div class="col-md-6">
                                <label class="form-label">
                                    Pertemuan / Minggu
                                </label>

                                <input
                                    type="number"
                                    name="pertemuan_per_minggu"
                                    class="form-control"
                                    value="3"
                                    min="1"
                                    max="7"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Durasi (Bulan)
                                </label>

                                <input
                                    type="number"
                                    name="durasi_bulan"
                                    class="form-control"
                                    value="1"
                                    min="1"
                                    required
                                >
                            </div>

                        </div>

                        <!-- STATUS -->
                        <div class="mb-3">
                            <label class="form-label">Status Paket</label>

                            <select name="status" class="form-select">
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>

                        <!-- DESKRIPSI -->
                        <div class="mb-2">
                            <label class="form-label">
                                Deskripsi & Keunggulan Paket
                            </label>

                            <textarea
                                name="deskripsi"
                                class="form-control"
                                rows="3"
                                placeholder="Fasilitas, kurikulum pengajaran, dan target kelulusan..."
                            ></textarea>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn-paket-secondary"
                            data-bs-dismiss="modal"
                        >
                            Batal
                        </button>

                        <button type="submit" class="btn-paket-primary">
                            <i class="bi bi-check-circle"></i>
                            Simpan Paket
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

<?php endif; ?>

<?php require_once BASEPATH . '/includes/footer.php'; ?>
