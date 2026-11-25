
<?php
/**
 * TAMBAH DATA TUTOR
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';
require_roles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

// Validasi CSRF
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    set_flash('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

$nip = trim($_POST['nip'] ?? '');
$nama = trim($_POST['nama_tutor'] ?? '');
$keahlian = trim($_POST['keahlian'] ?? '');
$telepon = trim($_POST['no_telepon'] ?? '');
$email = trim($_POST['email'] ?? '');
$status = $_POST['status_tutor'] ?? 'Aktif';

if (
    $nip === '' ||
    $nama === '' ||
    $keahlian === '' ||
    !in_array($status, ['Aktif', 'Cuti', 'Non-Aktif'], true)
) {
    set_flash('danger', 'NIP, nama, dan keahlian wajib diisi.');
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

if (
    mb_strlen($nip) > 20 ||
    mb_strlen($nama) > 100 ||
    mb_strlen($keahlian) > 100 ||
    mb_strlen($telepon) > 20 ||
    mb_strlen($email) > 100
) {
    set_flash('danger', 'Panjang salah satu data melebihi batas kolom database.');
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Format email tidak valid.');
    header('Location: ' . base_url('modules/tutor/index.php'));
    exit;
}

try {
    // Cek NIP duplikat
    $cek = $pdo->prepare("SELECT id_tutor FROM tutor WHERE nip = ?");
    $cek->execute([$nip]);

    if ($cek->fetch()) {
        set_flash('danger', 'NIP atau kode tutor sudah digunakan.');
        header('Location: ' . base_url('modules/tutor/index.php'));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO tutor
            (nip, nama_tutor, no_telepon, email, keahlian, status_tutor)
        VALUES
            (:nip, :nama, :telepon, :email, :keahlian, :status)
    ");

    $stmt->execute([
        ':nip' => $nip,
        ':nama' => $nama,
        ':telepon' => $telepon !== '' ? $telepon : null,
        ':email' => $email !== '' ? $email : null,
        ':keahlian' => $keahlian,
        ':status' => $status
    ]);

    set_flash('success', 'Data tutor berhasil ditambahkan.');

} catch (PDOException $e) {
    set_flash('danger', 'Data tutor gagal ditambahkan. Periksa kembali data yang dimasukkan.');
}

header('Location: ' . base_url('modules/tutor/index.php'));
exit;
