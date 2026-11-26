
<?php
/**
 * Tambah Data Siswa
 * SMART EDUCATION PAREPARE
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__DIR__)));
}

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// ========================================
// FUNGSI REDIRECT DAN NOTIFIKASI
// ========================================

function create_siswa_redirect($type, $message)
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;

    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

// ========================================
// VALIDASI METHOD POST
// ========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

// ========================================
// VALIDASI CSRF TOKEN
// ========================================

$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_siswa']) ||
    !is_string($csrf_token) ||
    !hash_equals($_SESSION['csrf_siswa'], $csrf_token)
) {
    create_siswa_redirect(
        'danger',
        'Permintaan tidak valid. Silakan coba kembali.'
    );
}

// ========================================
// AMBIL DATA SISWA
// ========================================

$nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
$alamat_rumah  = trim($_POST['alamat_rumah'] ?? '');
$sekolah_asal  = trim($_POST['sekolah_asal'] ?? '');
$kelas         = trim($_POST['kelas'] ?? '');
$agama         = trim($_POST['agama'] ?? '');
$tempat_lahir  = trim($_POST['tempat_lahir'] ?? '');
$tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
$no_hp         = trim($_POST['no_hp'] ?? '');
$no_rumah      = trim($_POST['no_rumah'] ?? '');

// ========================================
// AMBIL DATA ORANG TUA
// ========================================

$nama_ayah = trim($_POST['nama_ayah'] ?? '');
$nama_ibu = trim($_POST['nama_ibu'] ?? '');

$nama_pemberi_informasi = trim(
    $_POST['nama_pemberi_informasi'] ?? ''
);

$alamat_orang_tua = trim(
    $_POST['alamat_orang_tua'] ?? ''
);

$no_hp_ayah = trim($_POST['no_hp_ayah'] ?? '');
$no_hp_ibu = trim($_POST['no_hp_ibu'] ?? '');

// ========================================
// AMBIL DATA PENDAFTARAN
// ========================================

$tanggal_pendaftaran = trim(
    $_POST['tanggal_pendaftaran'] ?? ''
);

$id_paket = (int)($_POST['id_paket'] ?? 0);

$jenis_program = trim(
    $_POST['jenis_program'] ?? ''
);

$pilihan_hari = trim(
    $_POST['pilihan_hari'] ?? ''
);

$pilihan_jam = trim(
    $_POST['pilihan_jam'] ?? ''
);

// ========================================
// VALIDASI DATA WAJIB
// ========================================

$required_fields = [
    $nama_lengkap,
    $alamat_rumah,
    $sekolah_asal,
    $kelas,
    $agama,
    $tempat_lahir,
    $tanggal_lahir,
    $no_hp,
    $nama_ayah,
    $nama_ibu,
    $nama_pemberi_informasi,
    $alamat_orang_tua,
    $no_hp_ayah,
    $no_hp_ibu,
    $tanggal_pendaftaran,
    $pilihan_hari,
    $pilihan_jam
];

foreach ($required_fields as $field) {
    if ($field === '') {
        create_siswa_redirect(
            'danger',
            'Semua kolom wajib harus diisi.'
        );
    }
}

// ========================================
// VALIDASI PAKET BELAJAR
// ========================================

if ($id_paket <= 0) {
    create_siswa_redirect(
        'danger',
        'Silakan pilih paket belajar.'
    );
}

// ========================================
// VALIDASI JENIS PROGRAM
// ========================================

if (!in_array(
    $jenis_program,
    ['Reguler', 'Privat'],
    true
)) {
    create_siswa_redirect(
        'danger',
        'Jenis program yang dipilih tidak valid.'
    );
}

// ========================================
// VALIDASI TANGGAL
// ========================================

function create_siswa_valid_date($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);

    return $d && $d->format('Y-m-d') === $date;
}

if (!create_siswa_valid_date($tanggal_lahir)) {
    create_siswa_redirect(
        'danger',
        'Tanggal lahir tidak valid.'
    );
}

if (!create_siswa_valid_date($tanggal_pendaftaran)) {
    create_siswa_redirect(
        'danger',
        'Tanggal pendaftaran tidak valid.'
    );
}

if ($tanggal_lahir > date('Y-m-d')) {
    create_siswa_redirect(
        'danger',
        'Tanggal lahir tidak boleh melebihi hari ini.'
    );
}

// ========================================
// VALIDASI NOMOR TELEPON
// ========================================

$phone_pattern = '/^[0-9+\-\s()]{6,20}$/';

foreach ([$no_hp, $no_hp_ayah, $no_hp_ibu] as $phone) {
    if (!preg_match($phone_pattern, $phone)) {
        create_siswa_redirect(
            'danger',
            'Format nomor HP tidak valid.'
        );
    }
}

if (
    $no_rumah !== '' &&
    !preg_match($phone_pattern, $no_rumah)
) {
    create_siswa_redirect(
        'danger',
        'Format nomor rumah tidak valid.'
    );
}

// ========================================
// PROSES PENYIMPANAN DATABASE
// ========================================

try {

    // Mulai transaksi
    $pdo->beginTransaction();

    // ------------------------------------
    // 1. PERIKSA PAKET BELAJAR
    // ------------------------------------

    $stmt_paket = $pdo->prepare("
        SELECT id_paket
        FROM paket_belajar
        WHERE id_paket = :id_paket
          AND status = 'aktif'
        LIMIT 1
    ");

    $stmt_paket->execute([
        ':id_paket' => $id_paket
    ]);

    $paket = $stmt_paket->fetch(PDO::FETCH_ASSOC);

    if (!$paket) {
        throw new Exception(
            'Paket belajar tidak ditemukan atau tidak aktif.'
        );
    }

    // ------------------------------------
    // 2. SIMPAN DATA SISWA
    // ------------------------------------

    $stmt_siswa = $pdo->prepare("
        INSERT INTO siswa (
            nama_lengkap,
            alamat_rumah,
            sekolah_asal,
            kelas,
            agama,
            tempat_lahir,
            tanggal_lahir,
            no_hp,
            no_rumah
        ) VALUES (
            :nama_lengkap,
            :alamat_rumah,
            :sekolah_asal,
            :kelas,
            :agama,
            :tempat_lahir,
            :tanggal_lahir,
            :no_hp,
            :no_rumah
        )
    ");

    $stmt_siswa->execute([
        ':nama_lengkap' => $nama_lengkap,
        ':alamat_rumah' => $alamat_rumah,
        ':sekolah_asal' => $sekolah_asal,
        ':kelas' => $kelas,
        ':agama' => $agama,
        ':tempat_lahir' => $tempat_lahir,
        ':tanggal_lahir' => $tanggal_lahir,
        ':no_hp' => $no_hp,
        ':no_rumah' => $no_rumah !== '' ? $no_rumah : null
    ]);

    $id_siswa = (int)$pdo->lastInsertId();

    // ------------------------------------
    // 3. SIMPAN DATA ORANG TUA
    // ------------------------------------

    $stmt_orang_tua = $pdo->prepare("
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
            :nama_ayah,
            :nama_ibu,
            :nama_pemberi_informasi,
            :alamat_rumah,
            :no_hp_ayah,
            :no_hp_ibu
        )
    ");

    $stmt_orang_tua->execute([
        ':id_siswa' => $id_siswa,
        ':nama_ayah' => $nama_ayah,
        ':nama_ibu' => $nama_ibu,
        ':nama_pemberi_informasi' => $nama_pemberi_informasi,
        ':alamat_rumah' => $alamat_orang_tua,
        ':no_hp_ayah' => $no_hp_ayah,
        ':no_hp_ibu' => $no_hp_ibu
    ]);

    // ------------------------------------
    // 4. BUAT NOMOR REGISTRASI
    // ------------------------------------

    $nomor_registrasi = 'REG-' .
        date('Ymd') . '-' .
        strtoupper(bin2hex(random_bytes(3)));

    // ------------------------------------
    // 5. SIMPAN DATA PENDAFTARAN
    // ------------------------------------

    $stmt_pendaftaran = $pdo->prepare("
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
            :nomor_registrasi,
            :id_siswa,
            :id_paket,
            :tanggal_pendaftaran,
            :jenis_program,
            :pilihan_hari,
            :pilihan_jam,
            220000,
            'Menunggu'
        )
    ");

    $stmt_pendaftaran->execute([
        ':nomor_registrasi' => $nomor_registrasi,
        ':id_siswa' => $id_siswa,
        ':id_paket' => $id_paket,
        ':tanggal_pendaftaran' => $tanggal_pendaftaran,
        ':jenis_program' => $jenis_program,
        ':pilihan_hari' => $pilihan_hari,
        ':pilihan_jam' => $pilihan_jam
    ]);

    // ------------------------------------
    // 6. SIMPAN SEMUA PERUBAHAN
    // ------------------------------------

    $pdo->commit();

    // Hapus token setelah berhasil digunakan
    unset($_SESSION['csrf_siswa']);

    create_siswa_redirect(
        'success',
        'Data siswa ' . $nama_lengkap .
        ' berhasil ditambahkan. Nomor registrasi: ' .
        $nomor_registrasi
    );

} catch (Throwable $e) {

    // Batalkan seluruh transaksi jika gagal
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Catat kesalahan ke log PHP
    error_log(
        'Gagal menambahkan siswa: ' . $e->getMessage()
    );

    create_siswa_redirect(
        'danger',
        'Data siswa gagal disimpan. Periksa kembali data dan koneksi database.'
    );
}
