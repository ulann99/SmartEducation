<?php
/**
 * Data Kelas
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'kelas';
$page_title = 'Pengelolaan Data Kelas';

// Fetch classes with related package, tutor, room, and student count
$sql = "
    SELECT 
        k.*, 
        p.nama_paket, 
        t.nama_tutor, 
        t.keahlian, 
        r.nama_ruangan, 
        r.kapasitas AS kapasitas_ruangan,
        COUNT(ak.id_siswa) AS total_siswa_terdaftar
    FROM kelas k
    JOIN paket_belajar p ON k.id_paket = p.id_paket
    JOIN tutor t ON k.id_tutor = t.id_tutor
    LEFT JOIN ruangan r ON k.id_ruangan = r.id_ruangan
    LEFT JOIN anggota_kelas ak ON k.id_kelas = ak.id_kelas
    GROUP BY k.id_kelas
    ORDER BY k.id_kelas ASC
";
$kelas_list = $pdo->query($sql)->fetchAll();

// Dropdowns data
$paket_list = $pdo->query("SELECT * FROM paket_belajar WHERE status = 'Aktif' ORDER BY nama_paket ASC")->fetchAll();
$tutor_list = $pdo->query("SELECT * FROM tutor WHERE status_tutor = 'Aktif' ORDER BY nama_tutor ASC")->fetchAll();
$ruangan_list = $pdo->query("SELECT * FROM ruangan WHERE status_ruangan = 'Tersedia' ORDER BY nama_ruangan ASC")->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="h3 fw-bold">Pengelolaan Data Kelas</h1>
        <p class="text-muted">Kelola kelas bimbel, integrasi tutor, paket belajar, ruangan, dan daftar anggota siswa.</p>
    </div>
    <button type="button" class="btn btn-mint" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
        <i class="bi bi-plus-circle me-1"></i> Buat Kelas Baru
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold text-mint-deep">
            <i class="bi bi-collection me-2 text-mint"></i>Daftar Kelas Bimbingan (Total: <?= count($kelas_list) ?>)
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode & Nama Kelas</th>
                    <th>Paket Belajar</th>
                    <th>Tutor Pengajar</th>
                    <th>Ruangan Default</th>
                    <th>Waktu / Sesi</th>
                    <th>Kapasitas / Siswa</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kelas_list)): ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">Belum ada data kelas yang terdaftar.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($kelas_list as $k): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong class="text-dark d-block"><?= htmlspecialchars($k['nama_kelas']) ?></strong>
                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($k['kode_kelas']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-mint-soft text-dark"><?= htmlspecialchars($k['nama_paket']) ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($k['nama_tutor']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($k['keahlian']) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($k['nama_ruangan'] ?? 'Belum ditentukan') ?>
                            </td>
                            <td>
                                <?php if ($k['hari']): ?>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($k['hari']) ?></span>
                                    <div class="small text-muted"><?= substr($k['jam_mulai'], 0, 5) ?> - <?= substr($k['jam_selesai'], 0, 5) ?></div>
                                <?php else: ?>
                                    <span class="text-muted small">Via Penjadwalan CSP</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <strong class="text-mint-dark"><?= $k['total_siswa_terdaftar'] ?></strong>
                                    <span class="text-muted small">/ <?= $k['max_siswa'] ?> Siswa</span>
                                </div>
                                <div class="progress" style="height: 5px; width: 70px;">
                                    <?php $pct = min(100, round(($k['total_siswa_terdaftar'] / max(1, $k['max_siswa'])) * 100)); ?>
                                    <div class="progress-bar bg-mint-dark" style="width: <?= $pct ?>%;"></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= ($k['status_kelas'] === 'Aktif') ? 'badge-mint' : 'bg-secondary' ?>">
                                    <?= htmlspecialchars($k['status_kelas']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?= base_url('modules/kelas/anggota.php?id=' . $k['id_kelas']) ?>" class="btn btn-outline-success" title="Kelola Siswa Kelas">
                                        <i class="bi bi-people"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditKelas<?= $k['id_kelas'] ?>" title="Edit Kelas">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('modules/kelas/delete.php?id=' . $k['id_kelas']) ?>" class="btn btn-outline-danger btn-confirm-delete" data-item-name="<?= htmlspecialchars($k['nama_kelas']) ?>" title="Hapus Kelas">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Edit Kelas -->
                        <div class="modal fade" id="modalEditKelas<?= $k['id_kelas'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="<?= base_url('modules/kelas/edit.php') ?>" method="POST">
                                        <input type="hidden" name="id_kelas" value="<?= $k['id_kelas'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Kelas</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-5">
                                                    <label class="form-label">Kode Kelas <span class="text-danger">*</span></label>
                                                    <input type="text" name="kode_kelas" class="form-control" value="<?= htmlspecialchars($k['kode_kelas']) ?>" required>
                                                </div>
                                                <div class="col-md-7">
                                                    <label class="form-label">Nama Kelas <span class="text-danger">*</span></label>
                                                    <input type="text" name="nama_kelas" class="form-control" value="<?= htmlspecialchars($k['nama_kelas']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Paket Belajar <span class="text-danger">*</span></label>
                                                    <select name="id_paket" class="form-select" required>
                                                        <?php foreach ($paket_list as $pk): ?>
                                                            <option value="<?= $pk['id_paket'] ?>" <?= ($k['id_paket'] == $pk['id_paket']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($pk['nama_paket']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Tutor Pengajar <span class="text-danger">*</span></label>
                                                    <select name="id_tutor" class="form-select" required>
                                                        <?php foreach ($tutor_list as $t): ?>
                                                            <option value="<?= $t['id_tutor'] ?>" <?= ($k['id_tutor'] == $t['id_tutor']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($t['nama_tutor']) ?> (<?= htmlspecialchars($t['keahlian']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Ruangan</label>
                                                    <select name="id_ruangan" class="form-select">
                                                        <option value="">-- Ditentukan Otomatis oleh CSP --</option>
                                                        <?php foreach ($ruangan_list as $r): ?>
                                                            <option value="<?= $r['id_ruangan'] ?>" <?= ($k['id_ruangan'] == $r['id_ruangan']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($r['nama_ruangan']) ?> (Kapasitas: <?= $r['kapasitas'] ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Kapasitas Maksimal Siswa</label>
                                                    <input type="number" name="max_siswa" class="form-control" value="<?= $k['max_siswa'] ?>" min="1" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Hari Belajar</label>
                                                    <select name="hari" class="form-select">
                                                        <option value="">-- Alokasikan via CSP --</option>
                                                        <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h): ?>
                                                            <option value="<?= $h ?>" <?= ($k['hari'] === $h) ? 'selected' : '' ?>><?= $h ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Jam Mulai</label>
                                                    <input type="time" name="jam_mulai" class="form-control" value="<?= htmlspecialchars($k['jam_mulai'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Jam Selesai</label>
                                                    <input type="time" name="jam_selesai" class="form-control" value="<?= htmlspecialchars($k['jam_selesai'] ?? '') ?>">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Status Kelas</label>
                                                    <select name="status_kelas" class="form-select">
                                                        <option value="Aktif" <?= ($k['status_kelas'] === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                                                        <option value="Selesai" <?= ($k['status_kelas'] === 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                                                        <option value="Batal" <?= ($k['status_kelas'] === 'Batal') ? 'selected' : '' ?>>Batal</option>
                                                    </select>
                                                </div>
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

<!-- Modal Tambah Kelas Baru -->
<div class="modal fade" id="modalTambahKelas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('modules/kelas/create.php') ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Buat Kelas Belajar Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Kode Kelas <span class="text-danger">*</span></label>
                            <input type="text" name="kode_kelas" class="form-control" placeholder="KLS-2026-007" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Nama Kelas <span class="text-danger">*</span></label>
                            <input type="text" name="nama_kelas" class="form-control" placeholder="Contoh: Kelas Kedinasan Bravo" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Paket Belajar <span class="text-danger">*</span></label>
                            <select name="id_paket" class="form-select" required>
                                <option value="">-- Pilih Paket Belajar --</option>
                                <?php foreach ($paket_list as $pk): ?>
                                    <option value="<?= $pk['id_paket'] ?>">
                                        <?= htmlspecialchars($pk['nama_paket']) ?> (<?= format_rupiah($pk['harga']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tutor Pengajar <span class="text-danger">*</span></label>
                            <select name="id_tutor" class="form-select" required>
                                <option value="">-- Pilih Tutor Pengajar --</option>
                                <?php foreach ($tutor_list as $t): ?>
                                    <option value="<?= $t['id_tutor'] ?>">
                                        <?= htmlspecialchars($t['nama_tutor']) ?> (<?= htmlspecialchars($t['keahlian']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ruangan Awal</label>
                            <select name="id_ruangan" class="form-select">
                                <option value="">-- Otomatis Ditentukan CSP --</option>
                                <?php foreach ($ruangan_list as $r): ?>
                                    <option value="<?= $r['id_ruangan'] ?>">
                                        <?= htmlspecialchars($r['nama_ruangan']) ?> (Kapasitas: <?= $r['kapasitas'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maksimal Siswa</label>
                            <input type="number" name="max_siswa" class="form-control" value="15" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hari Belajar</label>
                            <select name="hari" class="form-select">
                                <option value="">-- Alokasikan via CSP --</option>
                                <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h): ?>
                                    <option value="<?= $h ?>"><?= $h ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status Kelas</label>
                            <select name="status_kelas" class="form-select">
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Batal">Batal</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-mint"><i class="bi bi-check-circle me-1"></i> Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASEPATH . '/includes/footer.php'; ?>

