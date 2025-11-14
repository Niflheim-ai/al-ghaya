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
     * Check program completion
     */
    public function checkProgramComplete() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM student_program_enrollments 
            WHERE student_id = ? AND completion_percentage >= 100
        ");
        $stmt->bind_param("i", $this->studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();

        if ($count >= 1) {
            return $this->awardAchievement('program_complete');
        }
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
     */
    public function checkGraduateAchievements() {
        $awarded = false;

        // Beginner Graduate
        $beginnerStmt = $this->conn->prepare("
            SELECT 
                COUNT(DISTINCT p.programID) as total,
                COUNT(DISTINCT CASE WHEN spe.completion_percentage >= 100 THEN spe.program_id END) as completed
            FROM programs p
            LEFT JOIN student_program_enrollments spe ON p.programID = spe.program_id AND spe.student_id = ?
            WHERE p.category = 'beginner'
        ");
        $beginnerStmt->bind_param("i", $this->studentID);
        $beginnerStmt->execute();
        $beginnerResult = $beginnerStmt->get_result()->fetch_assoc();
        $beginnerStmt->close();

        if ($beginnerResult['total'] > 0 && $beginnerResult['completed'] >= $beginnerResult['total']) {
            $awarded = $this->awardAchievement('beginner_graduate') || $awarded;
        }

        // Intermediate Graduate
        $intermediateStmt = $this->conn->prepare("
            SELECT 
                COUNT(DISTINCT p.programID) as total,
                COUNT(DISTINCT CASE WHEN spe.completion_percentage >= 100 THEN spe.program_id END) as completed
            FROM programs p
            LEFT JOIN student_program_enrollments spe ON p.programID = spe.program_id AND spe.student_id = ?
            WHERE p.category = 'intermediate'
        ");
        $intermediateStmt->bind_param("i", $this->studentID);
        $intermediateStmt->execute();
        $intermediateResult = $intermediateStmt->get_result()->fetch_assoc();
        $intermediateStmt->close();

        if ($intermediateResult['total'] > 0 && $intermediateResult['completed'] >= $intermediateResult['total']) {
            $awarded = $this->awardAchievement('intermediate_graduate') || $awarded;
        }

        // Advanced Graduate
        $advancedStmt = $this->conn->prepare("
            SELECT 
                COUNT(DISTINCT p.programID) as total,
                COUNT(DISTINCT CASE WHEN spe.completion_percentage >= 100 THEN spe.program_id END) as completed
            FROM programs p
            LEFT JOIN student_program_enrollments spe ON p.programID = spe.program_id AND spe.student_id = ?
            WHERE p.category = 'advanced'
        ");
        $advancedStmt->bind_param("i", $this->studentID);
        $advancedStmt->execute();
        $advancedResult = $advancedStmt->get_result()->fetch_assoc();
        $advancedStmt->close();

        if ($advancedResult['total'] > 0 && $advancedResult['completed'] >= $advancedResult['total']) {
            $awarded = $this->awardAchievement('advanced_graduate') || $awarded;
        }

        return $awarded;
    }

    /**
     * Check level up achievement
     */
    public function checkLevelUp() {
        // Assuming you have a level system in your user table
        $stmt = $this->conn->prepare("SELECT level FROM user WHERE userID = ?");
        $stmt->bind_param("i", $this->studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $level = $result->fetch_assoc()['level'] ?? 1;
        $stmt->close();

        if ($level > 1) {
            return $this->awardAchievement('level_up');
        }
        return false;
    }

    /**
     * Check proficiency up achievement
     * Should ONLY be called when proficiency actually changes
     * Don't call this automatically - trigger it manually when updating proficiency
     */
    public function checkProficiencyUp() {
        // This should NOT be called in checkAllAchievements()
        // Only call this when you manually increase a user's proficiency level
        
        // For now, return false to prevent auto-awarding
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