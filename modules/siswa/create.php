<?php
/**
 * Create Siswa Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// ========================================
// PASTIKAN REQUEST POST
// ========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

// ========================================
// AMBIL DATA FORM
// ========================================

$nis = trim($_POST['nis'] ?? '');
$nama_siswa = trim($_POST['nama_siswa'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
$id_paket = !empty($_POST['id_paket'])
    ? (int) $_POST['id_paket']
    : null;

$no_telepon = trim($_POST['no_telepon'] ?? '');
$email = trim($_POST['email'] ?? '');
$tanggal_daftar = trim($_POST['tanggal_daftar'] ?? date('Y-m-d'));
$status_siswa = trim($_POST['status_siswa'] ?? 'Aktif');
$alamat = trim($_POST['alamat'] ?? '');

$buat_akun = isset($_POST['buat_akun']) &&
             $_POST['buat_akun'] === '1';

// ========================================
// VALIDASI DATA
// ========================================

if ($nis === '' || $nama_siswa === '') {
    set_flash('danger', 'NIS dan Nama Siswa wajib diisi!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

if (strlen($nis) > 20) {
    set_flash('danger', 'NIS maksimal 20 karakter!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

if (strlen($nama_siswa) > 100) {
    set_flash('danger', 'Nama siswa maksimal 100 karakter!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

if (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'], true)) {
    set_flash('danger', 'Jenis kelamin tidak valid!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

if (!in_array($status_siswa, ['Aktif', 'Alumni', 'Non-Aktif'], true)) {
    set_flash('danger', 'Status siswa tidak valid!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Format email tidak valid!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

if (strlen($email) > 100 || strlen($no_telepon) > 20) {
    set_flash('danger', 'Email atau nomor telepon melebihi batas karakter!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

$date = DateTime::createFromFormat('Y-m-d', $tanggal_daftar);

if (!$date || $date->format('Y-m-d') !== $tanggal_daftar) {
    set_flash('danger', 'Tanggal pendaftaran tidak valid!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

// ========================================
// PROSES PENYIMPANAN
// ========================================

try {

    // Mulai transaksi database
    $pdo->beginTransaction();

    // ------------------------------------
    // 1. CEK NIS
    // ------------------------------------

    $stmt_check = $pdo->prepare("
        SELECT id_siswa
        FROM siswa
        WHERE nis = :nis
        LIMIT 1
    ");

    $stmt_check->execute([
        ':nis' => $nis
    ]);

    if ($stmt_check->fetch()) {
        throw new Exception('NIS "' . $nis . '" sudah terdaftar!');
    }

    // ------------------------------------
    // 2. CEK PAKET BELAJAR
    // ------------------------------------

    if ($id_paket !== null) {

        $stmt_paket = $pdo->prepare("
            SELECT id_paket
            FROM paket_belajar
            WHERE id_paket = :id_paket
            AND status = 'Aktif'
            LIMIT 1
        ");

        $stmt_paket->execute([
            ':id_paket' => $id_paket
        ]);

        if (!$stmt_paket->fetch()) {
            throw new Exception('Paket belajar tidak tersedia atau sudah tidak aktif!');
        }
    }

    // ------------------------------------
    // 3. CEK EMAIL
    // ------------------------------------

    if ($email !== '') {

        $stmt_email = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $stmt_email->execute([
            ':email' => $email
        ]);

        if ($stmt_email->fetch()) {
            throw new Exception('Email sudah digunakan oleh akun lain!');
        }
    }

    // ------------------------------------
    // 4. CEK USERNAME OTOMATIS
    // ------------------------------------

    if ($buat_akun) {

        $stmt_username = $pdo->prepare("
            SELECT id
            FROM users
            WHERE username = :username
            LIMIT 1
        ");

        $stmt_username->execute([
            ':username' => $nis
        ]);

        if ($stmt_username->fetch()) {
            throw new Exception('Username berdasarkan NIS sudah digunakan!');
        }
    }

    // ------------------------------------
    // 5. SIMPAN DATA SISWA
    // ------------------------------------

    $stmt_siswa = $pdo->prepare("
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
        ) VALUES (
            :nis,
            :nama_siswa,
            :jenis_kelamin,
            :alamat,
            :no_telepon,
            :email,
            :id_paket,
            :status_siswa,
            :tanggal_daftar
        )
    ");

    $stmt_siswa->execute([
        ':nis' => $nis,
        ':nama_siswa' => $nama_siswa,
        ':jenis_kelamin' => $jenis_kelamin,
        ':alamat' => $alamat !== '' ? $alamat : null,
        ':no_telepon' => $no_telepon !== '' ? $no_telepon : null,
        ':email' => $email !== '' ? $email : null,
        ':id_paket' => $id_paket,
        ':status_siswa' => $status_siswa,
        ':tanggal_daftar' => $tanggal_daftar
    ]);

    $new_id_siswa = $pdo->lastInsertId();

    // ------------------------------------
    // 6. BUAT AKUN LOGIN SISWA
    // ------------------------------------

    if ($buat_akun) {

        $username = $nis;

        // Password awal akun siswa
        $password_default = 'siswa123';

        // Hash password menggunakan Bcrypt
        $password_hash = password_hash(
            $password_default,
            PASSWORD_BCRYPT
        );

        if ($password_hash === false) {
            throw new Exception('Gagal mengamankan password akun siswa!');
        }

        $stmt_user = $pdo->prepare("
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
                :nama_lengkap,
                :email,
                'siswa',
                :id_siswa
            )
        ");

        $stmt_user->execute([
            ':username' => $username,
            ':password' => $password_hash,
            ':nama_lengkap' => $nama_siswa,
            ':email' => $email !== '' ? $email : null,
            ':id_siswa' => $new_id_siswa
        ]);
    }

    // ------------------------------------
    // 7. SIMPAN TRANSAKSI
    // ------------------------------------

    $pdo->commit();

    if ($buat_akun) {

        set_flash(
            'success',
            'Data siswa berhasil ditambahkan! Username: ' .
            $nis .
            ' | Password awal: siswa123'
        );

    } else {

        set_flash(
            'success',
            'Data siswa "' . $nama_siswa .
            '" berhasil ditambahkan!'
        );
    }

} catch (Throwable $e) {

    // Batalkan seluruh perubahan jika gagal
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash(
        'danger',
        'Gagal menambahkan data siswa: ' . $e->getMessage()
    );
}

// ========================================
// KEMBALI KE DATA SISWA
// ========================================

header('Location: ' . base_url('modules/siswa/index.php'));
exit;
