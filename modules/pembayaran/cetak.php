<?php
/**
 * Cetak Kwitansi / Bukti Pembayaran Digital
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_login();

$id_pembayaran = (int)($_GET['id'] ?? 0);

if (empty($id_pembayaran)) {
    die("ID Pembayaran tidak valid.");
}

// Fetch payment data
$stmt = $pdo->prepare("
    SELECT p.*, s.nama_siswa, s.nis, s.no_telepon, s.alamat, pk.nama_paket, pk.jenjang, u.nama_lengkap AS verifikator_nama
    FROM pembayaran p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN paket_belajar pk ON p.id_paket = pk.id_paket
    LEFT JOIN users u ON p.diverifikasi_oleh = u.id
    WHERE p.id_pembayaran = :id
");
$stmt->execute([':id' => $id_pembayaran]);
$data = $stmt->fetch();

if (!$data) {
    die("Data transaksi pembayaran tidak ditemukan.");
}

$user = current_user();
// If logged in as siswa, make sure they only print their own receipt
if ($user['role'] === 'siswa' && $user['id_siswa'] != $data['id_siswa']) {
    die("Akses ditolak. Anda tidak diizinkan mencetak kwitansi siswa lain.");
}

$setting = get_setting();

/**
 * Konversi angka ke kalimat terbilang bahasa Indonesia
 */
function terbilang($nilai) {
    $nilai = abs($nilai);
    $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    $temp = "";
    if ($nilai < 12) {
        $temp = " " . $huruf[$nilai];
    } else if ($nilai < 20) {
        $temp = terbilang($nilai - 10) . " Belas";
    } else if ($nilai < 100) {
        $temp = terbilang($nilai / 10) . " Puluh" . terbilang($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " Seratus" . terbilang($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = terbilang($nilai / 100) . " Ratus" . terbilang($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " Seribu" . terbilang($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = terbilang($nilai / 1000) . " Ribu" . terbilang($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = terbilang($nilai / 1000000) . " Juta" . terbilang($nilai % 1000000);
    }
    return trim($temp);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi - <?= htmlspecialchars($data['kode_transaksi']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 30px 15px;
        }
        .receipt-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            padding: 40px;
            position: relative;
        }
        .receipt-header {
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 5rem;
            font-weight: 900;
            color: rgba(15, 118, 110, 0.05);
            pointer-events: none;
            text-transform: uppercase;
            letter-spacing: 10px;
        }
        .receipt-table th {
            width: 200px;
            color: #64748b;
            font-weight: 500;
            padding: 8px 0;
        }
        .receipt-table td {
            color: #0f172a;
            font-weight: 600;
            padding: 8px 0;
        }
        .amount-box {
            background-color: #f0fdfa;
            border: 1.5px solid #98EDE1;
            border-radius: 12px;
            padding: 16px 24px;
        }
        .stamp-box {
            display: inline-block;
            border: 2px solid #0f766e;
            border-radius: 8px;
            padding: 6px 14px;
            color: #0f766e;
            font-weight: 800;
            text-transform: uppercase;
            transform: rotate(-5deg);
            letter-spacing: 1px;
        }
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .receipt-card {
                box-shadow: none;
                border: none;
                padding: 20px;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="watermark"><?= htmlspecialchars($data['status_pembayaran']) ?></div>

    <!-- Action Buttons for Screen View -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="javascript:window.history.back()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <button onclick="window.print()" class="btn btn-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Cetak / Simpan PDF
        </button>
    </div>

    <!-- Header Instansi -->
    <div class="receipt-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <div style="width: 36px; height: 36px; background: #98EDE1; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-mortarboard-fill text-dark fs-5"></i>
                </div>
                <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($setting['nama_instansi'] ?? 'Smart Education Parepare') ?></h4>
            </div>
            <p class="text-muted small mb-0" style="max-width: 450px;">
                <?= htmlspecialchars($setting['alamat'] ?? 'Jl. Bau Massepe No. 142, Kota Parepare') ?><br>
                Telp: <?= htmlspecialchars($setting['telepon'] ?? '0812-4455-6677') ?> | Email: <?= htmlspecialchars($setting['email'] ?? 'admin@smarteducation.sch.id') ?>
            </p>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-dark border font-monospace px-3 py-2 fs-6">
                KWITANSI PEMBAYARAN
            </span>
            <div class="small text-muted mt-1">No. Bukti: <strong><?= htmlspecialchars($data['kode_transaksi']) ?></strong></div>
        </div>
    </div>

    <!-- Content Table -->
    <table class="receipt-table w-100 mb-4">
        <tr>
            <th>Telah Diterima Dari</th>
            <td>: <?= htmlspecialchars($data['nama_siswa']) ?> (NIS: <?= htmlspecialchars($data['nis']) ?>)</td>
        </tr>
        <tr>
            <th>Untuk Pembayaran</th>
            <td>: Bimbingan Belajar - <?= htmlspecialchars($data['nama_paket']) ?> (<?= htmlspecialchars($data['jenjang']) ?>)</td>
        </tr>
        <tr>
            <th>Keterangan</th>
            <td>: <?= htmlspecialchars($data['keterangan'] ?? 'Pembayaran SPP Bimbingan Belajar') ?></td>
        </tr>
        <tr>
            <th>Tanggal Transaksi</th>
            <td>: <?= format_tanggal($data['tanggal_bayar'], true) ?></td>
        </tr>
        <tr>
            <th>Metode Pembayaran</th>
            <td>: <?= htmlspecialchars($data['metode_pembayaran']) ?></td>
        </tr>
        <tr>
            <th>Status Transaksi</th>
            <td>: 
                <?php if ($data['status_pembayaran'] === 'Lunas'): ?>
                    <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>LUNAS</span>
                <?php else: ?>
                    <span class="text-warning fw-bold"><?= htmlspecialchars($data['status_pembayaran']) ?></span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- Amount Box -->
    <div class="amount-box mb-4">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small text-uppercase fw-semibold">Jumlah Pembayaran:</span>
            <h3 class="fw-bolder text-dark mb-0"><?= format_rupiah($data['nominal']) ?></h3>
        </div>
        <div class="small text-muted">
            <em>Terbilang: <strong># <?= terbilang($data['nominal']) ?> Rupiah #</strong></em>
        </div>
    </div>

    <!-- Footer Signature & QR -->
    <div class="row pt-3 align-items-end">
        <div class="col-6">
            <div class="stamp-box mb-2">
                <i class="bi bi-patch-check-fill me-1"></i> SMART EDU VERIFIED
            </div>
            <div class="small text-muted">
                Bukti sah transaksi digital bimbingan belajar.<br>
                Simpan kwitansi ini sebagai bukti pembayaran resmi.
            </div>
        </div>
        <div class="col-6 text-end">
            <div class="small text-muted mb-1">Kota Parepare, <?= format_tanggal($data['tanggal_bayar']) ?></div>
            <div class="small fw-semibold text-dark">Petugas Keuangan,</div>
            <div style="height: 55px;"></div>
            <div class="fw-bold text-dark text-decoration-underline">
                <?= htmlspecialchars($data['verifikator_nama'] ?? 'Bagian Administrasi') ?>
            </div>
            <div class="small text-muted">Smart Education Parepare</div>
        </div>
    </div>
</div>

</body>
</html>

