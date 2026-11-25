
<?php
/**
 * EDIT PENGGUNA
 * SMART EDUCATION PAREPARE
 */

$root = dirname(__DIR__, 2);

if (!defined('BASEPATH')) {
    define('BASEPATH', $root);
}

require_once $root . '/config/helper.php';

require_login();

if (($_SESSION['role'] ?? '') !== 'superadmin') {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$current_user = current_user();

$page_title = 'Edit Pengguna';
$error = '';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('danger', 'ID pengguna tidak valid.');
    header('Location: ' . base_url('modules/users/index.php'));
    exit;
}

// Ambil data pengguna
$stmt = $pdo->prepare("
    SELECT id, username, nama_lengkap, email, role, id_siswa
    FROM users
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'Data pengguna tidak ditemukan.');
    header('Location: ' . base_url('modules/users/index.php'));
    exit;
}

$isSuperAdmin = $user['role'] === 'superadmin';

// Token CSRF
if (empty($_SESSION['csrf_edit_user'])) {
    $_SESSION['csrf_edit_user'] = bin2hex(random_bytes(32));
}

// Ambil data siswa yang belum memiliki akun,
// termasuk siswa yang saat ini terhubung dengan akun ini.
$stmtSiswa = $pdo->prepare("
    SELECT
        s.id_siswa,
        s.nis,
        s.nama_siswa,
        s.email
    FROM siswa s
    LEFT JOIN users u ON u.id_siswa = s.id_siswa
    WHERE u.id IS NULL OR u.id = :user_id
    ORDER BY s.nama_siswa ASC
");
$stmtSiswa->execute([':user_id' => $id]);
$daftarSiswa = $stmtSiswa->fetchAll();

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_edit_user'], $token)) {
        $error = 'Permintaan tidak valid. Muat ulang halaman.';
    } else {

        $username = trim($_POST['username'] ?? '');
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Role Super Admin tidak boleh diubah.
        $role = $isSuperAdmin
            ? 'superadmin'
            : ($_POST['role'] ?? '');

        $id_siswa = null;

        if (!in_array($role, ['superadmin', 'admin', 'siswa'], true)) {
            $error = 'Role pengguna tidak valid.';
        } elseif ($username === '' || strlen($username) > 50) {
            $error = 'Username wajib diisi dan maksimal 50 karakter.';
        } elseif ($nama === '' || strlen($nama) > 100) {
            $error = 'Nama lengkap wajib diisi dan maksimal 100 karakter.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif ($password !== '' && strlen($password) < 8) {
            $error = 'Password baru minimal 8 karakter.';
        }

        // Jika role siswa, ambil data siswa yang dipilih.
        if ($error === '' && $role === 'siswa') {

            $id_siswa = (int)($_POST['id_siswa'] ?? 0);

            if ($id_siswa <= 0) {
                $error = 'Silakan pilih data siswa.';
            } else {

                $stmt = $pdo->prepare("
                    SELECT s.id_siswa, s.nama_siswa, s.email
                    FROM siswa s
                    LEFT JOIN users u ON u.id_siswa = s.id_siswa
                    WHERE s.id_siswa = :id_siswa
                    AND (u.id IS NULL OR u.id = :user_id)
                    LIMIT 1
                ");

                $stmt->execute([
                    ':id_siswa' => $id_siswa,
                    ':user_id' => $id
                ]);

                $dataSiswa = $stmt->fetch();

                if (!$dataSiswa) {
                    $error = 'Data siswa tidak tersedia atau sudah memiliki akun lain.';
                } else {
                    $nama = $dataSiswa['nama_siswa'];
                    $email = trim($dataSiswa['email'] ?? '');
                }
            }
        }

        if ($error === '' && $role === 'admin' && $email === '') {
            $error = 'Email Admin wajib diisi.';
        }

        // Periksa username duplikat
        if ($error === '') {

            $stmt = $pdo->prepare("
                SELECT id FROM users
                WHERE username = :username
                AND id <> :id
                LIMIT 1
            ");

            $stmt->execute([
                ':username' => $username,
                ':id' => $id
            ]);

            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan akun lain.';
            }
        }

        // Periksa email duplikat
        if ($error === '' && $email !== '') {

            $stmt = $pdo->prepare("
                SELECT id FROM users
                WHERE email = :email
                AND id <> :id
                LIMIT 1
            ");

            $stmt->execute([
                ':email' => $email,
                ':id' => $id
            ]);

            if ($stmt->fetch()) {
                $error = 'Email sudah digunakan akun lain.';
            }
        }

        // Simpan perubahan
        if ($error === '') {

            try {

                if ($password !== '') {

                    $passwordHash = password_hash(
                        $password,
                        PASSWORD_BCRYPT
                    );

                    $sql = "
                        UPDATE users SET
                            username = :username,
                            nama_lengkap = :nama,
                            email = :email,
                            role = :role,
                            id_siswa = :id_siswa,
                            password = :password
                        WHERE id = :id
                    ";

                } else {

                    $sql = "
                        UPDATE users SET
                            username = :username,
                            nama_lengkap = :nama,
                            email = :email,
                            role = :role,
                            id_siswa = :id_siswa
                        WHERE id = :id
                    ";
                }

                $stmt = $pdo->prepare($sql);

                $params = [
                    ':username' => $username,
                    ':nama' => $nama,
                    ':email' => $email !== '' ? $email : null,
                    ':role' => $role,
                    ':id_siswa' => $id_siswa,
                    ':id' => $id
                ];

                if ($password !== '') {
                    $params[':password'] = $passwordHash;
                }

                $stmt->execute($params);

                // Perbarui session jika akun yang diedit
                // adalah akun Super Admin yang sedang login.
                if ((int)$current_user['id'] === (int)$id) {
                    $_SESSION['username'] = $username;
                    $_SESSION['nama_lengkap'] = $nama;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $role;
                    $_SESSION['id_siswa'] = $id_siswa;
                }

                set_flash('success', 'Data pengguna berhasil diperbarui.');

                header('Location: ' . base_url('modules/users/index.php'));
                exit;

            } catch (PDOException $e) {
                $error = 'Gagal memperbarui data pengguna. Periksa kembali data.';
            }
        }
    }
}

require_once $root . '/includes/header.php';
require_once $root . '/includes/sidebar.php';
require_once $root . '/includes/navbar.php';
?>

<div class="edit-user-page">

    <div class="page-heading">
        <div>
            <h3>Edit Pengguna</h3>
            <p>Perbarui informasi akun pengguna Smart Education.</p>
        </div>

        <a href="<?= base_url('modules/users/index.php'); ?>" class="btn-back">
            <i class="bi bi-arrow-left"></i>
            Kembali
        </a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="edit-card">

        <div class="edit-card-heading">
            <div class="edit-icon">
                <i class="bi bi-person-gear"></i>
            </div>

            <div>
                <h5>Informasi Akun Pengguna</h5>
                <p>Ubah informasi akun sesuai kebutuhan.</p>
            </div>
        </div>

        <form method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_edit_user']); ?>">

            <div class="form-group">
                <label>Role Pengguna <span>*</span></label>

                <?php if ($isSuperAdmin): ?>

                    <input type="text"
                           class="form-control"
                           value="Super Admin"
                           readonly>

                    <input type="hidden" name="role" value="superadmin">

                    <small>Role Super Admin tidak dapat diubah melalui halaman ini.</small>

                <?php else: ?>

                    <select name="role" id="role" class="form-control" required>
                        <option value="admin"
                            <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>
                            Admin
                        </option>

                        <option value="siswa"
                            <?= $user['role'] === 'siswa' ? 'selected' : ''; ?>>
                            Siswa/Orang Tua
                        </option>
                    </select>

                <?php endif; ?>
            </div>

            <div class="form-group" id="student-group"
                 style="<?= $user['role'] === 'siswa' ? '' : 'display:none;'; ?>">

                <label for="id_siswa">Data Siswa <span>*</span></label>

                <select name="id_siswa" id="id_siswa" class="form-control">

                    <option value="">-- Pilih Siswa --</option>

                    <?php foreach ($daftarSiswa as $siswa): ?>
                        <option
                            value="<?= (int)$siswa['id_siswa']; ?>"
                            data-nama="<?= htmlspecialchars($siswa['nama_siswa'], ENT_QUOTES); ?>"
                            data-email="<?= htmlspecialchars($siswa['email'] ?? '', ENT_QUOTES); ?>"
                            <?= ((int)$user['id_siswa'] === (int)$siswa['id_siswa']) ? 'selected' : ''; ?>>

                            <?= htmlspecialchars($siswa['nama_siswa']); ?>
                            (NIS: <?= htmlspecialchars($siswa['nis']); ?>)

                        </option>
                    <?php endforeach; ?>

                </select>

                <small>Pilih siswa yang terhubung dengan akun ini.</small>
            </div>

            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap <span>*</span></label>

                <input type="text"
                       name="nama_lengkap"
                       id="nama_lengkap"
                       class="form-control"
                       maxlength="100"
                       value="<?= htmlspecialchars($user['nama_lengkap']); ?>"
                       required
                       <?= $user['role'] === 'siswa' ? 'readonly' : ''; ?>>
            </div>

            <div class="form-group">
                <label for="username">Username <span>*</span></label>

                <input type="text"
                       name="username"
                       id="username"
                       class="form-control"
                       maxlength="50"
                       value="<?= htmlspecialchars($user['username']); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>

                <input type="email"
                       name="email"
                       id="email"
                       class="form-control"
                       value="<?= htmlspecialchars($user['email'] ?? ''); ?>"
                       <?= $user['role'] === 'siswa' ? 'readonly' : ''; ?>>
            </div>

            <div class="form-group">
                <label for="password">Password Baru</label>

                <input type="password"
                       name="password"
                       id="password"
                       class="form-control"
                       minlength="8"
                       autocomplete="new-password"
                       placeholder="Kosongkan jika tidak ingin mengganti password">

                <small>
                    Isi hanya jika ingin mengganti password.
                    Minimal 8 karakter dan akan disimpan menggunakan Bcrypt.
                </small>
            </div>

            <div class="form-actions">

                <a href="<?= base_url('modules/users/index.php'); ?>"
                   class="btn-cancel">
                    Batal
                </a>

                <button type="submit" class="btn-save">
                    <i class="bi bi-check-circle"></i>
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</div>

<style>

.edit-user-page {
    padding: 24px 28px 35px;
    background: #f5fbfa;
    min-height: calc(100vh - 140px);
}

.page-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 24px;
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

.edit-card {
    max-width: 850px;
    padding: 28px;
    background: white;
    border: 1px solid #e4f0ed;
    border-radius: 17px;
    box-shadow: 0 4px 18px rgba(31, 92, 81, .04);
}

.edit-card-heading {
    display: flex;
    align-items: center;
    gap: 13px;
    padding-bottom: 22px;
    margin-bottom: 24px;
    border-bottom: 1px solid #edf3f1;
}

.edit-icon {
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

.edit-card-heading h5 {
    margin: 0 0 5px;
    color: #173b38;
    font-size: 16px;
    font-weight: 700;
}

.edit-card-heading p {
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

@media (max-width: 768px) {
    .edit-user-page {
        padding: 20px 15px;
    }

    .edit-card {
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

    if (!role || !studentGroup || !studentSelect) {
        return;
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

    function updateRole() {
        const isStudent = role.value === 'siswa';

        studentGroup.style.display = isStudent ? 'block' : 'none';
        studentSelect.required = isStudent;

        nama.readOnly = isStudent;
        email.readOnly = isStudent;

        if (isStudent) {
            updateStudent();
        }
    }

    role.addEventListener('change', updateRole);
    studentSelect.addEventListener('change', updateStudent);

    updateRole();
});
</script>

<?php require_once $root . '/includes/footer.php'; ?>
