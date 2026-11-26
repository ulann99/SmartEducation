
<?php
/**
 * Edit Data Siswa
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

function edit_siswa_redirect($type, $message) {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;

    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/siswa/index.php'));
    exit;
}

// Validasi CSRF
$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_siswa']) ||
    !hash_equals($_SESSION['csrf_siswa'], $csrf_token)
) {
    edit_siswa_redirect('danger', 'Permintaan tidak valid.');
}

// Ambil ID siswa
$id_siswa = filter_var(
    $_POST['id_siswa'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id_siswa || $id_siswa <= 0) {
    edit_siswa_redirect('danger', 'ID siswa tidak valid.');
}

// ========================================
// DATA SISWA
// ========================================

$nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
$alamat_rumah = trim($_POST['alamat_rumah'] ?? '');
$sekolah_asal = trim($_POST['sekolah_asal'] ?? '');
$kelas = trim($_POST['kelas'] ?? '');
$agama = trim($_POST['agama'] ?? '');
$tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
$tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');
$no_rumah = trim($_POST['no_rumah'] ?? '');

// ========================================
// DATA ORANG TUA
// ========================================

$nama_ayah = trim($_POST['nama_ayah'] ?? '');
$nama_ibu = trim($_POST['nama_ibu'] ?? '');
$nama_pemberi_informasi = trim(
    $_POST['nama_pemberi_informasi'] ?? ''
);
$alamat_orang_tua = trim($_POST['alamat_orang_tua'] ?? '');
$no_hp_ayah = trim($_POST['no_hp_ayah'] ?? '');
$no_hp_ibu = trim($_POST['no_hp_ibu'] ?? '');

// ========================================
// DATA PENDAFTARAN
// ========================================

$tanggal_pendaftaran = trim(
    $_POST['tanggal_pendaftaran'] ?? ''
);

$id_paket = (int)($_POST['id_paket'] ?? 0);

$jenis_program = trim($_POST['jenis_program'] ?? '');
$pilihan_hari = trim($_POST['pilihan_hari'] ?? '');
$pilihan_jam = trim($_POST['pilihan_jam'] ?? '');

// ========================================
// VALIDASI INPUT
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
    $no_hp_ibu
];

foreach ($required_fields as $field) {
    if ($field === '') {
        edit_siswa_redirect(
            'danger',
            'Semua kolom wajib data siswa dan orang tua harus diisi.'
        );
    }
}

if (!in_array($jenis_program, ['Reguler', 'Privat'], true)) {
    edit_siswa_redirect('danger', 'Jenis program tidak valid.');
}

function edit_valid_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);

    return $d && $d->format('Y-m-d') === $date;
}

if (!edit_valid_date($tanggal_lahir)) {
    edit_siswa_redirect('danger', 'Tanggal lahir tidak valid.');
}

if ($tanggal_lahir > date('Y-m-d')) {
    edit_siswa_redirect(
        'danger',
        'Tanggal lahir tidak boleh melebihi hari ini.'
    );
}

if (
    $tanggal_pendaftaran !== '' &&
    !edit_valid_date($tanggal_pendaftaran)
) {
    edit_siswa_redirect(
        'danger',
        'Tanggal pendaftaran tidak valid.'
    );
}

$phone_pattern = '/^[0-9+\-\s()]{6,20}$/';

foreach ([$no_hp, $no_hp_ayah, $no_hp_ibu] as $phone) {
    if (!preg_match($phone_pattern, $phone)) {
        edit_siswa_redirect('danger', 'Format nomor HP tidak valid.');
    }
}

if (
    $no_rumah !== '' &&
    !preg_match($phone_pattern, $no_rumah)
) {
    edit_siswa_redirect('danger', 'Format nomor rumah tidak valid.');
}

// ========================================
// PROSES UPDATE
// ========================================

try {

    $pdo->beginTransaction();

    // Periksa apakah siswa tersedia
    $stmt_cek = $pdo->prepare("
        SELECT id_siswa
        FROM siswa
        WHERE id_siswa = :id_siswa
        FOR UPDATE
    ");

    $stmt_cek->execute([
        ':id_siswa' => $id_siswa
    ]);

    if (!$stmt_cek->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('Data siswa tidak ditemukan.');
    }

    // Update data siswa
    $stmt_siswa = $pdo->prepare("
        UPDATE siswa SET
            nama_lengkap = :nama_lengkap,
            alamat_rumah = :alamat_rumah,
            sekolah_asal = :sekolah_asal,
            kelas = :kelas,
            agama = :agama,
            tempat_lahir = :tempat_lahir,
            tanggal_lahir = :tanggal_lahir,
            no_hp = :no_hp,
            no_rumah = :no_rumah
        WHERE id_siswa = :id_siswa
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
        ':no_rumah' => $no_rumah !== '' ? $no_rumah : null,
        ':id_siswa' => $id_siswa
    ]);

    // Update data orang tua
    $stmt_cek_orang_tua = $pdo->prepare("
        SELECT id_orang_tua
        FROM orang_tua
        WHERE id_siswa = :id_siswa
        LIMIT 1
    ");

    $stmt_cek_orang_tua->execute([
        ':id_siswa' => $id_siswa
    ]);

    $orang_tua = $stmt_cek_orang_tua->fetch(PDO::FETCH_ASSOC);

    if ($orang_tua) {

        $stmt_orang_tua = $pdo->prepare("
            UPDATE orang_tua SET
                nama_ayah = :nama_ayah,
                nama_ibu = :nama_ibu,
                nama_pemberi_informasi = :nama_pemberi_informasi,
                alamat_rumah = :alamat_rumah,
                no_hp_ayah = :no_hp_ayah,
                no_hp_ibu = :no_hp_ibu
            WHERE id_siswa = :id_siswa
        ");

    } else {

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

    }

    $stmt_orang_tua->execute([
        ':id_siswa' => $id_siswa,
        ':nama_ayah' => $nama_ayah,
        ':nama_ibu' => $nama_ibu,
        ':nama_pemberi_informasi' => $nama_pemberi_informasi,
        ':alamat_rumah' => $alamat_orang_tua,
        ':no_hp_ayah' => $no_hp_ayah,
        ':no_hp_ibu' => $no_hp_ibu
    ]);

    // Periksa pendaftaran terbaru
    $stmt_pendaftaran = $pdo->prepare("
        SELECT id_pendaftaran
        FROM pendaftaran
        WHERE id_siswa = :id_siswa
        ORDER BY id_pendaftaran DESC
        LIMIT 1
        FOR UPDATE
    ");

    $stmt_pendaftaran->execute([
        ':id_siswa' => $id_siswa
    ]);

    $pendaftaran = $stmt_pendaftaran->fetch(PDO::FETCH_ASSOC);

    // Update pendaftaran jika datanya ada
    if ($pendaftaran) {

        $id_pendaftaran = (int)$pendaftaran['id_pendaftaran'];

        // Paket wajib valid dan aktif
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

        if (!$stmt_paket->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception(
                'Paket belajar tidak ditemukan atau tidak aktif.'
            );
        }

        $stmt_update_pendaftaran = $pdo->prepare("
            UPDATE pendaftaran SET
                id_paket = :id_paket,
                tanggal_pendaftaran = :tanggal_pendaftaran,
                jenis_program = :jenis_program,
                pilihan_hari = :pilihan_hari,
                pilihan_jam = :pilihan_jam
            WHERE id_pendaftaran = :id_pendaftaran
        ");

        $stmt_update_pendaftaran->execute([
            ':id_paket' => $id_paket,
            ':tanggal_pendaftaran' => $tanggal_pendaftaran !== ''
                ? $tanggal_pendaftaran
                : date('Y-m-d'),
            ':jenis_program' => $jenis_program,
            ':pilihan_hari' => $pilihan_hari,
            ':pilihan_jam' => $pilihan_jam,
            ':id_pendaftaran' => $id_pendaftaran
        ]);

    } else {

        // Jika belum memiliki pendaftaran, buat pendaftaran baru
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

        if (!$stmt_paket->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception(
                'Paket belajar tidak ditemukan atau tidak aktif.'
            );
        }

        $nomor_registrasi = 'REG-' .
            date('Ymd') . '-' .
            strtoupper(bin2hex(random_bytes(3)));

        $stmt_buat_pendaftaran = $pdo->prepare("
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

        $stmt_buat_pendaftaran->execute([
            ':nomor_registrasi' => $nomor_registrasi,
            ':id_siswa' => $id_siswa,
            ':id_paket' => $id_paket,
            ':tanggal_pendaftaran' => $tanggal_pendaftaran !== ''
                ? $tanggal_pendaftaran
                : date('Y-m-d'),
            ':jenis_program' => $jenis_program,
            ':pilihan_hari' => $pilihan_hari,
            ':pilihan_jam' => $pilihan_jam
        ]);
    }

    $pdo->commit();

    edit_siswa_redirect(
        'success',
        'Data siswa ' . $nama_lengkap . ' berhasil diperbarui.'
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Gagal memperbarui data siswa: ' . $e->getMessage()
    );

    edit_siswa_redirect(
        'danger',
        'Data siswa gagal diperbarui. Periksa kembali data yang dimasukkan.'
    );
}
