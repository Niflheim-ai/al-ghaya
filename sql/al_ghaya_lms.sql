-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2025 at 11:55 AM
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
-- Table structure for table `chapter_quizzes`
--

CREATE TABLE `chapter_quizzes` (
  `quiz_id` int(11) NOT NULL,
  `chapter_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT 'Chapter Quiz',
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chapter_stories`
--

CREATE TABLE `chapter_stories` (
  `story_id` int(11) NOT NULL,
  `chapter_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `synopsis_arabic` text DEFAULT NULL,
  `synopsis_english` text DEFAULT NULL,
  `video_url` varchar(500) NOT NULL,
  `story_order` int(11) NOT NULL DEFAULT 1,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interactive_questions`
--

CREATE TABLE `interactive_questions` (
  `question_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','fill_in_blanks','multiple_select') NOT NULL,
  `question_order` int(11) NOT NULL DEFAULT 1,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`, `used_at`) VALUES
(1, 'faamanaois@kld.edu.ph', 'b850a9530b8098da5df61ad9120d1a87f11c392dac1ed838cfb807439997fef7', '2025-10-13 08:14:24', '2025-10-13 08:13:23', '2025-10-13 08:14:24');

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
(1, 2, 10, 'daily_login', 'Daily login bonus', NULL, '2025-10-12 08:31:52'),
(2, 2, 10, 'daily_login', 'Daily login bonus', NULL, '2025-10-13 04:58:56'),
(3, 4, 10, 'daily_login', 'Daily login bonus', NULL, '2025-10-13 05:38:42'),
(4, 2, 10, 'daily_login', 'Daily login bonus', NULL, '2025-10-14 05:50:01');

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
  `datePublished` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`programID`, `teacherID`, `title`, `description`, `category`, `difficulty_label`, `video_link`, `overview_video_url`, `price`, `currency`, `image`, `thumbnail`, `status`, `rejection_reason`, `difficulty_level`, `estimated_duration`, `prerequisites`, `learning_objectives`, `dateCreated`, `dateUpdated`, `datePublished`) VALUES
(1, 1, 'awdawdwa', 'dawddawdwadwa', 'intermediate', 'Aspiring', NULL, '0', '500.00', 'PHP', NULL, 'thumb_68ee10ccbd8cc6.00469609.jpg', 'draft', NULL, 1, NULL, NULL, NULL, '2025-10-14 08:58:52', '2025-10-14 09:42:04', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `program_chapters`
--

CREATE TABLE `program_chapters` (
  `chapter_id` int(11) NOT NULL,
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
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `program_chapters`
--
DELIMITER $$
CREATE TRIGGER `after_chapter_insert` AFTER INSERT ON `program_chapters` FOR EACH ROW BEGIN
  -- Only create quiz if chapter_quizzes table exists and chapter doesn't already have a quiz
  IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'chapter_quizzes') > 0 THEN
    INSERT IGNORE INTO chapter_quizzes (chapter_id, program_id, title)
    VALUES (NEW.chapter_id, NEW.program_id, CONCAT(NEW.title, ' Quiz'));
  END IF;
  
  -- Update has_quiz flag
  UPDATE program_chapters SET has_quiz = 1 WHERE chapter_id = NEW.chapter_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_chapter_update` AFTER UPDATE ON `program_chapters` FOR EACH ROW BEGIN
  UPDATE programs SET dateUpdated = CURRENT_TIMESTAMP WHERE programID = NEW.program_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `program_library`
-- (See below for the actual view)
--
CREATE TABLE `program_library` (
`programID` int(11)
,`teacherID` int(11)
,`title` varchar(255)
,`description` text
,`category` enum('beginner','intermediate','advanced')
,`difficulty_label` enum('Student','Aspiring','Master')
,`price` decimal(10,2)
,`currency` varchar(10)
,`thumbnail` varchar(255)
,`datePublished` timestamp
,`teacher_first_name` varchar(100)
,`teacher_last_name` varchar(100)
,`teacher_user_fname` varchar(100)
,`teacher_user_lname` varchar(100)
,`enrollment_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `program_publish_requests`
--

CREATE TABLE `program_publish_requests` (
  `request_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_id` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `dateRequested` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateReviewed` timestamp NULL DEFAULT NULL,
  `review_message` text DEFAULT NULL COMMENT 'Admin feedback on approval/rejection',
  `reviewed_at` timestamp NULL DEFAULT NULL COMMENT 'When the request was reviewed by admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `program_publish_requests`
--
DELIMITER $$
CREATE TRIGGER `after_publish_request_update` AFTER UPDATE ON `program_publish_requests` FOR EACH ROW BEGIN
  -- When admin approves a pending request
  IF NEW.status = 'approved' AND OLD.status = 'pending' THEN
    UPDATE programs 
    SET 
      status = 'published',
      datePublished = CURRENT_TIMESTAMP,
      rejection_reason = NULL,  -- Clear any previous rejection reason
      dateUpdated = CURRENT_TIMESTAMP
    WHERE programID = NEW.program_id;
    
  -- When admin rejects a pending request
  ELSEIF NEW.status = 'rejected' AND OLD.status = 'pending' THEN
    UPDATE programs 
    SET 
      status = 'draft',
      rejection_reason = COALESCE(NEW.review_message, 'Program rejected by admin'),  -- Store admin feedback with fallback
      dateUpdated = CURRENT_TIMESTAMP
    WHERE programID = NEW.program_id;
    
  -- When request is cancelled (teacher or admin cancellation)
  ELSEIF NEW.status = 'cancelled' AND OLD.status = 'pending' THEN
    UPDATE programs 
    SET 
      status = 'draft',
      dateUpdated = CURRENT_TIMESTAMP
    WHERE programID = NEW.program_id;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `set_reviewed_timestamp` BEFORE UPDATE ON `program_publish_requests` FOR EACH ROW BEGIN
  -- Set reviewed_at timestamp when status changes from pending to approved/rejected/cancelled
  IF OLD.status = 'pending' AND NEW.status IN ('approved', 'rejected', 'cancelled') THEN
    SET NEW.reviewed_at = CURRENT_TIMESTAMP;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `program_statistics`
-- (See below for the actual view)
--
CREATE TABLE `program_statistics` (
`programID` int(11)
,`title` varchar(255)
,`category` enum('beginner','intermediate','advanced')
,`status` enum('draft','pending_review','published','rejected','archived')
,`enrolled_students` bigint(21)
,`average_progress` decimal(9,6)
,`completed_count` bigint(21)
,`dateCreated` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `program_stories`
--

CREATE TABLE `program_stories` (
  `story_id` int(11) NOT NULL,
  `chapter_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `synopsis_arabic` text DEFAULT NULL,
  `synopsis_english` text DEFAULT NULL,
  `video_url` varchar(500) NOT NULL COMMENT 'YouTube video URL for story progression',
  `story_order` int(11) DEFAULT 1,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `program_stories`
--
DELIMITER $$
CREATE TRIGGER `after_story_delete` AFTER DELETE ON `program_stories` FOR EACH ROW BEGIN
  -- Update story count in chapter
  UPDATE program_chapters 
  SET story_count = (SELECT COUNT(*) FROM program_stories WHERE chapter_id = OLD.chapter_id)
  WHERE chapter_id = OLD.chapter_id;
  
  -- Update program modification date
  UPDATE programs SET dateUpdated = CURRENT_TIMESTAMP WHERE programID = OLD.program_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_story_insert` AFTER INSERT ON `program_stories` FOR EACH ROW BEGIN
  -- Update story count in chapter
  UPDATE program_chapters 
  SET story_count = (SELECT COUNT(*) FROM program_stories WHERE chapter_id = NEW.chapter_id)
  WHERE chapter_id = NEW.chapter_id;
  
  -- Update program modification date
  UPDATE programs SET dateUpdated = CURRENT_TIMESTAMP WHERE programID = NEW.program_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `question_options`
--

CREATE TABLE `question_options` (
  `option_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `option_order` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `quiz_question_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_order` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `quiz_questions`
--
DELIMITER $$
CREATE TRIGGER `after_quiz_question_delete` AFTER DELETE ON `quiz_questions` FOR EACH ROW BEGIN
  DECLARE chapter_id_var INT DEFAULT NULL;
  
  -- Get chapter_id from quiz
  SELECT chapter_id INTO chapter_id_var FROM chapter_quizzes WHERE quiz_id = OLD.quiz_id;
  
  -- Update question count if chapter found
  IF chapter_id_var IS NOT NULL THEN
    UPDATE program_chapters 
    SET quiz_question_count = (
      SELECT COUNT(*) 
      FROM quiz_questions qq 
      JOIN chapter_quizzes cq ON qq.quiz_id = cq.quiz_id 
      WHERE cq.chapter_id = chapter_id_var
    )
    WHERE chapter_id = chapter_id_var;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_quiz_question_insert` AFTER INSERT ON `quiz_questions` FOR EACH ROW BEGIN
  DECLARE chapter_id_var INT DEFAULT NULL;
  
  -- Get chapter_id from quiz
  SELECT chapter_id INTO chapter_id_var FROM chapter_quizzes WHERE quiz_id = NEW.quiz_id;
  
  -- Update question count if chapter found
  IF chapter_id_var IS NOT NULL THEN
    UPDATE program_chapters 
    SET quiz_question_count = (
      SELECT COUNT(*) 
      FROM quiz_questions qq 
      JOIN chapter_quizzes cq ON qq.quiz_id = cq.quiz_id 
      WHERE cq.chapter_id = chapter_id_var
    )
    WHERE chapter_id = chapter_id_var;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_question_options`
--

CREATE TABLE `quiz_question_options` (
  `quiz_option_id` int(11) NOT NULL,
  `quiz_question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `option_order` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `story_interactive_sections`
--

CREATE TABLE `story_interactive_sections` (
  `section_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `section_order` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `student_program_enrollments`
--

CREATE TABLE `student_program_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'References user.userID where role=student',
  `program_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `student_program_enrollments`
--
DELIMITER $$
CREATE TRIGGER `after_enrollment_delete` AFTER DELETE ON `student_program_enrollments` FOR EACH ROW BEGIN
  -- Update program modification date when student unenrolls
  UPDATE programs SET dateUpdated = CURRENT_TIMESTAMP WHERE programID = OLD.program_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_enrollment_insert` AFTER INSERT ON `student_program_enrollments` FOR EACH ROW BEGIN
  -- Update program modification date when student enrolls
  UPDATE programs SET dateUpdated = CURRENT_TIMESTAMP WHERE programID = NEW.program_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_quiz_attempts`
--

CREATE TABLE `student_quiz_attempts` (
  `attempt_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT NULL,
  `is_passed` tinyint(1) DEFAULT 0,
  `attempt_number` int(11) DEFAULT 1,
  `attempt_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_story_progress`
--

CREATE TABLE `student_story_progress` (
  `progress_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completion_date` timestamp NULL DEFAULT NULL,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`teacherID`, `userID`, `email`, `username`, `password`, `fname`, `lname`, `specialization`, `bio`, `profile_picture`, `dateCreated`, `isActive`) VALUES
(1, 3, 'fmanaois4@gmail.com', 'fmanaois4', '$2y$10$BeduVyu9cKm7JojZwwo4A.BkR9Ghpji94h5HQqtuF/MtkQxZPuEjK', 'Test', 'Teacher', NULL, NULL, NULL, '2025-10-13 17:46:48', 1),
(2, 5, 'dlagonia@kld.edu.ph', 'dlagonia', '$2y$12$sAk6fU51wzigS0wdS7ejCehnJkX6H4o477K9xiK7L636BWy2tlNKu', 'David', 'Agonia', NULL, NULL, NULL, '2025-10-13 17:46:48', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `teacher_program_overview`
-- (See below for the actual view)
--
CREATE TABLE `teacher_program_overview` (
);

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
(1, 'admin@al-ghaya.com', '$2y$12$jGlNxHe6LAqrhheqRezKF.DbDwmJK2EcpX.rocFGeuANbvzA05YBm', 'System', 'Administrator', 'admin', 99, 99999, 'advanced', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-12 08:11:20', '2025-10-14 05:50:46', 1),
(2, 'faamanaois@kld.edu.ph', '$2y$10$cbh7ePMq.2F4ZG1mhyzcIOshgZ8VVv.dgJwdk5ftYOMYqN7i62cuK', 'Fred Andrei', 'Manaois', 'student', 1, 30, 'beginner', NULL, NULL, NULL, 'Testing', '', 'male', '2025-10-12 08:31:44', '2025-10-14 05:50:01', 1),
(3, 'fmanaois4@gmail.com', '$2y$10$BeduVyu9cKm7JojZwwo4A.BkR9Ghpji94h5HQqtuF/MtkQxZPuEjK', 'Test', 'Teacher', 'teacher', 1, 0, '', NULL, 'General Studies', 'Entry Level', 'Test', '', NULL, '2025-10-12 11:14:13', '2025-10-14 06:00:06', 1),
(4, 'thegodlykali@gmail.com', '$2y$10$3XjZfzMa9.J2CZLoTS.8CuJyLkcu0LEZZ1qG0HM/GjO24cPSJoIUW', 'TheGodly', 'Kali', 'student', 1, 10, 'beginner', NULL, NULL, NULL, '', '', NULL, '2025-10-13 05:38:42', NULL, 1),
(5, 'dlagonia@kld.edu.ph', '$2y$12$sAk6fU51wzigS0wdS7ejCehnJkX6H4o477K9xiK7L636BWy2tlNKu', 'David', 'Agonia', 'teacher', 1, 0, '', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-13 07:13:12', NULL, 1);

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
-- Structure for view `program_library`
--
DROP TABLE IF EXISTS `program_library`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `program_library`  AS SELECT `p`.`programID` AS `programID`, `p`.`teacherID` AS `teacherID`, `p`.`title` AS `title`, `p`.`description` AS `description`, `p`.`category` AS `category`, `p`.`difficulty_label` AS `difficulty_label`, `p`.`price` AS `price`, `p`.`currency` AS `currency`, `p`.`thumbnail` AS `thumbnail`, `p`.`datePublished` AS `datePublished`, `t`.`fname` AS `teacher_first_name`, `t`.`lname` AS `teacher_last_name`, `u`.`fname` AS `teacher_user_fname`, `u`.`lname` AS `teacher_user_lname`, count(distinct `spe`.`student_id`) AS `enrollment_count` FROM (((`programs` `p` left join `teacher` `t` on(`p`.`teacherID` = `t`.`teacherID`)) left join `user` `u` on(`t`.`userID` = `u`.`userID`)) left join `student_program_enrollments` `spe` on(`p`.`programID` = `spe`.`program_id`)) WHERE `p`.`status` = 'published' GROUP BY `p`.`programID` ORDER BY `p`.`datePublished` AS `DESCdesc` ASC  ;

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
-- Structure for view `teacher_program_overview`
--
DROP TABLE IF EXISTS `teacher_program_overview`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `teacher_program_overview`  AS SELECT `p`.`programID` AS `programID`, `p`.`teacherID` AS `teacherID`, `p`.`title` AS `title`, `p`.`description` AS `description`, `p`.`category` AS `category`, `p`.`difficulty_label` AS `difficulty_label`, `p`.`price` AS `price`, `p`.`currency` AS `currency`, `p`.`thumbnail` AS `thumbnail`, `p`.`overview_video_url` AS `overview_video_url`, `p`.`status` AS `status`, `p`.`rejection_reason` AS `rejection_reason`, `p`.`dateCreated` AS `dateCreated`, `p`.`dateUpdated` AS `dateUpdated`, `p`.`datePublished` AS `datePublished`, count(distinct `pc`.`chapter_id`) AS `chapter_count`, count(distinct `ps`.`story_id`) AS `story_count`, count(distinct `cq`.`quiz_id`) AS `quiz_count`, count(distinct `spe`.`student_id`) AS `enrollment_count` FROM ((((`programs` `p` left join `program_chapters` `pc` on(`p`.`programID` = `pc`.`program_id`)) left join `program_stories` `ps` on(`pc`.`chapter_id` = `ps`.`chapter_id`)) left join `chapter_quizzes` `cq` on(`pc`.`chapter_id` = `cq`.`chapter_id`)) left join `student_program_enrollments` `spe` on(`p`.`programID` = `spe`.`program_id`)) GROUP BY `p`.`programID``programID`  ;

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
-- Indexes for table `chapter_quizzes`
--
ALTER TABLE `chapter_quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD UNIQUE KEY `unique_chapter_quiz` (`chapter_id`);

--
-- Indexes for table `chapter_stories`
--
ALTER TABLE `chapter_stories`
  ADD PRIMARY KEY (`story_id`),
  ADD KEY `idx_chapter_order` (`chapter_id`,`story_order`);

--
-- Indexes for table `interactive_questions`
--
ALTER TABLE `interactive_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_section_order` (`section_id`,`question_order`),
  ADD KEY `idx_questions_section` (`section_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_userID` (`userID`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

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
  ADD KEY `idx_program_updated` (`dateUpdated`),
  ADD KEY `idx_programs_teacher_status` (`teacherID`,`status`),
  ADD KEY `idx_programs_status_published` (`status`,`datePublished`);

--
-- Indexes for table `program_chapters`
--
ALTER TABLE `program_chapters`
  ADD PRIMARY KEY (`chapter_id`),
  ADD KEY `idx_program_id` (`programID`),
  ADD KEY `idx_chapter_order` (`chapter_order`),
  ADD KEY `idx_chapter_program_order` (`programID`,`chapter_order`);

--
-- Indexes for table `program_publish_requests`
--
ALTER TABLE `program_publish_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_date` (`dateRequested`);

--
-- Indexes for table `program_stories`
--
ALTER TABLE `program_stories`
  ADD PRIMARY KEY (`story_id`),
  ADD KEY `idx_story_chapter` (`chapter_id`),
  ADD KEY `idx_story_program_order` (`program_id`,`story_order`),
  ADD KEY `idx_stories_chapter_order` (`chapter_id`,`story_order`);

--
-- Indexes for table `question_options`
--
ALTER TABLE `question_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `idx_question_order` (`question_id`,`option_order`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`quiz_question_id`),
  ADD KEY `idx_quiz_order` (`quiz_id`,`question_order`);

--
-- Indexes for table `quiz_question_options`
--
ALTER TABLE `quiz_question_options`
  ADD PRIMARY KEY (`quiz_option_id`),
  ADD KEY `idx_question_order` (`quiz_question_id`,`option_order`);

--
-- Indexes for table `story_interactive_sections`
--
ALTER TABLE `story_interactive_sections`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `idx_story_order` (`story_id`,`section_order`),
  ADD KEY `idx_interactions_story` (`story_id`);

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
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`program_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_program` (`program_id`);

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
-- Indexes for table `student_program_enrollments`
--
ALTER TABLE `student_program_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_student_program` (`student_id`,`program_id`),
  ADD KEY `idx_enrollment_student` (`student_id`),
  ADD KEY `idx_enrollment_program` (`program_id`),
  ADD KEY `idx_enrollments_student_program` (`student_id`,`program_id`);

--
-- Indexes for table `student_quiz_attempts`
--
ALTER TABLE `student_quiz_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_student_quiz` (`student_id`,`quiz_id`);

--
-- Indexes for table `student_story_progress`
--
ALTER TABLE `student_story_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD UNIQUE KEY `unique_progress` (`student_id`,`story_id`),
  ADD KEY `idx_student_story` (`student_id`,`story_id`),
  ADD KEY `idx_progress_student` (`student_id`);

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
-- AUTO_INCREMENT for table `chapter_quizzes`
--
ALTER TABLE `chapter_quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chapter_stories`
--
ALTER TABLE `chapter_stories`
  MODIFY `story_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interactive_questions`
--
ALTER TABLE `interactive_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `point_transactions`
--
ALTER TABLE `point_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `programID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `program_chapters`
--
ALTER TABLE `program_chapters`
  MODIFY `chapter_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `program_publish_requests`
--
ALTER TABLE `program_publish_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `program_stories`
--
ALTER TABLE `program_stories`
  MODIFY `story_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_options`
--
ALTER TABLE `question_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `quiz_question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_question_options`
--
ALTER TABLE `quiz_question_options`
  MODIFY `quiz_option_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `story_interactive_sections`
--
ALTER TABLE `story_interactive_sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_chapter_progress`
--
ALTER TABLE `student_chapter_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_program`
--
ALTER TABLE `student_program`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_program_enrollments`
--
ALTER TABLE `student_program_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_quiz_attempts`
--
ALTER TABLE `student_quiz_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_story_progress`
--
ALTER TABLE `student_story_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `teacher`
--
ALTER TABLE `teacher`
  MODIFY `teacherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Constraints for table `chapter_quizzes`
--
ALTER TABLE `chapter_quizzes`
  ADD CONSTRAINT `chapter_quizzes_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `program_chapters` (`chapter_id`) ON DELETE CASCADE;

--
-- Constraints for table `chapter_stories`
--
ALTER TABLE `chapter_stories`
  ADD CONSTRAINT `chapter_stories_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `program_chapters` (`chapter_id`) ON DELETE CASCADE;

--
-- Constraints for table `interactive_questions`
--
ALTER TABLE `interactive_questions`
  ADD CONSTRAINT `interactive_questions_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `story_interactive_sections` (`section_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `program_chapters_ibfk_1` FOREIGN KEY (`programID`) REFERENCES `programs` (`programID`) ON DELETE CASCADE;

--
-- Constraints for table `program_publish_requests`
--
ALTER TABLE `program_publish_requests`
  ADD CONSTRAINT `program_publish_requests_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`programID`) ON DELETE CASCADE;

--
-- Constraints for table `program_stories`
--
ALTER TABLE `program_stories`
  ADD CONSTRAINT `fk_story_chapter` FOREIGN KEY (`chapter_id`) REFERENCES `program_chapters` (`chapter_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_story_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`programID`) ON DELETE CASCADE;

--
-- Constraints for table `question_options`
--
ALTER TABLE `question_options`
  ADD CONSTRAINT `question_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `interactive_questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `chapter_quizzes` (`quiz_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_question_options`
--
ALTER TABLE `quiz_question_options`
  ADD CONSTRAINT `quiz_question_options_ibfk_1` FOREIGN KEY (`quiz_question_id`) REFERENCES `quiz_questions` (`quiz_question_id`) ON DELETE CASCADE;

--
-- Constraints for table `story_interactive_sections`
--
ALTER TABLE `story_interactive_sections`
  ADD CONSTRAINT `story_interactive_sections_ibfk_1` FOREIGN KEY (`story_id`) REFERENCES `chapter_stories` (`story_id`) ON DELETE CASCADE;

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
-- Constraints for table `student_program_enrollments`
--
ALTER TABLE `student_program_enrollments`
  ADD CONSTRAINT `fk_enrollment_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`programID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollment_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

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
