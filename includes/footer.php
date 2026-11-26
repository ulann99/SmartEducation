
<?php
/**
 * Footer Template
 * SMART EDUCATION PAREPARE
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__));
}

require_once __DIR__ . '/../config/helper.php';

// =====================================
// NOTIFIKASI
// =====================================

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? 'success';

// Hapus pesan setelah diambil agar tidak muncul berulang
unset($_SESSION['flash_message']);
unset($_SESSION['flash_type']);

// Sesuaikan tipe notifikasi SweetAlert2
$swal_icon = 'success';
$swal_title = 'Berhasil!';

if ($flash_type === 'danger' || $flash_type === 'error') {
    $swal_icon = 'error';
    $swal_title = 'Gagal!';
} elseif ($flash_type === 'warning') {
    $swal_icon = 'warning';
    $swal_title = 'Perhatian!';
} elseif ($flash_type === 'info') {
    $swal_icon = 'info';
    $swal_title = 'Informasi';
}
?>

    </main>

    <!-- Footer Copyright -->
    <footer class="py-3 px-4 bg-white border-top text-center text-muted small mt-auto">
        <div class="container-fluid d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div>
                &copy; <?= date('Y') ?>
                <strong>Smart Education Parepare</strong>.
                All rights reserved.
            </div>

            <div>
                <span class="text-mint-darker fw-medium">
                    Sistem Informasi Pengelolaan Data Siswa & Pembayaran
                </span>
            </div>
        </div>
    </footer>

</div>
</div> <!-- End .app-wrapper -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= base_url('assets/js/main.js') ?>"></script>

<?php if (!empty($flash_message)): ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    Swal.fire({
        icon: <?= json_encode($swal_icon); ?>,
        title: <?= json_encode($swal_title); ?>,
        text: <?= json_encode($flash_message); ?>,
        confirmButtonText: 'OK',
        confirmButtonColor: '#27877B',
        background: '#FFFFFF',
        color: '#172B2A',
        allowOutsideClick: false
    });

});
</script>

<?php endif; ?>

</body>
</html>
