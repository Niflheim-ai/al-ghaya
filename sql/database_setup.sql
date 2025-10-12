-- Al-Ghaya LMS Database Schema
-- Complete database structure for the gamified learning management system

-- Users table (enhanced)
CREATE TABLE IF NOT EXISTS `user` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) DEFAULT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `role` enum('student','teacher','admin') DEFAULT 'student',
  `level` int(11) DEFAULT 1,
  `points` int(11) DEFAULT 0,
  `proficiency` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastLogin` timestamp NULL DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`userID`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_proficiency` (`proficiency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teachers table
CREATE TABLE IF NOT EXISTS `teacher` (
  `teacherID` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`teacherID`),
  KEY `idx_userID` (`userID`),
  KEY `idx_email` (`email`),
  FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Programs table (enhanced)
CREATE TABLE IF NOT EXISTS `programs` (
  `programID` int(11) NOT NULL AUTO_INCREMENT,
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
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`programID`),
  KEY `idx_teacherID` (`teacherID`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`teacherID`) REFERENCES `teacher` (`teacherID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Program chapters table
CREATE TABLE IF NOT EXISTS `program_chapters` (
  `chapter_id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `audio_url` varchar(500) DEFAULT NULL,
  `question` text DEFAULT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay') DEFAULT 'multiple_choice',
  `correct_answer` varchar(500) DEFAULT NULL,
  `answer_options` json DEFAULT NULL COMMENT 'JSON array for multiple choice options',
  `points_reward` int(11) DEFAULT 50,
  `chapter_order` int(11) DEFAULT 1,
  `is_required` tinyint(1) DEFAULT 1,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chapter_id`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_chapter_order` (`chapter_order`),
  FOREIGN KEY (`program_id`) REFERENCES `programs` (`programID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student program enrollment table
CREATE TABLE IF NOT EXISTS `student_program` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `progress` decimal(5,2) DEFAULT 0.00 COMMENT 'Progress percentage',
  `current_chapter` int(11) DEFAULT 1,
  `status` enum('enrolled','in_progress','completed','dropped') DEFAULT 'enrolled',
  `enrolledAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completedAt` timestamp NULL DEFAULT NULL,
  `lastAccessedAt` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_program` (`studentID`, `programID`),
  KEY `idx_studentID` (`studentID`),
  KEY `idx_programID` (`programID`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`studentID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  FOREIGN KEY (`programID`) REFERENCES `programs` (`programID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student chapter progress table
CREATE TABLE IF NOT EXISTS `student_chapter_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `chapterID` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `score` decimal(5,2) DEFAULT NULL COMMENT 'Score for chapter quiz/assessment',
  `attempts` int(11) DEFAULT 0,
  `time_spent` int(11) DEFAULT 0 COMMENT 'Time spent in seconds',
  `completedAt` timestamp NULL DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_chapter` (`studentID`, `programID`, `chapterID`),
  KEY `idx_studentID` (`studentID`),
  KEY `idx_programID` (`programID`),
  KEY `idx_chapterID` (`chapterID`),
  FOREIGN KEY (`studentID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  FOREIGN KEY (`programID`) REFERENCES `programs` (`programID`) ON DELETE CASCADE,
  FOREIGN KEY (`chapterID`) REFERENCES `program_chapters` (`chapter_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Point transactions table (gamification)
CREATE TABLE IF NOT EXISTS `point_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `points` int(11) NOT NULL COMMENT 'Points earned (positive) or spent (negative)',
  `activity_type` varchar(100) NOT NULL COMMENT 'Type of activity that earned/spent points',
  `description` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID of related object (program, chapter, etc.)',
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_userID` (`userID`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_dateCreated` (`dateCreated`),
  FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User achievements table (gamification)
CREATE TABLE IF NOT EXISTS `user_achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `achievement_type` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `dateUnlocked` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_achievement` (`userID`, `achievement_type`),
  KEY `idx_userID` (`userID`),
  KEY `idx_achievement_type` (`achievement_type`),
  FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User streaks table (gamification)
CREATE TABLE IF NOT EXISTS `user_streaks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `streak_type` enum('daily_login','chapter_completion','quiz_completion') DEFAULT 'daily_login',
  `current_streak` int(11) DEFAULT 0,
  `best_streak` int(11) DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_streak_type` (`userID`, `streak_type`),
  KEY `idx_userID` (`userID`),
  FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment transactions table
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `programID` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_userID` (`userID`),
  KEY `idx_programID` (`programID`),
  KEY `idx_status` (`status`),
  KEY `idx_transaction_id` (`transaction_id`),
  FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  FOREIGN KEY (`programID`) REFERENCES `programs` (`programID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_userID` (`userID`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_type` (`type`),
  FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings table
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `dateUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('points_chapter_completion', '50', 'Points awarded for completing a chapter'),
('points_program_completion', '200', 'Points awarded for completing a program'),
('points_daily_login', '10', 'Points awarded for daily login'),
('points_quiz_correct', '5', 'Points awarded per correct quiz answer'),
('points_quiz_perfect', '25', 'Bonus points for perfect quiz score'),
('points_streak_bonus', '15', 'Bonus points for maintaining streaks'),
('site_name', 'Al-Ghaya LMS', 'Name of the learning management system'),
('site_description', 'A Gamified Learning Management System for Arabic and Islamic Studies', 'Site description'),
('registration_enabled', '1', 'Whether new user registration is enabled'),
('email_notifications', '1', 'Whether email notifications are enabled');

-- Create indexes for better performance
CREATE INDEX idx_user_points ON `user` (`points` DESC);
CREATE INDEX idx_user_level ON `user` (`level` DESC);
CREATE INDEX idx_program_created ON `programs` (`dateCreated` DESC);
CREATE INDEX idx_program_updated ON `programs` (`dateUpdated` DESC);
CREATE INDEX idx_chapter_program_order ON `program_chapters` (`program_id`, `chapter_order`);
CREATE INDEX idx_student_progress_program ON `student_program` (`programID`, `progress` DESC);
CREATE INDEX idx_transaction_date ON `point_transactions` (`dateCreated` DESC);

-- Create views for common queries
CREATE OR REPLACE VIEW `user_leaderboard` AS
SELECT 
    u.userID,
    CONCAT(u.fname, ' ', u.lname) AS full_name,
    u.points,
    u.level,
    u.proficiency,
    ROW_NUMBER() OVER (ORDER BY u.points DESC) as rank_position
FROM `user` u 
WHERE u.role = 'student' AND u.isActive = 1
ORDER BY u.points DESC;

CREATE OR REPLACE VIEW `program_statistics` AS
SELECT 
    p.programID,
    p.title,
    p.category,
    p.status,
    COUNT(sp.studentID) as enrolled_students,
    AVG(sp.progress) as average_progress,
    COUNT(CASE WHEN sp.status = 'completed' THEN 1 END) as completed_count,
    p.dateCreated
FROM `programs` p
LEFT JOIN `student_program` sp ON p.programID = sp.programID
GROUP BY p.programID;

CREATE OR REPLACE VIEW `student_dashboard_stats` AS
SELECT 
    u.userID,
    u.points,
    u.level,
    u.proficiency,
    COUNT(DISTINCT sp.programID) as enrolled_programs,
    COUNT(DISTINCT CASE WHEN sp.status = 'completed' THEN sp.programID END) as completed_programs,
    AVG(sp.progress) as average_progress,
    COUNT(DISTINCT scp.chapterID) as completed_chapters
FROM `user` u
LEFT JOIN `student_program` sp ON u.userID = sp.studentID
LEFT JOIN `student_chapter_progress` scp ON u.userID = scp.studentID AND scp.completed = 1
WHERE u.role = 'student'
GROUP BY u.userID;

-- Add some sample achievement types
CREATE TABLE IF NOT EXISTS `achievement_definitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `achievement_type` varchar(100) NOT NULL UNIQUE,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `points_required` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_achievement_type` (`achievement_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `achievement_definitions` (`achievement_type`, `name`, `description`, `icon`, `points_required`) VALUES
('first_login', 'Welcome Aboard!', 'Completed your first login to Al-Ghaya', 'welcome.svg', NULL),
('level_up', 'Level Master', 'Advanced to a new level', 'level-up.svg', NULL),
('proficiency_up', 'Knowledge Seeker', 'Advanced to a new proficiency level', 'proficiency.svg', NULL),
('first_program', 'Learning Begins', 'Enrolled in your first program', 'first-program.svg', NULL),
('program_complete', 'Program Master', 'Completed a learning program', 'program-complete.svg', NULL),
('chapter_streak_5', 'Dedicated Learner', 'Completed 5 chapters in a row', 'streak.svg', NULL),
('points_100', 'Point Collector', 'Earned your first 100 points', 'points-100.svg', 100),
('points_500', 'Point Master', 'Earned 500 points', 'points-500.svg', 500),
('points_1000', 'Point Legend', 'Earned 1000 points', 'points-1000.svg', 1000),
('beginner_graduate', 'Beginner Graduate', 'Completed all beginner level programs', 'beginner-complete.svg', NULL),
('intermediate_graduate', 'Intermediate Graduate', 'Completed all intermediate level programs', 'intermediate-complete.svg', NULL),
('advanced_graduate', 'Advanced Graduate', 'Completed all advanced level programs', 'advanced-complete.svg', NULL);
