
<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

// ==========================================
// VALIDASI LOGIN
// ==========================================
if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: ../modules/auth/login.php");
    exit;
}

// ==========================================
// VALIDASI ROLE
// ==========================================
$role = $_SESSION["role"] ?? "";

if (!in_array($role, ["siswa", "siswa_orang_tua"], true)) {
    header("Location: ../dashboard.php");
    exit;
}

// ==========================================
// VALIDASI ID SISWA
// ==========================================
$id_siswa = (int)($_SESSION["id_siswa"] ?? 0);

if ($id_siswa <= 0) {
    die("Data siswa tidak ditemukan.");
}

// ==========================================
// INFORMASI HALAMAN
// ==========================================
$current_page = "jadwal";
$page_title = "Informasi Jadwal";

// ==========================================
// HELPER
// ==========================================
function e($value)
{
    return htmlspecialchars((string)($value ?? "-"), ENT_QUOTES, "UTF-8");
}

// ==========================================
// AMBIL JADWAL SISWA
// ==========================================
$daftarJadwal = [];

$stmtJadwal = $conn->prepare("
    SELECT
        j.hari,
        j.jam_mulai,
        j.jam_selesai,
        k.nama_kelas,
        t.nama_tutor,
        r.nama_ruangan
    FROM anggota_kelas ak
    INNER JOIN kelas k
        ON ak.id_kelas = k.id_kelas
    INNER JOIN jadwal j
        ON k.id_kelas = j.id_kelas
    LEFT JOIN tutor t
        ON j.id_tutor = t.id_tutor
    LEFT JOIN ruangan r
        ON j.id_ruangan = r.id_ruangan
    WHERE ak.id_siswa = ?
      AND j.status_jadwal = 'Aktif'
    ORDER BY
        CASE j.hari
            WHEN 'Senin' THEN 1
            WHEN 'Selasa' THEN 2
            WHEN 'Rabu' THEN 3
            WHEN 'Kamis' THEN 4
            WHEN 'Jumat' THEN 5
            WHEN 'Sabtu' THEN 6
            WHEN 'Minggu' THEN 7
            ELSE 8
        END,
        j.jam_mulai ASC
");

if ($stmtJadwal) {

    $stmtJadwal->bind_param("i", $id_siswa);
    $stmtJadwal->execute();

    $resultJadwal = $stmtJadwal->get_result();

    if ($resultJadwal) {
        while ($row = $resultJadwal->fetch_assoc()) {
            $daftarJadwal[] = $row;
        }
    }

    $stmtJadwal->close();
}

// ==========================================
// JUMLAH JADWAL
// ==========================================
$totalJadwal = count($daftarJadwal);

// ==========================================
// INCLUDE LAYOUT
// ==========================================
require_once __DIR__ . "/includes/header.php";
?>

<div class="portal-layout">

    <?php require_once __DIR__ . "/includes/sidebar.php"; ?>

    <div class="portal-main">

        <?php require_once __DIR__ . "/includes/navbar.php"; ?>

        <main class="portal-content">

<style>
/* =========================================
   INFORMASI JADWAL
========================================= */

.jadwal-page {
    width: 100%;
}

.jadwal-heading {
    margin-bottom: 24px;
}

.jadwal-heading h2 {
    font-size: 22px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 6px;
}

.jadwal-heading p {
    font-size: 13px;
    color: #718582;
    margin: 0;
}

/* SUMMARY */
.jadwal-summary {
    background: linear-gradient(120deg, #98EDE1, #E8FDFF);
    border-radius: 15px;
    padding: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
}

.jadwal-summary-info small {
    color: #438278;
    font-size: 12px;
    display: block;
    margin-bottom: 7px;
}

.jadwal-summary-info h3 {
    color: #173B38;
    font-size: 19px;
    font-weight: 700;
    margin: 0;
}

.jadwal-summary-icon {
    width: 55px;
    height: 55px;
    border-radius: 15px;
    background: rgba(255,255,255,.65);
    color: #278578;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

/* SECTION */
.jadwal-section {
    background: #fff;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 22px;
    margin-bottom: 20px;
}

.jadwal-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 20px;
}

.jadwal-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
    font-weight: 700;
    color: #173B38;
    margin: 0;
}

.jadwal-section-title i {
    color: #318E81;
    font-size: 18px;
}

.jadwal-count {
    background: #E8FDFF;
    color: #318E81;
    padding: 7px 12px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

/* CARD */
.jadwal-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.jadwal-card {
    border: 1px solid #E5F0ED;
    background: #FCFEFD;
    border-radius: 13px;
    padding: 18px;
    display: grid;
    grid-template-columns: 145px minmax(0, 1fr);
    gap: 18px;
    transition: .2s ease;
}

.jadwal-card:hover {
    border-color: #98EDE1;
    box-shadow: 0 5px 15px rgba(33,105,93,.05);
}

/* DAY */
.jadwal-day {
    background: #E8FDFF;
    border-radius: 11px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.jadwal-day i {
    color: #318E81;
    font-size: 22px;
    margin-bottom: 8px;
}

.jadwal-day strong {
    color: #226B61;
    font-size: 14px;
    font-weight: 700;
}

/* DETAIL */
.jadwal-detail {
    min-width: 0;
}

.jadwal-class-name {
    color: #173B38;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 12px;
}

.jadwal-time {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #EAF9F5;
    color: #2D8073;
    border-radius: 8px;
    padding: 8px 11px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 14px;
}

.jadwal-time i {
    font-size: 14px;
}

.jadwal-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.jadwal-detail-item {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
}

.jadwal-detail-item i {
    color: #70A69C;
    font-size: 15px;
    flex-shrink: 0;
}

.jadwal-detail-text {
    min-width: 0;
}

.jadwal-detail-text small {
    display: block;
    color: #82938F;
    font-size: 10px;
    margin-bottom: 3px;
}

.jadwal-detail-text strong {
    display: block;
    color: #294541;
    font-size: 12px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

/* EMPTY STATE */
.jadwal-empty {
    background: #fff;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 50px 25px;
    text-align: center;
}

.jadwal-empty-icon {
    width: 68px;
    height: 68px;
    background: #E8FDFF;
    color: #318E81;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 29px;
    margin: 0 auto 18px;
}

.jadwal-empty h3 {
    font-size: 17px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 8px;
}

.jadwal-empty p {
    color: #718582;
    font-size: 13px;
    margin: 0;
    line-height: 1.7;
}

/* RESPONSIVE */
@media (max-width: 768px) {

    .jadwal-heading h2 {
        font-size: 19px;
    }

    .jadwal-summary {
        padding: 18px;
    }

    .jadwal-summary-info h3 {
        font-size: 16px;
    }

    .jadwal-section {
        padding: 16px;
    }

    .jadwal-card {
        grid-template-columns: 1fr;
        gap: 12px;
        padding: 14px;
    }

    .jadwal-day {
        flex-direction: row;
        gap: 10px;
        padding: 12px;
    }

    .jadwal-day i {
        margin: 0;
    }

    .jadwal-detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="jadwal-page">

    <!-- JUDUL -->
    <div class="jadwal-heading">
        <h2></h2>
        <p></p>
    </div>

    <!-- RINGKASAN -->
    <div class="jadwal-summary">

        <div class="jadwal-summary-info">
            <small>Jadwal Belajar Aktif</small>
            <h3><?= $totalJadwal; ?> Jadwal Tersedia</h3>
        </div>

        <div class="jadwal-summary-icon">
            <i class="bi bi-calendar-week"></i>
        </div>

    </div>

    <!-- DAFTAR JADWAL -->
    <div class="jadwal-section">

        <div class="jadwal-section-header">

            <h4 class="jadwal-section-title">
                <i class="bi bi-calendar-check"></i>
                Jadwal Kelas Saya
            </h4>

            <span class="jadwal-count">
                <?= $totalJadwal; ?> Jadwal
            </span>

        </div>

        <?php if (!empty($daftarJadwal)): ?>

            <div class="jadwal-list">

                <?php foreach ($daftarJadwal as $jadwal): ?>

                    <div class="jadwal-card">

                        <!-- HARI -->
                        <div class="jadwal-day">
                            <i class="bi bi-calendar3"></i>
                            <strong><?= e($jadwal["hari"]); ?></strong>
                        </div>

                        <!-- DETAIL JADWAL -->
                        <div class="jadwal-detail">

                            <div class="jadwal-class-name">
                                <?= e($jadwal["nama_kelas"]); ?>
                            </div>

                            <div class="jadwal-time">
                                <i class="bi bi-clock"></i>

                                <?= e(substr((string)$jadwal["jam_mulai"], 0, 5)); ?>
                                -
                                <?= e(substr((string)$jadwal["jam_selesai"], 0, 5)); ?>
                            </div>

                            <div class="jadwal-detail-grid">

                                <!-- TUTOR -->
                                <div class="jadwal-detail-item">
                                    <i class="bi bi-person-badge"></i>

                                    <div class="jadwal-detail-text">
                                        <small>Tutor</small>
                                        <strong>
                                            <?= e($jadwal["nama_tutor"] ?? "Belum ditentukan"); ?>
                                        </strong>
                                    </div>
                                </div>

                                <!-- RUANGAN -->
                                <div class="jadwal-detail-item">
                                    <i class="bi bi-geo-alt"></i>

                                    <div class="jadwal-detail-text">
                                        <small>Ruangan</small>
                                        <strong>
                                            <?= e($jadwal["nama_ruangan"] ?? "Belum ditentukan"); ?>
                                        </strong>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <!-- JIKA BELUM ADA JADWAL -->
            <div class="jadwal-empty">

                <div class="jadwal-empty-icon">
                    <i class="bi bi-calendar-x"></i>
                </div>

                <h3>Belum Ada Jadwal Belajar</h3>

                <p>
                    Saat ini belum ada jadwal kelas aktif yang terhubung
                    dengan akun kamu. Jadwal akan ditampilkan di sini
                    setelah Admin mengatur kelas dan jadwal belajar.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>

        </main>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
