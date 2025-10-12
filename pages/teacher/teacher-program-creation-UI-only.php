<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>

<div class="page-container">
    <div class="page-content">
        <section class="content-section">
            <!-- Back BTN -->
            <div class="w-full h-fit flex items-start">
                <div class="size-fit group flex items-center gap-x-[10px] text-tertiary cursor-pointer">
                    <i class="ph ph-arrow-circle-left text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-arrow-circle-left text-[24px] hidden group-hover:block"></i>
                    <p class="font-medium">Back</p>
                </div>
            </div>
            <h1 class="section-title">Program Details</h1>
            <form action="" method="post" class="section-card-inputs">
                <!-- Program Title -->
                <div class="flex flex-col gap-[10px] w-full h-fit">
                    <p class="body-text2-semibold">Program Title</p>
                    <input type="text" placeholder="e.i. Hadith (حديث)" class="w-full h-[40px] border border-company_black rounded-[10px] p-[12px] focus:outline-offset-2 focus:accent-tertiary">
                </div>
                <!-- Program Description -->
                <div class="flex flex-col gap-[10px] w-full h-fit">
                    <p class="body-text2-semibold">Program Description</p>
                    <textarea placeholder="e.i. Hadith are the recorded accounts of the sayings, actions, silent approvals, and physical descriptions of the Prophet Muhammad (peace be upon him). They serve as the …" class="w-full h-[100px] border border-company_black rounded-[10px] p-[12px] focus:outline-offset-2 focus:accent-tertiary"></textarea>
                </div>
                <!-- Difficulty -->
                <div class="flex flex-col gap-[10px] w-full h-fit">
                    <p class="body-text2-semibold">Difficulty</p>
                    <div class="flex gap-[25px]">
                        <!-- Student Difficulty -->
                        <label for="student" class="proficiency-badge-creation cursor-pointer bg-primary text-company_grey has-checked:bg-company_black has-checked:text-company_white">
                            <i class="ph-fill ph-barbell text-[15px]"></i>
                            <p class="text-[14px]/[2em] font-semibold">Student Difficulty</p>
                            <input type="radio" name="difficulty" id="student" value="student-difficulty" class="appearance-none">
                        </label>
                        <!-- Aspiring Difficulty -->
                        <label for="aspiring" class="proficiency-badge-creation cursor-pointer bg-primary text-company_grey has-checked:bg-secondary has-checked:text-company_white">
                            <i class="ph-fill ph-barbell text-[15px]"></i>
                            <p class="text-[14px]/[2em] font-semibold">Aspiring Difficulty</p>
                            <input type="radio" name="difficulty" id="aspiring" value="aspiring-difficulty" class="appearance-none">
                        </label>
                        <!-- Master Difficulty -->
                        <label for="master" class="proficiency-badge-creation cursor-pointer bg-primary text-company_grey has-checked:bg-tertiary has-checked:text-company_white">
                            <i class="ph-fill ph-barbell text-[15px]"></i>
                            <p class="text-[14px]/[2em] font-semibold">Master Difficulty</p>
                            <input type="radio" name="difficulty" id="master" value="master-difficulty" class="appearance-none">
                        </label>
                    </div>
                </div>
                <!-- Program Price -->
                <div class="flex flex-col gap-[10px] w-full h-fit">
                    <p class="body-text2-semibold">Program Price</p>
                    <input type="number" placeholder="e.i. $100" class="w-full h-[40px] border border-company_black rounded-[10px] p-[12px] focus:outline-offset-2 focus:accent-tertiary">
                </div>
                <!-- Overview Video -->
                <div class="flex flex-col gap-[10px] w-full h-fit">
                    <p class="body-text2-semibold">Overview Video</p>
                    <input type="text" placeholder="e.i. https/youtube.com/..." class="w-full h-[40px] border border-company_black rounded-[10px] p-[12px] focus:outline-offset-2 focus:accent-tertiary">
                </div>
                <!-- Chapters -->
                <div class="flex flex-col gap-[25px] w-full h-fit">
                    <p class="body-text2-semibold">Chapters</p>
                    <div class="w-full flex items-center justify-center">
                        <!-- (place function here for adding chapters)  -->
                        <button type="button" class="group btn-blue">
                            <i class="ph ph-book-bookmark text-[24px] group-hover:hidden"></i>
                            <i class="ph-duotone ph-book-bookmark text-[24px] hidden group-hover:block"></i>
                            <p class="font-medium">Add Chapter</p>
                        </button>
                    </div>
                    <div class="w-full flex flex-col items-start justify-center gap-[10px]">
                        <!-- Chapter Items -->

                        <!-- This is the expected output after adding a chapter -->
                        <div class="w-full flex gap-[10px] items-center justify-center">
                            <div class="w-full flex items-center gap-[15px] p-[15px] border border-primary hover:bg-company_black hover:text-company_white transition duration-300 ease-in-out">
                                <i class="ph ph-book-bookmark text-[24px] group-hover:hidden"></i>
                                <i class="ph-duotone ph-book-bookmark text-[24px] hidden group-hover:block"></i>
                                <p class="font-medium">Sample Chapter Name</p>
                            </div>
                            <button type="button" class="group text-company_red size-fit">
                                <i class="ph ph-trash text-[24px] group-hover:hidden"></i>
                                <i class="ph-duotone ph-trash text-[24px] hidden group-hover:block"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Save Button -->
                    <button type="submit" class="btn-secondary group">
                        <i class="ph ph-floppy-disk text-[24px] group-hover:hidden"></i>
                        <i class="ph-duotone ph-floppy-disk text-[24px] hidden group-hover:block"></i>
                        <span>Save</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>