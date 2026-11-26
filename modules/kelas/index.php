
<?php
/**
 * PENGELOLAAN DATA KELAS
 * SMART EDUCATION PAREPARE
 */

define('BASEPATH', dirname(dirname(__DIR__)));
require_once BASEPATH . '/config/helper.php';
require_roles(['superadmin', 'admin']);

$current_page = 'kelas';
$page_title = 'Pengelolaan Data Kelas';

if (empty($_SESSION['csrf_kelas'])) {
    $_SESSION['csrf_kelas'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_kelas'];

// Ambil data kelas
$sql = "
    SELECT
        k.id_kelas,
        k.nama_kelas,
        k.id_tutor,
        k.id_ruangan,
        t.nama_tutor,
        t.keahlian,
        r.nama_ruangan,
        r.kapasitas,
        r.lokasi,
        (
            SELECT COUNT(*)
            FROM anggota_kelas ak
            WHERE ak.id_kelas = k.id_kelas
        ) AS total_siswa
    FROM kelas k
    LEFT JOIN tutor t ON k.id_tutor = t.id_tutor
    LEFT JOIN ruangan r ON k.id_ruangan = r.id_ruangan
    ORDER BY k.id_kelas ASC
";

$kelas_list = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Dropdown tutor
$tutor_list = $pdo->query("
    SELECT id_tutor, nama_tutor, keahlian
    FROM tutor
    WHERE status_tutor = 'Aktif'
    ORDER BY nama_tutor ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Dropdown ruangan
$ruangan_list = $pdo->query("
    SELECT id_ruangan, nama_ruangan, kapasitas, lokasi
    FROM ruangan
    WHERE status_ruangan = 'Aktif'
    ORDER BY nama_ruangan ASC
")->fetchAll(PDO::FETCH_ASSOC);

require_once BASEPATH . '/includes/header.php';
require_once BASEPATH . '/includes/sidebar.php';
require_once BASEPATH . '/includes/navbar.php';
?>

<style>
.kelas-page {
    padding: 22px 24px;
    color: #173f3b;
}

.kelas-toolbar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
}

.btn-kelas {
    background: #258879;
    color: white;
    border: 0;
    border-radius: 9px;
    padding: 11px 18px;
    font-weight: 600;
}

.btn-kelas:hover {
    background: #1d7165;
    color: white;
}

.kelas-card {
    background: white;
    border: 1px solid #edf2f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 3px 12px rgba(20,60,50,.05);
}

.kelas-card-header {
    padding: 18px 20px;
    border-bottom: 1px solid #dcece8;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.kelas-card-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 16px;
}

.kelas-table {
    min-width: 850px;
    margin: 0;
}

.kelas-table th {
    background: #effbf8;
    color: #315a52;
    padding: 14px;
    font-size: 12px;
}

.kelas-table td {
    padding: 14px;
    font-size: 12px;
    vertical-align: middle;
}

.kelas-badge {
    display: inline-block;
    background: #e8fdff;
    color: #258879;
    padding: 5px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.kelas-actions {
    display: flex;
    justify-content: center;
    gap: 6px;
}

.btn-kelas-icon {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid;
    border-radius: 7px;
    background: white;
    text-decoration: none;
    cursor: pointer;
}

.btn-anggota {
    color: #198754;
    border-color: #b9e6d3;
}

.btn-edit {
    color: #2878bd;
    border-color: #b9d8f5;
}

.btn-hapus {
    color: #dc3545;
    border-color: #f1c5ca;
}

.btn-hapus:hover {
    background: #dc3545;
    color: white;
}

.kelas-modal .modal-content {
    border: 0;
    border-radius: 15px;
    overflow: hidden;
}

.kelas-modal .modal-header {
    background: #e8fdff;
    color: #194b44;
}

.kelas-modal .modal-title {
    font-weight: 700;
}

.kelas-modal .form-label {
    font-size: 12px;
    font-weight: 600;
    color: #254b45;
}

.kelas-modal .form-control,
.kelas-modal .form-select {
    min-height: 42px;
    border-radius: 8px;
    font-size: 13px;
}

.kelas-empty {
    text-align: center;
    padding: 45px !important;
    color: #84958f;
}

.modal-hapus-icon {
    width: 75px;
    height: 75px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    background: #fff0f0;
    color: #dc3545;
    border-radius: 50%;
    font-size: 32px;
}

.btn-konfirmasi-hapus {
    background: #dc3545;
    color: white;
    border: 0;
    border-radius: 8px;
    padding: 10px 18px;
    font-weight: 600;
}

.btn-konfirmasi-hapus:hover {
    background: #bb2d3b;
    color: white;
}
</style>

<div class="kelas-page">

    <div class="kelas-toolbar">
        <button type="button"
                class="btn-kelas"
                data-bs-toggle="modal"
                data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i>
            Tambah Kelas
        </button>
    </div>

    <div class="kelas-card">

        <div class="kelas-card-header">
            <h5>
                <i class="bi bi-collection me-2"></i>
                Daftar Kelas Bimbingan
            </h5>

            <span class="kelas-badge">
                <?= count($kelas_list) ?> Kelas
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover kelas-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kelas</th>
                        <th>Tutor Pengajar</th>
                        <th>Ruangan</th>
                        <th>Kapasitas Ruangan</th>
                        <th>Jumlah Siswa</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($kelas_list)): ?>
                    <tr>
                        <td colspan="7" class="kelas-empty">
                            <i class="bi bi-collection fs-3 d-block mb-2"></i>
                            Belum ada kelas yang terdaftar.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php $no = 1; ?>

                    <?php foreach ($kelas_list as $k): ?>
                    <tr>
                        <td><?= $no++ ?></td>

                        <td>
                            <strong>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= htmlspecialchars($k['nama_tutor'] ?? 'Belum ditentukan') ?>

                            <?php if (!empty($k['keahlian'])): ?>
                                <small class="d-block text-muted">
                                    <?= htmlspecialchars($k['keahlian']) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($k['nama_ruangan'] ?? 'Belum ditentukan') ?>
                        </td>

                        <td>
                            <?= $k['kapasitas'] !== null
                                ? (int)$k['kapasitas'] . ' Siswa'
                                : '-' ?>
                        </td>

                        <td>
                            <span class="kelas-badge">
                                <?= (int)$k['total_siswa'] ?> Siswa
                            </span>
                        </td>

                        <td>
                            <div class="kelas-actions">

                                <a href="<?= base_url('modules/kelas/anggota.php?id=' . (int)$k['id_kelas']) ?>"
                                   class="btn-kelas-icon btn-anggota"
                                   title="Kelola Anggota">
                                    <i class="bi bi-people"></i>
                                </a>

                                <button type="button"
                                        class="btn-kelas-icon btn-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#edit<?= (int)$k['id_kelas'] ?>"
                                        title="Edit Kelas">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <!-- TOMBOL HAPUS -->
                                <button type="button"
                                        class="btn-kelas-icon btn-hapus"
                                        title="Hapus Kelas"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalHapusKelas"
                                        data-id="<?= (int)$k['id_kelas'] ?>"
                                        data-nama="<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-trash"></i>
                                </button>

                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL EDIT -->
<?php foreach ($kelas_list as $k): ?>
<div class="modal fade kelas-modal"
     id="edit<?= (int)$k['id_kelas'] ?>"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form action="<?= base_url('modules/kelas/edit.php') ?>"
                  method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Kelas</h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($csrf) ?>">

                    <input type="hidden"
                           name="id_kelas"
                           value="<?= (int)$k['id_kelas'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Nama Kelas</label>

                        <input type="text"
                               name="nama_kelas"
                               class="form-control"
                               value="<?= htmlspecialchars($k['nama_kelas']) ?>"
                               maxlength="100"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tutor Pengajar</label>

                        <select name="id_tutor" class="form-select">
                            <option value="">Belum ditentukan</option>

                            <?php foreach ($tutor_list as $t): ?>
                            <option value="<?= (int)$t['id_tutor'] ?>"
                                <?= ($k['id_tutor'] == $t['id_tutor']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nama_tutor']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ruangan</label>

                        <select name="id_ruangan" class="form-select">
                            <option value="">Belum ditentukan</option>

                            <?php foreach ($ruangan_list as $r): ?>
                            <option value="<?= (int)$r['id_ruangan'] ?>"
                                <?= ($k['id_ruangan'] == $r['id_ruangan']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['nama_ruangan']) ?>
                                (<?= (int)$r['kapasitas'] ?> siswa)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn-kelas">
                        <i class="bi bi-save me-1"></i>
                        Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- MODAL TAMBAH -->
<div class="modal fade kelas-modal"
     id="modalTambah"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form action="<?= base_url('modules/kelas/create.php') ?>"
                  method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kelas Baru</h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($csrf) ?>">

                    <div class="mb-3">
                        <label class="form-label">Nama Kelas</label>

                        <input type="text"
                               name="nama_kelas"
                               class="form-control"
                               maxlength="100"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tutor Pengajar</label>

                        <select name="id_tutor" class="form-select">
                            <option value="">Belum ditentukan</option>

                            <?php foreach ($tutor_list as $t): ?>
                            <option value="<?= (int)$t['id_tutor'] ?>">
                                <?= htmlspecialchars($t['nama_tutor']) ?>
                                - <?= htmlspecialchars($t['keahlian']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ruangan</label>

                        <select name="id_ruangan" class="form-select">
                            <option value="">Belum ditentukan</option>

                            <?php foreach ($ruangan_list as $r): ?>
                            <option value="<?= (int)$r['id_ruangan'] ?>">
                                <?= htmlspecialchars($r['nama_ruangan']) ?>
                                (<?= (int)$r['kapasitas'] ?> siswa)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn-kelas">
                        <i class="bi bi-check-circle me-1"></i>
                        Simpan Kelas
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS -->
<div class="modal fade kelas-modal"
     id="modalHapusKelas"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form action="<?= base_url('modules/kelas/delete.php') ?>"
                  method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                        Konfirmasi Hapus Kelas
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center py-4">

                    <div class="modal-hapus-icon">
                        <i class="bi bi-trash3"></i>
                    </div>

                    <p class="mb-2">
                        Apakah Anda yakin ingin menghapus kelas:
                    </p>

                    <h5 class="fw-bold text-danger"
                        id="namaKelasHapus">
                        -
                    </h5>

                    <small class="text-muted">
                        Data kelas yang dihapus tidak dapat dikembalikan.
                    </small>

                    <input type="hidden"
                           name="id_kelas"
                           id="idKelasHapus">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($csrf) ?>">

                </div>

                <div class="modal-footer justify-content-center">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit"
                            class="btn-konfirmasi-hapus">
                        <i class="bi bi-trash me-1"></i>
                        Ya, Hapus
                    </button>

                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const modalHapus = document.getElementById('modalHapusKelas');

    if (modalHapus) {

        modalHapus.addEventListener('show.bs.modal', function (event) {

            const tombol = event.relatedTarget;

            const id = tombol.getAttribute('data-id');
            const nama = tombol.getAttribute('data-nama');

            document.getElementById('idKelasHapus').value = id;

            document.getElementById('namaKelasHapus').textContent = nama;

        });

    }

});
</script>

<?php require_once BASEPATH . '/includes/footer.php'; ?>
