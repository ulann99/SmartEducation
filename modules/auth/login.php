
<?php
session_start();
require_once "../../config/koneksi.php";

$error = "";

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

                $_SESSION["login"] = true;
                $_SESSION["id_user"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["nama_lengkap"] = $user["nama_lengkap"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["id_siswa"] = $user["id_siswa"];

                header("Location: ../../dashboard.php");
                exit;
            } else {
                $error = "Username atau password salah.";
            }

            mysqli_stmt_close($stmt);
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

<title>Login - Smart Education Parepare</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    font-family: 'Poppins', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 35px;
    background:
        linear-gradient(rgba(15, 35, 27, 0.30), rgba(15, 35, 27, 0.30)),
        url("../../assets/images/background-login.jpg") center center / cover no-repeat fixed;
}

/* KONTEN UTAMA */
.login-container {
    width: 100%;
    max-width: 1120px;
    min-height: 620px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: center;
    gap: 35px;
}

/* BAGIAN KIRI */
.welcome-section {
    padding: 45px;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 500px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.brand {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 100px;
}

.brand img {
    width: 65px;
    height: 65px;
    padding: 5px;
    border-radius: 13px;
    background: white;
    object-fit: contain;
}

.brand h3 {
    font-size: 16px;
    font-weight: 700;
}

.brand p {
    font-size: 10px;
    letter-spacing: 2px;
    margin-top: 4px;
}

.welcome-text .eyebrow {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 2px;
    margin-bottom: 15px;
}

.welcome-text h1 {
    font-size: 36px;
    line-height: 1.35;
    font-weight: 700;
    margin-bottom: 15px;
}

.welcome-text p {
    font-size: 13px;
    line-height: 1.9;
    max-width: 390px;
}

/* BAGIAN KANAN - FORM */
.form-section {
    width: 100%;
    max-width: 460px;
    justify-self: center;
    background: #ffffff;
    padding: 42px;
    border-radius: 17px;
    box-shadow: 0 15px 45px rgba(0,0,0,0.15);
}

.logo-form {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    margin-bottom: 35px;
}

.logo-form img {
    width: 48px;
    height: 48px;
    object-fit: contain;
}

.logo-form h2 {
    font-size: 17px;
    font-weight: 700;
    color: #172b23;
}

.logo-form p {
    font-size: 9px;
    color: #718078;
    margin-top: 3px;
}

.form-group {
    margin-bottom: 18px;
}

.input-wrapper {
    position: relative;
}

.input-wrapper input {
    width: 100%;
    height: 49px;
    padding: 0 42px;
    border: 1px solid #d8e2dc;
    border-radius: 9px;
    outline: none;
    font-family: inherit;
    font-size: 11px;
    color: #263c32;
    background: #fbfdfc;
    transition: 0.2s;
}

.input-wrapper input:focus {
    border-color: #277653;
    box-shadow: 0 0 0 3px rgba(39,118,83,0.08);
}

.input-wrapper input::placeholder {
    color: #a5b0a9;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #819187;
    font-size: 14px;
}

.toggle-password {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    cursor: pointer;
    color: #718078;
    font-size: 16px;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 13px 0 22px;
    font-size: 10px;
    color: #69776f;
}

.remember {
    display: flex;
    align-items: center;
    gap: 6px;
}

.remember input {
    accent-color: #247653;
}

.form-options a {
    color: #52675b;
    text-decoration: none;
}

.login-button {
    width: 100%;
    height: 49px;
    background: #c9eddf;
    color: #245b43;
    border: none;
    border-radius: 8px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.login-button:hover {
    background: #a9dfc9;
}

.error {
    background: #fff0f0;
    color: #b42318;
    padding: 11px;
    border-radius: 8px;
    font-size: 11px;
    margin-bottom: 18px;
    border: 1px solid #ffd6d6;
}

.register-text {
    text-align: center;
    margin-top: 22px;
    font-size: 10px;
    color: #849087;
}

.register-text a {
    color: #26734f;
    font-weight: 600;
    text-decoration: none;
}

.register-text a:hover {
    text-decoration: underline;
}

@media (max-width: 800px) {
    body {
        padding: 20px;
        background-attachment: scroll;
    }

    .login-container {
        grid-template-columns: 1fr;
        gap: 10px;
        max-width: 470px;
    }

    .welcome-section {
        min-height: auto;
        padding: 25px 15px;
    }

    .brand {
        margin-bottom: 35px;
    }

    .welcome-text h1 {
        font-size: 27px;
    }

    .form-section {
        padding: 32px 25px;
    }
}

@media (max-width: 480px) {
    .welcome-section {
        padding: 15px 5px;
    }

    .welcome-text h1 {
        font-size: 23px;
    }

    .welcome-text p {
        font-size: 11px;
    }

    .form-section {
        padding: 28px 20px;
    }
}
</style>
</head>

<body>

<div class="login-container">

    <!-- BAGIAN KIRI -->
    <section class="welcome-section">

        <div class="brand">
            <img src="../../assets/images/logo.png" alt="Logo Smart Education">

            <div>
                <h3>SMART EDUCATION</h3>
                <p>PAREPARE</p>
            </div>
        </div>

        <div class="welcome-text">
            <div class="eyebrow">YOUR FUTURE STARTS HERE</div>

            <h1>Selamat Datang!</h1>

            <p>
                Silakan masuk untuk mengakses akun Smart Education Anda
                dan melanjutkan aktivitas pembelajaran.
            </p>
        </div>

    </section>


    <!-- BAGIAN KANAN -->
    <section class="form-section">

        <div class="logo-form">
            <img src="../../assets/images/logo.png" alt="Logo">

            <div>
                <h2>Smart Education</h2>
                <p>Bimbingan Belajar Parepare</p>
            </div>
        </div>

        <?php if ($error !== ""): ?>
            <div class="error">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <div class="input-wrapper">
                    <span class="input-icon">♙</span>

                    <input
                        type="text"
                        name="username"
                        placeholder="Username"
                        autocomplete="username"
                        required
                        value="<?= htmlspecialchars($_POST["username"] ?? ""); ?>"
                    >
                </div>
            </div>

            <div class="form-group">
                <div class="input-wrapper">
                    <span class="input-icon">♙</span>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword"
                        aria-label="Tampilkan password"
                    >◎</button>
                </div>
            </div>

            <div class="form-options">
                <label class="remember">
                    <input type="checkbox" name="remember">
                    Ingat Saya
                </label>

                <a href="#" onclick="return false;">Lupa Password?</a>
            </div>

            <button type="submit" class="login-button">
                Login
            </button>

        </form>

        <div class="register-text">
            Belum memiliki akun?
            <a href="register.php">Daftar sekarang</a>
        </div>

    </section>

</div>

<script>
const passwordInput = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");

togglePassword.addEventListener("click", function () {
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        togglePassword.textContent = "◉";
        togglePassword.setAttribute("aria-label", "Sembunyikan password");
    } else {
        passwordInput.type = "password";
        togglePassword.textContent = "◎";
        togglePassword.setAttribute("aria-label", "Tampilkan password");
    }
});
</script>

</body>
</html>
