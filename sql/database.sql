-- ============================================
-- DATABASE SCHEMA: Absensi HIMA TI (v2)
-- Politeknik Hasnur
-- Arsitektur: Admin-Only + Public Link
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+08:00";

-- ============================================
-- Drop existing tables (fresh install)
-- ============================================
DROP TABLE IF EXISTS `attendances`;
DROP TABLE IF EXISTS `meetings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admin`;
DROP TABLE IF EXISTS `settings`;

-- ============================================
-- TABEL: admin (Hanya 1 akun admin)
-- ============================================
CREATE TABLE IF NOT EXISTS `admin` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama` VARCHAR(100) NOT NULL DEFAULT 'Admin HIMA TI',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL: users (Registry Anggota + Akun Portal)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `departemen` VARCHAR(50) NOT NULL,
    `jabatan` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `password` VARCHAR(255) DEFAULT NULL,
    `qr_code` VARCHAR(255) NOT NULL UNIQUE,
    `qr_image` VARCHAR(255) DEFAULT NULL,
    `profile_photo` VARCHAR(255) DEFAULT NULL,
    `remember_token` VARCHAR(100) DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `is_bph` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_departemen` (`departemen`),
    INDEX `idx_qr_code` (`qr_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL: meetings (Rapat)
-- ============================================
CREATE TABLE IF NOT EXISTS `meetings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `created_by` INT(11) NOT NULL,
    `jenis_rapat` ENUM('internal_dept','perdana','kepanitiaan','bph','lintas_dept') NOT NULL,
    `mode_rapat` ENUM('offline','online') NOT NULL DEFAULT 'offline',
    `departemen` VARCHAR(100) DEFAULT NULL,
    `nama_event` VARCHAR(200) DEFAULT NULL,
    `divisi_rapat` VARCHAR(100) DEFAULT NULL,
    `tipe_divisi` ENUM('perdivisi','semua') DEFAULT NULL,
    `fokus_bahasan` TEXT DEFAULT NULL,
    `departemen_collab` TEXT DEFAULT NULL,
    `tanggal` DATE NOT NULL,
    `waktu_mulai` TIME NOT NULL,
    `waktu_selesai` TIME DEFAULT NULL,
    `status` ENUM('draft','scanning','completed','cancelled') NOT NULL DEFAULT 'draft',
    `unique_link` VARCHAR(64) NOT NULL UNIQUE,
    `absent_link` VARCHAR(64) NOT NULL UNIQUE,
    `foto_dokumentasi` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_jenis_rapat` (`jenis_rapat`),
    INDEX `idx_tanggal` (`tanggal`),
    INDEX `idx_status` (`status`),
    INDEX `idx_unique_link` (`unique_link`),
    INDEX `idx_absent_link` (`absent_link`),
    CONSTRAINT `fk_meeting_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL: attendances (Kehadiran)
-- ============================================
CREATE TABLE IF NOT EXISTS `attendances` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `meeting_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `status` ENUM('hadir','izin','sakit','alpha') NOT NULL DEFAULT 'alpha',
    `keterangan` TEXT DEFAULT NULL,
    `bukti_foto` VARCHAR(255) DEFAULT NULL,
    `scanned_at` DATETIME DEFAULT NULL,
    `scan_method` ENUM('qr_scan','online_link','manual','absent_form') NOT NULL DEFAULT 'qr_scan',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_meeting_user` (`meeting_id`, `user_id`),
    INDEX `idx_meeting_id` (`meeting_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_attendance_meeting` FOREIGN KEY (`meeting_id`) REFERENCES `meetings`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL: settings
-- ============================================
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT: Default Settings
-- ============================================
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('app_name', 'Absensi HIMA TI Politeknik Hasnur'),
('google_script_url', ''),
('google_drive_folder_id', ''),
('logo_1', ''),
('logo_2', ''),
('logo_3', ''),
('timezone', 'Asia/Makassar'),
('online_link_expiry_hours', '3'),
('max_photo_size_mb', '5');

-- ============================================
-- INSERT: Admin Account
-- Password: admin123
-- ============================================
INSERT INTO `admin` (`email`, `password`, `nama`) VALUES
('admin@himati.id', '$2y$10$HCYi/LbdTQbtty4hjSCN.uKSLFBCfgl4fSWBJMuuCEaLvKTWzBN.C', 'Admin HIMA TI');

-- ============================================
-- INSERT: Dummy Data — Pengurus HIMA TI
-- (Tanpa password — hanya registry anggota)
-- ============================================

-- Departemen Inti (BPH)
INSERT INTO `users` (`nama_lengkap`, `departemen`, `jabatan`, `email`, `qr_code`, `is_bph`) VALUES
('Ahmad Rizky Pratama', 'Inti', 'Ketua Umum', 'ketua@himati.id', 'HIMATI-USR-001-KETUA', 1),
('Siti Nurhaliza', 'Inti', 'Wakil Ketua Umum', 'wakil@himati.id', 'HIMATI-USR-002-WAKIL', 1),
('Muhammad Faisal', 'Inti', 'Sekretaris 1', 'sekretaris1@himati.id', 'HIMATI-USR-003-SEK1', 1),
('Dewi Anggraini', 'Inti', 'Sekretaris 2', 'sekretaris2@himati.id', 'HIMATI-USR-004-SEK2', 1),
('Reza Mahendra', 'Inti', 'Bendahara 1', 'bendahara1@himati.id', 'HIMATI-USR-005-BEN1', 1),
('Putri Rahmadani', 'Inti', 'Bendahara 2', 'bendahara2@himati.id', 'HIMATI-USR-006-BEN2', 1);

-- Departemen PSDM
INSERT INTO `users` (`nama_lengkap`, `departemen`, `jabatan`, `email`, `qr_code`, `is_bph`) VALUES
('Bayu Setiawan', 'PSDM', 'Kepala Departemen', 'kadep.psdm@himati.id', 'HIMATI-USR-007-KDPSDM', 1),
('Rina Wulandari', 'PSDM', 'Anggota', 'psdm1@himati.id', 'HIMATI-USR-008-PSDM1', 0),
('Andi Firmansyah', 'PSDM', 'Anggota', 'psdm2@himati.id', 'HIMATI-USR-009-PSDM2', 0),
('Nadia Safitri', 'PSDM', 'Anggota', 'psdm3@himati.id', 'HIMATI-USR-010-PSDM3', 0),
('Dimas Prasetyo', 'PSDM', 'Anggota', 'psdm4@himati.id', 'HIMATI-USR-011-PSDM4', 0),
('Lestari Handayani', 'PSDM', 'Anggota', 'psdm5@himati.id', 'HIMATI-USR-012-PSDM5', 0);

-- Departemen Medinfo
INSERT INTO `users` (`nama_lengkap`, `departemen`, `jabatan`, `email`, `qr_code`, `is_bph`) VALUES
('Irfan Hakim', 'Medinfo', 'Kepala Departemen', 'kadep.medinfo@himati.id', 'HIMATI-USR-013-KDMED', 1),
('Alya Maharani', 'Medinfo', 'Anggota', 'medinfo1@himati.id', 'HIMATI-USR-014-MED1', 0),
('Farhan Maulana', 'Medinfo', 'Anggota', 'medinfo2@himati.id', 'HIMATI-USR-015-MED2', 0),
('Jasmine Putri', 'Medinfo', 'Anggota', 'medinfo3@himati.id', 'HIMATI-USR-016-MED3', 0),
('Galih Pradipta', 'Medinfo', 'Anggota', 'medinfo4@himati.id', 'HIMATI-USR-017-MED4', 0),
('Tiara Aisyah', 'Medinfo', 'Anggota', 'medinfo5@himati.id', 'HIMATI-USR-018-MED5', 0);

-- Departemen Afkers
INSERT INTO `users` (`nama_lengkap`, `departemen`, `jabatan`, `email`, `qr_code`, `is_bph`) VALUES
('Hendra Kurniawan', 'Afkers', 'Kepala Departemen', 'kadep.afkers@himati.id', 'HIMATI-USR-019-KDAFK', 1),
('Melani Susanti', 'Afkers', 'Anggota', 'afkers1@himati.id', 'HIMATI-USR-020-AFK1', 0),
('Yoga Prabowo', 'Afkers', 'Anggota', 'afkers2@himati.id', 'HIMATI-USR-021-AFK2', 0),
('Intan Permata', 'Afkers', 'Anggota', 'afkers3@himati.id', 'HIMATI-USR-022-AFK3', 0),
('Raffi Adriansyah', 'Afkers', 'Anggota', 'afkers4@himati.id', 'HIMATI-USR-023-AFK4', 0),
('Citra Dewantari', 'Afkers', 'Anggota', 'afkers5@himati.id', 'HIMATI-USR-024-AFK5', 0);

-- Departemen Industif
INSERT INTO `users` (`nama_lengkap`, `departemen`, `jabatan`, `email`, `qr_code`, `is_bph`) VALUES
('Wahyu Hidayat', 'Industif', 'Kepala Departemen', 'kadep.industif@himati.id', 'HIMATI-USR-025-KDIND', 1),
('Sarah Amalia', 'Industif', 'Anggota', 'industif1@himati.id', 'HIMATI-USR-026-IND1', 0),
('Kevin Setiabudi', 'Industif', 'Anggota', 'industif2@himati.id', 'HIMATI-USR-027-IND2', 0),
('Diana Puspita', 'Industif', 'Anggota', 'industif3@himati.id', 'HIMATI-USR-028-IND3', 0),
('Arief Nugroho', 'Industif', 'Anggota', 'industif4@himati.id', 'HIMATI-USR-029-IND4', 0),
('Mega Silvia', 'Industif', 'Anggota', 'industif5@himati.id', 'HIMATI-USR-030-IND5', 0);

COMMIT;
