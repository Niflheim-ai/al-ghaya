-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2025 at 07:10 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `al_ghaya_lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievement_definitions`
--

CREATE TABLE `achievement_definitions` (
  `id` int(11) NOT NULL,
  `achievement_type` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `points_required` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `achievement_definitions`
--

INSERT INTO `achievement_definitions` (`id`, `achievement_type`, `name`, `description`, `icon`, `points_required`, `is_active`) VALUES
(1, 'first_login', 'Welcome Aboard!', 'Completed your first login to Al-Ghaya', 'welcome.svg', NULL, 1),
(2, 'level_up', 'Level Master', 'Advanced to a new level', 'level-up.svg', NULL, 1),
(3, 'proficiency_up', 'Knowledge Seeker', 'Advanced to a new proficiency level', 'proficiency.svg', NULL, 1),
(4, 'first_program', 'Learning Begins', 'Enrolled in your first program', 'first-program.svg', NULL, 1),
(5, 'program_complete', 'Program Master', 'Completed a learning program', 'program-complete.svg', NULL, 1),
(6, 'chapter_streak_5', 'Dedicated Learner', 'Completed 5 chapters in a row', 'streak.svg', NULL, 1),
(7, 'points_100', 'Point Collector', 'Earned your first 100 points', 'points-100.svg', 100, 1),
(8, 'points_500', 'Point Master', 'Earned 500 points', 'points-500.svg', 500, 1),
(9, 'points_1000', 'Point Legend', 'Earned 1000 points', 'points-1000.svg', 1000, 1),
(10, 'beginner_graduate', 'Beginner Graduate', 'Completed all beginner level programs', 'beginner-complete.svg', NULL, 1),
(11, 'intermediate_graduate', 'Intermediate Graduate', 'Completed all intermediate level programs', 'intermediate-complete.svg', NULL, 1),
(12, 'advanced_graduate', 'Advanced Graduate', 'Completed all advanced level programs', 'advanced-complete.svg', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `adminID` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL COMMENT 'user, teacher, program, etc.',
  `target_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL,
  `adminID` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `point_transactions`
--

CREATE TABLE `point_transactions` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `points` int(11) NOT NULL COMMENT 'Points earned (positive) or spent (negative)',
  `activity_type` varchar(100) NOT NULL COMMENT 'Type of activity that earned/spent points',
  `description` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID of related object (program, chapter, etc.)',
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `point_transactions`
--

INSERT INTO `point_transactions` (`id`, `userID`, `points`, `activity_type`, `description`, `reference_id`, `dateCreated`) VALUES
(1, 2, 10, 'daily_login', 'Daily login bonus', NULL, '2025-10-12 08:31:52');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `programID` int(11) NOT NULL,
  `teacherID` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `video_link` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `difficulty_level` int(1) DEFAULT 1,
  `estimated_duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `prerequisites` text DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `program_chapters`
--

CREATE TABLE `program_chapters` (
  `chapter_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
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
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `program_statistics`
-- (See below for the actual view)
--
CREATE TABLE `program_statistics` (
`programID` int(11)
,`title` varchar(255)
,`category` enum('beginner','intermediate','advanced')
,`status` enum('draft','published','archived')
,`enrolled_students` bigint(21)
,`average_progress` decimal(9,6)
,`completed_count` bigint(21)
,`dateCreated` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `student_chapter_progress`
--

CREATE TABLE `student_chapter_progress` (
  `id` int(11) NOT NULL,
  `studentID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `chapterID` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `score` decimal(5,2) DEFAULT NULL COMMENT 'Score for chapter quiz/assessment',
  `attempts` int(11) DEFAULT 0,
  `time_spent` int(11) DEFAULT 0 COMMENT 'Time spent in seconds',
  `completedAt` timestamp NULL DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `student_dashboard_stats` (
`userID` int(11)
,`points` int(11)
,`level` int(11)
,`proficiency` enum('beginner','intermediate','advanced')
,`enrolled_programs` bigint(21)
,`completed_programs` bigint(21)
,`average_progress` decimal(9,6)
,`completed_chapters` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `student_program`
--

CREATE TABLE `student_program` (
  `id` int(11) NOT NULL,
  `studentID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `progress` decimal(5,2) DEFAULT 0.00 COMMENT 'Progress percentage',
  `current_chapter` int(11) DEFAULT 1,
  `status` enum('enrolled','in_progress','completed','dropped') DEFAULT 'enrolled',
  `enrolledAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `completedAt` timestamp NULL DEFAULT NULL,
  `lastAccessedAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `dateUpdated`) VALUES
(1, 'points_chapter_completion', '50', 'Points awarded for completing a chapter', '2025-10-12 07:20:17'),
(2, 'points_program_completion', '200', 'Points awarded for completing a program', '2025-10-12 07:20:17'),
(3, 'points_daily_login', '10', 'Points awarded for daily login', '2025-10-12 07:20:17'),
(4, 'points_quiz_correct', '5', 'Points awarded per correct quiz answer', '2025-10-12 07:20:17'),
(5, 'points_quiz_perfect', '25', 'Bonus points for perfect quiz score', '2025-10-12 07:20:17'),
(6, 'points_streak_bonus', '15', 'Bonus points for maintaining streaks', '2025-10-12 07:20:17'),
(7, 'site_name', 'Al-Ghaya LMS', 'Name of the learning management system', '2025-10-12 07:20:17'),
(8, 'site_description', 'A Gamified Learning Management System for Arabic and Islamic Studies', 'Site description', '2025-10-12 07:20:17'),
(9, 'registration_enabled', '1', 'Whether new user registration is enabled', '2025-10-12 07:20:17'),
(10, 'email_notifications', '1', 'Whether email notifications are enabled', '2025-10-12 07:20:17'),
(11, 'admin_email', 'admin@al-ghaya.com', 'Default admin email address', '2025-10-12 08:11:20'),
(12, 'smtp_host', 'smtp.gmail.com', 'SMTP server for sending emails', '2025-10-12 08:11:20'),
(13, 'smtp_port', '587', 'SMTP port number', '2025-10-12 08:11:20'),
(14, 'smtp_username', '', 'SMTP username for authentication', '2025-10-12 08:11:20'),
(15, 'smtp_password', '', 'SMTP password for authentication', '2025-10-12 08:11:20'),
(16, 'teacher_creation_enabled', '1', 'Whether teachers can be created by admin', '2025-10-12 08:11:20'),
(17, 'max_failed_login_attempts', '5', 'Maximum failed login attempts before account lock', '2025-10-12 08:11:20'),
(18, 'system_name', 'Al-Ghaya LMS', 'System name for emails and branding', '2025-10-12 08:11:20');

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `teacherID` int(11) NOT NULL,
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
  `isActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userID` int(11) NOT NULL,
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
  `isActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userID`, `email`, `password`, `fname`, `lname`, `role`, `level`, `points`, `proficiency`, `profile_picture`, `department`, `experience`, `bio`, `phone`, `gender`, `dateCreated`, `lastLogin`, `isActive`) VALUES
(1, 'admin@al-ghaya.com', '$2y$12$jGlNxHe6LAqrhheqRezKF.DbDwmJK2EcpX.rocFGeuANbvzA05YBm', 'System', 'Administrator', 'admin', 99, 99999, 'advanced', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 08:11:20', '2025-10-12 16:17:56', 1),
(2, 'faamanaois@kld.edu.ph', '$2y$10$XswmXRG/OlpBYTUGbfdfI.lsELYyBU6hGTenkO34XhW3cxoVzbj7m', 'Fred Andrei', 'Manaois', 'student', 1, 10, 'beginner', NULL, NULL, NULL, 'Testing', '', 'male', '2025-10-12 08:31:44', '2025-10-12 16:15:17', 1),
(3, 'fmanaois4@gmail.com', '$2y$10$BeduVyu9cKm7JojZwwo4A.BkR9Ghpji94h5HQqtuF/MtkQxZPuEjK', 'Test', 'Teacher', 'teacher', 1, 0, '', NULL, 'General Studies', 'Entry Level', 'Test', '', NULL, '2025-10-12 11:14:13', '2025-10-12 16:14:31', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_achievements`
--

CREATE TABLE `user_achievements` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `achievement_type` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `dateUnlocked` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_achievements`
--

INSERT INTO `user_achievements` (`id`, `userID`, `achievement_type`, `description`, `icon`, `dateUnlocked`) VALUES
(1, 2, 'first_login', 'Welcome to Al-Ghaya!', NULL, '2025-10-12 08:31:44');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_leaderboard`
-- (See below for the actual view)
--
CREATE TABLE `user_leaderboard` (
`userID` int(11)
,`full_name` varchar(201)
,`points` int(11)
,`level` int(11)
,`proficiency` enum('beginner','intermediate','advanced')
,`rank_position` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `user_streaks`
--

CREATE TABLE `user_streaks` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `streak_type` enum('daily_login','chapter_completion','quiz_completion') DEFAULT 'daily_login',
  `current_streak` int(11) DEFAULT 0,
  `best_streak` int(11) DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `program_statistics`
--
DROP TABLE IF EXISTS `program_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `program_statistics`  AS SELECT `p`.`programID` AS `programID`, `p`.`title` AS `title`, `p`.`category` AS `category`, `p`.`status` AS `status`, count(`sp`.`studentID`) AS `enrolled_students`, avg(`sp`.`progress`) AS `average_progress`, count(case when `sp`.`status` = 'completed' then 1 end) AS `completed_count`, `p`.`dateCreated` AS `dateCreated` FROM (`programs` `p` left join `student_program` `sp` on(`p`.`programID` = `sp`.`programID`)) GROUP BY `p`.`programID``programID`  ;

-- --------------------------------------------------------

--
-- Structure for view `student_dashboard_stats`
--
DROP TABLE IF EXISTS `student_dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_dashboard_stats`  AS SELECT `u`.`userID` AS `userID`, `u`.`points` AS `points`, `u`.`level` AS `level`, `u`.`proficiency` AS `proficiency`, count(distinct `sp`.`programID`) AS `enrolled_programs`, count(distinct case when `sp`.`status` = 'completed' then `sp`.`programID` end) AS `completed_programs`, avg(`sp`.`progress`) AS `average_progress`, count(distinct `scp`.`chapterID`) AS `completed_chapters` FROM ((`user` `u` left join `student_program` `sp` on(`u`.`userID` = `sp`.`studentID`)) left join `student_chapter_progress` `scp` on(`u`.`userID` = `scp`.`studentID` and `scp`.`completed` = 1)) WHERE `u`.`role` = 'student' GROUP BY `u`.`userID``userID`  ;

-- --------------------------------------------------------

--
-- Structure for view `user_leaderboard`
--
DROP TABLE IF EXISTS `user_leaderboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_leaderboard`  AS SELECT `u`.`userID` AS `userID`, concat(`u`.`fname`,' ',`u`.`lname`) AS `full_name`, `u`.`points` AS `points`, `u`.`level` AS `level`, `u`.`proficiency` AS `proficiency`, row_number() over ( order by `u`.`points` desc) AS `rank_position` FROM `user` AS `u` WHERE `u`.`role` = 'student' AND `u`.`isActive` = 1 ORDER BY `u`.`points` AS `DESCdesc` ASC  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievement_definitions`
--
ALTER TABLE `achievement_definitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `achievement_type` (`achievement_type`),
  ADD KEY `idx_achievement_type` (`achievement_type`);

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_adminID` (`adminID`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_dateCreated` (`dateCreated`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_session_token` (`session_token`),
  ADD KEY `idx_adminID` (`adminID`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_userID` (`userID`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_userID` (`userID`),
  ADD KEY `idx_programID` (`programID`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transaction_id` (`transaction_id`);

--
-- Indexes for table `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_userID` (`userID`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_dateCreated` (`dateCreated`),
  ADD KEY `idx_transaction_date` (`dateCreated`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`programID`),
  ADD KEY `idx_teacherID` (`teacherID`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_program_created` (`dateCreated`),
  ADD KEY `idx_program_updated` (`dateUpdated`);

--
-- Indexes for table `program_chapters`
--
ALTER TABLE `program_chapters`
  ADD PRIMARY KEY (`chapter_id`),
  ADD KEY `idx_program_id` (`program_id`),
  ADD KEY `idx_chapter_order` (`chapter_order`),
  ADD KEY `idx_chapter_program_order` (`program_id`,`chapter_order`);

--
-- Indexes for table `student_chapter_progress`
--
ALTER TABLE `student_chapter_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_chapter` (`studentID`,`programID`,`chapterID`),
  ADD KEY `idx_studentID` (`studentID`),
  ADD KEY `idx_programID` (`programID`),
  ADD KEY `idx_chapterID` (`chapterID`);

--
-- Indexes for table `student_program`
--
ALTER TABLE `student_program`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_program` (`studentID`,`programID`),
  ADD KEY `idx_studentID` (`studentID`),
  ADD KEY `idx_programID` (`programID`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_student_progress_program` (`programID`,`progress`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`teacherID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_userID` (`userID`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_proficiency` (`proficiency`),
  ADD KEY `idx_user_points` (`points`),
  ADD KEY `idx_user_level` (`level`);

--
-- Indexes for table `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_achievement` (`userID`,`achievement_type`),
  ADD KEY `idx_userID` (`userID`),
  ADD KEY `idx_achievement_type` (`achievement_type`);

--
-- Indexes for table `user_streaks`
--
ALTER TABLE `user_streaks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_streak_type` (`userID`,`streak_type`),
  ADD KEY `idx_userID` (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievement_definitions`
--
ALTER TABLE `achievement_definitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `point_transactions`
--
ALTER TABLE `point_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `programID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `program_chapters`
--
ALTER TABLE `program_chapters`
  MODIFY `chapter_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_chapter_progress`
--
ALTER TABLE `student_chapter_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_program`
--
ALTER TABLE `student_program`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `teacher`
--
ALTER TABLE `teacher`
  MODIFY `teacherID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_streaks`
--
ALTER TABLE `user_streaks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`programID`) REFERENCES `programs` (`programID`) ON DELETE CASCADE;

--
-- Constraints for table `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD CONSTRAINT `point_transactions_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`teacherID`) REFERENCES `teacher` (`teacherID`) ON DELETE CASCADE;

--
-- Constraints for table `program_chapters`
--
ALTER TABLE `program_chapters`
  ADD CONSTRAINT `program_chapters_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`programID`) ON DELETE CASCADE;

--
-- Constraints for table `student_chapter_progress`
--
ALTER TABLE `student_chapter_progress`
  ADD CONSTRAINT `student_chapter_progress_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_chapter_progress_ibfk_2` FOREIGN KEY (`programID`) REFERENCES `programs` (`programID`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_chapter_progress_ibfk_3` FOREIGN KEY (`chapterID`) REFERENCES `program_chapters` (`chapter_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_program`
--
ALTER TABLE `student_program`
  ADD CONSTRAINT `student_program_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_program_ibfk_2` FOREIGN KEY (`programID`) REFERENCES `programs` (`programID`) ON DELETE CASCADE;

--
-- Constraints for table `teacher`
--
ALTER TABLE `teacher`
  ADD CONSTRAINT `teacher_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `user_achievements_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `user_streaks`
--
ALTER TABLE `user_streaks`
  ADD CONSTRAINT `user_streaks_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
