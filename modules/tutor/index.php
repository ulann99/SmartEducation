<?php
/**
 * Data Tutor
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'tutor';
$page_title = 'Pengelolaan Data Tutor';

// Fetch tutors with class counts
$sql = "
    SELECT t.*, COUNT(k.id_kelas) AS total_kelas
    FROM tutor t
    LEFT JOIN kelas k ON t.id_tutor = k.id_tutor
    GROUP BY t.id_tutor
    ORDER BY t.id_tutor ASC
";
$tutor_list = $pdo->query($sql)->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="h3 fw-bold">Pengelolaan Data Tutor</h1>
        <p class="text-muted">Kelola data tutor pengajar profesional bimbingan belajar Smart Education Parepare.</p>
    </div>
    <button type="button" class="btn btn-mint" data-bs-toggle="modal" data-bs-target="#modalTambahTutor">
        <i class="bi bi-person-plus-fill me-1"></i> Tambah Tutor Baru
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold text-mint-deep">
            <i class="bi bi-person-badge me-2 text-mint"></i>Daftar Tutor Pengajar (Total: <?= count($tutor_list) ?>)
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>NIP / Kode</th>
                    <th>Nama Tutor</th>
                    <th>Bidang Keahlian</th>
                    <th>No. Telepon / Email</th>
                    <th>Kelas Diampu</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tutor_list)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada data tutor pengajar.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($tutor_list as $t): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($t['nip']) ?></span></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($t['nama_tutor']) ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-mint-soft text-dark px-2 py-1"><?= htmlspecialchars($t['keahlian']) ?></span>
                            </td>
                            <td>
                                <div><i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($t['no_telepon'] ?? '-') ?></div>
                                <small class="text-muted"><?= htmlspecialchars($t['email'] ?? '-') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-info-emphasis"><?= $t['total_kelas'] ?> Kelas</span>
                            </td>
                            <td>
                                <?php if ($t['status_tutor'] === 'Aktif'): ?>
                                    <span class="badge badge-mint">Aktif</span>
                                <?php elseif ($t['status_tutor'] === 'Cuti'): ?>
                                    <span class="badge badge-warning-soft">Cuti</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Non-Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditTutor<?= $t['id_tutor'] ?>" title="Edit Tutor">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('modules/tutor/delete.php?id=' . $t['id_tutor']) ?>" class="btn btn-outline-danger btn-confirm-delete" data-item-name="<?= htmlspecialchars($t['nama_tutor']) ?>" title="Hapus Tutor">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Edit Tutor -->
                        <div class="modal fade" id="modalEditTutor<?= $t['id_tutor'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="<?= base_url('modules/tutor/edit.php') ?>" method="POST">
                                        <input type="hidden" name="id_tutor" value="<?= $t['id_tutor'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Tutor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">NIP / Kode Tutor <span class="text-danger">*</span></label>
                                                <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($t['nip']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                                <input type="text" name="nama_tutor" class="form-control" value="<?= htmlspecialchars($t['nama_tutor']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Bidang Keahlian / Mata Pelajaran <span class="text-danger">*</span></label>
                                                <input type="text" name="keahlian" class="form-control" value="<?= htmlspecialchars($t['keahlian']) ?>" required>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">No. Telepon / WhatsApp</label>
                                                    <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($t['no_telepon'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Status Tutor</label>
                                                    <select name="status_tutor" class="form-select">
                                                        <option value="Aktif" <?= ($t['status_tutor'] === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                                                        <option value="Cuti" <?= ($t['status_tutor'] === 'Cuti') ? 'selected' : '' ?>>Cuti</option>
                                                        <option value="Non-Aktif" <?= ($t['status_tutor'] === 'Non-Aktif') ? 'selected' : '' ?>>Non-Aktif</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($t['email'] ?? '') ?>">
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Tutor Baru -->
<div class="modal fade" id="modalTambahTutor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('modules/tutor/create.php') ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Tambah Tutor Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">NIP / Kode Tutor <span class="text-danger">*</span></label>
                        <input type="text" name="nip" class="form-control" placeholder="Contoh: TUTOR006" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                        <input type="text" name="nama_tutor" class="form-control" placeholder="Nama Lengkap Tutor, S.Pd., M.Si." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bidang Keahlian / Mata Pelajaran <span class="text-danger">*</span></label>
                        <input type="text" name="keahlian" class="form-control" placeholder="Contoh: Kimia Organik & TPS SNBT" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">No. Telepon / WhatsApp</label>
                            <input type="text" name="no_telepon" class="form-control" placeholder="0852-xxxx-xxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Tutor</label>
                            <select name="status_tutor" class="form-select">
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Cuti">Cuti</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="tutor@smarteducation.sch.id">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-mint"><i class="bi bi-check-circle me-1"></i> Simpan Tutor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASEPATH . '/includes/footer.php'; ?>

