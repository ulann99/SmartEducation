
<?php
/**
 * CETAK LAPORAN ANALISIS RFM
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

// ========================================
// 1. VALIDASI PERIODE
// ========================================

$periode = trim($_GET['periode'] ?? 'Semua Waktu');

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
// 2. MENGAMBIL DATA ANALISIS RFM
// ========================================

$sql = "
    SELECT
        r.*,
        s.nama_lengkap,
        COALESCE(pb.nama_paket, '-') AS nama_paket

    FROM analisis_rfm r

    INNER JOIN siswa s
        ON r.id_siswa = s.id_siswa

    LEFT JOIN (
        SELECT
            pd1.id_siswa,
            pd1.id_paket

        FROM pendaftaran pd1

        INNER JOIN (
            SELECT
                id_siswa,
                MAX(id_pendaftaran) AS id_pendaftaran
            FROM pendaftaran
            GROUP BY id_siswa
        ) terbaru
            ON pd1.id_pendaftaran = terbaru.id_pendaftaran

    ) pendaftaran_terakhir
        ON s.id_siswa = pendaftaran_terakhir.id_siswa

    LEFT JOIN paket_belajar pb
        ON pendaftaran_terakhir.id_paket = pb.id_paket

    WHERE r.periode_analisis = :periode

    ORDER BY
        r.total_rfm_score DESC,
        r.monetary_amount DESC,
        s.nama_lengkap ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':periode' => $periode
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// 3. MENGHITUNG RINGKASAN SEGMENTASI
// ========================================

$total_siswa = count($rows);

$pembayar_rutin = 0;
$perlu_pengingat = 0;
$berisiko = 0;

foreach ($rows as $r) {

    if ($r['segmentasi'] === 'Pembayar Rutin') {

        $pembayar_rutin++;

    } elseif ($r['segmentasi'] === 'Perlu Pengingat') {

        $perlu_pengingat++;

    } elseif ($r['segmentasi'] === 'Berisiko') {

        $berisiko++;

    }
}

// ========================================
// 4. PENGATURAN LAPORAN
// ========================================

$setting = get_setting();

$url_kembali = base_url(
    'modules/rfm/index.php?periode=' . urlencode($periode)
);

$tanggal_cetak = format_tanggal(date('Y-m-d'));

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Laporan Analisis RFM - Smart Education Parepare</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            font-size: 11px;
            margin: 0;
            padding: 25px;
        }

        .report-container {
            background: #ffffff;
            max-width: 1200px;
            margin: auto;
            padding: 35px 40px;
            border: 1px solid #e2e8f0;
        }

        /* =================================
           TOMBOL AKSI
        ================================= */

        .report-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            width: 100%;
            gap: 12px;
        }

        .btn-back,
        .btn-print {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 7px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: .2s;
        }

        .btn-back {
            background: #ffffff;
            color: #258477;
            border: 1px solid #258477;
        }

        .btn-back:hover {
            background: #258477;
            color: #ffffff;
        }

        .btn-print {
            background: #1e293b;
            color: #ffffff;
            border: 1px solid #1e293b;
        }

        .btn-print:hover {
            background: #334155;
            color: #ffffff;
        }

        /* =================================
           KOP LAPORAN
        ================================= */

        .report-header {
            text-align: center;
            padding-bottom: 18px;
            border-bottom: 4px double #1e293b;
            margin-bottom: 25px;
        }

        .report-header h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: .5px;
        }

        .report-header p {
            margin: 2px 0;
            font-size: 10px;
            color: #475569;
        }

        /* =================================
           JUDUL LAPORAN
        ================================= */

        .report-title {
            text-align: center;
            margin: 25px 0 20px;
        }

        .report-title h3 {
            font-size: 16px;
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 7px;
        }

        .report-title p {
            font-size: 11px;
            margin: 0;
        }

        .report-info {
            margin-bottom: 15px;
            font-size: 10px;
            line-height: 1.8;
        }

        /* =================================
           RINGKASAN SEGMENTASI
        ================================= */

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 22px;
        }

        .summary-item {
            border: 1px solid #cbd5e1;
            padding: 11px;
            text-align: center;
            border-radius: 5px;
        }

        .summary-item .number {
            font-size: 18px;
            font-weight: 700;
            color: #155e59;
            margin-top: 4px;
        }

        .summary-item .label {
            font-size: 9px;
            color: #475569;
        }

        /* =================================
           TABEL LAPORAN
        ================================= */

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th {
            background: #e8fdff !important;
            color: #134e4a;
            font-weight: 600;
            text-align: center;
            font-size: 9px;
            padding: 9px 5px;
            border: 1px solid #94a3b8;
        }

        .table td {
            font-size: 9px;
            padding: 8px 5px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        .table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: 700;
        }

        .segment {
            font-weight: 600;
            text-align: center;
        }

        /* =================================
           KETERANGAN
        ================================= */

        .report-note {
            border: 1px solid #cbd5e1;
            padding: 12px;
            margin-top: 20px;
            border-radius: 4px;
            font-size: 9px;
            line-height: 1.8;
        }

        /* =================================
           TANDA TANGAN
        ================================= */

        .signature {
            width: 260px;
            margin-left: auto;
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
        }

        .signature-space {
            height: 55px;
        }

        .signature-name {
            font-weight: 700;
            text-decoration: underline;
        }

        /* =================================
           FOOTER
        ================================= */

        .report-footer {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            text-align: center;
            font-size: 9px;
            color: #64748b;
        }

        /* =================================
           RESPONSIVE
        ================================= */

        @media screen and (max-width: 768px) {

            body {
                padding: 10px;
            }

            .report-container {
                padding: 20px 12px;
            }

            .report-toolbar {
                flex-wrap: wrap;
            }

            .summary {
                grid-template-columns: repeat(2, 1fr);
            }

            .table {
                min-width: 850px;
            }

        }

        /* =================================
           MODE CETAK PDF
        ================================= */

        @media print {

            @page {
                size: A4 landscape;
                margin: 12mm;
            }

            body {
                background: #ffffff;
                padding: 0;
                font-size: 10px;
            }

            .report-container {
                max-width: none;
                border: none;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }

            .summary-item,
            .report-note,
            .signature {
                break-inside: avoid;
            }

        }

    </style>

</head>

<body>

<div class="report-container">

    <!-- =================================
         TOMBOL KEMBALI DAN CETAK
    ================================== -->

    <div class="report-toolbar no-print">

        <a
            href="<?= htmlspecialchars($url_kembali) ?>"
            class="btn-back"
        >

            <i class="bi bi-arrow-left"></i>

            Kembali ke Analisis RFM

        </a>

        <button
            type="button"
            onclick="window.print()"
            class="btn-print"
        >

            <i class="bi bi-printer"></i>

            Cetak Laporan / Simpan PDF

        </button>

    </div>

    <!-- =================================
         KOP LAPORAN
    ================================== -->

    <div class="report-header">

        <h2>

            <?= htmlspecialchars(
                $setting['nama_instansi'] ?? 'SMART EDUCATION PAREPARE'
            ) ?>

        </h2>

        <p>

            <?= htmlspecialchars(
                $setting['alamat'] ?? 'Jl. Bau Massepe No. 142, Kota Parepare'
            ) ?>

        </p>

        <p>

            Telepon:

            <?= htmlspecialchars(
                $setting['telepon'] ?? '0812-4455-6677'
            ) ?>

            &nbsp; | &nbsp;

            Email:

            <?= htmlspecialchars(
                $setting['email'] ?? 'admin@smarteducation.sch.id'
            ) ?>

        </p>

    </div>

    <!-- =================================
         JUDUL LAPORAN
    ================================== -->

    <div class="report-title">

        <h3>

            LAPORAN HASIL ANALISIS RFM
            DAN SEGMENTASI SISWA

        </h3>

        <p>

            Periode Analisis:

            <strong>
                <?= htmlspecialchars($periode) ?>
            </strong>

        </p>

    </div>

    <!-- =================================
         INFORMASI LAPORAN
    ================================== -->

    <div class="report-info">

        <strong>Informasi Laporan</strong><br>

        Tanggal Cetak:
        <?= htmlspecialchars($tanggal_cetak) ?><br>

        Jumlah Siswa Dianalisis:
        <?= $total_siswa ?> siswa

    </div>

    <!-- =================================
         RINGKASAN HASIL ANALISIS
    ================================== -->

    <div class="summary">

        <div class="summary-item">

            <div class="label">
                Total Siswa Dianalisis
            </div>

            <div class="number">
                <?= $total_siswa ?>
            </div>

        </div>

        <div class="summary-item">

            <div class="label">
                Pembayar Rutin
            </div>

            <div class="number">
                <?= $pembayar_rutin ?>
            </div>

        </div>

        <div class="summary-item">

            <div class="label">
                Perlu Pengingat
            </div>

            <div class="number">
                <?= $perlu_pengingat ?>
            </div>

        </div>

        <div class="summary-item">

            <div class="label">
                Berisiko
            </div>

            <div class="number">
                <?= $berisiko ?>
            </div>

        </div>

    </div>

    <!-- =================================
         TABEL HASIL RFM
    ================================== -->

    <table class="table">

        <thead>

            <tr>

                <th style="width:35px;">
                    No
                </th>

                <th>
                    ID Siswa
                </th>

                <th>
                    Nama Siswa
                </th>

                <th>
                    Paket Belajar
                </th>

                <th>
                    Recency<br>(Hari / Skor)
                </th>

                <th>
                    Frequency<br>(Kali / Skor)
                </th>

                <th>
                    Monetary<br>(Nominal / Skor)
                </th>

                <th>
                    Total RFM
                </th>

                <th>
                    Segmentasi Siswa
                </th>

            </tr>

        </thead>

        <tbody>

        <?php if (empty($rows)): ?>

            <tr>

                <td colspan="9" class="text-center">

                    Belum ada data analisis pada periode ini.

                </td>

            </tr>

        <?php else: ?>

            <?php $no = 1; ?>

            <?php foreach ($rows as $r): ?>

                <tr>

                    <td class="text-center">
                        <?= $no++ ?>
                    </td>

                    <td class="text-center">
                        <?= (int)$r['id_siswa'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($r['nama_lengkap']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($r['nama_paket'] ?? '-') ?>
                    </td>

                    <td class="text-center">

                        <?php if ((int)$r['recency_days'] === 999): ?>

                            Belum pernah membayar

                        <?php else: ?>

                            <?= (int)$r['recency_days'] ?> hari

                        <?php endif; ?>

                        (R: <?= (int)$r['r_score'] ?>)

                    </td>

                    <td class="text-center">

                        <?= (int)$r['frequency_count'] ?>x

                        (F: <?= (int)$r['f_score'] ?>)

                    </td>

                    <td class="text-end">

                        <?= format_rupiah($r['monetary_amount']) ?>

                        (M: <?= (int)$r['m_score'] ?>)

                    </td>

                    <td class="text-center fw-bold">

                        <?= (int)$r['total_rfm_score'] ?>

                    </td>

                    <td class="segment">

                        <?= htmlspecialchars($r['segmentasi']) ?>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

    <!-- =================================
         KETERANGAN PERHITUNGAN
    ================================== -->

    <div class="report-note">

        <strong>Keterangan Perhitungan RFM:</strong><br>

        <strong>Recency (R)</strong> menunjukkan jarak waktu
        dalam hari sejak pembayaran berhasil terakhir dilakukan.<br>

        <strong>Frequency (F)</strong> menunjukkan jumlah transaksi
        pembayaran berhasil dalam periode analisis.<br>

        <strong>Monetary (M)</strong> menunjukkan total nominal
        pembayaran berhasil dalam periode analisis.<br>

        Siswa yang belum pernah melakukan pembayaran pada periode
        analisis diberikan nilai penanda Recency 999 hari dan
        skor RFM sesuai ketentuan sistem.

    </div>

    <!-- =================================
         TANDA TANGAN
    ================================== -->

    <div class="signature">

        <div>

            Kota Parepare,
            <?= htmlspecialchars($tanggal_cetak) ?>

        </div>

        <div class="mt-2">

            Kepala Bagian Akademik & Analisis,

        </div>

        <div class="signature-space"></div>

        <div class="signature-name">

            Administrator Smart Edu

        </div>

        <div>

            Smart Education Parepare

        </div>

    </div>

    <!-- =================================
         FOOTER
    ================================== -->

    <div class="report-footer">

        Smart Education Parepare

        &nbsp; | &nbsp;

        Laporan Analisis RFM dan Segmentasi Siswa

    </div>

</div>

</body>

</html>
