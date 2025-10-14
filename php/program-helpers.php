<?php
/**
 * PROGRAM HELPERS - LEGACY COMPATIBILITY WRAPPER
 * This file now simply includes the centralized program-handler.php
 * All functions have been moved to program-handler.php to prevent redeclaration errors
 */

// Include the centralized program handler
require_once 'program-handler.php';

// NOTE: All program, chapter, story, and interactive section functions
// are now available through the centralized program-handler.php
// 
// Legacy function names are provided automatically for backward compatibility.
// 
// NEW FUNCTION NAMES (recommended for new code):
// - program_create($conn, $data)
// - program_update($conn, $program_id, $data)
// - program_getById($conn, $program_id, $teacher_id)
// - program_getByTeacher($conn, $teacher_id, $sortBy)
// - program_verifyOwnership($conn, $program_id, $teacher_id)
// 
// - chapter_add($conn, $program_id, $title, $content, $question)
// - chapter_update($conn, $chapter_id, $title, $content, $question)
// - chapter_delete($conn, $chapter_id)
// - chapter_getById($conn, $chapter_id)
// - chapter_getByProgram($conn, $program_id)
// - chapter_getStories($conn, $chapter_id)
// - chapter_getQuiz($conn, $chapter_id)
// 
// - story_create($conn, $data)
// - story_delete($conn, $story_id)
// - story_getById($conn, $story_id)
// - story_getInteractiveSections($conn, $story_id)
// - story_deleteInteractiveSections($conn, $story_id)
// 
// - section_create($conn, $story_id)
// - section_delete($conn, $section_id)
// - section_getQuestions($conn, $section_id)
//
// LEGACY FUNCTION NAMES (for backward compatibility):
// All old function names like getProgram(), addChapter(), etc. are still available
// but they now call the new centralized functions internally.

?>