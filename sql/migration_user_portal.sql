-- ============================================
-- MIGRATION: Portal Pengurus (Member Login)
-- Absensi HIMA TI Politeknik Hasnur
-- Run this AFTER database.sql
-- ============================================

ALTER TABLE `users`
    ADD COLUMN `password` VARCHAR(255) DEFAULT NULL AFTER `email`,
    ADD COLUMN `profile_photo` VARCHAR(255) DEFAULT NULL AFTER `qr_image`,
    ADD COLUMN `remember_token` VARCHAR(100) DEFAULT NULL AFTER `profile_photo`,
    ADD COLUMN `last_login_at` DATETIME DEFAULT NULL AFTER `remember_token`;

-- Setelah migrasi, buka menu Admin > Akun Pengurus (/members/accounts)
-- lalu klik "Buat Akun yang Belum Ada" untuk membuat akun login massal.

