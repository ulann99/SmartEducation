
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
$current_page = "datapribadi";
$page_title = "Data Pribadi";

// ==========================================
// FUNGSI ESCAPE HTML
// ==========================================
function e($value)
{
    return htmlspecialchars((string)($value ?? "-"), ENT_QUOTES, "UTF-8");
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
// DATA IDENTITAS SESUAI DATABASE
// ==========================================
$nama = $dataSiswa["nama_siswa"]
    ?? $_SESSION["nama_lengkap"]
    ?? "Siswa";

$nis = $dataSiswa["nis"] ?? "-";

$jenisKelamin = $dataSiswa["jenis_kelamin"] ?? "-";

$alamat = $dataSiswa["alamat"] ?? "-";

$noHP = $dataSiswa["no_telepon"] ?? "-";

$email = $dataSiswa["email"] ?? "-";

// ==========================================
// AMBIL PAKET BELAJAR
// ==========================================
$namaPaket = "-";

if (!empty($dataSiswa["id_paket"])) {

    $stmtPaket = $conn->prepare(
        "SELECT nama_paket
         FROM paket_belajar
         WHERE id_paket = ?
         LIMIT 1"
    );

    if ($stmtPaket) {
        $id_paket = (int)$dataSiswa["id_paket"];

        $stmtPaket->bind_param("i", $id_paket);
        $stmtPaket->execute();

        $resultPaket = $stmtPaket->get_result();

        if ($resultPaket && $resultPaket->num_rows > 0) {
            $paket = $resultPaket->fetch_assoc();
            $namaPaket = $paket["nama_paket"];
        }

        $stmtPaket->close();
    }
}

// ==========================================
// INCLUDE LAYOUT PORTAL SISWA
// ==========================================
require_once __DIR__ . "/includes/header.php";
?>

<div class="portal-layout">

    <?php require_once __DIR__ . "/includes/sidebar.php"; ?>

    <div class="portal-main">

        <?php require_once __DIR__ . "/includes/navbar.php"; ?>

        <main class="portal-content">

<style>
.profile-page {
    width: 100%;
}

.profile-heading {
    margin-bottom: 24px;
}

.profile-heading h2 {
    font-size: 22px;
    font-weight: 700;
    color: #173B38;
    margin-bottom: 6px;
}

.profile-heading p {
    font-size: 13px;
    color: #718582;
}

.profile-card {
    background: #fff;
    border: 1px solid #E2EFEC;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 22px;
}

.profile-banner {
    height: 130px;
    background: linear-gradient(120deg, #98EDE1, #E8FDFF);
}

.profile-main {
    padding: 0 25px 25px;
}

.profile-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: #fff;
    border: 5px solid white;
    margin-top: -45px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #287E72;
    font-size: 35px;
    font-weight: 700;
    box-shadow: 0 3px 15px rgba(0,0,0,.08);
}

.profile-name {
    font-size: 20px;
    font-weight: 700;
    color: #173B38;
    margin-top: 14px;
    margin-bottom: 5px;
}

.profile-subtitle {
    font-size: 12px;
    color: #718582;
}

.profile-info-card {
    background: #fff;
    border: 1px solid #E2EFEC;
    border-radius: 15px;
    padding: 22px;
    margin-bottom: 20px;
}

.profile-section-title {
    font-size: 15px;
    font-weight: 600;
    color: #173B38;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-section-title i {
    color: #318E81;
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.profile-field {
    padding: 14px;
    background: #F8FCFB;
    border: 1px solid #EAF3F1;
    border-radius: 10px;
}

.profile-field-label {
    font-size: 11px;
    color: #82938F;
    margin-bottom: 7px;
}

.profile-field-value {
    font-size: 13px;
    font-weight: 600;
    color: #203C39;
    overflow-wrap: anywhere;
}

.profile-package {
    background: linear-gradient(120deg, #E8FDFF, #F7FFFE);
    border: 1px solid #D5F2ED;
    border-radius: 12px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.profile-package-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #98EDE1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #226B61;
    font-size: 21px;
    flex-shrink: 0;
}

.profile-package-info small {
    display: block;
    color: #718582;
    font-size: 11px;
    margin-bottom: 4px;
}

.profile-package-info strong {
    font-size: 14px;
    color: #173B38;
}

@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .profile-main {
        padding: 0 16px 20px;
    }

    .profile-info-card {
        padding: 17px;
    }

    .profile-heading h2 {
        font-size: 19px;
    }
}
</style>

<div class="profile-page">

    <!-- JUDUL HALAMAN -->
    <div class="profile-heading">
        <h2></h2>
        <p></p>
    </div>

    <!-- PROFIL UTAMA -->
    <div class="profile-card">

        <div class="profile-banner"></div>

        <div class="profile-main">

            <div class="profile-avatar">
                <?= strtoupper(substr($nama, 0, 1)); ?>
            </div>

            <h3 class="profile-name">
                <?= e($nama); ?>
            </h3>

            <div class="profile-subtitle">
                Siswa Smart Education
            </div>

        </div>
    </div>

    <!-- INFORMASI IDENTITAS -->
    <div class="profile-info-card">

        <h4 class="profile-section-title">
            <i class="bi bi-person-vcard"></i>
            Informasi Identitas
        </h4>

        <div class="profile-grid">

            <div class="profile-field">
                <div class="profile-field-label">Nama Lengkap</div>
                <div class="profile-field-value">
                    <?= e($nama); ?>
                </div>
            </div>

            <div class="profile-field">
                <div class="profile-field-label">Nomor Induk / NIS</div>
                <div class="profile-field-value">
                    <?= e($nis); ?>
                </div>
            </div>

            <div class="profile-field">
                <div class="profile-field-label">Jenis Kelamin</div>
                <div class="profile-field-value">
                    <?= e($jenisKelamin); ?>
                </div>
            </div>

            <div class="profile-field">
                <div class="profile-field-label">Nomor HP</div>
                <div class="profile-field-value">
                    <?= e($noHP); ?>
                </div>
            </div>

            <div class="profile-field">
                <div class="profile-field-label">Email</div>
                <div class="profile-field-value">
                    <?= e($email); ?>
                </div>
            </div>

            <div class="profile-field">
                <div class="profile-field-label">Alamat</div>
                <div class="profile-field-value">
                    <?= e($alamat); ?>
                </div>
            </div>

        </div>

    </div>

    <!-- PAKET BELAJAR -->
    <div class="profile-info-card">

        <h4 class="profile-section-title">
            <i class="bi bi-book"></i>
            Paket Belajar
        </h4>

        <div class="profile-package">

            <div class="profile-package-icon">
                <i class="bi bi-mortarboard"></i>
            </div>

            <div class="profile-package-info">
                <small>Paket belajar yang diikuti</small>
                <strong>
                    <?= e($namaPaket); ?>
                </strong>
            </div>

        </div>

    </div>

</div>

        </main>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
