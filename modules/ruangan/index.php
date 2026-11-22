<?php
/**
 * Data Ruangan
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'ruangan';
$page_title = 'Pengelolaan Data Ruangan';

// Ambil data ruangan dan jumlah kelas
$sql = "
    SELECT
        r.*,
        COUNT(k.id_kelas) AS total_kelas_ruangan
    FROM ruangan r
    LEFT JOIN kelas k ON r.id_ruangan = k.id_ruangan
    GROUP BY r.id_ruangan
    ORDER BY r.id_ruangan ASC
";

$ruangan_list = $pdo->query($sql)->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<style>
:root {
    --room-primary: #258879;
    --room-primary-dark: #1d7165;
    --room-mint: #98EDE1;
    --room-soft: #E8FDFF;
    --room-text: #173f3b;
    --room-border: #dcece8;
}

/* =========================
   HALAMAN RUANGAN
========================= */

.room-page {
    padding: 22px 24px;
    color: var(--room-text);
}

.room-toolbar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 24px;
}

.btn-room-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 44px;
    padding: 11px 20px;
    border: 1px solid var(--room-primary);
    border-radius: 11px;
    background: var(--room-primary);
    color: #fff !important;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(37, 136, 121, .15);
    transition: .2s ease;
}

.btn-room-primary:hover {
    background: var(--room-primary-dark);
    border-color: var(--room-primary-dark);
    transform: translateY(-1px);
}

/* =========================
   KARTU RUANGAN
========================= */

.room-card {
    height: 100%;
    overflow: hidden;
    background: #fff;
    border: 1px solid #edf2f0;
    border-radius: 15px;
    box-shadow: 0 3px 12px rgba(20, 60, 50, .05);
    transition: .2s ease;
}

.room-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 18px rgba(20, 60, 50, .08);
}

.room-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 17px 19px 10px;
}

.room-code {
    padding: 5px 9px;
    border: 1px solid var(--room-border);
    border-radius: 6px;
    background: #f7faf9;
    color: #315b54;
    font-family: monospace;
    font-size: 11px;
    font-weight: 600;
}

.room-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

.room-status-available {
    background: #e3f8ed;
    color: #198754;
}

.room-status-repair {
    background: #fff3cd;
    color: #946c00;
}

.room-status-unavailable {
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
    margin-bottom: 19px;
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

.room-capacity {
    color: #7b8b85;
    font-size: 11px;
}

.room-facilities {
    min-height: 67px;
    margin-bottom: 17px;
    padding: 12px;
    border-radius: 10px;
    background: #f6f9f8;
}

.room-facilities-title {
    display: block;
    margin-bottom: 6px;
    color: #71827b;
    font-size: 10px;
    font-weight: 700;
}

.room-facilities-text {
    margin: 0;
    color: #40564f;
    font-size: 11px;
    line-height: 1.6;
}

.room-card-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-room-edit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    flex: 1;
    min-height: 36px;
    border: 1px solid #258879;
    border-radius: 8px;
    background: #fff;
    color: #258879;
    font-size: 11px;
    font-weight: 600;
    transition: .2s ease;
}

.btn-room-edit:hover {
    background: #258879;
    color: #fff;
}

.btn-room-delete {
    display: inline-flex;
    width: 38px;
    height: 36px;
    align-items: center;
    justify-content: center;
    border: 1px solid #f2c5ca;
    border-radius: 8px;
    background: #fff;
    color: #dc3545;
    text-decoration: none;
    transition: .2s ease;
}

.btn-room-delete:hover {
    background: #dc3545;
    color: #fff;
}

/* =========================
   MODAL
========================= */

.room-modal .modal-dialog {
    max-width: 570px;
}

.room-modal .modal-content {
    overflow: hidden;
    border: none;
    border-radius: 17px;
    box-shadow: 0 15px 45px rgba(0, 0, 0, .15);
}

.room-modal .modal-header {
    padding: 17px 22px;
    background: var(--room-soft);
    border-bottom: 1px solid var(--room-border);
}

.room-modal .modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #194b44;
    font-size: 18px;
    font-weight: 700;
}

.room-modal .modal-title i {
    color: var(--room-primary);
}

.room-modal .modal-body {
    padding: 22px;
}

.room-modal .form-label {
    display: block;
    margin-bottom: 7px;
    color: #254b45;
    font-size: 11px;
    font-weight: 600;
}

.room-modal .form-control,
.room-modal .form-select {
    min-height: 41px;
    padding: 10px 12px;
    border: 1px solid #d5e7e2;
    border-radius: 9px;
    background-color: #fff;
    color: #344b46;
    font-size: 12px;
    box-shadow: none;
}

.room-modal textarea.form-control {
    min-height: 82px;
    resize: vertical;
}

.room-modal .form-control:focus,
.room-modal .form-select:focus {
    border-color: var(--room-primary);
    box-shadow: 0 0 0 3px rgba(37, 136, 121, .10);
}

.room-modal .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding: 14px 22px;
    border-top: 1px solid var(--room-border);
    background: #fff;
}

.btn-room-cancel {
    min-width: 78px;
    padding: 10px 16px;
    border: 1px solid #737e85;
    border-radius: 9px;
    background: #737e85;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
}

.btn-room-cancel:hover {
    background: #5e686e;
    color: #fff;
}

.btn-room-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-width: 135px;
    padding: 10px 17px;
    border: 1px solid var(--room-primary);
    border-radius: 9px;
    background: var(--room-primary);
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    transition: .2s ease;
}

.btn-room-save:hover {
    background: var(--room-primary-dark);
    border-color: var(--room-primary-dark);
    color: #fff;
}

.room-empty {
    padding: 65px 20px;
    color: #84958f;
    text-align: center;
}

.room-empty i {
    display: block;
    margin-bottom: 12px;
    color: var(--room-primary);
    font-size: 35px;
}

@media (max-width: 768px) {
    .room-page {
        padding: 15px 12px;
    }

    .room-modal .modal-dialog {
        margin: 12px;
    }

    .room-modal .modal-body {
        padding: 17px;
    }
}
</style>

<div class="room-page">

    <!-- TOMBOL TAMBAH RUANGAN -->
    <div class="room-toolbar">
        <button type="button"
                class="btn-room-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalTambahRuangan">
            <i class="bi bi-plus-lg"></i>
            Tambah Ruangan Baru
        </button>
    </div>

    <!-- DAFTAR KARTU RUANGAN -->
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

                            <span class="room-code">
                                <?= htmlspecialchars($r['kode_ruangan']) ?>
                            </span>

                            <?php if ($r['status_ruangan'] === 'Tersedia'): ?>
                                <span class="room-status room-status-available">
                                    <i class="bi bi-check-circle"></i>
                                    Tersedia
                                </span>
                            <?php elseif ($r['status_ruangan'] === 'Perbaikan'): ?>
                                <span class="room-status room-status-repair">
                                    <i class="bi bi-tools"></i>
                                    Perbaikan
                                </span>
                            <?php else: ?>
                                <span class="room-status room-status-unavailable">
                                    <i class="bi bi-x-circle"></i>
                                    Tidak Tersedia
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

                                    <div class="room-capacity">
                                        <i class="bi bi-people me-1"></i>
                                        Kapasitas:
                                        <strong><?= (int)$r['kapasitas'] ?> Siswa</strong>
                                    </div>

                                    <div class="room-capacity mt-1">
                                        <i class="bi bi-calendar-check me-1"></i>
                                        <?= (int)$r['total_kelas_ruangan'] ?> Kelas
                                    </div>
                                </div>

                            </div>

                            <div class="room-facilities">

                                <span class="room-facilities-title">
                                    Fasilitas Ruangan
                                </span>

                                <p class="room-facilities-text">
                                    <i class="bi bi-stars text-warning me-1"></i>
                                    <?= htmlspecialchars($r['fasilitas'] ?? '-') ?>
                                </p>

                            </div>

                            <div class="room-card-actions">

                                <button type="button"
                                        class="btn-room-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditRuangan<?= (int)$r['id_ruangan'] ?>">
                                    <i class="bi bi-pencil-square"></i>
                                    Edit Ruangan
                                </button>

                                <a href="<?= base_url('modules/ruangan/delete.php?id=' . (int)$r['id_ruangan']) ?>"
                                   class="btn-room-delete btn-confirm-delete"
                                   data-item-name="<?= htmlspecialchars($r['nama_ruangan']) ?>"
                                   title="Hapus Ruangan">
                                    <i class="bi bi-trash"></i>
                                </a>

                            </div>

                        </div>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

<!-- =========================
     MODAL TAMBAH RUANGAN
========================= -->

<div class="modal fade room-modal"
     id="modalTambahRuangan"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form action="<?= base_url('modules/ruangan/create.php') ?>"
                  method="POST">

                <div class="modal-header">

                    <h5 class="modal-title">
                        <i class="bi bi-door-open"></i>
                        Tambah Ruangan Baru
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-5">
                            <label class="form-label">
                                Kode Ruangan <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="kode_ruangan"
                                   class="form-control"
                                   placeholder="Contoh: R-06"
                                   required>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label">
                                Nama Ruangan <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="nama_ruangan"
                                   class="form-control"
                                   placeholder="Contoh: Ruang Stephen Hawking"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Kapasitas Siswa <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                   name="kapasitas"
                                   class="form-control"
                                   value="15"
                                   min="1"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Status Ketersediaan
                            </label>

                            <select name="status_ruangan" class="form-select">
                                <option value="Tersedia" selected>Tersedia</option>
                                <option value="Perbaikan">Perbaikan</option>
                                <option value="Tidak Tersedia">Tidak Tersedia</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Fasilitas Ruangan
                            </label>

                            <textarea name="fasilitas"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Contoh: AC, Smart TV, Whiteboard, Sound System, Wifi"></textarea>
                        </div>

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

<!-- =========================
     MODAL EDIT RUANGAN
========================= -->

<?php foreach ($ruangan_list as $r): ?>

<div class="modal fade room-modal"
     id="modalEditRuangan<?= (int)$r['id_ruangan'] ?>"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form action="<?= base_url('modules/ruangan/edit.php') ?>"
                  method="POST">

                <input type="hidden"
                       name="id_ruangan"
                       value="<?= (int)$r['id_ruangan'] ?>">

                <div class="modal-header">

                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i>
                        Edit Data Ruangan
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-5">
                            <label class="form-label">
                                Kode Ruangan <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="kode_ruangan"
                                   class="form-control"
                                   value="<?= htmlspecialchars($r['kode_ruangan']) ?>"
                                   required>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label">
                                Nama Ruangan <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="nama_ruangan"
                                   class="form-control"
                                   value="<?= htmlspecialchars($r['nama_ruangan']) ?>"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Kapasitas Maksimal Siswa
                                <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                   name="kapasitas"
                                   class="form-control"
                                   value="<?= (int)$r['kapasitas'] ?>"
                                   min="1"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Status Ketersediaan
                            </label>

                            <select name="status_ruangan" class="form-select">

                                <option value="Tersedia"
                                    <?= ($r['status_ruangan'] === 'Tersedia') ? 'selected' : '' ?>>
                                    Tersedia
                                </option>

                                <option value="Perbaikan"
                                    <?= ($r['status_ruangan'] === 'Perbaikan') ? 'selected' : '' ?>>
                                    Perbaikan
                                </option>

                                <option value="Tidak Tersedia"
                                    <?= ($r['status_ruangan'] === 'Tidak Tersedia') ? 'selected' : '' ?>>
                                    Tidak Tersedia
                                </option>

                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Fasilitas Ruangan
                            </label>

                            <textarea name="fasilitas"
                                      class="form-control"
                                      rows="3"><?= htmlspecialchars($r['fasilitas'] ?? '') ?></textarea>
                        </div>

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

<?php endforeach; ?>

<?php require_once BASEPATH . '/includes/footer.php'; ?>
