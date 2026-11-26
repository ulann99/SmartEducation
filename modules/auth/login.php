
<?php
/**
 * Login Page
 * SMART EDUCATION PAREPARE
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../config/koneksi.php";

$error = "";

// =====================================
// PROSES LOGIN
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Username dan password wajib diisi.";

    } else {

        $query = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user && password_verify($password, $user["password"])) {

                session_regenerate_id(true);

                // =====================================
                // SIMPAN DATA SESSION
                // =====================================

                $_SESSION["login"] = true;
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["nama_lengkap"] = $user["nama_lengkap"];
                $_SESSION["email"] = $user["email"] ?? "";
                $_SESSION["role"] = $user["role"];
                $_SESSION["id_siswa"] = $user["id_siswa"] ?? null;
                $_SESSION["foto"] = $user["foto"] ?? "default.png";

                $role = strtolower(trim($user["role"]));

                mysqli_stmt_close($stmt);

                // =====================================
                // REDIRECT BERDASARKAN ROLE
                // =====================================

                // SUPER ADMIN
                if ($role === "superadmin") {

                    header("Location: ../../dashboard_superadmin.php");
                    exit;

                }

                // ADMIN
                elseif ($role === "admin") {

                    header("Location: ../../dashboard.php");
                    exit;

                }

                // SISWA / ORANG TUA
                elseif (
                    $role === "siswa" ||
                    $role === "siswa_orang_tua"
                ) {

                    // Pastikan akun memiliki ID siswa
                    if (empty($_SESSION["id_siswa"])) {

                        session_unset();
                        session_destroy();

                        session_start();
                        $error = "Akun siswa belum terhubung dengan data siswa.";

                    } else {

                        header("Location: ../../portal_siswa/dashboard_siswa.php");
                        exit;

                    }

                }

                // ROLE TIDAK DIKENALI
                else {

                    session_unset();
                    session_destroy();

                    session_start();
                    $error = "Role pengguna tidak dikenali.";

                }

            } else {

                $error = "Username atau password salah.";
                mysqli_stmt_close($stmt);

            }

        } else {

            $error = "Terjadi kesalahan pada sistem.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Smart Education Parepare</title>

    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- BOOTSTRAP ICONS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        /* =====================================
           RESET DAN KONFIGURASI
        ===================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #98EDE1;
            --secondary: #E8FDFF;
            --dark: #173B38;
            --accent: #287E72;
            --muted: #718582;
        }

        body {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 35px 20px;

            background:
                linear-gradient(
                    rgba(15, 35, 27, .42),
                    rgba(15, 35, 27, .42)
                ),
                url("../../assets/images/background-login.jpg")
                center center / cover no-repeat fixed;
        }

        /* =====================================
           PEMBUNGKUS UTAMA
        ===================================== */

        .login-container {
            width: 100%;
            max-width: 880px;

            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* =====================================
           BAGIAN SAMBUTAN
        ===================================== */

        .welcome-section {
            width: 100%;
            text-align: center;
            color: #FFFFFF;

            margin-bottom: 25px;

            animation: fadeDown .7s ease;
            text-shadow: 0 2px 8px rgba(0, 0, 0, .3);
        }

        .welcome-eyebrow {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 4px;
            margin-bottom: 12px;
        }

        .welcome-section h1 {
            font-size: clamp(32px, 5vw, 54px);
            line-height: 1.2;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .welcome-section p {
            max-width: 650px;
            margin: 0 auto;

            font-size: 16px;
            line-height: 1.8;
            font-weight: 400;
        }

        /* =====================================
           KARTU LOGIN
        ===================================== */

        .form-section {
            width: 100%;
            max-width: 880px;

            padding: 35px 68px 38px;

            background: rgba(255, 255, 255, .98);
            border-radius: 24px;

            box-shadow: 0 18px 55px rgba(0, 0, 0, .18);

            animation: fadeUp .7s ease;
        }

        /* =====================================
           LOGO DAN NAMA BIMBEL
        ===================================== */

        .logo-form {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;

            margin-bottom: 30px;
        }

        .logo-form img {
            width: 62px;
            height: 62px;
            object-fit: contain;
        }

        .logo-form h2 {
            color: var(--dark);
            font-size: 23px;
            font-weight: 800;
            margin-bottom: 3px;
        }

        .logo-form p {
            color: var(--muted);
            font-size: 12px;
        }

        /* =====================================
           FORM INPUT
        ===================================== */

        .form-group {
            margin-bottom: 17px;
        }

        .input-wrapper {
            width: 100%;
            height: 61px;

            display: flex;
            align-items: center;
            gap: 14px;

            padding: 0 18px;

            border: 1px solid #D6E4E0;
            border-radius: 12px;

            background: #E8F1FF;
            transition: .2s ease;
        }

        .input-wrapper:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(40, 126, 114, .10);
        }

        .input-icon {
            color: #82958F;
            font-size: 17px;
        }

        .input-wrapper input {
            width: 100%;
            height: 100%;

            border: none;
            outline: none;

            background: transparent;

            color: #172B2A;
            font-family: inherit;
            font-size: 14px;
        }

        .input-wrapper input::placeholder {
            color: #9AA9A5;
        }

        /* TOMBOL LIHAT PASSWORD */

        .toggle-password {
            border: none;
            background: transparent;

            color: #82958F;
            font-size: 18px;

            cursor: pointer;
        }

        /* =====================================
           INGAT SAYA DAN LUPA PASSWORD
        ===================================== */

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            margin: 20px 0 24px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;

            color: var(--muted);
            font-size: 12px;
            cursor: pointer;
        }

        .remember input {
            width: 15px;
            height: 15px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .form-options a {
            color: #536D65;
            font-size: 12px;
            text-decoration: none;
        }

        .form-options a:hover {
            color: var(--accent);
        }

        /* =====================================
           TOMBOL LOGIN
        ===================================== */

        .login-button {
            width: 100%;
            height: 60px;

            border: none;
            border-radius: 11px;

            background: #C7EFE2;
            color: #245E4E;

            font-family: inherit;
            font-size: 15px;
            font-weight: 700;

            cursor: pointer;
            transition: .25s ease;
        }

        .login-button:hover {
            background: #98EDE1;
            transform: translateY(-2px);

            box-shadow: 0 7px 18px rgba(40, 126, 114, .13);
        }

        /* =====================================
           PESAN ERROR
        ===================================== */

        .error {
            padding: 12px 15px;
            margin-bottom: 18px;

            border-radius: 9px;

            background: #FFF0F0;
            border: 1px solid #FFD4D4;

            color: #B42318;
            font-size: 12px;
        }

        /* =====================================
           DAFTAR AKUN
        ===================================== */

        .register-text {
            margin-top: 26px;

            text-align: center;

            color: #82918B;
            font-size: 12px;
        }

        .register-text a {
            color: #287E55;
            font-weight: 700;
            text-decoration: none;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        /* =====================================
           ANIMASI
        ===================================== */

        @keyframes fadeDown {

            from {
                opacity: 0;
                transform: translateY(-15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }

        @keyframes fadeUp {

            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }

        /* =====================================
           RESPONSIVE TABLET
        ===================================== */

        @media (max-width: 768px) {

            body {
                padding: 25px 15px;
                background-attachment: scroll;
            }

            .login-container {
                max-width: 560px;
            }

            .welcome-section {
                margin-bottom: 22px;
            }

            .welcome-eyebrow {
                font-size: 11px;
                letter-spacing: 3px;
            }

            .welcome-section h1 {
                font-size: 34px;
            }

            .welcome-section p {
                font-size: 13px;
            }

            .form-section {
                padding: 30px 35px;
                border-radius: 20px;
            }

            .logo-form h2 {
                font-size: 20px;
            }

        }

        /* =====================================
           RESPONSIVE HP
        ===================================== */

        @media (max-width: 480px) {

            body {
                padding: 20px 12px;
            }

            .welcome-section h1 {
                font-size: 28px;
            }

            .welcome-section p {
                font-size: 12px;
            }

            .form-section {
                padding: 27px 20px 30px;
            }

            .logo-form {
                gap: 10px;
                margin-bottom: 25px;
            }

            .logo-form img {
                width: 50px;
                height: 50px;
            }

            .logo-form h2 {
                font-size: 17px;
            }

            .logo-form p {
                font-size: 10px;
            }

            .input-wrapper {
                height: 54px;
            }

            .login-button {
                height: 54px;
            }

            .form-options {
                gap: 8px;
            }

        }

    </style>

</head>

<body>

<div class="login-container">

    <!-- =====================================
         SAMBUTAN DI BAGIAN ATAS
    ====================================== -->

    <section class="welcome-section">

        <div class="welcome-eyebrow">
            YOUR FUTURE STARTS HERE
        </div>

        <h1>Selamat Datang!</h1>

        <p>
            Silakan masuk untuk mengakses akun Smart Education
            Anda dan melanjutkan aktivitas pembelajaran.
        </p>

    </section>

    <!-- =====================================
         KARTU FORM LOGIN
    ====================================== -->

    <section class="form-section">

        <!-- LOGO -->
        <div class="logo-form">

            <img
                src="../../assets/images/logo.png"
                alt="Logo Smart Education"
            >

            <div>
                <h2>Smart Education</h2>
                <p>Bimbingan Belajar Parepare</p>
            </div>

        </div>

        <!-- PESAN ERROR -->
        <?php if ($error !== ""): ?>

            <div class="error">
                <i class="bi bi-exclamation-circle me-1"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>

        <?php endif; ?>

        <!-- FORM LOGIN -->
        <form method="POST" action="" autocomplete="off">

            <!-- USERNAME -->
            <div class="form-group">

                <div class="input-wrapper">

                    <i class="bi bi-person input-icon"></i>

                    <input
                        type="text"
                        name="username"
                        placeholder="Username"
                        autocomplete="off"
                        autofocus
                        value="<?= htmlspecialchars($_POST["username"] ?? "", ENT_QUOTES, 'UTF-8'); ?>"
                    >

                </div>

            </div>

            <!-- PASSWORD -->
            <div class="form-group">

                <div class="input-wrapper">

                    <i class="bi bi-lock input-icon"></i>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Password"
                        autocomplete="new-password"
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword"
                        aria-label="Tampilkan password"
                    >
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>

                </div>

            </div>

            <!-- INGAT SAYA DAN LUPA PASSWORD -->
            <div class="form-options">

                <label class="remember">

                    <input
                        type="checkbox"
                        name="remember"
                    >

                    <span>Ingat Saya</span>

                </label>

                <a href="#" onclick="return false;">
                    Lupa Password?
                </a>

            </div>

            <!-- TOMBOL LOGIN -->
            <button
                type="submit"
                class="login-button"
            >
                Login
            </button>

        </form>

        <!-- PENDAFTARAN -->
        <div class="register-text">

            Belum memiliki akun?

            <a href="register.php">
                Daftar sekarang
            </a>

        </div>

    </section>

</div>

<!-- =====================================
     JAVASCRIPT LIHAT PASSWORD
====================================== -->

<script>

const passwordInput = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");
const eyeIcon = document.getElementById("eyeIcon");

togglePassword.addEventListener("click", function () {

    if (passwordInput.type === "password") {

        passwordInput.type = "text";

        eyeIcon.classList.remove("bi-eye");
        eyeIcon.classList.add("bi-eye-slash");

        togglePassword.setAttribute(
            "aria-label",
            "Sembunyikan password"
        );

    } else {

        passwordInput.type = "password";

        eyeIcon.classList.remove("bi-eye-slash");
        eyeIcon.classList.add("bi-eye");

        togglePassword.setAttribute(
            "aria-label",
            "Tampilkan password"
        );

    }

});

</script>

</body>
</html>
