<?php
/**
 * Paket Belajar
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_login();

$current_page = 'paket';
$page_title = 'Paket Bimbingan Belajar';
$user = current_user();
$is_admin = ($user['role'] === 'superadmin' || $user['role'] === 'admin');

// Fetch all packages with enrolled student count
$sql = "
    SELECT p.*, COUNT(s.id_siswa) AS jumlah_siswa
    FROM paket_belajar p
    LEFT JOIN siswa s ON p.id_paket = s.id_paket
    GROUP BY p.id_paket
    ORDER BY p.id_paket ASC
";
$paket_list = $pdo->query($sql)->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="h3 fw-bold">Paket Bimbingan Belajar</h1>
        <p class="text-muted">Pilihan paket belajar terstruktur, komprehensif, dan berorientasi prestasi.</p>
    </div>
    <?php if ($is_admin): ?>
        <button type="button" class="btn btn-mint" data-bs-toggle="modal" data-bs-target="#modalTambahPaket">
            <i class="bi bi-plus-circle me-1"></i> Tambah Paket Baru
        </button>
    <?php endif; ?>
</div>

<!-- Package Cards Grid -->
<div class="row g-4 mb-4">
    <?php foreach ($paket_list as $pk): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm position-relative">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <span class="badge bg-mint-soft text-dark px-3 py-1 rounded-pill fw-semibold">
                        <?= htmlspecialchars($pk['jenjang']) ?>
                    </span>
                    <span class="badge <?= ($pk['status'] === 'Aktif') ? 'badge-mint' : 'bg-secondary' ?>">
                        <?= htmlspecialchars($pk['status']) ?>
                    </span>
                </div>
                <div class="card-body pt-0">
                    <h5 class="fw-bold text-mint-deep mb-2"><?= htmlspecialchars($pk['nama_paket']) ?></h5>
                    <div class="d-flex align-items-baseline mb-3">
                        <span class="fs-4 fw-bolder text-dark-mint"><?= format_rupiah($pk['harga']) ?></span>
                        <span class="text-muted small ms-1">/ bulan</span>
                    </div>

                    <p class="text-muted small mb-3" style="min-height: 48px;">
                        <?= nl2br(htmlspecialchars($pk['deskripsi'] ?? 'Program bimbingan belajar intensif.')) ?>
                    </p>

                    <div class="p-3 bg-light rounded-3 mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted"><i class="bi bi-clock me-1 text-mint"></i> Pertemuan:</span>
                            <strong class="text-dark"><?= $pk['pertemuan_per_minggu'] ?>x / Minggu</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted"><i class="bi bi-calendar3 me-1 text-mint"></i> Durasi:</span>
                            <strong class="text-dark"><?= $pk['durasi_bulan'] ?> Bulan</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted"><i class="bi bi-people me-1 text-mint"></i> Siswa Terdaftar:</span>
                            <strong class="text-dark-mint"><?= $pk['jumlah_siswa'] ?> Siswa</strong>
                        </div>
                    </div>

                    <?php if ($is_admin): ?>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalEditPaket<?= $pk['id_paket'] ?>">
                                <i class="bi bi-pencil me-1"></i> Edit Paket
                            </button>
                            <a href="<?= base_url('modules/paket/delete.php?id=' . $pk['id_paket']) ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" data-item-name="<?= htmlspecialchars($pk['nama_paket']) ?>">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($is_admin): ?>
            <!-- Modal Edit Paket -->
            <div class="modal fade" id="modalEditPaket<?= $pk['id_paket'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="<?= base_url('modules/paket/edit.php') ?>" method="POST">
                            <input type="hidden" name="id_paket" value="<?= $pk['id_paket'] ?>">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Paket Belajar</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nama Paket Belajar <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_paket" class="form-control" value="<?= htmlspecialchars($pk['nama_paket']) ?>" required>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Jenjang Pendidikan <span class="text-danger">*</span></label>
                                        <input type="text" name="jenjang" class="form-control" value="<?= htmlspecialchars($pk['jenjang']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Harga per Bulan (Rp) <span class="text-danger">*</span></label>
                                        <input type="number" step="1000" name="harga" class="form-control" value="<?= $pk['harga'] ?>" required>
                                    </div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Pertemuan / Minggu</label>
                                        <input type="number" name="pertemuan_per_minggu" class="form-control" value="<?= $pk['pertemuan_per_minggu'] ?>" min="1" max="7" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Durasi (Bulan)</label>
                                        <input type="number" name="durasi_bulan" class="form-control" value="<?= $pk['durasi_bulan'] ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status Paket</label>
                                    <select name="status" class="form-select">
                                        <option value="Aktif" <?= ($pk['status'] === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                                        <option value="Non-Aktif" <?= ($pk['status'] === 'Non-Aktif') ? 'selected' : '' ?>>Non-Aktif</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi & Keunggulan Paket</label>
                                    <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($pk['deskripsi'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-mint"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php if ($is_admin): ?>
    <!-- Modal Tambah Paket Baru -->
    <div class="modal fade" id="modalTambahPaket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= base_url('modules/paket/create.php') ?>" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Paket Belajar Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Paket Belajar <span class="text-danger">*</span></label>
                            <input type="text" name="nama_paket" class="form-control" placeholder="Contoh: Paket Intensif Kedinasan 2026" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Jenjang Pendidikan <span class="text-danger">*</span></label>
                                <input type="text" name="jenjang" class="form-control" placeholder="SD / SMP / SMA / Umum" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga per Bulan (Rp) <span class="text-danger">*</span></label>
                                <input type="number" step="1000" name="harga" class="form-control" placeholder="500000" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Pertemuan / Minggu</label>
                                <input type="number" name="pertemuan_per_minggu" class="form-control" value="3" min="1" max="7" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Durasi (Bulan)</label>
                                <input type="number" name="durasi_bulan" class="form-control" value="1" min="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Paket</label>
                            <select name="status" class="form-select">
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi & Keunggulan Paket</label>
                            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Fasilitas, kurikulum pengajaran, dan target kelulusan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-mint"><i class="bi bi-check-circle me-1"></i> Simpan Paket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once BASEPATH . '/includes/footer.php'; ?>

