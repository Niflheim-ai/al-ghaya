<?php
/**
 * Enhanced Program Management Functions - Compatible with Existing Schema
 * Handles programs, chapters, stories, quizzes, and interactive sections
 * Updated to work with existing al_ghaya_lms database structure
 */

// ==================== PROGRAM FUNCTIONS ====================

/**
 * Get all programs for a specific teacher
 */
function getTeacherPrograms($conn, $teacher_id, $status = null) {
    $sql = "SELECT p.*, t.userID 
            FROM programs p 
            JOIN teacher t ON p.teacherID = t.teacherID 
            WHERE p.teacherID = ?";
    $params = [$teacher_id];
    $types = "i";
    
    if ($status) {
        $sql .= " AND p.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $sql .= " ORDER BY p.dateCreated DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all published programs (for Program Library)
 */
function getAllPublishedPrograms($conn, $exclude_teacher_id = null) {
    $sql = "SELECT p.*, t.fname as teacher_fname, t.lname as teacher_lname,
                   u.fname as user_fname, u.lname as user_lname
            FROM programs p 
            LEFT JOIN teacher t ON p.teacherID = t.teacherID 
            LEFT JOIN user u ON t.userID = u.userID
            WHERE p.status = 'published'";
    $params = [];
    $types = "";
    
    if ($exclude_teacher_id) {
        $sql .= " AND p.teacherID != ?";
        $params[] = $exclude_teacher_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY p.datePublished DESC, p.dateCreated DESC";
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get a specific program by ID and verify teacher ownership
 */
function getProgram($conn, $program_id, $teacher_id) {
    $stmt = $conn->prepare("SELECT * FROM programs WHERE programID = ? AND teacherID = ?");
    $stmt->bind_param("ii", $program_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Create a new program
 */
function createProgram($conn, $data) {
    $sql = "INSERT INTO programs (teacherID, title, description, category, difficulty_label, price, thumbnail, overview_video_url, status, currency) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssdssss", 
        $data['teacherID'],
        $data['title'],
        $data['description'],
        $data['category'],
        $data['difficulty_level'],
        $data['price'],
        $data['thumbnail'],
        $data['overview_video_url'],
        $data['status'],
        $data['currency'] ?? 'PHP'
    );
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

/**
 * Update an existing program
 */
function updateProgram($conn, $program_id, $data) {
    $sql = "UPDATE programs SET title = ?, description = ?, category = ?, difficulty_label = ?, price = ?, 
                               overview_video_url = ?, status = ?, dateUpdated = CURRENT_TIMESTAMP
            WHERE programID = ? AND teacherID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssdssii", 
        $data['title'],
        $data['description'],
        $data['category'],
        $data['difficulty_level'],
        $data['price'],
        $data['overview_video_url'],
        $data['status'],
        $program_id,
        $data['teacherID']
    );
    
    return $stmt->execute();
}

/**
 * Delete a program and all associated data
 */
function deleteProgram($conn, $program_id, $teacher_id) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete all associated data (cascading deletes should handle most of this)
        $stmt = $conn->prepare("DELETE FROM programs WHERE programID = ? AND teacherID = ?");
        $stmt->bind_param("ii", $program_id, $teacher_id);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

/**
 * Verify program ownership by teacher
 */
function verifyProgramOwnership($conn, $program_id, $teacher_id) {
    $stmt = $conn->prepare("SELECT 1 FROM programs WHERE programID = ? AND teacherID = ?");
    $stmt->bind_param("ii", $program_id, $teacher_id);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}
?>