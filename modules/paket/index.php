
<?php
/**
 * MANAJEMEN PAKET BELAJAR
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';

require_login();

// Hak akses
$user = current_user();
$is_admin = in_array(
    $_SESSION['role'] ?? '',
    ['superadmin', 'admin'],
    true
);

$current_page = 'paket';
$page_title = 'Paket Bimbingan Belajar';

// Token keamanan
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Mengambil data paket dan jumlah siswa
$sql = "
    SELECT
        p.*,
        COUNT(s.id_siswa) AS jumlah_siswa
    FROM paket_belajar p
    LEFT JOIN siswa s ON p.id_paket = s.id_paket
    GROUP BY p.id_paket
    ORDER BY p.id_paket DESC
";

$stmt = $pdo->query($sql);
$paket_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik paket
$totalPaket = count($paket_list);
$totalAktif = 0;
$totalNonAktif = 0;

foreach ($paket_list as $pk) {
    if ($pk['status'] === 'Aktif') {
        $totalAktif++;
    } else {
        $totalNonAktif++;
    }
}

require_once $root . '/includes/header.php';
require_once $root . '/includes/sidebar.php';
require_once $root . '/includes/navbar.php';
?>

<style>
:root {
    --paket-primary: #98EDE1;
    --paket-dark: #287e72;
    --paket-hover: #176d63;
    --paket-soft: #E8FDFF;
    --paket-text: #173b38;
    --paket-muted: #718582;
    --paket-border: #e4f0ed;
}

.paket-page {
    padding: 24px 28px 35px;
    background: #f5fbfa;
    min-height: calc(100vh - 140px);
}

/* HEADER */

.paket-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 23px;
}

.paket-heading h3 {
    margin: 0 0 6px;
    color: var(--paket-text);
    font-size: 22px;
    font-weight: 700;
}

.paket-heading p {
    margin: 0;
    color: var(--paket-muted);
    font-size: 13px;
}

/* TOMBOL */

.btn-paket-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 18px;
    border: none;
    border-radius: 10px;
    background: var(--paket-dark);
    color: white;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: .2s;
}

.btn-paket-primary:hover {
    background: var(--paket-hover);
    color: white;
}

.btn-paket-secondary {
    padding: 10px 18px;
    border: none;
    border-radius: 9px;
    background: #77818a;
    color: white;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.btn-paket-secondary:hover {
    background: #626c75;
}

/* STATISTIK */

.paket-statistics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 24px;
}

.paket-stat-card {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 19px;
    background: white;
    border: 1px solid var(--paket-border);
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(31, 92, 81, .04);
}

.paket-stat-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: var(--paket-soft);
    color: var(--paket-dark);
    font-size: 20px;
}

.paket-stat-card span {
    display: block;
    color: var(--paket-muted);
    font-size: 11px;
    margin-bottom: 4px;
}

.paket-stat-card h4 {
    margin: 0;
    color: var(--paket-text);
    font-size: 22px;
    font-weight: 700;
}

/* KARTU PAKET */

.paket-card {
    height: 100%;
    overflow: hidden;
    border: 1px solid #edf2f1;
    border-radius: 15px;
    background: white;
    box-shadow: 0 3px 12px rgba(20, 60, 55, .045);
    transition: .2s;
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
    color: #172f2c;
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

.badge-paket-inactive {
    padding: 5px 9px;
    border-radius: 20px;
    background: #eeeeee;
    color: #777;
    font-size: 10px;
    font-weight: 700;
}

.paket-info {
    padding: 13px;
    border-radius: 10px;
    background: #f6f9f8;
    font-size: 12px;
}

/* TOMBOL AKSI */

.btn-paket-edit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    flex: 1;
    padding: 9px 12px;
    border: 1px solid var(--paket-dark);
    border-radius: 9px;
    background: white;
    color: var(--paket-dark);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.btn-paket-edit:hover {
    background: var(--paket-dark);
    color: white;
}

.btn-paket-delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 38px;
    border: 1px solid #f1a5a5;
    border-radius: 9px;
    background: white;
    color: #dc4545;
    cursor: pointer;
}

.btn-paket-delete:hover {
    background: #dc4545;
    color: white;
}

/* MODAL */

.paket-modal .modal-dialog {
    max-width: 620px;
}

.paket-modal .modal-content {
    max-height: calc(100vh - 30px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: none;
    border-radius: 17px;
    box-shadow: 0 15px 45px rgba(0, 0, 0, .15);
}

/*
   PENTING:
   Form menjadi flex container.
   Header dan footer tetap terlihat.
   Body menjadi bagian yang dapat di-scroll.
*/

.paket-modal .modal-content > form {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    max-height: calc(100vh - 30px);
    overflow: hidden;
}

.paket-modal .modal-header {
    flex: 0 0 auto;
    padding: 19px 23px;
    border-bottom: 1px solid #dcefeb;
    background: var(--paket-soft);
}

.paket-modal .modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--paket-text);
    font-size: 18px;
    font-weight: 750;
}

.paket-modal .modal-title i {
    color: var(--paket-dark);
    font-size: 21px;
}

/* BAGIAN FORM YANG DI-SCROLL */

.paket-modal .modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    padding: 22px 23px;
}

/* SCROLLBAR */

.paket-modal .modal-body::-webkit-scrollbar {
    width: 7px;
}

.paket-modal .modal-body::-webkit-scrollbar-track {
    background: #f1f7f5;
    border-radius: 10px;
}

.paket-modal .modal-body::-webkit-scrollbar-thumb {
    background: #a8dcd2;
    border-radius: 10px;
}

.paket-modal .modal-body::-webkit-scrollbar-thumb:hover {
    background: #72bdb0;
}

/* FOOTER TOMBOL */

.paket-modal .modal-footer {
    flex: 0 0 auto;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 23px;
    border-top: 1px solid #e6eeec;
    background: white;
}

/* INPUT FORM */

.paket-modal .form-label {
    margin-bottom: 7px;
    color: #244a45;
    font-size: 12px;
    font-weight: 650;
}

.paket-modal .form-control,
.paket-modal .form-select {
    min-height: 43px;
    padding: 10px 12px;
    border: 1px solid var(--paket-border);
    border-radius: 9px;
    background-color: white;
    color: #263a37;
    font-size: 12px;
    box-shadow: none;
}

.paket-modal textarea.form-control {
    min-height: 85px;
    resize: vertical;
}

.paket-modal .form-control:focus,
.paket-modal .form-select:focus {
    border-color: var(--paket-dark);
    box-shadow: 0 0 0 3px rgba(39, 135, 121, .10);
}

/* DATA KOSONG */

.paket-empty {
    padding: 55px 20px;
    text-align: center;
    background: white;
    border: 1px solid var(--paket-border);
    border-radius: 15px;
}

.paket-empty i {
    display: block;
    margin-bottom: 12px;
    color: #91cfc3;
    font-size: 38px;
}

.paket-empty strong {
    display: block;
    color: var(--paket-text);
    font-size: 15px;
}

.paket-empty span {
    display: block;
    margin-top: 6px;
    color: var(--paket-muted);
    font-size: 12px;
}

/* RESPONSIVE */

@media (max-width: 768px) {
    .paket-page {
        padding: 20px 15px;
    }

    .paket-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .paket-statistics {
        grid-template-columns: 1fr;
    }

    .paket-heading .btn-paket-primary {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .paket-modal .modal-dialog {
        margin: 10px;
    }

    .paket-modal .modal-header,
    .paket-modal .modal-body,
    .paket-modal .modal-footer {
        padding-left: 16px;
        padding-right: 16px;
    }

    .paket-modal .modal-footer {
        flex-wrap: wrap;
    }
}
</style>

<div class="paket-page">

    <!-- HEADER -->
    <div class="paket-heading">
        <div>
            <h3>Paket Bimbingan Belajar</h3>
            <p>Kelola paket belajar yang tersedia di Smart Education Parepare.</p>
        </div>

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

    <!-- STATISTIK -->
    <div class="paket-statistics">

        <div class="paket-stat-card">
            <div class="paket-stat-icon">
                <i class="bi bi-journals"></i>
            </div>
            <div>
                <span>Total Paket</span>
                <h4><?= number_format($totalPaket, 0, ',', '.'); ?></h4>
            </div>
        </div>

        <div class="paket-stat-card">
            <div class="paket-stat-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <span>Paket Aktif</span>
                <h4><?= number_format($totalAktif, 0, ',', '.'); ?></h4>
            </div>
        </div>

        <div class="paket-stat-card">
            <div class="paket-stat-icon">
                <i class="bi bi-pause-circle"></i>
            </div>
            <div>
                <span>Paket Non-Aktif</span>
                <h4><?= number_format($totalNonAktif, 0, ',', '.'); ?></h4>
            </div>
        </div>

    </div>

    <!-- DAFTAR PAKET -->
    <div class="row g-4">

        <?php if (!empty($paket_list)): ?>

            <?php foreach ($paket_list as $pk): ?>

                <div class="col-lg-4 col-md-6">

                    <div class="card paket-card">

                        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">

                            <span class="badge bg-light text-dark px-3 py-2 rounded-pill">
                                <?= htmlspecialchars($pk['jenjang']); ?>
                            </span>

                            <span class="<?= $pk['status'] === 'Aktif' ? 'badge-paket-active' : 'badge-paket-inactive'; ?>">
                                <?= htmlspecialchars($pk['status']); ?>
                            </span>

                        </div>

                        <div class="card-body">

                            <h5 class="paket-card-title mb-2">
                                <?= htmlspecialchars($pk['nama_paket']); ?>
                            </h5>

                            <div class="d-flex align-items-baseline mb-3">
                                <span class="paket-price">
                                    <?= format_rupiah($pk['harga']); ?>
                                </span>
                                <span class="text-muted small ms-1">
                                    / bulan
                                </span>
                            </div>

                            <p class="paket-description mb-3">
                                <?= nl2br(htmlspecialchars(
                                    $pk['deskripsi'] ?? 'Program bimbingan belajar intensif.'
                                )); ?>
                            </p>

                            <div class="paket-info mb-3">

                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        Pertemuan:
                                    </span>
                                    <strong>
                                        <?= (int)$pk['pertemuan_per_minggu']; ?>x / Minggu
                                    </strong>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        Durasi:
                                    </span>
                                    <strong>
                                        <?= (int)$pk['durasi_bulan']; ?> Bulan
                                    </strong>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">
                                        <i class="bi bi-people me-1"></i>
                                        Siswa Terdaftar:
                                    </span>
                                    <strong>
                                        <?= (int)$pk['jumlah_siswa']; ?> Siswa
                                    </strong>
                                </div>

                            </div>

                            <?php if ($is_admin): ?>

                                <div class="d-flex gap-2">

                                    <!-- TOMBOL EDIT -->
                                    <button
                                        type="button"
                                        class="btn-paket-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditPaket<?= (int)$pk['id_paket']; ?>"
                                    >
                                        <i class="bi bi-pencil"></i>
                                        Edit Paket
                                    </button>

                                    <!-- TOMBOL HAPUS -->
                                    <form
                                        action="<?= base_url('modules/paket/delete.php'); ?>"
                                        method="POST"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket <?= htmlspecialchars($pk['nama_paket'], ENT_QUOTES, 'UTF-8'); ?>?');"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="id_paket"
                                            value="<?= (int)$pk['id_paket']; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn-paket-delete"
                                            title="Hapus Paket"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </form>

                                </div>

                            <?php endif; ?>

                        </div>
                    </div>

                </div>

                <?php if ($is_admin): ?>

                    <!-- MODAL EDIT -->
                    <div
                        class="modal fade paket-modal"
                        id="modalEditPaket<?= (int)$pk['id_paket']; ?>"
                        tabindex="-1"
                        aria-hidden="true"
                    >

                        <div class="modal-dialog modal-dialog-centered">

                            <div class="modal-content">

                                <form
                                    action="<?= base_url('modules/paket/edit.php'); ?>"
                                    method="POST"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id_paket"
                                        value="<?= (int)$pk['id_paket']; ?>"
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
                                                Nama Paket Belajar *
                                            </label>

                                            <input
                                                type="text"
                                                name="nama_paket"
                                                class="form-control"
                                                maxlength="100"
                                                value="<?= htmlspecialchars($pk['nama_paket']); ?>"
                                                required
                                            >
                                        </div>

                                        <div class="row g-3 mb-3">

                                            <div class="col-md-6">
                                                <label class="form-label">
                                                    Jenjang Pendidikan *
                                                </label>

                                                <input
                                                    type="text"
                                                    name="jenjang"
                                                    class="form-control"
                                                    maxlength="50"
                                                    value="<?= htmlspecialchars($pk['jenjang']); ?>"
                                                    required
                                                >
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">
                                                    Harga per Bulan (Rp) *
                                                </label>

                                                <input
                                                    type="number"
                                                    name="harga"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars((string)$pk['harga']); ?>"
                                                    min="1"
                                                    step="0.01"
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
                                                    value="<?= (int)$pk['pertemuan_per_minggu']; ?>"
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
                                                    value="<?= (int)$pk['durasi_bulan']; ?>"
                                                    min="1"
                                                    required
                                                >
                                            </div>

                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">
                                                Status Paket
                                            </label>

                                            <select
                                                name="status"
                                                class="form-select"
                                            >
                                                <option
                                                    value="Aktif"
                                                    <?= $pk['status'] === 'Aktif' ? 'selected' : ''; ?>
                                                >
                                                    Aktif
                                                </option>

                                                <option
                                                    value="Non-Aktif"
                                                    <?= $pk['status'] === 'Non-Aktif' ? 'selected' : ''; ?>
                                                >
                                                    Non-Aktif
                                                </option>
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label">
                                                Deskripsi dan Keunggulan Paket
                                            </label>

                                            <textarea
                                                name="deskripsi"
                                                class="form-control"
                                                rows="4"
                                                placeholder="Fasilitas, kurikulum pengajaran, dan target pembelajaran..."
                                            ><?= htmlspecialchars($pk['deskripsi'] ?? ''); ?></textarea>
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

                                        <button
                                            type="submit"
                                            class="btn-paket-primary"
                                        >
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

        <?php else: ?>

            <div class="col-12">
                <div class="paket-empty">
                    <i class="bi bi-journals"></i>
                    <strong>Belum Ada Paket Belajar</strong>
                    <span>Silakan tambahkan paket belajar baru.</span>
                </div>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php if ($is_admin): ?>

    <!-- MODAL TAMBAH PAKET -->
    <div
        class="modal fade paket-modal"
        id="modalTambahPaket"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <form
                    action="<?= base_url('modules/paket/create.php'); ?>"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>"
                    >

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

                        <div class="mb-3">
                            <label class="form-label">
                                Nama Paket Belajar *
                            </label>

                            <input
                                type="text"
                                name="nama_paket"
                                class="form-control"
                                placeholder="Contoh: Paket Intensif Kedinasan 2026"
                                maxlength="100"
                                required
                            >
                        </div>

                        <div class="row g-3 mb-3">

                            <div class="col-md-6">
                                <label class="form-label">
                                    Jenjang Pendidikan *
                                </label>

                                <input
                                    type="text"
                                    name="jenjang"
                                    class="form-control"
                                    placeholder="SD / SMP / SMA / Umum"
                                    maxlength="50"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Harga per Bulan (Rp) *
                                </label>

                                <input
                                    type="number"
                                    name="harga"
                                    class="form-control"
                                    placeholder="500000"
                                    min="1"
                                    step="0.01"
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

                        <div class="mb-3">
                            <label class="form-label">
                                Status Paket
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >
                                <option value="Aktif" selected>
                                    Aktif
                                </option>

                                <option value="Non-Aktif">
                                    Non-Aktif
                                </option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">
                                Deskripsi dan Keunggulan Paket
                            </label>

                            <textarea
                                name="deskripsi"
                                class="form-control"
                                rows="4"
                                placeholder="Fasilitas, kurikulum pengajaran, dan target pembelajaran..."
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

                        <button
                            type="submit"
                            class="btn-paket-primary"
                        >
                            <i class="bi bi-check-circle"></i>
                            Simpan Paket
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

<?php endif; ?>

<?php require_once $root . '/includes/footer.php'; ?>
