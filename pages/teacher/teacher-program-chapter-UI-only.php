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
            <h1 class="section-title">Chapter Details</h1>
            <form action="" method="post" class="section-card-inputs">
                <div class="flex flex-col gap-[25px] w-full h-fit">
                    <!-- Adding of story or quiz (Story maximum of 3) (Quiz maximum of 1) -->
                    <div class="w-full flex items-center justify-center">
                        <!-- (place function here for adding story and quiz)... see Framer for inactive style for these buttons (teacher_view/program chapter)  -->
                        <div class="w-full flex gap-[25px] items-center justify-center">
                            <div class="size-fit flex flex-col gap-[10px] items-center justify-center">
                                <p>Story Left: <span>3</span></p>
                                <button type="button" class="group btn-red">
                                    <i class="ph ph-shapes text-[24px] group-hover:hidden"></i>
                                    <i class="ph-duotone ph-shapes text-[24px] hidden group-hover:block"></i>
                                    <p class="font-medium">Add Story</p>
                                </button>
                            </div>
                            <div class="size-fit flex flex-col gap-[10px] items-center justify-center">
                                <p>Quiz Left: <span>1</span></p>
                                <button type="button" class="group btn-blue">
                                    <i class="ph ph-books text-[24px] group-hover:hidden"></i>
                                    <i class="ph-duotone ph-books text-[24px] hidden group-hover:block"></i>
                                    <p class="font-medium">Add Quiz</p>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="w-full flex flex-col items-start justify-center gap-[10px]">
                        <!-- Stories and Quiz -->

                        <!-- This is the expected outputs after adding a chapter -->
                        <!-- Story -->
                        <div class="w-full flex gap-[10px] items-center justify-center">
                            <div class="group w-full flex items-center gap-[15px] p-[15px] border border-primary hover:bg-company_black hover:text-company_white transition duration-300 ease-in-out">
                                <i class="ph ph-shapes text-[24px] group-hover:hidden"></i>
                                <i class="ph-duotone ph-shapes text-[24px] hidden group-hover:block"></i>
                                <p class="font-medium"><span>Chapter Name</span>: Sample Story Name</p>
                            </div>
                            <button type="button" class="group text-company_red size-fit">
                                <i class="ph ph-trash text-[24px] group-hover:hidden"></i>
                                <i class="ph-duotone ph-trash text-[24px] hidden group-hover:block"></i>
                            </button>
                        </div>
                        <!-- Quiz -->
                        <div class="w-full flex gap-[10px] items-center justify-center">
                            <div class="group w-full flex items-center gap-[15px] p-[15px] border border-primary hover:bg-company_black hover:text-company_white transition duration-300 ease-in-out">
                                <i class="ph ph-books text-[24px] group-hover:hidden"></i>
                                <i class="ph-duotone ph-books text-[24px] hidden group-hover:block"></i>
                                <p class="font-medium"><span>Chapter Name</span>: Quiz</p>
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