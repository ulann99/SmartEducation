<?php
/**
 * Create Siswa Action
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

$nis = trim($_POST['nis'] ?? '');
$nama_siswa = trim($_POST['nama_siswa'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'Laki-laki');
$id_paket = !empty($_POST['id_paket']) ? (int)$_POST['id_paket'] : null;
$no_telepon = trim($_POST['no_telepon'] ?? '');
$email = trim($_POST['email'] ?? '');
$tanggal_daftar = trim($_POST['tanggal_daftar'] ?? date('Y-m-d'));
$status_siswa = trim($_POST['status_siswa'] ?? 'Aktif');
$alamat = trim($_POST['alamat'] ?? '');
$buat_akun = isset($_POST['buat_akun']) && $_POST['buat_akun'] == '1';

if (empty($nis) || empty($nama_siswa)) {
    set_flash('danger', 'NIS dan Nama Siswa wajib diisi!');
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

try {
    // Check if NIS already exists
    $stmt_check = $pdo->prepare("SELECT id_siswa FROM siswa WHERE nis = :nis LIMIT 1");
    $stmt_check->execute([':nis' => $nis]);
    if ($stmt_check->fetch()) {
        set_flash('danger', 'NIS "' . htmlspecialchars($nis) . '" sudah terdaftar! Harap gunakan NIS lain.');
        header('Location: ' . base_url('modules/siswa/index.php'));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO siswa (nis, nama_siswa, jenis_kelamin, alamat, no_telepon, email, id_paket, status_siswa, tanggal_daftar)
        VALUES (:nis, :nama_siswa, :jenis_kelamin, :alamat, :no_telepon, :email, :id_paket, :status_siswa, :tanggal_daftar)
    ");
    $stmt->execute([
        ':nis' => $nis,
        ':nama_siswa' => $nama_siswa,
        ':jenis_kelamin' => $jenis_kelamin,
        ':alamat' => $alamat,
        ':no_telepon' => $no_telepon,
        ':email' => $email,
        ':id_paket' => $id_paket,
        ':status_siswa' => $status_siswa,
        ':tanggal_daftar' => $tanggal_daftar
    ]);
    $new_id_siswa = $pdo->lastInsertId();

    // Auto create user account for student if checked
    if ($buat_akun) {
        $username = $nis;
        $password_hash = password_hash('siswa123', PASSWORD_BCRYPT);
        
        $stmt_user_check = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt_user_check->execute([':username' => $username]);
        if (!$stmt_user_check->fetch()) {
            $stmt_user = $pdo->prepare("
                INSERT INTO users (username, password, nama_lengkap, email, role, id_siswa)
                VALUES (:username, :password, :nama_lengkap, :email, 'siswa', :id_siswa)
            ");
            $stmt_user->execute([
                ':username' => $username,
                ':password' => $password_hash,
                ':nama_lengkap' => $nama_siswa,
                ':email' => !empty($email) ? $email : null,
                ':id_siswa' => $new_id_siswa
            ]);
        }
    }

    set_flash('success', 'Data siswa "' . htmlspecialchars($nama_siswa) . '" berhasil ditambahkan ke sistem!');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal menambahkan data siswa: ' . $e->getMessage());
}

header('Location: ' . base_url('modules/siswa/index.php'));
exit;

