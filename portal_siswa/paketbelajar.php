
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

$current_page = "paketbelajar";
$page_title = "Paket Belajar";

// ==========================================
// FUNGSI ESCAPE
// ==========================================
function e($value)
{
    return htmlspecialchars((string)($value ?? "-"), ENT_QUOTES, "UTF-8");
}

// ==========================================
// FUNGSI ALTERNATIF KOLOM
// ==========================================
function ambilData($data, $alternatif, $default = "-")
{
    foreach ($alternatif as $kolom) {
        if (isset($data[$kolom]) && $data[$kolom] !== "") {
            return $data[$kolom];
        }
    }

    return $default;
}

// ==========================================
// AMBIL DATA SISWA
// ==========================================
$dataSiswa = [];

$stmt = $conn->prepare(
    "SELECT * FROM siswa WHERE id_siswa = ? LIMIT 1"
);

if ($stmt) {
    $stmt->bind_param("i", $id_siswa);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $dataSiswa = $result->fetch_assoc();
    }

    $stmt->close();
}

// ==========================================
// AMBIL PAKET SISWA
// ==========================================
$dataPaket = [];

if (!empty($dataSiswa["id_paket"])) {

    $id_paket = (int)$dataSiswa["id_paket"];

    $stmtPaket = $conn->prepare(
        "SELECT * FROM paket_belajar WHERE id_paket = ? LIMIT 1"
    );

    if ($stmtPaket) {
        $stmtPaket->bind_param("i", $id_paket);
        $stmtPaket->execute();

        $resultPaket = $stmtPaket->get_result();

        if ($resultPaket && $resultPaket->num_rows > 0) {
            $dataPaket = $resultPaket->fetch_assoc();
        }

        $stmtPaket->close();
    }
}

// ==========================================
// DATA PAKET
// ==========================================
$namaPaket = ambilData(
    $dataPaket,
    ["nama_paket", "nama", "paket"]
);

$deskripsi = ambilData(
    $dataPaket,
    ["deskripsi", "keterangan", "detail_paket"],
    "Tidak ada deskripsi paket."
);

$harga = ambilData(
    $dataPaket,
    ["harga", "harga_paket", "biaya", "biaya_paket", "nominal"]
);

$durasi = ambilData(
    $dataPaket,
    ["durasi", "lama_belajar", "periode", "masa_belajar"]
);

$statusPaket = ambilData(
    $dataPaket,
    ["status", "status_paket"],
    "Terdaftar"
);

// ==========================================
// FORMAT HARGA
// ==========================================
if ($harga !== "-" && is_numeric($harga)) {
    $hargaTampil = "Rp " . number_format((float)$harga, 0, ",", ".");
} else {
    $hargaTampil = $harga;
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
.paket-page {
    width: 100%;
}

.paket-heading {
    margin-bottom: 24px;
}

.paket-heading h2 {
    font-size: 22px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 6px;
}

.paket-heading p {
    font-size: 13px;
    color: #718582;
    margin: 0;
}

.paket-card {
    background: #fff;
    border: 1px solid #E2EFEC;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 22px;
}

.paket-banner {
    padding: 28px;
    background: linear-gradient(120deg, #98EDE1, #E8FDFF);
    position: relative;
}

.paket-banner-label {
    font-size: 12px;
    color: #31796F;
    font-weight: 600;
    margin-bottom: 10px;
}

.paket-banner h3 {
    font-size: 24px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 10px;
}

.paket-banner p {
    font-size: 13px;
    color: #456B65;
    margin: 0;
}

.paket-content {
    padding: 24px;
}

.paket-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #E9FAF5;
    color: #27806F;
    border: 1px solid #CDEDE3;
    border-radius: 30px;
    padding: 7px 13px;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 20px;
}

.paket-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.paket-info {
    background: #F8FCFB;
    border: 1px solid #EAF3F1;
    border-radius: 12px;
    padding: 17px;
}

.paket-info-label {
    color: #81938F;
    font-size: 11px;
    margin-bottom: 8px;
}

.paket-info-value {
    font-size: 17px;
    font-weight: 700;
    color: #203C39;
    overflow-wrap: anywhere;
}

.paket-info-value.harga {
    color: #27806F;
}

.paket-description {
    margin-top: 20px;
    background: #F8FCFB;
    border: 1px solid #EAF3F1;
    border-radius: 12px;
    padding: 18px;
}

.paket-description h4 {
    font-size: 14px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 10px;
}

.paket-description p {
    color: #718582;
    font-size: 13px;
    line-height: 1.8;
    margin: 0;
}

.paket-empty {
    background: #fff;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 45px 25px;
    text-align: center;
}

.paket-empty-icon {
    width: 65px;
    height: 65px;
    background: #E8FDFF;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #318E81;
    font-size: 28px;
    margin: 0 auto 17px;
}

.paket-empty h3 {
    font-size: 17px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 8px;
}

.paket-empty p {
    font-size: 13px;
    color: #718582;
    margin: 0;
}

@media (max-width: 768px) {
    .paket-grid {
        grid-template-columns: 1fr;
    }

    .paket-banner {
        padding: 22px;
    }

    .paket-banner h3 {
        font-size: 20px;
    }

    .paket-content {
        padding: 17px;
    }

    .paket-heading h2 {
        font-size: 19px;
    }
}
</style>

<div class="paket-page">

    <!-- JUDUL -->
    <div class="paket-heading">
        <h2></h2>
        <p></p>
    </div>

    <?php if (!empty($dataPaket)): ?>

        <!-- KARTU PAKET -->
        <div class="paket-card">

            <!-- BANNER PAKET -->
            <div class="paket-banner">

                <div class="paket-banner-label">
                    <i class="bi bi-mortarboard me-1"></i>
                    PAKET BELAJAR SMART EDUCATION
                </div>

                <h3><?= e($namaPaket); ?></h3>

                <p>
                    Paket belajar yang telah terdaftar pada akun siswa.
                </p>

            </div>

            <!-- DETAIL PAKET -->
            <div class="paket-content">

                <div class="paket-status">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= e($statusPaket); ?>
                </div>

                <div class="paket-grid">

                    <!-- BIAYA -->
                    <div class="paket-info">
                        <div class="paket-info-label">
                            Biaya Paket
                        </div>

                        <div class="paket-info-value harga">
                            <?= e($hargaTampil); ?>
                        </div>
                    </div>

                    <!-- DURASI -->
                    <div class="paket-info">
                        <div class="paket-info-label">
                            Durasi Belajar
                        </div>

                        <div class="paket-info-value">
                            <?= e($durasi); ?>
                        </div>
                    </div>

                </div>

                <!-- DESKRIPSI -->
                <div class="paket-description">

                    <h4>
                        <i class="bi bi-info-circle me-2"></i>
                        Deskripsi Paket
                    </h4>

                    <p>
                        <?= nl2br(e($deskripsi)); ?>
                    </p>

                </div>

            </div>

        </div>

    <?php else: ?>

        <!-- JIKA SISWA BELUM MEMILIKI PAKET -->
        <div class="paket-empty">

            <div class="paket-empty-icon">
                <i class="bi bi-book"></i>
            </div>

            <h3>Belum Ada Paket Belajar</h3>

            <p>
                Saat ini belum ada paket belajar yang terhubung
                dengan akun kamu. Silakan hubungi Admin Smart Education
                untuk informasi lebih lanjut.
            </p>

        </div>

    <?php endif; ?>

</div>

        </main>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
