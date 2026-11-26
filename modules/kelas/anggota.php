
<?php
/**
 * KELOLA ANGGOTA KELAS
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

// CSRF Token
if (empty($_SESSION['csrf_kelas'])) {
    $_SESSION['csrf_kelas'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_kelas'];
$id_kelas = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id_kelas) {
    set_flash('danger', 'ID kelas tidak valid.');
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

$redirect = base_url('modules/kelas/anggota.php?id=' . $id_kelas);

// ==========================================
// PROSES TAMBAH DAN HAPUS ANGGOTA
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_kelas']) ||
        !hash_equals($_SESSION['csrf_kelas'], $_POST['csrf_token'])
    ) {
        set_flash('danger', 'Permintaan tidak valid.');
        header("Location: $redirect");
        exit;
    }

    $aksi = $_POST['aksi'] ?? '';

    try {

        // TAMBAH ANGGOTA
        if ($aksi === 'tambah') {

            $id_siswa = filter_var(
                $_POST['id_siswa'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id_siswa) {
                throw new Exception('Silakan pilih siswa.');
            }

            // Pastikan siswa aktif
            $cek = $pdo->prepare("
                SELECT id_siswa
                FROM siswa
                WHERE id_siswa = ? AND status_siswa = 'Aktif'
            ");
            $cek->execute([$id_siswa]);

            if (!$cek->fetch()) {
                throw new Exception('Siswa tidak ditemukan atau tidak aktif.');
            }

            // Cek apakah siswa sudah menjadi anggota
            $cek = $pdo->prepare("
                SELECT id_anggota
                FROM anggota_kelas
                WHERE id_kelas = ? AND id_siswa = ?
            ");
            $cek->execute([$id_kelas, $id_siswa]);

            if ($cek->fetch()) {
                throw new Exception('Siswa sudah terdaftar di kelas ini.');
            }

            // Simpan anggota
            $stmt = $pdo->prepare("
                INSERT INTO anggota_kelas
                    (id_kelas, id_siswa, tanggal_gabung)
                VALUES (?, ?, CURDATE())
            ");
            $stmt->execute([$id_kelas, $id_siswa]);

            set_flash('success', 'Siswa berhasil ditambahkan ke kelas.');

        // HAPUS ANGGOTA
        } elseif ($aksi === 'hapus') {

            $id_anggota = filter_var(
                $_POST['id_anggota'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id_anggota) {
                throw new Exception('Data anggota tidak valid.');
            }

            $stmt = $pdo->prepare("
                DELETE FROM anggota_kelas
                WHERE id_anggota = ? AND id_kelas = ?
            ");
            $stmt->execute([$id_anggota, $id_kelas]);

            if ($stmt->rowCount() < 1) {
                throw new Exception('Anggota tidak ditemukan.');
            }

            set_flash('success', 'Siswa berhasil dikeluarkan dari kelas.');

        } else {
            throw new Exception('Aksi tidak dikenali.');
        }

    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }

    header("Location: $redirect");
    exit;
}

// ==========================================
// INFORMASI KELAS
// ==========================================

$stmt = $pdo->prepare("
    SELECT
        k.*,
        t.nama_tutor,
        r.nama_ruangan
    FROM kelas k
    LEFT JOIN tutor t ON k.id_tutor = t.id_tutor
    LEFT JOIN ruangan r ON k.id_ruangan = r.id_ruangan
    WHERE k.id_kelas = ?
");
$stmt->execute([$id_kelas]);
$kelas = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kelas) {
    set_flash('danger', 'Kelas tidak ditemukan.');
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

// ==========================================
// DAFTAR ANGGOTA KELAS
// ==========================================

$stmt = $pdo->prepare("
    SELECT
        ak.id_anggota,
        ak.tanggal_gabung,
        s.id_siswa,
        s.nama_lengkap,
        s.sekolah_asal,
        s.kelas AS tingkat_kelas,
        s.no_hp
    FROM anggota_kelas ak
    JOIN siswa s ON ak.id_siswa = s.id_siswa
    WHERE ak.id_kelas = ?
    ORDER BY ak.id_anggota DESC
");
$stmt->execute([$id_kelas]);
$anggota = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// DAFTAR SISWA YANG BELUM MENJADI ANGGOTA
// ==========================================

$stmt = $pdo->prepare("
    SELECT
        s.id_siswa,
        s.nama_lengkap,
        s.sekolah_asal,
        s.kelas
    FROM siswa s
    WHERE s.status_siswa = 'Aktif'
    AND NOT EXISTS (
        SELECT 1
        FROM anggota_kelas ak
        WHERE ak.id_siswa = s.id_siswa
        AND ak.id_kelas = ?
    )
    ORDER BY s.nama_lengkap ASC
");
$stmt->execute([$id_kelas]);
$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_page = 'kelas';
$page_title = 'Anggota Kelas';

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<style>
/* HALAMAN ANGGOTA */
.anggota-page {
    padding: 22px 24px;
}

/* KARTU */
.anggota-card {
    background: #ffffff;
    border: 1px solid #e5efec;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 20px;
}

/* HEADER KELAS */
.anggota-header {
    background: #e8fdff;
    padding: 18px 20px;
    color: #194b44;
}

.anggota-header h5 {
    font-weight: 700;
    margin: 0 0 6px;
}

/* KONTEN */
.anggota-content {
    padding: 20px;
}

/* TABEL */
.anggota-table th {
    background: #effbf8;
    font-size: 12px;
    color: #194b44;
    white-space: nowrap;
}

.anggota-table td {
    font-size: 12px;
    vertical-align: middle;
}

/* TOMBOL KEMBALI */
.btn-kembali-kelas {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 11px 20px;
    background: #258879;
    color: #ffffff !important;
    border: 1px solid #258879;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none !important;
    transition: all 0.2s ease;
}

.btn-kembali-kelas:hover {
    background: #1d7165;
    border-color: #1d7165;
    color: #ffffff !important;
    box-shadow: 0 4px 10px rgba(37, 136, 121, 0.2);
    transform: translateY(-1px);
}

/* TOMBOL TAMBAH */
.btn-tambah-anggota {
    background: #258879;
    border-color: #258879;
    color: #ffffff;
    font-weight: 600;
}

.btn-tambah-anggota:hover {
    background: #1d7165;
    border-color: #1d7165;
    color: #ffffff;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .anggota-page {
        padding: 15px;
    }

    .anggota-content {
        padding: 15px;
    }
}
</style>

<div class="anggota-page">

    <!-- TOMBOL KEMBALI -->
    <div class="mb-3">
        <a href="<?= base_url('modules/kelas/index.php') ?>"
           class="btn-kembali-kelas">
            <i class="bi bi-arrow-left"></i>
            Kembali ke Data Kelas
        </a>
    </div>

    <!-- INFORMASI KELAS -->
    <div class="anggota-card">

        <div class="anggota-header">
            <h5>
                <i class="bi bi-people me-2"></i>
                <?= htmlspecialchars($kelas['nama_kelas']) ?>
            </h5>

            <small>
                Tutor:
                <?= htmlspecialchars($kelas['nama_tutor'] ?? 'Belum ditentukan') ?>

                &nbsp; | &nbsp;

                Ruangan:
                <?= htmlspecialchars($kelas['nama_ruangan'] ?? 'Belum ditentukan') ?>
            </small>
        </div>

        <div class="anggota-content">

            <h6 class="fw-bold mb-3">
                Tambah Siswa ke Kelas
            </h6>

            <form method="POST" class="row g-2">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= htmlspecialchars($csrf) ?>">

                <input type="hidden"
                       name="aksi"
                       value="tambah">

                <div class="col-md-9">
                    <select name="id_siswa"
                            class="form-select"
                            required>

                        <option value="">-- Pilih Siswa --</option>

                        <?php foreach ($siswa_list as $s): ?>
                            <option value="<?= (int)$s['id_siswa'] ?>">
                                <?= htmlspecialchars($s['nama_lengkap']) ?>
                                - <?= htmlspecialchars($s['kelas']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit"
                            class="btn btn-tambah-anggota w-100">

                        <i class="bi bi-plus-circle me-1"></i>
                        Tambah Siswa

                    </button>
                </div>

            </form>

            <?php if (empty($siswa_list)): ?>
                <small class="text-muted d-block mt-2">
                    Tidak ada siswa aktif lain yang tersedia untuk ditambahkan.
                </small>
            <?php endif; ?>

        </div>
    </div>

    <!-- DAFTAR ANGGOTA -->
    <div class="anggota-card">

        <div class="anggota-content">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h6 class="fw-bold mb-0">
                    Daftar Anggota Kelas
                </h6>

                <span class="badge text-bg-info">
                    <?= count($anggota) ?> Siswa
                </span>

            </div>

            <div class="table-responsive">

                <table class="table table-hover anggota-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>Sekolah Asal</th>
                            <th>Kelas</th>
                            <th>Tanggal Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($anggota)): ?>

                        <tr>
                            <td colspan="6"
                                class="text-center text-muted py-5">

                                <i class="bi bi-people fs-2 d-block mb-2"></i>

                                Belum ada siswa yang tergabung dalam kelas ini.

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php $no = 1; ?>

                        <?php foreach ($anggota as $a): ?>

                            <tr>

                                <td>
                                    <?= $no++ ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($a['nama_lengkap']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($a['sekolah_asal']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($a['tingkat_kelas']) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd-m-Y',
                                        strtotime($a['tanggal_gabung'])
                                    ) ?>
                                </td>

                                <td>

                                    <form method="POST"
                                          onsubmit="return confirm('Keluarkan siswa ini dari kelas?')">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= htmlspecialchars($csrf) ?>">

                                        <input type="hidden"
                                               name="aksi"
                                               value="hapus">

                                        <input type="hidden"
                                               name="id_anggota"
                                               value="<?= (int)$a['id_anggota'] ?>">

                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger">

                                            <i class="bi bi-person-dash"></i>
                                            Keluarkan

                                        </button>

                                    </form>

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
