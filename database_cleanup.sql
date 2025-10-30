-- ============================================
-- Database Cleanup Script
-- Attendance System - Remove Unnecessary Tables
-- ============================================
-- 
-- This script drops all tables that are not needed for the core
-- attendance functionality (receiving data from devices and storing
-- in staff_attendance table).
--
-- IMPORTANT: Backup your database before running this script!
--
-- Usage:
-- mysql -u your_username -p your_database_name < database_cleanup.sql
-- OR
-- Run each DROP TABLE command individually in your MySQL client
--
-- ============================================

-- Drop device management tables
DROP TABLE IF EXISTS `devices`;
DROP TABLE IF EXISTS `device_handshake_configs`;
DROP TABLE IF EXISTS `device_log`;

-- Drop error logging table
DROP TABLE IF EXISTS `error_log`;

-- Drop authentication and user tables
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `personal_access_tokens`;

-- Drop Laravel job/queue tables
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `jobs`;

-- Drop fingerprint log table
DROP TABLE IF EXISTS `finger_log`;

-- Drop prayer attendance tables (if exist)
DROP TABLE IF EXISTS `absensi_sholat`;
DROP TABLE IF EXISTS `jadwal_sholat`;

-- Drop Laravel migrations tracking table
-- WARNING: This will remove migration history
-- Only drop if you're sure you won't need to rollback migrations
DROP TABLE IF EXISTS `migrations`;

-- ============================================
-- Verification Query
-- ============================================
-- After running the cleanup, verify only staff_attendance table remains:
-- 
-- SHOW TABLES;
--
-- Expected result: Only 'staff_attendance' table should be listed
-- ============================================

-- ============================================
-- Optional: Reset staff_attendance table
-- ============================================
-- Uncomment the following lines if you want to clear all attendance data
-- and start fresh (WARNING: This will delete all attendance records!)
--
-- TRUNCATE TABLE `staff_attendance`;
-- ALTER TABLE `staff_attendance` AUTO_INCREMENT = 1;
-- ============================================

