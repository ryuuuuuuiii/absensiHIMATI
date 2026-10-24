-- ============================================
-- MIGRATION: Event Management Tables
-- Absensi HIMA TI Politeknik Hasnur
-- Run this AFTER initial database.sql
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";

-- ============================================
-- TABEL: events (Daftar Event/Kepanitiaan)
-- ============================================
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nama_event` VARCHAR(200) NOT NULL,
    `deskripsi` TEXT DEFAULT NULL,
    `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL: event_divisions (Divisi dalam Event)
-- ============================================
CREATE TABLE IF NOT EXISTS `event_divisions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `event_id` INT(11) NOT NULL,
    `nama_divisi` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_event_id` (`event_id`),
    CONSTRAINT `fk_division_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL: event_members (Panitia per Divisi)
-- ============================================
CREATE TABLE IF NOT EXISTS `event_members` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `event_id` INT(11) NOT NULL,
    `division_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `jabatan_panitia` VARCHAR(100) NOT NULL DEFAULT 'Anggota',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_event_div_user` (`event_id`, `division_id`, `user_id`),
    INDEX `idx_event_id` (`event_id`),
    INDEX `idx_division_id` (`division_id`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `fk_member_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_member_division` FOREIGN KEY (`division_id`) REFERENCES `event_divisions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ALTER: meetings — tambah event_id
-- ============================================
ALTER TABLE `meetings` ADD COLUMN `event_id` INT(11) DEFAULT NULL AFTER `departemen_collab`;
ALTER TABLE `meetings` ADD INDEX `idx_event_id` (`event_id`);
