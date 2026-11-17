<?php
/**
 * Kelola Anggota Siswa Kelas
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$id_kelas = (int)($_GET['id'] ?? 0);

if (empty($id_kelas)) {
    set_flash('danger', 'ID Kelas tidak ditemukan!');
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

// Fetch class details
$stmt_kls = $pdo->prepare("
    SELECT k.*, p.nama_paket, t.nama_tutor, r.nama_ruangan
    FROM kelas k
    JOIN paket_belajar p ON k.id_paket = p.id_paket
    JOIN tutor t ON k.id_tutor = t.id_tutor
    LEFT JOIN ruangan r ON k.id_ruangan = r.id_ruangan
    WHERE k.id_kelas = :id_kelas
");
$stmt_kls->execute([':id_kelas' => $id_kelas]);
$kelas = $stmt_kls->fetch();

if (!$kelas) {
    set_flash('danger', 'Data kelas tidak ditemukan!');
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

// Handle Add Student to Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_anggota') {
    $id_siswa = (int)($_POST['id_siswa'] ?? 0);
    $tanggal_gabung = trim($_POST['tanggal_gabung'] ?? date('Y-m-d'));

    if ($id_siswa > 0) {
        try {
            // Check if already in class
            $stmt_check = $pdo->prepare("SELECT id_anggota FROM anggota_kelas WHERE id_kelas = :id_kelas AND id_siswa = :id_siswa");
            $stmt_check->execute([':id_kelas' => $id_kelas, ':id_siswa' => $id_siswa]);
            if ($stmt_check->fetch()) {
                set_flash('warning', 'Siswa tersebut sudah terdaftar di kelas ini!');
            } else {
                $stmt_add = $pdo->prepare("INSERT INTO anggota_kelas (id_kelas, id_siswa, tanggal_gabung) VALUES (:id_kelas, :id_siswa, :tanggal_gabung)");
                $stmt_add->execute([':id_kelas' => $id_kelas, ':id_siswa' => $id_siswa, ':tanggal_gabung' => $tanggal_gabung]);
                set_flash('success', 'Siswa berhasil ditambahkan ke kelas!');
            }
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal menambahkan siswa ke kelas: ' . $e->getMessage());
        }
    }
    header('Location: ' . base_url('modules/kelas/anggota.php?id=' . $id_kelas));
    exit;
}

// Handle Remove Student from Class
if (isset($_GET['hapus_anggota'])) {
    $id_anggota = (int)$_GET['hapus_anggota'];
    try {
        $stmt_del = $pdo->prepare("DELETE FROM anggota_kelas WHERE id_anggota = :id_anggota AND id_kelas = :id_kelas");
        $stmt_del->execute([':id_anggota' => $id_anggota, ':id_kelas' => $id_kelas]);
        set_flash('success', 'Siswa berhasil dikeluarkan dari kelas!');
    } catch (PDOException $e) {
        set_flash('danger', 'Gagal mengeluarkan siswa: ' . $e->getMessage());
    }
    header('Location: ' . base_url('modules/kelas/anggota.php?id=' . $id_kelas));
    exit;
}

// Fetch current members
$stmt_members = $pdo->prepare("
    SELECT ak.id_anggota, ak.tanggal_gabung, s.id_siswa, s.nis, s.nama_siswa, s.jenis_kelamin, s.no_telepon, s.status_siswa
    FROM anggota_kelas ak
    JOIN siswa s ON ak.id_siswa = s.id_siswa
    WHERE ak.id_kelas = :id_kelas
    ORDER BY s.nama_siswa ASC
");
$stmt_members->execute([':id_kelas' => $id_kelas]);
$members = $stmt_members->fetchAll();

// Fetch available students not yet in this class
$stmt_avail = $pdo->prepare("
    SELECT id_siswa, nis, nama_siswa 
    FROM siswa 
    WHERE status_siswa = 'Aktif' 
      AND id_siswa NOT IN (SELECT id_siswa FROM anggota_kelas WHERE id_kelas = :id_kelas)
    ORDER BY nama_siswa ASC
");
$stmt_avail->execute([':id_kelas' => $id_kelas]);
$available_students = $stmt_avail->fetchAll();

$current_page = 'kelas';
$page_title = 'Anggota Kelas ' . $kelas['nama_kelas'];

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div>
        <a href="<?= base_url('modules/kelas/index.php') ?>" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Kelas
        </a>
        <h1 class="h3 fw-bold">Kelola Anggota Kelas: <?= htmlspecialchars($kelas['nama_kelas']) ?></h1>
        <p class="text-muted">
            Kode: <span class="badge bg-light text-dark border"><?= htmlspecialchars($kelas['kode_kelas']) ?></span> |
            Paket: <strong><?= htmlspecialchars($kelas['nama_paket']) ?></strong> |
            Tutor: <strong><?= htmlspecialchars($kelas['nama_tutor']) ?></strong> |
            Ruang: <strong><?= htmlspecialchars($kelas['nama_ruangan'] ?? 'Belum ditentukan') ?></strong>
        </p>
    </div>
    <button type="button" class="btn btn-mint" data-bs-toggle="modal" data-bs-target="#modalTambahAnggota">
        <i class="bi bi-person-plus-fill me-1"></i> Masukkan Siswa ke Kelas
    </button>
</div>

<!-- Class Capacity Bar -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold text-dark">Kapasitas Kelas: <?= count($members) ?> / <?= $kelas['max_siswa'] ?> Siswa</span>
            <?php $pct = min(100, round((count($members) / max(1, $kelas['max_siswa'])) * 100)); ?>
            <span class="badge <?= ($pct >= 100) ? 'badge-danger-soft' : 'badge-mint' ?>"><?= $pct ?>% Terisi</span>
        </div>
        <div class="progress" style="height: 10px;">
            <div class="progress-bar <?= ($pct >= 100) ? 'bg-danger' : 'bg-mint-dark' ?>" role="progressbar" style="width: <?= $pct ?>%;"></div>
        </div>
    </div>
</div>

<!-- Members Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold text-mint-deep">
            <i class="bi bi-people-fill me-2 text-mint"></i>Daftar Siswa Terdaftar di Kelas Ini (<?= count($members) ?>)
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Jenis Kelamin</th>
                    <th>No. Telepon / WA</th>
                    <th>Tanggal Bergabung</th>
                    <th class="text-center" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada siswa yang dimasukkan ke kelas ini. Klik "Masukkan Siswa ke Kelas" di atas.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($members as $m): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($m['nis']) ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($m['nama_siswa']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($m['jenis_kelamin']) ?></td>
                            <td><?= htmlspecialchars($m['no_telepon'] ?? '-') ?></td>
                            <td><?= format_tanggal($m['tanggal_gabung']) ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('modules/kelas/anggota.php?id=' . $id_kelas . '&hapus_anggota=' . $m['id_anggota']) ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete" data-item-name="<?= htmlspecialchars($m['nama_siswa']) ?>" title="Keluarkan dari Kelas">
                                    <i class="bi bi-x-circle me-1"></i> Keluarkan
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Anggota -->
<div class="modal fade" id="modalTambahAnggota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="" method="POST">
                <input type="hidden" name="action" value="tambah_anggota">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Tambah Siswa ke Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Siswa <span class="text-danger">*</span></label>
                        <select name="id_siswa" class="form-select" required>
                            <option value="">-- Pilih Siswa Aktif --</option>
                            <?php foreach ($available_students as $as): ?>
                                <option value="<?= $as['id_siswa'] ?>">
                                    <?= htmlspecialchars($as['nama_siswa']) ?> (NIS: <?= htmlspecialchars($as['nis']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($available_students)): ?>
                            <div class="form-text text-warning mt-1">Semua siswa aktif sudah terdaftar di kelas ini.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Bergabung</label>
                        <input type="date" name="tanggal_gabung" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-mint" <?= empty($available_students) ? 'disabled' : '' ?>>
                        <i class="bi bi-check-circle me-1"></i> Masukkan ke Kelas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASEPATH . '/includes/footer.php'; ?>

