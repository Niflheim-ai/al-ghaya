<?php
session_start();
require '../../php/dbConnection.php';
require '../../php/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

// Check if program_id is set
$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : null;
if (!$program_id) {
    header("Location: student-programs.php");
    exit();
}

// Fetch the program
$program = fetchProgram($conn, $program_id);
if (!$program) {
    header("Location: student-programs.php?tab=all");
    exit();
}

$current_page = "student-programs";

// Fetch chapters for the program
$chapters = fetchChapters($conn, $program_id);
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>

<div class="page-container">
    <div class="page-content">
        <section class="content-section">
            <div class="flex justify-between items-center mb-6">
                <h1 class="section-title"><?= htmlspecialchars($program['title']) ?></h1>
                <a href="student-programs.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Programs</span>
                </a>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex flex-col gap-6">
                    <div>
                        <h2 class="text-xl font-bold mb-2">Description</h2>
                        <p><?= nl2br(htmlspecialchars($program['description'])) ?></p>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold mb-2">Chapters</h2>
                        <?php if (empty($chapters)): ?>
                            <p class="text-gray-500">No chapters available for this program.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($chapters as $chapter): ?>
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <h3 class="font-semibold mb-1"><?= htmlspecialchars($chapter['title']) ?></h3>
                                        <div class="text-sm text-gray-600">
                                            <?php if (!empty($chapter['content'])): ?>
                                                <p class="mb-2"><strong>Story:</strong> <?= nl2br(htmlspecialchars(substr($chapter['content'], 0, 100))) ?>...</p>
                                            <?php endif; ?>
                                            <?php if (!empty($chapter['question'])): ?>
                                                <p><strong>Question:</strong> <?= nl2br(htmlspecialchars(substr($chapter['question'], 0, 100))) ?>...</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include '../../components/footer.php'; ?>
