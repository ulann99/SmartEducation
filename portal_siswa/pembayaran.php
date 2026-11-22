
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
$current_page = "pembayaran";
$page_title = "Pembayaran";

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
// DATA PEMBAYARAN
// ==========================================
$daftarPembayaran = [];
$totalTransaksi = 0;
$totalLunas = 0;
$totalBelumLunas = 0;
$totalMenunggu = 0;
$totalDitolak = 0;

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

            $daftarPembayaran[] = $row;
            $totalTransaksi++;

            $status = strtolower(trim($row["status_pembayaran"] ?? ""));

            if ($status === "lunas") {
                $totalLunas += (float)$row["nominal"];
            } elseif ($status === "belum lunas") {
                $totalBelumLunas += (float)$row["nominal"];
            } elseif ($status === "menunggu konfirmasi") {
                $totalMenunggu += (float)$row["nominal"];
            } elseif ($status === "ditolak") {
                $totalDitolak += (float)$row["nominal"];
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
   PEMBAYARAN PORTAL SISWA
========================================= */

.payment-page {
    width: 100%;
}

.payment-heading {
    margin-bottom: 24px;
}

.payment-heading h2 {
    font-size: 22px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 6px;
}

.payment-heading p {
    font-size: 13px;
    color: #718582;
    margin: 0;
}

/* SUMMARY */
.payment-summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.payment-summary-card {
    background: #fff;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.payment-summary-icon {
    width: 48px;
    height: 48px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.payment-summary-icon.mint {
    background: #E8FDFF;
    color: #318E81;
}

.payment-summary-icon.green {
    background: #E8F8F0;
    color: #29936C;
}

.payment-summary-icon.orange {
    background: #FFF5E8;
    color: #D79439;
}

.payment-summary-text small {
    display: block;
    font-size: 11px;
    color: #81938F;
    margin-bottom: 6px;
}

.payment-summary-text strong {
    font-size: 18px;
    font-weight: 700;
    color: #203C39;
    overflow-wrap: anywhere;
}

/* CONTENT CARD */
.payment-card {
    background: #fff;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 22px;
    margin-bottom: 20px;
}

.payment-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 20px;
}

.payment-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #173B38;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.payment-card-title i {
    color: #318E81;
    font-size: 18px;
}

.payment-total-badge {
    background: #E8FDFF;
    color: #318E81;
    border-radius: 30px;
    padding: 7px 12px;
    font-size: 11px;
    font-weight: 600;
}

/* TABLE */
.payment-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.payment-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

.payment-table thead th {
    background: #F5FBF9;
    color: #718582;
    font-size: 11px;
    font-weight: 600;
    text-align: left;
    padding: 14px;
    border-bottom: 1px solid #E7F0ED;
    white-space: nowrap;
}

.payment-table tbody td {
    padding: 15px 14px;
    border-bottom: 1px solid #EEF4F2;
    font-size: 12px;
    color: #506762;
    vertical-align: middle;
}

.payment-table tbody tr:last-child td {
    border-bottom: none;
}

.payment-table tbody tr:hover {
    background: #FBFEFD;
}

.payment-code {
    font-weight: 600;
    color: #278578 !important;
}

.payment-amount {
    font-weight: 700;
    color: #203C39 !important;
    white-space: nowrap;
}

/* STATUS */
.payment-status {
    display: inline-block;
    border-radius: 30px;
    padding: 6px 11px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
}

.status-lunas {
    background: #E8F8F0;
    color: #26916B;
}

.status-pending {
    background: #FFF5E5;
    color: #C58A32;
}

.status-waiting {
    background: #E8FDFF;
    color: #318E81;
}

.status-rejected {
    background: #FDECEC;
    color: #D15B5B;
}

.status-default {
    background: #F1F4F3;
    color: #718582;
}

/* EMPTY STATE */
.payment-empty {
    text-align: center;
    padding: 45px 20px;
}

.payment-empty-icon {
    width: 65px;
    height: 65px;
    border-radius: 18px;
    background: #E8FDFF;
    color: #318E81;
    font-size: 27px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 17px;
}

.payment-empty h3 {
    font-size: 17px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 8px;
}

.payment-empty p {
    font-size: 13px;
    color: #718582;
    margin: 0;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
    .payment-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .payment-heading h2 {
        font-size: 19px;
    }

    .payment-summary-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .payment-summary-card {
        padding: 16px;
    }

    .payment-card {
        padding: 16px;
    }

    .payment-card-header {
        align-items: flex-start;
    }
}
</style>

<div class="payment-page">

    <!-- JUDUL -->
    <div class="payment-heading">
        <h2></h2>
        <p></p>
    </div>

    <!-- RINGKASAN -->
    <div class="payment-summary-grid">

        <!-- TOTAL TRANSAKSI -->
        <div class="payment-summary-card">

            <div class="payment-summary-icon mint">
                <i class="bi bi-receipt"></i>
            </div>

            <div class="payment-summary-text">
                <small>Total Transaksi</small>
                <strong><?= $totalTransaksi; ?> Transaksi</strong>
            </div>

        </div>

        <!-- TOTAL LUNAS -->
        <div class="payment-summary-card">

            <div class="payment-summary-icon green">
                <i class="bi bi-check-circle"></i>
            </div>

            <div class="payment-summary-text">
                <small>Total Pembayaran Lunas</small>
                <strong><?= rupiah($totalLunas); ?></strong>
            </div>

        </div>

        <!-- BELUM LUNAS -->
        <div class="payment-summary-card">

            <div class="payment-summary-icon orange">
                <i class="bi bi-wallet2"></i>
            </div>

            <div class="payment-summary-text">
                <small>Total Belum Lunas</small>
                <strong><?= rupiah($totalBelumLunas + $totalMenunggu); ?></strong>
            </div>

        </div>

    </div>

    <!-- TABEL PEMBAYARAN -->
    <div class="payment-card">

        <div class="payment-card-header">

            <h4 class="payment-card-title">
                <i class="bi bi-wallet2"></i>
                Daftar Pembayaran
            </h4>

            <span class="payment-total-badge">
                <?= $totalTransaksi; ?> Transaksi
            </span>

        </div>

        <?php if (!empty($daftarPembayaran)): ?>

            <div class="payment-table-wrapper">

                <table class="payment-table">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Transaksi</th>
                            <th>Tanggal</th>
                            <th>Nominal</th>
                            <th>Metode</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($daftarPembayaran as $index => $bayar): ?>

                        <?php
                        $status = $bayar["status_pembayaran"] ?? "-";

                        switch (strtolower(trim($status))) {
                            case "lunas":
                                $statusClass = "status-lunas";
                                break;

                            case "belum lunas":
                                $statusClass = "status-pending";
                                break;

                            case "menunggu konfirmasi":
                                $statusClass = "status-waiting";
                                break;

                            case "ditolak":
                                $statusClass = "status-rejected";
                                break;

                            default:
                                $statusClass = "status-default";
                        }
                        ?>

                        <tr>

                            <td><?= $index + 1; ?></td>

                            <td class="payment-code">
                                <?= e($bayar["kode_transaksi"]); ?>
                            </td>

                            <td>
                                <?php
                                if (!empty($bayar["tanggal_bayar"])) {
                                    echo date(
                                        "d/m/Y",
                                        strtotime($bayar["tanggal_bayar"])
                                    );
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>

                            <td class="payment-amount">
                                <?= rupiah($bayar["nominal"]); ?>
                            </td>

                            <td>
                                <?= e($bayar["metode_pembayaran"]); ?>
                            </td>

                            <td>
                                <span class="payment-status <?= $statusClass; ?>">
                                    <?= e($status); ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="payment-empty">

                <div class="payment-empty-icon">
                    <i class="bi bi-receipt"></i>
                </div>

                <h3>Belum Ada Transaksi Pembayaran</h3>

                <p>
                    Data transaksi pembayaran kamu akan ditampilkan
                    di halaman ini setelah pembayaran tercatat oleh Admin.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>

        </main>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
