<?php
/**
 * Data Ruangan
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'ruangan';
$page_title = 'Pengelolaan Data Ruangan';

// Fetch rooms with classes count
$sql = "
    SELECT r.*, COUNT(k.id_kelas) AS total_kelas_ruangan
    FROM ruangan r
    LEFT JOIN kelas k ON r.id_ruangan = k.id_ruangan
    GROUP BY r.id_ruangan
    ORDER BY r.id_ruangan ASC
";
$ruangan_list = $pdo->query($sql)->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="h3 fw-bold">Pengelolaan Data Ruangan</h1>
        <p class="text-muted">Kelola kapasitas, fasilitas, dan ketersediaan ruang belajar bimbingan belajar.</p>
    </div>
    <button type="button" class="btn btn-mint" data-bs-toggle="modal" data-bs-target="#modalTambahRuangan">
        <i class="bi bi-door-open-fill me-1"></i> Tambah Ruangan Baru
    </button>
</div>

<div class="row g-4">
    <?php foreach ($ruangan_list as $r): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                        <?= htmlspecialchars($r['kode_ruangan']) ?>
                    </span>
                    <?php if ($r['status_ruangan'] === 'Tersedia'): ?>
                        <span class="badge badge-mint"><i class="bi bi-check-circle me-1"></i>Tersedia</span>
                    <?php elseif ($r['status_ruangan'] === 'Perbaikan'): ?>
                        <span class="badge badge-warning-soft"><i class="bi bi-tools me-1"></i>Perbaikan</span>
                    <?php else: ?>
                        <span class="badge badge-danger-soft">Tidak Tersedia</span>
                    <?php endif; ?>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="stat-icon mint mb-0" style="width: 48px; height: 48px; font-size: 1.4rem;">
                            <i class="bi bi-door-closed-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-mint-deep mb-0"><?= htmlspecialchars($r['nama_ruangan']) ?></h5>
                            <small class="text-muted"><i class="bi bi-people me-1"></i>Kapasitas: <strong><?= $r['kapasitas'] ?> Siswa</strong></small>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3">
                        <small class="text-muted d-block fw-semibold mb-1">Fasilitas Ruangan:</small>
                        <p class="mb-0 small text-dark">
                            <i class="bi bi-stars text-warning me-1"></i><?= htmlspecialchars($r['fasilitas'] ?? 'AC, Whiteboard, Wifi') ?>
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalEditRuangan<?= $r['id_ruangan'] ?>">
                            <i class="bi bi-pencil me-1"></i> Edit Ruangan
                        </button>
                        <a href="<?= base_url('modules/ruangan/delete.php?id=' . $r['id_ruangan']) ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" data-item-name="<?= htmlspecialchars($r['nama_ruangan']) ?>">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edit Ruangan -->
        <div class="modal fade" id="modalEditRuangan<?= $r['id_ruangan'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="<?= base_url('modules/ruangan/edit.php') ?>" method="POST">
                        <input type="hidden" name="id_ruangan" value="<?= $r['id_ruangan'] ?>">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Ruangan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-3">
                                <div class="col-md-5">
                                    <label class="form-label">Kode Ruangan <span class="text-danger">*</span></label>
                                    <input type="text" name="kode_ruangan" class="form-control" value="<?= htmlspecialchars($r['kode_ruangan']) ?>" required>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label">Nama Ruangan <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_ruangan" class="form-control" value="<?= htmlspecialchars($r['nama_ruangan']) ?>" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kapasitas Maksimal Siswa <span class="text-danger">*</span></label>
                                    <input type="number" name="kapasitas" class="form-control" value="<?= $r['kapasitas'] ?>" min="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status Ketersediaan</label>
                                    <select name="status_ruangan" class="form-select">
                                        <option value="Tersedia" <?= ($r['status_ruangan'] === 'Tersedia') ? 'selected' : '' ?>>Tersedia</option>
                                        <option value="Perbaikan" <?= ($r['status_ruangan'] === 'Perbaikan') ? 'selected' : '' ?>>Perbaikan</option>
                                        <option value="Tidak Tersedia" <?= ($r['status_ruangan'] === 'Tidak Tersedia') ? 'selected' : '' ?>>Tidak Tersedia</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fasilitas Ruangan</label>
                                <textarea name="fasilitas" class="form-control" rows="2"><?= htmlspecialchars($r['fasilitas'] ?? '') ?></textarea>
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

    <?php endforeach; ?>
</div>

<!-- Modal Tambah Ruangan Baru -->
<div class="modal fade" id="modalTambahRuangan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('modules/ruangan/create.php') ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-door-open me-2"></i>Tambah Ruangan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label">Kode Ruangan <span class="text-danger">*</span></label>
                            <input type="text" name="kode_ruangan" class="form-control" placeholder="R-06" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Nama Ruangan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_ruangan" class="form-control" placeholder="Ruang Stephen Hawking" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kapasitas Siswa <span class="text-danger">*</span></label>
                            <input type="number" name="kapasitas" class="form-control" value="15" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Ketersediaan</label>
                            <select name="status_ruangan" class="form-select">
                                <option value="Tersedia" selected>Tersedia</option>
                                <option value="Perbaikan">Perbaikan</option>
                                <option value="Tidak Tersedia">Tidak Tersedia</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fasilitas Ruangan</label>
                        <textarea name="fasilitas" class="form-control" rows="2" placeholder="AC, Smart TV, Whiteboard, Sound System, Wifi"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-mint"><i class="bi bi-check-circle me-1"></i> Simpan Ruangan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASEPATH . '/includes/footer.php'; ?>

