<?php
// Get the user's progress on all stories in a program
function getUserStoryProgress($conn, $studentID, $programID) {
    $stmt = $conn->prepare("SELECT cs.story_id, IF(ssp.is_completed=1,1,0) as is_completed FROM chapter_stories cs JOIN program_chapters pc ON pc.chapter_id=cs.chapter_id LEFT JOIN student_story_progress ssp ON (ssp.story_id=cs.story_id AND ssp.student_id=?) WHERE pc.programID=?");
    $stmt->bind_param("ii", $studentID, $programID);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = [];
    while($row = $result->fetch_assoc()) {
        $progress[$row['story_id']] = (int)$row['is_completed'];
    }
    $stmt->close();
    return $progress;
}