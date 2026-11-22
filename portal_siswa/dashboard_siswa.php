<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . "/../config/koneksi.php";

define("BASEPATH", __DIR__);

// =====================================
// PEMERIKSAAN LOGIN
// =====================================
if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: ../modules/auth/login.php");
    exit;
}

$role = $_SESSION["role"] ?? "";

if (!in_array($role, ["siswa", "siswa_orang_tua"], true)) {
    header("Location: ../dashboard.php");
    exit;
}

$id_siswa = (int)($_SESSION["id_siswa"] ?? 0);

if ($id_siswa <= 0) {
    die("ID siswa tidak ditemukan pada session login.");
}

// =====================================
// KONFIGURASI TEMPLATE
// =====================================
$current_page = "dashboard";
$page_title = "Dashboard";

// =====================================
// FUNGSI BANTU
// =====================================
function escSiswa($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function rupiahSiswa($value)
{
    return "Rp " . number_format((float)$value, 0, ",", ".");
}

// =====================================
// DATA SISWA DAN PAKET BELAJAR
// =====================================
$namaSiswa = $_SESSION["nama_lengkap"] ?? "Siswa";
$namaPaket = "-";
$jenjang = "-";
$hargaPaket = 0;

$stmtSiswa = $conn->prepare(
    "SELECT
        s.nama_siswa,
        s.id_paket,
        p.nama_paket,
        p.jenjang,
        p.harga
     FROM siswa s
     LEFT JOIN paket_belajar p
        ON s.id_paket = p.id_paket
     WHERE s.id_siswa = ?
     LIMIT 1"
);

if ($stmtSiswa) {
    $stmtSiswa->bind_param("i", $id_siswa);
    $stmtSiswa->execute();

    $resultSiswa = $stmtSiswa->get_result();

    if ($resultSiswa && $resultSiswa->num_rows > 0) {
        $dataSiswa = $resultSiswa->fetch_assoc();

        $namaSiswa = $dataSiswa["nama_siswa"] ?? $namaSiswa;
        $namaPaket = $dataSiswa["nama_paket"] ?? "-";
        $jenjang = $dataSiswa["jenjang"] ?? "-";
        $hargaPaket = (float)($dataSiswa["harga"] ?? 0);
    }

    $stmtSiswa->close();
}

// =====================================
// TOTAL TRANSAKSI & PEMBAYARAN LUNAS
// =====================================
$totalTransaksi = 0;
$totalPembayaranLunas = 0;

$stmtPembayaran = $conn->prepare(
    "SELECT
        COUNT(*) AS total_transaksi,
        COALESCE(
            SUM(
                CASE
                    WHEN status_pembayaran = 'Lunas'
                    THEN nominal
                    ELSE 0
                END
            ), 0
        ) AS total_lunas
     FROM pembayaran
     WHERE id_siswa = ?"
);

if ($stmtPembayaran) {
    $stmtPembayaran->bind_param("i", $id_siswa);
    $stmtPembayaran->execute();

    $resultPembayaran = $stmtPembayaran->get_result();

    if ($resultPembayaran) {
        $dataPembayaran = $resultPembayaran->fetch_assoc();

        $totalTransaksi = (int)($dataPembayaran["total_transaksi"] ?? 0);
        $totalPembayaranLunas = (float)($dataPembayaran["total_lunas"] ?? 0);
    }

    $stmtPembayaran->close();
}

// =====================================
// PEMBAYARAN TERAKHIR
// =====================================
$statusPembayaran = "Belum ada pembayaran";
$tanggalPembayaranTerakhir = "-";
$metodePembayaranTerakhir = "-";

$stmtTerakhir = $conn->prepare(
    "SELECT
        status_pembayaran,
        tanggal_bayar,
        metode_pembayaran
     FROM pembayaran
     WHERE id_siswa = ?
     ORDER BY tanggal_bayar DESC
     LIMIT 1"
);

if ($stmtTerakhir) {
    $stmtTerakhir->bind_param("i", $id_siswa);
    $stmtTerakhir->execute();

    $resultTerakhir = $stmtTerakhir->get_result();

    if ($resultTerakhir && $resultTerakhir->num_rows > 0) {
        $dataTerakhir = $resultTerakhir->fetch_assoc();

        $statusPembayaran = $dataTerakhir["status_pembayaran"] ?? "-";
        $metodePembayaranTerakhir = $dataTerakhir["metode_pembayaran"] ?? "-";

        if (!empty($dataTerakhir["tanggal_bayar"])) {
            $tanggalPembayaranTerakhir = date(
                "d/m/Y",
                strtotime($dataTerakhir["tanggal_bayar"])
            );
        }
    }

    $stmtTerakhir->close();
}

// =====================================
// DATA JADWAL SISWA
// =====================================
$daftarJadwal = [];

$stmtJadwal = $conn->prepare(
    "SELECT
        k.nama_kelas,
        j.hari,
        j.jam_mulai,
        j.jam_selesai,
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
        j.jam_mulai ASC"
);

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

// =====================================
// NAMA UNTUK NAVBAR
// =====================================
$nama = $namaSiswa;

// =====================================
// TEMPLATE
// =====================================
require_once __DIR__ . "/includes/header.php";
?>

<div class="portal-layout">
    <?php require_once __DIR__ . "/includes/sidebar.php"; ?>

    <div class="portal-main">
        <?php require_once __DIR__ . "/includes/navbar.php"; ?>

        <main class="portal-content">

<style>
.siswa-dashboard {
    width: 100%;
}

.siswa-welcome {
    margin-bottom: 24px;
}

.siswa-welcome h2 {
    color: #173B38;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 6px;
}

.siswa-welcome p {
    color: #718582;
    font-size: 13px;
    margin-bottom: 0;
}

/* RINGKASAN */
.siswa-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}

.siswa-summary-card {
    min-width: 0;
    background: #FFFFFF;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 19px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 4px 15px rgba(33, 105, 93, .045);
    transition: .2s ease;
}

.siswa-summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(33, 105, 93, .09);
}

.siswa-summary-icon {
    width: 47px;
    height: 47px;
    min-width: 47px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #E8FDFF;
    color: #287E72;
    font-size: 21px;
}

.siswa-summary-info {
    min-width: 0;
}

.siswa-summary-info p {
    color: #718582;
    font-size: 12px;
    margin-bottom: 5px;
}

.siswa-summary-info h3 {
    color: #173B38;
    font-size: 18px;
    font-weight: 700;
    margin: 0;
    overflow-wrap: anywhere;
}

.siswa-summary-info .small-value {
    font-size: 14px;
}

/* PANEL */
.siswa-panel {
    min-width: 0;
    background: #FFFFFF;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(33, 105, 93, .045);
}

.siswa-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 22px;
}

.siswa-panel-header h3 {
    color: #173B38;
    font-size: 15px;
    font-weight: 600;
    margin: 0;
}

.siswa-panel-label {
    background: #E8FDFF;
    color: #287E72;
    font-size: 10px;
    padding: 6px 11px;
    border-radius: 20px;
    white-space: nowrap;
}

/* BAGIAN BAWAH */
.siswa-bottom-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
    gap: 20px;
    align-items: stretch;
}

/* JADWAL */
.siswa-schedule-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.siswa-schedule-item {
    border: 1px solid #E8F2EF;
    background: #FAFEFD;
    border-radius: 12px;
    padding: 14px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.siswa-schedule-date {
    min-width: 85px;
    background: #E8FDFF;
    border-radius: 10px;
    padding: 10px;
    text-align: center;
}

.siswa-schedule-date strong {
    display: block;
    color: #287E72;
    font-size: 12px;
}

.siswa-schedule-date span {
    color: #718582;
    font-size: 11px;
}

.siswa-schedule-detail {
    min-width: 0;
    flex: 1;
}

.siswa-schedule-detail h4 {
    color: #173B38;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 5px;
}

.siswa-schedule-detail p {
    color: #718582;
    font-size: 11px;
    margin: 0 0 4px;
}

.siswa-schedule-detail p:last-child {
    margin-bottom: 0;
}

/* PEMBAYARAN */
.siswa-payment-info {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.siswa-payment-total {
    background: #E8FDFF;
    padding: 16px;
    border-radius: 12px;
}

.siswa-payment-total span {
    color: #55746F;
    font-size: 11px;
    display: block;
    margin-bottom: 6px;
}

.siswa-payment-total strong {
    color: #287E72;
    font-size: 20px;
    font-weight: 700;
}

.siswa-payment-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding-bottom: 13px;
    border-bottom: 1px solid #EDF4F2;
}

.siswa-payment-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.siswa-payment-row span {
    color: #718582;
    font-size: 12px;
}

.siswa-payment-row strong {
    color: #173B38;
    font-size: 12px;
    text-align: right;
}

.siswa-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: #E8FDFF;
    color: #287E72;
}

/* KOSONG */
.siswa-empty {
    min-height: 170px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #718582;
    font-size: 12px;
    gap: 10px;
}

.siswa-empty i {
    font-size: 32px;
    color: #A5CFC8;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .siswa-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .siswa-bottom-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 575px) {
    .siswa-welcome h2 {
        font-size: 19px;
    }

    .siswa-welcome p {
        font-size: 11px;
    }

    .siswa-summary {
        gap: 10px;
    }

    .siswa-summary-card {
        padding: 13px 10px;
        gap: 9px;
    }

    .siswa-summary-icon {
        width: 37px;
        height: 37px;
        min-width: 37px;
        font-size: 17px;
    }

    .siswa-summary-info p {
        font-size: 10px;
    }

    .siswa-summary-info h3 {
        font-size: 15px;
    }

    .siswa-summary-info .small-value {
        font-size: 12px;
    }

    .siswa-panel {
        padding: 16px 13px;
    }

    .siswa-panel-header h3 {
        font-size: 13px;
    }

    .siswa-schedule-item {
        padding: 10px;
        gap: 9px;
    }

    .siswa-schedule-date {
        min-width: 70px;
        padding: 8px 5px;
    }
}
</style>

<div class="siswa-dashboard">

    <!-- SAMBUTAN -->
    <section class="siswa-welcome">
        <h2>
            Selamat Datang, <?= escSiswa($namaSiswa); ?>!
        </h2>

        <p>
            Berikut ringkasan kegiatan belajar dan informasi pembayaran Anda di Smart Education.
        </p>
    </section>

    <!-- 4 KARTU RINGKASAN -->
    <section class="siswa-summary">

        <!-- PAKET BELAJAR -->
        <div class="siswa-summary-card">
            <div class="siswa-summary-icon">
                <i class="bi bi-book"></i>
            </div>

            <div class="siswa-summary-info">
                <p>Paket Belajar</p>
                <h3 class="small-value">
                    <?= escSiswa($namaPaket); ?>
                </h3>
            </div>
        </div>

        <!-- JADWAL -->
        <div class="siswa-summary-card">
            <div class="siswa-summary-icon">
                <i class="bi bi-calendar-week"></i>
            </div>

            <div class="siswa-summary-info">
                <p>Jadwal Belajar</p>
                <h3>
                    <?= count($daftarJadwal); ?> Jadwal
                </h3>
            </div>
        </div>

        <!-- STATUS PEMBAYARAN -->
        <div class="siswa-summary-card">
            <div class="siswa-summary-icon">
                <i class="bi bi-wallet2"></i>
            </div>

            <div class="siswa-summary-info">
                <p>Status Pembayaran</p>
                <h3 class="small-value">
                    <?= escSiswa($statusPembayaran); ?>
                </h3>
            </div>
        </div>

        <!-- RIWAYAT -->
        <div class="siswa-summary-card">
            <div class="siswa-summary-icon">
                <i class="bi bi-receipt"></i>
            </div>

            <div class="siswa-summary-info">
                <p>Riwayat Pembayaran</p>
                <h3>
                    <?= number_format($totalTransaksi, 0, ",", "."); ?> Transaksi
                </h3>
            </div>
        </div>

    </section>

    <!-- INFORMASI BAWAH -->
    <section class="siswa-bottom-grid">

        <!-- INFORMASI JADWAL -->
        <div class="siswa-panel">

            <div class="siswa-panel-header">
                <h3>
                    <i class="bi bi-calendar-check me-2"></i>
                    Informasi Jadwal
                </h3>

                <span class="siswa-panel-label">
                    Jadwal Belajar
                </span>
            </div>

            <?php if (!empty($daftarJadwal)): ?>

                <div class="siswa-schedule-list">

                    <?php foreach (array_slice($daftarJadwal, 0, 3) as $jadwal): ?>

                        <div class="siswa-schedule-item">

                            <div class="siswa-schedule-date">
                                <strong>
                                    <?= escSiswa($jadwal["hari"]); ?>
                                </strong>

                                <span>
                                    <?= date("H:i", strtotime($jadwal["jam_mulai"])); ?>
                                    -
                                    <?= date("H:i", strtotime($jadwal["jam_selesai"])); ?>
                                </span>
                            </div>

                            <div class="siswa-schedule-detail">

                                <h4>
                                    <?= escSiswa($jadwal["nama_kelas"]); ?>
                                </h4>

                                <p>
                                    <i class="bi bi-person-workspace me-1"></i>
                                    Tutor:
                                    <?= escSiswa($jadwal["nama_tutor"] ?? "-"); ?>
                                </p>

                                <p>
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Ruangan:
                                    <?= escSiswa($jadwal["nama_ruangan"] ?? "-"); ?>
                                </p>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="siswa-empty">
                    <i class="bi bi-calendar-x"></i>
                    <span>Belum ada jadwal belajar yang tersedia.</span>
                </div>

            <?php endif; ?>

        </div>

        <!-- INFORMASI PEMBAYARAN -->
        <div class="siswa-panel">

            <div class="siswa-panel-header">
                <h3>
                    <i class="bi bi-wallet2 me-2"></i>
                    Informasi Pembayaran
                </h3>

                <span class="siswa-panel-label">
                    Pembayaran
                </span>
            </div>

            <div class="siswa-payment-info">

                <div class="siswa-payment-total">
                    <span>Total Pembayaran Lunas</span>
                    <strong>
                        <?= rupiahSiswa($totalPembayaranLunas); ?>
                    </strong>
                </div>

                <div class="siswa-payment-row">
                    <span>Status Terakhir</span>

                    <strong>
                        <span class="siswa-status">
                            <?= escSiswa($statusPembayaran); ?>
                        </span>
                    </strong>
                </div>

                <div class="siswa-payment-row">
                    <span>Jumlah Transaksi</span>

                    <strong>
                        <?= number_format($totalTransaksi, 0, ",", "."); ?>
                    </strong>
                </div>

                <div class="siswa-payment-row">
                    <span>Pembayaran Terakhir</span>

                    <strong>
                        <?= escSiswa($tanggalPembayaranTerakhir); ?>
                    </strong>
                </div>

                <div class="siswa-payment-row">
                    <span>Metode Pembayaran</span>

                    <strong>
                <?= escSiswa($metodePembayaranTerakhir); ?>
                    </strong>
                </div>

            </div>

        </div>

    </section>

</div>

</main>
    </div>
</div>

<?php
require_once __DIR__ . "/includes/footer.php";
?>