
<?php
/**
 * INFORMASI JADWAL SMART EDUCATION PAREPARE
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__DIR__)));
}

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

$current_page = 'informasi_jadwal';
$page_title = 'Informasi Jadwal';

// Mengambil jadwal aktif dari database
$stmt = $pdo->prepare("
    SELECT
        j.id_jadwal,
        j.hari,
        j.jam_mulai,
        j.jam_selesai,
        j.status_jadwal,
        j.metode_pembuatan,
        k.kode_kelas,
        k.nama_kelas,
        t.nama_tutor,
        r.kode_ruangan,
        r.nama_ruangan,
        r.kapasitas
    FROM jadwal j
    INNER JOIN kelas k
        ON j.id_kelas = k.id_kelas
    INNER JOIN tutor t
        ON j.id_tutor = t.id_tutor
    INNER JOIN ruangan r
        ON j.id_ruangan = r.id_ruangan
    WHERE j.status_jadwal = 'Aktif'
    ORDER BY
        FIELD(
            j.hari,
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu',
            'Minggu'
        ),
        j.jam_mulai ASC
");

$stmt->execute();
$jadwal_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_jadwal = count($jadwal_list);

$total_csp = 0;

foreach ($jadwal_list as $jadwal) {
    if ($jadwal['metode_pembuatan'] === 'CSP_Backtracking') {
        $total_csp++;
    }
}

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<style>
/* INFORMASI JADWAL */

.schedule-page {
    padding-bottom: 30px;
}

.schedule-heading {
    margin-bottom: 24px;
}

.schedule-heading h2 {
    color: #173B38;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 7px;
}

.schedule-heading p {
    color: #718582;
    font-size: 13px;
    margin: 0;
}

/* STATISTIK */

.schedule-stat {
    background: #FFFFFF;
    border: 1px solid #E1EFEB;
    border-radius: 13px;
    padding: 20px;
    height: 100%;
    box-shadow: 0 3px 12px rgba(23,59,56,.04);
}

.schedule-stat-label {
    color: #718582;
    font-size: 12px;
    margin-bottom: 8px;
}

.schedule-stat-number {
    color: #173B38;
    font-size: 25px;
    font-weight: 700;
    margin: 0;
}

/* CARD */

.schedule-card {
    background: #FFFFFF;
    border: 1px solid #E1EFEB;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 3px 12px rgba(23,59,56,.04);
}

.schedule-card-header {
    padding: 18px 20px;
    border-bottom: 1px solid #E6F0ED;
}

.schedule-card-header h5 {
    color: #173B38;
    font-size: 16px;
    font-weight: 700;
    margin: 0;
}

/* TOMBOL */

.btn-schedule {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 10px 17px;
    border-radius: 8px;
    background: #269889;
    color: #FFFFFF;
    text-decoration: none;
    border: 1px solid #269889;
    font-size: 12px;
    font-weight: 600;
    transition: .2s;
}

.btn-schedule:hover {
    background: #1D786D;
    color: #FFFFFF;
}

/* TABEL */

.schedule-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.schedule-table thead th {
    background: #E8F7F4;
    color: #173B38;
    padding: 14px 12px;
    font-weight: 700;
    white-space: nowrap;
    border-bottom: 1px solid #DCEDE8;
}

.schedule-table tbody td {
    padding: 15px 12px;
    border-bottom: 1px solid #EDF2F0;
    color: #526D68;
    vertical-align: middle;
}

.schedule-table tbody tr:hover {
    background: #F7FCFA;
}

.class-name {
    color: #173B38;
    font-weight: 700;
    margin-bottom: 3px;
}

.class-code {
    font-size: 10px;
    color: #8A9D99;
}

.time-badge {
    display: inline-block;
    background: #E8F7F4;
    color: #176B60;
    border: 1px solid #D0EEE6;
    padding: 6px 9px;
    border-radius: 7px;
    white-space: nowrap;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    background: #E1F5E9;
    color: #18834B;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.method-badge {
    display: inline-block;
    background: #E8F7F4;
    color: #267F73;
    padding: 6px 9px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
}

/* EMPTY STATE */

.empty-schedule {
    text-align: center;
    padding: 55px 20px;
}

.empty-schedule i {
    font-size: 45px;
    color: #98DCD1;
    margin-bottom: 15px;
}

.empty-schedule h5 {
    color: #173B38;
    font-weight: 700;
}

.empty-schedule p {
    color: #718582;
    font-size: 13px;
}

@media(max-width: 768px) {
    .schedule-heading h2 {
        font-size: 20px;
    }

    .schedule-table {
        min-width: 950px;
    }
}
</style>

<div class="schedule-page">

    <!-- JUDUL HALAMAN -->

    <div class="schedule-heading">

        <h2>
            <i class="bi bi-calendar3 text-success me-2"></i>
          
        </h2>

        <p>
            
        </p>

    </div>

    <!-- STATISTIK -->

    <div class="row g-3 mb-4">

        <div class="col-md-6 col-xl-4">

            <div class="schedule-stat">

                <div class="schedule-stat-label">
                    Total Jadwal Aktif
                </div>

                <p class="schedule-stat-number">
                    <?= $total_jadwal ?>
                </p>

                <small class="text-muted">
                    Jadwal tersimpan dalam sistem
                </small>

            </div>

        </div>

        <div class="col-md-6 col-xl-4">

            <div class="schedule-stat">

                <div class="schedule-stat-label">
                    Jadwal Otomatis CSP
                </div>

                <p class="schedule-stat-number">
                    <?= $total_csp ?>
                </p>

                <small class="text-muted">
                    Dihasilkan menggunakan CSP Backtracking
                </small>

            </div>

        </div>

        <div class="col-md-6 col-xl-4">

            <div class="schedule-stat">

                <div class="schedule-stat-label">
                    Jadwal Manual
                </div>

                <p class="schedule-stat-number">
                    <?= $total_jadwal - $total_csp ?>
                </p>

                <small class="text-muted">
                    Jadwal yang dibuat secara manual
                </small>

            </div>

        </div>

    </div>

    <!-- DAFTAR JADWAL -->

    <div class="schedule-card">

        <div class="schedule-card-header
                    d-flex justify-content-between
                    align-items-center flex-wrap gap-2">

            <div>

                <h5>
                    <i class="bi bi-list-check text-success me-2"></i>
                    Daftar Jadwal Kelas
                </h5>

                <small class="text-muted">
                    Data jadwal resmi dari database
                </small>

            </div>

            <a
                href="<?= base_url('modules/jadwal/index.php') ?>"
                class="btn-schedule">

                <i class="bi bi-cpu"></i>
                Penjadwalan Otomatis

            </a>

        </div>

        <?php if ($total_jadwal > 0): ?>

            <div class="table-responsive">

                <table class="schedule-table">

                    <thead>

                        <tr>
                            <th>No.</th>
                            <th>Kelas</th>
                            <th>Tutor</th>
                            <th>Ruangan</th>
                            <th>Hari</th>
                            <th>Waktu</th>
                            <th>Kapasitas</th>
                            <th>Metode</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($jadwal_list as $i => $j): ?>

                            <tr>

                                <td>
                                    <?= $i + 1 ?>
                                </td>

                                <td>

                                    <div class="class-name">
                                        <?= htmlspecialchars($j['nama_kelas']) ?>
                                    </div>

                                    <div class="class-code">
                                        <?= htmlspecialchars($j['kode_kelas']) ?>
                                    </div>

                                </td>

                                <td>

                                    <i class="bi bi-person text-success me-1"></i>

                                    <?= htmlspecialchars($j['nama_tutor']) ?>

                                </td>

                                <td>

                                    <div class="fw-semibold text-dark">
                                        <?= htmlspecialchars($j['nama_ruangan']) ?>
                                    </div>

                                    <small class="text-muted">
                                        <?= htmlspecialchars($j['kode_ruangan']) ?>
                                    </small>

                                </td>

                                <td>

                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($j['hari']) ?>
                                    </span>

                                </td>

                                <td>

                                    <span class="time-badge">

                                        <?= date('H:i', strtotime($j['jam_mulai'])) ?>

                                        -

                                        <?= date('H:i', strtotime($j['jam_selesai'])) ?>

                                        WITA

                                    </span>

                                </td>

                                <td>
                                    <?= (int) $j['kapasitas'] ?> Siswa
                                </td>

                                <td>

                                    <?php if ($j['metode_pembuatan'] === 'CSP_Backtracking'): ?>

                                        <span class="method-badge">
                                            CSP Backtracking
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-light text-dark border">
                                            Manual
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <span class="status-badge">
                                        <?= htmlspecialchars($j['status_jadwal']) ?>
                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-schedule">

                <i class="bi bi-calendar-x d-block"></i>

                <h5>Belum Ada Jadwal Aktif</h5>

                <p>
                    Belum terdapat jadwal kelas aktif di dalam database.
                    Silakan jalankan penjadwalan otomatis terlebih dahulu.
                </p>

                <a
                    href="<?= base_url('modules/jadwal/index.php') ?>"
                    class="btn-schedule">

                    <i class="bi bi-cpu"></i>
                    Buka Penjadwalan Otomatis

                </a>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php
require_once BASEPATH . '/includes/footer.php';
?>
