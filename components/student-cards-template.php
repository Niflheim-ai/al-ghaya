<?php
require '../../php/dbConnection.php';
require_once '../../php/functions.php';

// Determine which programs to fetch based on the active tab and filters
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'my';
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$programs = [];

if ($activeTab === 'my') {
    $programs = fetchEnrolledPrograms($conn, $_SESSION['userID'], $difficulty, $status, $search);
} else {
    $programs = fetchPublishedPrograms($conn, $_SESSION['userID'], $difficulty, $status, $search);
}
?>

<?php if (empty($programs)): ?>
    <div class="w-full text-center py-8">
        <p class="text-gray-500">
            <?php
            if ($activeTab === 'my') {
                echo "No enrolled programs found.";
            } else {
                echo "No available programs found.";
            }
            ?>
        </p>
    </div>
<?php else: ?>
    <?php foreach ($programs as $program): ?>
        <a href="student-program-view.php?program_id=<?= $program['programID'] ?>" class="block">
            <div class="min-w-[345px] min-h-[300px] rounded-[20px] w-full h-fit bg-company_white border-[1px] border-primary mb-4 hover:shadow-lg transition-shadow duration-300">
                <div class="w-full h-fit overflow-hidden rounded-[20px] flex flex-wrap">
                    <!-- Image -->
                    <img src="<?= !empty($program['image']) ? '../../uploads/program_thumbnails/'.$program['image'] : '../../images/blog-bg.svg' ?>"
                            alt="Program Image"
                            class="h-auto min-w-[221px] min-h-[170px] object-cover flex-grow flex-shrink-0 basis-1/4">
                    <!-- Content -->
                    <div class="overflow-hidden p-[30px] h-fit min-h-[300px] flex-grow flex-shrink-0 basis-3/4 flex flex-col gap-y-[25px]">
                        <!-- Chapter Checkpoint -->
                        <div class="flex flex-col gap-y-[5px] w-full h-fit">
                            <p class="font-semibold">Chapter Checkpoint</p>
                            <p>Chapter 1</p>
                        </div>
                        <!-- Program Name -->
                        <div class="flex flex-col gap-y-[10px] w-full h-fit">
                            <div class="flex flex-col gap-y-[5px] w-full h-fit">
                                <p class="arabic body-text2-semibold"><?= htmlspecialchars($program['title']) ?></p>
                                <div class="mask-b-from-20% mask-b-to-80% w-full h-[120px]">
                                    <p><?= htmlspecialchars(substr($program['description'], 0, 150)) ?>...</p>
                                </div>
                            </div>
                        </div>
                        <!-- Difficulty -->
                        <div class="proficiency-badge">
                            <i class="ph-fill ph-barbell text-[15px]"></i>
                            <p class="text-[14px]/[2em] font-semibold">
                                <?= htmlspecialchars(ucfirst(strtolower($program['category']))); ?> Difficulty
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>