<?php
    require_once 'dbConnection.php';
    $teacherId = intval($_GET['teacherId'] ?? 0);

    // Get teacher details
    $stmt = $conn->prepare("
    SELECT u.fname, u.lname, u.email, u.dateCreated, t.specialization
    FROM teacher t
    JOIN user u ON u.userID = t.userID
    WHERE t.userID = ?
    ");
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$teacher) {
    echo json_encode(['success'=>false, 'message'=>'Teacher not found.']); exit;
    }

    // Get programs and enrollee count for each
    $progQ = $conn->prepare("
    SELECT p.programID, p.title,
            (SELECT COUNT(*) FROM student_program_enrollments e WHERE e.program_id = p.programID) AS enrollee_count
    FROM programs p
    WHERE p.teacherID = (SELECT teacherID FROM teacher WHERE userID = ?)
    ");
    $progQ->bind_param("i", $teacherId);
    $progQ->execute();
    $res = $progQ->get_result();
    $programs = [];
    $total_enrollees = 0;
    while ($row = $res->fetch_assoc()) {
    $row['enrollee_count'] = intval($row['enrollee_count']);
    $total_enrollees += $row['enrollee_count'];
    $programs[] = $row;
    }
    $progQ->close();

    echo json_encode([
    'success' => true,
    'teacher' => $teacher,
    'programs' => $programs,
    'total_programs' => count($programs),
    'total_enrollees' => $total_enrollees
    ]);
?>