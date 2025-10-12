<?php
include_once('dbConnection.php');

/**
 * Al-Ghaya Gamification System
 * Handles points, levels, achievements, and progress tracking
 */

class GamificationSystem {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    /**
     * Award points to a user for completing activities
     */
    public function awardPoints($userID, $points, $activity_type, $description = '') {
        try {
            // Add points to user account
            $stmt = $this->conn->prepare("UPDATE user SET points = points + ? WHERE userID = ?");
            $stmt->bind_param("ii", $points, $userID);
            $stmt->execute();
            
            // Log the point transaction
            $stmt = $this->conn->prepare("
                INSERT INTO point_transactions (userID, points, activity_type, description, dateCreated) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iiss", $userID, $points, $activity_type, $description);
            $stmt->execute();
            
            // Check if user leveled up
            $this->checkLevelUp($userID);
            
            return true;
        } catch (Exception $e) {
            error_log("Error awarding points: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user should level up and update accordingly
     */
    public function checkLevelUp($userID) {
        try {
            // Get current user stats
            $stmt = $this->conn->prepare("SELECT points, level, proficiency FROM user WHERE userID = ?");
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) return false;
            
            $currentPoints = $user['points'];
            $currentLevel = $user['level'];
            $currentProficiency = $user['proficiency'];
            
            // Calculate new level based on points
            $newLevel = $this->calculateLevel($currentPoints);
            $newProficiency = $this->calculateProficiency($newLevel);
            
            // If leveled up, update user and award achievement
            if ($newLevel > $currentLevel || $newProficiency != $currentProficiency) {
                $stmt = $this->conn->prepare("UPDATE user SET level = ?, proficiency = ? WHERE userID = ?");
                $stmt->bind_param("isi", $newLevel, $newProficiency, $userID);
                $stmt->execute();
                
                // Award level up achievement
                $this->unlockAchievement($userID, 'level_up', "Reached Level {$newLevel}");
                
                // Award proficiency achievement if changed
                if ($newProficiency != $currentProficiency) {
                    $this->unlockAchievement($userID, 'proficiency_up', "Advanced to {$newProficiency} proficiency");
                }
                
                return [
                    'leveled_up' => true,
                    'new_level' => $newLevel,
                    'new_proficiency' => $newProficiency,
                    'proficiency_changed' => $newProficiency != $currentProficiency
                ];
            }
            
            return ['leveled_up' => false];
        } catch (Exception $e) {
            error_log("Error checking level up: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate user level based on points
     */
    private function calculateLevel($points) {
        if ($points < 100) return 1;
        if ($points < 300) return 2;
        if ($points < 600) return 3;
        if ($points < 1000) return 4;
        if ($points < 1500) return 5;
        if ($points < 2200) return 6;
        if ($points < 3000) return 7;
        if ($points < 4000) return 8;
        if ($points < 5500) return 9;
        if ($points < 7500) return 10;
        
        // For levels above 10, each level requires 1000 more points
        return 10 + floor(($points - 7500) / 1000);
    }

    /**
     * Calculate proficiency based on level
     */
    private function calculateProficiency($level) {
        if ($level <= 3) return 'beginner';
        if ($level <= 7) return 'intermediate';
        return 'advanced';
    }

    /**
     * Get points required for next level
     */
    public function getPointsForNextLevel($currentPoints) {
        $currentLevel = $this->calculateLevel($currentPoints);
        $nextLevel = $currentLevel + 1;
        
        // Calculate points needed for next level
        if ($nextLevel <= 10) {
            $thresholds = [0, 100, 300, 600, 1000, 1500, 2200, 3000, 4000, 5500, 7500];
            return isset($thresholds[$nextLevel]) ? $thresholds[$nextLevel] : 7500 + (($nextLevel - 10) * 1000);
        }
        
        return 7500 + (($nextLevel - 10) * 1000);
    }

    /**
     * Unlock achievement for user
     */
    public function unlockAchievement($userID, $achievementType, $description) {
        try {
            // Check if achievement already exists
            $stmt = $this->conn->prepare("
                SELECT id FROM user_achievements 
                WHERE userID = ? AND achievement_type = ?
            ");
            $stmt->bind_param("is", $userID, $achievementType);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                // Achievement doesn't exist, create it
                $stmt = $this->conn->prepare("
                    INSERT INTO user_achievements (userID, achievement_type, description, dateUnlocked)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->bind_param("iss", $userID, $achievementType, $description);
                $stmt->execute();
                
                return true;
            }
            
            return false; // Achievement already exists
        } catch (Exception $e) {
            error_log("Error unlocking achievement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's gamification stats
     */
    public function getUserStats($userID) {
        try {
            $stmt = $this->conn->prepare("
                SELECT points, level, proficiency 
                FROM user 
                WHERE userID = ?
            ");
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) return null;
            
            $pointsForNext = $this->getPointsForNextLevel($user['points']);
            $progressToNext = ($user['points'] / $pointsForNext) * 100;
            
            return [
                'points' => $user['points'],
                'level' => $user['level'],
                'proficiency' => $user['proficiency'],
                'points_for_next_level' => $pointsForNext,
                'progress_to_next_level' => min(100, $progressToNext)
            ];
        } catch (Exception $e) {
            error_log("Error getting user stats: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recent point transactions for user
     */
    public function getRecentTransactions($userID, $limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT points, activity_type, description, dateCreated 
                FROM point_transactions 
                WHERE userID = ? 
                ORDER BY dateCreated DESC 
                LIMIT ?
            ");
            $stmt->bind_param("ii", $userID, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting recent transactions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user achievements
     */
    public function getUserAchievements($userID) {
        try {
            $stmt = $this->conn->prepare("
                SELECT achievement_type, description, dateUnlocked 
                FROM user_achievements 
                WHERE userID = ? 
                ORDER BY dateUnlocked DESC
            ");
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user achievements: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update program progress and award points
     */
    public function updateProgramProgress($studentID, $programID, $chapterID, $completed = false) {
        try {
            // Update chapter completion
            if ($completed) {
                $stmt = $this->conn->prepare("
                    INSERT INTO student_chapter_progress (studentID, programID, chapterID, completed, completedAt)
                    VALUES (?, ?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE completed = 1, completedAt = NOW()
                ");
                $stmt->bind_param("iii", $studentID, $programID, $chapterID);
                $stmt->execute();
                
                // Award points for chapter completion
                $this->awardPoints($studentID, 50, 'chapter_completion', "Completed chapter in program {$programID}");
            }
            
            // Calculate overall program progress
            $progress = $this->calculateProgramProgress($studentID, $programID);
            
            // Update student_program table
            $stmt = $this->conn->prepare("
                INSERT INTO student_program (studentID, programID, progress, enrolledAt)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE progress = ?
            ");
            $stmt->bind_param("iidi", $studentID, $programID, $progress, $progress);
            $stmt->execute();
            
            // Award completion bonus if program is finished
            if ($progress >= 100) {
                $this->awardPoints($studentID, 200, 'program_completion', "Completed program {$programID}");
                $this->unlockAchievement($studentID, 'program_complete', "Completed a learning program");
            }
            
            return $progress;
        } catch (Exception $e) {
            error_log("Error updating program progress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate program completion percentage
     */
    private function calculateProgramProgress($studentID, $programID) {
        try {
            // Get total chapters in program
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) as total FROM program_chapters WHERE program_id = ?"
            );
            $stmt->bind_param("i", $programID);
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc()['total'];
            
            if ($total == 0) return 0;
            
            // Get completed chapters
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as completed 
                FROM student_chapter_progress 
                WHERE studentID = ? AND programID = ? AND completed = 1
            ");
            $stmt->bind_param("ii", $studentID, $programID);
            $stmt->execute();
            $completed = $stmt->get_result()->fetch_assoc()['completed'];
            
            return round(($completed / $total) * 100, 2);
        } catch (Exception $e) {
            error_log("Error calculating program progress: " . $e->getMessage());
            return 0;
        }
    }
}

// Point values for different activities
class PointValues {
    const LOGIN_DAILY = 10;
    const CHAPTER_COMPLETION = 50;
    const PROGRAM_COMPLETION = 200;
    const QUIZ_CORRECT = 5;
    const QUIZ_PERFECT = 25;
    const STREAK_BONUS = 15;
    const FIRST_PROGRAM = 100;
}

// Global helper functions
function getGamificationSystem() {
    global $conn;
    return new GamificationSystem($conn);
}

?>
