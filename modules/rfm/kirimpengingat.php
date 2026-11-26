
<?php
/**
 * PROSES PENGINGAT PEMBAYARAN INTERNAL
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));

require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// ========================================
// 1. HANYA MENERIMA PERMINTAAN POST
// ========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        'Location: ' . base_url('modules/rfm/index.php')
    );

    exit;
}

// ========================================
// 2. MENGAMBIL DATA DARI FORM
// ========================================

$id_siswa = filter_input(
    INPUT_POST,
    'id_siswa',
    FILTER_VALIDATE_INT
);

$periode = trim(
    $_POST['periode'] ?? 'Semua Waktu'
);

// ========================================
// 3. VALIDASI PERIODE
// ========================================

$periode_valid = [
    'Semua Waktu',
    '3 Bulan Terakhir',
    '6 Bulan Terakhir',
    '1 Tahun Terakhir'
];

if (!in_array($periode, $periode_valid, true)) {
    $periode = 'Semua Waktu';
}

// ========================================
// 4. VALIDASI ID SISWA
// ========================================

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

    // ========================================
    // 5. MEMERIKSA DATA SISWA DAN TAGIHAN
    // ========================================

    /*
     * Relasi:
     *
     * siswa.id_siswa
     *      |
     *      v
     * pendaftaran.id_siswa
     *      |
     *      v
     * pembayaran.id_pendaftaran
     *
     * Pembayaran berstatus Menunggu
     * dianggap sebagai tagihan yang
     * perlu mendapatkan pengingat.
     */

    $sql = "
        SELECT
            s.id_siswa,
            s.nama_lengkap,

            COUNT(DISTINCT p.id_pembayaran)
                AS jumlah_tagihan,

            COALESCE(
                SUM(p.jumlah_bayar),
                0
            ) AS total_tagihan

        FROM siswa s

        LEFT JOIN pendaftaran pd
            ON s.id_siswa = pd.id_siswa

        LEFT JOIN pembayaran p
            ON pd.id_pendaftaran = p.id_pendaftaran
            AND p.status_pembayaran = 'Menunggu'

        WHERE s.id_siswa = :id_siswa

        GROUP BY
            s.id_siswa,
            s.nama_lengkap
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id_siswa' => $id_siswa
    ]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {

        throw new RuntimeException(
            'Data siswa tidak ditemukan.'
        );
    }

    // ========================================
    // 6. MEMERIKSA TAGIHAN YANG MENUNGGU
    // ========================================

    $jumlah_tagihan = (int) $data['jumlah_tagihan'];

    $total_tagihan = (float) $data['total_tagihan'];

    if ($jumlah_tagihan < 1) {

        set_flash(
            'warning',
            'Pengingat tidak dibuat karena siswa tidak memiliki pembayaran berstatus Menunggu.'
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

    // ========================================
    // 7. MENYIAPKAN PESAN PENGINGAT
    // ========================================

    $judul = 'Pengingat Pembayaran';

    $pesan =
        'Yth. Siswa/Orang Tua dari ' .
        $data['nama_lengkap'] .
        ', terdapat ' .
        $jumlah_tagihan .
        ' pembayaran yang masih menunggu penyelesaian ' .
        'dengan total nominal Rp' .
        number_format(
            $total_tagihan,
            0,
            ',',
            '.'
        ) .
        '. Silakan periksa dan selesaikan pembayaran ' .
        'melalui sistem Smart Education.';

    // ========================================
    // 8. MENYIMPAN NOTIFIKASI
    // ========================================

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

    // ========================================
    // 9. PESAN BERHASIL
    // ========================================

    set_flash(
        'success',
        'Pengingat pembayaran berhasil disimpan untuk siswa ' .
        $data['nama_lengkap'] .
        '.'
    );

} catch (Throwable $e) {

    // ========================================
    // 10. MENANGANI KESALAHAN
    // ========================================

    set_flash(
        'danger',
        'Gagal membuat pengingat: ' .
        $e->getMessage()
    );

}

// ========================================
// 11. KEMBALI KE HALAMAN ANALISIS RFM
// ========================================

header(
    'Location: ' .
    base_url(
        'modules/rfm/index.php?periode=' .
        urlencode($periode)
    )
);

exit;
