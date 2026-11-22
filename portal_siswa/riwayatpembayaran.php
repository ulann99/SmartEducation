
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
// ID SISWA
// ==========================================
$id_siswa = (int)($_SESSION["id_siswa"] ?? 0);

if ($id_siswa <= 0) {
    die("Data siswa tidak ditemukan.");
}

// ==========================================
// INFORMASI HALAMAN
// ==========================================
$current_page = "riwayatpembayaran";
$page_title = "Riwayat Pembayaran";

// ==========================================
// HELPER
// ==========================================
function e($value)
{
    return htmlspecialchars((string)($value ?? "-"), ENT_QUOTES, "UTF-8");
}

function rupiah($angka)
{
    return "Rp " . number_format((float)$angka, 0, ",", ".");
}

// ==========================================
// AMBIL RIWAYAT PEMBAYARAN
// ==========================================
$riwayat = [];
$totalTransaksi = 0;
$totalNominal = 0;
$totalLunas = 0;

$stmt = $conn->prepare("
    SELECT
        kode_transaksi,
        tanggal_bayar,
        nominal,
        metode_pembayaran,
        status_pembayaran
    FROM pembayaran
    WHERE id_siswa = ?
    ORDER BY tanggal_bayar DESC
");

if ($stmt) {
    $stmt->bind_param("i", $id_siswa);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {

            $riwayat[] = $row;
            $totalTransaksi++;

            $totalNominal += (float)$row["nominal"];

            if (
                strtolower(trim($row["status_pembayaran"] ?? "")) === "lunas"
            ) {
                $totalLunas += (float)$row["nominal"];
            }
        }
    }

    $stmt->close();
}

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
   RIWAYAT PEMBAYARAN
========================================= */

.history-page {
    width: 100%;
}

.history-heading {
    margin-bottom: 24px;
}

.history-heading h2 {
    font-size: 22px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 6px;
}

.history-heading p {
    font-size: 13px;
    color: #718582;
    margin: 0;
}

/* SUMMARY */
.history-summary {
    background: linear-gradient(120deg, #98EDE1, #E8FDFF);
    border-radius: 16px;
    padding: 23px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.history-summary small {
    display: block;
    color: #438278;
    font-size: 12px;
    margin-bottom: 7px;
}

.history-summary h3 {
    font-size: 21px;
    color: #173B38;
    font-weight: 700;
    margin: 0;
}

.history-summary-icon {
    width: 55px;
    height: 55px;
    border-radius: 15px;
    background: rgba(255,255,255,.65);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #278578;
    font-size: 24px;
}

/* HISTORY CARD */
.history-card {
    background: #fff;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 22px;
    margin-bottom: 20px;
}

.history-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.history-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #173B38;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.history-card-title i {
    color: #318E81;
    font-size: 18px;
}

.history-badge {
    background: #E8FDFF;
    color: #318E81;
    border-radius: 30px;
    padding: 7px 12px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

/* TABLE */
.history-table-wrapper {
    overflow-x: auto;
    width: 100%;
}

.history-table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
}

.history-table thead th {
    background: #F5FBF9;
    color: #718582;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    padding: 14px;
    white-space: nowrap;
    border-bottom: 1px solid #E7F0ED;
}

.history-table tbody td {
    padding: 15px 14px;
    border-bottom: 1px solid #EEF4F2;
    font-size: 12px;
    color: #506762;
    vertical-align: middle;
}

.history-table tbody tr:last-child td {
    border-bottom: none;
}

.history-table tbody tr:hover {
    background: #FBFEFD;
}

.history-code {
    color: #278578 !important;
    font-weight: 600;
}

.history-amount {
    color: #203C39 !important;
    font-weight: 700;
    white-space: nowrap;
}

/* STATUS */
.history-status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 30px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
}

.history-status-lunas {
    background: #E8F8F0;
    color: #26916B;
}

.history-status-pending {
    background: #FFF5E5;
    color: #C58A32;
}

.history-status-waiting {
    background: #E8FDFF;
    color: #318E81;
}

.history-status-rejected {
    background: #FDECEC;
    color: #D15B5B;
}

.history-status-default {
    background: #F1F4F3;
    color: #718582;
}

/* EMPTY */
.history-empty {
    text-align: center;
    padding: 45px 20px;
}

.history-empty-icon {
    width: 65px;
    height: 65px;
    background: #E8FDFF;
    color: #318E81;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27px;
    margin: 0 auto 17px;
}

.history-empty h3 {
    font-size: 17px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 8px;
}

.history-empty p {
    font-size: 13px;
    color: #718582;
    margin: 0;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .history-heading h2 {
        font-size: 19px;
    }

    .history-summary {
        padding: 18px;
    }

    .history-summary h3 {
        font-size: 17px;
    }

    .history-card {
        padding: 16px;
    }
}
</style>

<div class="history-page">

    <!-- JUDUL -->
    <div class="history-heading">
        <h2></h2>
        <p></p>
    </div>

    <!-- RINGKASAN -->
    <div class="history-summary">

        <div>
            <small>Seluruh Riwayat Transaksi</small>
            <h3><?= $totalTransaksi; ?> Transaksi Pembayaran</h3>
        </div>

        <div class="history-summary-icon">
            <i class="bi bi-clock-history"></i>
        </div>

    </div>

    <!-- DAFTAR RIWAYAT -->
    <div class="history-card">

        <div class="history-card-header">

            <h4 class="history-card-title">
                <i class="bi bi-receipt"></i>
                Histori Transaksi
            </h4>

            <span class="history-badge">
                <?= $totalTransaksi; ?> Transaksi
            </span>

        </div>

        <?php if (!empty($riwayat)): ?>

            <div class="history-table-wrapper">

                <table class="history-table">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Transaksi</th>
                            <th>Tanggal Pembayaran</th>
                            <th>Nominal</th>
                            <th>Metode</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($riwayat as $index => $item): ?>

                        <?php
                        $status = $item["status_pembayaran"] ?? "-";

                        switch (strtolower(trim($status))) {
                            case "lunas":
                                $statusClass = "history-status-lunas";
                                break;

                            case "belum lunas":
                                $statusClass = "history-status-pending";
                                break;

                            case "menunggu konfirmasi":
                                $statusClass = "history-status-waiting";
                                break;

                            case "ditolak":
                                $statusClass = "history-status-rejected";
                                break;

                            default:
                                $statusClass = "history-status-default";
                        }
                        ?>

                        <tr>

                            <td><?= $index + 1; ?></td>

                            <td class="history-code">
                                <?= e($item["kode_transaksi"]); ?>
                            </td>

                            <td>
                                <?php
                                if (!empty($item["tanggal_bayar"])) {
                                    echo date(
                                        "d/m/Y",
                                        strtotime($item["tanggal_bayar"])
                                    );
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>

                            <td class="history-amount">
                                <?= rupiah($item["nominal"]); ?>
                            </td>

                            <td>
                                <?= e($item["metode_pembayaran"]); ?>
                            </td>

                            <td>
                                <span class="history-status <?= $statusClass; ?>">
                                    <?= e($status); ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="history-empty">

                <div class="history-empty-icon">
                    <i class="bi bi-clock-history"></i>
                </div>

                <h3>Belum Ada Riwayat Pembayaran</h3>

                <p>
                    Riwayat transaksi kamu akan muncul di sini
                    setelah pembayaran tercatat di sistem.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>

        </main>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
