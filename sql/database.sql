-- ============================================
-- DATABASE SCHEMA & SEED: Absensi HIMA TI
-- Politeknik Hasnur
-- Generated for TiDB Cloud / MySQL
-- ============================================

CREATE DATABASE IF NOT EXISTS `test`;
USE `test`;

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Admin HIMA TI',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=653 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `departemen` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jabatan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nim` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nrp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `angkatan` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ttl` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qr_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `is_bph` tinyint(1) NOT NULL DEFAULT '0',
  `sp_level` tinyint unsigned NOT NULL DEFAULT '0',
  `sp_base_level` tinyint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sp_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sp_detail` text COLLATE utf8mb4_unicode_ci,
  `sp_issued_at` timestamp NULL DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_code` (`qr_code`),
  KEY `idx_departemen` (`departemen`),
  KEY `idx_qr_code` (`qr_code`),
  KEY `users_nim_index` (`nim`),
  KEY `users_nrp_index` (`nrp`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_event` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=500 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `event_divisions`;
CREATE TABLE `event_divisions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `nama_divisi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`),
  CONSTRAINT `fk_division_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=628 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `event_members`;
CREATE TABLE `event_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `division_id` int NOT NULL,
  `user_id` int NOT NULL,
  `jabatan_panitia` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Anggota',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_div_user` (`event_id`,`division_id`,`user_id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_division_id` (`division_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_member_division` FOREIGN KEY (`division_id`) REFERENCES `event_divisions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `meetings`;
CREATE TABLE `meetings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_by` int NOT NULL,
  `jenis_rapat` enum('internal_dept','perdana','kepanitiaan','bph','lintas_dept') COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode_rapat` enum('offline','online') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'offline',
  `departemen` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_event` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `divisi_rapat` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipe_divisi` enum('perdivisi','semua') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fokus_bahasan` text COLLATE utf8mb4_unicode_ci,
  `departemen_collab` text COLLATE utf8mb4_unicode_ci,
  `event_id` int DEFAULT NULL,
  `tanggal` date NOT NULL,
  `waktu_mulai` time NOT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `status` enum('draft','scanning','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `unique_link` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `absent_link` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_dokumentasi` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_link` (`unique_link`),
  UNIQUE KEY `absent_link` (`absent_link`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_jenis_rapat` (`jenis_rapat`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_status` (`status`),
  KEY `idx_unique_link` (`unique_link`),
  KEY `idx_absent_link` (`absent_link`),
  KEY `idx_event_id` (`event_id`),
  CONSTRAINT `fk_meeting_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `attendances`;
CREATE TABLE `attendances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `meeting_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('hadir','izin','sakit','alpha') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'alpha',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `bukti_foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scanned_at` datetime DEFAULT NULL,
  `scan_method` enum('qr_scan','online_link','manual','absent_form') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'qr_scan',
  `sp_ignored` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meeting_user` (`meeting_id`,`user_id`),
  KEY `idx_meeting_id` (`meeting_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_attendance_meeting` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE `feedback` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `kategori` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'saran',
  `subjek` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pesan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `screenshot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_user_id_index` (`user_id`),
  KEY `feedback_status_index` (`status`),
  KEY `feedback_kategori_index` (`kategori`),
  KEY `feedback_created_at_index` (`created_at`),
  CONSTRAINT `feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=294 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `judul` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `konten` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `target_departemen` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_user_id` int DEFAULT NULL,
  `is_banner` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `pinned` tinyint(1) NOT NULL DEFAULT '0',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_admin_id_foreign` (`admin_id`),
  KEY `announcements_target_type_index` (`target_type`),
  KEY `announcements_target_departemen_index` (`target_departemen`),
  KEY `announcements_target_user_id_index` (`target_user_id`),
  KEY `announcements_is_active_index` (`is_active`),
  KEY `announcements_is_banner_index` (`is_banner`),
  KEY `announcements_pinned_index` (`pinned`),
  KEY `announcements_created_at_index` (`created_at`),
  CONSTRAINT `announcements_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL,
  CONSTRAINT `announcements_target_user_id_foreign` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `announcement_reads`;
CREATE TABLE `announcement_reads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint unsigned NOT NULL,
  `user_id` int NOT NULL,
  `is_dismissed` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcement_reads_announcement_id_user_id_unique` (`announcement_id`,`user_id`),
  KEY `announcement_reads_user_id_index` (`user_id`),
  KEY `announcement_reads_is_dismissed_index` (`is_dismissed`),
  CONSTRAINT `announcement_reads_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stored_uploads`;
CREATE TABLE `stored_uploads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `folder` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int unsigned NOT NULL,
  `payload` longblob NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_folder_filename` (`folder`,`filename`),
  KEY `idx_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATA: admin
-- ============================================
INSERT INTO `admin` (`id`, `email`, `password`, `nama`, `created_at`) VALUES (1, 'admin@himati.id', '$2y$04$kqhcRch86xBywFX9XhqopOSGiz7sASI5uvvG4eOJHthwVLSfUFLmm', 'Admin HIMA TI', '2026-09-08 23:36:55');

-- ============================================
-- DATA: settings
-- ============================================
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('app_name', 'Absensi HIMA TI Politeknik Hasnur');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('google_script_url', 'https://script.google.com/macros/s/AKfycbyLfxTu21-oz2sxULh8nAPlOLOpBj3ZfpFajXbKoruaVuDiv2KPWiemv65lEuoSqQ7M7w/exec');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('google_drive_folder_id', '');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('logo_1', 'logo_1_1793111983.png');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('logo_2', 'logo_2_1793111911.png');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('logo_3', 'logo_3_1793111917.png');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('timezone', 'Asia/Makassar');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('online_link_expiry_hours', '3');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('max_photo_size_mb', '5');

-- ============================================
-- DATA: users (41 Pengurus Resmi)
-- ============================================
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (1, 'Iqbal Rupawan', 'Inti', 'Ketua Umum', '24302043', 'HMTI/02-26/KTU/01-CWL', '2024', '2006-10-05', 'iqbal@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/KTU/01-CWL', 'qr_1_HMTI02-26KTU01-CWL.png', NULL, NULL, NULL, 1, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:28', '2026-11-08 13:28:31');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (2, 'Muhammad Faisal', 'Inti', 'Wakil Ketua Umum', '25302008', 'HMTI/02-26/WKTU/02-CWL', '2025', '2006-04-13', 'faisal@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/WKTU/02-CWL', 'qr_2_HMTI02-26WKTU02-CWL.png', NULL, NULL, NULL, 1, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:31', '2026-11-08 13:28:32');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (3, 'Rofiatul Husna', 'Inti', 'Sekretaris Umum I', '25302029', 'HMTI/02-26/SKU1/03-CWL', '2025', '2007-04-06', 'rofiatul@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/SKU1/03-CWL', 'qr_3_HMTI02-26SKU103-CWL.png', NULL, NULL, NULL, 1, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:32', '2026-11-08 13:28:33');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (4, 'Seftia Hadliani', 'Inti', 'Sekretaris Umum II', '24302059', 'HMTI/02-26/SKU2/04-CWL', '2024', '2006-05-09', 'seftia@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/SKU2/04-CWL', 'qr_4_HMTI02-26SKU204-CWL.png', NULL, NULL, NULL, 1, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:33', '2026-11-08 13:28:33');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (5, 'Fatimmatuzahra', 'Inti', 'Bendahara Umum I', '25302040', 'HMTI/02-26/BDR1/05-CWL', '2025', '2007-01-29', 'fatimmatuzahra@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/BDR1/05-CWL', 'qr_5_HMTI02-26BDR105-CWL.png', NULL, NULL, NULL, 1, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:33', '2026-11-08 13:28:36');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (6, 'Halimatus Sa’diah', 'Inti', 'Bendahara Umum II', '24302017', 'HMTI/02-26/BDR2/06-CWL', '2024', '2005-06-12', 'halimatus@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/BDR2/06-CWL', 'qr_6_HMTI02-26BDR206-CWL.png', NULL, NULL, NULL, 1, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:36', '2026-11-08 13:28:36');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (7, 'Muhammad Wahyu Saputra', 'Medinfo', 'Koordinator', '24302045', 'HMTI/02-26/CO/D-MDI/07-CWL', '2024', '2006-07-31', 'wahyu@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/CO/D-MDI/07-CWL', 'qr_7_HMTI02-26COD-MDI07-CWL.png', NULL, NULL, NULL, 1, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:36', '2026-11-08 13:28:37');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (8, 'A. Nadil Ulum Annafis', 'Medinfo', 'Staff', '25302033', 'HMTI/02-26/D-MDI/08-CWL', '2025', '2006-05-31', 'nadil@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-MDI/08-CWL', 'qr_8_HMTI02-26D-MDI08-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:37', '2026-11-08 13:28:38');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (9, 'Muhammad Abdi', 'Medinfo', 'Staff', '24302057', 'HMTI/02-26/D-MDI/09-CWL', '2024', '2005-11-10', 'abdi@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-MDI/09-CWL', 'qr_9_HMTI02-26D-MDI09-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:38', '2026-11-08 13:28:38');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (10, 'Muhammad Rofie', 'Medinfo', 'Staff', '25302053', 'HMTI/02-26/D-MDI/10-CWL', '2025', '2006-03-29', 'rofie@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-MDI/10-CWL', 'qr_10_HMTI02-26D-MDI10-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:38', '2026-11-08 13:28:39');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (11, 'Noor Syarifah Putri Azzahra', 'Medinfo', 'Staff', '25302030', 'HMTI/02-26/D-MDI/11-CWL', '2025', '2007-01-02', 'syarifah@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-MDI/11-CWL', 'qr_11_HMTI02-26D-MDI11-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:39', '2026-11-08 13:28:40');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (12, 'Siti Khadijah', 'Medinfo', 'Staff', '25302039', 'HMTI/02-26/D-MDI/12-CWL', '2025', '2007-04-02', 'khadijah@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-MDI/12-CWL', 'qr_12_HMTI02-26D-MDI12-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:40', '2026-11-08 13:28:41');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (13, 'Isya Widiyana', 'Medinfo', 'Staff', '25302037', 'HMTI/02-26/D-MDI/13-CWL', '2025', '2007-01-17', 'isya@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-MDI/13-CWL', 'qr_13_HMTI02-26D-MDI13-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:41', '2026-11-08 13:28:42');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (14, 'Siti Aisyah', 'Medinfo', 'Staff', '25302026', 'HMTI/02-26/D-MDI/14-CWL', '2025', '2006-09-28', 'aisyah@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-MDI/14-CWL', 'qr_14_HMTI02-26D-MDI14-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:42', '2026-11-08 13:28:43');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (15, 'Sari Mulyani', 'Medinfo', 'Staff', '25302010', 'HMTI/02-26/D-MDI/15-CWL', '2025', '2006-12-18', 'sari@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-MDI/15-CWL', 'qr_15_HMTI02-26D-MDI15-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:43', '2026-11-08 13:28:43');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (16, 'Puja Dwi Anggreini', 'Medinfo', 'Staff', '25302038', 'HMTI/02-26/D-MDI/16-CWL', '2025', '2005-08-12', 'puja@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-MDI/16-CWL', 'qr_16_HMTI02-26D-MDI16-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:43', '2026-11-08 13:28:45');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (17, 'Muhammad Arif', 'PSDM', 'Koordinator', '24302026', 'HMTI/02-26/CO/D-PSDM/17-CWL', '2024', '2005-01-02', 'arif@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/CO/D-PSDM/17-CWL', 'qr_17_HMTI02-26COD-PSDM17-CWL.png', NULL, NULL, NULL, 1, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:45', '2026-11-08 13:28:46');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (18, 'Mu’min Yasin', 'PSDM', 'Staff', '25302011', 'HMTI/02-26/D-PSDM/18-CWL', '2025', '2007-04-02', 'mumin@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-PSDM/18-CWL', 'qr_18_HMTI02-26D-PSDM18-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:46', '2026-11-08 13:28:47');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (19, 'Nazwa Nur Rahma', 'PSDM', 'Staff', '25302001', 'HMTI/02-26/D-PSDM/20-CWL', '2025', '2007-07-24', 'nazwa@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-PSDM/20-CWL', 'qr_19_HMTI02-26D-PSDM20-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:47', '2026-11-08 13:28:48');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (20, 'Fitria', 'PSDM', 'Staff', '25302007', 'HMTI/02-26/D-PSDM/21-CWL', '2025', '2007-03-31', 'fitria@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-PSDM/21-CWL', 'qr_20_HMTI02-26D-PSDM21-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:48', '2026-11-08 13:28:51');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (21, 'Meidina Nur Aulia', 'PSDM', 'Staff', '25302036', 'HMTI/02-26/D-PSDM/22-CWL', '2025', '2007-08-15', 'meidina@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-PSDM/22-CWL', 'qr_21_HMTI02-26D-PSDM22-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:28:51', '2026-11-08 13:29:01');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (22, 'Raudhatul Hasanah', 'PSDM', 'Staff', '24302008', 'HMTI/02-26/D-PSDM/23-CWL', '2024', '2006-05-02', 'raudhatul@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-PSDM/23-CWL', 'qr_22_HMTI02-26D-PSDM23-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:01', '2026-11-08 13:29:02');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (23, 'Barkah', 'PSDM', 'Staff', '24302007', 'HMTI/02-26/D-PSDM/24-CWL', '2024', '2006-10-19', 'barkah@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-PSDM/24-CWL', 'qr_23_HMTI02-26D-PSDM24-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:02', '2026-11-08 13:29:03');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (24, 'Aidul Akbari', 'Industif', 'Koordinator', '24302013', 'HMTI/02-26/CO/D-IKA/26-CWL', '2024', '2007-02-01', 'aidul@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/CO/D-IKA/26-CWL', 'qr_24_HMTI02-26COD-IKA26-CWL.png', NULL, NULL, NULL, 1, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:03', '2026-11-08 13:29:03');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (25, 'Muhammad Hidayat', 'Industif', 'Staff', '24302031', 'HMTI/02-26/D-IKA/27-CWL', '2024', '2005-02-12', 'hidayat@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-IKA/27-CWL', 'qr_25_HMTI02-26D-IKA27-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:03', '2026-11-08 13:29:04');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (26, 'Muhammad Amin', 'Industif', 'Staff', '24302012', 'HMTI/02-26/D-IKA/28-CWL', '2024', NULL, 'amin@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-IKA/28-CWL', 'qr_26_HMTI02-26D-IKA28-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:04', '2026-11-08 13:29:10');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (27, 'Anida Rahmah', 'Industif', 'Staff', '25302020', 'HMTI/02-26/D-IKA/29-CWL', '2025', '2007-06-13', 'anida@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-IKA/29-CWL', 'qr_27_HMTI02-26D-IKA29-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:10', '2026-11-08 13:29:12');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (28, 'Sururia Mahmudah', 'Industif', 'Staff', '25302041', 'HMTI/02-26/D-IKA/31-CWL', '2025', '2007-04-21', 'sururia@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-IKA/31-CWL', 'qr_28_HMTI02-26D-IKA31-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:12', '2026-11-08 13:29:13');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (29, 'Raudatul Jannah', 'Industif', 'Staff', '24302044', 'HMTI/02-26/D-IKA/32-CWL', '2024', '2006-05-05', 'jannah@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-IKA/32-CWL', 'qr_29_HMTI02-26D-IKA32-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:13', '2026-11-08 13:29:14');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (30, 'Farras Dwie Cahyani', 'Industif', 'Staff', '24302032', 'HMTI/02-26/D-IKA/33-CWL', '2024', '2006-01-17', 'farras@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-IKA/33-CWL', 'qr_30_HMTI02-26D-IKA33-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:14', '2026-11-08 13:29:19');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (31, 'Risnawati', 'Industif', 'Staff', '24302003', 'HMTI/02-26/D-IKA/34-CWL', '2024', '2003-04-01', 'risnawati@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-IKA/34-CWL', 'qr_31_HMTI02-26D-IKA34-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:19', '2026-11-08 13:29:26');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (32, 'Noor Hidayah', 'Afkers', 'Koordinator', '24302029', 'HMTI/02-26/CO/D-AKS/35-CWL', '2024', '2005-01-11', 'noor.hidayah@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/CO/D-AKS/35-CWL', 'qr_32_HMTI02-26COD-AKS35-CWL.png', NULL, NULL, NULL, 1, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:26', '2026-11-08 13:29:26');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (33, 'Anton Maulana', 'Afkers', 'Staff', '25302031', 'HMTI/02-26/D-AKS/36-CWL', '2025', '2006-01-04', 'anton@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-AKS/36-CWL', 'qr_33_HMTI02-26D-AKS36-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:26', '2026-11-08 13:29:27');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (34, 'Muhammad Basit', 'Afkers', 'Staff', '25302005', 'HMTI/02-26/D-AKS/37-CWL', '2025', '2006-10-30', 'basit@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-AKS/37-CWL', 'qr_34_HMTI02-26D-AKS37-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:27', '2026-11-08 13:29:28');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (35, 'Eva Widya Safitri', 'Afkers', 'Staff', '25302002', 'HMTI/02-26/D-AKS/38-CWL', '2025', '2006-11-29', 'eva@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-AKS/38-CWL', 'qr_35_HMTI02-26D-AKS38-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:28', '2026-11-08 13:29:28');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (36, 'Salsa Agustina', 'Afkers', 'Staff', '25302047', 'HMTI/02-26/D-AKS/39-CWL', '2025', '2006-08-22', 'salsa@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-AKS/39-CWL', 'qr_36_HMTI02-26D-AKS39-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:28', '2026-11-08 13:29:29');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (37, 'Nabila Carolina Puteri', 'Afkers', 'Staff', '25302027', 'HMTI/02-26/D-AKS/40-CWL', '2025', '2007-05-04', 'carolina@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-AKS/40-CWL', 'qr_37_HMTI02-26D-AKS40-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:29', '2026-11-08 13:29:30');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (38, 'Sandriana Ariani', 'Afkers', 'Staff', '24302004', 'HMTI/02-26/D-AKS/41-CWL', '2024', '2006-02-10', 'sandriana@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-AKS/41-CWL', 'qr_38_HMTI02-26D-AKS41-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:30', '2026-11-08 13:29:40');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (39, 'Kharunnisa', 'Afkers', 'Staff', '24302030', 'HMTI/02-26/D-AKS/42-CWL', '2024', '2005-01-07', 'kharunnisa@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-AKS/42-CWL', 'qr_39_HMTI02-26D-AKS42-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:40', '2026-11-08 13:29:41');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (40, 'Muhammad Amin Badali', 'Afkers', 'Staff', '24302015', 'HMTI/02-26/D-AKS/43-CWL', '2024', '2005-10-08', 'badali@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-AKS/43-CWL', 'qr_40_HMTI02-26D-AKS43-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:41', '2026-11-08 13:29:42');
INSERT INTO `users` (`id`, `nama_lengkap`, `departemen`, `jabatan`, `nim`, `nrp`, `angkatan`, `ttl`, `email`, `password`, `qr_code`, `qr_image`, `profile_photo`, `remember_token`, `last_login_at`, `is_bph`, `sp_level`, `sp_base_level`, `is_active`, `sp_reason`, `sp_detail`, `sp_issued_at`, `created_at`, `updated_at`) VALUES (41, 'Siti Maryam', 'Afkers', 'Staff', '24302005', 'HMTI/02-26/D-AKS/44-CWL', '2024', '2005-08-09', 'maryam@himati.id', '$2y$12$.3WiUjETDAwVudXgZ65XoOo8e5r/202y8cYolufDTyFfFBc9P6y6O', 'HMTI/02-26/D-AKS/44-CWL', 'qr_41_HMTI02-26D-AKS44-CWL.png', NULL, NULL, NULL, 0, 0, 0, 1, NULL, NULL, NULL, '2026-11-08 13:29:42', '2026-11-08 13:29:43');

SET FOREIGN_KEY_CHECKS = 1;
