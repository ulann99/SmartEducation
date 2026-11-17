<?php
/**
 * Data Siswa
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'siswa';
$page_title = 'Pengelolaan Data Siswa';

// Handle Search and Filter
$search = trim($_GET['search'] ?? '');
$filter_paket = trim($_GET['paket'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$sql = "
    SELECT s.*, p.nama_paket, p.harga
    FROM siswa s
    LEFT JOIN paket_belajar p ON s.id_paket = p.id_paket
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (s.nama_siswa LIKE :search OR s.nis LIKE :search OR s.no_telepon LIKE :search OR s.email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filter_paket)) {
    $sql .= " AND s.id_paket = :id_paket";
    $params[':id_paket'] = $filter_paket;
}

if (!empty($filter_status)) {
    $sql .= " AND s.status_siswa = :status_siswa";
    $params[':status_siswa'] = $filter_status;
}

$sql .= " ORDER BY s.id_siswa DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$siswa_list = $stmt->fetchAll();

// Fetch Packages for Dropdown
$paket_list = $pdo->query("SELECT * FROM paket_belajar WHERE status = 'Aktif' ORDER BY nama_paket ASC")->fetchAll();

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="h3 fw-bold">Pengelolaan Data Siswa</h1>
        <p class="text-muted">Kelola data seluruh siswa bimbingan belajar Smart Education Parepare.</p>
    </div>
    <button type="button" class="btn btn-mint" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">
        <i class="bi bi-person-plus-fill me-1"></i> Tambah Siswa Baru
    </button>
</div>

<!-- Search & Filter Card -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari berdasarkan nama, NIS, no. HP..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="paket" class="form-select">
                    <option value="">-- Semua Paket Belajar --</option>
                    <?php foreach ($paket_list as $pk): ?>
                        <option value="<?= $pk['id_paket'] ?>" <?= ($filter_paket == $pk['id_paket']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pk['nama_paket']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">-- Status --</option>
                    <option value="Aktif" <?= ($filter_status === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                    <option value="Alumni" <?= ($filter_status === 'Alumni') ? 'selected' : '' ?>>Alumni</option>
                    <option value="Non-Aktif" <?= ($filter_status === 'Non-Aktif') ? 'selected' : '' ?>>Non-Aktif</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark-mint w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($filter_paket) || !empty($filter_status)): ?>
                    <a href="<?= base_url('modules/siswa/index.php') ?>" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold text-mint-deep">
            <i class="bi bi-people me-2 text-mint"></i>Daftar Siswa (Total: <?= count($siswa_list) ?>)
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Gender</th>
                    <th>Paket Belajar</th>
                    <th>No. Telepon</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($siswa_list)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Tidak ada data siswa ditemukan sesuai kriteria pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($siswa_list as $s): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($s['nis']) ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="stat-icon mint mb-0" style="width: 32px; height: 32px; font-size: 0.9rem;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div>
                                        <strong class="text-dark"><?= htmlspecialchars($s['nama_siswa']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars($s['email'] ?? '-') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?= ($s['jenis_kelamin'] === 'Laki-laki') ? '<span class="badge bg-info-subtle text-info-emphasis">L</span> Laki-laki' : '<span class="badge bg-danger-subtle text-danger-emphasis">P</span> Perempuan' ?>
                            </td>
                            <td>
                                <span class="fw-medium text-dark-mint"><?= htmlspecialchars($s['nama_paket'] ?? 'Belum Pilih Paket') ?></span>
                                <?php if ($s['harga']): ?>
                                    <div class="small text-muted"><?= format_rupiah($s['harga']) ?> / bln</div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($s['no_telepon'] ?? '-') ?></td>
                            <td>
                                <?php if ($s['status_siswa'] === 'Aktif'): ?>
                                    <span class="badge badge-mint">Aktif</span>
                                <?php elseif ($s['status_siswa'] === 'Alumni'): ?>
                                    <span class="badge bg-primary-subtle text-primary">Alumni</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Non-Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- Detail Button -->
                                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalDetailSiswa<?= $s['id_siswa'] ?>" title="Detail Siswa">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <!-- Edit Button -->
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditSiswa<?= $s['id_siswa'] ?>" title="Edit Siswa">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <!-- Delete Button -->
                                    <a href="<?= base_url('modules/siswa/delete.php?id=' . $s['id_siswa']) ?>" class="btn btn-outline-danger btn-confirm-delete" data-item-name="<?= htmlspecialchars($s['nama_siswa']) ?>" title="Hapus Siswa">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Detail Siswa -->
                        <div class="modal fade" id="modalDetailSiswa<?= $s['id_siswa'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Detail Siswa</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="text-center mb-3">
                                            <div class="stat-icon mint mx-auto mb-2" style="width: 60px; height: 60px; font-size: 1.8rem;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($s['nama_siswa']) ?></h5>
                                            <span class="badge bg-light text-dark border">NIS: <?= htmlspecialchars($s['nis']) ?></span>
                                        </div>
                                        <ul class="list-group list-group-flush small">
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span class="text-muted">Jenis Kelamin:</span>
                                                <strong><?= htmlspecialchars($s['jenis_kelamin']) ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span class="text-muted">Paket Belajar:</span>
                                                <strong class="text-mint-dark"><?= htmlspecialchars($s['nama_paket'] ?? '-') ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span class="text-muted">Nomor WhatsApp / HP:</span>
                                                <strong><?= htmlspecialchars($s['no_telepon'] ?? '-') ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span class="text-muted">Email:</span>
                                                <strong><?= htmlspecialchars($s['email'] ?? '-') ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span class="text-muted">Alamat Domisili:</span>
                                                <span class="text-end fw-medium"><?= nl2br(htmlspecialchars($s['alamat'] ?? '-')) ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span class="text-muted">Tanggal Pendaftaran:</span>
                                                <strong><?= format_tanggal($s['tanggal_daftar']) ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span class="text-muted">Status:</span>
                                                <span class="badge badge-mint"><?= htmlspecialchars($s['status_siswa']) ?></span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Edit Siswa -->
                        <div class="modal fade" id="modalEditSiswa<?= $s['id_siswa'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="<?= base_url('modules/siswa/edit.php') ?>" method="POST">
                                        <input type="hidden" name="id_siswa" value="<?= $s['id_siswa'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Siswa</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">NIS (Nomor Induk Siswa) <span class="text-danger">*</span></label>
                                                    <input type="text" name="nis" class="form-control" value="<?= htmlspecialchars($s['nis']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                                                    <input type="text" name="nama_siswa" class="form-control" value="<?= htmlspecialchars($s['nama_siswa']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                                    <select name="jenis_kelamin" class="form-select" required>
                                                        <option value="Laki-laki" <?= ($s['jenis_kelamin'] === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                                        <option value="Perempuan" <?= ($s['jenis_kelamin'] === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Paket Belajar</label>
                                                    <select name="id_paket" class="form-select">
                                                        <option value="">-- Pilih Paket Belajar --</option>
                                                        <?php foreach ($paket_list as $pk): ?>
                                                            <option value="<?= $pk['id_paket'] ?>" <?= ($s['id_paket'] == $pk['id_paket']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($pk['nama_paket']) ?> (<?= format_rupiah($pk['harga']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">No. Telepon / WhatsApp</label>
                                                    <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($s['no_telepon'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($s['email'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Status Siswa</label>
                                                    <select name="status_siswa" class="form-select">
                                                        <option value="Aktif" <?= ($s['status_siswa'] === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                                                        <option value="Alumni" <?= ($s['status_siswa'] === 'Alumni') ? 'selected' : '' ?>>Alumni</option>
                                                        <option value="Non-Aktif" <?= ($s['status_siswa'] === 'Non-Aktif') ? 'selected' : '' ?>>Non-Aktif</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Tanggal Pendaftaran</label>
                                                    <input type="date" name="tanggal_daftar" class="form-control" value="<?= htmlspecialchars($s['tanggal_daftar']) ?>" required>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Alamat Lengkap</label>
                                                    <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($s['alamat'] ?? '') ?></textarea>
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

<!-- Modal Tambah Siswa Baru -->
<div class="modal fade" id="modalTambahSiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('modules/siswa/create.php') ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Tambah Siswa Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NIS (Nomor Induk Siswa) <span class="text-danger">*</span></label>
                            <input type="text" name="nis" class="form-control" placeholder="Contoh: 2026011" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                            <input type="text" name="nama_siswa" class="form-control" placeholder="Masukkan nama siswa" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Paket Belajar</label>
                            <select name="id_paket" class="form-select">
                                <option value="">-- Pilih Paket Belajar --</option>
                                <?php foreach ($paket_list as $pk): ?>
                                    <option value="<?= $pk['id_paket'] ?>">
                                        <?= htmlspecialchars($pk['nama_paket']) ?> (<?= format_rupiah($pk['harga']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. Telepon / WhatsApp</label>
                            <input type="text" name="no_telepon" class="form-control" placeholder="0812-xxxx-xxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="siswa@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pendaftaran</label>
                            <input type="date" name="tanggal_daftar" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Siswa</label>
                            <select name="status_siswa" class="form-select">
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Alumni">Alumni</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Jl. ... No. ..., Kota Parepare"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check p-2 bg-light rounded border">
                                <input class="form-check-input ms-1" type="checkbox" name="buat_akun" id="buat_akun" value="1" checked>
                                <label class="form-check-label ms-2 fw-medium text-dark" for="buat_akun">
                                    Buatkan Akun Login Siswa Secara Otomatis
                                </label>
                                <div class="form-text ms-4">Akun akan dibuatkan dengan Username = NIS, Kata sandi default = <code>siswa123</code>.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-mint"><i class="bi bi-check-circle me-1"></i> Simpan Data Siswa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASEPATH . '/includes/footer.php'; ?>

