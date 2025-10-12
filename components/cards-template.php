<?php
    // Card Template for Teachers and Students
    if (!isset($teacher_id) && isset($_SESSION['userID'])) {
        $teacher_id = $_SESSION['userID'];
    }
    // Check if the user is a teacher
    $isTeacher = (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher');
    // Teacher-specific cards
    if ($isTeacher) {
        if (!function_exists('getTeacherPrograms')) {
            require '../../php/functions.php';
        }
        $programs = getTeacherPrograms($conn, $teacher_id);

        // --- Recently Edited Section ---
        if (!empty($programs)) {
            // Sort programs by dateUpdated (most recent first)
            usort($programs, function($a, $b) {
                return strtotime($b['dateUpdated']) - strtotime($a['dateUpdated']);
            });
            // Get the most recently edited program
            $recentlyEdited = $programs[0];
            ?>
            <div class="w-full mb-6">
                <h2 class="text-xl font-bold mb-4">Recently Edited</h2>
                <div class="min-w-[345px] min-h-[300px] rounded-[20px] w-full h-fit bg-company_white border-[1px] border-primary">
                    <div class="w-full h-fit overflow-hidden rounded-[20px] flex flex-wrap">
                        <!-- Image -->
                        <img src="<?= !empty($recentlyEdited['image']) ? '../../uploads/program_thumbnails/'.$recentlyEdited['image'] : '../../images/blog-bg.svg' ?>"
                            alt="Program Image" class=" min-w-[221px] min-h-[400px] object-cover flex-grow flex-shrink-0 basis-1/4">
                        <!-- Content -->
                        <div class="overflow-hidden p-[30px] h-fit min-h-[300px] flex-grow flex-shrink-0 basis-3/4 flex flex-col gap-y-[25px]">
                            <h2 class="price-sub-header">$<?= number_format($recentlyEdited['price'], 2) ?></h2>
                            <div class="flex flex-col gap-y-[10px] w-full h-fit">
                                <div class="flex flex-col gap-y-[5px] w-full h-fit">
                                    <p class="arabic body-text2-semibold"><?= htmlspecialchars($recentlyEdited['title']) ?></p>
                                    <div class="mask-b-from-20% mask-b-to-80% w-full h-[120px]">
                                        <p><?= htmlspecialchars(substr($recentlyEdited['description'], 0, 150)) ?>...</p>
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
                                    <?= htmlspecialchars(ucfirst(strtolower($recentlyEdited['category']))); ?> Difficulty
                                </p>
                            </div>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-sm text-gray-500">
                                    Last edited: <?= date('M d, Y', strtotime($recentlyEdited['dateUpdated'])) ?>
                                </span>
                                <span class="px-2 py-1 text-xs rounded-full <?= $recentlyEdited['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= ucfirst($recentlyEdited['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 flex justify-end gap-2">
                        <a href="teacher-programs.php?action=create&program_id=<?= $recentlyEdited['programID'] ?>" class="text-blue-500 hover:text-blue-700 text-sm">
                            <i class="ph-pencil-simple text-[16px]"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
            <?php
        }
        // --- End Recently Edited Section ---

        // --- All Programs Section ---
        ?>
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
                            <img src="<?= !empty($program['image']) ? '../../uploads/program_thumbnails/'.$program['image'] : '../../images/blog-bg.svg' ?>"
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
    <?php
    }
    
    // Student-specific cards
    else {
        // Fetch recommended programs (published programs not enrolled by the student)
        if (!function_exists('fetchPublishedPrograms')) {
            require '../../php/functions.php';
        }
        $recommendedPrograms = fetchPublishedPrograms($conn, $_SESSION['userID'], 'all', 'all', '');
        ?>
        <?php if (empty($recommendedPrograms)): ?>
            <div class="w-full text-center py-8">
                <p class="text-gray-500">No recommended programs found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($recommendedPrograms as $program): ?>
                <a href="student-program-view.php?program_id=<?= $program['programID'] ?>" class="block">
                    <div class="min-w-[345px] min-h-[300px] rounded-[20px] w-full h-fit bg-company_white border-[1px] border-primary mb-4 hover:shadow-lg transition-shadow duration-300">
                        <div class="w-full h-fit overflow-hidden rounded-[20px] flex flex-wrap">
                            <!-- Image -->
                            <img src="<?= !empty($program['image']) ? '../../uploads/program_thumbnails/'.$program['image'] : '../../images/blog-bg.svg' ?>"
                                    alt="Program Image"
                                    class="h-auto min-w-[221px] min-h-[200px] object-cover flex-grow flex-shrink-0 basis-1/4">
                            <!-- Content -->
                            <div class="overflow-hidden p-[30px] h-fit min-h-[300px] flex-grow flex-shrink-0 basis-3/4 flex flex-col gap-y-[25px]">
                                <!-- Price -->
                                <h2 class="price-sub-header">₱<?= number_format($program['price'], 2) ?></h2>
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
    <?php
    }
?>
