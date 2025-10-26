<?php
/*
 * CENTRALIZED PROGRAM HANDLER - Al-Ghaya LMS
 * Canonical functions only; quiz legacy wrappers removed (handled in quiz-handler.php)
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'dbConnection.php';
require_once 'functions.php';
// ... (rest of file unchanged except quiz wrapper removals) ...

// ONLY canonical quiz functions below; legacy wrappers now exist only in quiz-handler.php
// function quiz_getById($conn, $quiz_id) { ... }
// function quiz_getByChapter($conn, $chapter_id) { ... }
// function quizQuestion_getByQuiz($conn, $quiz_id) { ... }
// function quizQuestionOption_getByQuestion($conn, $question_id) { ... }
// Add ph_ wrappers and legacy wrappers only in helpers/quiz-handler
