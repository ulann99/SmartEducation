<?php
/**
 * Pembayaran Siswa (Portal Siswa)
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['siswa']);

$current_page = 'pembayaran';
$page_title = 'Pembayaran & Tagihan Siswa';
$user = current_user();
$id_siswa = $user['id_siswa'];

// Student and active package details
$stmt_siswa = $pdo->prepare("
    SELECT s.*, p.nama_paket, p.harga, p.jenjang
    FROM siswa s
    LEFT JOIN paket_belajar p ON s.id_paket = p.id_paket
    WHERE s.id_siswa = :id_siswa
");
$stmt_siswa->execute([':id_siswa' => $id_siswa]);
$siswa = $stmt_siswa->fetch();

$setting = get_setting();

// Handle Submit Payment Confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal = (float)($_POST['nominal'] ?? 0);
    $metode_pembayaran = trim($_POST['metode_pembayaran'] ?? 'QRIS');
    $tanggal_bayar = trim($_POST['tanggal_bayar'] ?? date('Y-m-d'));
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($nominal <= 0 || empty($siswa['id_paket'])) {
        set_flash('danger', 'Nominal tidak valid atau Anda belum terdaftar pada paket belajar!');
    } else {
        $bukti_filename = null;
        if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
            $upload = upload_image($_FILES['bukti_pembayaran'], 'uploads');
            if ($upload['success']) {
                $bukti_filename = $upload['filename'];
            } else {
                set_flash('danger', $upload['message']);
                header('Location: ' . base_url('modules/pembayaran/bayar_siswa.php'));
                exit;
            }
        } else {
            set_flash('danger', 'Wajib melampirkan berkas bukti pembayaran (struk / tangkapan layar)!');
            header('Location: ' . base_url('modules/pembayaran/bayar_siswa.php'));
            exit;
        }

        try {
            $kode_transaksi = 'TRX-' . date('Ymd', strtotime($tanggal_bayar)) . '-' . strtoupper(substr(uniqid(), -5));

            $stmt = $pdo->prepare("
                INSERT INTO pembayaran (kode_transaksi, id_siswa, id_paket, nominal, tanggal_bayar, metode_pembayaran, status_pembayaran, bukti_pembayaran, keterangan)
                VALUES (:kode, :id_siswa, :id_paket, :nominal, :tanggal_bayar, :metode, 'Menunggu Konfirmasi', :bukti, :keterangan)
            ");
            $stmt->execute([
                ':kode' => $kode_transaksi,
                ':id_siswa' => $id_siswa,
                ':id_paket' => $siswa['id_paket'],
                ':nominal' => $nominal,
                ':tanggal_bayar' => $tanggal_bayar,
                ':metode' => $metode_pembayaran,
                ':bukti' => $bukti_filename,
                ':keterangan' => $keterangan
            ]);

            set_flash('success', 'Konfirmasi pembayaran berhasil dikirim! Menunggu verifikasi admin.');
            header('Location: ' . base_url('modules/pembayaran/bayar_siswa.php'));
            exit;
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal memproses konfirmasi: ' . $e->getMessage());
        }
    }
}

// Student Payment History
$stmt_history = $pdo->prepare("
    SELECT p.*, pk.nama_paket
    FROM pembayaran p
    JOIN paket_belajar pk ON p.id_paket = pk.id_paket
    WHERE p.id_siswa = :id_siswa
    ORDER BY p.id_pembayaran DESC
");
$stmt_history->execute([':id_siswa' => $id_siswa]);
$history = $stmt_history->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="h3 fw-bold">Pembayaran & Tagihan Belajar</h1>
        <p class="text-muted">Informasi tagihan bimbel, instruksi QRIS / Transfer Bank, dan konfirmasi bukti pembayaran.</p>
    </div>
</div>

<!-- Notice Alert regarding QRIS Verification -->
<div class="alert alert-info border-0 shadow-sm rounded-3 d-flex align-items-center mb-4">
    <i class="bi bi-info-circle-fill fs-4 me-3 text-info"></i>
    <div>
        <strong>Pemberitahuan Sistem Pembayaran:</strong><br>
        Setelah Anda mengunggah bukti pembayaran QRIS atau Transfer Bank, admin keuangan Smart Education Parepare akan melakukan verifikasi manual terhadap mutasi rekening sebelum status tagihan dinyatakan <strong>Lunas</strong>.
    </div>
</div>

<div class="row g-4">
    <!-- QRIS & Bank Info Column -->
    <div class="col-lg-5">
        <!-- QRIS Box -->
        <div class="card border-0 shadow-sm text-center mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-mint-deep">
                    <i class="bi bi-qr-code me-1 text-mint"></i> QRIS Smart Education
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <img src="<?= base_url('assets/images/qris-dummy.svg') ?>" alt="QRIS Code" class="img-fluid rounded border shadow-sm" style="max-width: 260px;">
                </div>
                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($setting['nama_instansi'] ?? 'Smart Education Parepare') ?></h6>
                <small class="text-muted d-block mb-3">Scan menggunakan GoPay, OVO, Dana, ShopeePay, BCA Mobile, Livin Mandiri, atau BRImo.</small>
            </div>
        </div>

        <!-- Bank Transfer Details -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-mint-deep">
                    <i class="bi bi-bank me-1 text-mint"></i> Rekening Transfer Bank
                </h6>
            </div>
            <div class="card-body">
                <div class="p-3 bg-light rounded-3 mb-2">
                    <div class="small text-muted mb-1">Nama Bank:</div>
                    <h6 class="fw-bold mb-2 text-dark"><?= htmlspecialchars($setting['nama_bank'] ?? 'Bank Mandiri') ?></h6>
                    <div class="small text-muted mb-1">Nomor Rekening:</div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fs-5 fw-bolder font-monospace text-mint-dark"><?= htmlspecialchars($setting['no_rekening'] ?? '174-00-9876543-2') ?></span>
                    </div>
                    <div class="small text-muted mt-2">Atas Nama: <strong><?= htmlspecialchars($setting['atas_nama'] ?? 'Smart Education Parepare') ?></strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Confirmation Form Column -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-mint-deep">
                    <i class="bi bi-upload me-1 text-mint"></i> Konfirmasi Pembayaran
                </h5>
            </div>
            <div class="card-body p-4">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3 p-3 bg-mint-soft rounded-3">
                        <div class="small text-muted">Paket Bimbingan Aktif Anda:</div>
                        <h5 class="fw-bold text-mint-deep mb-1"><?= htmlspecialchars($siswa['nama_paket'] ?? 'Belum ada paket') ?></h5>
                        <div class="text-dark-mint fw-bold fs-6">Biaya: <?= format_rupiah($siswa['harga'] ?? 0) ?> / bulan</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nominal Transfer (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1000" name="nominal" class="form-control" value="<?= $siswa['harga'] ?? 0 ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Transfer / Bayar <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Metode Pembayaran yang Digunakan <span class="text-danger">*</span></label>
                            <select name="metode_pembayaran" class="form-select" required>
                                <option value="QRIS" selected>QRIS (Semua E-Wallet / Bank)</option>
                                <option value="Transfer Bank">Transfer Bank Mandiri</option>
                                <option value="Tunai">Tunai (Di Kantor Bimbel)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload Bukti Transfer / Resi <span class="text-danger">*</span></label>
                            <input type="file" name="bukti_pembayaran" class="form-control" accept="image/*" required>
                            <div class="form-text">Format: JPG, PNG, WEBP (Maks. 3MB)</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan Tambahan</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Pembayaran SPP Bulan November 2026 atas nama Ahmad">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-mint w-100 py-2 fw-bold">
                            <i class="bi bi-send-check me-1"></i> Kirim Konfirmasi Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- History of Student Payments -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-mint-deep">
                    <i class="bi bi-clock-history me-1 text-mint"></i> Status Riwayat Transaksi Anda
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode TRX</th>
                            <th>Tanggal</th>
                            <th>Nominal</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Kwitansi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi.</td></tr>
                        <?php else: ?>
                            <?php foreach ($history as $h): ?>
                                <tr>
                                    <td><span class="small font-monospace"><?= htmlspecialchars($h['kode_transaksi']) ?></span></td>
                                    <td class="small"><?= date('d/m/Y', strtotime($h['tanggal_bayar'])) ?></td>
                                    <td class="fw-bold text-mint-deep small"><?= format_rupiah($h['nominal']) ?></td>
                                    <td><span class="badge bg-light text-dark border small"><?= htmlspecialchars($h['metode_pembayaran']) ?></span></td>
                                    <td>
                                        <?php if ($h['status_pembayaran'] === 'Lunas'): ?>
                                            <span class="badge badge-mint small">Lunas</span>
                                        <?php elseif ($h['status_pembayaran'] === 'Menunggu Konfirmasi'): ?>
                                            <span class="badge badge-warning-soft small">Menunggu</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger-soft small"><?= htmlspecialchars($h['status_pembayaran']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($h['status_pembayaran'] === 'Lunas'): ?>
                                            <a href="<?= base_url('modules/pembayaran/cetak.php?id=' . $h['id_pembayaran']) ?>" target="_blank" class="btn btn-sm btn-outline-dark py-0 px-2">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once BASEPATH . '/includes/footer.php'; ?>

