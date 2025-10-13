<!-- Quick Access Toolbar Component -->
<div class="quick-access-card">
    <a href="teacher-programs.php?action=create" class="group btn-blue">
        <i class="ph ph-plus-square text-[24px] group-hover:hidden"></i>
        <i class="ph-duotone ph-plus-square text-[24px] hidden group-hover:block"></i>
        <p class="font-medium">New Program</p>
    </a>
    <button type="button" class="group btn-green" onclick="openPublishModal()">
        <i class="ph ph-box-arrow-up text-[24px] group-hover:hidden"></i>
        <i class="ph-duotone ph-box-arrow-up text-[24px] hidden group-hover:block"></i>
        <p class="font-medium">Publish</p>
    </button>
    <button type="button" class="group btn-orange" onclick="showUpdateOptions()">
        <i class="ph ph-warning-octagon text-[24px] group-hover:hidden"></i>
        <i class="ph-duotone ph-warning-octagon text-[24px] hidden group-hover:block"></i>
        <p class="font-medium">Update</p>
    </button>
</div>

<!-- Publish Modal -->
<div id="publishModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ph ph-box-arrow-up text-green-600 text-[20px]"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Submit Programs for Publishing
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Select the draft programs you want to submit for admin approval.
                            </p>
                        </div>
                        <div class="mt-4 max-h-60 overflow-y-auto">
                            <div id="publishProgramsList" class="space-y-2">
                                <!-- Programs will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="submitForPublishing()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Submit for Review
                </button>
                <button type="button" onclick="closePublishModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>