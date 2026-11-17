<?php
/**
 * Footer Template
 * SMART EDUCATION PAREPARE
 */
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__));
}
require_once __DIR__ . '/../config/helper.php';
?>
    </main>

    <!-- Footer Copyright -->
    <footer class="py-3 px-4 bg-white border-top text-center text-muted small mt-auto">
        <div class="container-fluid d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div>
                &copy; <?= date('Y') ?> <strong>Smart Education Parepare</strong>. All rights reserved.
            </div>
            <div>
                <span class="text-mint-darker fw-medium">Sistem Informasi Pengelolaan Data Siswa & Pembayaran</span>
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

</body>
</html>

