<?php
session_start();

require_once __DIR__ . "/../config/koneksi.php";

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
    die("ID siswa tidak ditemukan.");
}

// =====================================
// KONFIGURASI HALAMAN
// =====================================
$current_page = "notifikasi";
$page_title = "Notifikasi";
$nama = $_SESSION["nama_lengkap"] ?? "Siswa";

// =====================================
// FUNGSI BANTU
// =====================================
function escNotif($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// =====================================
// TANDAI SATU NOTIFIKASI DIBACA
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["baca_notifikasi"])) {

    $id_notifikasi = (int)($_POST["id_notifikasi"] ?? 0);

    if ($id_notifikasi > 0) {

        $stmtBaca = $conn->prepare(
            "UPDATE notifikasi
             SET status_baca = 'Sudah Dibaca'
             WHERE id_notifikasi = ?
             AND id_siswa = ?"
        );

        if ($stmtBaca) {
            $stmtBaca->bind_param("ii", $id_notifikasi, $id_siswa);
            $stmtBaca->execute();
            $stmtBaca->close();
        }
    }

    header("Location: notifikasi.php");
    exit;
}

// =====================================
// TANDAI SEMUA NOTIFIKASI DIBACA
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["baca_semua"])) {

    $stmtSemua = $conn->prepare(
        "UPDATE notifikasi
         SET status_baca = 'Sudah Dibaca'
         WHERE id_siswa = ?
         AND status_baca = 'Belum Dibaca'"
    );

    if ($stmtSemua) {
        $stmtSemua->bind_param("i", $id_siswa);
        $stmtSemua->execute();
        $stmtSemua->close();
    }

    header("Location: notifikasi.php");
    exit;
}

// =====================================
// HITUNG NOTIFIKASI BELUM DIBACA
// =====================================
$totalBelumDibaca = 0;

$stmtCount = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM notifikasi
     WHERE id_siswa = ?
     AND status_baca = 'Belum Dibaca'"
);

if ($stmtCount) {
    $stmtCount->bind_param("i", $id_siswa);
    $stmtCount->execute();

    $resultCount = $stmtCount->get_result();

    if ($resultCount) {
        $dataCount = $resultCount->fetch_assoc();
        $totalBelumDibaca = (int)($dataCount["total"] ?? 0);
    }

    $stmtCount->close();
}

// =====================================
// AMBIL DATA NOTIFIKASI
// =====================================
$daftarNotifikasi = [];

$stmtNotif = $conn->prepare(
    "SELECT
        id_notifikasi,
        judul,
        pesan,
        status_baca,
        created_at
     FROM notifikasi
     WHERE id_siswa = ?
     ORDER BY created_at DESC"
);

if ($stmtNotif) {
    $stmtNotif->bind_param("i", $id_siswa);
    $stmtNotif->execute();

    $resultNotif = $stmtNotif->get_result();

    if ($resultNotif) {
        while ($row = $resultNotif->fetch_assoc()) {
            $daftarNotifikasi[] = $row;
        }
    }

    $stmtNotif->close();
}

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
.notifikasi-page {
    width: 100%;
}

/* HEADER */
.notifikasi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.notifikasi-header h2 {
    font-size: 22px;
    font-weight: 700;
    color: #173B38;
    margin: 0 0 6px;
}

.notifikasi-header p {
    font-size: 13px;
    color: #718582;
    margin: 0;
}

/* SUMMARY */
.notifikasi-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    background: #FFFFFF;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 20px 22px;
    margin-bottom: 22px;
    box-shadow: 0 4px 15px rgba(33, 105, 93, .04);
}

.notifikasi-summary-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.notifikasi-summary-icon {
    width: 48px;
    height: 48px;
    background: #E8FDFF;
    color: #287E72;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
}

.notifikasi-summary-text span {
    display: block;
    font-size: 12px;
    color: #718582;
    margin-bottom: 4px;
}

.notifikasi-summary-text strong {
    color: #173B38;
    font-size: 20px;
    font-weight: 700;
}

.notifikasi-badge-count {
    background: #98EDE1;
    color: #205F56;
    padding: 7px 13px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

/* ACTION */
.notifikasi-action {
    margin-bottom: 20px;
    display: flex;
    justify-content: flex-end;
}

.btn-baca-semua {
    background: #98EDE1;
    border: none;
    color: #205F56;
    padding: 10px 16px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 600;
    transition: .2s;
}

.btn-baca-semua:hover {
    background: #75DCCD;
    color: #174D44;
}

/* LIST */
.notifikasi-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.notifikasi-card {
    background: #FFFFFF;
    border: 1px solid #E2EFEC;
    border-radius: 14px;
    padding: 20px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
    transition: .2s;
    box-shadow: 0 4px 15px rgba(33, 105, 93, .035);
}

.notifikasi-card:hover {
    box-shadow: 0 7px 20px rgba(33, 105, 93, .07);
    transform: translateY(-2px);
}

.notifikasi-card.unread {
    background: #F6FFFE;
    border-left: 4px solid #98EDE1;
}

.notifikasi-icon {
    width: 45px;
    height: 45px;
    min-width: 45px;
    border-radius: 12px;
    background: #E8FDFF;
    color: #287E72;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.notifikasi-content {
    flex: 1;
    min-width: 0;
}

.notifikasi-content-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 8px;
}

.notifikasi-content h3 {
    color: #173B38;
    font-size: 14px;
    font-weight: 700;
    margin: 0;
}

.notifikasi-status {
    font-size: 10px;
    font-weight: 600;
    padding: 5px 10px;
    border-radius: 20px;
    white-space: nowrap;
}

.status-belum {
    color: #287E72;
    background: #DDFBF5;
}

.status-sudah {
    color: #788A87;
    background: #F0F4F3;
}

.notifikasi-pesan {
    color: #718582;
    font-size: 12px;
    line-height: 1.7;
    margin-bottom: 12px;
    overflow-wrap: anywhere;
}

.notifikasi-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.notifikasi-waktu {
    color: #9AA9A6;
    font-size: 11px;
}

.btn-tandai-baca {
    background: transparent;
    border: 1px solid #BDECE4;
    color: #287E72;
    padding: 7px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}

.btn-tandai-baca:hover {
    background: #E8FDFF;
}

/* EMPTY */
.notifikasi-empty {
    background: #FFFFFF;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    min-height: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 30px;
}

.notifikasi-empty-icon {
    width: 75px;
    height: 75px;
    border-radius: 50%;
    background: #E8FDFF;
    color: #7CBDB2;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin-bottom: 16px;
}

.notifikasi-empty h3 {
    color: #173B38;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 8px;
}

.notifikasi-empty p {
    color: #718582;
    font-size: 12px;
    margin: 0;
}

/* RESPONSIVE */
@media (max-width: 600px) {

    .notifikasi-header h2 {
        font-size: 19px;
    }

    .notifikasi-summary {
        padding: 16px;
    }

    .notifikasi-card {
        padding: 15px;
        gap: 11px;
    }

    .notifikasi-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        font-size: 17px;
    }

    .notifikasi-content-top {
        flex-direction: column;
        gap: 8px;
    }

    .notifikasi-content h3 {
        font-size: 13px;
    }

    .notifikasi-pesan {
        font-size: 11px;
    }
}
</style>

<div class="notifikasi-page">

    <!-- HEADER -->
    <div class="notifikasi-header">
        <div>
            <h2>Notifikasi</h2>
            <p>Informasi dan pemberitahuan aktivitas belajar Anda.</p>
        </div>
    </div>

    <!-- SUMMARY -->
    <div class="notifikasi-summary">

        <div class="notifikasi-summary-left">

            <div class="notifikasi-summary-icon">
                <i class="bi bi-bell"></i>
            </div>

            <div class="notifikasi-summary-text">
                <span>Notifikasi Belum Dibaca</span>
                <strong><?= $totalBelumDibaca; ?> Notifikasi</strong>
            </div>

        </div>

        <span class="notifikasi-badge-count">
            <?= $totalBelumDibaca; ?> Baru
        </span>

    </div>

    <!-- TANDAI SEMUA -->
    <?php if ($totalBelumDibaca > 0): ?>

        <div class="notifikasi-action">
            <form method="POST">
                <button type="submit"
                        name="baca_semua"
                        class="btn-baca-semua">
                    <i class="bi bi-check2-all me-1"></i>
                    Tandai Semua Dibaca
                </button>
            </form>
        </div>

    <?php endif; ?>

    <!-- DAFTAR NOTIFIKASI -->
    <?php if (!empty($daftarNotifikasi)): ?>

        <div class="notifikasi-list">

            <?php foreach ($daftarNotifikasi as $notif): ?>

                <?php
                $belumDibaca = ($notif["status_baca"] === "Belum Dibaca");
                ?>

                <div class="notifikasi-card <?= $belumDibaca ? 'unread' : ''; ?>">

                    <div class="notifikasi-icon">
                        <i class="bi bi-bell"></i>
                    </div>

                    <div class="notifikasi-content">

                        <div class="notifikasi-content-top">

                            <h3>
                                <?= escNotif($notif["judul"]); ?>
                            </h3>

                            <span class="notifikasi-status <?= $belumDibaca ? 'status-belum' : 'status-sudah'; ?>">
                                <?= escNotif($notif["status_baca"]); ?>
                            </span>

                        </div>

                        <div class="notifikasi-pesan">
                            <?= nl2br(escNotif($notif["pesan"])); ?>
                        </div>

                        <div class="notifikasi-footer">

                            <span class="notifikasi-waktu">
                                <i class="bi bi-clock me-1"></i>

                                <?= date(
                                    "d M Y, H:i",
                                    strtotime($notif["created_at"])
                                ); ?>
                            </span>

                            <?php if ($belumDibaca): ?>

                                <form method="POST">

                                    <input type="hidden"
                                           name="id_notifikasi"
                                           value="<?= (int)$notif["id_notifikasi"]; ?>">

                                    <button type="submit"
                                            name="baca_notifikasi"
                                            class="btn-tandai-baca">
                                        <i class="bi bi-check2 me-1"></i>
                                        Tandai Dibaca
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <!-- KOSONG -->
        <div class="notifikasi-empty">

            <div class="notifikasi-empty-icon">
                <i class="bi bi-bell-slash"></i>
            </div>

            <h3>Belum Ada Notifikasi</h3>

            <p>
                Saat ada informasi terbaru mengenai kegiatan belajar
                atau pembayaran, notifikasi akan muncul di halaman ini.
            </p>

        </div>

    <?php endif; ?>

</div>

</main>
    </div>
</div>

<?php
require_once __DIR__ . "/includes/footer.php";
?>