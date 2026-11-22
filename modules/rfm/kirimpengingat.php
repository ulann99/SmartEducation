<?php
/**
 * PROSES PENGINGAT PEMBAYARAN INTERNAL
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// Hanya menerima permintaan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('modules/rfm/index.php'));
    exit;
}

// Mengambil data dari form
$id_siswa = filter_input(
    INPUT_POST,
    'id_siswa',
    FILTER_VALIDATE_INT
);

$periode = trim($_POST['periode'] ?? 'Semua Waktu');

// Validasi periode
$periode_valid = [
    'Semua Waktu',
    '3 Bulan Terakhir',
    '6 Bulan Terakhir',
    '1 Tahun Terakhir'
];

if (!in_array($periode, $periode_valid, true)) {
    $periode = 'Semua Waktu';
}

// Validasi ID siswa
if (!$id_siswa || $id_siswa <= 0) {

    set_flash(
        'danger',
        'Data siswa tidak valid.'
    );

    header(
        'Location: ' .
        base_url(
            'modules/rfm/index.php?periode=' .
            urlencode($periode)
        )
    );

    exit;
}

try {

    // =========================================
    // 1. MEMERIKSA DATA SISWA DAN TAGIHAN
    // =========================================

    $stmt = $pdo->prepare("
        SELECT
            s.id_siswa,
            s.nama_siswa,
            COUNT(p.id_pembayaran) AS jumlah_tagihan,
            COALESCE(SUM(p.nominal), 0) AS total_tagihan

        FROM siswa s

        LEFT JOIN pembayaran p
            ON p.id_siswa = s.id_siswa
            AND p.status_pembayaran = 'Belum Lunas'

        WHERE s.id_siswa = :id_siswa

        GROUP BY
            s.id_siswa,
            s.nama_siswa
    ");

    $stmt->execute([
        ':id_siswa' => $id_siswa
    ]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        throw new RuntimeException(
            'Data siswa tidak ditemukan.'
        );
    }

    // =========================================
    // 2. MEMASTIKAN ADA TAGIHAN BELUM LUNAS
    // =========================================

    $jumlah_tagihan = (int) $data['jumlah_tagihan'];

    $total_tagihan = (float) $data['total_tagihan'];

    if ($jumlah_tagihan < 1) {

        set_flash(
            'warning',
            'Pengingat tidak dibuat karena siswa tidak memiliki tagihan berstatus Belum Lunas.'
        );

        header(
            'Location: ' .
            base_url(
                'modules/rfm/index.php?periode=' .
                urlencode($periode)
            )
        );

        exit;
    }

    // =========================================
    // 3. MENYIAPKAN PESAN PENGINGAT
    // =========================================

    $judul = 'Pengingat Pembayaran';

    $pesan =
        'Yth. Siswa/Orang Tua dari ' .
        $data['nama_siswa'] .
        ', terdapat ' .
        $jumlah_tagihan .
        ' tagihan yang belum lunas dengan total nominal Rp' .
        number_format($total_tagihan, 0, ',', '.') .
        '. Silakan periksa dan selesaikan pembayaran melalui sistem Smart Education.';

    // =========================================
    // 4. MENYIMPAN NOTIFIKASI
    // =========================================

    $stmt_insert = $pdo->prepare("
        INSERT INTO notifikasi (
            id_siswa,
            judul,
            pesan,
            status_baca,
            created_at
        )
        VALUES (
            :id_siswa,
            :judul,
            :pesan,
            'Belum Dibaca',
            NOW()
        )
    ");

    $stmt_insert->execute([
        ':id_siswa' => $id_siswa,
        ':judul' => $judul,
        ':pesan' => $pesan
    ]);

    // =========================================
    // 5. PESAN BERHASIL
    // =========================================

    set_flash(
        'success',
        'Pengingat pembayaran berhasil disimpan untuk siswa ' .
        $data['nama_siswa'] .
        '.'
    );

} catch (Throwable $e) {

    set_flash(
        'danger',
        'Gagal membuat pengingat: ' . $e->getMessage()
    );

}

// =========================================
// 6. KEMBALI KE HALAMAN RFM
// =========================================

header(
    'Location: ' .
    base_url(
        'modules/rfm/index.php?periode=' .
        urlencode($periode)
    )
);

exit;