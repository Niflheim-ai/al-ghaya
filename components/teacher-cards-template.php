<!-- Card Template -->
<!-- will be put on a loop for display -->
<div class="min-w-[345px] min-h-[300px] rounded-[20px] w-full h-fit bg-company_white border-[1px] border-primary">
    <div class="w-full h-fit overflow-hidden rounded-[20px] flex flex-wrap">
        <!-- Image -->
        <img src="../images/blog-bg.svg" alt="Program Image"
            class="h-auto min-w-[221px] min-h-[170px] object-cover flex-grow flex-shrink-0 basis-1/4">
        <!-- Content -->
        <div
            class="overflow-hidden p-[30px] h-fit min-h-[300px] flex-grow flex-shrink-0 basis-3/4 flex flex-col gap-y-[25px]">
            <div class="w-full flex items-center justify-end-safe">
                <div class="w-fit h-fit px-[15px] py-[5px] rounded-[10px] gap-x-[5px] flex items-center bg-company_orange text-company_white">
                    <i class="ph-fill ph-barricade text-[15px]"></i>
                    <p class="text-[14px]/[2em] font-semibold">Status</p>
                </div>
            </div>
            <h2 class="price-sub-header">Price</h2>
            <div class="flex flex-col gap-y-[10px] w-full h-fit">
                <div class="flex flex-col gap-y-[5px] w-full h-fit">
                    <p class="arabic body-text2-semibold">Program Name</p>
                    <div class="mask-b-from-20% mask-b-to-80% w-full h-[120px]">
                        <p>Program Description</p>
                    </div>
                </div>
                <div class="flex flex-col gap-y-[5px] w-full h-fit">
                    <p class="font-semibold">Enrollees</p>
                    <div class="flex gap-x-[10px]">
                        <p>Number</p>
                        <p>Enrollees</p>
                    </div>
                </div>
            </div>
            <div
                class="proficiency-badge">
                <i class="ph-fill ph-barbell text-[15px]"></i>
                <p class="text-[14px]/[2em] font-semibold">Difficulty</p>
            </div>
        </div>
    </div>
</div>