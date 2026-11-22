
<?php
/**
 * Logout - Smart Education Parepare
 */

// ==========================================
// MULAI SESSION
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// HAPUS SELURUH DATA SESSION
// ==========================================
$_SESSION = [];

// ==========================================
// HAPUS COOKIE SESSION
// ==========================================
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// ==========================================
// HANCURKAN SESSION
// ==========================================
session_destroy();

// ==========================================
// REDIRECT KE HALAMAN LOGIN
// ==========================================
header('Location: login.php');
exit;
