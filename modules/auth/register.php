<?php
// ========================================
// KONFIGURASI DAN DEBUG PHP
// ========================================

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/koneksi.php';

// ========================================
// FUNGSI BANTUAN
// ========================================

function register_escape($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function register_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// ========================================
// TOKEN KEAMANAN
// ========================================

if (empty($_SESSION['csrf_register'])) {
    $_SESSION['csrf_register'] = bin2hex(random_bytes(32));
}

$pesan = '';
$jenis_pesan = '';

// ========================================
// AMBIL PAKET BELAJAR
// ========================================

try {
    $query_paket = mysqli_query($conn, "
        SELECT id_paket, nama_paket, jenjang, harga
        FROM paket_belajar
        WHERE status = 'Aktif'
        ORDER BY nama_paket ASC
    ");
} catch (Throwable $error) {
    die(
        "Kesalahan mengambil paket belajar: " .
        register_escape($error->getMessage())
    );
}

// ========================================
// PROSES PENDAFTARAN
// ========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_register'], $token)
    ) {
        $pesan = "Permintaan tidak valid. Silakan muat ulang halaman.";
        $jenis_pesan = "error";

    } else {

        $nama = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
        $telepon = trim($_POST['no_telepon'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $id_paket_input = $_POST['id_paket'] ?? '';
        $password = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';

        // VALIDASI DATA

        if (
            $nama === '' ||
            $username === '' ||
            $email === '' ||
            $jenis_kelamin === '' ||
            $password === '' ||
            $konfirmasi === ''
        ) {
            $pesan = "Mohon lengkapi seluruh kolom wajib.";
            $jenis_pesan = "error";

        } elseif (strlen($nama) > 100) {
            $pesan = "Nama lengkap maksimal 100 karakter.";
            $jenis_pesan = "error";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pesan = "Format email tidak valid.";
            $jenis_pesan = "error";

        } elseif (strlen($email) > 100) {
            $pesan = "Email maksimal 100 karakter.";
            $jenis_pesan = "error";

        } elseif (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'], true)) {
            $pesan = "Jenis kelamin tidak valid.";
            $jenis_pesan = "error";

        } elseif (!preg_match('/^[a-zA-Z0-9._-]{4,50}$/', $username)) {
            $pesan = "Username harus 4-50 karakter.";
            $jenis_pesan = "error";

        } elseif (strlen($password) < 8) {
            $pesan = "Password minimal 8 karakter.";
            $jenis_pesan = "error";

        } elseif ($password !== $konfirmasi) {
            $pesan = "Konfirmasi password tidak sesuai.";
            $jenis_pesan = "error";

        } elseif (strlen($telepon) > 20) {
            $pesan = "Nomor telepon maksimal 20 karakter.";
            $jenis_pesan = "error";

        } else {

            // VALIDASI PAKET BELAJAR

            $id_paket = null;

            if ($id_paket_input !== '') {

                if (!ctype_digit((string)$id_paket_input)) {

                    $pesan = "Pilihan paket tidak valid.";
                    $jenis_pesan = "error";

                } else {

                    $id_paket = (int)$id_paket_input;

                    try {
                        $stmt_paket = mysqli_prepare($conn, "
                            SELECT id_paket
                            FROM paket_belajar
                            WHERE id_paket = ?
                            AND status = 'Aktif'
                            LIMIT 1
                        ");

                        mysqli_stmt_bind_param(
                            $stmt_paket,
                            "i",
                            $id_paket
                        );

                        mysqli_stmt_execute($stmt_paket);

                        $result_paket = mysqli_stmt_get_result($stmt_paket);
                        $paket_valid = mysqli_num_rows($result_paket) > 0;

                        mysqli_stmt_close($stmt_paket);

                        if (!$paket_valid) {
                            $pesan = "Paket belajar tidak tersedia.";
                            $jenis_pesan = "error";
                        }

                    } catch (Throwable $error) {
                        die(
                            "Kesalahan validasi paket: " .
                            register_escape($error->getMessage())
                        );
                    }
                }
            }

            // CEK USERNAME DAN EMAIL

            if ($pesan === '') {

                try {
                    $stmt_cek = mysqli_prepare($conn, "
                        SELECT id
                        FROM users
                        WHERE username = ? OR email = ?
                        LIMIT 1
                    ");

                    mysqli_stmt_bind_param(
                        $stmt_cek,
                        "ss",
                        $username,
                        $email
                    );

                    mysqli_stmt_execute($stmt_cek);

                    $result_cek = mysqli_stmt_get_result($stmt_cek);
                    $sudah_ada = mysqli_num_rows($result_cek) > 0;

                    mysqli_stmt_close($stmt_cek);

                    if ($sudah_ada) {
                        $pesan = "Username atau email sudah digunakan.";
                        $jenis_pesan = "error";
                    }

                } catch (Throwable $error) {
                    die(
                        "Kesalahan pemeriksaan akun: " .
                        register_escape($error->getMessage())
                    );
                }
            }

            // SIMPAN DATA SISWA DAN AKUN

            if ($pesan === '') {

                try {

                    mysqli_begin_transaction($conn);

                    $nis = 'SE' . date('ymdHis') . random_int(10, 99);
                    $tanggal_daftar = date('Y-m-d');
                    $status_siswa = 'Aktif';

                    // SIMPAN SISWA

                    $stmt_siswa = mysqli_prepare($conn, "
                        INSERT INTO siswa (
                            nis,
                            nama_siswa,
                            jenis_kelamin,
                            alamat,
                            no_telepon,
                            email,
                            id_paket,
                            status_siswa,
                            tanggal_daftar
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    mysqli_stmt_bind_param(
                        $stmt_siswa,
                        "ssssssiss",
                        $nis,
                        $nama,
                        $jenis_kelamin,
                        $alamat,
                        $telepon,
                        $email,
                        $id_paket,
                        $status_siswa,
                        $tanggal_daftar
                    );

                    mysqli_stmt_execute($stmt_siswa);

                    $id_siswa_baru = mysqli_insert_id($conn);

                    mysqli_stmt_close($stmt_siswa);

                    // HASH PASSWORD BCRYPT

                    $hash_password = password_hash(
                        $password,
                        PASSWORD_BCRYPT
                    );

                    if ($hash_password === false) {
                        throw new Exception("Gagal mengamankan password.");
                    }

                    // ROLE SISWA

                    $role = 'siswa';

                    // SIMPAN AKUN PENGGUNA

                    $stmt_user = mysqli_prepare($conn, "
                        INSERT INTO users (
                            username,
                            password,
                            nama_lengkap,
                            email,
                            role,
                            id_siswa
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    mysqli_stmt_bind_param(
                        $stmt_user,
                        "sssssi",
                        $username,
                        $hash_password,
                        $nama,
                        $email,
                        $role,
                        $id_siswa_baru
                    );

                    mysqli_stmt_execute($stmt_user);

                    mysqli_stmt_close($stmt_user);

                    mysqli_commit($conn);

                    $_SESSION['csrf_register'] = bin2hex(random_bytes(32));

                    header("Location: login.php?daftar=berhasil");
                    exit;

                } catch (Throwable $error) {

                    mysqli_rollback($conn);

                    $pesan = "Pendaftaran gagal: " . $error->getMessage();
                    $jenis_pesan = "error";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Daftar Akun - Smart Education Parepare</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        /* =====================================
           RESET DAN VARIABEL
        ===================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #267A55;
            --primary-dark: #19583E;
            --mint: #C7EFE2;
            --dark: #173B38;
            --muted: #849087;
            --border: #D8E2DC;
        }

        body {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 25px 35px;

            background:
                linear-gradient(
                    rgba(15, 35, 27, 0.38),
                    rgba(15, 35, 27, 0.38)
                ),
                url("../../assets/images/background-login.jpg")
                center center / cover no-repeat fixed;

            animation: pageFade 0.8s ease both;
        }

        /* =====================================
           ANIMASI
        ===================================== */

        @keyframes pageFade {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideLeft {
            from {
                opacity: 0;
                transform: translateX(-35px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideRight {
            from {
                opacity: 0;
                transform: translateX(35px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes softFade {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =====================================
           CONTAINER UTAMA
        ===================================== */

        .register-container {
            width: 100%;
            max-width: 1280px;

            display: grid;
            grid-template-columns: 0.9fr 1.1fr;

            align-items: center;
            gap: 35px;
        }

        /* =====================================
           BRANDING KIRI
        ===================================== */

        .welcome-section {
            min-width: 0;
            min-height: 500px;

            display: flex;
            flex-direction: column;
            justify-content: center;

            padding: 25px 15px;

            color: #FFFFFF;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.28);

            animation: slideLeft 0.9s cubic-bezier(.22, 1, .36, 1) both;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .brand img {
            width: 115px;
            height: 115px;
            flex-shrink: 0;

            padding: 8px;
            border-radius: 22px;

            background: #FFFFFF;
            object-fit: contain;

            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.13);

            transition:
                transform 0.4s cubic-bezier(.22, 1, .36, 1),
                box-shadow 0.35s ease;
        }

        .brand img:hover {
            transform: translateY(-4px) scale(1.035);
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.2);
        }

        .brand-divider {
            width: 3px;
            height: 130px;
            flex-shrink: 0;

            background: #72D6B7;
            border-radius: 10px;
        }

        .brand-text {
            animation: softFade 0.9s ease 0.15s both;
        }

        .brand-text h1 {
            color: #FFFFFF;
            font-size: clamp(34px, 4vw, 50px);
            line-height: 1.12;
            font-weight: 800;
            letter-spacing: -1.5px;
        }

        .brand-text span {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 20px;

            border-radius: 30px;

            background: var(--mint);
            color: #16805B;

            font-size: 14px;
            font-weight: 800;
            letter-spacing: 7px;

            text-shadow: none;
        }

        .brand-tagline {
            max-width: 470px;
            margin-top: 30px;
            margin-left: 3px;

            color: #FFFFFF;
            font-size: 17px;
            line-height: 1.8;

            animation: softFade 0.9s ease 0.3s both;
        }

        .brand-line {
            width: 100px;
            height: 5px;
            margin-top: 18px;
            margin-left: 3px;

            border-radius: 20px;
            background: #72D6B7;

            animation: softFade 0.9s ease 0.4s both;
        }

        /* =====================================
           KARTU FORM
        ===================================== */

        .form-section {
            width: 100%;
            max-width: 690px;
            justify-self: center;

            padding: 30px 38px;

            background: #FFFFFF;
            border-radius: 20px;

            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15);

            animation: slideRight 0.9s cubic-bezier(.22, 1, .36, 1) both;

            transition:
                box-shadow 0.35s ease,
                transform 0.35s ease;
        }

        .form-section:hover {
            box-shadow: 0 20px 55px rgba(0, 0, 0, 0.17);
        }

        .form-heading {
            margin-bottom: 18px;
            animation: softFade 0.7s ease 0.2s both;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 7px;

            color: #39815F;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2.5px;
        }

        .form-heading h2 {
            margin-bottom: 5px;
            color: #263C32;
            font-size: 27px;
            font-weight: 600;
        }

        .form-heading p {
            color: #849087;
            font-size: 11px;
            line-height: 1.7;
        }

        /* =====================================
           ALERT
        ===================================== */

        .alert {
            padding: 10px 13px;
            margin-bottom: 14px;

            border-radius: 8px;
            font-size: 11px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .alert.error {
            background: #FFF0F0;
            color: #B42318;
            border: 1px solid #FFD6D6;
        }

        /* =====================================
           FORM GRID
        ===================================== */

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 11px 15px;
        }

        .form-group {
            min-width: 0;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;

            color: #465B53;
            font-size: 10px;
            font-weight: 600;
        }

        .required {
            color: #D14343;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            height: 41px;
            padding: 0 11px;

            border: 1px solid var(--border);
            border-radius: 9px;

            outline: none;

            color: #263C32;
            background: #FBFDFC;

            font-family: inherit;
            font-size: 10px;

            transition:
                border-color 0.25s ease,
                box-shadow 0.25s ease,
                background-color 0.25s ease,
                transform 0.25s ease;
        }

        .form-group textarea {
            height: 53px;
            padding: 9px 11px;
            resize: vertical;
        }

        .form-group input:hover,
        .form-group select:hover,
        .form-group textarea:hover {
            border-color: #A9CDBB;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(38, 122, 85, 0.09);
            transform: translateY(-1px);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #929B96;
        }

        /* =====================================
           PASSWORD
        ===================================== */

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 65px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 11px;
            transform: translateY(-50%);

            border: none;
            background: transparent;

            color: #26734F;
            font-family: inherit;
            font-size: 9px;
            font-weight: 600;

            cursor: pointer;

            transition:
                color 0.25s ease,
                transform 0.25s ease;
        }

        .toggle-password:hover {
            color: var(--primary-dark);
            transform: translateY(-50%) scale(1.05);
        }

        .password-note {
            display: block;
            margin-top: 4px;

            color: #849087;
            font-size: 9px;
        }

        /* =====================================
           TOMBOL DAFTAR
        ===================================== */

        .submit-btn {
            position: relative;
            overflow: hidden;

            width: 100%;
            height: 47px;
            margin-top: 17px;

            border: none;
            border-radius: 9px;

            background: var(--primary);
            color: #FFFFFF;

            font-family: inherit;
            font-size: 11px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background 0.3s ease,
                transform 0.3s ease,
                box-shadow 0.3s ease;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(38, 115, 79, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0) scale(0.985);
            box-shadow: none;
        }

        .submit-btn span {
            display: inline-block;
            margin-left: 9px;
            font-size: 14px;

            transition: transform 0.3s ease;
        }

        .submit-btn:hover span {
            transform: translateX(5px);
        }

        /* =====================================
           LOGIN DAN FOOTER
        ===================================== */

        .login-link {
            margin-top: 15px;

            color: #849087;
            font-size: 10px;
            text-align: center;
        }

        .login-link a {
            color: #26734F;
            font-weight: 700;
            text-decoration: none;

            transition:
                color 0.25s ease,
                letter-spacing 0.25s ease;
        }

        .login-link a:hover {
            color: var(--primary-dark);
            letter-spacing: 0.2px;
        }

        .form-footer {
            margin-top: 14px;

            color: #A0ACA5;
            font-size: 9px;
            text-align: center;
        }

        /* =====================================
           RESPONSIVE LAPTOP
        ===================================== */

        @media (max-width: 1100px) {

            .register-container {
                grid-template-columns: 0.8fr 1.2fr;
                gap: 20px;
            }

            .brand {
                gap: 12px;
            }

            .brand img {
                width: 85px;
                height: 85px;
            }

            .brand-divider {
                height: 100px;
            }

            .brand-text h1 {
                font-size: 32px;
            }

            .brand-text span {
                font-size: 10px;
                letter-spacing: 5px;
            }

            .brand-tagline {
                font-size: 14px;
            }

            .form-section {
                padding: 25px;
            }
        }

        /* =====================================
           RESPONSIVE TABLET
        ===================================== */

        @media (max-width: 800px) {

            body {
                padding: 20px 14px;
                background-attachment: scroll;
            }

            .register-container {
                max-width: 560px;
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .welcome-section {
                min-height: auto;
                padding: 10px 5px;
            }

            .brand {
                justify-content: center;
            }

            .brand img {
                width: 65px;
                height: 65px;
            }

            .brand-divider {
                height: 75px;
            }

            .brand-text h1 {
                font-size: 27px;
            }

            .brand-text span {
                margin-top: 6px;
                font-size: 9px;
                letter-spacing: 4px;
            }

            .brand-tagline {
                margin: 12px auto 0;
                text-align: center;
                font-size: 12px;
            }

            .brand-line {
                width: 70px;
                height: 4px;
                margin: 10px auto 0;
            }

            .form-section {
                max-width: 560px;
                padding: 25px;
            }
        }

        /* =====================================
           RESPONSIVE HP
        ===================================== */

        @media (max-width: 480px) {

            body {
                padding: 14px 10px;
            }

            .brand {
                gap: 10px;
            }

            .brand img {
                width: 55px;
                height: 55px;
            }

            .brand-divider {
                height: 65px;
                width: 2px;
            }

            .brand-text h1 {
                font-size: 23px;
            }

            .brand-text span {
                padding: 5px 9px;
                font-size: 8px;
                letter-spacing: 3px;
            }

            .brand-tagline {
                font-size: 11px;
            }

            .form-section {
                padding: 22px 17px;
                border-radius: 15px;
            }

            .form-heading h2 {
                font-size: 24px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .form-group.full {
                grid-column: auto;
            }

            .form-group input,
            .form-group select {
                height: 42px;
            }

            .submit-btn {
                height: 45px;
            }
        }

        /* =====================================
           AKSESIBILITAS ANIMASI
        ===================================== */

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

    </style>

</head>

<body>

<div class="register-container">

    <!-- ==================================
         BRANDING SMART EDUCATION
    ================================== -->

    <section class="welcome-section">

        <div class="brand">

            <img
                src="../../assets/images/logo.png"
                alt="Logo Smart Education"
            >

            <div class="brand-divider"></div>

            <div class="brand-text">

                <h1>
                    Smart<br>
                    Education
                </h1>

                <span>PAREPARE</span>

            </div>

        </div>

        <p class="brand-tagline">
            Bimbingan Belajar Berkualitas
            untuk Masa Depan yang Lebih Baik.
        </p>

        <div class="brand-line"></div>

    </section>


    <!-- ==================================
         FORM DAFTAR AKUN
    ================================== -->

    <section class="form-section">

        <div class="form-heading">

            <div class="eyebrow">
                CREATE YOUR ACCOUNT
            </div>

            <h2>Daftar Akun</h2>

            <p>
                Lengkapi data diri Anda untuk bergabung
                bersama Smart Education.
            </p>

        </div>

        <?php if ($pesan !== ''): ?>

            <div class="alert <?= register_escape($jenis_pesan); ?>">
                <?= register_escape($pesan); ?>
            </div>

        <?php endif; ?>


        <form method="POST" action="" autocomplete="off">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= register_escape($_SESSION['csrf_register']); ?>"
            >

            <div class="form-grid">

                <!-- NAMA LENGKAP -->

                <div class="form-group">

                    <label>
                        Nama Lengkap <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="nama_lengkap"
                        placeholder="Masukkan nama lengkap"
                        maxlength="100"
                        value="<?= register_escape($_POST['nama_lengkap'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- USERNAME -->

                <div class="form-group">

                    <label>
                        Username <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Masukkan username"
                        minlength="4"
                        maxlength="50"
                        value="<?= register_escape($_POST['username'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group full">

                    <label>
                        Email <span class="required">*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        placeholder="Masukkan alamat email"
                        maxlength="100"
                        value="<?= register_escape($_POST['email'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- JENIS KELAMIN -->

                <div class="form-group">

                    <label>
                        Jenis Kelamin <span class="required">*</span>
                    </label>

                    <select name="jenis_kelamin" required>

                        <option value="">Pilih jenis kelamin</option>

                        <option
                            value="Laki-laki"
                            <?= (($_POST['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'selected' : ''; ?>
                        >
                            Laki-laki
                        </option>

                        <option
                            value="Perempuan"
                            <?= (($_POST['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : ''; ?>
                        >
                            Perempuan
                        </option>

                    </select>

                </div>


                <!-- NOMOR TELEPON -->

                <div class="form-group">

                    <label>Nomor Telepon</label>

                    <input
                        type="tel"
                        name="no_telepon"
                        placeholder="Masukkan nomor telepon"
                        maxlength="20"
                        value="<?= register_escape($_POST['no_telepon'] ?? ''); ?>"
                    >

                </div>


                <!-- ALAMAT -->

                <div class="form-group full">

                    <label>Alamat</label>

                    <textarea
                        name="alamat"
                        placeholder="Masukkan alamat tempat tinggal"
                    ><?= register_escape($_POST['alamat'] ?? ''); ?></textarea>

                </div>


                <!-- PAKET BELAJAR -->

                <div class="form-group full">

                    <label>Paket Belajar</label>

                    <select name="id_paket">

                        <option value="">
                            Pilih paket belajar (opsional)
                        </option>

                        <?php while ($paket = mysqli_fetch_assoc($query_paket)): ?>

                            <option
                                value="<?= register_escape($paket['id_paket']); ?>"
                                <?= (($_POST['id_paket'] ?? '') == $paket['id_paket']) ? 'selected' : ''; ?>
                            >
                                <?= register_escape($paket['nama_paket']); ?>
                                - <?= register_escape($paket['jenjang']); ?>
                                (<?= register_rupiah($paket['harga']); ?>)
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label>
                        Password <span class="required">*</span>
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Buat password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword('password', this)"
                        >
                            Lihat
                        </button>

                    </div>

                    <span class="password-note">
                        Minimal 8 karakter.
                    </span>

                </div>


                <!-- KONFIRMASI PASSWORD -->

                <div class="form-group">

                    <label>
                        Konfirmasi Password <span class="required">*</span>
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            name="konfirmasi_password"
                            id="konfirmasi_password"
                            placeholder="Ulangi password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword('konfirmasi_password', this)"
                        >
                            Lihat
                        </button>

                    </div>

                </div>

            </div>


            <!-- TOMBOL DAFTAR -->

            <button type="submit" class="submit-btn">
                Daftar Sekarang <span>→</span>
            </button>

        </form>


        <!-- LINK LOGIN -->

        <div class="login-link">

            Sudah memiliki akun?

            <a href="login.php">
                Login sekarang
            </a>

        </div>


        <!-- FOOTER -->

        <div class="form-footer">
            Dengan mendaftar, Anda bergabung bersama
            Smart Education Parepare.
        </div>

    </section>

</div>


<!-- =====================================
     JAVASCRIPT LIHAT PASSWORD
===================================== -->

<script>

function togglePassword(id, button) {

    const input = document.getElementById(id);

    if (input.type === 'password') {

        input.type = 'text';
        button.textContent = 'Sembunyi';

    } else {

        input.type = 'password';
        button.textContent = 'Lihat';

    }

}

</script>

</body>
</html>
