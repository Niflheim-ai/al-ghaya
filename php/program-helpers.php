<?php
// Helpers for teacher pages
require_once __DIR__ . '/program-handler.php';

// Safe proxy to avoid naming conflicts
function ph_getTeacherIdFromSession($conn, $user_id) { return getTeacherIdFromSession($conn, $user_id); }

function getPublishedPrograms($conn) {
    $sql = "SELECT programID, title, description, price, category, thumbnail, status, dateCreated FROM programs WHERE status = 'published' ORDER BY dateCreated DESC LIMIT 50";
    $res = $conn->query($sql); if (!$res) return []; return $res->fetch_all(MYSQLI_ASSOC);
}

// Legacy compatibility wrappers used by components/pages
function getStory($conn, $story_id) { return story_getById($conn, (int)$story_id); }
function getChapter($conn, $chapter_id) { return chapter_getById($conn, (int)$chapter_id); }
function getChapterQuiz($conn, $chapter_id) { return chapter_getQuiz($conn, (int)$chapter_id); }
function getStoryInteractiveSections($conn, $story_id) { return story_getInteractiveSections($conn, (int)$story_id); }
function getSectionQuestions($conn, $section_id) { return section_getQuestions($conn, (int)$section_id); }
