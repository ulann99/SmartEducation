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
    die("ID Pembayaran tidak valid.");
}

// Ambil data pembayaran
$stmt = $pdo->prepare("
    SELECT
        p.*,
        s.nama_siswa,
        s.nis,
        s.no_telepon,
        s.alamat,
        pk.nama_paket,
        pk.jenjang,
        u.nama_lengkap AS verifikator_nama
    FROM pembayaran p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN paket_belajar pk ON p.id_paket = pk.id_paket
    LEFT JOIN users u ON p.diverifikasi_oleh = u.id
    WHERE p.id_pembayaran = :id
    LIMIT 1
");

$stmt->execute([':id' => $id_pembayaran]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data transaksi pembayaran tidak ditemukan.");
}

// Validasi akses pengguna
$user = current_user();

if (
    $user['role'] === 'siswa' &&
    (int)$user['id_siswa'] !== (int)$data['id_siswa']
) {
    die("Akses ditolak. Anda tidak diizinkan mencetak kwitansi siswa lain.");
}

$setting = get_setting();

/**
 * Konversi angka menjadi terbilang Bahasa Indonesia
 */
function terbilang($nilai)
{
    $nilai = (int)abs($nilai);

    $huruf = [
        "",
        "Satu",
        "Dua",
        "Tiga",
        "Empat",
        "Lima",
        "Enam",
        "Tujuh",
        "Delapan",
        "Sembilan",
        "Sepuluh",
        "Sebelas"
    ];

    if ($nilai < 12) {
        return $huruf[$nilai];
    } elseif ($nilai < 20) {
        return terbilang($nilai - 10) . " Belas";
    } elseif ($nilai < 100) {
        return terbilang((int)($nilai / 10)) .
            " Puluh " .
            terbilang($nilai % 10);
    } elseif ($nilai < 200) {
        return "Seratus " . terbilang($nilai - 100);
    } elseif ($nilai < 1000) {
        return terbilang((int)($nilai / 100)) .
            " Ratus " .
            terbilang($nilai % 100);
    } elseif ($nilai < 2000) {
        return "Seribu " . terbilang($nilai - 1000);
    } elseif ($nilai < 1000000) {
        return terbilang((int)($nilai / 1000)) .
            " Ribu " .
            terbilang($nilai % 1000);
    } elseif ($nilai < 1000000000) {
        return terbilang((int)($nilai / 1000000)) .
            " Juta " .
            terbilang($nilai % 1000000);
    } elseif ($nilai < 1000000000000) {
        return terbilang((int)($nilai / 1000000000)) .
            " Miliar " .
            terbilang($nilai % 1000000000);
    }

    return (string)$nilai;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Kwitansi - <?= htmlspecialchars($data['kode_transaksi']) ?>
    </title>

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

        /* Tombol aksi */
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
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all .2s ease;
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
            border-color: #277f73;
        }

        .btn-print {
            color: #ffffff;
            background: #247f73;
            border: 1px solid #247f73;
        }

        .btn-print:hover {
            color: #ffffff;
            background: #17675e;
            border-color: #17675e;
        }

        /* Header kwitansi */
        .receipt-header {
            border-bottom: 2px dashed #cbded9;
            padding-bottom: 22px;
            margin-bottom: 25px;
        }

        .receipt-logo {
            width: 40px;
            height: 40px;
            background: #98EDE1;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .receipt-logo i {
            font-size: 21px;
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
            letter-spacing: .5px;
        }

        /* Watermark */
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

        /* Tabel informasi */
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

        /* Nominal pembayaran */
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

        /* Stempel */
        .stamp-box {
            display: inline-block;
            border: 2px solid #247f73;
            border-radius: 8px;
            padding: 7px 13px;
            color: #247f73;
            font-weight: 800;
            text-transform: uppercase;
            transform: rotate(-5deg);
            letter-spacing: 1px;
            font-size: 13px;
        }

        /* Footer */
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

            .amount-box {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

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
        <?= htmlspecialchars($data['status_pembayaran']) ?>
    </div>

    <!-- Tombol Aksi -->
    <div class="receipt-actions no-print">

        <a
            href="<?= base_url('modules/pembayaran/index.php') ?>"
            class="btn-back"
        >
            <i class="bi bi-arrow-left"></i>
            Kembali
        </a>

        <button
            type="button"
            onclick="window.print()"
            class="btn-print"
        >
            <i class="bi bi-printer"></i>
            Cetak / Simpan PDF
        </button>

    </div>

    <!-- Header Instansi -->
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

                <?= htmlspecialchars($setting['alamat'] ?? 'Jl. Bau Massepe No. 142, Kota Parepare') ?><br>

                Telp:
                <?= htmlspecialchars($setting['telepon'] ?? '0812-4455-6677') ?>

                |

                Email:
                <?= htmlspecialchars($setting['email'] ?? 'admin@smarteducation.sch.id') ?>

            </div>

        </div>

        <div class="text-end">

            <span class="receipt-label">
                KWITANSI PEMBAYARAN
            </span>

            <div class="small text-muted mt-2">
                No. Bukti:
                <strong>
                    <?= htmlspecialchars($data['kode_transaksi']) ?>
                </strong>
            </div>

        </div>

    </div>

    <!-- Informasi Pembayaran -->
    <table class="receipt-table">

        <tr>
            <th>Telah Diterima Dari</th>
            <td>
                : <?= htmlspecialchars($data['nama_siswa']) ?>
                (NIS: <?= htmlspecialchars($data['nis']) ?>)
            </td>
        </tr>

        <tr>
            <th>Untuk Pembayaran</th>
            <td>
                : Bimbingan Belajar -
                <?= htmlspecialchars($data['nama_paket']) ?>
                (<?= htmlspecialchars($data['jenjang']) ?>)
            </td>
        </tr>

        <tr>
            <th>Keterangan</th>
            <td>
                : <?= htmlspecialchars($data['keterangan'] ?? 'Pembayaran SPP Bimbingan Belajar') ?>
            </td>
        </tr>

        <tr>
            <th>Tanggal Transaksi</th>
            <td>
                : <?= format_tanggal($data['tanggal_bayar'], true) ?>
            </td>
        </tr>

        <tr>
            <th>Metode Pembayaran</th>
            <td>
                : <?= htmlspecialchars($data['metode_pembayaran']) ?>
            </td>
        </tr>

        <tr>
            <th>Status Transaksi</th>
            <td>
                :

                <?php if ($data['status_pembayaran'] === 'Lunas'): ?>

                    <span class="text-success fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        LUNAS
                    </span>

                <?php else: ?>

                    <span class="text-warning fw-bold">
                        <?= htmlspecialchars($data['status_pembayaran']) ?>
                    </span>

                <?php endif; ?>

            </td>
        </tr>

    </table>

    <!-- Nominal Pembayaran -->
    <div class="amount-box mb-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">

            <span class="amount-label">
                Jumlah Pembayaran:
            </span>

            <h3 class="amount-value mb-0">
                <?= format_rupiah($data['nominal']) ?>
            </h3>

        </div>

        <div class="small text-muted">
            <em>
                Terbilang:
                <strong>
                    # <?= htmlspecialchars(terbilang($data['nominal'])) ?> Rupiah #
                </strong>
            </em>
        </div>

    </div>

    <!-- Footer Kwitansi -->
    <div class="row pt-3 align-items-end receipt-footer">

        <div class="col-6">

            <div class="stamp-box mb-3">
                <i class="bi bi-patch-check-fill me-1"></i>
                SMART EDU VERIFIED
            </div>

            <div class="small text-muted">
                Bukti sah transaksi digital bimbingan belajar.<br>
                Simpan kwitansi ini sebagai bukti pembayaran resmi.
            </div>

        </div>

        <div class="col-6 text-end">

            <div class="small text-muted mb-1">
                Kota Parepare,
                <?= format_tanggal($data['tanggal_bayar']) ?>
            </div>

            <div class="small fw-semibold text-dark">
                Petugas Keuangan,
            </div>

            <div style="height: 55px;"></div>

            <div class="fw-bold text-dark text-decoration-underline">
                <?= htmlspecialchars($data['verifikator_nama'] ?? 'Bagian Administrasi') ?>
            </div>

            <div class="small text-muted">
                Smart Education Parepare
            </div>

        </div>

    </div>

</div>

</body>
</html>
