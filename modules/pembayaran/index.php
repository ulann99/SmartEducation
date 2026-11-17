<?php
/**
 * Pengelolaan Pembayaran
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'pembayaran';
$page_title = 'Pengelolaan Pembayaran Siswa';

// Filters
$status_filter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT p.*, s.nama_siswa, s.nis, s.no_telepon, pk.nama_paket, u.nama_lengkap AS verifikator_nama
    FROM pembayaran p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN paket_belajar pk ON p.id_paket = pk.id_paket
    LEFT JOIN users u ON p.diverifikasi_oleh = u.id
    WHERE 1=1
";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND p.status_pembayaran = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (s.nama_siswa LIKE :search OR s.nis LIKE :search OR p.kode_transaksi LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY p.id_pembayaran DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pembayaran_list = $stmt->fetchAll();

// Dropdowns for New Payment Modal
$siswa_list = $pdo->query("SELECT id_siswa, nis, nama_siswa, id_paket FROM siswa WHERE status_siswa = 'Aktif' ORDER BY nama_siswa ASC")->fetchAll();
$paket_list = $pdo->query("SELECT * FROM paket_belajar WHERE status = 'Aktif' ORDER BY nama_paket ASC")->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="h3 fw-bold">Pengelolaan Pembayaran Siswa</h1>
        <p class="text-muted">Pencatatan pembayaran tunai, verifikasi bukti QRIS & transfer, serta penerbitan kwitansi.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-mint" data-bs-toggle="modal" data-bs-target="#modalTambahPembayaran">
            <i class="bi bi-cash me-1"></i> Input Pembayaran Baru
        </button>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nomor transaksi, nama siswa, NIS..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">-- Semua Status --</option>
                    <option value="Menunggu Konfirmasi" <?= ($status_filter === 'Menunggu Konfirmasi') ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                    <option value="Lunas" <?= ($status_filter === 'Lunas') ? 'selected' : '' ?>>Lunas</option>
                    <option value="Ditolak" <?= ($status_filter === 'Ditolak') ? 'selected' : '' ?>>Ditolak</option>
                    <option value="Belum Lunas" <?= ($status_filter === 'Belum Lunas') ? 'selected' : '' ?>>Belum Lunas</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark-mint w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($status_filter)): ?>
                    <a href="<?= base_url('modules/pembayaran/index.php') ?>" class="btn btn-outline-secondary" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold text-mint-deep">
            <i class="bi bi-receipt-cutoff me-2 text-mint"></i>Daftar Transaksi Pembayaran (<?= count($pembayaran_list) ?>)
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode Transaksi</th>
                    <th>Nama Siswa & Paket</th>
                    <th>Nominal</th>
                    <th>Tanggal Bayar</th>
                    <th>Metode</th>
                    <th>Bukti</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pembayaran_list)): ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">Belum ada data pembayaran yang sesuai kriteria.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($pembayaran_list as $p): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong class="text-dark font-monospace"><?= htmlspecialchars($p['kode_transaksi']) ?></strong>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['nama_siswa']) ?></div>
                                <small class="text-muted">NIS: <?= htmlspecialchars($p['nis']) ?> | <?= htmlspecialchars($p['nama_paket']) ?></small>
                            </td>
                            <td class="fw-bold text-mint-deep fs-6"><?= format_rupiah($p['nominal']) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['tanggal_bayar'])) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-<?= ($p['metode_pembayaran'] === 'QRIS' ? 'qr-code' : ($p['metode_pembayaran'] === 'Tunai' ? 'cash' : 'bank')) ?> me-1 text-mint"></i>
                                    <?= htmlspecialchars($p['metode_pembayaran']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($p['bukti_pembayaran'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalBukti<?= $p['id_pembayaran'] ?>">
                                        <i class="bi bi-image me-1"></i> Lihat
                                    </button>

                                    <!-- Modal Bukti Pembayaran -->
                                    <div class="modal fade" id="modalBukti<?= $p['id_pembayaran'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold">Bukti Pembayaran: <?= htmlspecialchars($p['kode_transaksi']) ?></h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-center p-4">
                                                    <img src="<?= base_url('assets/images/qris-dummy.svg') ?>" alt="Bukti Pembayaran" class="img-fluid rounded border shadow-sm" style="max-height: 400px;">
                                                    <div class="mt-3 small text-muted">
                                                        Siswa: <strong><?= htmlspecialchars($p['nama_siswa']) ?></strong> (<?= format_rupiah($p['nominal']) ?>)
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['status_pembayaran'] === 'Lunas'): ?>
                                    <span class="badge badge-mint"><i class="bi bi-check-circle-fill me-1"></i>Lunas</span>
                                <?php elseif ($p['status_pembayaran'] === 'Menunggu Konfirmasi'): ?>
                                    <span class="badge badge-warning-soft"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>
                                <?php elseif ($p['status_pembayaran'] === 'Ditolak'): ?>
                                    <span class="badge badge-danger-soft"><i class="bi bi-x-circle me-1"></i>Ditolak</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum Lunas</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <?php if ($p['status_pembayaran'] === 'Menunggu Konfirmasi'): ?>
                                        <!-- Verify Modal Button -->
                                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalVerifikasi<?= $p['id_pembayaran'] ?>" title="Verifikasi Pembayaran">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Cetak Kwitansi -->
                                    <a href="<?= base_url('modules/pembayaran/cetak.php?id=' . $p['id_pembayaran']) ?>" target="_blank" class="btn btn-outline-dark" title="Cetak Kwitansi">
                                        <i class="bi bi-printer"></i>
                                    </a>

                                    <!-- Delete Button -->
                                    <a href="<?= base_url('modules/pembayaran/delete.php?id=' . $p['id_pembayaran']) ?>" class="btn btn-outline-danger btn-confirm-delete" data-item-name="<?= htmlspecialchars($p['kode_transaksi']) ?>" title="Hapus Transaksi">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Verifikasi Pembayaran -->
                        <div class="modal fade" id="modalVerifikasi<?= $p['id_pembayaran'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="<?= base_url('modules/pembayaran/verify.php') ?>" method="POST">
                                        <input type="hidden" name="id_pembayaran" value="<?= $p['id_pembayaran'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold"><i class="bi bi-check2-circle me-2"></i>Verifikasi Transaksi</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="p-3 bg-light rounded-3 mb-3 small">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Kode Transaksi:</span>
                                                    <strong><?= htmlspecialchars($p['kode_transaksi']) ?></strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Nama Siswa:</span>
                                                    <strong><?= htmlspecialchars($p['nama_siswa']) ?></strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Nominal:</span>
                                                    <strong class="text-mint-dark"><?= format_rupiah($p['nominal']) ?></strong>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">Metode Pembayaran:</span>
                                                    <strong><?= htmlspecialchars($p['metode_pembayaran']) ?></strong>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Tindakan Verifikasi <span class="text-danger">*</span></label>
                                                <select name="status_pembayaran" class="form-select" required>
                                                    <option value="Lunas">Setujui & Tandai Lunas</option>
                                                    <option value="Ditolak">Tolak Pembayaran (Bukti Tidak Valid / Dana Belum Masuk)</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Catatan / Keterangan Verifikasi</label>
                                                <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Dana telah diterima via QRIS Mandiri..."><?= htmlspecialchars($p['keterangan'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-mint"><i class="bi bi-save me-1"></i> Simpan Status</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pembayaran Baru (Admin Input) -->
<div class="modal fade" id="modalTambahPembayaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('modules/pembayaran/create.php') ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash me-2"></i>Input Pembayaran Siswa Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pilih Siswa <span class="text-danger">*</span></label>
                            <select name="id_siswa" id="selectSiswa" class="form-select" required>
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($siswa_list as $s): ?>
                                    <option value="<?= $s['id_siswa'] ?>" data-paket="<?= $s['id_paket'] ?>">
                                        <?= htmlspecialchars($s['nama_siswa']) ?> (NIS: <?= htmlspecialchars($s['nis']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Paket Bimbingan Belajar <span class="text-danger">*</span></label>
                            <select name="id_paket" id="selectPaket" class="form-select" required>
                                <option value="">-- Pilih Paket --</option>
                                <?php foreach ($paket_list as $pk): ?>
                                    <option value="<?= $pk['id_paket'] ?>" data-harga="<?= $pk['harga'] ?>">
                                        <?= htmlspecialchars($pk['nama_paket']) ?> (<?= format_rupiah($pk['harga']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nominal Pembayaran (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1000" name="nominal" id="inputNominal" class="form-control" placeholder="750000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pembayaran <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="metode_pembayaran" class="form-select" required>
                                <option value="Tunai" selected>Tunai (Cash di Kantor)</option>
                                <option value="QRIS">QRIS</option>
                                <option value="Transfer Bank">Transfer Bank Mandiri</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Pembayaran</label>
                            <select name="status_pembayaran" class="form-select" required>
                                <option value="Lunas" selected>Lunas</option>
                                <option value="Menunggu Konfirmasi">Menunggu Konfirmasi</option>
                                <option value="Belum Lunas">Belum Lunas</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload Bukti Transaksi (Opsional)</label>
                            <input type="file" name="bukti_pembayaran" class="form-control" accept="image/*">
                            <div class="form-text">Format: JPG, PNG, WEBP. Maks. 3MB.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catatan / Keterangan Transaksi</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Pembayaran SPP Bulan November 2026">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-mint"><i class="bi bi-check-circle me-1"></i> Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-fill price when packet is selected
document.getElementById('selectPaket')?.addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const harga = selectedOption.getAttribute('data-harga');
    if (harga) {
        document.getElementById('inputNominal').value = harga;
    }
});

// Auto-select student's package when student is chosen
document.getElementById('selectSiswa')?.addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const paketId = selectedOption.getAttribute('data-paket');
    const selectPaket = document.getElementById('selectPaket');
    if (paketId && selectPaket) {
        selectPaket.value = paketId;
        const opt = selectPaket.querySelector(`option[value="${paketId}"]`);
        if (opt) {
            document.getElementById('inputNominal').value = opt.getAttribute('data-harga') || '';
        }
    }
});
</script>

<?php require_once BASEPATH . '/includes/footer.php'; ?>

