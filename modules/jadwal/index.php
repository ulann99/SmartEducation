
<?php
/**
 * Penjadwalan Otomatis (CSP + Backtracking)
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';
require_once __DIR__ . '/csp_solver.php';

require_roles(['superadmin', 'admin']);

$current_page = 'penjadwalan';
$page_title = 'Penjadwalan Otomatis CSP';

$solver = new CSPSolver($pdo);
$solver_result = null;

// Menjalankan algoritma CSP + Backtracking
if (isset($_POST['run_solver'])) {

    $start_time = microtime(true);

    $solver_result = $solver->solve();

    $end_time = microtime(true);

    $solver_result['execution_time'] = round(
        ($end_time - $start_time) * 1000,
        2
    );

    $_SESSION['last_csp_result'] = $solver_result;

} elseif (isset($_SESSION['last_csp_result'])) {

    $solver_result = $_SESSION['last_csp_result'];
}

$active_classes = $solver->getClasses();
$available_rooms = $solver->getRooms();

// Template
require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<style>
/* =====================================
   PENJADWALAN OTOMATIS
===================================== */

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 28px;
}

.page-header h1 {
    color: #173B38;
    font-size: 25px;
    font-weight: 700;
    margin-bottom: 8px;
}

.page-header p {
    color: #718582;
    font-size: 13px;
    line-height: 1.7;
    margin-bottom: 0;
}

/* =====================================
   TOMBOL
===================================== */

.btn-mint,
.btn-dark-mint {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 20px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all .2s ease;
    white-space: nowrap;
}

.btn-mint {
    background: #269889;
    color: #FFFFFF;
    border-color: #269889;
}

.btn-mint:hover {
    background: #1D786D;
    border-color: #1D786D;
    color: #FFFFFF;
    transform: translateY(-1px);
}

.btn-dark-mint {
    background: #173F3A;
    color: #FFFFFF;
    border-color: #173F3A;
}

.btn-dark-mint:hover {
    background: #245D54;
    border-color: #245D54;
    color: #FFFFFF;
    transform: translateY(-1px);
}

/* =====================================
   KARTU KENDALA
===================================== */

.constraint-card {
    height: 100%;
    background: #FFFFFF;
    border: 1px solid #E1EFEB;
    border-radius: 13px;
    padding: 20px;
    box-shadow: 0 3px 12px rgba(23, 59, 56, .05);
    transition: .2s ease;
}

.constraint-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(23, 59, 56, .08);
}

.constraint-card h6 {
    color: #173B38;
    font-size: 14px;
    font-weight: 700;
}

.constraint-card p {
    color: #718582;
    font-size: 12px;
    line-height: 1.7;
}

/* =====================================
   KARTU UMUM
===================================== */

.card {
    border: 1px solid #E1EFEB !important;
    border-radius: 14px !important;
    overflow: hidden;
    box-shadow: 0 3px 12px rgba(23, 59, 56, .05) !important;
}

.card-header {
    border-bottom: 1px solid #E6F0ED;
}

.card-title {
    color: #173B38;
}

.card-body {
    padding: 22px;
}

/* =====================================
   HASIL CSP
===================================== */

.bg-mint-soft {
    background: #E8F7F4 !important;
}

.bg-mint {
    background: #98EDE1 !important;
}

.text-mint {
    color: #269889 !important;
}

.text-mint-deep {
    color: #173B38 !important;
}

.text-mint-dark {
    color: #287E72 !important;
}

.badge-mint {
    background: #D8F5EF;
    color: #176B60;
    border: 1px solid #C5EBE3;
    padding: 6px 9px;
    border-radius: 6px;
    font-weight: 600;
}

/* =====================================
   TABEL
===================================== */

.table {
    font-size: 12px;
    margin-bottom: 0;
}

.table thead th {
    background: #E8F7F4;
    color: #173B38;
    font-weight: 700;
    padding: 14px 10px;
    white-space: nowrap;
    border-color: #E1EFEB;
}

.table tbody td {
    padding: 12px 10px;
    vertical-align: middle;
    border-color: #E4EFEC;
}

.table-bordered > :not(caption) > * > * {
    border-color: #E4EFEC;
}

/* =====================================
   KARTU JADWAL DALAM MATRIKS
===================================== */

.schedule-item {
    background: #E8F7F4;
    border: 1px solid #98EDE1;
    border-radius: 9px;
    padding: 10px;
    margin-bottom: 7px;
    text-align: left;
    font-size: 11px;
    box-shadow: 0 2px 5px rgba(23, 59, 56, .04);
}

.schedule-item strong {
    color: #173B38;
    display: block;
    margin-bottom: 5px;
    font-size: 11px;
}

.schedule-item .schedule-info {
    color: #718582;
    font-size: 10px;
    margin-bottom: 3px;
}

/* =====================================
   STAT ICON
===================================== */

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #D8F5EF;
    color: #269889;
}

/* =====================================
   KONDISI AWAL
===================================== */

.initial-solver {
    padding: 45px 20px;
    text-align: center;
}

.initial-solver p {
    color: #718582;
    font-size: 13px;
    line-height: 1.8;
}

/* =====================================
   RESPONSIVE
===================================== */

@media (max-width: 992px) {

    .page-header {
        align-items: flex-start;
    }

    .page-header form {
        width: 100%;
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {

    .page-header {
        flex-direction: column;
        align-items: stretch;
    }

    .page-header h1 {
        font-size: 21px;
    }

    .page-header form {
        flex-direction: column;
    }

    .page-header form .btn {
        width: 100%;
    }

    .card-body {
        padding: 16px;
    }

    .constraint-card {
        padding: 16px;
    }
}
</style>

<!-- =====================================
     HEADER PENJADWALAN
===================================== -->

<div class="page-header">

    <div>
        <h1>
           
        </h1>

        <p>
            
        </p>
    </div>

    <form method="POST" action="" class="d-flex gap-2">

        <button
            type="submit"
            name="run_solver"
            value="1"
            class="btn btn-mint">

            <i class="bi bi-cpu-fill"></i>
            Jalankan Penjadwalan Otomatis (CSP)

        </button>

        <?php if (!empty($solver_result['solution'])): ?>

            <a
                href="<?= base_url('modules/jadwal/simpan_jadwal.php') ?>"
                class="btn btn-dark-mint">

                <i class="bi bi-cloud-check-fill"></i>
                Terapkan ke Database Resmi

            </a>

        <?php endif; ?>

    </form>

</div>

<!-- =====================================
     KARTU KENDALA CSP
===================================== -->

<div class="row g-3 mb-4">

    <div class="col-md-6 col-xl-3">
        <div class="constraint-card">

            <div class="d-flex align-items-center gap-2 mb-2">

                <i class="bi bi-shield-check text-mint fs-5"></i>

                <h6 class="mb-0">
                    Kendala 1: Tutor
                </h6>

            </div>

            <p class="mb-0">
                Tutor tidak boleh mengajar di dua kelas berbeda
                pada hari dan jam yang bersamaan.
            </p>

        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="constraint-card">

            <div class="d-flex align-items-center gap-2 mb-2">

                <i class="bi bi-door-closed text-mint fs-5"></i>

                <h6 class="mb-0">
                    Kendala 2: Ruangan
                </h6>

            </div>

            <p class="mb-0">
                Satu ruangan hanya dapat digunakan oleh satu kelas
                pada sesi waktu tertentu.
            </p>

        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="constraint-card">

            <div class="d-flex align-items-center gap-2 mb-2">

                <i class="bi bi-people text-mint fs-5"></i>

                <h6 class="mb-0">
                    Kendala 3: Siswa
                </h6>

            </div>

            <p class="mb-0">
                Jadwal siswa tidak boleh bertabrakan jika terdaftar
                di lebih dari satu kelas bimbingan.
            </p>

        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="constraint-card">

            <div class="d-flex align-items-center gap-2 mb-2">

                <i class="bi bi-aspect-ratio text-mint fs-5"></i>

                <h6 class="mb-0">
                    Kendala 4: Kapasitas
                </h6>

            </div>

            <p class="mb-0">
                Kapasitas ruang belajar harus mencukupi seluruh
                jumlah siswa dalam kelas tersebut.
            </p>

        </div>
    </div>

</div>

<!-- =====================================
     HASIL SOLVER
===================================== -->

<?php if ($solver_result): ?>

    <?php if ($solver_result['success']): ?>

        <!-- NOTIFIKASI BERHASIL -->

        <div class="card border-0 mb-4 bg-mint-soft">

            <div class="card-body p-4">

                <div class="d-flex flex-column flex-md-row
                            justify-content-between align-items-md-center gap-3">

                    <div class="d-flex align-items-center gap-3">

                        <div
                            class="stat-icon bg-white"
                            style="width: 52px; height: 52px;">

                            <i class="bi bi-check-circle-fill text-success fs-3"></i>

                        </div>

                        <div>

                            <h5 class="fw-bold text-mint-deep mb-1">
                                Solusi CSP Berhasil Ditemukan!
                            </h5>

                            <p class="mb-0 text-muted small">

                                Seluruh
                                <?= count($solver_result['solution']) ?>
                                kelas telah dialokasikan jadwal tanpa bentrok.

                                Waktu eksekusi:
                                <strong>
                                    <?= $solver_result['execution_time'] ?? '< 1' ?> ms
                                </strong>

                                |

                                Langkah pencarian:
                                <strong>
                                    <?= $solver_result['steps'] ?> node
                                </strong>

                                |

                                Backtrack:
                                <strong>
                                    <?= $solver_result['backtracks'] ?> kali
                                </strong>.

                            </p>

                        </div>

                    </div>

                    <div>

                        <a
                            href="<?= base_url('modules/jadwal/simpan_jadwal.php') ?>"
                            class="btn btn-dark-mint">

                            <i class="bi bi-check2-all"></i>
                            Terapkan ke Database Resmi

                        </a>

                    </div>

                </div>

            </div>

        </div>

        <!-- =====================================
             MATRIKS JADWAL
        ====================================== -->

        <div class="card mb-4">

            <div class="card-header bg-white py-3
                        d-flex justify-content-between align-items-center flex-wrap gap-2">

                <h5 class="card-title mb-0 fw-bold">

                    <i class="bi bi-calendar-week me-2 text-mint"></i>
                    Hasil Matriks Jadwal Otomatis (Preview)

                </h5>

                <span class="badge badge-mint">

                    <?= count($solver_result['solution']) ?>
                    Kelas Terjadwal

                </span>

            </div>

            <div class="table-responsive">

                <table
                    class="table table-bordered text-center align-middle"
                    style="min-width: 850px;">

                    <thead>

                        <tr>

                            <th style="width: 140px;">
                                Sesi Waktu
                            </th>

                            <?php foreach ($solver->getDays() as $day): ?>

                                <th>
                                    <?= htmlspecialchars($day) ?>
                                </th>

                            <?php endforeach; ?>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($solver->getTimeSlots() as $slot): ?>

                            <tr>

                                <td class="bg-light fw-bold small text-start">

                                    <div class="text-mint-dark">
                                        <?= htmlspecialchars($slot['label']) ?>
                                    </div>

                                </td>

                                <?php foreach ($solver->getDays() as $day): ?>

                                    <td
                                        class="p-2"
                                        style="vertical-align: top; width: 14%;">

                                        <?php

                                        $matched = [];

                                        foreach ($solver_result['solution'] as $sol) {

                                            if (
                                                $sol['hari'] === $day &&
                                                $sol['jam_mulai'] === $slot['mulai']
                                            ) {

                                                $matched[] = $sol;

                                            }

                                        }

                                        ?>

                                        <?php if (!empty($matched)): ?>

                                            <?php foreach ($matched as $m): ?>

                                                <div class="schedule-item">

                                                    <strong>
                                                        <?= htmlspecialchars($m['nama_kelas']) ?>
                                                    </strong>

                                                    <div class="schedule-info">

                                                        <i class="bi bi-person me-1"></i>

                                                        <?= htmlspecialchars($m['nama_tutor']) ?>

                                                    </div>

                                                    <div class="schedule-info">

                                                        <i class="bi bi-door-open me-1"></i>

                                                        <?= htmlspecialchars($m['nama_ruangan']) ?>

                                                    </div>

                                                    <span class="badge badge-mint mt-1">

                                                        <?= $m['total_siswa'] ?> Siswa

                                                    </span>

                                                </div>

                                            <?php endforeach; ?>

                                        <?php else: ?>

                                            <span class="text-muted small opacity-25">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                <?php endforeach; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- =====================================
             RINCIAN JADWAL CSP
        ====================================== -->

        <div class="card">

            <div class="card-header bg-white py-3">

                <h5 class="card-title mb-0 fw-bold">

                    <i class="bi bi-list-check me-2 text-mint"></i>
                    Rincian Jadwal Hasil CSP

                </h5>

            </div>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th style="width: 50px;">No</th>
                            <th>Kelas</th>
                            <th>Paket Belajar</th>
                            <th>Tutor</th>
                            <th>Ruangan</th>
                            <th>Hari</th>
                            <th>Jam Belajar</th>
                            <th>Jumlah Siswa</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php $no = 1; ?>

                        <?php foreach ($solver_result['solution'] as $s): ?>

                            <tr>

                                <td>
                                    <?= $no++ ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($s['nama_kelas']) ?>
                                    </strong>
                                </td>

                                <td>

                                    <span class="badge bg-mint-soft text-dark">
                                        <?= htmlspecialchars($s['nama_paket']) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= htmlspecialchars($s['nama_tutor']) ?>
                                </td>

                                <td>

                                    <i class="bi bi-door-open me-1 text-mint"></i>

                                    <?= htmlspecialchars($s['nama_ruangan']) ?>

                                </td>

                                <td>

                                    <span class="badge bg-mint text-dark">
                                        <?= htmlspecialchars($s['hari']) ?>
                                    </span>

                                </td>

                                <td class="fw-semibold text-dark">

                                    <?= substr($s['jam_mulai'], 0, 5) ?>

                                    -

                                    <?= substr($s['jam_selesai'], 0, 5) ?>

                                    WITA

                                </td>

                                <td>

                                    <?= $s['total_siswa'] ?>

                                    /

                                    <?= $s['kapasitas_ruangan'] ?>

                                    Kursi

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php else: ?>

        <!-- =====================================
             SOLUSI TIDAK DITEMUKAN
        ====================================== -->

        <div
            class="alert alert-danger shadow-sm rounded-3 border-0 p-4 mb-4"
            role="alert">

            <div class="d-flex align-items-start gap-3">

                <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>

                <div>

                    <h5 class="fw-bold mb-1">
                        Jadwal Belum Dapat Dibuat Otomatis!
                    </h5>

                    <p class="mb-2">
                        <?= htmlspecialchars($solver_result['message']) ?>
                    </p>

                    <p class="mb-0 small text-muted">

                        Langkah pencarian dievaluasi:
                        <?= $solver_result['steps'] ?> iterasi.

                        Algoritma Backtracking telah menelusuri
                        kemungkinan domain namun menemukan bentrok
                        pada kendala di bawah ini.

                    </p>

                </div>

            </div>

        </div>

        <!-- DIAGNOSTIK KONFLIK -->

        <?php if (!empty($solver_result['conflicts'])): ?>

            <div class="card">

                <div class="card-header bg-danger-subtle text-danger py-3">

                    <h5 class="card-title mb-0 fw-bold">

                        <i class="bi bi-diagram-3-fill me-2"></i>
                        Laporan Diagnostik Kendala Penyebab Konflik

                    </h5>

                </div>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead>

                            <tr>

                                <th style="width: 50px;">No</th>
                                <th>Kelas Target</th>
                                <th>Tutor Terkait</th>
                                <th>Slot Waktu yang Dicoba</th>
                                <th>Ruangan</th>
                                <th>Penyebab Konflik / Pelanggaran Kendala</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php $no = 1; ?>

                            <?php foreach ($solver_result['conflicts'] as $c): ?>

                                <tr>

                                    <td>
                                        <?= $no++ ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($c['kelas']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($c['tutor']) ?>
                                    </td>

                                    <td>

                                        <span class="badge bg-light text-dark border">

                                            <?= htmlspecialchars($c['hari']) ?>,

                                            <?= htmlspecialchars($c['slot']) ?>

                                        </span>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars($c['ruangan']) ?>
                                    </td>

                                    <td class="text-danger fw-medium">
                                        <?= htmlspecialchars($c['alasan']) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php endif; ?>

    <?php endif; ?>

<?php else: ?>

    <!-- =====================================
         KONDISI AWAL PENJADWALAN
    ====================================== -->

    <div class="card border-0">

        <div class="card-body initial-solver">

            <div
                class="stat-icon mx-auto mb-3"
                style="width: 70px; height: 70px; font-size: 2.2rem;">

                <i class="bi bi-cpu-fill"></i>

            </div>

            <h4 class="fw-bold text-mint-deep mb-2">

                Siap Menjalankan Penjadwalan Otomatis

            </h4>

            <p class="mx-auto mb-4" style="max-width: 600px;">

                Terdapat

                <strong>
                    <?= count($active_classes) ?> kelas aktif
                </strong>

                dan

                <strong>
                    <?= count($available_rooms) ?> ruangan tersedia
                </strong>

                yang akan dihitung kombinasi hari, jam, dan ruangannya
                menggunakan Constraint Satisfaction Problem
                dan algoritma Backtracking.

            </p>

            <form method="POST" action="">

                <button
                    type="submit"
                    name="run_solver"
                    value="1"
                    class="btn btn-mint">

                    <i class="bi bi-play-circle-fill"></i>

                    Mulai Penjadwalan Otomatis Sekarang

                </button>

            </form>

        </div>

    </div>

<?php endif; ?>

<?php
// Template Footer
require_once BASEPATH . '/includes/footer.php';
?>
