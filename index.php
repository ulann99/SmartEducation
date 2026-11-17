
<?php
$pageTitle = "Smart Education Parepare";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Smart Education Parepare - Bimbingan belajar untuk membantu siswa belajar lebih mudah dan meraih prestasi.">

    <title><?= htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>

<body>

<!-- TOP STRIP -->
<div class="top-strip">
    SMART EDUCATION PAREPARE · Teman Belajar untuk Masa Depan Cerah
</div>

<!-- HEADER -->
<header class="site-header">

    <a class="brand" href="index.php">
        <span class="brand-mark">S</span>

        <span class="brand-copy">
            <strong>SMART EDUCATION</strong>
            <small>PAREPARE</small>
        </span>
    </a>

    <button class="menu-toggle" type="button"
        aria-label="Buka navigasi"
        aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- NAVBAR -->
    <nav class="main-nav">
        <a class="active" href="#beranda">Beranda</a>
        <a href="#tentang">Tentang Kami</a>
        <a href="#program">Program</a>
        <a href="#keunggulan">Keunggulan</a>
        <a href="#kontak">Kontak</a>
    </nav>

    <!-- LOGIN DAN DAFTAR -->
    <div class="header-actions">
        <a class="button button-outline"
           href="modules/auth/login.php">
            Login
        </a>

        <a class="button button-dark"
           href="modules/auth/register.php">
            Daftar
        </a>
    </div>

</header>

<main>

    <!-- HERO SECTION -->
    <section class="hero" id="beranda">

        <div class="hero-bg-shape shape-one"></div>
        <div class="hero-bg-shape shape-two"></div>

        <div class="hero-inner">

            <div class="hero-copy">

                <span class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    BIMBINGAN BELAJAR SMART EDUCATION
                </span>

                <h1>
                    Belajar Lebih Mudah,
                    <span>Raih Masa Depan Cerah!</span>
                </h1>

                <p class="hero-description">
                    Temukan pengalaman belajar yang menyenangkan
                    dan terarah. Kembangkan potensi, tingkatkan
                    pemahaman, dan raih prestasi bersama
                    Smart Education Parepare.
                </p>

                <div class="hero-actions">

                    <a class="button button-dark button-large"
                       href="#program">
                        Jelajahi Program <span>→</span>
                    </a>

                    <a class="text-link" href="#tentang">
                        Kenali Smart Education
                    </a>

                </div>

                <div class="hero-note">
                    <span class="note-icon">✦</span>
                    <span>
                        Belajar dengan nyaman,
                        tumbuh dengan percaya diri.
                    </span>
                </div>

            </div>

            <!-- ILUSTRASI HERO -->
            <div class="hero-art">

                <div class="art-orbit orbit-one"></div>
                <div class="art-orbit orbit-two"></div>

                <div class="art-card card-note">
                    <span class="mini-icon">✎</span>
                    <span>Semangat belajar!</span>
                </div>

                <div class="art-main">

                    <div class="art-window-top">
                        <i></i>
                        <i></i>
                        <i></i>
                    </div>

                    <div class="art-book">📚</div>
                    <div class="art-cap">🎓</div>

                    <div class="art-spark spark-a">✦</div>
                    <div class="art-spark spark-b">✧</div>

                    <div class="art-caption">
                        <strong>Waktunya berkembang!</strong>
                        <small>
                            Langkah kecil menuju prestasi
                        </small>
                    </div>

                </div>

                <div class="art-card card-score">
                    <span class="score-star">★</span>

                    <span>
                        <strong>Potensi terbaikmu</strong>
                        <small>Mulai dari sini</small>
                    </span>
                </div>

            </div>

        </div>

        <div class="hero-bottom-wave"></div>

    </section>


    <!-- KEUNGGULAN SINGKAT -->
    <section class="quick-benefits">

        <article class="quick-item">
            <span class="quick-icon">📖</span>

            <span>
                <strong>Belajar Terarah</strong>
                <small>Materi sesuai kebutuhan</small>
            </span>
        </article>

        <article class="quick-item">
            <span class="quick-icon">🧑‍🏫</span>

            <span>
                <strong>Pendampingan Tutor</strong>
                <small>Belajar lebih terbimbing</small>
            </span>
        </article>

        <article class="quick-item">
            <span class="quick-icon">🌟</span>

            <span>
                <strong>Kenali Potensi</strong>
                <small>Kembangkan kemampuan diri</small>
            </span>
        </article>

    </section>


    <!-- TENTANG KAMI -->
    <section class="section about-section" id="tentang">

        <div class="section-heading">

            <span class="section-kicker">
                TENTANG KAMI
            </span>

            <h2>
                Tempat Belajar untuk
                <span>Tumbuh dan Berprestasi</span>
            </h2>

            <p>
                Smart Education Parepare hadir untuk mendukung
                proses belajar siswa melalui pendampingan
                dan lingkungan belajar yang positif.
            </p>

        </div>

        <div class="about-grid">

            <div class="about-visual">

                <div class="about-visual-inner">
                    <span class="about-big-icon">🎒</span>

                    <span class="floating-bubble bubble-one">
                        Ayo belajar!
                    </span>

                    <span class="floating-bubble bubble-two">
                        ✨
                    </span>
                </div>

            </div>

            <div class="about-content">

                <h3>
                    Setiap anak punya potensi untuk berkembang.
                </h3>

                <p>
                    Kami percaya bahwa proses belajar yang baik
                    dimulai dari pemahaman, pendampingan, dan
                    semangat untuk terus mencoba. Smart Education
                    menjadi ruang bagi siswa untuk mengembangkan
                    kemampuan akademik secara bertahap.
                </p>

                <ul class="check-list">
                    <li>
                        <span>✓</span>
                        Suasana belajar yang nyaman
                    </li>

                    <li>
                        <span>✓</span>
                        Pendampingan sesuai kebutuhan belajar
                    </li>

                    <li>
                        <span>✓</span>
                        Fokus pada perkembangan siswa
                    </li>
                </ul>

                <a class="button button-soft" href="#kontak">
                    Hubungi Kami
                </a>

            </div>

        </div>

    </section>


    <!-- PROGRAM PEMBELAJARAN -->
    <section class="section programs-section" id="program">

        <div class="section-heading">

            <span class="section-kicker">
                PROGRAM PEMBELAJARAN
            </span>

            <h2>
                Pilih Cara Belajar
                <span>yang Tepat untukmu</span>
            </h2>

            <p>
                Kenali pilihan program dan layanan
                pembelajaran Smart Education.
            </p>

        </div>

        <div class="program-grid">

            <article class="program-card">

                <div class="program-icon icon-mint">
                    📘
                </div>

                <span class="card-number">01</span>

                <h3>Program Belajar</h3>

                <p>
                    Pilihan pembelajaran yang disesuaikan
                    dengan kebutuhan dan tujuan belajar siswa.
                </p>

                <a href="#kontak">
                    Pelajari lebih lanjut <span>→</span>
                </a>

            </article>


            <article class="program-card featured-card">

                <div class="program-icon icon-blue">
                    🧑‍🏫
                </div>

                <span class="card-number">02</span>

                <h3>Pendampingan Tutor</h3>

                <p>
                    Dukungan tutor untuk membantu siswa
                    memahami materi dan mengembangkan kemampuan.
                </p>

                <a href="#kontak">
                    Pelajari lebih lanjut <span>→</span>
                </a>

            </article>


            <article class="program-card">

                <div class="program-icon icon-yellow">
                    🏫
                </div>

                <span class="card-number">03</span>

                <h3>Kelas Belajar</h3>

                <p>
                    Pengalaman belajar dalam lingkungan
                    yang nyaman, tertata, dan mendukung.
                </p>

                <a href="#kontak">
                    Pelajari lebih lanjut <span>→</span>
                </a>

            </article>

        </div>

    </section>


    <!-- KEUNGGULAN -->
    <section class="section advantage-section" id="keunggulan">

        <div class="advantage-intro">

            <span class="section-kicker">
                MENGAPA SMART EDUCATION?
            </span>

            <h2>
                Dukungan Belajar untuk
                <span>Setiap Langkahmu</span>
            </h2>

            <p>
                Kami ingin membantu siswa menjalani proses
                belajar dengan lebih percaya diri dan terarah.
            </p>

        </div>

        <div class="advantage-list">

            <article>
                <span>01</span>

                <div>
                    <h3>Fokus pada Siswa</h3>
                    <p>
                        Memperhatikan kebutuhan dan perkembangan
                        belajar setiap siswa.
                    </p>
                </div>
            </article>

            <article>
                <span>02</span>

                <div>
                    <h3>Proses Belajar Terarah</h3>
                    <p>
                        Mendukung kegiatan belajar dengan
                        tujuan yang jelas.
                    </p>
                </div>
            </article>

            <article>
                <span>03</span>

                <div>
                    <h3>Lingkungan Positif</h3>
                    <p>
                        Mendorong siswa untuk aktif, berani
                        bertanya, dan terus berkembang.
                    </p>
                </div>
            </article>

        </div>

    </section>


    <!-- CALL TO ACTION -->
    <section class="cta-section">

        <div>

            <span class="section-kicker">
                MULAI LANGKAHMU
            </span>

            <h2>
                Siap Belajar dan Berkembang Bersama Kami?
            </h2>

            <p>
                Hubungi Smart Education Parepare untuk
                mendapatkan informasi lebih lanjut.
            </p>

        </div>

        <a class="button button-dark button-large" href="#kontak">
            Hubungi Kami <span>→</span>
        </a>

    </section>

</main>


<!-- FOOTER -->
<footer class="site-footer" id="kontak">

    <div class="footer-main">

        <div class="footer-brand">

            <a class="brand" href="#beranda">

                <span class="brand-mark">S</span>

                <span class="brand-copy">
                    <strong>SMART EDUCATION</strong>
                    <small>PAREPARE</small>
                </span>

            </a>

            <p>
                Teman belajar untuk membantu siswa
                mengembangkan potensi dan meraih masa depan cerah.
            </p>

        </div>


        <div class="footer-column">

            <h3>Navigasi</h3>

            <a href="#beranda">Beranda</a>
            <a href="#tentang">Tentang Kami</a>
            <a href="#program">Program</a>
            <a href="#keunggulan">Keunggulan</a>

        </div>


        <div class="footer-column">

            <h3>Informasi</h3>

            <a href="modules/auth/login.php">Login</a>
            <a href="modules/auth/register.php">Daftar</a>
            <a href="#kontak">Kontak</a>

        </div>


        <div class="footer-column">

            <h3>Hubungi Kami</h3>

            <p>Smart Education Parepare</p>
            <p>
                Tambahkan alamat dan nomor kontak resmi
                di bagian ini.
            </p>

        </div>

    </div>


    <div class="footer-bottom">

        <p>
            © <?= date("Y"); ?> Smart Education Parepare.
            Seluruh hak cipta dilindungi.
        </p>

        <a href="#beranda">
            Kembali ke atas ↑
        </a>

    </div>

</footer>

</body>
</html>
