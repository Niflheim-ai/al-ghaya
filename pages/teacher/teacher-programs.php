<?php
session_start();
$current_page = "teacher-programs";
$page_title = "My Programs";
// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
// Include required files
require '../../php/dbConnection.php';
require '../../php/functions.php';
// The teacherID is already in the session as userID
$teacher_id = $_SESSION['userID'];
// Get filter type if set
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
// Handle success/error messages from redirects
$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
// Clear messages after displaying
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
// Get programs based on filter
$programs = getTeacherPrograms($conn, $teacher_id);
// Filter programs if needed
if ($filter !== 'all') {
    $programs = array_filter($programs, function ($program) use ($filter) {
        return $program['status'] === $filter;
    });
}
// Check if we're creating/editing a program
$show_form = isset($_GET['action']) && $_GET['action'] === 'create';
$editing_program = isset($_GET['program_id']);
$program_id = $editing_program ? $_GET['program_id'] : null;
$program = null;
$chapters = [];
if ($editing_program) {
    $program = getProgram($conn, $program_id, $teacher_id);
    if (!$program) {
        header("Location: teacher-programs.php");
        exit();
    }
    $chapters = getChapters($conn, $program_id);
} elseif (isset($_SESSION['temp_chapters'])) {
    $chapters = $_SESSION['temp_chapters'];
}
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>
<input type="hidden" id="chapters-data" value='<?= json_encode($chapters) ?>'>
<input type="hidden" id="success-message" value="<?= htmlspecialchars($success) ?>">
<input type="hidden" id="error-message" value="<?= htmlspecialchars($error) ?>">
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="page-container">
    <div class="page-content">
        <!-- 1ST Section - Quick Access Toolbar and Programs -->
        <section class="content-section">
            <?php if (!$show_form && !$editing_program): ?>
                <h1 class="section-title md:text-2xl font-bold">My Programs</h1>
                <!-- Quick Access Toolbar (DAVID Version) -->
                <div class="quick-access-card">
                    <a href="teacher-programs.php?action=create" class="group btn-blue">
                        <i class="ph ph-plus-square text-[24px] group-hover:hidden"></i>
                        <i class="ph-duotone ph-plus-square text-[24px] hidden group-hover:block"></i>
                        <p class="font-medium">New Program</p>
                    </a>
                    <button type="button" class="group btn-green">
                        <i class="ph ph-box-arrow-up text-[24px] group-hover:hidden"></i>
                        <i class="ph-duotone ph-box-arrow-up text-[24px] hidden group-hover:block"></i>
                        <p class="font-medium">Publish</p>
                    </button>
                    <button type="button" class="group btn-orange">
                        <i class="ph ph-warning-octagon text-[24px] group-hover:hidden"></i>
                        <i class="ph-duotone ph-warning-octagon text-[24px] hidden group-hover:block"></i>
                        <p class="font-medium">Update</p>
                    </button>
                </div>

                <!-- Quick Access Toolbar for filtering (FRED Version) -->
                <!-- <div class="w-full flex gap-4 mb-6 overflow-x-auto pb-2">
                    <a href="teacher-programs.php?filter=all"
                        class="flex items-center p-3 gap-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 <?php echo ($filter === 'all') ? 'ring-2 ring-blue-500' : ''; ?>">
                        <i class="fas fa-book text-[20px]"></i>
                        <p class="program-name">All Programs</p>
                    </a>
                    <a href="teacher-programs.php?filter=draft"
                        class="flex items-center p-3 gap-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 <?php echo ($filter === 'draft') ? 'ring-2 ring-blue-500' : ''; ?>">
                        <i class="fas fa-file-alt text-[20px]"></i>
                        <p class="program-name">Drafts</p>
                    </a>
                    <a href="teacher-programs.php?filter=published"
                        class="flex items-center p-3 gap-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 <?php echo ($filter === 'published') ? 'ring-2 ring-blue-500' : ''; ?>">
                        <i class="fas fa-book-open text-[20px]"></i>
                        <p class="program-name">Published</p>
                    </a>
                    <a href="teacher-programs.php?action=create"
                        class="flex items-center p-3 gap-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:shadow-lg hover:cursor-pointer">
                        <i class="fas fa-plus-circle text-[20px]"></i>
                        <p class="program-name">Create Program</p>
                    </a>
                </div> -->


                <!-- Drafts and Publish Section -->
                <div class="w-full h-fit flex flex-col bg-company_white gap-[20px] p-[20px] rounded-[40px] items-start justify-start">
                    <div class="w-full flex gap-[25px] items-center justify-start">
                        <a href="teacher-programs.php?filter=draft" class="flex items-center gap-[10px] p-[10px] text-company_orange">
                            <i class="ph ph-stamp text-[24px]"></i>
                            <p class="body-text2-semibold">Drafts</p>
                        </a>
                        <a href="teacher-programs.php?filter=published" class="flex items-center gap-[10px] p-[10px] text-company_green">
                            <i class="ph ph-books text-[24px]"></i>
                            <p class="body-text2-semibold">Published</p>
                        </a>
                    </div>
                    <!-- Teacher Program Cards here (full width version), dynamic either drafts or published version... see Framer for reference (teacher_view/programs) -->

                    <!-- Programs Display -->
                    <?php if (empty($programs)): ?>
                        <div class="w-full text-center py-8">
                            <p class="text-gray-500">
                                <?php
                                if ($filter === 'draft') {
                                    echo "No draft programs found.";
                                } elseif ($filter === 'published') {
                                    echo "No published programs found.";
                                } else {
                                    echo "No programs found. Create your first program!";
                                }
                                ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($programs as $program): ?>
                                <div class="min-w-[345px] min-h-[300px] rounded-[20px] w-full h-fit bg-company_white border-[1px] border-primary relative">
                                    <!-- Status indicator -->
                                    <div class="absolute top-2 right-2">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $program['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                            <?php echo ucfirst($program['status']) ?>
                                        </span>
                                    </div>
                                    <div class="w-full h-fit overflow-hidden rounded-[20px] flex flex-wrap">
                                        <!-- Image with consistent sizing -->
                                        <img src="<?php echo !empty($program['image']) ? '../../uploads/program_thumbnails/' . $program['image'] : '../../images/blog-bg.svg'; ?>"
                                            alt="Program Image" class="program-thumbnail flex-grow flex-shrink-0 basis-1/4">
                                        <!-- Content -->
                                        <div class="overflow-hidden p-[30px] h-fit min-h-[300px] flex-grow flex-shrink-0 basis-3/4 flex flex-col gap-y-[25px]">
                                            <h2 class="price-sub-header">₱<?php echo number_format($program['price'], 2); ?></h2>
                                            <div class="flex flex-col gap-y-[10px] w-full h-fit">
                                                <div class="flex flex-col gap-y-[5px] w-full h-fit">
                                                    <p class="arabic body-text2-semibold"><?php echo htmlspecialchars($program['title']); ?></p>
                                                    <div class="mask-b-from-20% mask-b-to-80% w-full h-[120px]">
                                                        <p><?php echo htmlspecialchars(substr($program['description'], 0, 150)); ?>...</p>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col gap-y-[5px] w-full h-fit">
                                                    <p class="font-semibold">Enrollees</p>
                                                    <div class="flex gap-x-[10px]">
                                                        <p>0</p>
                                                        <p>Enrolled</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="proficiency-badge">
                                                <i class="ph-fill ph-barbell text-[15px]"></i>
                                                <p class="text-[14px]/[2em] font-semibold">
                                                    <?php echo htmlspecialchars(ucfirst(strtolower($program['category']))); ?> Difficulty
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 flex justify-between items-center">
                                        <span class="text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($program['dateCreated'])); ?>
                                        </span>
                                        <a href="teacher-programs.php?action=create&program_id=<?php echo $program['programID']; ?>"
                                            class="text-blue-500 hover:text-blue-700 text-sm flex items-center">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <section class="content-section">
                    <h1 class="section-title">Program Library</h1>
                    <!-- All Teacher Program Cards here (shrink version)... see Framer for reference (teacher_view/programs) -->
                    <?php if (empty($programs)): ?>
                        <div class="w-full text-center py-8">
                            <p class="text-gray-500">No programs found. Create your first program!</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($programs as $program): ?>
                                <div class="min-w-[345px] min-h-[300px] rounded-[20px] w-full h-fit bg-company_white border-[1px] border-primary">
                                    <div class="w-full h-fit overflow-hidden rounded-[20px] flex flex-wrap">
                                        <!-- Image -->
                                        <img src="<?= !empty($program['image']) ? '../../uploads/program_thumbnails/' . $program['image'] : '../../images/blog-bg.svg' ?>"
                                            alt="Program Image" class="h-auto min-w-[221px] min-h-[200px] object-cover flex-grow flex-shrink-0 basis-1/4">
                                        <!-- Content -->
                                        <div class="overflow-hidden p-[30px] h-fit min-h-[300px] flex-grow flex-shrink-0 basis-3/4 flex flex-col gap-y-[25px]">
                                            <h2 class="price-sub-header">$<?= number_format($program['price'], 2) ?></h2>
                                            <div class="flex flex-col gap-y-[10px] w-full h-fit">
                                                <div class="flex flex-col gap-y-[5px] w-full h-fit">
                                                    <p class="arabic body-text2-semibold"><?= htmlspecialchars($program['title']) ?></p>
                                                    <div class="mask-b-from-20% mask-b-to-80% w-full h-[120px]">
                                                        <p><?= htmlspecialchars(substr($program['description'], 0, 150)) ?>...</p>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col gap-y-[5px] w-full h-fit">
                                                    <p class="font-semibold">Enrollees</p>
                                                    <div class="flex gap-x-[10px]">
                                                        <p>0</p>
                                                        <p>Enrolled</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="proficiency-badge">
                                                <i class="ph-fill ph-barbell text-[15px]"></i>
                                                <p class="text-[14px]/[2em] font-semibold">
                                                    <?= htmlspecialchars(ucfirst(strtolower($program['category']))); ?> Difficulty
                                                </p>
                                            </div>
                                            <div class="flex justify-end mt-2">
                                                <span class="px-2 py-1 text-xs rounded-full <?= $program['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                    <?= ucfirst($program['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 flex justify-end gap-2">
                                        <a href="teacher-programs.php?action=create&program_id=<?= $program['programID'] ?>"
                                            class="text-blue-500 hover:text-blue-700 text-sm">
                                            <i class="ph-pencil-simple text-[16px]"></i> Edit
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php else: ?>


                <!-- Program Creation/Edit Form -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h1 class="section-title text-xl md:text-2xl font-bold">
                            <?php echo $editing_program ? 'Edit Program' : 'Create New Program'; ?>
                        </h1>
                        <a href="teacher-programs.php"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Programs</span>
                        </a>
                    </div>
                    <?php if ($success): ?>
                        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="../../php/create-program.php" enctype="multipart/form-data" class="space-y-6" onsubmit="return confirmSubmit(event)">
                        <!-- Hidden fields -->
                        <?php if ($editing_program): ?>
                            <input type="hidden" name="program_id" value="<?= $program_id ?>">
                            <input type="hidden" name="update_program" value="1">
                        <?php else: ?>
                            <input type="hidden" name="create_program" value="1">
                        <?php endif; ?>
                        <!-- Program Title -->
                        <div>
                            <label for="title" class="block text-gray-700 font-medium mb-2">Program Title</label>
                            <input type="text" id="title" name="title"
                                value="<?= ($editing_program && isset($program['title'])) ? htmlspecialchars($program['title']) : (isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '') ?>"
                                required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <!-- Program Description -->
                        <div>
                            <label for="description" class="block text-gray-700 font-medium mb-2">Program Description</label>
                            <textarea id="description" name="description" rows="4" required
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?=
                                                                                                                        ($editing_program && isset($program['description'])) ? htmlspecialchars($program['description']) : (isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '') ?></textarea>
                        </div>
                        <!-- Difficulty -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Difficulty</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="category" value="Beginner" <?= (($editing_program && isset($program['category']) && $program['category'] === 'Beginner') || (isset($_POST['category']) && $_POST['category'] === 'Beginner')) ? 'checked' : '' ?> required>
                                    <span class="px-4 py-2 bg-gray-100 rounded-lg">Student Difficulty</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="category" value="Intermediate" <?= (($editing_program && isset($program['category']) && $program['category'] === 'Intermediate') || (isset($_POST['category']) && $_POST['category'] === 'Intermediate')) ? 'checked' : '' ?>>
                                    <span class="px-4 py-2 bg-gray-100 rounded-lg">Aspiring Difficulty</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="category" value="Advanced" <?= (($editing_program && isset($program['category']) && $program['category'] === 'Advanced') || (isset($_POST['category']) && $_POST['category'] === 'Advanced')) ? 'checked' : '' ?>>
                                    <span class="px-4 py-2 bg-gray-100 rounded-lg">Master Difficulty</span>
                                </label>
                            </div>
                        </div>
                        <!-- Program Price -->
                        <div>
                            <label for="price" class="block text-gray-700 font-medium mb-2">Program Price</label>
                            <input type="number" id="price" name="price" min="0" step="0.01"
                                value="<?= ($editing_program && isset($program['price'])) ? htmlspecialchars($program['price']) : (isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '') ?>"
                                required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <!-- Overview Video -->
                        <div>
                            <label for="video_link" class="block text-gray-700 font-medium mb-2">Overview Video</label>
                            <input type="url" id="video_link" name="video_link"
                                value="<?= ($editing_program && isset($program['video_link'])) ? htmlspecialchars($program['video_link']) : (isset($_POST['video_link']) ? htmlspecialchars($_POST['video_link']) : '') ?>"
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="https://www.youtube.com/watch?v=">
                        </div>
                        <!-- Program Status -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Program Status</label>
                            <select name="status" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                                <option value="draft" <?= ($editing_program && isset($program['status']) && $program['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= ($editing_program && isset($program['status']) && $program['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                        <!-- Thumbnail Upload (only for new programs) -->
                        <?php if (!$editing_program): ?>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Program Thumbnail</label>
                                <div class="flex items-center gap-4">
                                    <img id="thumbnailPreview" src="../images/default-thumbnail.jpg" alt="Thumbnail Preview"
                                        class="chapter-thumbnail-preview border">
                                    <div>
                                        <input type="file" name="thumbnail" id="thumbnail" accept="image/*"
                                            class="hidden" onchange="previewThumbnail()">
                                        <label for="thumbnail"
                                            class="bg-blue-500 text-white px-4 py-2 rounded-lg cursor-pointer hover:bg-blue-600">
                                            Upload Image
                                        </label>
                                        <p class="text-sm text-gray-500 mt-1">Recommended: 400x400px, JPG/PNG</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <!-- Submit Buttons -->
                        <div class="flex justify-end gap-4">
                            <a href="teacher-programs.php"
                                class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                                Cancel
                            </a>
                            <?php if ($editing_program): ?>
                                <button type="button" onclick="confirmSaveAsDraft()"
                                    class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                                    Save as Draft
                                </button>
                            <?php endif; ?>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:shadow-lg hover:cursor-pointer">
                                <?= $editing_program ? 'Save Program' : 'Create Program' ?>
                            </button>
                        </div>
                    </form>
                    <!-- Chapters Section -->
                    <div class="border-t pt-6 mt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold">Chapters</h2>
                            <button type="button" onclick="toggleAddChapterForm()"
                                class="bg-blue-500 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:shadow-lg hover:cursor-pointer">
                                <i class="fas fa-plus"></i>
                                <span>Add Chapter</span>
                            </button>
                        </div>
                        <!-- Add Chapter Form -->
                        <div id="addChapterForm" class="hidden bg-gray-50 rounded-lg p-4 mb-4">
                            <form onsubmit="return addChapter(event)" class="space-y-4">
                                <input type="hidden" name="program_id" value="<?= $program_id ?>">
                                <div>
                                    <label for="chapter_title" class="block text-gray-700 font-medium mb-2">Chapter Title</label>
                                    <input type="text" id="chapter_title" name="chapter_title" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="chapter_content" class="block text-gray-700 font-medium mb-2">Story Content</label>
                                    <textarea id="chapter_content" name="chapter_content" rows="4" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                                </div>
                                <div>
                                    <label for="chapter_question" class="block text-gray-700 font-medium mb-2">Question</label>
                                    <textarea id="chapter_question" name="chapter_question" rows="3" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                                </div>
                                <div class="flex justify-end gap-4">
                                    <button type="button" onclick="toggleAddChapterForm()"
                                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 hover:shadow-lg hover:cursor-pointer">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:shadow-lg hover:cursor-pointer">
                                        Add Chapter
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!-- Edit Chapter Form -->
                        <div id="editChapterForm" class="hidden bg-gray-50 rounded-lg p-4 mb-4">
                            <form onsubmit="return updateChapter(event)" class="space-y-4">
                                <input type="hidden" name="program_id" value="<?= $program_id ?>">
                                <input type="hidden" name="chapter_id" id="edit_chapter_id">
                                <div>
                                    <label for="edit_chapter_title" class="block text-gray-700 font-medium mb-2">Chapter Title</label>
                                    <input type="text" id="edit_chapter_title" name="chapter_title" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="edit_chapter_content" class="block text-gray-700 font-medium mb-2">Story Content</label>
                                    <textarea id="edit_chapter_content" name="chapter_content" rows="4" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                                </div>
                                <div>
                                    <label for="edit_chapter_question" class="block text-gray-700 font-medium mb-2">Question</label>
                                    <textarea id="edit_chapter_question" name="chapter_question" rows="3" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                                </div>
                                <div class="flex justify-end gap-4">
                                    <button type="button" onclick="toggleEditChapterForm()"
                                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                        Update Chapter
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!-- Chapters List -->
                        <?php if (count($chapters) > 0): ?>
                            <div class="space-y-4" id="chapters-list">
                                <?php foreach ($chapters as $index => $chapter): ?>
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
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
                                            <div class="flex gap-2 ml-4">
                                                <button type="button" onclick="openEditChapterForm(<?= $editing_program ? $chapter['chapter_id'] : $index ?>)"
                                                    class="text-blue-500 hover:text-blue-700">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" onclick="confirmDeleteChapter(<?= $editing_program ? $chapter['chapter_id'] : $index ?>)"
                                                    class="text-red-500 hover:text-red-700">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500">No chapters added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<!-- Back to Top button -->
<button type="button" onclick="scrollToTop()"
    class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:cursor-pointer hover:bg-gray-700 transition z-50"
    id="scroll-to-top">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
    </svg>
</button>

<!-- Scroll to top -->
<script>
    // Scroll to top function
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Show/hide back to top button
    window.addEventListener('scroll', function() {
        const btn = document.getElementById('scroll-to-top');
        if (window.pageYOffset > 300) {
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../components/navbar.js"></script>
<script src="../../dist/javascript/teacher-programs.js"></script>
</body>

</html>