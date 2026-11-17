-- SMART EDUCATION PAREPARE Database Schema
-- Sistem Informasi Pengelolaan Data Siswa dan Pembayaran pada Bimbingan Belajar Smart Education Parepare

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `analisis_rfm`;
DROP TABLE IF EXISTS `jadwal`;
DROP TABLE IF EXISTS `pembayaran`;
DROP TABLE IF EXISTS `anggota_kelas`;
DROP TABLE IF EXISTS `kelas`;
DROP TABLE IF EXISTS `ruangan`;
DROP TABLE IF EXISTS `tutor`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `siswa`;
DROP TABLE IF EXISTS `paket_belajar`;
DROP TABLE IF EXISTS `pengaturan`;

-- 1. Table Paket Belajar
CREATE TABLE `paket_belajar` (
  `id_paket` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_paket` VARCHAR(100) NOT NULL,
  `jenjang` VARCHAR(50) NOT NULL,
  `harga` DECIMAL(12,2) NOT NULL,
  `durasi_bulan` INT NOT NULL DEFAULT 1,
  `pertemuan_per_minggu` INT NOT NULL DEFAULT 3,
  `deskripsi` TEXT,
  `status` ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table Siswa
CREATE TABLE `siswa` (
  `id_siswa` INT AUTO_INCREMENT PRIMARY KEY,
  `nis` VARCHAR(20) UNIQUE NOT NULL,
  `nama_siswa` VARCHAR(100) NOT NULL,
  `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL,
  `alamat` TEXT,
  `no_telepon` VARCHAR(20),
  `email` VARCHAR(100),
  `id_paket` INT NULL,
  `status_siswa` ENUM('Aktif', 'Alumni', 'Non-Aktif') DEFAULT 'Aktif',
  `tanggal_daftar` DATE NOT NULL,
  `foto` VARCHAR(255) DEFAULT 'default.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_siswa_paket` FOREIGN KEY (`id_paket`) REFERENCES `paket_belajar` (`id_paket`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table Users
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE,
  `role` ENUM('superadmin', 'admin', 'siswa') NOT NULL,
  `id_siswa` INT NULL,
  `foto` VARCHAR(255) DEFAULT 'default.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_users_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table Tutor
CREATE TABLE `tutor` (
  `id_tutor` INT AUTO_INCREMENT PRIMARY KEY,
  `nip` VARCHAR(20) UNIQUE NOT NULL,
  `nama_tutor` VARCHAR(100) NOT NULL,
  `no_telepon` VARCHAR(20),
  `email` VARCHAR(100),
  `keahlian` VARCHAR(100) NOT NULL,
  `status_tutor` ENUM('Aktif', 'Cuti', 'Non-Aktif') DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table Ruangan
CREATE TABLE `ruangan` (
  `id_ruangan` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_ruangan` VARCHAR(20) UNIQUE NOT NULL,
  `nama_ruangan` VARCHAR(50) NOT NULL,
  `kapasitas` INT NOT NULL DEFAULT 15,
  `fasilitas` VARCHAR(255),
  `status_ruangan` ENUM('Tersedia', 'Perbaikan', 'Tidak Tersedia') DEFAULT 'Tersedia',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table Kelas
CREATE TABLE `kelas` (
  `id_kelas` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_kelas` VARCHAR(20) UNIQUE NOT NULL,
  `nama_kelas` VARCHAR(100) NOT NULL,
  `id_paket` INT NOT NULL,
  `id_tutor` INT NOT NULL,
  `id_ruangan` INT NULL,
  `hari` VARCHAR(20) NULL,
  `jam_mulai` TIME NULL,
  `jam_selesai` TIME NULL,
  `max_siswa` INT DEFAULT 15,
  `status_kelas` ENUM('Aktif', 'Selesai', 'Batal') DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_kelas_paket` FOREIGN KEY (`id_paket`) REFERENCES `paket_belajar` (`id_paket`) ON DELETE CASCADE,
  CONSTRAINT `fk_kelas_tutor` FOREIGN KEY (`id_tutor`) REFERENCES `tutor` (`id_tutor`) ON DELETE RESTRICT,
  CONSTRAINT `fk_kelas_ruangan` FOREIGN KEY (`id_ruangan`) REFERENCES `ruangan` (`id_ruangan`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table Anggota Kelas
CREATE TABLE `anggota_kelas` (
  `id_anggota` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kelas` INT NOT NULL,
  `id_siswa` INT NOT NULL,
  `tanggal_gabung` DATE NOT NULL,
  UNIQUE KEY `unique_kelas_siswa` (`id_kelas`, `id_siswa`),
  CONSTRAINT `fk_anggota_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE,
  CONSTRAINT `fk_anggota_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table Pembayaran
CREATE TABLE `pembayaran` (
  `id_pembayaran` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_transaksi` VARCHAR(35) UNIQUE NOT NULL,
  `id_siswa` INT NOT NULL,
  `id_paket` INT NOT NULL,
  `nominal` DECIMAL(12,2) NOT NULL,
  `tanggal_bayar` DATE NOT NULL,
  `metode_pembayaran` ENUM('Tunai', 'QRIS', 'Transfer Bank') NOT NULL,
  `status_pembayaran` ENUM('Lunas', 'Menunggu Konfirmasi', 'Ditolak', 'Belum Lunas') DEFAULT 'Menunggu Konfirmasi',
  `bukti_pembayaran` VARCHAR(255) NULL,
  `keterangan` TEXT NULL,
  `diverifikasi_oleh` INT NULL,
  `tanggal_verifikasi` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pembayaran_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pembayaran_paket` FOREIGN KEY (`id_paket`) REFERENCES `paket_belajar` (`id_paket`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pembayaran_verifikator` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table Jadwal
CREATE TABLE `jadwal` (
  `id_jadwal` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kelas` INT NOT NULL,
  `id_tutor` INT NOT NULL,
  `id_ruangan` INT NOT NULL,
  `hari` ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') NOT NULL,
  `jam_mulai` TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `status_jadwal` ENUM('Aktif', 'Dibatalkan', 'Selesai') DEFAULT 'Aktif',
  `keterangan` VARCHAR(255) NULL,
  `metode_pembuatan` ENUM('Manual', 'CSP_Backtracking') DEFAULT 'Manual',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_jadwal_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE,
  CONSTRAINT `fk_jadwal_tutor` FOREIGN KEY (`id_tutor`) REFERENCES `tutor` (`id_tutor`) ON DELETE RESTRICT,
  CONSTRAINT `fk_jadwal_ruangan` FOREIGN KEY (`id_ruangan`) REFERENCES `ruangan` (`id_ruangan`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Table Analisis RFM
CREATE TABLE `analisis_rfm` (
  `id_rfm` INT AUTO_INCREMENT PRIMARY KEY,
  `id_siswa` INT NOT NULL,
  `periode_analisis` VARCHAR(50) NOT NULL,
  `recency_days` INT NOT NULL,
  `frequency_count` INT NOT NULL,
  `monetary_amount` DECIMAL(12,2) NOT NULL,
  `r_score` INT NOT NULL,
  `f_score` INT NOT NULL,
  `m_score` INT NOT NULL,
  `total_rfm_score` INT NOT NULL,
  `segmentasi` VARCHAR(50) NOT NULL,
  `tanggal_analisis` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rfm_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Table Pengaturan
CREATE TABLE `pengaturan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_instansi` VARCHAR(150) NOT NULL DEFAULT 'Smart Education Parepare',
  `tagline` VARCHAR(255) DEFAULT 'Bimbingan Belajar Terbaik, Terpadu & Berprestasi di Parepare',
  `alamat` TEXT,
  `telepon` VARCHAR(50) DEFAULT '0812-4455-6677',
  `email` VARCHAR(100) DEFAULT 'info@smarteducation-parepare.sch.id',
  `nama_bank` VARCHAR(50) DEFAULT 'Bank Mandiri',
  `no_rekening` VARCHAR(50) DEFAULT '174-00-1234567-8',
  `atas_nama` VARCHAR(100) DEFAULT 'Bimbel Smart Education Parepare',
  `qris_image` VARCHAR(255) DEFAULT 'assets/images/qris-dummy.png',
  `logo` VARCHAR(255) DEFAULT 'assets/images/logo.png',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- DUMMY SEED DATA
-- ==========================================================

-- Pengaturan
INSERT INTO `pengaturan` (`id`, `nama_instansi`, `tagline`, `alamat`, `telepon`, `email`, `nama_bank`, `no_rekening`, `atas_nama`, `qris_image`, `logo`)
VALUES (1, 'Smart Education Parepare', 'Solusi Belajar Cerdas & Berprestasi di Parepare', 'Jl. Bau Massepe No. 142, Kel. Mallusetasi, Kec. Ujung, Kota Parepare, Sulawesi Selatan 91111', '0812-4455-6677', 'admin@smarteducation.sch.id', 'Bank Mandiri', '174-00-9876543-2', 'Smart Education Parepare', 'assets/images/qris-dummy.png', 'assets/images/logo.png');

-- Paket Belajar
INSERT INTO `paket_belajar` (`id_paket`, `nama_paket`, `jenjang`, `harga`, `durasi_bulan`, `pertemuan_per_minggu`, `deskripsi`, `status`) VALUES
(1, 'Paket Bintang SD (Juara Kelas)', 'SD / MI', 350000.00, 1, 3, 'Pendampingan belajar Matematika, Tematik, dan Bahasa Inggris dengan metode interaktif untuk siswa kelas 4-6 SD.', 'Aktif'),
(2, 'Paket Prestasi SMP (Lolos Favorit)', 'SMP / MTs', 450000.00, 1, 3, 'Penguatan materi Matematika, IPA, dan Bahasa Inggris persiapan ujian sekolah dan olimpiade tingkat SMP.', 'Aktif'),
(3, 'Paket Intensif UTBK - SNBT SMA', 'SMA / MA / SMK', 750000.00, 1, 4, 'Fokus drilling soal Tes Potensi Skolastik (TPS), Literasi Bahasa, dan Penalaran Matematika lolos PTN impian.', 'Aktif'),
(4, 'Paket Kedinasan & Akpol/Akmil', 'Alumni / SMA', 950000.00, 1, 5, 'Pelatihan terpadu Tes Kemampuan Akademik, Psikotes, SKD CAT BKN, dan pembinaan kedinasan.', 'Aktif'),
(5, 'Paket TOEFL Preparation & English Master', 'Umum / Siswa', 500000.00, 1, 2, 'Program akselerasi kemampuan Listening, Reading, Structure & Written Expression untuk meraih skor 550+.', 'Aktif');

-- Ruangan
INSERT INTO `ruangan` (`id_ruangan`, `kode_ruangan`, `nama_ruangan`, `kapasitas`, `fasilitas`, `status_ruangan`) VALUES
(1, 'R-01', 'Ruang Galileo Galilei', 15, 'AC, Projector, Whiteboard, Speaker, High-speed Wifi', 'Tersedia'),
(2, 'R-02', 'Ruang Isaac Newton', 12, 'AC, Smart TV 55 Inch, Whiteboard, Ergonomic Chairs', 'Tersedia'),
(3, 'R-03', 'Ruang Albert Einstein', 20, 'AC, Whiteboard Double, Interactive Smart Board, Wifi', 'Tersedia'),
(4, 'R-04', 'Ruang Al-Khawarizmi', 10, 'AC, Whiteboard, Library Mini, Quiet Zone', 'Tersedia'),
(5, 'R-05', 'Ruang Marie Curie', 16, 'AC, Smart TV, Lab Sains Mini, Sound System', 'Tersedia');

-- Tutor
INSERT INTO `tutor` (`id_tutor`, `nip`, `nama_tutor`, `no_telepon`, `email`, `keahlian`, `status_tutor`) VALUES
(1, 'TUTOR001', 'Dr. Andi Fajar Nugraha, S.Pd., M.Si.', '0852-1111-2222', 'andi.fajar@smarteducation.sch.id', 'Matematika & Penalaran Kuantitatif', 'Aktif'),
(2, 'TUTOR002', 'Siti Rahmawati, M.Pd.', '0852-2222-3333', 'siti.rahma@smarteducation.sch.id', 'Bahasa Inggris & TOEFL Specialist', 'Aktif'),
(3, 'TUTOR003', 'Muhammad Rizky Pratama, S.Si.', '0852-3333-4444', 'rizky.pratama@smarteducation.sch.id', 'Fisika & Kimia Terapan', 'Aktif'),
(4, 'TUTOR004', 'Nurul Annisa, S.Pd.', '0852-4444-5555', 'nurul.annisa@smarteducation.sch.id', 'Biologi & Sains Tematik SD', 'Aktif'),
(5, 'TUTOR005', 'Bambang Sudarsono, S.Kom., M.T.', '0852-5555-6666', 'bambang.s@smarteducation.sch.id', 'Tes Bakat Skolastik & SKD Kedinasan', 'Aktif');

-- Siswa
INSERT INTO `siswa` (`id_siswa`, `nis`, `nama_siswa`, `jenis_kelamin`, `alamat`, `no_telepon`, `email`, `id_paket`, `status_siswa`, `tanggal_daftar`) VALUES
(1, '2026001', 'Ahmad Fadillah', 'Laki-laki', 'Jl. Sultan Hasanuddin No. 45, Parepare', '0813-9001-0001', 'ahmad.fadillah@gmail.com', 3, 'Aktif', '2026-01-10'),
(2, '2026002', 'Aisyah Putri Azzahra', 'Perempuan', 'Jl. Veteran No. 12, Parepare', '0813-9002-0002', 'aisyah.putri@gmail.com', 3, 'Aktif', '2026-01-15'),
(3, '2026003', 'Fikri Haikal', 'Laki-laki', 'Jl. Lasinrang No. 88, Parepare', '0813-9003-0003', 'fikri.haikal@gmail.com', 2, 'Aktif', '2026-02-01'),
(4, '2026004', 'Nabila Shafa', 'Perempuan', 'Jl. Mattirotasi No. 23, Parepare', '0813-9004-0004', 'nabila.shafa@gmail.com', 1, 'Aktif', '2026-02-10'),
(5, '2026005', 'Reza Kurniawan', 'Laki-laki', 'Jl. Ahmad Yani KM 3, Parepare', '0813-9005-0005', 'reza.kurniawan@gmail.com', 4, 'Aktif', '2026-02-15'),
(6, '2026006', 'Dinda Permata Sari', 'Perempuan', 'Jl. Jenderal Sudirman No. 71, Parepare', '0813-9006-0006', 'dinda.permata@gmail.com', 5, 'Aktif', '2026-03-01'),
(7, '2026007', 'Farhan Maulana', 'Laki-laki', 'Jl. Bau Massepe No. 201, Parepare', '0813-9007-0007', 'farhan.m@gmail.com', 3, 'Aktif', '2026-03-05'),
(8, '2026008', 'Gita Amelia', 'Perempuan', 'Jl. Kebun Sayur No. 15, Parepare', '0813-9008-0008', 'gita.amelia@gmail.com', 2, 'Aktif', '2026-03-12'),
(9, '2026009', 'Hendra Gunawan', 'Laki-laki', 'Jl. H. Agussalim No. 34, Parepare', '0813-9009-0009', 'hendra.g@gmail.com', 4, 'Aktif', '2026-03-20'),
(10, '2026010', 'Indah Lestari', 'Perempuan', 'Jl. Laupe No. 10, Parepare', '0813-9010-0010', 'indah.lestari@gmail.com', 1, 'Aktif', '2026-04-01');

-- Users
-- Password for admin123: $2y$10$WhCvyH07Lp0Z5cViXXcBN.nKFfOQpoMxSD/NNb0/ppGfP.DpedbzK
-- Password for siswa123: $2y$10$6GzFyCJaE8/yzk90.I9fvO75pJ98EW1DvTN5wPT8/hBvsEy/0PTDK
INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `role`, `id_siswa`, `foto`) VALUES
(1, 'superadmin', '$2y$10$WhCvyH07Lp0Z5cViXXcBN.nKFfOQpoMxSD/NNb0/ppGfP.DpedbzK', 'Super Administrator Utama', 'superadmin@smarteducation.sch.id', 'superadmin', NULL, 'default.png'),
(2, 'admin', '$2y$10$WhCvyH07Lp0Z5cViXXcBN.nKFfOQpoMxSD/NNb0/ppGfP.DpedbzK', 'Administrator Akademik', 'admin@smarteducation.sch.id', 'admin', NULL, 'default.png'),
(3, 'siswa1', '$2y$10$6GzFyCJaE8/yzk90.I9fvO75pJ98EW1DvTN5wPT8/hBvsEy/0PTDK', 'Ahmad Fadillah', 'ahmad.fadillah@gmail.com', 'siswa', 1, 'default.png'),
(4, 'siswa2', '$2y$10$6GzFyCJaE8/yzk90.I9fvO75pJ98EW1DvTN5wPT8/hBvsEy/0PTDK', 'Aisyah Putri Azzahra', 'aisyah.putri@gmail.com', 'siswa', 2, 'default.png'),
(5, 'siswa3', '$2y$10$6GzFyCJaE8/yzk90.I9fvO75pJ98EW1DvTN5wPT8/hBvsEy/0PTDK', 'Fikri Haikal', 'fikri.haikal@gmail.com', 'siswa', 3, 'default.png'),
(6, 'siswa4', '$2y$10$6GzFyCJaE8/yzk90.I9fvO75pJ98EW1DvTN5wPT8/hBvsEy/0PTDK', 'Nabila Shafa', 'nabila.shafa@gmail.com', 'siswa', 4, 'default.png'),
(7, 'siswa5', '$2y$10$6GzFyCJaE8/yzk90.I9fvO75pJ98EW1DvTN5wPT8/hBvsEy/0PTDK', 'Reza Kurniawan', 'reza.kurniawan@gmail.com', 'siswa', 5, 'default.png');

-- Kelas
INSERT INTO `kelas` (`id_kelas`, `kode_kelas`, `nama_kelas`, `id_paket`, `id_tutor`, `id_ruangan`, `hari`, `jam_mulai`, `jam_selesai`, `max_siswa`, `status_kelas`) VALUES
(1, 'KLS-SNBT-01', 'Kelas Intensif SNBT Alfa', 3, 1, 1, 'Senin', '15:30:00', '17:00:00', 15, 'Aktif'),
(2, 'KLS-SNBT-02', 'Kelas Intensif SNBT Beta', 3, 3, 2, 'Selasa', '15:30:00', '17:00:00', 12, 'Aktif'),
(3, 'KLS-SMP-01', 'Kelas Prestasi SMP Juara', 2, 4, 3, 'Rabu', '13:30:00', '15:00:00', 15, 'Aktif'),
(4, 'KLS-SD-01', 'Kelas Bintang SD Ceria', 1, 2, 4, 'Kamis', '13:30:00', '15:00:00', 10, 'Aktif'),
(5, 'KLS-KDN-01', 'Kelas Kedinasan Tangguh', 4, 5, 5, 'Jumat', '15:30:00', '17:00:00', 15, 'Aktif'),
(6, 'KLS-ENG-01', 'Kelas English TOEFL Master', 5, 2, 1, 'Sabtu', '08:30:00', '10:00:00', 15, 'Aktif');

-- Anggota Kelas
INSERT INTO `anggota_kelas` (`id_kelas`, `id_siswa`, `tanggal_gabung`) VALUES
(1, 1, '2026-01-11'),
(1, 2, '2026-01-16'),
(1, 7, '2026-03-06'),
(2, 2, '2026-02-01'),
(3, 3, '2026-02-02'),
(3, 8, '2026-03-13'),
(4, 4, '2026-02-11'),
(4, 10, '2026-04-02'),
(5, 5, '2026-02-16'),
(5, 9, '2026-03-21'),
(6, 6, '2026-03-02');

-- Jadwal Belajar
INSERT INTO `jadwal` (`id_jadwal`, `id_kelas`, `id_tutor`, `id_ruangan`, `hari`, `jam_mulai`, `jam_selesai`, `status_jadwal`, `keterangan`, `metode_pembuatan`) VALUES
(1, 1, 1, 1, 'Senin', '15:30:00', '17:00:00', 'Aktif', 'Matematika Penalaran & TPS', 'Manual'),
(2, 2, 3, 2, 'Selasa', '15:30:00', '17:00:00', 'Aktif', 'Fisika & Kimia Intensif', 'Manual'),
(3, 3, 4, 3, 'Rabu', '13:30:00', '15:00:00', 'Aktif', 'IPA Terpadu & Matematika SMP', 'Manual'),
(4, 4, 2, 4, 'Kamis', '13:30:00', '15:00:00', 'Aktif', 'English & Tematik SD Ceria', 'Manual'),
(5, 5, 5, 5, 'Jumat', '15:30:00', '17:00:00', 'Aktif', 'Simulasi CAT SKD & Wawancara', 'Manual'),
(6, 6, 2, 1, 'Sabtu', '08:30:00', '10:00:00', 'Aktif', 'Structure & Listening Drill', 'Manual');

-- Pembayaran
-- Designed to produce rich RFM segments:
-- Ahmad Fadillah (id_siswa 1): frequent recent high spender -> Champions
-- Aisyah Putri Azzahra (id_siswa 2): frequent recent -> Loyal / Champions
-- Fikri Haikal (id_siswa 3): moderate recency, 2 payments -> Potential Loyalist
-- Nabila Shafa (id_siswa 4): 1 payment recently -> Recent Customer
-- Reza Kurniawan (id_siswa 5): high value, moderate frequency -> Promising
-- Gita Amelia (id_siswa 8): older payment -> At Risk / Need Attention
-- Hendra Gunawan (id_siswa 9): long ago -> Hibernating
INSERT INTO `pembayaran` (`id_pembayaran`, `kode_transaksi`, `id_siswa`, `id_paket`, `nominal`, `tanggal_bayar`, `metode_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `keterangan`, `diverifikasi_oleh`, `tanggal_verifikasi`) VALUES
(1, 'TRX-20261101-001', 1, 3, 750000.00, '2026-11-05', 'QRIS', 'Lunas', 'dummy-qris-1.jpg', 'Pembayaran SPP Bulan November 2026', 1, '2026-11-05 10:15:00'),
(2, 'TRX-20261001-002', 1, 3, 750000.00, '2026-10-02', 'Transfer Bank', 'Lunas', 'dummy-tf-1.jpg', 'Pembayaran SPP Bulan Oktober 2026', 2, '2026-10-02 14:20:00'),
(3, 'TRX-20260901-003', 1, 3, 750000.00, '2026-09-03', 'Tunai', 'Lunas', NULL, 'Pembayaran SPP Bulan September 2026', 2, '2026-09-03 16:00:00'),
(4, 'TRX-20260801-004', 1, 3, 750000.00, '2026-08-04', 'Tunai', 'Lunas', NULL, 'Pembayaran SPP Bulan Agustus 2026', 1, '2026-08-04 09:30:00'),
(5, 'TRX-20261102-005', 2, 3, 750000.00, '2026-11-08', 'QRIS', 'Lunas', 'dummy-qris-2.jpg', 'Pembayaran SPP Bulan November 2026', 2, '2026-11-08 11:00:00'),
(6, 'TRX-20261005-006', 2, 3, 750000.00, '2026-10-05', 'QRIS', 'Lunas', 'dummy-qris-3.jpg', 'Pembayaran SPP Bulan Oktober 2026', 2, '2026-10-05 13:45:00'),
(7, 'TRX-20260907-007', 2, 3, 750000.00, '2026-09-07', 'Transfer Bank', 'Lunas', 'dummy-tf-2.jpg', 'Pembayaran SPP Bulan September 2026', 1, '2026-09-07 10:00:00'),
(8, 'TRX-20261020-008', 3, 2, 450000.00, '2026-10-20', 'Tunai', 'Lunas', NULL, 'Pembayaran SPP Bulan Oktober 2026', 2, '2026-10-20 15:30:00'),
(9, 'TRX-20260920-009', 3, 2, 450000.00, '2026-09-20', 'Tunai', 'Lunas', NULL, 'Pembayaran SPP Bulan September 2026', 2, '2026-09-20 14:15:00'),
(10, 'TRX-20261110-010', 4, 1, 350000.00, '2026-11-10', 'QRIS', 'Lunas', 'dummy-qris-4.jpg', 'Pembayaran Pendaftaran & SPP Pertama', 1, '2026-11-10 16:30:00'),
(11, 'TRX-20261015-011', 5, 4, 950000.00, '2026-10-15', 'Transfer Bank', 'Lunas', 'dummy-tf-3.jpg', 'Pembayaran Paket Kedinasan Bulan 1', 1, '2026-10-15 11:20:00'),
(12, 'TRX-20260915-012', 5, 4, 950000.00, '2026-09-15', 'Transfer Bank', 'Lunas', 'dummy-tf-4.jpg', 'Pembayaran Uang Masuk Kedinasan', 1, '2026-09-15 08:45:00'),
(13, 'TRX-20261025-013', 6, 5, 500000.00, '2026-10-25', 'QRIS', 'Lunas', 'dummy-qris-5.jpg', 'Pembayaran Kelas TOEFL Master', 2, '2026-10-25 10:10:00'),
(14, 'TRX-20260710-014', 8, 2, 450000.00, '2026-07-10', 'Tunai', 'Lunas', NULL, 'Pembayaran SPP Bulan Juli 2026', 2, '2026-07-10 13:00:00'),
(15, 'TRX-20260605-015', 9, 4, 950000.00, '2026-06-05', 'Transfer Bank', 'Lunas', 'dummy-tf-5.jpg', 'Pembayaran SPP Juni 2026', 1, '2026-06-05 09:00:00'),
(16, 'TRX-20261111-016', 7, 3, 750000.00, '2026-11-11', 'QRIS', 'Menunggu Konfirmasi', 'dummy-qris-pending.jpg', 'Konfirmasi Pembayaran Bulan November 2026', NULL, NULL),
(17, 'TRX-20261111-017', 10, 1, 350000.00, '2026-11-11', 'Tunai', 'Belum Lunas', NULL, 'Tagihan Bulan November 2026', NULL, NULL);

-- Seed awal tabel analisis_rfm
INSERT INTO `analisis_rfm` (`id_siswa`, `periode_analisis`, `recency_days`, `frequency_count`, `monetary_amount`, `r_score`, `f_score`, `m_score`, `total_rfm_score`, `segmentasi`, `tanggal_analisis`) VALUES
(1, 'Semua Waktu', 6, 4, 3000000.00, 5, 5, 5, 15, 'Champions', NOW()),
(2, 'Semua Waktu', 3, 3, 2250000.00, 5, 4, 4, 13, 'Champions', NOW()),
(3, 'Semua Waktu', 22, 2, 900000.00, 4, 3, 2, 9, 'Potential Loyalists', NOW()),
(4, 'Semua Waktu', 1, 1, 350000.00, 5, 1, 1, 7, 'Recent Customers', NOW()),
(5, 'Semua Waktu', 27, 2, 1900000.00, 4, 3, 4, 11, 'Loyal Students', NOW()),
(6, 'Semua Waktu', 17, 1, 500000.00, 4, 1, 2, 7, 'Promising', NOW()),
(8, 'Semua Waktu', 124, 1, 450000.00, 1, 1, 1, 3, 'Hibernating', NOW()),
(9, 'Semua Waktu', 159, 1, 950000.00, 1, 1, 2, 4, 'At Risk', NOW());

