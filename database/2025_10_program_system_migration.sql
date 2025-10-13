-- Enhanced Program System Migration
-- Compatible with existing al_ghaya_lms database structure
-- Run this migration to add new features while preserving existing data

-- Database: al_ghaya_lms
-- Generated: 2025-10-13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ========================================
-- 1. ENHANCE EXISTING PROGRAMS TABLE
-- ========================================

-- Add new columns to existing programs table
ALTER TABLE programs
  MODIFY COLUMN status ENUM('draft','pending_review','published','rejected','archived') DEFAULT 'draft',
  ADD COLUMN IF NOT EXISTS difficulty_label ENUM('Student','Aspiring','Master') DEFAULT 'Student' AFTER category,
  ADD COLUMN IF NOT EXISTS overview_video_url VARCHAR(500) NULL AFTER video_link,
  ADD COLUMN IF NOT EXISTS currency VARCHAR(10) DEFAULT 'PHP' AFTER price,
  ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL AFTER status,
  ADD COLUMN IF NOT EXISTS datePublished TIMESTAMP NULL AFTER dateUpdated;

-- Update existing categories to match new difficulty labels if needed
UPDATE programs SET 
  difficulty_label = CASE 
    WHEN category = 'beginner' THEN 'Student'
    WHEN category = 'intermediate' THEN 'Aspiring' 
    WHEN category = 'advanced' THEN 'Master'
    ELSE 'Student'
  END
WHERE difficulty_label IS NULL;

-- ========================================
-- 2. ENHANCE EXISTING PROGRAM_CHAPTERS TABLE
-- ========================================

-- Add metadata columns to help with UI and validation
ALTER TABLE program_chapters
  ADD COLUMN IF NOT EXISTS has_quiz TINYINT(1) DEFAULT 0 AFTER content,
  ADD COLUMN IF NOT EXISTS story_count INT DEFAULT 0 AFTER has_quiz,
  ADD COLUMN IF NOT EXISTS quiz_question_count INT DEFAULT 0 AFTER story_count;

-- ========================================
-- 3. CREATE STORIES TABLE
-- ========================================

CREATE TABLE IF NOT EXISTS program_stories (
  story_id INT AUTO_INCREMENT PRIMARY KEY,
  chapter_id INT NOT NULL,
  program_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  synopsis_arabic TEXT NULL,
  synopsis_english TEXT NULL,
  video_url VARCHAR(500) NOT NULL COMMENT 'YouTube video URL for story progression',
  story_order INT DEFAULT 1,
  dateCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dateUpdated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_story_chapter FOREIGN KEY (chapter_id) REFERENCES program_chapters(chapter_id) ON DELETE CASCADE,
  CONSTRAINT fk_story_program FOREIGN KEY (program_id) REFERENCES programs(programID) ON DELETE CASCADE,
  
  INDEX idx_story_chapter (chapter_id),
  INDEX idx_story_program_order (program_id, story_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 4. CREATE INTERACTIVE SECTIONS TABLES
-- ========================================

-- Interactive sections within stories (1-3 per story)
CREATE TABLE IF NOT EXISTS story_interactive_sections (
  section_id INT AUTO_INCREMENT PRIMARY KEY,
  story_id INT NOT NULL,
  section_order INT NOT NULL DEFAULT 1,
  dateCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_section_story FOREIGN KEY (story_id) REFERENCES program_stories(story_id) ON DELETE CASCADE,
  
  INDEX idx_section_story_order (story_id, section_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Questions within interactive sections
CREATE TABLE IF NOT EXISTS interactive_questions (
  question_id INT AUTO_INCREMENT PRIMARY KEY,
  section_id INT NOT NULL,
  question_text TEXT NOT NULL,
  question_type ENUM('multiple_choice','fill_in_the_blanks','multiple_select') NOT NULL,
  question_order INT DEFAULT 1,
  dateCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_question_section FOREIGN KEY (section_id) REFERENCES story_interactive_sections(section_id) ON DELETE CASCADE,
  
  INDEX idx_question_section_order (section_id, question_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Answer options for interactive questions
CREATE TABLE IF NOT EXISTS question_options (
  option_id INT AUTO_INCREMENT PRIMARY KEY,
  question_id INT NOT NULL,
  option_text TEXT NOT NULL,
  is_correct TINYINT(1) DEFAULT 0 COMMENT 'Set to 1 for correct answer (green highlight in UI)',
  option_order INT DEFAULT 1,
  dateCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_option_question FOREIGN KEY (question_id) REFERENCES interactive_questions(question_id) ON DELETE CASCADE,
  
  INDEX idx_option_question_order (question_id, option_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 5. CREATE CHAPTER QUIZ TABLES
-- ========================================

-- One quiz per chapter (mandatory)
CREATE TABLE IF NOT EXISTS chapter_quizzes (
  quiz_id INT AUTO_INCREMENT PRIMARY KEY,
  chapter_id INT NOT NULL UNIQUE COMMENT 'One quiz per chapter',
  program_id INT NOT NULL,
  title VARCHAR(255) DEFAULT 'Chapter Quiz',
  max_questions INT DEFAULT 30 COMMENT 'Maximum 30 questions per quiz',
  dateCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dateUpdated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_quiz_chapter FOREIGN KEY (chapter_id) REFERENCES program_chapters(chapter_id) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_program FOREIGN KEY (program_id) REFERENCES programs(programID) ON DELETE CASCADE,
  
  INDEX idx_quiz_program (program_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz questions (multiple choice only, max 30)
CREATE TABLE IF NOT EXISTS quiz_questions (
  quiz_question_id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT NOT NULL,
  question_text TEXT NOT NULL,
  question_order INT DEFAULT 1,
  dateCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_quiz_question_quiz FOREIGN KEY (quiz_id) REFERENCES chapter_quizzes(quiz_id) ON DELETE CASCADE,
  
  INDEX idx_quiz_question_order (quiz_id, question_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz answer options
CREATE TABLE IF NOT EXISTS quiz_question_options (
  quiz_option_id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_question_id INT NOT NULL,
  option_text TEXT NOT NULL,
  is_correct TINYINT(1) DEFAULT 0,
  option_order INT DEFAULT 1,
  dateCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_quiz_option_question FOREIGN KEY (quiz_question_id) REFERENCES quiz_questions(quiz_question_id) ON DELETE CASCADE,
  
  INDEX idx_quiz_option_order (quiz_question_id, option_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 6. CREATE PUBLISHING WORKFLOW TABLES
-- ========================================

-- Program publishing requests (teacher submits, admin approves/rejects)
CREATE TABLE IF NOT EXISTS program_publish_requests (
  request_id INT AUTO_INCREMENT PRIMARY KEY,
  program_id INT NOT NULL,
  teacher_id INT NOT NULL COMMENT 'References teacher.teacherID',
  status ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
  admin_id INT NULL COMMENT 'References user.userID for admin who reviewed',
  review_message TEXT NULL COMMENT 'Admin feedback on approval/rejection',
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL DEFAULT NULL,
  
  CONSTRAINT fk_publish_program FOREIGN KEY (program_id) REFERENCES programs(programID) ON DELETE CASCADE,
  CONSTRAINT fk_publish_teacher FOREIGN KEY (teacher_id) REFERENCES teacher(teacherID) ON DELETE CASCADE,
  CONSTRAINT fk_publish_admin FOREIGN KEY (admin_id) REFERENCES user(userID) ON DELETE SET NULL,
  
  INDEX idx_publish_status (status),
  INDEX idx_publish_program (program_id),
  INDEX idx_publish_teacher (teacher_id),
  INDEX idx_publish_submitted (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 7. CREATE STUDENT PROGRESS TRACKING TABLES
-- ========================================

-- Student enrollment in programs
CREATE TABLE IF NOT EXISTS student_program_enrollments (
  enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL COMMENT 'References user.userID where role=student',
  program_id INT NOT NULL,
  enrollment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completion_percentage DECIMAL(5,2) DEFAULT 0.00,
  last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_enrollment_student FOREIGN KEY (student_id) REFERENCES user(userID) ON DELETE CASCADE,
  CONSTRAINT fk_enrollment_program FOREIGN KEY (program_id) REFERENCES programs(programID) ON DELETE CASCADE,
  
  UNIQUE KEY unique_student_program (student_id, program_id),
  INDEX idx_enrollment_student (student_id),
  INDEX idx_enrollment_program (program_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student progress on individual stories
CREATE TABLE IF NOT EXISTS student_story_progress (
  progress_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  story_id INT NOT NULL,
  is_completed TINYINT(1) DEFAULT 0,
  completion_date TIMESTAMP NULL,
  last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_story_progress_student FOREIGN KEY (student_id) REFERENCES user(userID) ON DELETE CASCADE,
  CONSTRAINT fk_story_progress_story FOREIGN KEY (story_id) REFERENCES program_stories(story_id) ON DELETE CASCADE,
  
  UNIQUE KEY unique_student_story (student_id, story_id),
  INDEX idx_story_progress_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student quiz attempts
CREATE TABLE IF NOT EXISTS student_quiz_attempts (
  attempt_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  quiz_id INT NOT NULL,
  score DECIMAL(5,2) NULL,
  max_score DECIMAL(5,2) NULL,
  is_passed TINYINT(1) DEFAULT 0,
  attempt_number INT DEFAULT 1,
  attempt_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_quiz_attempt_student FOREIGN KEY (student_id) REFERENCES user(userID) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_attempt_quiz FOREIGN KEY (quiz_id) REFERENCES chapter_quizzes(quiz_id) ON DELETE CASCADE,
  
  INDEX idx_quiz_attempt_student (student_id),
  INDEX idx_quiz_attempt_quiz (quiz_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 8. CREATE USEFUL VIEWS
-- ========================================

-- Teacher's programs with enhanced info
CREATE OR REPLACE VIEW teacher_program_overview AS
SELECT 
  p.programID,
  p.teacherID,
  p.title,
  p.description,
  p.category,
  p.difficulty_label,
  p.price,
  p.currency,
  p.thumbnail,
  p.overview_video_url,
  p.status,
  p.rejection_reason,
  p.dateCreated,
  p.dateUpdated,
  p.datePublished,
  COUNT(DISTINCT pc.chapter_id) as chapter_count,
  COUNT(DISTINCT ps.story_id) as story_count,
  COUNT(DISTINCT cq.quiz_id) as quiz_count,
  COUNT(DISTINCT spe.student_id) as enrollment_count
FROM programs p
LEFT JOIN program_chapters pc ON p.programID = pc.program_id
LEFT JOIN program_stories ps ON pc.chapter_id = ps.chapter_id
LEFT JOIN chapter_quizzes cq ON pc.chapter_id = cq.chapter_id
LEFT JOIN student_program_enrollments spe ON p.programID = spe.program_id
GROUP BY p.programID;

-- Program library view (published programs for discovery)
CREATE OR REPLACE VIEW program_library AS
SELECT 
  p.programID,
  p.teacherID,
  p.title,
  p.description,
  p.category,
  p.difficulty_label,
  p.price,
  p.currency,
  p.thumbnail,
  p.datePublished,
  t.fname as teacher_first_name,
  t.lname as teacher_last_name,
  u.fname as teacher_user_fname,
  u.lname as teacher_user_lname,
  COUNT(DISTINCT spe.student_id) as enrollment_count
FROM programs p
LEFT JOIN teacher t ON p.teacherID = t.teacherID
LEFT JOIN user u ON t.userID = u.userID
LEFT JOIN student_program_enrollments spe ON p.programID = spe.program_id
WHERE p.status = 'published'
GROUP BY p.programID
ORDER BY p.datePublished DESC;

-- ========================================
-- 9. CREATE TRIGGERS FOR DATA INTEGRITY
-- ========================================

DELIMITER //

-- Automatically create a quiz when a chapter is created
CREATE TRIGGER IF NOT EXISTS after_chapter_insert
AFTER INSERT ON program_chapters
FOR EACH ROW
BEGIN
  INSERT INTO chapter_quizzes (chapter_id, program_id, title)
  VALUES (NEW.chapter_id, NEW.program_id, CONCAT(NEW.title, ' Quiz'));
  
  UPDATE program_chapters SET has_quiz = 1 WHERE chapter_id = NEW.chapter_id;
END//

-- Update program modification date when chapters are modified
CREATE TRIGGER IF NOT EXISTS after_chapter_update
AFTER UPDATE ON program_chapters
FOR EACH ROW
BEGIN
  UPDATE programs SET dateUpdated = CURRENT_TIMESTAMP WHERE programID = NEW.program_id;
END//

-- Update story count when stories are added/removed
CREATE TRIGGER IF NOT EXISTS after_story_insert
AFTER INSERT ON program_stories
FOR EACH ROW
BEGIN
  UPDATE program_chapters 
  SET story_count = (SELECT COUNT(*) FROM program_stories WHERE chapter_id = NEW.chapter_id)
  WHERE chapter_id = NEW.chapter_id;
  
  UPDATE programs SET dateUpdated = CURRENT_TIMESTAMP WHERE programID = NEW.program_id;
END//

CREATE TRIGGER IF NOT EXISTS after_story_delete
AFTER DELETE ON program_stories
FOR EACH ROW
BEGIN
  UPDATE program_chapters 
  SET story_count = (SELECT COUNT(*) FROM program_stories WHERE chapter_id = OLD.chapter_id)
  WHERE chapter_id = OLD.chapter_id;
  
  UPDATE programs SET dateUpdated = CURRENT_TIMESTAMP WHERE programID = OLD.program_id;
END//

-- Update quiz question count when questions are added/removed
CREATE TRIGGER IF NOT EXISTS after_quiz_question_insert
AFTER INSERT ON quiz_questions
FOR EACH ROW
BEGIN
  DECLARE chapter_id_var INT;
  SELECT chapter_id INTO chapter_id_var FROM chapter_quizzes WHERE quiz_id = NEW.quiz_id;
  
  UPDATE program_chapters 
  SET quiz_question_count = (SELECT COUNT(*) FROM quiz_questions qq JOIN chapter_quizzes cq ON qq.quiz_id = cq.quiz_id WHERE cq.chapter_id = chapter_id_var)
  WHERE chapter_id = chapter_id_var;
END//

CREATE TRIGGER IF NOT EXISTS after_quiz_question_delete
AFTER DELETE ON quiz_questions
FOR EACH ROW
BEGIN
  DECLARE chapter_id_var INT;
  SELECT chapter_id INTO chapter_id_var FROM chapter_quizzes WHERE quiz_id = OLD.quiz_id;
  
  UPDATE program_chapters 
  SET quiz_question_count = (SELECT COUNT(*) FROM quiz_questions qq JOIN chapter_quizzes cq ON qq.quiz_id = cq.quiz_id WHERE cq.chapter_id = chapter_id_var)
  WHERE chapter_id = chapter_id_var;
END//

-- Update program status when publish request is approved/rejected
CREATE TRIGGER IF NOT EXISTS after_publish_request_update
AFTER UPDATE ON program_publish_requests
FOR EACH ROW
BEGIN
  IF NEW.status = 'approved' AND OLD.status = 'pending' THEN
    UPDATE programs 
    SET status = 'published', datePublished = CURRENT_TIMESTAMP 
    WHERE programID = NEW.program_id;
  ELSEIF NEW.status = 'rejected' AND OLD.status = 'pending' THEN
    UPDATE programs 
    SET status = 'draft', rejection_reason = NEW.review_message 
    WHERE programID = NEW.program_id;
  END IF;
END//

DELIMITER ;

-- ========================================
-- 10. CREATE INDEXES FOR PERFORMANCE
-- ========================================

-- Additional indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_programs_teacher_status ON programs(teacherID, status);
CREATE INDEX IF NOT EXISTS idx_programs_status_published ON programs(status, datePublished);
CREATE INDEX IF NOT EXISTS idx_stories_chapter_order ON program_stories(chapter_id, story_order);
CREATE INDEX IF NOT EXISTS idx_interactions_story ON story_interactive_sections(story_id);
CREATE INDEX IF NOT EXISTS idx_questions_section ON interactive_questions(section_id);
CREATE INDEX IF NOT EXISTS idx_enrollments_student_program ON student_program_enrollments(student_id, program_id);
CREATE INDEX IF NOT EXISTS idx_progress_student ON student_story_progress(student_id);

-- ========================================
-- 11. SAMPLE DATA UPDATE (OPTIONAL)
-- ========================================

-- Update existing programs to have proper difficulty labels
UPDATE programs SET difficulty_label = 'Student' WHERE category = 'beginner' AND difficulty_label IS NULL;
UPDATE programs SET difficulty_label = 'Aspiring' WHERE category = 'intermediate' AND difficulty_label IS NULL;
UPDATE programs SET difficulty_label = 'Master' WHERE category = 'advanced' AND difficulty_label IS NULL;

-- Set currency for existing programs
UPDATE programs SET currency = 'PHP' WHERE currency IS NULL;

-- Update existing chapters to have proper counts (will be maintained by triggers going forward)
UPDATE program_chapters pc
SET 
  story_count = (SELECT COUNT(*) FROM program_stories ps WHERE ps.chapter_id = pc.chapter_id),
  has_quiz = (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM chapter_quizzes cq WHERE cq.chapter_id = pc.chapter_id),
  quiz_question_count = (SELECT COUNT(*) FROM quiz_questions qq JOIN chapter_quizzes cq ON qq.quiz_id = cq.quiz_id WHERE cq.chapter_id = pc.chapter_id);

COMMIT;

-- Migration completed successfully!
-- Next steps:
-- 1. Update your PHP code to use the new tables and columns
-- 2. Test the enhanced program creation workflow
-- 3. Implement the publishing system for admin approval
-- 4. Add progress tracking for students