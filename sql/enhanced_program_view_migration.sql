-- ============================================================================
-- ENHANCED PROGRAM VIEW MIGRATION
-- ============================================================================
-- Additional tables and modifications needed for the enhanced student program view
-- with sidebar, progress tracking, and security features
-- ============================================================================

USE `al_ghaya_lms`;

-- Create Stories table
CREATE TABLE IF NOT EXISTS `chapter_stories` (
  `story_id` int(11) NOT NULL AUTO_INCREMENT,
  `chapter_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `audio_url` varchar(500) DEFAULT NULL,
  `question` text DEFAULT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay') DEFAULT 'multiple_choice',
  `correct_answer` varchar(500) DEFAULT NULL,
  `answer_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array for multiple choice options' CHECK (json_valid(`answer_options`)),
  `points_reward` int(11) DEFAULT 25,
  `story_order` int(11) DEFAULT 1,
  `is_required` tinyint(1) DEFAULT 1,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`story_id`),
  KEY `idx_chapter_id` (`chapter_id`),
  KEY `idx_story_order` (`story_order`),
  KEY `idx_story_chapter_order` (`chapter_id`, `story_order`),
  CONSTRAINT `fk_story_chapter` FOREIGN KEY (`chapter_id`) REFERENCES `program_chapters` (`chapter_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Student Chapter Progress table
CREATE TABLE IF NOT EXISTS `student_chapter_progress` (
  `progress_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `chapter_id` int(11) NOT NULL,
  `video_watched` tinyint(1) DEFAULT 0,
  `video_watch_time` int(11) DEFAULT 0 COMMENT 'Seconds watched',
  `video_duration` int(11) DEFAULT 0 COMMENT 'Total video duration in seconds',
  `interactive_completed` tinyint(1) DEFAULT 0,
  `interactive_attempts` int(11) DEFAULT 0,
  `interactive_correct` tinyint(1) DEFAULT 0,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `is_completed` tinyint(1) DEFAULT 0,
  `first_accessed` timestamp NULL DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`progress_id`),
  UNIQUE KEY `unique_student_chapter` (`student_id`, `chapter_id`),
  KEY `idx_student_chapter` (`student_id`, `chapter_id`),
  KEY `idx_completion` (`is_completed`),
  CONSTRAINT `fk_chapter_progress_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  CONSTRAINT `fk_chapter_progress_chapter` FOREIGN KEY (`chapter_id`) REFERENCES `program_chapters` (`chapter_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Student Story Progress table
CREATE TABLE IF NOT EXISTS `student_story_progress` (
  `progress_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `video_watched` tinyint(1) DEFAULT 0,
  `video_watch_time` int(11) DEFAULT 0 COMMENT 'Seconds watched',
  `video_duration` int(11) DEFAULT 0 COMMENT 'Total video duration in seconds',
  `interactive_completed` tinyint(1) DEFAULT 0,
  `interactive_attempts` int(11) DEFAULT 0,
  `interactive_correct` tinyint(1) DEFAULT 0,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `is_completed` tinyint(1) DEFAULT 0,
  `first_accessed` timestamp NULL DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`progress_id`),
  UNIQUE KEY `unique_student_story` (`student_id`, `story_id`),
  KEY `idx_student_story` (`student_id`, `story_id`),
  KEY `idx_story_completion` (`is_completed`),
  CONSTRAINT `fk_story_progress_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  CONSTRAINT `fk_story_progress_story` FOREIGN KEY (`story_id`) REFERENCES `chapter_stories` (`story_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Interactive Attempts table for detailed tracking
CREATE TABLE IF NOT EXISTS `interactive_attempts` (
  `attempt_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `content_type` enum('chapter','story') NOT NULL,
  `content_id` int(11) NOT NULL,
  `submitted_answer` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `points_earned` int(11) DEFAULT 0,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attempt_id`),
  KEY `idx_student_content` (`student_id`, `content_type`, `content_id`),
  KEY `idx_attempt_time` (`attempt_time`),
  CONSTRAINT `fk_attempt_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Video Watch Sessions table for detailed video tracking
CREATE TABLE IF NOT EXISTS `video_watch_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `content_type` enum('chapter','story','program_overview') NOT NULL,
  `content_id` int(11) NOT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `watch_start_time` int(11) DEFAULT 0 COMMENT 'Video timestamp when started watching',
  `watch_end_time` int(11) DEFAULT 0 COMMENT 'Video timestamp when stopped watching',
  `watch_duration` int(11) DEFAULT 0 COMMENT 'Actual seconds watched',
  `video_duration` int(11) DEFAULT 0 COMMENT 'Total video duration',
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `session_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_end` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `idx_student_content_video` (`student_id`, `content_type`, `content_id`),
  KEY `idx_session_start` (`session_start`),
  CONSTRAINT `fk_video_session_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add columns to existing program_chapters table
ALTER TABLE `program_chapters` 
ADD COLUMN IF NOT EXISTS `video_duration` int(11) DEFAULT NULL COMMENT 'Video duration in seconds',
ADD COLUMN IF NOT EXISTS `estimated_read_time` int(11) DEFAULT NULL COMMENT 'Estimated reading time in minutes',
ADD COLUMN IF NOT EXISTS `unlock_requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON requirements to unlock this chapter' CHECK (json_valid(`unlock_requirements`));

-- Insert sample stories for existing chapters
INSERT IGNORE INTO `chapter_stories` (`chapter_id`, `title`, `content`, `story_order`, `points_reward`) 
SELECT 
    pc.chapter_id,
    CONCAT('Introduction to ', pc.title) as title,
    'This is the introductory story for this chapter. Here you will learn the fundamental concepts.' as content,
    1 as story_order,
    25 as points_reward
FROM `program_chapters` pc
WHERE pc.chapter_id NOT IN (SELECT DISTINCT chapter_id FROM `chapter_stories`);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_program_enrollment_completion` ON `student_program_enrollments` (`student_id`, `program_id`, `completion_percentage`);
CREATE INDEX IF NOT EXISTS `idx_chapter_program_order` ON `program_chapters` (`programID`, `chapter_order`);
CREATE INDEX IF NOT EXISTS `idx_story_chapter_order` ON `chapter_stories` (`chapter_id`, `story_order`);

-- Create a view for easy chapter progress checking
CREATE OR REPLACE VIEW `v_chapter_progress` AS
SELECT 
    scp.student_id,
    scp.chapter_id,
    pc.programID,
    pc.title as chapter_title,
    pc.chapter_order,
    scp.video_watched,
    scp.interactive_completed,
    scp.completion_percentage,
    scp.is_completed,
    CASE 
        WHEN scp.is_completed = 1 THEN 'completed'
        WHEN scp.video_watched = 1 OR scp.interactive_completed = 1 THEN 'in_progress'
        WHEN scp.first_accessed IS NOT NULL THEN 'started'
        ELSE 'not_started'
    END as progress_status
FROM `student_chapter_progress` scp
JOIN `program_chapters` pc ON scp.chapter_id = pc.chapter_id;

-- Create a view for story progress checking
CREATE OR REPLACE VIEW `v_story_progress` AS
SELECT 
    ssp.student_id,
    ssp.story_id,
    cs.chapter_id,
    cs.title as story_title,
    cs.story_order,
    ssp.video_watched,
    ssp.interactive_completed,
    ssp.completion_percentage,
    ssp.is_completed,
    CASE 
        WHEN ssp.is_completed = 1 THEN 'completed'
        WHEN ssp.video_watched = 1 OR ssp.interactive_completed = 1 THEN 'in_progress'
        WHEN ssp.first_accessed IS NOT NULL THEN 'started'
        ELSE 'not_started'
    END as progress_status
FROM `student_story_progress` ssp
JOIN `chapter_stories` cs ON ssp.story_id = cs.story_id;

-- Create triggers to automatically update program completion percentage
DELIMITER //

CREATE TRIGGER IF NOT EXISTS `update_program_completion_after_chapter`
AFTER UPDATE ON `student_chapter_progress`
FOR EACH ROW
BEGIN
    DECLARE total_chapters INT DEFAULT 0;
    DECLARE completed_chapters INT DEFAULT 0;
    DECLARE program_id INT DEFAULT 0;
    DECLARE completion_pct DECIMAL(5,2) DEFAULT 0;
    
    -- Get program ID
    SELECT pc.programID INTO program_id 
    FROM program_chapters pc 
    WHERE pc.chapter_id = NEW.chapter_id;
    
    -- Count total chapters in program
    SELECT COUNT(*) INTO total_chapters 
    FROM program_chapters pc 
    WHERE pc.programID = program_id;
    
    -- Count completed chapters for this student
    SELECT COUNT(*) INTO completed_chapters
    FROM student_chapter_progress scp
    JOIN program_chapters pc ON scp.chapter_id = pc.chapter_id
    WHERE scp.student_id = NEW.student_id 
    AND pc.programID = program_id 
    AND scp.is_completed = 1;
    
    -- Calculate completion percentage
    IF total_chapters > 0 THEN
        SET completion_pct = (completed_chapters * 100.0) / total_chapters;
    END IF;
    
    -- Update enrollment completion percentage
    UPDATE student_program_enrollments 
    SET completion_percentage = completion_pct,
        last_accessed = NOW()
    WHERE student_id = NEW.student_id 
    AND program_id = program_id;
END//

DELIMITER ;

-- Insert system settings for the enhanced features
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('video_completion_threshold', '80', 'Percentage of video that must be watched to consider it completed'),
('max_interactive_attempts', '3', 'Maximum attempts allowed for interactive sections'),
('auto_unlock_next_content', '1', 'Automatically unlock next content when current is completed'),
('require_video_completion', '1', 'Require video completion before allowing progress'),
('require_interactive_completion', '1', 'Require interactive section completion before allowing progress'),
('points_video_completion', '10', 'Points awarded for completing a video'),
('points_interactive_correct', '15', 'Points awarded for correct interactive answer'),
('sidebar_default_collapsed', '0', 'Whether sidebar should be collapsed by default on mobile');

-- ============================================================================
-- MIGRATION COMPLETE!
-- ============================================================================
-- 
-- Enhanced Program View Database Migration Complete!
--
-- NEW FEATURES ADDED:
-- ===================
-- ✓ Chapter Stories support
-- ✓ Detailed progress tracking for chapters and stories
-- ✓ Video watching session tracking
-- ✓ Interactive attempt logging
-- ✓ Automatic program completion calculation
-- ✓ Performance optimization indexes
-- ✓ Database views for easy progress querying
-- ✓ Triggers for automatic completion updates
-- ✓ System settings for feature configuration
--
-- TABLES CREATED:
-- ===============
-- • chapter_stories - Stories within chapters
-- • student_chapter_progress - Chapter completion tracking
-- • student_story_progress - Story completion tracking  
-- • interactive_attempts - Interactive section attempts
-- • video_watch_sessions - Video watching sessions
--
-- VIEWS CREATED:
-- ==============
-- • v_chapter_progress - Easy chapter progress checking
-- • v_story_progress - Easy story progress checking
--
-- TRIGGERS CREATED:
-- =================
-- • update_program_completion_after_chapter - Auto-update program completion
--
-- The enhanced student program view is now ready to use!
-- ============================================================================