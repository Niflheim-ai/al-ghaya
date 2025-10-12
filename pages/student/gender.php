<!-- Welcome Regards | Gender Picking -->
<?php
$page_title = "My Gender";
?>

<?php include '../../components/header.php'; ?>
<!-- Main Background -->
<section class="w-[100dvw] h-[100dvh] bg-primary flex items-center justify-center">
    <!-- Main Content -->
    <div class="w-full h-fit bg-company_white max-w-[1000px] flex flex-col items-center justify-center rounded-[30px] shadow-xl">
        <!-- Back BTN -->
        <div class="w-full h-fit flex items-start pt-[50px] pl-[50px]">
            <div class="size-fit group flex items-center gap-x-[10px] text-tertiary cursor-pointer">
                <i class="ph ph-arrow-circle-left text-[24px] group-hover:hidden"></i>
                <i class="ph-duotone ph-arrow-circle-left text-[24px] hidden group-hover:block"></i>
                <p class="font-medium">Back</p>
            </div>
        </div>
        <!-- Greetings -->
        <div class="w-full h-fit flex flex-col items-center justify-center gap-[10px]">
            <h1 class="arabic program-name-2">السلام عليكم</h1>
            <p>Assalamu Alaykum</p>
        </div>
        <!-- Gender Selection -->
        <div class="w-full h-fit flex items-center p-[50px]">
            <!-- Woman -->
            <div class="w-full h-fit flex flex-col items-center gap-[20px]">
                <!-- Image -->
                <div class="size-fit p-[20px] bg-primary rounded-[20px] border-[1px] border-company_black cursor-pointer hover:scale-[1.05] duration-300 ease-in-out">
                    <img src="../../images/dashboard-profile-female.svg" alt="Female Student" class="w-[252px] h-[274px]">
                </div>
                <!-- Details -->
                <div class="flex flex-col items-center gap-[10px]">
                    <p class="arabic program-name-2">طالبة</p>
                    <p>Female Student</p>
                </div>
            </div>
            <!-- Man -->
            <div class="w-full h-fit flex flex-col items-center gap-[20px]">
                <!-- Image -->
                <div class="size-fit p-[20px] bg-primary rounded-[20px] border-[1px] border-company_black cursor-pointer hover:scale-[1.05] duration-300 ease-in-out">
                    <img src="../../images/dashboard-profile-male.svg" alt="Male Student" class="w-[252px] h-[274px]">
                </div>
                <!-- Details -->
                <div class="flex flex-col items-center gap-[10px]">
                    <p class="arabic program-name-2">طالب</p>
                    <p>Male Student</p>
                </div>
            </div>
        </div>
    </div>
</section>
</body>
</html>