<?php
/**
 * Pengelolaan Pembayaran dan Riwayat Pembayaran
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'pembayaran';
$page_title = 'Pengelolaan Pembayaran Siswa';

$active_tab = $_GET['tab'] ?? 'kelola';
if (!in_array($active_tab, ['kelola', 'riwayat'], true)) {
    $active_tab = 'kelola';
}

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$tanggal_mulai = trim($_GET['tanggal_mulai'] ?? '');
$tanggal_akhir = trim($_GET['tanggal_akhir'] ?? '');

$where = " WHERE 1=1 ";
$params = [];

if ($search !== '') {
    $where .= " AND (
        s.nama_siswa LIKE :search_nama
        OR s.nis LIKE :search_nis
        OR p.kode_transaksi LIKE :search_kode
    )";

    $params[':search_nama'] = "%{$search}%";
    $params[':search_nis'] = "%{$search}%";
    $params[':search_kode'] = "%{$search}%";
}

if ($status_filter !== '') {
    $where .= " AND p.status_pembayaran = :status";
    $params[':status'] = $status_filter;
}

if ($tanggal_mulai !== '') {
    $where .= " AND p.tanggal_bayar >= :tanggal_mulai";
    $params[':tanggal_mulai'] = $tanggal_mulai;
}

if ($tanggal_akhir !== '') {
    $where .= " AND p.tanggal_bayar <= :tanggal_akhir";
    $params[':tanggal_akhir'] = $tanggal_akhir;
}

$base_sql = "
    SELECT p.*, s.nama_siswa, s.nis, s.no_telepon,
           pk.nama_paket,
           u.nama_lengkap AS verifikator_nama
    FROM pembayaran p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN paket_belajar pk ON p.id_paket = pk.id_paket
    LEFT JOIN users u ON p.diverifikasi_oleh = u.id
";

/* Data kelola pembayaran */
$sql_kelola = $base_sql . $where . "
    AND p.status_pembayaran IN ('Menunggu Konfirmasi', 'Belum Lunas')
    ORDER BY p.id_pembayaran DESC
";

$stmt = $pdo->prepare($sql_kelola);
$stmt->execute($params);
$kelola_list = $stmt->fetchAll();

/* Data riwayat pembayaran */
$sql_riwayat = $base_sql . $where . "
    AND p.status_pembayaran IN ('Lunas', 'Ditolak')
    ORDER BY p.id_pembayaran DESC
";

$stmt = $pdo->prepare($sql_riwayat);
$stmt->execute($params);
$riwayat_list = $stmt->fetchAll();

/* Ringkasan pembayaran */
$total_transaksi = (int) $pdo->query("
    SELECT COUNT(*) FROM pembayaran
")->fetchColumn();

$total_lunas = (int) $pdo->query("
    SELECT COUNT(*) FROM pembayaran
    WHERE status_pembayaran = 'Lunas'
")->fetchColumn();

$total_menunggu = (int) $pdo->query("
    SELECT COUNT(*) FROM pembayaran
    WHERE status_pembayaran = 'Menunggu Konfirmasi'
")->fetchColumn();

$total_ditolak = (int) $pdo->query("
    SELECT COUNT(*) FROM pembayaran
    WHERE status_pembayaran = 'Ditolak'
")->fetchColumn();

/* Dropdown siswa */
$siswa_list = $pdo->query("
    SELECT id_siswa, nis, nama_siswa, id_paket
    FROM siswa
    WHERE status_siswa = 'Aktif'
    ORDER BY nama_siswa ASC
")->fetchAll();

/* Dropdown paket */
$paket_list = $pdo->query("
    SELECT *
    FROM paket_belajar
    WHERE status = 'Aktif'
    ORDER BY nama_paket ASC
")->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';

function tampilkan_status_pembayaran($status)
{
    if ($status === 'Lunas') {
        return '<span class="payment-status status-paid">
            <i class="bi bi-check-circle-fill"></i> Lunas
        </span>';
    }

    if ($status === 'Menunggu Konfirmasi') {
        return '<span class="payment-status status-pending">
            <i class="bi bi-hourglass-split"></i> Menunggu Konfirmasi
        </span>';
    }

    if ($status === 'Ditolak') {
        return '<span class="payment-status status-rejected">
            <i class="bi bi-x-circle"></i> Ditolak
        </span>';
    }

    return '<span class="payment-status status-unpaid">
        <i class="bi bi-exclamation-circle"></i> Belum Lunas
    </span>';
}

$data_tampil = ($active_tab === 'kelola')
    ? $kelola_list
    : $riwayat_list;
?>

<style>
:root {
    --payment-primary: #258477;
    --payment-primary-dark: #1d6e63;
    --payment-mint: #e8fbf8;
    --payment-border: #dcebe8;
    --payment-text: #183f3b;
    --payment-muted: #788c89;
    --payment-bg: #f5faf9;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 25px;
}

.page-header h1 {
    color: var(--payment-text);
    font-size: 24px;
    font-weight: 750;
    margin: 0 0 5px;
}

.page-header p {
    color: var(--payment-muted);
    font-size: 13px;
    margin: 0;
}

/* Tombol utama */
.btn-add-action,
.btn-payment-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 44px;
    padding: 11px 20px;
    border: 1px solid var(--payment-primary);
    border-radius: 10px;
    background: var(--payment-primary);
    color: #fff !important;
    font-size: 13px;
    font-weight: 650;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
    box-shadow: 0 4px 10px rgba(37,132,119,.12);
}

.btn-add-action:hover,
.btn-payment-primary:hover {
    background: var(--payment-primary-dark);
    border-color: var(--payment-primary-dark);
    color: #fff !important;
    transform: translateY(-1px);
}

.btn-payment-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 42px;
    padding: 9px 17px;
    border: 1px solid #d7e0df;
    border-radius: 9px;
    background: #f0f3f3;
    color: #536360;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.btn-payment-secondary:hover {
    background: #e4eae9;
    color: #344743;
}

/* Ringkasan */
.payment-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}

.summary-card {
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 105px;
    padding: 19px;
    border: 1px solid #edf2f1;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 2px 7px rgba(28,67,61,.045);
}

.summary-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 43px;
    height: 43px;
    border-radius: 12px;
    font-size: 19px;
}

.summary-icon.total {
    color: #278477;
    background: #e5f7f3;
}

.summary-icon.paid {
    color: #15945e;
    background: #e6f8ee;
}

.summary-icon.pending {
    color: #d89516;
    background: #fff5dc;
}

.summary-icon.rejected {
    color: #dc4c58;
    background: #fff0f0;
}

.summary-label {
    color: var(--payment-muted);
    font-size: 12px;
    margin-bottom: 3px;
}

.summary-value {
    color: var(--payment-text);
    font-size: 24px;
    line-height: 1.2;
    font-weight: 750;
}

.summary-note {
    color: var(--payment-muted);
    font-size: 11px;
    margin-top: 3px;
}

/* Kontainer tabel */
.payment-panel {
    border: 1px solid #edf2f1;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(28,67,61,.045);
    overflow: hidden;
}

.payment-tabs {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 18px 0;
    border-bottom: 1px solid var(--payment-border);
}

.payment-tabs .nav-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 12px 14px;
    border: 0;
    border-bottom: 3px solid transparent;
    border-radius: 8px 8px 0 0;
    color: #6d807c;
    font-size: 13px;
    font-weight: 600;
}

.payment-tabs .nav-link:hover {
    color: var(--payment-primary);
    background: #f6fbfa;
}

.payment-tabs .nav-link.active {
    color: var(--payment-primary);
    background: #f0faf8;
    border-bottom-color: var(--payment-primary);
}

.payment-tabs .badge {
    font-size: 10px;
}

.payment-panel-body {
    padding: 20px;
}

/* Filter */
.payment-filter {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
    align-items: end;
    gap: 12px;
    margin-bottom: 20px;
}

.payment-filter label,
.payment-form label {
    display: block;
    margin-bottom: 7px;
    color: var(--payment-text);
    font-size: 12px;
    font-weight: 650;
}

.payment-filter .form-control,
.payment-filter .form-select,
.payment-form .form-control,
.payment-form .form-select {
    min-height: 42px;
    border: 1px solid var(--payment-border);
    border-radius: 9px;
    color: #344743;
    font-size: 13px;
    box-shadow: none;
}

.payment-filter .form-control:focus,
.payment-filter .form-select:focus,
.payment-form .form-control:focus,
.payment-form .form-select:focus {
    border-color: var(--payment-primary);
    box-shadow: 0 0 0 3px rgba(37,132,119,.10);
}

.filter-actions {
    display: flex;
    gap: 7px;
}

.btn-filter {
    min-height: 42px;
    padding: 9px 15px;
    border: 0;
    border-radius: 9px;
    background: var(--payment-primary);
    color: #fff;
    font-size: 12px;
    font-weight: 650;
}

.btn-filter:hover {
    background: var(--payment-primary-dark);
    color: #fff;
}

.btn-reset {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    min-height: 42px;
    border: 1px solid var(--payment-border);
    border-radius: 9px;
    color: #637773;
    background: #fff;
    text-decoration: none;
}

.btn-reset:hover {
    background: var(--payment-mint);
    color: var(--payment-primary);
}

/* Tabel */
.payment-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.payment-table thead th {
    padding: 14px 11px;
    background: #eaf8f5;
    color: #28534d;
    font-size: 11px;
    font-weight: 750;
    white-space: nowrap;
    border-bottom: 1px solid var(--payment-border);
}

.payment-table thead th:first-child {
    border-radius: 9px 0 0 0;
}

.payment-table thead th:last-child {
    border-radius: 0 9px 0 0;
}

.payment-table tbody td {
    padding: 14px 11px;
    color: #405550;
    border-bottom: 1px solid #edf2f1;
    vertical-align: middle;
}

.payment-table tbody tr:hover {
    background: #f8fcfb;
}

.payment-table tbody tr:last-child td {
    border-bottom: 0;
}

.payment-code {
    color: #315b54;
    font-family: monospace;
    font-size: 11px;
    font-weight: 700;
}

.payment-student-name {
    color: #244a43;
    font-weight: 700;
    font-size: 12px;
}

.payment-subtext {
    display: block;
    margin-top: 4px;
    color: #899995;
    font-size: 10px;
}

.payment-amount {
    color: var(--payment-primary);
    font-weight: 750;
    white-space: nowrap;
}

.payment-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.status-paid {
    color: #168451;
    background: #e5f7ed;
}

.status-pending {
    color: #a96b08;
    background: #fff4d9;
}

.status-rejected {
    color: #c53e4a;
    background: #ffebed;
}

.status-unpaid {
    color: #687773;
    background: #edf1f0;
}

.payment-method {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px;
    border: 1px solid #e6eeec;
    border-radius: 7px;
    background: #f8fbfa;
    color: #586b66;
    font-size: 10px;
    white-space: nowrap;
}

/* Tombol aksi tabel */
.payment-actions {
    display: flex;
    justify-content: center;
    gap: 5px;
}

.payment-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 33px;
    height: 33px;
    border: 1px solid;
    border-radius: 8px;
    background: #fff;
    font-size: 13px;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
}

.payment-action.edit {
    color: #2875d0;
    border-color: #cfe0fa;
}

.payment-action.edit:hover {
    color: #fff;
    background: #2875d0;
}

.payment-action.verify {
    color: #188b59;
    border-color: #ccebd9;
}

.payment-action.verify:hover {
    color: #fff;
    background: #188b59;
}

.payment-action.print {
    color: #536b66;
    border-color: #dce7e4;
}

.payment-action.print:hover {
    color: #fff;
    background: #536b66;
}

.payment-action.delete {
    color: #dc4b55;
    border-color: #f5d1d4;
}

.payment-action.delete:hover {
    color: #fff;
    background: #dc4b55;
}

/* Modal */
.payment-modal .modal-content {
    border: 0;
    border-radius: 17px;
    overflow: hidden;
    box-shadow: 0 18px 55px rgba(20,50,45,.18);
}

.payment-modal .modal-dialog {
    max-width: 820px;
}

.payment-modal .modal-header {
    display: flex;
    align-items: center;
    padding: 19px 24px;
    background: #e7fbf9;
    border-bottom: 1px solid #d9efeb;
}

.payment-modal .modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #194b44;
    font-size: 19px;
    font-weight: 750;
}

.payment-modal .modal-title i {
    color: var(--payment-primary);
    font-size: 21px;
}

.payment-modal .btn-close {
    opacity: .65;
    box-shadow: none;
}

.payment-modal .modal-body {
    padding: 24px;
    background: #fff;
}

.payment-modal .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 24px;
    border-top: 1px solid #e8efed;
    background: #fff;
}

.payment-form .row {
    row-gap: 18px;
}

.payment-form .form-control,
.payment-form .form-select {
    min-height: 43px;
    padding: 10px 12px;
}

.payment-form textarea.form-control {
    min-height: 90px;
    resize: vertical;
}

.payment-required {
    color: #dc3545;
}

.payment-help {
    display: block;
    margin-top: 6px;
    color: #899995;
    font-size: 11px;
}

.payment-modal .btn-payment-primary {
    min-height: 42px;
    padding: 10px 18px;
}

.payment-info-box {
    padding: 13px 15px;
    margin-bottom: 20px;
    border: 1px solid #dceee9;
    border-radius: 10px;
    background: #f4fbf9;
    color: #47635d;
    font-size: 12px;
    line-height: 1.8;
}

/* Modal bukti */
.proof-image {
    display: block;
    max-width: 100%;
    max-height: 420px;
    margin: auto;
    border: 1px solid var(--payment-border);
    border-radius: 12px;
}

/* Responsive */
@media (max-width: 1100px) {
    .payment-filter {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .filter-actions {
        grid-column: span 2;
    }

    .payment-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767px) {
    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .payment-summary {
        grid-template-columns: 1fr 1fr;
    }

    .payment-panel-body {
        padding: 13px;
    }

    .payment-filter {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        grid-column: auto;
    }

    .payment-modal .modal-dialog {
        margin: 10px;
    }

    .payment-modal .modal-body {
        padding: 18px;
    }

    .payment-modal .modal-header {
        padding: 16px 18px;
    }

    .payment-modal .modal-title {
        font-size: 16px;
    }

    .payment-modal .modal-footer {
        padding: 14px 18px;
    }
}

@media (max-width: 480px) {
    .payment-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- HEADER HALAMAN -->
<div class="page-header">
    <div>
        <h1></h1>
        <p></p>
    </div>

    <?php if ($active_tab === 'kelola'): ?>
        <button type="button"
                class="btn-add-action"
                data-bs-toggle="modal"
                data-bs-target="#modalTambahPembayaran">
            <i class="bi bi-plus-lg"></i>
            Input Pembayaran Baru
        </button>
    <?php endif; ?>
</div>

<!-- RINGKASAN -->
<div class="payment-summary">

    <div class="summary-card">
        <div class="summary-icon total">
            <i class="bi bi-receipt"></i>
        </div>
        <div>
            <div class="summary-label">Total Transaksi</div>
            <div class="summary-value"><?= $total_transaksi ?></div>
            <div class="summary-note">Seluruh transaksi</div>
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-icon paid">
            <i class="bi bi-check-circle"></i>
        </div>
        <div>
            <div class="summary-label">Pembayaran Lunas</div>
            <div class="summary-value"><?= $total_lunas ?></div>
            <div class="summary-note">Transaksi berhasil</div>
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-icon pending">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <div>
            <div class="summary-label">Menunggu Konfirmasi</div>
            <div class="summary-value"><?= $total_menunggu ?></div>
            <div class="summary-note">Perlu diverifikasi</div>
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-icon rejected">
            <i class="bi bi-x-circle"></i>
        </div>
        <div>
            <div class="summary-label">Pembayaran Ditolak</div>
            <div class="summary-value"><?= $total_ditolak ?></div>
            <div class="summary-note">Transaksi ditolak</div>
        </div>
    </div>

</div>

<!-- PANEL PEMBAYARAN -->
<div class="payment-panel">

    <ul class="nav payment-tabs">

        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'kelola' ? 'active' : '' ?>"
               href="<?= base_url('modules/pembayaran/index.php?tab=kelola') ?>">
                <i class="bi bi-wallet2"></i>
                Kelola Pembayaran
                <span class="badge bg-secondary"><?= count($kelola_list) ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'riwayat' ? 'active' : '' ?>"
               href="<?= base_url('modules/pembayaran/index.php?tab=riwayat') ?>">
                <i class="bi bi-receipt"></i>
                Riwayat Pembayaran
                <span class="badge bg-secondary"><?= count($riwayat_list) ?></span>
            </a>
        </li>

    </ul>

    <div class="payment-panel-body">

        <!-- FILTER -->
        <form method="GET" action="" class="payment-filter">

            <input type="hidden"
                   name="tab"
                   value="<?= htmlspecialchars($active_tab) ?>">

            <div>
                <label>Cari Transaksi</label>
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Nama siswa, NIS, kode transaksi..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <div>
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="Menunggu Konfirmasi" <?= $status_filter === 'Menunggu Konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                    <option value="Lunas" <?= $status_filter === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                    <option value="Ditolak" <?= $status_filter === 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    <option value="Belum Lunas" <?= $status_filter === 'Belum Lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                </select>
            </div>

            <div>
                <label>Dari Tanggal</label>
                <input type="date"
                       name="tanggal_mulai"
                       class="form-control"
                       value="<?= htmlspecialchars($tanggal_mulai) ?>">
            </div>

            <div>
                <label>Sampai Tanggal</label>
                <input type="date"
                       name="tanggal_akhir"
                       class="form-control"
                       value="<?= htmlspecialchars($tanggal_akhir) ?>">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-filter flex-fill">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>

                <a href="<?= base_url('modules/pembayaran/index.php?tab=' . $active_tab) ?>"
                   class="btn-reset"
                   title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>

        </form>

        <!-- TABEL -->
        <div class="table-responsive">
            <table class="payment-table">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Transaksi</th>
                        <th>Nama Siswa & Paket</th>
                        <th>Nominal</th>
                        <th>Tanggal Bayar</th>
                        <th>Metode</th>
                        <th>Bukti</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($data_tampil)): ?>

                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>

                            <?php if ($active_tab === 'kelola'): ?>
                                Tidak ada transaksi yang perlu dikelola.
                            <?php else: ?>
                                Belum ada riwayat pembayaran yang sesuai.
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php $no = 1; foreach ($data_tampil as $p): ?>

                    <tr>
                        <td><?= $no++ ?></td>

                        <td>
                            <span class="payment-code">
                                <?= htmlspecialchars($p['kode_transaksi']) ?>
                            </span>
                        </td>

                        <td>
                            <div class="payment-student-name">
                                <?= htmlspecialchars($p['nama_siswa']) ?>
                            </div>
                            <span class="payment-subtext">
                                NIS: <?= htmlspecialchars($p['nis']) ?>
                                | <?= htmlspecialchars($p['nama_paket']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="payment-amount">
                                <?= format_rupiah($p['nominal']) ?>
                            </span>
                        </td>

                        <td>
                            <?= date('d/m/Y', strtotime($p['tanggal_bayar'])) ?>
                        </td>

                        <td>
                            <span class="payment-method">
                                <i class="bi bi-credit-card"></i>
                                <?= htmlspecialchars($p['metode_pembayaran']) ?>
                            </span>
                        </td>

                        <td>
                            <?php if (!empty($p['bukti_pembayaran'])): ?>

                                <button type="button"
                                        class="payment-action edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalBukti<?= $p['id_pembayaran'] ?>"
                                        title="Lihat Bukti">
                                    <i class="bi bi-image"></i>
                                </button>

                                <div class="modal fade payment-modal"
                                     id="modalBukti<?= $p['id_pembayaran'] ?>"
                                     tabindex="-1"
                                     aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">

                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-image"></i>
                                                    Bukti Pembayaran
                                                </h5>
                                                <button type="button"
                                                        class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body text-center">
                                                <img src="<?= base_url('assets/images/qris-dummy.svg') ?>"
                                                     alt="Bukti Pembayaran"
                                                     class="proof-image">

                                                <div class="mt-3 small text-muted">
                                                    <?= htmlspecialchars($p['nama_siswa']) ?>
                                                    · <?= format_rupiah($p['nominal']) ?>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= tampilkan_status_pembayaran($p['status_pembayaran']) ?>
                        </td>

                        <td>
                            <div class="payment-actions">

                                <?php if ($active_tab === 'kelola'): ?>

                                    <button type="button"
                                            class="payment-action edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEdit<?= $p['id_pembayaran'] ?>"
                                            title="Edit Pembayaran">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                <?php endif; ?>

                                <?php if (
                                    $active_tab === 'kelola' &&
                                    $p['status_pembayaran'] === 'Menunggu Konfirmasi'
                                ): ?>

                                    <button type="button"
                                            class="payment-action verify"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalVerifikasi<?= $p['id_pembayaran'] ?>"
                                            title="Verifikasi Pembayaran">
                                        <i class="bi bi-check-lg"></i>
                                    </button>

                                <?php endif; ?>

                                <a href="<?= base_url('modules/pembayaran/cetak.php?id=' . $p['id_pembayaran']) ?>"
                                   target="_blank"
                                   class="payment-action print"
                                   title="Cetak Kwitansi">
                                    <i class="bi bi-printer"></i>
                                </a>

                                <?php if ($active_tab === 'kelola'): ?>

                                    <a href="<?= base_url('modules/pembayaran/delete.php?id=' . $p['id_pembayaran']) ?>"
                                       class="payment-action delete btn-confirm-delete"
                                       data-item-name="<?= htmlspecialchars($p['kode_transaksi']) ?>"
                                       title="Hapus Transaksi">
                                        <i class="bi bi-trash"></i>
                                    </a>

                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>

                    <?php if ($active_tab === 'kelola'): ?>

                    <!-- MODAL EDIT -->
                    <div class="modal fade payment-modal"
                         id="modalEdit<?= $p['id_pembayaran'] ?>"
                         tabindex="-1"
                         aria-hidden="true">

                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">

                                <form action="<?= base_url('modules/pembayaran/edit.php') ?>"
                                      method="POST">

                                    <input type="hidden"
                                           name="id_pembayaran"
                                           value="<?= (int) $p['id_pembayaran'] ?>">

                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="bi bi-pencil-square"></i>
                                            Edit Pembayaran
                                        </h5>
                                        <button type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body payment-form">

                                        <div class="payment-info-box">
                                            <strong>Kode Transaksi:</strong>
                                            <?= htmlspecialchars($p['kode_transaksi']) ?><br>
                                            <strong>Nama Siswa:</strong>
                                            <?= htmlspecialchars($p['nama_siswa']) ?>
                                        </div>

                                        <div class="row g-3">

                                            <div class="col-md-6">
                                                <label>Nominal Pembayaran (Rp) <span class="payment-required">*</span></label>
                                                <input type="number"
                                                       name="nominal"
                                                       class="form-control"
                                                       min="1"
                                                       step="1"
                                                       required
                                                       value="<?= htmlspecialchars((string) $p['nominal']) ?>">
                                            </div>

                                            <div class="col-md-6">
                                                <label>Tanggal Pembayaran <span class="payment-required">*</span></label>
                                                <input type="date"
                                                       name="tanggal_bayar"
                                                       class="form-control"
                                                       required
                                                       value="<?= htmlspecialchars(date('Y-m-d', strtotime($p['tanggal_bayar']))) ?>">
                                            </div>

                                            <div class="col-md-6">
                                                <label>Metode Pembayaran <span class="payment-required">*</span></label>
                                                <select name="metode_pembayaran" class="form-select" required>
                                                    <option value="Tunai" <?= $p['metode_pembayaran'] === 'Tunai' ? 'selected' : '' ?>>Tunai</option>
                                                    <option value="QRIS" <?= $p['metode_pembayaran'] === 'QRIS' ? 'selected' : '' ?>>QRIS</option>
                                                    <option value="Transfer Bank" <?= $p['metode_pembayaran'] === 'Transfer Bank' ? 'selected' : '' ?>>Transfer Bank</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label>Status Pembayaran <span class="payment-required">*</span></label>
                                                <select name="status_pembayaran" class="form-select" required>
                                                    <option value="Belum Lunas" <?= $p['status_pembayaran'] === 'Belum Lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                                                    <option value="Menunggu Konfirmasi" <?= $p['status_pembayaran'] === 'Menunggu Konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                                    <option value="Lunas" <?= $p['status_pembayaran'] === 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                                                    <option value="Ditolak" <?= $p['status_pembayaran'] === 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <label>Keterangan</label>
                                                <textarea name="keterangan"
                                                          class="form-control"
                                                          rows="3"
                                                          placeholder="Tambahkan keterangan pembayaran..."><?= htmlspecialchars($p['keterangan'] ?? '') ?></textarea>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button"
                                                class="btn-payment-secondary"
                                                data-bs-dismiss="modal">
                                            Batal
                                        </button>

                                        <button type="submit" class="btn-payment-primary">
                                            <i class="bi bi-check-circle"></i>
                                            Simpan Perubahan
                                        </button>
                                    </div>

                                </form>

                            </div>
                        </div>
                    </div>

                    <?php endif; ?>

                    <?php if (
                        $active_tab === 'kelola' &&
                        $p['status_pembayaran'] === 'Menunggu Konfirmasi'
                    ): ?>

                    <!-- MODAL VERIFIKASI -->
                    <div class="modal fade payment-modal"
                         id="modalVerifikasi<?= $p['id_pembayaran'] ?>"
                         tabindex="-1"
                         aria-hidden="true">

                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">

                                <form action="<?= base_url('modules/pembayaran/verify.php') ?>"
                                      method="POST">

                                    <input type="hidden"
                                           name="id_pembayaran"
                                           value="<?= $p['id_pembayaran'] ?>">

                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="bi bi-check-circle"></i>
                                            Verifikasi Transaksi
                                        </h5>
                                        <button type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body payment-form">

                                        <div class="payment-info-box">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Kode Transaksi</span>
                                                <strong><?= htmlspecialchars($p['kode_transaksi']) ?></strong>
                                            </div>

                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Nama Siswa</span>
                                                <strong><?= htmlspecialchars($p['nama_siswa']) ?></strong>
                                            </div>

                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Nominal</span>
                                                <strong><?= format_rupiah($p['nominal']) ?></strong>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <span>Metode</span>
                                                <strong><?= htmlspecialchars($p['metode_pembayaran']) ?></strong>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label>Tindakan Verifikasi <span class="payment-required">*</span></label>
                                            <select name="status_pembayaran" class="form-select" required>
                                                <option value="Lunas">Setujui & Tandai Lunas</option>
                                                <option value="Ditolak">Tolak Pembayaran</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label>Catatan / Keterangan Verifikasi</label>
                                            <textarea name="keterangan"
                                                      class="form-control"
                                                      rows="3"
                                                      placeholder="Masukkan catatan verifikasi..."><?= htmlspecialchars($p['keterangan'] ?? '') ?></textarea>
                                        </div>

                                    </div>

                                    <div class="modal-footer">
                                        <button type="button"
                                                class="btn-payment-secondary"
                                                data-bs-dismiss="modal">
                                            Batal
                                        </button>

                                        <button type="submit" class="btn-payment-primary">
                                            <i class="bi bi-check-circle"></i>
                                            Simpan Status
                                        </button>
                                    </div>

                                </form>

                            </div>
                        </div>
                    </div>

                    <?php endif; ?>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- MODAL INPUT PEMBAYARAN BARU -->
<div class="modal fade payment-modal"
     id="modalTambahPembayaran"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form action="<?= base_url('modules/pembayaran/create.php') ?>"
                  method="POST"
                  enctype="multipart/form-data">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-wallet2"></i>
                        Input Pembayaran Siswa Baru
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body payment-form">

                    <div class="row">

                        <div class="col-md-6">
                            <label>
                                Pilih Siswa <span class="payment-required">*</span>
                            </label>

                            <select name="id_siswa"
                                    id="selectSiswa"
                                    class="form-select"
                                    required>

                                <option value="">-- Pilih Siswa --</option>

                                <?php foreach ($siswa_list as $s): ?>
                                    <option value="<?= $s['id_siswa'] ?>"
                                            data-paket="<?= $s['id_paket'] ?>">
                                        <?= htmlspecialchars($s['nama_siswa']) ?>
                                        (NIS: <?= htmlspecialchars($s['nis']) ?>)
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label>
                                Paket Bimbingan Belajar <span class="payment-required">*</span>
                            </label>

                            <select name="id_paket"
                                    id="selectPaket"
                                    class="form-select"
                                    required>

                                <option value="">-- Pilih Paket --</option>

                                <?php foreach ($paket_list as $pk): ?>
                                    <option value="<?= $pk['id_paket'] ?>"
                                            data-harga="<?= $pk['harga'] ?>">
                                        <?= htmlspecialchars($pk['nama_paket']) ?>
                                        (<?= format_rupiah($pk['harga']) ?>)
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label>
                                Nominal Pembayaran (Rp) <span class="payment-required">*</span>
                            </label>

                            <input type="number"
                                   step="1000"
                                   min="1"
                                   name="nominal"
                                   id="inputNominal"
                                   class="form-control"
                                   placeholder="Contoh: 750000"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label>
                                Tanggal Pembayaran <span class="payment-required">*</span>
                            </label>

                            <input type="date"
                                   name="tanggal_bayar"
                                   class="form-control"
                                   value="<?= date('Y-m-d') ?>"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label>
                                Metode Pembayaran <span class="payment-required">*</span>
                            </label>

                            <select name="metode_pembayaran"
                                    class="form-select"
                                    required>
                                <option value="Tunai" selected>Tunai (Cash di Kantor)</option>
                                <option value="QRIS">QRIS</option>
                                <option value="Transfer Bank">Transfer Bank Mandiri</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label>Status Pembayaran</label>

                            <select name="status_pembayaran"
                                    class="form-select"
                                    required>
                                <option value="Lunas" selected>Lunas</option>
                                <option value="Menunggu Konfirmasi">Menunggu Konfirmasi</option>
                                <option value="Belum Lunas">Belum Lunas</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label>Upload Bukti Transaksi (Opsional)</label>

                            <input type="file"
                                   name="bukti_pembayaran"
                                   class="form-control"
                                   accept="image/jpeg,image/png,image/webp">

                            <span class="payment-help">
                                Format JPG, PNG, WEBP. Maksimal 3 MB.
                            </span>
                        </div>

                        <div class="col-md-6">
                            <label>Catatan / Keterangan Transaksi</label>

                            <input type="text"
                                   name="keterangan"
                                   class="form-control"
                                   placeholder="Contoh: Pembayaran SPP Bulan November 2026">
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn-payment-secondary"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn-payment-primary">
                        <i class="bi bi-check-circle"></i>
                        Simpan Transaksi
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
// Mengisi nominal otomatis berdasarkan paket yang dipilih.
document.getElementById('selectPaket')?.addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const harga = selectedOption.getAttribute('data-harga');
    const inputNominal = document.getElementById('inputNominal');

    if (harga && inputNominal) {
        inputNominal.value = harga;
    }
});

// Memilih paket otomatis berdasarkan paket yang terhubung ke siswa.
document.getElementById('selectSiswa')?.addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const paketId = selectedOption.getAttribute('data-paket');
    const selectPaket = document.getElementById('selectPaket');

    if (paketId && selectPaket) {
        selectPaket.value = paketId;

        const optionPaket = selectPaket.querySelector(
            `option[value="${paketId}"]`
        );

        if (optionPaket) {
            document.getElementById('inputNominal').value =
                optionPaket.getAttribute('data-harga') || '';
        }
    }
});
</script>

<?php require_once BASEPATH . '/includes/footer.php'; ?>
