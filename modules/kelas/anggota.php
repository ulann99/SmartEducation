<?php
/**
 * Kelola Anggota Siswa Kelas
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';

require_roles(['superadmin', 'admin']);

$id_kelas = (int)($_GET['id'] ?? 0);

if ($id_kelas <= 0) {
    set_flash('danger', 'ID Kelas tidak ditemukan!');
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

// ========================================
// AMBIL DATA KELAS
// ========================================

$stmt_kls = $pdo->prepare("
    SELECT k.*, p.nama_paket, t.nama_tutor, r.nama_ruangan
    FROM kelas k
    JOIN paket_belajar p ON k.id_paket = p.id_paket
    JOIN tutor t ON k.id_tutor = t.id_tutor
    LEFT JOIN ruangan r ON k.id_ruangan = r.id_ruangan
    WHERE k.id_kelas = :id_kelas
");

$stmt_kls->execute([':id_kelas' => $id_kelas]);
$kelas = $stmt_kls->fetch(PDO::FETCH_ASSOC);

if (!$kelas) {
    set_flash('danger', 'Data kelas tidak ditemukan!');
    header('Location: ' . base_url('modules/kelas/index.php'));
    exit;
}

// ========================================
// TAMBAH SISWA KE KELAS
// ========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'tambah_anggota'
) {
    $id_siswa = (int)($_POST['id_siswa'] ?? 0);
    $tanggal_gabung = trim($_POST['tanggal_gabung'] ?? date('Y-m-d'));

    if ($id_siswa <= 0) {
        set_flash('danger', 'Silakan pilih siswa terlebih dahulu!');
    } else {
        try {
            $stmt_check = $pdo->prepare("
                SELECT id_anggota
                FROM anggota_kelas
                WHERE id_kelas = :id_kelas
                AND id_siswa = :id_siswa
                LIMIT 1
            ");

            $stmt_check->execute([
                ':id_kelas' => $id_kelas,
                ':id_siswa' => $id_siswa
            ]);

            if ($stmt_check->fetch()) {
                set_flash('warning', 'Siswa tersebut sudah terdaftar di kelas ini!');
            } else {
                $stmt_capacity = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM anggota_kelas
                    WHERE id_kelas = :id_kelas
                ");

                $stmt_capacity->execute([':id_kelas' => $id_kelas]);
                $jumlah_anggota = (int)$stmt_capacity->fetchColumn();

                if ($jumlah_anggota >= (int)$kelas['max_siswa']) {
                    set_flash('warning', 'Kapasitas kelas sudah penuh!');
                } else {
                    $stmt_add = $pdo->prepare("
                        INSERT INTO anggota_kelas
                        (id_kelas, id_siswa, tanggal_gabung)
                        VALUES
                        (:id_kelas, :id_siswa, :tanggal_gabung)
                    ");

                    $stmt_add->execute([
                        ':id_kelas' => $id_kelas,
                        ':id_siswa' => $id_siswa,
                        ':tanggal_gabung' => $tanggal_gabung
                    ]);

                    set_flash('success', 'Siswa berhasil ditambahkan ke kelas!');
                }
            }
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal menambahkan siswa ke kelas: ' . $e->getMessage());
        }
    }

    header('Location: ' . base_url('modules/kelas/anggota.php?id=' . $id_kelas));
    exit;
}

// ========================================
// KELUARKAN SISWA DARI KELAS
// ========================================

if (isset($_GET['hapus_anggota'])) {
    $id_anggota = (int)$_GET['hapus_anggota'];

    try {
        $stmt_del = $pdo->prepare("
            DELETE FROM anggota_kelas
            WHERE id_anggota = :id_anggota
            AND id_kelas = :id_kelas
        ");

        $stmt_del->execute([
            ':id_anggota' => $id_anggota,
            ':id_kelas' => $id_kelas
        ]);

        set_flash('success', 'Siswa berhasil dikeluarkan dari kelas!');
    } catch (PDOException $e) {
        set_flash('danger', 'Gagal mengeluarkan siswa: ' . $e->getMessage());
    }

    header('Location: ' . base_url('modules/kelas/anggota.php?id=' . $id_kelas));
    exit;
}

// ========================================
// AMBIL DAFTAR ANGGOTA KELAS
// ========================================

$stmt_members = $pdo->prepare("
    SELECT
        ak.id_anggota,
        ak.tanggal_gabung,
        s.id_siswa,
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.no_telepon,
        s.status_siswa
    FROM anggota_kelas ak
    JOIN siswa s ON ak.id_siswa = s.id_siswa
    WHERE ak.id_kelas = :id_kelas
    ORDER BY s.nama_siswa ASC
");

$stmt_members->execute([':id_kelas' => $id_kelas]);
$members = $stmt_members->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// AMBIL SISWA YANG BELUM TERDAFTAR
// ========================================

$stmt_avail = $pdo->prepare("
    SELECT id_siswa, nis, nama_siswa
    FROM siswa
    WHERE status_siswa = 'Aktif'
    AND id_siswa NOT IN (
        SELECT id_siswa
        FROM anggota_kelas
        WHERE id_kelas = :id_kelas
    )
    ORDER BY nama_siswa ASC
");

$stmt_avail->execute([':id_kelas' => $id_kelas]);
$available_students = $stmt_avail->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// INFORMASI HALAMAN
// ========================================

$current_page = 'kelas';
$page_title = 'Anggota Kelas ' . $kelas['nama_kelas'];

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';

$jumlah_anggota = count($members);
$kapasitas = max(1, (int)$kelas['max_siswa']);
$pct = min(100, round(($jumlah_anggota / $kapasitas) * 100));
?>

<style>
/* =====================================
   HALAMAN ANGGOTA KELAS
===================================== */

.anggota-page {
    width: 100%;
    padding: 24px 20px 30px !important;
    box-sizing: border-box;
    text-align: left !important;
}

.anggota-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    width: 100%;
    margin: 0 0 24px !important;
    padding: 0 !important;
}

.anggota-header-left {
    flex: 1;
    min-width: 0;
    text-align: left !important;
    padding: 0 !important;
    margin: 0 !important;
}

/* TOMBOL KEMBALI - PERBAIKAN UTAMA */

.btn-kembali-kelas {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 10px 17px !important;
    margin: 0 0 14px !important;
    background: #258879 !important;
    border: 1px solid #258879 !important;
    border-radius: 8px !important;
    color: #ffffff !important;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none !important;
    cursor: pointer;
    transition: all .2s ease;
}

.btn-kembali-kelas:hover {
    background: #1d7165 !important;
    border-color: #1d7165 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(37, 136, 121, .18);
}

.btn-kembali-kelas i {
    color: #ffffff !important;
}

/* JUDUL DAN INFORMASI KELAS */

.anggota-title {
    margin: 0 0 8px;
    color: #173f3b;
    font-size: 24px;
    font-weight: 700;
    line-height: 1.4;
    text-align: left;
}

.anggota-info {
    margin: 0;
    color: #778982;
    font-size: 12px;
    line-height: 1.9;
    text-align: left;
}

.anggota-info strong {
    color: #425c55;
    font-weight: 600;
}

/* TOMBOL MASUKKAN SISWA */

.btn-masukkan-siswa {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 10px 17px;
    border: 1px solid #258879;
    border-radius: 8px;
    background: #258879;
    color: #fff;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    transition: .2s ease;
}

.btn-masukkan-siswa:hover {
    background: #1d7165;
    border-color: #1d7165;
    color: #fff;
}

/* KARTU */

.anggota-page .card {
    border: 1px solid #edf1ef !important;
    border-radius: 12px;
    overflow: hidden;
}

.anggota-page .progress {
    background: #e9eef0;
    border-radius: 20px;
}

.anggota-page .progress-bar {
    border-radius: 20px;
}

/* TABEL */

.anggota-page .table {
    min-width: 850px;
    margin-bottom: 0;
}

.anggota-page .table thead th {
    padding: 13px 12px;
    background: #eaf9f6;
    color: #244b45;
    font-size: 11px;
    font-weight: 700;
    border-bottom: 1px solid #e2eeeb;
    white-space: nowrap;
}

.anggota-page .table tbody td {
    padding: 13px 12px;
    color: #425c55;
    font-size: 12px;
    vertical-align: middle;
    border-bottom: 1px solid #edf1ef;
}

.anggota-page .table tbody tr:last-child td {
    border-bottom: none;
}

.anggota-page .table tbody tr:hover {
    background: #f8fcfb;
}

/* TOMBOL KELUARKAN */

.btn-keluarkan {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 36px;
    padding: 7px 13px;
    border: 1px solid #dc3545;
    border-radius: 7px;
    background: #fff;
    color: #dc3545;
    font-family: inherit;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    white-space: nowrap;
    transition: .2s ease;
}

.btn-keluarkan:hover {
    background: #dc3545;
    color: #fff;
}

/* =====================================
   MODAL TAMBAH ANGGOTA
===================================== */

#modalTambahAnggota .modal-content {
    border: none;
    border-radius: 13px;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0,0,0,.12);
}

#modalTambahAnggota .modal-header {
    padding: 18px 22px;
    background: #E8FDFF;
    border-bottom: 1px solid #e3efed;
}

#modalTambahAnggota .modal-title {
    color: #173f3b;
    font-size: 17px;
    font-weight: 700;
}

#modalTambahAnggota .modal-body {
    padding: 22px;
}

#modalTambahAnggota .modal-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    padding: 16px 22px;
    border-top: 1px solid #e5eeeb;
}

#modalTambahAnggota .form-label {
    color: #31544d;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}

#modalTambahAnggota .form-control,
#modalTambahAnggota .form-select {
    min-height: 42px;
    border: 1px solid #d8e8e3;
    border-radius: 8px;
    font-family: inherit;
    font-size: 13px;
}

#modalTambahAnggota .form-control:focus,
#modalTambahAnggota .form-select:focus {
    border-color: #98EDE1;
    box-shadow: 0 0 0 3px rgba(152,237,225,.2);
}

/* TOMBOL MODAL */

#modalTambahAnggota .modal-footer .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-width: 105px;
    min-height: 40px;
    padding: 9px 16px;
    border-radius: 7px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: .2s ease;
}

#modalTambahAnggota .btn-batal {
    background: #6c757d;
    color: #fff;
    border: 1px solid #6c757d;
}

#modalTambahAnggota .btn-batal:hover {
    background: #565e64;
    border-color: #565e64;
    color: #fff;
}

#modalTambahAnggota .btn-simpan-anggota {
    background: #258879;
    color: #fff;
    border: 1px solid #258879;
}

#modalTambahAnggota .btn-simpan-anggota:hover {
    background: #1d7165;
    border-color: #1d7165;
    color: #fff;
}

#modalTambahAnggota .btn:disabled {
    opacity: .55;
    cursor: not-allowed;
}

/* RESPONSIVE */

@media (max-width: 768px) {
    .anggota-page {
        width: 100%;
        padding: 16px 12px !important;
    }

    .anggota-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .anggota-title {
        font-size: 20px;
    }

    .btn-masukkan-siswa {
        width: 100%;
    }

    #modalTambahAnggota .modal-footer {
        flex-wrap: wrap;
    }
}
</style>

<div class="anggota-page">

    <!-- HEADER HALAMAN -->

    <div class="anggota-header">

        <div class="anggota-header-left">

            <a href="<?= base_url('modules/kelas/index.php') ?>"
               class="btn-kembali-kelas">
                <i class="bi bi-arrow-left"></i>
                Kembali ke Daftar Kelas
            </a>

            <h1 class="anggota-title">
                Kelola Anggota Kelas:
                <?= htmlspecialchars($kelas['nama_kelas']) ?>
            </h1>

            <p class="anggota-info">
                Kode:
                <strong><?= htmlspecialchars($kelas['kode_kelas']) ?></strong>

                <span class="mx-1">|</span>

                Paket:
                <strong><?= htmlspecialchars($kelas['nama_paket']) ?></strong>

                <span class="mx-1">|</span>

                Tutor:
                <strong><?= htmlspecialchars($kelas['nama_tutor']) ?></strong>

                <span class="mx-1">|</span>

                Ruangan:
                <strong><?= htmlspecialchars($kelas['nama_ruangan'] ?? 'Belum ditentukan') ?></strong>
            </p>

        </div>

        <button type="button"
                class="btn-masukkan-siswa"
                data-bs-toggle="modal"
                data-bs-target="#modalTambahAnggota">
            <i class="bi bi-person-plus-fill"></i>
            Masukkan Siswa ke Kelas
        </button>

    </div>

    <!-- KAPASITAS KELAS -->

    <div class="card mb-4 shadow-sm">
        <div class="card-body p-3">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold text-dark">
                    Kapasitas Kelas:
                    <?= $jumlah_anggota ?> /
                    <?= (int)$kelas['max_siswa'] ?> Siswa
                </span>

                <span class="badge <?= ($pct >= 100) ? 'badge-danger-soft' : 'badge-mint' ?>">
                    <?= $pct ?>% Terisi
                </span>
            </div>

            <div class="progress" style="height: 10px;">
                <div class="progress-bar <?= ($pct >= 100) ? 'bg-danger' : 'bg-mint-dark' ?>"
                     role="progressbar"
                     style="width: <?= $pct ?>%;"
                     aria-valuenow="<?= $pct ?>"
                     aria-valuemin="0"
                     aria-valuemax="100">
                </div>
            </div>

        </div>
    </div>

    <!-- DAFTAR ANGGOTA KELAS -->

    <div class="card shadow-sm">

        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold text-mint-deep">
                <i class="bi bi-people-fill me-2 text-mint"></i>
                Daftar Siswa Terdaftar di Kelas Ini
                (<?= $jumlah_anggota ?>)
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Jenis Kelamin</th>
                        <th>No. Telepon / WA</th>
                        <th>Tanggal Bergabung</th>
                        <th class="text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($members)): ?>

                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-3 d-block mb-2"></i>
                                Belum ada siswa yang dimasukkan ke kelas ini.
                                <br>
                                Klik tombol "Masukkan Siswa ke Kelas" untuk menambahkan anggota.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php $no = 1; ?>

                        <?php foreach ($members as $m): ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        <?= htmlspecialchars($m['nis']) ?>
                                    </span>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($m['nama_siswa']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($m['jenis_kelamin'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($m['no_telepon'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= format_tanggal($m['tanggal_gabung']) ?>
                                </td>

                                <td class="text-center">
                                    <a href="<?= base_url(
                                        'modules/kelas/anggota.php?id=' .
                                        $id_kelas .
                                        '&hapus_anggota=' .
                                        $m['id_anggota']
                                    ) ?>"
                                       class="btn-keluarkan btn-confirm-delete"
                                       data-item-name="<?= htmlspecialchars($m['nama_siswa']) ?>"
                                       title="Keluarkan dari Kelas">

                                        <i class="bi bi-x-circle"></i>
                                        Keluarkan
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL TAMBAH ANGGOTA -->

<div class="modal fade"
     id="modalTambahAnggota"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form action="" method="POST">

                <input type="hidden"
                       name="action"
                       value="tambah_anggota">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus me-2"></i>
                        Tambah Siswa ke Kelas
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">
                            Pilih Siswa
                            <span class="text-danger">*</span>
                        </label>

                        <select name="id_siswa"
                                class="form-select"
                                required>

                            <option value="">
                                -- Pilih Siswa Aktif --
                            </option>

                            <?php foreach ($available_students as $as): ?>
                                <option value="<?= (int)$as['id_siswa'] ?>">
                                    <?= htmlspecialchars($as['nama_siswa']) ?>
                                    (NIS: <?= htmlspecialchars($as['nis']) ?>)
                                </option>
                            <?php endforeach; ?>

                        </select>

                        <?php if (empty($available_students)): ?>
                            <div class="form-text text-warning mt-1">
                                Semua siswa aktif sudah terdaftar di kelas ini.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Tanggal Bergabung
                        </label>

                        <input type="date"
                               name="tanggal_gabung"
                               class="form-control"
                               value="<?= date('Y-m-d') ?>"
                               required>
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-batal"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit"
                            class="btn btn-simpan-anggota"
                            <?= empty($available_students) ? 'disabled' : '' ?>>
                        <i class="bi bi-check-circle"></i>
                        Masukkan ke Kelas
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<?php require_once BASEPATH . '/includes/footer.php'; ?>
