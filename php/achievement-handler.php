<?php
/**
 * Achievement Handler
 * Manages automatic achievement unlocking based on student actions
 */

require_once 'dbConnection.php';

class AchievementHandler {
    private $conn;
    private $studentID;
    private $newlyUnlocked = []; // Track newly unlocked achievements in this session

    public function __construct($db, $studentID) {
        $this->conn = $db;
        $this->studentID = (int)$studentID;
    }

    /**
     * Check and award achievement if conditions are met
     */
    public function awardAchievement($achievementType, $description = null) {
        // Check if already unlocked
        $checkStmt = $this->conn->prepare("
            SELECT id FROM user_achievements 
            WHERE userID = ? AND achievement_type = ?
        ");
        $checkStmt->bind_param("is", $this->studentID, $achievementType);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $checkStmt->close();
            return false; // Already unlocked
        }
        $checkStmt->close();

        // Get achievement details if not provided
        $defStmt = $this->conn->prepare("
            SELECT name, description, icon 
            FROM achievement_definitions 
            WHERE achievement_type = ?
        ");
        $defStmt->bind_param("s", $achievementType);
        $defStmt->execute();
        $defResult = $defStmt->get_result();
        $achievementData = $defResult->fetch_assoc();
        $defStmt->close();

        if (!$achievementData) {
            return false; // Achievement type doesn't exist
        }

        $description = $description ?? $achievementData['description'];

        // Award the achievement
        $insertStmt = $this->conn->prepare("
            INSERT INTO user_achievements (userID, achievement_type, description, dateUnlocked) 
            VALUES (?, ?, ?, NOW())
        ");
        $insertStmt->bind_param("iss", $this->studentID, $achievementType, $description);
        $success = $insertStmt->execute();
        $insertStmt->close();

        if ($success) {
            // Track newly unlocked for notification
            $this->newlyUnlocked[] = [
                'achievement_type' => $achievementType,
                'name' => $achievementData['name'],
                'description' => $description,
                'icon' => $achievementData['icon']
            ];
            
            error_log("Achievement unlocked for user {$this->studentID}: {$achievementType}");
        }

        return $success;
    }

    /**
     * Get newly unlocked achievements from this session
     */
    public function getNewlyUnlocked() {
        return $this->newlyUnlocked;
    }

    /**
     * Get recent achievements (last N seconds)
     */
    public function getRecentAchievements($seconds = 10) {
        $stmt = $this->conn->prepare("
            SELECT ua.*, ad.name, ad.icon 
            FROM user_achievements ua
            JOIN achievement_definitions ad ON ua.achievement_type = ad.achievement_type
            WHERE ua.userID = ? 
            AND ua.dateUnlocked >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ORDER BY ua.dateUnlocked DESC
        ");
        $stmt->bind_param("ii", $this->studentID, $seconds);
        $stmt->execute();
        $result = $stmt->get_result();
        $achievements = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $achievements;
    }

    /**
     * Check for new achievements and return them as JSON
     */
    public function checkAndGetNew($seconds = 10) {
        $achievements = $this->getRecentAchievements($seconds);
        
        return [
            'success' => true,
            'achievements' => $achievements
        ];
    }

    /**
     * Check first login achievement
     */
    public function checkFirstLogin() {
        $checkStmt = $this->conn->prepare("
            SELECT id FROM user_achievements 
            WHERE userID = ? AND achievement_type = 'first_login'
        ");
        $checkStmt->bind_param("i", $this->studentID);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            // Already awarded
            $checkStmt->close();
            return false;
        }
        $checkStmt->close();
        
        // Award the achievement (first login!)
        return $this->awardAchievement('first_login');
    }

    /**
     * Check first program enrollment
     */
    public function checkFirstProgram() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM student_program_enrollments 
            WHERE student_id = ?
        ");
        $stmt->bind_param("i", $this->studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();

        if ($count >= 1) {
            return $this->awardAchievement('first_program');
        }
        return false;
    }

    /**
     * Check first story completion
    */
    public function checkStoryComplete() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM student_story_progress 
            WHERE student_id = ? AND is_completed = 1
        ");
        $stmt->bind_param("i", $this->studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();

        if ($count >= 1) {
            return $this->awardAchievement('story_complete');
        }
        return false;
    }

    /**
     * Check program completion achievement
     * Awards achievement when student completes ALL stories in at least one program
     */
    public function checkProgramComplete() {
        // Get all programs the student has started
        $stmt = $this->conn->prepare("
            SELECT DISTINCT pc.programID, 
                COUNT(DISTINCT cs.story_id) as total_stories,
                COUNT(DISTINCT CASE WHEN ssp.is_completed = 1 THEN ssp.story_id END) as completed_stories
            FROM program_chapters pc
            INNER JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
            LEFT JOIN student_story_progress ssp ON cs.story_id = ssp.story_id AND ssp.student_id = ?
            GROUP BY pc.programID
            HAVING total_stories > 0 AND completed_stories >= total_stories
        ");
        $stmt->bind_param("i", $this->studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // If at least one program is 100% complete, award achievement
        if ($result->num_rows > 0) {
            $stmt->close();
            return $this->awardAchievement('program_complete');
        }
        
        $stmt->close();
        return false;
    }

    /**
     * Check points-based achievements
     */
    public function checkPointsAchievements() {
        // Get user's current points
        $stmt = $this->conn->prepare("SELECT points FROM user WHERE userID = ?");
        $stmt->bind_param("i", $this->studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $points = $result->fetch_assoc()['points'] ?? 0;
        $stmt->close();

        $awarded = false;

        // Check each points milestone
        if ($points >= 1000) {
            $awarded = $this->awardAchievement('points_1000') || $awarded;
        }
        if ($points >= 500) {
            $awarded = $this->awardAchievement('points_500') || $awarded;
        }
        if ($points >= 100) {
            $awarded = $this->awardAchievement('points_100') || $awarded;
        }

        return $awarded;
    }

    /**
     * Check chapter streak achievement
     */
    public function checkChapterStreak() {
        // This assumes you track chapter completions in order
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM student_story_progress 
            WHERE student_id = ? AND is_completed = 1
        ");
        $stmt->bind_param("i", $this->studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $completedChapters = $result->fetch_assoc()['count'];
        $stmt->close();

        if ($completedChapters >= 5) {
            return $this->awardAchievement('chapter_streak_5');
        }
        return false;
    }

    /**
     * Check graduate achievements (beginner, intermediate, advanced)
     * Awards when student completes ALL programs in a difficulty category
     */
    public function checkGraduateAchievements() {
        $awarded = false;

        // Check each category
        $categories = ['beginner', 'intermediate', 'advanced'];
        
        foreach ($categories as $category) {
            // Get total programs in this category
            $totalStmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT programID) as total 
                FROM programs 
                WHERE category = ? AND status = 'published'
            ");
            $totalStmt->bind_param("s", $category);
            $totalStmt->execute();
            $totalPrograms = $totalStmt->get_result()->fetch_assoc()['total'];
            $totalStmt->close();
            
            if ($totalPrograms == 0) continue; // Skip if no programs in this category
            
            // Count how many programs in this category are 100% complete
            $completedStmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT completed_programs.programID) as completed
                FROM (
                    SELECT pc.programID,
                        COUNT(DISTINCT cs.story_id) as total_stories,
                        COUNT(DISTINCT CASE WHEN ssp.is_completed = 1 THEN ssp.story_id END) as completed_stories
                    FROM programs p
                    INNER JOIN program_chapters pc ON p.programID = pc.programID
                    INNER JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
                    LEFT JOIN student_story_progress ssp ON cs.story_id = ssp.story_id AND ssp.student_id = ?
                    WHERE p.category = ? AND p.status = 'published'
                    GROUP BY pc.programID
                    HAVING total_stories > 0 AND completed_stories >= total_stories
                ) AS completed_programs
            ");
            $completedStmt->bind_param("is", $this->studentID, $category);
            $completedStmt->execute();
            $completedPrograms = $completedStmt->get_result()->fetch_assoc()['completed'];
            $completedStmt->close();
            
            // Award achievement if all programs in category are complete
            if ($completedPrograms >= $totalPrograms) {
                $achievementType = $category . '_graduate';
                $awarded = $this->awardAchievement($achievementType) || $awarded;
            }
        }
        
        return $awarded;
    }

    /**
     * Check level up achievement
     * NOTE: This is now handled by gamification.php automatically
     * Keep this method for backward compatibility but don't call it
     */
    public function checkLevelUp() {
        // This achievement is now awarded automatically by gamification.php
        // when the user's level increases via checkLevelUp() method
        return false;
    }

    /**
     * Check proficiency up achievement
     * NOTE: This is now handled by gamification.php automatically
     * Keep this method for backward compatibility but don't call it
     */
    public function checkProficiencyUp() {
        // This achievement is now awarded automatically by gamification.php
        // when the user's proficiency changes via checkLevelUp() method
        return false;
    }

    /**
     * Check all achievements at once
     */
    public function checkAllAchievements() {
        // $this->checkFirstLogin();
        $this->checkFirstProgram();
        $this->checkProgramComplete();
        $this->checkPointsAchievements();
        $this->checkChapterStreak();
        $this->checkGraduateAchievements();
        // $this->checkLevelUp();
        // $this->checkProficiencyUp();
    }
}

/**
 * Helper function to check achievements for a student
 */
function checkStudentAchievements($conn, $studentID) {
    $handler = new AchievementHandler($conn, $studentID);
    $handler->checkAllAchievements();
    return $handler;
}

/**
 * Helper function to award specific achievement
 */
function awardSpecificAchievement($conn, $studentID, $achievementType) {
    $handler = new AchievementHandler($conn, $studentID);
    return $handler->awardAchievement($achievementType);
}
?>