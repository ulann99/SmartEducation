
<?php
/**
 * Header Template
 * SMART EDUCATION PAREPARE
 */

// =====================================
// KONFIGURASI
// =====================================

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__));
}

require_once __DIR__ . '/../config/helper.php';

// =====================================
// INFORMASI HALAMAN DAN PENGGUNA
// =====================================

$app_name = "SMART EDUCATION PAREPARE";

// Judul halaman untuk navbar
$page_title = $page_title ?? "Dashboard";

// Judul lengkap untuk tab browser
$document_title = $page_title . " | " . $app_name;

// Informasi pengguna
$current_user = current_user();
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($document_title, ENT_QUOTES, 'UTF-8') ?>
    </title>

    <!-- GOOGLE FONTS POPPINS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- BOOTSTRAP 5.3 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- BOOTSTRAP ICONS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- SWEETALERT2 CSS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"
    >

    <!-- CUSTOM CSS LANDING PAGE -->
    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/style.css') ?>"
    >

    <!-- CUSTOM CSS ADMIN PANEL -->
    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/admin.css') ?>?v=1.0"
    >

</head>

<body>

<div class="app-wrapper">
