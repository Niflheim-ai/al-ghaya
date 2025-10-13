-- Enhanced Program System Database Schema
-- This schema supports the complete program creation workflow with chapters, stories, quizzes and publishing

-- Programs table (enhanced)
CREATE TABLE IF NOT EXISTS programs (
    programID INT PRIMARY KEY AUTO_INCREMENT,
    teacherID INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('Student', 'Aspiring', 'Master') NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    thumbnail VARCHAR(255) DEFAULT 'default-thumbnail.jpg',
    overview_video_url VARCHAR(500),
    difficulty_level ENUM('Student', 'Aspiring', 'Master') NOT NULL,
    status ENUM('draft', 'pending_review', 'published', 'rejected') DEFAULT 'draft',
    rejection_reason TEXT,
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dateUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    datePublished TIMESTAMP NULL,
    INDEX idx_teacher (teacherID),
    INDEX idx_status (status),
    INDEX idx_category (category)
);

-- Program chapters table (enhanced)
CREATE TABLE IF NOT EXISTS program_chapters (
    chapter_id INT PRIMARY KEY AUTO_INCREMENT,
    program_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    chapter_order INT NOT NULL,
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dateUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(programID) ON DELETE CASCADE,
    INDEX idx_program_order (program_id, chapter_order)
);

-- Chapter stories table
CREATE TABLE IF NOT EXISTS chapter_stories (
    story_id INT PRIMARY KEY AUTO_INCREMENT,
    chapter_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    synopsis_arabic TEXT,
    synopsis_english TEXT,
    video_url VARCHAR(500) NOT NULL,
    story_order INT NOT NULL DEFAULT 1,
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dateUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chapter_id) REFERENCES program_chapters(chapter_id) ON DELETE CASCADE,
    INDEX idx_chapter_order (chapter_id, story_order)
);

-- Interactive sections within stories
CREATE TABLE IF NOT EXISTS story_interactive_sections (
    section_id INT PRIMARY KEY AUTO_INCREMENT,
    story_id INT NOT NULL,
    section_order INT NOT NULL,
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (story_id) REFERENCES chapter_stories(story_id) ON DELETE CASCADE,
    INDEX idx_story_order (story_id, section_order)
);

-- Questions within interactive sections
CREATE TABLE IF NOT EXISTS interactive_questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'fill_in_blanks', 'multiple_select') NOT NULL,
    question_order INT NOT NULL DEFAULT 1,
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES story_interactive_sections(section_id) ON DELETE CASCADE,
    INDEX idx_section_order (section_id, question_order)
);

-- Answer options for questions
CREATE TABLE IF NOT EXISTS question_options (
    option_id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    option_order INT NOT NULL,
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES interactive_questions(question_id) ON DELETE CASCADE,
    INDEX idx_question_order (question_id, option_order)
);

-- Chapter quizzes (mandatory 1 per chapter)
CREATE TABLE IF NOT EXISTS chapter_quizzes (
    quiz_id INT PRIMARY KEY AUTO_INCREMENT,
    chapter_id INT NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT 'Chapter Quiz',
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dateUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chapter_id) REFERENCES program_chapters(chapter_id) ON DELETE CASCADE,
    UNIQUE KEY unique_chapter_quiz (chapter_id)
);

-- Quiz questions (multiple choice only, max 30)
CREATE TABLE IF NOT EXISTS quiz_questions (
    quiz_question_id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_order INT NOT NULL,
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES chapter_quizzes(quiz_id) ON DELETE CASCADE,
    INDEX idx_quiz_order (quiz_id, question_order)
);

-- Quiz answer options
CREATE TABLE IF NOT EXISTS quiz_question_options (
    quiz_option_id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    option_order INT NOT NULL,
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_question_id) REFERENCES quiz_questions(quiz_question_id) ON DELETE CASCADE,
    INDEX idx_question_order (quiz_question_id, option_order)
);

-- Program publishing requests
CREATE TABLE IF NOT EXISTS program_publish_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    program_id INT NOT NULL,
    teacher_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_id INT NULL,
    admin_notes TEXT,
    dateRequested TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dateReviewed TIMESTAMP NULL,
    FOREIGN KEY (program_id) REFERENCES programs(programID) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_teacher (teacher_id),
    INDEX idx_date (dateRequested)
);

-- Student enrollment and progress
CREATE TABLE IF NOT EXISTS student_enrollments (
    enrollment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    program_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_program (program_id),
    UNIQUE KEY unique_enrollment (student_id, program_id)
);

-- Student progress tracking for stories
CREATE TABLE IF NOT EXISTS student_story_progress (
    progress_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    story_id INT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completion_date TIMESTAMP NULL,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_story (student_id, story_id),
    UNIQUE KEY unique_progress (student_id, story_id)
);

-- Student quiz attempts
CREATE TABLE IF NOT EXISTS student_quiz_attempts (
    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score DECIMAL(5,2),
    max_score DECIMAL(5,2),
    is_passed BOOLEAN DEFAULT FALSE,
    attempt_number INT DEFAULT 1,
    attempt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_quiz (student_id, quiz_id)
);

-- Views for easier data retrieval

-- Complete program overview
CREATE OR REPLACE VIEW program_overview AS
SELECT 
    p.programID,
    p.title,
    p.description,
    p.category,
    p.price,
    p.thumbnail,
    p.overview_video_url,
    p.status,
    p.dateCreated,
    p.datePublished,
    u.fname as teacher_first_name,
    u.lname as teacher_last_name,
    COUNT(DISTINCT pc.chapter_id) as chapter_count,
    COUNT(DISTINCT cs.story_id) as story_count,
    COUNT(DISTINCT se.student_id) as enrollment_count
FROM programs p
LEFT JOIN user u ON p.teacherID = u.userID
LEFT JOIN program_chapters pc ON p.programID = pc.program_id
LEFT JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
LEFT JOIN student_enrollments se ON p.programID = se.program_id
GROUP BY p.programID;

-- Chapter completion overview
CREATE OR REPLACE VIEW chapter_completion_stats AS
SELECT 
    pc.chapter_id,
    pc.program_id,
    pc.title,
    COUNT(cs.story_id) as story_count,
    COUNT(cq.quiz_id) as quiz_count,
    COUNT(qq.quiz_question_id) as quiz_question_count
FROM program_chapters pc
LEFT JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
LEFT JOIN chapter_quizzes cq ON pc.chapter_id = cq.chapter_id
LEFT JOIN quiz_questions qq ON cq.quiz_id = qq.quiz_id
GROUP BY pc.chapter_id;

-- Sample data triggers to maintain data integrity

DELIMITER //

-- Ensure each chapter has exactly one quiz
CREATE TRIGGER after_chapter_insert
AFTER INSERT ON program_chapters
FOR EACH ROW
BEGIN
    INSERT INTO chapter_quizzes (chapter_id, title)
    VALUES (NEW.chapter_id, CONCAT(NEW.title, ' Quiz'));
END//

-- Update program modification date when chapters are modified
CREATE TRIGGER after_chapter_update
AFTER UPDATE ON program_chapters
FOR EACH ROW
BEGIN
    UPDATE programs SET dateUpdated = CURRENT_TIMESTAMP WHERE programID = NEW.program_id;
END//

-- Update program status when publish request is approved
CREATE TRIGGER after_publish_approval
AFTER UPDATE ON program_publish_requests
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' AND OLD.status = 'pending' THEN
        UPDATE programs 
        SET status = 'published', datePublished = CURRENT_TIMESTAMP 
        WHERE programID = NEW.program_id;
    ELSEIF NEW.status = 'rejected' AND OLD.status = 'pending' THEN
        UPDATE programs 
        SET status = 'rejected', rejection_reason = NEW.admin_notes 
        WHERE programID = NEW.program_id;
    END IF;
END//

DELIMITER ;

-- Sample indexes for performance
CREATE INDEX idx_programs_teacher_status ON programs(teacherID, status);
CREATE INDEX idx_stories_chapter ON chapter_stories(chapter_id);
CREATE INDEX idx_questions_section ON interactive_questions(section_id);
CREATE INDEX idx_enrollments_student ON student_enrollments(student_id);
CREATE INDEX idx_progress_student ON student_story_progress(student_id);