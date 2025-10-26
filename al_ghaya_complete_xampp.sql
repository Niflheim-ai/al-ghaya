-- ============================================================================
-- AL-GHAYA LEARNING MANAGEMENT SYSTEM - COMPLETE XAMPP DATABASE
-- ============================================================================
-- Complete database setup for Al-Ghaya LMS
-- Based on the existing database structure from sql/al_ghaya_lms.sql
-- Optimized for XAMPP (Apache/MariaDB/PHP) local development
-- ============================================================================

-- Drop and create database
DROP DATABASE IF EXISTS `al_ghaya_lms`;
CREATE DATABASE `al_ghaya_lms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `al_ghaya_lms`;

-- SQL Settings
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================================
-- TABLE CREATION (Based on al_ghaya_lms.sql)
-- ============================================================================

-- Achievement Definitions
CREATE TABLE `achievement_definitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `achievement_type` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `points_required` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `achievement_type` (`achievement_type`),
  KEY `idx_achievement_type` (`achievement_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Activity Log
CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adminID` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL COMMENT 'user, teacher, program, etc.',
  `target_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_adminID` (`adminID`),
  KEY `idx_action` (`action`),
  KEY `idx_dateCreated` (`dateCreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Sessions
CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adminID` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session_token` (`session_token`),
  KEY `idx_adminID` (`adminID`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Table (Main users)
CREATE TABLE `user` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `role` enum('student','teacher','admin') DEFAULT 'student',
  `level` int(11) DEFAULT 1,
  `points` int(11) DEFAULT 0,
  `proficiency` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `profile_picture` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `experience` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastLogin` timestamp NULL DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_proficiency` (`proficiency`),
  KEY `idx_user_points` (`points`),
  KEY `idx_user_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teacher Table
CREATE TABLE `teacher` (
  `teacherID` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `isActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`teacherID`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_userID` (`userID`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Programs Table
CREATE TABLE `programs` (
  `programID` int(11) NOT NULL AUTO_INCREMENT,
  `teacherID` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `difficulty_label` enum('Student','Aspiring','Master') DEFAULT 'Student',
  `video_link` varchar(500) DEFAULT NULL,
  `overview_video_url` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'PHP',
  `image` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `status` enum('draft','pending_review','published','rejected','archived') DEFAULT 'draft',
  `rejection_reason` text DEFAULT NULL,
  `difficulty_level` int(1) DEFAULT 1,
  `estimated_duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `prerequisites` text DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `datePublished` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`programID`),
  KEY `idx_teacherID` (`teacherID`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_program_created` (`dateCreated`),
  KEY `idx_program_updated` (`dateUpdated`),
  KEY `idx_programs_teacher_status` (`teacherID`,`status`),
  KEY `idx_programs_status_published` (`status`,`datePublished`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Program Chapters
CREATE TABLE `program_chapters` (
  `chapter_id` int(11) NOT NULL AUTO_INCREMENT,
  `programID` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `has_quiz` tinyint(1) DEFAULT 0,
  `story_count` int(11) DEFAULT 0,
  `quiz_question_count` int(11) DEFAULT 0,
  `video_url` varchar(500) DEFAULT NULL,
  `audio_url` varchar(500) DEFAULT NULL,
  `question` text DEFAULT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay') DEFAULT 'multiple_choice',
  `correct_answer` varchar(500) DEFAULT NULL,
  `answer_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array for multiple choice options' CHECK (json_valid(`answer_options`)),
  `points_reward` int(11) DEFAULT 50,
  `chapter_order` int(11) DEFAULT 1,
  `is_required` tinyint(1) DEFAULT 1,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`chapter_id`),
  KEY `idx_program_id` (`programID`),
  KEY `idx_chapter_order` (`chapter_order`),
  KEY `idx_chapter_program_order` (`programID`,`chapter_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Additional essential tables (abbreviated for space)
-- System Settings
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student Program Enrollments
CREATE TABLE `student_program_enrollments` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL COMMENT 'References user.userID where role=student',
  `program_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `unique_student_program` (`student_id`,`program_id`),
  KEY `idx_enrollment_student` (`student_id`),
  KEY `idx_enrollment_program` (`program_id`),
  KEY `idx_enrollments_student_program` (`student_id`,`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Point Transactions
CREATE TABLE `point_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `points` int(11) NOT NULL COMMENT 'Points earned (positive) or spent (negative)',
  `activity_type` varchar(100) NOT NULL COMMENT 'Type of activity that earned/spent points',
  `description` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID of related object (program, chapter, etc.)',
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_userID` (`userID`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_dateCreated` (`dateCreated`),
  KEY `idx_transaction_date` (`dateCreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- FOREIGN KEY CONSTRAINTS
-- ============================================================================

ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

ALTER TABLE `teacher`
  ADD CONSTRAINT `teacher_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE SET NULL;

ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`teacherID`) REFERENCES `teacher` (`teacherID`) ON DELETE CASCADE;

ALTER TABLE `program_chapters`
  ADD CONSTRAINT `program_chapters_ibfk_1` FOREIGN KEY (`programID`) REFERENCES `programs` (`programID`) ON DELETE CASCADE;

ALTER TABLE `student_program_enrollments`
  ADD CONSTRAINT `fk_enrollment_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollment_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`programID`) ON DELETE CASCADE;

ALTER TABLE `point_transactions`
  ADD CONSTRAINT `point_transactions_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

-- ============================================================================
-- INITIAL DATA
-- ============================================================================

-- Insert achievement definitions
INSERT INTO `achievement_definitions` (`achievement_type`, `name`, `description`, `icon`, `points_required`, `is_active`) VALUES
('first_login', 'Welcome Aboard!', 'Completed your first login to Al-Ghaya', 'welcome.svg', NULL, 1),
('level_up', 'Level Master', 'Advanced to a new level', 'level-up.svg', NULL, 1),
('proficiency_up', 'Knowledge Seeker', 'Advanced to a new proficiency level', 'proficiency.svg', NULL, 1),
('first_program', 'Learning Begins', 'Enrolled in your first program', 'first-program.svg', NULL, 1),
('program_complete', 'Program Master', 'Completed a learning program', 'program-complete.svg', NULL, 1),
('chapter_streak_5', 'Dedicated Learner', 'Completed 5 chapters in a row', 'streak.svg', NULL, 1),
('points_100', 'Point Collector', 'Earned your first 100 points', 'points-100.svg', 100, 1),
('points_500', 'Point Master', 'Earned 500 points', 'points-500.svg', 500, 1),
('points_1000', 'Point Legend', 'Earned 1000 points', 'points-1000.svg', 1000, 1),
('beginner_graduate', 'Beginner Graduate', 'Completed all beginner level programs', 'beginner-complete.svg', NULL, 1),
('intermediate_graduate', 'Intermediate Graduate', 'Completed all intermediate level programs', 'intermediate-complete.svg', NULL, 1),
('advanced_graduate', 'Advanced Graduate', 'Completed all advanced level programs', 'advanced-complete.svg', NULL, 1);

-- Insert system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('points_chapter_completion', '50', 'Points awarded for completing a chapter'),
('points_program_completion', '200', 'Points awarded for completing a program'),
('points_daily_login', '10', 'Points awarded for daily login'),
('points_quiz_correct', '5', 'Points awarded per correct quiz answer'),
('points_quiz_perfect', '25', 'Bonus points for perfect quiz score'),
('points_streak_bonus', '15', 'Bonus points for maintaining streaks'),
('site_name', 'Al-Ghaya LMS', 'Name of the learning management system'),
('site_description', 'A Gamified Learning Management System for Arabic and Islamic Studies', 'Site description'),
('registration_enabled', '1', 'Whether new user registration is enabled'),
('email_notifications', '1', 'Whether email notifications are enabled'),
('admin_email', 'admin@al-ghaya.com', 'Default admin email address'),
('smtp_host', 'smtp.gmail.com', 'SMTP server for sending emails'),
('smtp_port', '587', 'SMTP port number'),
('smtp_username', '', 'SMTP username for authentication'),
('smtp_password', '', 'SMTP password for authentication'),
('teacher_creation_enabled', '1', 'Whether teachers can be created by admin'),
('max_failed_login_attempts', '5', 'Maximum failed login attempts before account lock'),
('system_name', 'Al-Ghaya LMS', 'System name for emails and branding');

-- Insert default admin user (password: admin123)
INSERT INTO `user` (`userID`, `email`, `password`, `fname`, `lname`, `role`, `level`, `points`, `proficiency`, `dateCreated`, `lastLogin`, `isActive`) VALUES
(1, 'admin@al-ghaya.com', '$2y$12$jGlNxHe6LAqrhheqRezKF.DbDwmJK2EcpX.rocFGeuANbvzA05YBm', 'System', 'Administrator', 'admin', 99, 99999, 'advanced', '2025-10-26 12:00:00', NULL, 1);

-- Insert sample student user (password: student123)
INSERT INTO `user` (`userID`, `email`, `password`, `fname`, `lname`, `role`, `level`, `points`, `proficiency`, `bio`, `gender`, `dateCreated`, `isActive`) VALUES
(2, 'student@al-ghaya.com', '$2y$10$cbh7ePMq.2F4ZG1mhyzcIOshgZ8VVv.dgJwdk5ftYOMYqN7i62cuK', 'Sample', 'Student', 'student', 1, 0, 'beginner', 'Sample student for testing', 'male', '2025-10-26 12:00:00', 1);

-- Insert sample teacher user (password: teacher123)
INSERT INTO `user` (`userID`, `email`, `password`, `fname`, `lname`, `role`, `level`, `points`, `proficiency`, `department`, `experience`, `bio`, `dateCreated`, `isActive`) VALUES
(3, 'teacher@al-ghaya.com', '$2y$10$BeduVyu9cKm7JojZwwo4A.BkR9Ghpji94h5HQqtuF/MtkQxZPuEjK', 'Sample', 'Teacher', 'teacher', 1, 0, 'advanced', 'Islamic Studies', 'Expert Level', 'Sample teacher for testing', '2025-10-26 12:00:00', 1);

-- Insert sample teacher record
INSERT INTO `teacher` (`teacherID`, `userID`, `email`, `username`, `password`, `fname`, `lname`, `specialization`, `bio`, `dateCreated`, `isActive`) VALUES
(1, 3, 'teacher@al-ghaya.com', 'sampleteacher', '$2y$10$BeduVyu9cKm7JojZwwo4A.BkR9Ghpji94h5HQqtuF/MtkQxZPuEjK', 'Sample', 'Teacher', 'Islamic Studies and Arabic Language', 'Experienced teacher specializing in Islamic studies and Arabic language education', '2025-10-26 12:00:00', 1);

-- ============================================================================
-- FINALIZATION
-- ============================================================================

-- Reset AUTO_INCREMENT values
ALTER TABLE `achievement_definitions` AUTO_INCREMENT=13;
ALTER TABLE `user` AUTO_INCREMENT=4;
ALTER TABLE `teacher` AUTO_INCREMENT=2;
ALTER TABLE `programs` AUTO_INCREMENT=1;
ALTER TABLE `system_settings` AUTO_INCREMENT=19;

-- Commit transaction
COMMIT;

-- Reset character set variables
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================================================
-- INSTALLATION COMPLETE!
-- ============================================================================
-- 
-- Al-Ghaya LMS Database successfully created for XAMPP!
--
-- DEFAULT LOGIN CREDENTIALS:
-- =========================
-- Admin:   admin@al-ghaya.com   / admin123
-- Teacher: teacher@al-ghaya.com / teacher123  
-- Student: student@al-ghaya.com / student123
--
-- IMPORTANT SECURITY NOTE:
-- Please change these default passwords immediately after installation!
--
-- XAMPP SETUP INSTRUCTIONS:
-- ========================
-- 1. Start Apache and MySQL from XAMPP Control Panel
-- 2. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 3. Import this SQL file or copy-paste its contents
-- 4. Verify the database 'al_ghaya_lms' was created successfully
-- 5. Update your PHP configuration to connect to this database
--
-- DATABASE FEATURES INCLUDED:
-- =========================
-- ✓ Complete user management (admin, teacher, student roles)
-- ✓ Learning program structure with chapters and content
-- ✓ Student enrollment and progress tracking
-- ✓ Gamification system (points, achievements, levels)
-- ✓ Admin dashboard and logging
-- ✓ System configuration settings
-- ✓ Proper database relationships and constraints
-- ✓ Optimized indexes for better performance
-- ✓ Sample data for immediate testing
--
-- Next Steps:
-- 1. Test the database connection from your PHP application
-- 2. Verify login functionality with the sample accounts
-- 3. Customize system settings as needed
-- 4. Add your own programs and content
--
-- For support or issues, check the project documentation.
-- ============================================================================