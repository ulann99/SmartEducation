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

// Check if user clicked "Jalankan Solver"
if (isset($_POST['run_solver'])) {
    $start_time = microtime(true);
    $solver_result = $solver->solve();
    $end_time = microtime(true);
    $solver_result['execution_time'] = round(($end_time - $start_time) * 1000, 2); // ms
    $_SESSION['last_csp_result'] = $solver_result;
} elseif (isset($_SESSION['last_csp_result'])) {
    $solver_result = $_SESSION['last_csp_result'];
}

$active_classes = $solver->getClasses();
$available_rooms = $solver->getRooms();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="h3 fw-bold">Penjadwalan Otomatis (CSP + Backtracking)</h1>
        <p class="text-muted">Optimasi jadwal kelas bebas konflik menggunakan Constraint Satisfaction Problem dan algoritma Backtracking.</p>
    </div>
    <form method="POST" action="" class="d-flex gap-2">
        <button type="submit" name="run_solver" value="1" class="btn btn-mint">
            <i class="bi bi-cpu-fill me-1"></i> Jalankan Penjadwalan Otomatis (CSP)
        </button>
        <?php if (!empty($solver_result['solution'])): ?>
            <a href="<?= base_url('modules/penjadwalan/simpan_jadwal.php') ?>" class="btn btn-dark-mint">
                <i class="bi bi-cloud-check-fill me-1"></i> Terapkan ke Jadwal Resmi
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Constraints & Specification Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="p-3 bg-white rounded-3 border h-100 shadow-sm">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-shield-check text-mint fs-5"></i>
                <h6 class="mb-0 fw-bold">Kendala 1: Tutor</h6>
            </div>
            <p class="mb-0 small text-muted">Tutor tidak boleh mengajar di dua kelas berbeda pada hari dan jam yang bersamaan.</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white rounded-3 border h-100 shadow-sm">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-door-closed text-mint fs-5"></i>
                <h6 class="mb-0 fw-bold">Kendala 2: Ruangan</h6>
            </div>
            <p class="mb-0 small text-muted">Satu ruangan hanya dapat digunakan oleh satu kelas pada sesi waktu tertentu.</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white rounded-3 border h-100 shadow-sm">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-people text-mint fs-5"></i>
                <h6 class="mb-0 fw-bold">Kendala 3: Siswa</h6>
            </div>
            <p class="mb-0 small text-muted">Jadwal siswa tidak boleh bertabrakan jika terdaftar di lebih dari satu kelas bimbingan.</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-white rounded-3 border h-100 shadow-sm">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-aspect-ratio text-mint fs-5"></i>
                <h6 class="mb-0 fw-bold">Kendala 4: Kapasitas</h6>
            </div>
            <p class="mb-0 small text-muted">Kapasitas ruang belajar harus mencukupi seluruh jumlah siswa dalam kelas tersebut.</p>
        </div>
    </div>
</div>

<?php if ($solver_result): ?>
    <?php if ($solver_result['success']): ?>
        <!-- Success Solver Alert Banner -->
        <div class="card border-0 mb-4 bg-mint-soft shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon emerald mb-0 bg-white" style="width: 50px; height: 50px;">
                            <i class="bi bi-check-circle-fill text-success fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-mint-deep mb-1">Solusi CSP Berhasil Ditemukan!</h5>
                            <p class="mb-0 text-muted small">
                                Seluruh <?= count($solver_result['solution']) ?> kelas telah dialokasikan jadwal tanpa bentrok. 
                                Waktu eksekusi: <strong><?= $solver_result['execution_time'] ?? '< 1' ?> ms</strong> | 
                                Langkah pencarian: <strong><?= $solver_result['steps'] ?> node</strong> | 
                                Backtrack: <strong><?= $solver_result['backtracks'] ?> kali</strong>.
                            </p>
                        </div>
                    </div>
                    <div>
                        <a href="<?= base_url('modules/penjadwalan/simpan_jadwal.php') ?>" class="btn btn-dark-mint btn-lg fs-6 px-4">
                            <i class="bi bi-check2-all me-1"></i> Terapkan ke Database Resmi
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generated Schedule Timetable Grid Preview -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-mint-deep">
                    <i class="bi bi-calendar-week me-2 text-mint"></i>Hasil Matriks Jadwal Otomatis (Preview)
                </h5>
                <span class="badge badge-mint"><?= count($solver_result['solution']) ?> Kelas Terjadwal</span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle mb-0" style="min-width: 800px;">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 140px;">Sesi Waktu</th>
                            <?php foreach ($solver->getDays() as $day): ?>
                                <th><?= $day ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solver->getTimeSlots() as $slot): ?>
                            <tr>
                                <td class="bg-light fw-bold small text-start">
                                    <div class="text-mint-dark"><?= $slot['label'] ?></div>
                                </td>
                                <?php foreach ($solver->getDays() as $day): ?>
                                    <td class="p-2" style="vertical-align: top; width: 14%;">
                                        <?php 
                                        $matched = [];
                                        foreach ($solver_result['solution'] as $sol) {
                                            if ($sol['hari'] === $day && $sol['jam_mulai'] === $slot['mulai']) {
                                                $matched[] = $sol;
                                            }
                                        }
                                        ?>
                                        <?php if (!empty($matched)): ?>
                                            <?php foreach ($matched as $m): ?>
                                                <div class="p-2 mb-1 bg-mint-soft rounded border border-info text-start small shadow-sm">
                                                    <strong class="d-block text-mint-deep"><?= htmlspecialchars($m['nama_kelas']) ?></strong>
                                                    <div class="text-muted small"><i class="bi bi-person me-1"></i><?= htmlspecialchars($m['nama_tutor']) ?></div>
                                                    <div class="text-muted small"><i class="bi bi-door-open me-1"></i><?= htmlspecialchars($m['nama_ruangan']) ?></div>
                                                    <span class="badge badge-mint mt-1" style="font-size: 0.65rem;"><?= $m['total_siswa'] ?> Siswa</span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small opacity-25">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detail Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold text-mint-deep">
                    <i class="bi bi-list-check me-2 text-mint"></i>Rincian Jadwal Hasil CSP
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                        <?php $no = 1; foreach ($solver_result['solution'] as $s): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($s['nama_kelas']) ?></strong></td>
                                <td><span class="badge bg-mint-soft text-dark"><?= htmlspecialchars($s['nama_paket']) ?></span></td>
                                <td><?= htmlspecialchars($s['nama_tutor']) ?></td>
                                <td><i class="bi bi-door-open me-1 text-mint"></i><?= htmlspecialchars($s['nama_ruangan']) ?></td>
                                <td><span class="badge bg-mint text-dark"><?= htmlspecialchars($s['hari']) ?></span></td>
                                <td class="fw-semibold text-dark"><?= substr($s['jam_mulai'], 0, 5) ?> - <?= substr($s['jam_selesai'], 0, 5) ?> WITA</td>
                                <td><?= $s['total_siswa'] ?> / <?= $s['kapasitas_ruangan'] ?> Kursi</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- Conflict / Failure Alert Banner -->
        <div class="alert alert-danger shadow-sm rounded-3 border-0 p-4 mb-4" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
                <div>
                    <h5 class="fw-bold mb-1">Jadwal Belum Dapat Dibuat Otomatis!</h5>
                    <p class="mb-2"><?= htmlspecialchars($solver_result['message']) ?></p>
                    <p class="mb-0 small text-muted">
                        Langkah pencarian dievaluasi: <?= $solver_result['steps'] ?> iterasi. Algoritma Backtracking telah menelusuri seluruh kemungkinan domain namun menemukan bentrok pada kendala di bawah ini.
                    </p>
                </div>
            </div>
        </div>

        <!-- Conflict Diagnostic Logs -->
        <?php if (!empty($solver_result['conflicts'])): ?>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger-subtle text-danger py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-diagram-3-fill me-2"></i>Laporan Diagnostik Kendala Penyebab Konflik
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
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
                            <?php $no = 1; foreach ($solver_result['conflicts'] as $c): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($c['kelas']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['tutor']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['hari']) ?>, <?= htmlspecialchars($c['slot']) ?></span></td>
                                    <td><?= htmlspecialchars($c['ruangan']) ?></td>
                                    <td class="text-danger fw-medium"><?= htmlspecialchars($c['alasan']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

<?php else: ?>
    <!-- Initial State Card -->
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <div class="stat-icon mint mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2.2rem;">
                <i class="bi bi-cpu-fill"></i>
            </div>
            <h4 class="fw-bold text-mint-deep mb-2">Siap Menjalankan Penjadwalan Otomatis</h4>
            <p class="text-muted mx-auto mb-4" style="max-width: 600px;">
                Terdapat <strong><?= count($active_classes) ?> kelas aktif</strong> dan <strong><?= count($available_rooms) ?> ruangan tersedia</strong> yang akan dihitung kombinasi hari, jam, dan ruangannya menggunakan Constraint Satisfaction Problem Backtracking algorithm.
            </p>
            <form method="POST" action="">
                <button type="submit" name="run_solver" value="1" class="btn btn-mint btn-lg px-4 py-2 fw-bold">
                    <i class="bi bi-play-circle-fill me-1"></i> Mulai Penjadwalan Otomatis Sekarang
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once BASEPATH . '/includes/footer.php'; ?>

