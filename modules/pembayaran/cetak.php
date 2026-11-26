
<?php
/**
 * Cetak Kwitansi / Bukti Pembayaran Digital
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';

require_login();

$id_pembayaran = (int)($_GET['id'] ?? 0);

if ($id_pembayaran <= 0) {
    die('ID Pembayaran tidak valid.');
}

try {
    // Ambil data pembayaran berdasarkan struktur database
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            pd.nomor_registrasi,
            pd.id_siswa,
            pd.jenis_program,
            s.nama_lengkap,
            s.sekolah_asal,
            s.kelas,
            s.alamat_rumah,
            s.no_hp,
            pk.nama_paket,
            pk.jenjang

        FROM pembayaran p

        INNER JOIN pendaftaran pd
            ON p.id_pendaftaran = pd.id_pendaftaran

        INNER JOIN siswa s
            ON pd.id_siswa = s.id_siswa

        LEFT JOIN paket_belajar pk
            ON pd.id_paket = pk.id_paket

        WHERE p.id_pembayaran = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id_pembayaran]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Gagal mengambil data pembayaran. Periksa struktur database.');
}

if (!$data) {
    die('Data transaksi pembayaran tidak ditemukan.');
}

// Validasi hak akses siswa/orang tua
$user = current_user();

if (
    isset($user['role']) &&
    $user['role'] === 'siswa_orang_tua' &&
    (int)($user['id_siswa'] ?? 0) !== (int)$data['id_siswa']
) {
    die('Akses ditolak. Anda tidak diizinkan mencetak kwitansi siswa lain.');
}

// Informasi instansi
$setting = get_setting();

// Kontak Smart Education
// Nomor telepon ini masih nomor contoh.
$kontak_telepon = '0812-3456-7890';
$kontak_email = 'smarteducationparepare@gmail.com';

// Nomor bukti pembayaran
$nomor_bukti = 'PAY-' . str_pad(
    $data['id_pembayaran'],
    5,
    '0',
    STR_PAD_LEFT
);

// Fungsi terbilang
if (!function_exists('terbilang')) {
    function terbilang($nilai)
    {
        $nilai = (int)abs($nilai);

        $huruf = [
            '',
            'Satu',
            'Dua',
            'Tiga',
            'Empat',
            'Lima',
            'Enam',
            'Tujuh',
            'Delapan',
            'Sembilan',
            'Sepuluh',
            'Sebelas'
        ];

        if ($nilai < 12) {
            return $huruf[$nilai];
        } elseif ($nilai < 20) {
            return terbilang($nilai - 10) . ' Belas';
        } elseif ($nilai < 100) {
            return terbilang((int)($nilai / 10)) .
                ' Puluh ' .
                terbilang($nilai % 10);
        } elseif ($nilai < 200) {
            return 'Seratus ' . terbilang($nilai - 100);
        } elseif ($nilai < 1000) {
            return terbilang((int)($nilai / 100)) .
                ' Ratus ' .
                terbilang($nilai % 100);
        } elseif ($nilai < 2000) {
            return 'Seribu ' . terbilang($nilai - 1000);
        } elseif ($nilai < 1000000) {
            return terbilang((int)($nilai / 1000)) .
                ' Ribu ' .
                terbilang($nilai % 1000);
        } elseif ($nilai < 1000000000) {
            return terbilang((int)($nilai / 1000000)) .
                ' Juta ' .
                terbilang($nilai % 1000000);
        } elseif ($nilai < 1000000000000) {
            return terbilang((int)($nilai / 1000000000)) .
                ' Miliar ' .
                terbilang($nilai % 1000000000);
        }

        return (string)$nilai;
    }
}

$tanggal = date(
    'd/m/Y',
    strtotime($data['tanggal_bayar'])
);

$status = $data['status_pembayaran'] ?? 'Menunggu';

$is_berhasil = ($status === 'Berhasil');

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kwitansi <?= htmlspecialchars($nomor_bukti) ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f1f7f6;
            color: #193b38;
            padding: 30px 15px;
        }

        .receipt-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e1ece9;
            border-radius: 18px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 35px rgba(20, 65, 58, 0.08);
        }

        .receipt-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .btn-back,
        .btn-print {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 10px 17px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-back {
            color: #277f73;
            background: #ffffff;
            border: 1px solid #277f73;
        }

        .btn-back:hover {
            color: #ffffff;
            background: #277f73;
        }

        .btn-print {
            color: #ffffff;
            background: #247f73;
            border: 1px solid #247f73;
        }

        .btn-print:hover {
            background: #17675e;
        }

        .receipt-header {
            border-bottom: 2px dashed #cbded9;
            padding-bottom: 22px;
            margin-bottom: 25px;
        }

        .receipt-logo {
            width: 48px;
            height: 48px;
            background: #98EDE1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .receipt-logo i {
            font-size: 25px;
            color: #174b45;
        }

        .receipt-title {
            font-size: 23px;
            font-weight: 800;
            color: #193b38;
            margin: 0;
        }

        .receipt-info {
            font-size: 12px;
            line-height: 1.8;
            color: #64748b;
        }

        .receipt-label {
            display: inline-block;
            padding: 9px 16px;
            background: #f8fafc;
            border: 1px solid #dce7e5;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 700;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 5rem;
            font-weight: 900;
            color: rgba(15, 118, 110, 0.045);
            pointer-events: none;
            text-transform: uppercase;
            letter-spacing: 10px;
            white-space: nowrap;
        }

        .receipt-table {
            width: 100%;
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }

        .receipt-table th {
            width: 200px;
            color: #64748b;
            font-weight: 500;
            padding: 9px 0;
            vertical-align: top;
            font-size: 14px;
        }

        .receipt-table td {
            color: #193b38;
            font-weight: 600;
            padding: 9px 0;
            font-size: 14px;
        }

        .amount-box {
            background: #effcf9;
            border: 1.5px solid #98EDE1;
            border-radius: 13px;
            padding: 18px 24px;
            position: relative;
            z-index: 1;
        }

        .amount-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .amount-value {
            color: #193b38;
            font-size: 28px;
            font-weight: 800;
        }

        .stamp-box {
            display: inline-block;
            border: 2px solid #247f73;
            border-radius: 8px;
            padding: 7px 13px;
            color: #247f73;
            font-weight: 800;
            text-transform: uppercase;
            transform: rotate(-5deg);
            font-size: 13px;
        }

        .receipt-footer {
            position: relative;
            z-index: 1;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .receipt-card {
                padding: 20px;
            }

            .receipt-actions {
                flex-wrap: wrap;
            }

            .receipt-title {
                font-size: 18px;
            }

            .receipt-table th {
                width: 125px;
                font-size: 12px;
            }

            .receipt-table td {
                font-size: 12px;
            }

            .amount-value {
                font-size: 21px;
            }

            .watermark {
                font-size: 3rem;
            }
        }

        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }

            body {
                background: #ffffff;
                padding: 0;
            }

            .receipt-card {
                max-width: 100%;
                box-shadow: none;
                border: none;
                border-radius: 0;
                padding: 15px;
            }

            .no-print {
                display: none !important;
            }

            .amount-box,
            .stamp-box,
            .watermark {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

<div class="receipt-card">

    <div class="watermark">
        <?= htmlspecialchars($status) ?>
    </div>

    <!-- Tombol -->
    <div class="receipt-actions no-print">

        <a href="<?= base_url('modules/pembayaran/index.php') ?>"
           class="btn-back">
            <i class="bi bi-arrow-left"></i>
            Kembali
        </a>

        <button type="button"
                onclick="window.print()"
                class="btn-print">
            <i class="bi bi-printer"></i>
            Cetak / Simpan PDF
        </button>

    </div>

    <!-- Header -->
    <div class="receipt-header d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <div class="d-flex align-items-center gap-2 mb-2">

                <div class="receipt-logo">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>

                <h4 class="receipt-title">
                    <?= htmlspecialchars($setting['nama_instansi'] ?? 'Smart Education Parepare') ?>
                </h4>

            </div>

            <div class="receipt-info">

                <?= htmlspecialchars($setting['alamat'] ?? 'Parepare') ?><br>

                Telp:
                <?= htmlspecialchars($kontak_telepon) ?>

                <br>

                Email:
                <?= htmlspecialchars($kontak_email) ?>

            </div>

        </div>

        <div class="text-end">

            <span class="receipt-label">
                KWITANSI PEMBAYARAN
            </span>

            <div class="small text-muted mt-2">
                No. Bukti:
                <strong>
                    <?= htmlspecialchars($nomor_bukti) ?>
                </strong>
            </div>

            <div class="small text-muted">
                Registrasi:
                <strong>
                    <?= htmlspecialchars($data['nomor_registrasi']) ?>
                </strong>
            </div>

        </div>

    </div>

    <!-- Informasi Pembayaran -->
    <table class="receipt-table">

        <tr>
            <th>Telah Diterima Dari</th>
            <td>
                : <?= htmlspecialchars($data['nama_lengkap']) ?>
            </td>
        </tr>

        <tr>
            <th>Sekolah Asal</th>
            <td>
                : <?= htmlspecialchars($data['sekolah_asal'] ?? '-') ?>
            </td>
        </tr>

        <tr>
            <th>Kelas Siswa</th>
            <td>
                : <?= htmlspecialchars($data['kelas'] ?? '-') ?>
            </td>
        </tr>

        <tr>
            <th>Untuk Pembayaran</th>
            <td>
                : <?= htmlspecialchars($data['jenis_pembayaran']) ?>
            </td>
        </tr>

        <tr>
            <th>Paket Belajar</th>
            <td>
                : <?= htmlspecialchars($data['nama_paket'] ?? '-') ?>
                (<?= htmlspecialchars($data['jenjang'] ?? '-') ?>)
            </td>
        </tr>

        <tr>
            <th>Jenis Program</th>
            <td>
                : <?= htmlspecialchars($data['jenis_program']) ?>
            </td>
        </tr>

        <tr>
            <th>Keterangan</th>
            <td>
                : <?= htmlspecialchars($data['keterangan'] ?? '-') ?>
            </td>
        </tr>

        <tr>
            <th>Tanggal Pembayaran</th>
            <td>
                : <?= htmlspecialchars($tanggal) ?>
            </td>
        </tr>

        <tr>
            <th>Metode Pembayaran</th>
            <td>
                : <?= htmlspecialchars($data['metode_pembayaran']) ?>
            </td>
        </tr>

        <tr>
            <th>Status Pembayaran</th>
            <td>
                :

                <?php if ($is_berhasil): ?>

                    <span class="text-success fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        BERHASIL
                    </span>

                <?php elseif ($status === 'Ditolak'): ?>

                    <span class="text-danger fw-bold">
                        DITOLAK
                    </span>

                <?php else: ?>

                    <span class="text-warning fw-bold">
                        MENUNGGU
                    </span>

                <?php endif; ?>

            </td>
        </tr>

    </table>

    <!-- Nominal -->
    <div class="amount-box mb-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">

            <span class="amount-label">
                Jumlah Pembayaran
            </span>

            <h3 class="amount-value mb-0">
                <?= format_rupiah($data['jumlah_bayar']) ?>
            </h3>

        </div>

        <div class="small text-muted">

            <em>
                Terbilang:

                <strong>
                    # <?= htmlspecialchars(terbilang($data['jumlah_bayar'])) ?> Rupiah #
                </strong>
            </em>

        </div>

    </div>

    <!-- Footer -->
    <div class="row pt-3 align-items-end receipt-footer">

        <div class="col-6">

            <?php if ($is_berhasil): ?>

                <div class="stamp-box mb-3">
                    <i class="bi bi-patch-check-fill me-1"></i>
                    Pembayaran Berhasil
                </div>

            <?php endif; ?>

            <div class="small text-muted">
                Bukti transaksi pembayaran Smart Education Parepare.
                Simpan kwitansi ini sebagai bukti pembayaran.
            </div>

        </div>

        <div class="col-6 text-end">

            <div class="small text-muted mb-1">
                Parepare, <?= htmlspecialchars($tanggal) ?>
            </div>

            <div class="small fw-semibold text-dark">
                Petugas Administrasi,
            </div>

            <div style="height: 55px;"></div>

            <div class="fw-bold text-dark text-decoration-underline">
                Smart Education Parepare
            </div>

        </div>

    </div>

</div>

</body>
</html>
