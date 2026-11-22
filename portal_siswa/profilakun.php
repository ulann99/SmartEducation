
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/koneksi.php";

// VALIDASI LOGIN
if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: ../modules/auth/login.php");
    exit;
}

// VALIDASI ROLE
$role = $_SESSION["role"] ?? "";

if (!in_array($role, ["siswa", "siswa_orang_tua"], true)) {
    header("Location: ../dashboard.php");
    exit;
}

// DATA SESSION
$idUser  = (int)($_SESSION["user_id"] ?? 0);
$idSiswa = (int)($_SESSION["id_siswa"] ?? 0);

$nama = $_SESSION["nama_lengkap"]
    ?? $_SESSION["nama"]
    ?? $_SESSION["username"]
    ?? "Pengguna";

$username = $_SESSION["username"] ?? "-";
$email    = $_SESSION["email"] ?? "-";

$roleLabel = ($role === "siswa_orang_tua")
    ? "Siswa / Orang Tua"
    : "Siswa";

$inisial = strtoupper(substr($nama, 0, 1));
$page_title = "Profil Akun";

$pesan = "";
$jenisPesan = "";

// FOTO DEFAULT
$foto = "img/profilsiswa.jpg";

// AMBIL FOTO DARI DATABASE
if ($idSiswa > 0) {
    $stmtFoto = $conn->prepare(
        "SELECT foto FROM siswa WHERE id_siswa = ? LIMIT 1"
    );

    if ($stmtFoto) {
        $stmtFoto->bind_param("i", $idSiswa);
        $stmtFoto->execute();

        $resultFoto = $stmtFoto->get_result();
        $dataFoto = $resultFoto->fetch_assoc();

        if ($dataFoto && !empty($dataFoto["foto"])) {
            $fotoDatabase = $dataFoto["foto"];

            if (
                strtolower(basename($fotoDatabase)) !== "default.png" &&
                strtolower(basename($fotoDatabase)) !== "default.jpg"
            ) {
                // Foto upload berada di folder root uploads
                if (strpos($fotoDatabase, "uploads/") === 0) {
                    $foto = "../" . $fotoDatabase;
                } else {
                    $foto = "../" . ltrim($fotoDatabase, "/");
                }
            }
        }

        $stmtFoto->close();
    }
}

// PROSES UPLOAD FOTO
if (isset($_POST["upload_foto"])) {
    if (
        !isset($_FILES["foto_profil"]) ||
        $_FILES["foto_profil"]["error"] !== UPLOAD_ERR_OK
    ) {
        $pesan = "Foto gagal dipilih atau diupload.";
        $jenisPesan = "danger";

    } elseif ($idSiswa <= 0) {
        $pesan = "Data siswa tidak ditemukan.";
        $jenisPesan = "danger";

    } else {
        $file = $_FILES["foto_profil"];
        $maksUkuran = 2 * 1024 * 1024;

        if ($file["size"] > $maksUkuran) {
            $pesan = "Ukuran foto maksimal 2 MB.";
            $jenisPesan = "danger";

        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file["tmp_name"]);
            finfo_close($finfo);

            $formatDiizinkan = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            $validGambar = getimagesize($file["tmp_name"]);

            if (
                !isset($formatDiizinkan[$mime]) ||
                $validGambar === false
            ) {
                $pesan = "Format foto harus JPG, PNG, atau WebP.";
                $jenisPesan = "danger";

            } else {
                $folderUpload = __DIR__ . "/../uploads/profil/";

                if (!is_dir($folderUpload)) {
                    mkdir($folderUpload, 0755, true);
                }

                $ekstensi = $formatDiizinkan[$mime];
                $namaFile = "profil_" . $idSiswa . "_" .
                    bin2hex(random_bytes(8)) . "." . $ekstensi;

                $lokasiFile = $folderUpload . $namaFile;

                if (move_uploaded_file($file["tmp_name"], $lokasiFile)) {
                    $pathDatabase = "uploads/profil/" . $namaFile;

                    $stmtUpdateFoto = $conn->prepare(
                        "UPDATE siswa SET foto = ? WHERE id_siswa = ?"
                    );

                    if ($stmtUpdateFoto) {
                        $stmtUpdateFoto->bind_param(
                            "si",
                            $pathDatabase,
                            $idSiswa
                        );

                        if ($stmtUpdateFoto->execute()) {
                            $foto = "../" . $pathDatabase;

                            // Simpan path agar navbar ikut menggunakan foto baru
                            $_SESSION["foto"] = "../" . $pathDatabase;

                            $pesan = "Foto profil berhasil diperbarui.";
                            $jenisPesan = "success";
                        } else {
                            if (file_exists($lokasiFile)) {
                                unlink($lokasiFile);
                            }

                            $pesan = "Gagal menyimpan foto ke database.";
                            $jenisPesan = "danger";
                        }

                        $stmtUpdateFoto->close();
                    } else {
                        if (file_exists($lokasiFile)) {
                            unlink($lokasiFile);
                        }

                        $pesan = "Terjadi kesalahan pada database.";
                        $jenisPesan = "danger";
                    }
                } else {
                    $pesan = "Foto gagal diupload.";
                    $jenisPesan = "danger";
                }
            }
        }
    }
}

// PROSES UBAH PASSWORD
if (isset($_POST["ubah_password"])) {
    $passwordLama = $_POST["password_lama"] ?? "";
    $passwordBaru = $_POST["password_baru"] ?? "";
    $konfirmasi  = $_POST["konfirmasi_password"] ?? "";

    if (
        empty($passwordLama) ||
        empty($passwordBaru) ||
        empty($konfirmasi)
    ) {
        $pesan = "Semua kolom password wajib diisi.";
        $jenisPesan = "danger";

    } elseif ($passwordBaru !== $konfirmasi) {
        $pesan = "Konfirmasi password tidak cocok.";
        $jenisPesan = "danger";

    } elseif (strlen($passwordBaru) < 8) {
        $pesan = "Password baru minimal 8 karakter.";
        $jenisPesan = "danger";

    } else {
        $kolomUsers = [];
        $resultKolom = $conn->query("SHOW COLUMNS FROM users");

        if ($resultKolom) {
            while ($row = $resultKolom->fetch_assoc()) {
                $kolomUsers[] = $row["Field"];
            }
        }

        $kolomId = null;
        foreach (["id_user", "user_id", "id"] as $candidate) {
            if (in_array($candidate, $kolomUsers, true)) {
                $kolomId = $candidate;
                break;
            }
        }

        $kolomPassword = null;
        foreach (["password", "password_hash"] as $candidate) {
            if (in_array($candidate, $kolomUsers, true)) {
                $kolomPassword = $candidate;
                break;
            }
        }

        if (!$kolomId || !$kolomPassword || $idUser <= 0) {
            $pesan = "Data akun tidak ditemukan.";
            $jenisPesan = "danger";

        } else {
            $sql = "SELECT `$kolomPassword`
                    FROM users
                    WHERE `$kolomId` = ?
                    LIMIT 1";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("i", $idUser);
                $stmt->execute();

                $result = $stmt->get_result();
                $dataPassword = $result->fetch_assoc();
                $stmt->close();

                if (!$dataPassword) {
                    $pesan = "Akun tidak ditemukan.";
                    $jenisPesan = "danger";

                } elseif (!password_verify(
                    $passwordLama,
                    $dataPassword[$kolomPassword]
                )) {
                    $pesan = "Password lama salah.";
                    $jenisPesan = "danger";

                } else {
                    $hashBaru = password_hash(
                        $passwordBaru,
                        PASSWORD_BCRYPT
                    );

                    $sqlUpdate = "UPDATE users
                                  SET `$kolomPassword` = ?
                                  WHERE `$kolomId` = ?";

                    $stmtUpdate = $conn->prepare($sqlUpdate);

                    if ($stmtUpdate) {
                        $stmtUpdate->bind_param("si", $hashBaru, $idUser);

                        if ($stmtUpdate->execute()) {
                            $pesan = "Password berhasil diperbarui.";
                            $jenisPesan = "success";
                        } else {
                            $pesan = "Password gagal diperbarui.";
                            $jenisPesan = "danger";
                        }

                        $stmtUpdate->close();
                    } else {
                        $pesan = "Gagal memproses perubahan password.";
                        $jenisPesan = "danger";
                    }
                }
            } else {
                $pesan = "Gagal mengambil data akun.";
                $jenisPesan = "danger";
            }
        }
    }
}

// HEADER
require_once __DIR__ . "/includes/header.php";
?>

<div class="portal-layout">
    <?php require_once __DIR__ . "/includes/sidebar.php"; ?>

    <div class="portal-main">
        <?php require_once __DIR__ . "/includes/navbar.php"; ?>

        <main class="portal-content">

<style>
.profile-account-page {
    width: 100%;
}

/* HEADING */
.profile-heading {
    margin-bottom: 25px;
}

.profile-heading h2 {
    color: #173B38;
    font-weight: 700;
    font-size: 23px;
    margin-bottom: 7px;
}

.profile-heading p {
    color: #718582;
    font-size: 13px;
}

/* CARD */
.profile-card,
.security-card {
    background: #ffffff;
    border: 1px solid #E2EFEC;
    border-radius: 22px;
    padding: 30px;
    margin-bottom: 25px;
}

/* PROFIL */
.profile-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 35px 20px;
    border-bottom: 1px solid #EAF3F1;
    margin-bottom: 28px;
}

/* FOTO PROFIL - DIPERBESAR */
.profile-photo {
    width: 250px;
    height: 250px;
    border-radius: 50%;
    background: #98EDE1;
    border: 6px solid #E8FDFF;
    color: #226B61;
    box-shadow: 0 8px 25px rgba(64,151,137,.13);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 65px;
    font-weight: 700;
    overflow: hidden;
    margin-bottom: 22px;
}

.profile-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* NAMA */
.profile-header h3 {
    color: #173B38;
    font-size: 25px;
    font-weight: 700;
    margin-bottom: 6px;
}

/* USERNAME */
.profile-username {
    color: #81918D;
    font-size: 14px;
    margin-bottom: 15px;
}

/* BADGE */
.profile-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.profile-role {
    padding: 7px 14px;
    background: #E8FDFF;
    color: #248B80;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
}

.profile-status {
    padding: 7px 14px;
    background: #E5F8F1;
    color: #21805F;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
}

/* JUDUL BAGIAN */
.profile-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #173B38;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 20px;
}

.profile-section-title i {
    color: #329588;
    font-size: 18px;
}

/* INFORMASI */
.profile-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.profile-field {
    background: #F8FCFB;
    border: 1px solid #E9F2F0;
    border-radius: 12px;
    padding: 19px 20px;
}

.profile-field small {
    display: block;
    color: #82938F;
    font-size: 12px;
    margin-bottom: 9px;
}

.profile-field strong {
    color: #203C39;
    font-size: 14px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

/* KEAMANAN */
.security-info {
    background: #F0FCFA;
    border: 1px solid #D5F2ED;
    border-radius: 12px;
    padding: 16px;
    color: #58756F;
    font-size: 13px;
    line-height: 1.7;
    margin-bottom: 24px;
}

.password-group {
    margin-bottom: 20px;
}

.password-group label {
    display: block;
    color: #38534E;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 9px;
}

.password-group input {
    width: 100%;
    height: 53px;
    border: 1px solid #DCEAE7;
    border-radius: 11px;
    padding: 0 16px;
    background: #FCFEFD;
    color: #203C39;
    outline: none;
}

.password-group input:focus {
    border-color: #98EDE1;
    box-shadow: 0 0 0 4px rgba(152,237,225,.15);
}

/* BUTTON PASSWORD */
.password-submit {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    border: none;
    background: #98EDE1;
    color: #205C53;
    padding: 13px 22px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.password-submit:hover {
    background: #7DDFD1;
}

/* ALERT */
.profile-alert {
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 20px;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .profile-card,
    .security-card {
        padding: 20px;
        border-radius: 17px;
    }

    .profile-photo {
        width: 200px;
        height: 200px;
        font-size: 50px;
    }

    .profile-grid {
        grid-template-columns: 1fr;
    }

    .profile-header h3 {
        font-size: 21px;
    }

    .password-submit {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="profile-account-page">

    <div class="profile-heading">
        <h2></h2>
        <p></p>
    </div>

    <!-- PROFIL UTAMA -->
    <div class="profile-card">

        <div class="profile-header">

            <!-- FOTO PROFIL -->
            <div class="profile-photo">
                <?php if (!empty($foto)): ?>
                    <img src="<?= htmlspecialchars($foto, ENT_QUOTES, "UTF-8") ?>"
                         alt="Foto Profil"
                         onerror="this.style.display='none';">
                <?php else: ?>
                    <?= htmlspecialchars($inisial, ENT_QUOTES, "UTF-8") ?>
                <?php endif; ?>
            </div>

            <!-- NAMA -->
            <h3><?= htmlspecialchars($nama, ENT_QUOTES, "UTF-8") ?></h3>

            <!-- USERNAME -->
            <div class="profile-username">
                @<?= htmlspecialchars($username, ENT_QUOTES, "UTF-8") ?>
            </div>

            <!-- BADGE -->
            <div class="profile-badges">
                <span class="profile-role">
                    <i class="bi bi-person"></i>
                    <?= htmlspecialchars($roleLabel, ENT_QUOTES, "UTF-8") ?>
                </span>

                <span class="profile-status">
                    <i class="bi bi-check-circle"></i>
                    Aktif
                </span>
            </div>

        </div>

        <!-- PESAN -->
        <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?= htmlspecialchars($jenisPesan, ENT_QUOTES, "UTF-8") ?> profile-alert">
                <?= htmlspecialchars($pesan, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <!-- INFORMASI AKUN -->
        <h4 class="profile-section-title">
            <i class="bi bi-person-badge"></i>
            Informasi Akun
        </h4>

        <div class="profile-grid">
            <div class="profile-field">
                <small>Nama Pengguna</small>
                <strong><?= htmlspecialchars($nama, ENT_QUOTES, "UTF-8") ?></strong>
            </div>

            <div class="profile-field">
                <small>Username</small>
                <strong><?= htmlspecialchars($username, ENT_QUOTES, "UTF-8") ?></strong>
            </div>

            <div class="profile-field">
                <small>Email Akun</small>
                <strong><?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?></strong>
            </div>

            <div class="profile-field">
                <small>Role Akun</small>
                <strong><?= htmlspecialchars($roleLabel, ENT_QUOTES, "UTF-8") ?></strong>
            </div>
        </div>
    </div>

    <!-- KEAMANAN AKUN -->
    <div class="security-card">

        <h4 class="profile-section-title">
            <i class="bi bi-shield-lock"></i>
            Keamanan Akun
        </h4>

        <div class="security-info">
            <i class="bi bi-info-circle"></i>
            Gunakan password yang kuat dan jangan membagikan password akun kepada orang lain.
        </div>

        <!-- FORM PASSWORD -->
        <form method="POST">

            <div class="password-group">
                <label for="password_lama">Password Lama</label>
                <input type="password"
                       id="password_lama"
                       name="password_lama"
                       placeholder="Masukkan password lama"
                       autocomplete="current-password"
                       required>
            </div>

            <div class="password-group">
                <label for="password_baru">Password Baru</label>
                <input type="password"
                       id="password_baru"
                       name="password_baru"
                       placeholder="Masukkan password baru (minimal 8 karakter)"
                       minlength="8"
                       autocomplete="new-password"
                       required>
            </div>

            <div class="password-group">
                <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                <input type="password"
                       id="konfirmasi_password"
                       name="konfirmasi_password"
                       placeholder="Ulangi password baru"
                       minlength="8"
                       autocomplete="new-password"
                       required>
            </div>

            <button type="submit"
                    name="ubah_password"
                    value="1"
                    class="password-submit">
                <i class="bi bi-shield-check"></i>
                Perbarui Password
            </button>

        </form>
    </div>

</div>

        </main>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
