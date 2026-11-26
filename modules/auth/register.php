
<?php
/**
 * REGISTER
 * SMART EDUCATION PAREPARE
 * Lokasi: modules/auth/register.php
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* CSRF */
if (empty($_SESSION['csrf_register'])) {
    $_SESSION['csrf_register'] = bin2hex(random_bytes(32));
}

$pesan = '';
$daftar_paket = [];

/* Mengambil paket belajar aktif */
try {
    $stmt = $pdo->query("
        SELECT id_paket, nama_paket, jenjang, harga
        FROM paket_belajar
        WHERE status = 'aktif'
        ORDER BY nama_paket ASC
    ");

    $daftar_paket = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $pesan = 'Data paket belajar gagal dimuat.';
}

/* Proses pendaftaran */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !isset($_SESSION['csrf_register']) ||
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_register'], $token)
    ) {
        $pesan = 'Permintaan tidak valid. Silakan muat ulang halaman.';

    } else {

        /* DATA AKUN */
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';

        /* DATA SISWA */
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $alamat_siswa = trim($_POST['alamat_siswa'] ?? '');
        $sekolah = trim($_POST['sekolah_asal'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $agama = trim($_POST['agama'] ?? '');
        $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
        $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $no_rumah = trim($_POST['no_rumah'] ?? '');

        /* DATA ORANG TUA */
        $nama_ayah = trim($_POST['nama_ayah'] ?? '');
        $nama_ibu = trim($_POST['nama_ibu'] ?? '');
        $nama_pemberi = trim($_POST['nama_pemberi_informasi'] ?? '');
        $alamat_orang_tua = trim($_POST['alamat_orang_tua'] ?? '');
        $no_hp_ayah = trim($_POST['no_hp_ayah'] ?? '');
        $no_hp_ibu = trim($_POST['no_hp_ibu'] ?? '');

        /* DATA PENDAFTARAN */
        $id_paket = filter_var(
            $_POST['id_paket'] ?? null,
            FILTER_VALIDATE_INT
        );

        $jenis_program = $_POST['jenis_program'] ?? '';
        $pilihan_jam = trim($_POST['pilihan_jam'] ?? '');
        $hari = $_POST['pilihan_hari'] ?? [];

        /* VALIDASI DATA WAJIB */
        $wajib = [
            $username,
            $email,
            $password,
            $konfirmasi,
            $nama,
            $alamat_siswa,
            $sekolah,
            $kelas,
            $agama,
            $tempat_lahir,
            $tanggal_lahir,
            $no_hp,
            $nama_ayah,
            $nama_ibu,
            $nama_pemberi,
            $alamat_orang_tua,
            $no_hp_ayah,
            $no_hp_ibu,
            $jenis_program,
            $pilihan_jam
        ];

        foreach ($wajib as $nilai) {
            if ($nilai === '') {
                $pesan = 'Mohon lengkapi seluruh data yang wajib diisi.';
                break;
            }
        }

        /* VALIDASI USERNAME */
        if (
            $pesan === '' &&
            !preg_match('/^[a-zA-Z0-9._-]{4,50}$/', $username)
        ) {
            $pesan = 'Username harus terdiri dari 4 sampai 50 karakter.';
        }

        /* VALIDASI EMAIL */
        if (
            $pesan === '' &&
            (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
            mb_strlen($email) > 100)
        ) {
            $pesan = 'Format email tidak valid.';
        }

        /* VALIDASI PASSWORD */
        if ($pesan === '' && strlen($password) < 8) {
            $pesan = 'Password minimal 8 karakter.';
        }

        if ($pesan === '' && $password !== $konfirmasi) {
            $pesan = 'Konfirmasi password tidak sesuai.';
        }

        /* VALIDASI PANJANG DATA */
        if (
            $pesan === '' &&
            (
                mb_strlen($nama) > 150 ||
                mb_strlen($sekolah) > 150 ||
                mb_strlen($kelas) > 30 ||
                mb_strlen($agama) > 30 ||
                mb_strlen($tempat_lahir) > 100 ||
                mb_strlen($no_hp) > 20 ||
                mb_strlen($no_rumah) > 20 ||
                mb_strlen($nama_ayah) > 150 ||
                mb_strlen($nama_ibu) > 150 ||
                mb_strlen($nama_pemberi) > 150 ||
                mb_strlen($no_hp_ayah) > 20 ||
                mb_strlen($no_hp_ibu) > 20
            )
        ) {
            $pesan = 'Salah satu data melebihi batas karakter.';
        }

        /* VALIDASI TANGGAL LAHIR */
        if ($pesan === '') {
            $tanggal = DateTime::createFromFormat('Y-m-d', $tanggal_lahir);

            if (
                !$tanggal ||
                $tanggal->format('Y-m-d') !== $tanggal_lahir ||
                $tanggal_lahir > date('Y-m-d')
            ) {
                $pesan = 'Tanggal lahir tidak valid.';
            }
        }

        /* VALIDASI JENIS PROGRAM */
        if (
            $pesan === '' &&
            !in_array($jenis_program, ['Reguler', 'Privat'], true)
        ) {
            $pesan = 'Jenis program tidak valid.';
        }

        /* VALIDASI HARI */
        $daftar_hari = [
            'Senin', 'Selasa', 'Rabu', 'Kamis',
            'Jumat', 'Sabtu', 'Minggu'
        ];

        if (!is_array($hari)) {
            $hari = [];
        }

        $hari = array_values(array_unique($hari));

        if (
            $pesan === '' &&
            (
                count($hari) < 1 ||
                count(array_diff($hari, $daftar_hari)) > 0
            )
        ) {
            $pesan = 'Pilih minimal satu hari belajar.';
        }

        /* VALIDASI JAM */
        if (
            $pesan === '' &&
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $pilihan_jam)
        ) {
            $pesan = 'Pilihan jam belajar tidak valid.';
        }

        /* VALIDASI PAKET */
        $paket_terpilih = null;

        if ($pesan === '') {
            foreach ($daftar_paket as $paket) {
                if ((int)$paket['id_paket'] === (int)$id_paket) {
                    $paket_terpilih = $paket;
                    break;
                }
            }

            if (!$paket_terpilih) {
                $pesan = 'Paket belajar tidak tersedia.';
            }
        }

        /* CEK AKUN DUPLIKAT */
        if ($pesan === '') {
            $cek = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = :username
                   OR email = :email
                LIMIT 1
            ");

            $cek->execute([
                ':username' => $username,
                ':email' => $email
            ]);

            if ($cek->fetch(PDO::FETCH_ASSOC)) {
                $pesan = 'Username atau email sudah digunakan.';
            }
        }

        /* PENYIMPANAN DATA */
        if ($pesan === '') {

            try {
                $pdo->beginTransaction();

                /* 1. SIMPAN DATA SISWA */
                $stmt = $pdo->prepare("
                    INSERT INTO siswa (
                        nama_lengkap,
                        alamat_rumah,
                        sekolah_asal,
                        kelas,
                        agama,
                        tempat_lahir,
                        tanggal_lahir,
                        no_hp,
                        no_rumah,
                        status_siswa
                    ) VALUES (
                        :nama,
                        :alamat,
                        :sekolah,
                        :kelas,
                        :agama,
                        :tempat,
                        :tanggal,
                        :hp,
                        :rumah,
                        'Aktif'
                    )
                ");

                $stmt->execute([
                    ':nama' => $nama,
                    ':alamat' => $alamat_siswa,
                    ':sekolah' => $sekolah,
                    ':kelas' => $kelas,
                    ':agama' => $agama,
                    ':tempat' => $tempat_lahir,
                    ':tanggal' => $tanggal_lahir,
                    ':hp' => $no_hp,
                    ':rumah' => $no_rumah !== '' ? $no_rumah : null
                ]);

                $id_siswa = (int)$pdo->lastInsertId();

                /* 2. SIMPAN DATA ORANG TUA */
                $stmt = $pdo->prepare("
                    INSERT INTO orang_tua (
                        id_siswa,
                        nama_ayah,
                        nama_ibu,
                        nama_pemberi_informasi,
                        alamat_rumah,
                        no_hp_ayah,
                        no_hp_ibu
                    ) VALUES (
                        :id_siswa,
                        :ayah,
                        :ibu,
                        :pemberi,
                        :alamat,
                        :hp_ayah,
                        :hp_ibu
                    )
                ");

                $stmt->execute([
                    ':id_siswa' => $id_siswa,
                    ':ayah' => $nama_ayah,
                    ':ibu' => $nama_ibu,
                    ':pemberi' => $nama_pemberi,
                    ':alamat' => $alamat_orang_tua,
                    ':hp_ayah' => $no_hp_ayah,
                    ':hp_ibu' => $no_hp_ibu
                ]);

                /* 3. MEMBUAT NOMOR REGISTRASI */
                do {
                    $nomor_registrasi =
                        'REG' .
                        date('Ymd') .
                        strtoupper(bin2hex(random_bytes(3)));

                    $cek_reg = $pdo->prepare("
                        SELECT id_pendaftaran
                        FROM pendaftaran
                        WHERE nomor_registrasi = ?
                        LIMIT 1
                    ");

                    $cek_reg->execute([$nomor_registrasi]);

                } while ($cek_reg->fetch());

                /* 4. SIMPAN PENDAFTARAN */
                $pilihan_hari = implode(', ', $hari);

                $stmt = $pdo->prepare("
                    INSERT INTO pendaftaran (
                        nomor_registrasi,
                        id_siswa,
                        id_paket,
                        tanggal_pendaftaran,
                        jenis_program,
                        pilihan_hari,
                        pilihan_jam,
                        biaya_pendaftaran,
                        status_pendaftaran
                    ) VALUES (
                        :nomor,
                        :siswa,
                        :paket,
                        :tanggal,
                        :program,
                        :hari,
                        :jam,
                        220000,
                        'Menunggu'
                    )
                ");

                $stmt->execute([
                    ':nomor' => $nomor_registrasi,
                    ':siswa' => $id_siswa,
                    ':paket' => $id_paket,
                    ':tanggal' => date('Y-m-d'),
                    ':program' => $jenis_program,
                    ':hari' => $pilihan_hari,
                    ':jam' => $pilihan_jam
                ]);

                /* 5. SIMPAN AKUN USERS */
                $hash_password = password_hash(
                    $password,
                    PASSWORD_BCRYPT
                );

                if ($hash_password === false) {
                    throw new Exception('Gagal membuat password.');
                }

                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        username,
                        password,
                        nama_lengkap,
                        email,
                        role,
                        id_siswa
                    ) VALUES (
                        :username,
                        :password,
                        :nama,
                        :email,
                        'siswa_orang_tua',
                        :id_siswa
                    )
                ");

                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hash_password,
                    ':nama' => $nama,
                    ':email' => $email,
                    ':id_siswa' => $id_siswa
                ]);

                /* SELESAI */
                $pdo->commit();

                unset($_SESSION['csrf_register']);

                header('Location: ' . base_url('modules/auth/login.php?daftar=berhasil'));
                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Register Smart Education: ' . $e->getMessage());

                $pesan = 'Pendaftaran gagal disimpan. Silakan periksa kembali data Anda.';
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

    <title>Daftar Akun - Smart Education</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 35px 15px;
            font-family: 'Poppins', sans-serif;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    rgba(15, 35, 27, 0.48),
                    rgba(15, 35, 27, 0.48)
                ),
                url("../../assets/images/background-login.jpg")
                center center / cover no-repeat fixed;
        }

        .register-container {
            width: 100%;
            max-width: 850px;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 15px 45px rgba(0, 0, 0, .22);
        }

        .register-header {
            background: #98EDE1;
            padding: 25px 35px;
            text-align: center;
        }

        .register-header h2 {
            margin: 0;
            color: #164c48;
            font-size: 27px;
            font-weight: 800;
        }

        .register-header p {
            margin: 7px 0 0;
            color: #245e59;
            font-size: 14px;
        }

        .register-body {
            padding: 30px 38px;
        }

        .section-title {
            color: #16877c;
            font-size: 17px;
            font-weight: 700;
            margin: 25px 0 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dcefeb;
        }

        .section-title:first-child {
            margin-top: 0;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #315b56;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            min-height: 48px;
            border: 1px solid #d5e8e4;
            border-radius: 12px;
            padding: 11px 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #269b8d;
            box-shadow: 0 0 0 3px rgba(38, 155, 141, .12);
        }

        textarea.form-control {
            min-height: 90px;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 75px;
        }

        .toggle-password {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #16877c;
            font-size: 12px;
            font-weight: 600;
        }

        .hari-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .hari-item {
            border: 1px solid #d5e8e4;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 13px;
            color: #315b56;
            cursor: pointer;
        }

        .hari-item input {
            margin-right: 5px;
            accent-color: #258c80;
        }

        .btn-register {
            width: 100%;
            min-height: 50px;
            border: none;
            border-radius: 12px;
            background: #258c80;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 700;
            transition: .2s;
        }

        .btn-register:hover {
            background: #176f65;
            color: #fff;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #687f7b;
        }

        .login-link a {
            color: #16877c;
            font-weight: 700;
            text-decoration: none;
        }

        .required {
            color: #dc3545;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px 10px;
            }

            .register-header {
                padding: 22px 15px;
            }

            .register-body {
                padding: 22px 18px;
            }

            .register-header h2 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

<div class="register-container">

    <div class="register-header">
        <h2>Daftar Akun</h2>
        <p>Smart Education Parepare</p>
    </div>

    <div class="register-body">

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST"
              action=""
              autocomplete="off">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_register'], ENT_QUOTES, 'UTF-8') ?>">

            <!-- INFORMASI AKUN -->
            <h5 class="section-title">
                <i class="bi bi-person-lock me-2"></i>
                Informasi Akun
            </h5>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">
                        Username <span class="required">*</span>
                    </label>
                    <input type="text"
                           name="username"
                           class="form-control"
                           placeholder=""
                           autocomplete="off"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Email <span class="required">*</span>
                    </label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           placeholder=""
                           autocomplete="off"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Password <span class="required">*</span>
                    </label>

                    <div class="password-wrapper">
                        <input type="password"
                               name="password"
                               id="password"
                               class="form-control"
                               placeholder=""
                               autocomplete="new-password"
                               required>

                        <button type="button"
                                class="toggle-password"
                                onclick="togglePassword('password', this)">
                            Lihat
                        </button>
                    </div>

                    <small class="text-muted">Minimal 8 karakter.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Konfirmasi Password <span class="required">*</span>
                    </label>

                    <div class="password-wrapper">
                        <input type="password"
                               name="konfirmasi_password"
                               id="konfirmasi_password"
                               class="form-control"
                               placeholder=""
                               autocomplete="new-password"
                               required>

                        <button type="button"
                                class="toggle-password"
                                onclick="togglePassword('konfirmasi_password', this)">
                            Lihat
                        </button>
                    </div>
                </div>

            </div>

            <!-- DATA SISWA -->
            <h5 class="section-title">
                <i class="bi bi-mortarboard me-2"></i>
                Data Siswa
            </h5>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">
                        Nama Lengkap <span class="required">*</span>
                    </label>
                    <input type="text"
                           name="nama_lengkap"
                           class="form-control"
                           placeholder=""
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Sekolah Asal <span class="required">*</span>
                    </label>
                    <input type="text"
                           name="sekolah_asal"
                           class="form-control"
                           placeholder=""
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Kelas <span class="required">*</span>
                    </label>
                    <input type="text"
                           name="kelas"
                           class="form-control"
                           placeholder=""
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Agama <span class="required">*</span>
                    </label>
                    <select name="agama" class="form-select" required>
                        <option value="">Pilih Agama</option>
                        <option value="Islam">Islam</option>
                        <option value="Kristen">Kristen</option>
                        <option value="Katolik">Katolik</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Buddha">Buddha</option>
                        <option value="Konghucu">Konghucu</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Tempat Lahir <span class="required">*</span>
                    </label>
                    <input type="text"
                           name="tempat_lahir"
                           class="form-control"
                           placeholder=""
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Tanggal Lahir <span class="required">*</span>
                    </label>
                    <input type="date"
                           name="tanggal_lahir"
                           class="form-control"
                           max="<?= date('Y-m-d') ?>"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Nomor HP <span class="required">*</span>
                    </label>
                    <input type="tel"
                           name="no_hp"
                           class="form-control"
                           placeholder=""
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nomor Rumah</label>
                    <input type="text"
                           name="no_rumah"
                           class="form-control"
                           placeholder="">
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Alamat Rumah <span class="required">*</span>
                    </label>
                    <textarea name="alamat_siswa"
                              class="form-control"
                              placeholder=""
                              required></textarea>
                </div>

            </div>

            <!-- DATA ORANG TUA -->
            <h5 class="section-title">
                <i class="bi bi-people me-2"></i>
                Data Orang Tua / Wali
            </h5>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">
                        Nama Ayah <span class="required">*</span>
                    </label>
                    <input type="text"
                           name="nama_ayah"
                           class="form-control"
                           placeholder=""
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Nama Ibu <span class="required">*</span>
                    </label>
                    <input type="text"
                           name="nama_ibu"
                           class="form-control"
                           placeholder=""
                           required>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Nama Pemberi Informasi <span class="required">*</span>
                    </label>
                    <input type="text"
                           name="nama_pemberi_informasi"
                           class="form-control"
                           placeholder=""
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Nomor HP Ayah <span class="required">*</span>
                    </label>
                    <input type="tel"
                           name="no_hp_ayah"
                           class="form-control"
                           placeholder=""
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Nomor HP Ibu <span class="required">*</span>
                    </label>
                    <input type="tel"
                           name="no_hp_ibu"
                           class="form-control"
                           placeholder=""
                           required>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Alamat Orang Tua <span class="required">*</span>
                    </label>
                    <textarea name="alamat_orang_tua"
                              class="form-control"
                              placeholder=""
                              required></textarea>
                </div>

            </div>

            <!-- INFORMASI PENDAFTARAN -->
            <h5 class="section-title">
                <i class="bi bi-journal-check me-2"></i>
                Informasi Pendaftaran
            </h5>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">
                        Paket Belajar <span class="required">*</span>
                    </label>

                    <select name="id_paket"
                            class="form-select"
                            required>

                        <option value="">Pilih Paket Belajar</option>

                        <?php foreach ($daftar_paket as $paket): ?>
                            <option value="<?= (int)$paket['id_paket'] ?>">
                                <?= htmlspecialchars($paket['nama_paket'], ENT_QUOTES, 'UTF-8') ?>
                                - <?= htmlspecialchars($paket['jenjang'], ENT_QUOTES, 'UTF-8') ?>
                                (Rp <?= number_format((float)$paket['harga'], 0, ',', '.') ?>)
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Jenis Program <span class="required">*</span>
                    </label>

                    <select name="jenis_program"
                            class="form-select"
                            required>
                        <option value="">Pilih Program</option>
                        <option value="Reguler">Reguler</option>
                        <option value="Privat">Privat</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Pilihan Hari <span class="required">*</span>
                    </label>

                    <div class="hari-wrapper">
                        <?php
                        $hari_list = [
                            'Senin', 'Selasa', 'Rabu',
                            'Kamis', 'Jumat', 'Sabtu', 'Minggu'
                        ];
                        ?>

                        <?php foreach ($hari_list as $h): ?>
                            <label class="hari-item">
                                <input type="checkbox"
                                       name="pilihan_hari[]"
                                       value="<?= $h ?>">
                                <?= $h ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Pilihan Jam <span class="required">*</span>
                    </label>

                    <select name="pilihan_jam"
                            class="form-select"
                            required>
                        <option value="">Pilih Jam Belajar</option>
                        <option value="08:00">08.00</option>
                        <option value="10:00">10.00</option>
                        <option value="13:00">13.00</option>
                        <option value="15:00">15.00</option>
                        <option value="16:00">16.00</option>
                    </select>
                </div>

            </div>

            <div class="mt-4">
                <button type="submit" class="btn-register">
                    <i class="bi bi-person-plus me-2"></i>
                    Daftar Sekarang
                </button>
            </div>

            <div class="login-link">
                Sudah memiliki akun?
                <a href="<?= base_url('modules/auth/login.php') ?>">
                    Masuk di sini
                </a>
            </div>

        </form>
    </div>
</div>

<script>
function togglePassword(id, button) {
    const input = document.getElementById(id);

    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = 'Sembunyikan';
    } else {
        input.type = 'password';
        button.textContent = 'Lihat';
    }
}
</script>

</body>
</html>
