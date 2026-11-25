
<?php
/**
 * TAMBAH PENGGUNA
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';

require_login();

// Hanya Super Admin yang boleh mengakses
if (($_SESSION['role'] ?? '') !== 'superadmin') {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$current_user = current_user();

$page_title = 'Tambah Pengguna';
$error = '';

// Token keamanan form
if (empty($_SESSION['csrf_users'])) {
    $_SESSION['csrf_users'] = bin2hex(random_bytes(32));
}

// Mengambil data siswa yang belum memiliki akun
$stmtSiswa = $pdo->query("
    SELECT
        s.id_siswa,
        s.nis,
        s.nama_siswa,
        s.email
    FROM siswa s
    LEFT JOIN users u ON u.id_siswa = s.id_siswa
    WHERE u.id IS NULL
    ORDER BY s.nama_siswa ASC
");

$daftarSiswa = $stmtSiswa->fetchAll();

// Proses penyimpanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_users'], $token)) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } else {

        $role = $_POST['role'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $id_siswa = null;

        // Validasi role
        if (!in_array($role, ['admin', 'siswa'], true)) {
            $error = 'Role pengguna tidak valid.';
        }

        // Validasi username
        elseif ($username === '' || strlen($username) > 50) {
            $error = 'Username wajib diisi dan maksimal 50 karakter.';
        }

        // Validasi password
        elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        }

        else {

            // Jika membuat akun siswa
            if ($role === 'siswa') {

                $id_siswa = (int)($_POST['id_siswa'] ?? 0);

                if ($id_siswa <= 0) {
                    $error = 'Silakan pilih data siswa terlebih dahulu.';
                } else {

                    // Pastikan siswa belum memiliki akun
                    $stmt = $pdo->prepare("
                        SELECT s.id_siswa, s.nama_siswa, s.email
                        FROM siswa s
                        LEFT JOIN users u ON u.id_siswa = s.id_siswa
                        WHERE s.id_siswa = :id_siswa
                        AND u.id IS NULL
                        LIMIT 1
                    ");

                    $stmt->execute([':id_siswa' => $id_siswa]);
                    $dataSiswa = $stmt->fetch();

                    if (!$dataSiswa) {
                        $error = 'Data siswa tidak ditemukan atau sudah memiliki akun.';
                    } else {
                        $nama = $dataSiswa['nama_siswa'];
                        $email = trim($dataSiswa['email'] ?? '');
                    }
                }
            }

            // Validasi data admin
            elseif ($role === 'admin') {

                if ($nama === '') {
                    $error = 'Nama lengkap wajib diisi.';
                } elseif ($email === '') {
                    $error = 'Email Admin wajib diisi.';
                }
            }

            // Validasi email jika tersedia
            if ($error === '' && $email !== ''
                && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Format email tidak valid.';
            }

            // Periksa username duplikat
            if ($error === '') {

                $stmt = $pdo->prepare("
                    SELECT id FROM users
                    WHERE username = :username
                    LIMIT 1
                ");

                $stmt->execute([':username' => $username]);

                if ($stmt->fetch()) {
                    $error = 'Username sudah digunakan. Silakan pilih username lain.';
                }
            }

            // Periksa email duplikat
            if ($error === '' && $email !== '') {

                $stmt = $pdo->prepare("
                    SELECT id FROM users
                    WHERE email = :email
                    LIMIT 1
                ");

                $stmt->execute([':email' => $email]);

                if ($stmt->fetch()) {
                    $error = 'Email sudah digunakan oleh akun lain.';
                }
            }

            // Simpan akun
            if ($error === '') {

                try {

                    $passwordHash = password_hash(
                        $password,
                        PASSWORD_BCRYPT
                    );

                    $stmt = $pdo->prepare("
                        INSERT INTO users (
                            username,
                            password,
                            nama_lengkap,
                            email,
                            role,
                            id_siswa
                        ) VALUES (
                            :username,
                            :password,
                            :nama,
                            :email,
                            :role,
                            :id_siswa
                        )
                    ");

                    $stmt->execute([
                        ':username' => $username,
                        ':password' => $passwordHash,
                        ':nama' => $nama,
                        ':email' => $email !== '' ? $email : null,
                        ':role' => $role,
                        ':id_siswa' => $id_siswa
                    ]);

                    set_flash(
                        'success',
                        'Akun pengguna berhasil ditambahkan.'
                    );

                    header('Location: ' . base_url('modules/users/index.php'));
                    exit;

                } catch (PDOException $e) {
                    $error = 'Gagal menyimpan akun. Periksa kembali data yang dimasukkan.';
                }
            }
        }
    }
}

require_once $root . '/includes/header.php';
require_once $root . '/includes/sidebar.php';
require_once $root . '/includes/navbar.php';
?>

<div class="add-user-page">

    <!-- HEADER -->
    <div class="page-heading">
        <div>
            <h3>Tambah Pengguna</h3>
            <p>Tambahkan akun baru ke sistem Smart Education Parepare.</p>
        </div>

        <a href="<?= base_url('modules/users/index.php'); ?>"
           class="btn-back">
            <i class="bi bi-arrow-left"></i>
            Kembali
        </a>
    </div>

    <!-- PESAN ERROR -->
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle"></i>
            <?= htmlspecialchars($error); ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- FORM -->
    <div class="form-card">

        <div class="form-card-heading">
            <div class="form-heading-icon">
                <i class="bi bi-person-plus"></i>
            </div>

            <div>
                <h5>Informasi Akun Pengguna</h5>
                <p>Lengkapi informasi berikut untuk membuat akun baru.</p>
            </div>
        </div>

        <form method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_users']); ?>">

            <!-- ROLE -->
            <div class="form-group">
                <label for="role">Role Pengguna <span>*</span></label>

                <select name="role"
                        id="role"
                        class="form-control"
                        required>

                    <option value="">-- Pilih Role --</option>

                    <option value="admin"
                        <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>
                        Admin
                    </option>

                    <option value="siswa"
                        <?= (($_POST['role'] ?? '') === 'siswa') ? 'selected' : ''; ?>>
                        Siswa/Orang Tua
                    </option>

                </select>

                <small>Pilih jenis akun yang akan dibuat.</small>
            </div>

            <!-- PILIH SISWA -->
            <div class="form-group" id="student-group" style="display:none;">

                <label for="id_siswa">Pilih Data Siswa <span>*</span></label>

                <select name="id_siswa"
                        id="id_siswa"
                        class="form-control">

                    <option value="">-- Pilih Siswa --</option>

                    <?php foreach ($daftarSiswa as $siswa): ?>

                        <option
                            value="<?= (int)$siswa['id_siswa']; ?>"
                            data-nama="<?= htmlspecialchars($siswa['nama_siswa'], ENT_QUOTES); ?>"
                            data-email="<?= htmlspecialchars($siswa['email'] ?? '', ENT_QUOTES); ?>"
                            <?= (($_POST['id_siswa'] ?? '') == $siswa['id_siswa']) ? 'selected' : ''; ?>>

                            <?= htmlspecialchars($siswa['nama_siswa']); ?>
                            (NIS: <?= htmlspecialchars($siswa['nis']); ?>)

                        </option>

                    <?php endforeach; ?>

                </select>

                <small>Hanya siswa yang belum memiliki akun yang ditampilkan.</small>

                <?php if (empty($daftarSiswa)): ?>
                    <small class="text-danger">
                        Semua data siswa sudah memiliki akun atau belum ada data siswa.
                    </small>
                <?php endif; ?>

            </div>

            <!-- NAMA -->
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap <span>*</span></label>

                <input type="text"
                       name="nama_lengkap"
                       id="nama_lengkap"
                       class="form-control"
                       placeholder="Masukkan nama lengkap"
                       value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>"
                       required>
            </div>

            <!-- USERNAME -->
            <div class="form-group">
                <label for="username">Username <span>*</span></label>

                <input type="text"
                       name="username"
                       id="username"
                       class="form-control"
                       placeholder="Masukkan username"
                       maxlength="50"
                       value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>"
                       required>

                <small>Username digunakan untuk login ke sistem.</small>
            </div>

            <!-- EMAIL -->
            <div class="form-group">
                <label for="email">Email</label>

                <input type="email"
                       name="email"
                       id="email"
                       class="form-control"
                       placeholder="contoh@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <!-- PASSWORD -->
            <div class="form-group">
                <label for="password">Password <span>*</span></label>

                <input type="password"
                       name="password"
                       id="password"
                       class="form-control"
                       placeholder="Masukkan password"
                       minlength="8"
                       required>

                <small>Password minimal 8 karakter dan akan disimpan menggunakan Bcrypt.</small>
            </div>

            <!-- TOMBOL -->
            <div class="form-actions">

                <a href="<?= base_url('modules/users/index.php'); ?>"
                   class="btn-cancel">
                    Batal
                </a>

                <button type="submit" class="btn-save">
                    <i class="bi bi-check-circle"></i>
                    Simpan Pengguna
                </button>

            </div>

        </form>

    </div>

</div>

<style>

.add-user-page {
    padding: 24px 28px 35px;
    background: #f5fbfa;
    min-height: calc(100vh - 140px);
}

.page-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 15px;
}

.page-heading h3 {
    margin: 0 0 7px;
    color: #173b38;
    font-size: 22px;
    font-weight: 700;
}

.page-heading p {
    margin: 0;
    color: #718582;
    font-size: 13px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    border-radius: 9px;
    background: #e8fdff;
    color: #287e72;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

.btn-back:hover {
    background: #d7f6f1;
    color: #176d63;
}

.form-card {
    max-width: 850px;
    padding: 28px;
    background: white;
    border: 1px solid #e4f0ed;
    border-radius: 17px;
    box-shadow: 0 4px 18px rgba(31, 92, 81, .04);
}

.form-card-heading {
    display: flex;
    align-items: center;
    gap: 13px;
    padding-bottom: 22px;
    margin-bottom: 24px;
    border-bottom: 1px solid #edf3f1;
}

.form-heading-icon {
    width: 46px;
    height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #e8fdff;
    color: #287e72;
    font-size: 21px;
}

.form-card-heading h5 {
    margin: 0 0 5px;
    color: #173b38;
    font-size: 16px;
    font-weight: 700;
}

.form-card-heading p {
    margin: 0;
    color: #718582;
    font-size: 12px;
}

.form-group {
    margin-bottom: 19px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #365850;
    font-size: 12px;
    font-weight: 600;
}

.form-group label span {
    color: #dc5555;
}

.form-control {
    width: 100%;
    min-height: 44px;
    padding: 10px 13px;
    border: 1px solid #e0ece9;
    border-radius: 9px;
    color: #173b38;
    background: white;
    font-size: 12px;
}

.form-control:focus {
    border-color: #72cbbb;
    box-shadow: 0 0 0 3px rgba(152, 237, 225, .2);
    outline: none;
}

.form-control:read-only {
    background: #f4faf8;
    cursor: not-allowed;
}

.form-group small {
    display: block;
    margin-top: 6px;
    color: #879994;
    font-size: 10px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    margin-top: 25px;
    border-top: 1px solid #edf3f1;
}

.btn-cancel,
.btn-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 19px;
    border: none;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}

.btn-cancel {
    background: #f0f6f4;
    color: #526c66;
}

.btn-save {
    background: #287e72;
    color: white;
}

.btn-save:hover {
    background: #176d63;
}

.btn-cancel:hover {
    background: #e4efeb;
    color: #365850;
}

@media (max-width: 768px) {
    .add-user-page {
        padding: 20px 15px;
    }

    .form-card {
        padding: 20px;
    }

    .page-heading {
        align-items: flex-start;
        flex-direction: column;
    }
}

</style>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const role = document.getElementById('role');
    const studentGroup = document.getElementById('student-group');
    const studentSelect = document.getElementById('id_siswa');
    const nama = document.getElementById('nama_lengkap');
    const email = document.getElementById('email');

    function updateRole() {

        const isStudent = role.value === 'siswa';

        studentGroup.style.display = isStudent ? 'block' : 'none';

        studentSelect.required = isStudent;

        nama.readOnly = isStudent;
        email.readOnly = isStudent;

        if (isStudent) {
            updateStudent();
        } else {
            nama.value = '';
            email.value = '';
        }
    }

    function updateStudent() {

        const option = studentSelect.options[studentSelect.selectedIndex];

        if (studentSelect.value !== '') {
            nama.value = option.dataset.nama || '';
            email.value = option.dataset.email || '';
        } else {
            nama.value = '';
            email.value = '';
        }
    }

    role.addEventListener('change', updateRole);
    studentSelect.addEventListener('change', updateStudent);

    updateRole();

});

</script>

<?php require_once $root . '/includes/footer.php'; ?>
