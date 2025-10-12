<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>

<div class="page-container">
    <div class="page-content">
        <!-- Teacher Program Section -->
        <section class="content-section">
            <h1 class="section-title">My Programs</h1>
            <?php include '../../components/quick-access.php'; ?>
            <div class="w-full h-fit flex flex-col bg-company_white gap-[20px] p-[20px] rounded-[40px] items-start justify-start">
                <div class="w-full flex gap-[25px] items-center justify-start">
                    <div class="flex items-center gap-[10px] p-[10px] text-company_orange">
                        <i class="ph ph-stamp text-[24px]"></i>
                        <p class="body-text2-semibold">Drafts</p>
                    </div>
                    <div class="flex items-center gap-[10px] p-[10px] text-company_green">
                        <i class="ph ph-books text-[24px]"></i>
                        <p class="body-text2-semibold">Published</p>
                    </div>
                </div>
                <!-- Teacher Program Cards here (full width version), dynamic either drafts or published version... see Framer for reference (teacher_view/programs) -->
            </div>
        </section>
        <!-- All of the Teacher's Programs aka Program Library -->
        <section class="content-section">
            <h1 class="section-title">Program Library</h1>
            <!-- All Teacher Program Cards here (shrink version)... see Framer for reference (teacher_view/programs) -->
        </section>
    </div>
</div>