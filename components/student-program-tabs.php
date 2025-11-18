<?php
// Set the active tab: 'my' or 'all'
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'my';
?>

<div class="quick-access-card flex flex-wrap w-full">
    <a href="?tab=my">
        <button type="button" id="my-programs-btn" class="group <?php echo $activeTab === 'my' ? 'btn-blue' : 'btn-blue-inactive'; ?>">
            <i class="ph ph-book-open-text text-[24px] group-hover:hidden"></i>
            <i class="ph-duotone ph-book-open-text text-[24px] hidden group-hover:block"></i>
            <p class="font-medium">My Programs</p>
        </button>
    </a>
    <a href="?tab=all">
        <button type="button" id="all-programs-btn" class="group <?php echo $activeTab === 'all' ? 'btn-orange' : 'btn-orange-inactive'; ?>">
            <i class="ph ph-book text-[24px] group-hover:hidden"></i>
            <i class="ph-duotone ph-book text-[24px] hidden group-hover:block"></i>
            <p class="font-medium">All Programs</p>
        </button>
    </a>
</div>