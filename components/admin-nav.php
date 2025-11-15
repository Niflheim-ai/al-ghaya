<?php
// At the top of each dashboard page
include('../../php/dbConnection.php');

// Get user information for profile display
if (isset($_SESSION['userID'])) {
    $userQuery = $conn->prepare("SELECT fname, lname, email FROM user WHERE userID = ?");
    $userQuery->bind_param("i", $_SESSION['userID']);
    $userQuery->execute();
    $userInfo = $userQuery->get_result()->fetch_assoc();
    $userInitials = strtoupper(substr($userInfo['fname'], 0, 1) . substr($userInfo['lname'], 0, 1));
    $userName = $userInfo['fname'] . ' ' . $userInfo['lname'];
}
?>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Navbar -->
<nav class="bg-secondary flex flex-grow items-center justify-center sticky top-0 z-50">
    <div class="px-[50px] py-[20px] sm:px-6 lg:px-8 max-w-[1200px] w-full">
        <div class="relative gap-[25px] flex flex-1 items-center w-full">

            <!-- Logo -->
            <div class="flex shrink-0">
                <a href="admin-dashboard.php">
                    <img class="w-[50px] h-auto" src="../../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                </a>
            </div>

            <!-- Navigation buttons -->
            <div class="hidden lg:flex justify-start gap-[20px] w-full">
                <a href="admin-dashboard.php" class="<?php if ($current_page == 'admin-home' || $current_page == 'admin-dashboard') {
                    echo 'group menu-item-active-admin flex items-center';
                } else {
                    echo 'group menu-item-inactive-admin flex items-center';
                } ?>">
                    <i class="ph ph-address-book-tabs text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-address-book-tabs text-[24px] hidden group-hover:block"></i>
                    <p>Dashboard</p>
                </a>

                <a href="admin-students.php" class="<?php if ($current_page == 'admin-students') {
                    echo 'group menu-item-active-admin flex items-center';
                } else {
                    echo 'group menu-item-inactive-admin flex items-center';
                } ?>">
                    <i class="ph ph-student text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-student text-[24px] hidden group-hover:block"></i>
                    <p>Students</p>
                </a>

                <a href="admin-faculty.php" class="<?php if ($current_page == 'admin-faculty') {
                    echo 'group menu-item-active-admin flex items-center';
                } else {
                    echo 'group menu-item-inactive-admin flex items-center';
                } ?>">
                    <i class="ph ph-chalkboard-simple text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-chalkboard-simple text-[24px] hidden group-hover:block"></i>
                    <p>Faculty</p>
                </a>

                <a href="admin-analytics.php" class="<?php if ($current_page == 'admin-analytics') {
                    echo 'group menu-item-active-admin flex items-center';
                } else {
                    echo 'group menu-item-inactive-admin flex items-center';
                } ?>">
                    <i class="ph ph-presentation-chart text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-presentation-chart text-[24px] hidden group-hover:block"></i>
                    <p>Analytics</p>
                </a>
            </div>

            <!-- Profile Dropdown -->
            <div class="flex items-center relative">
                <div class="relative">
                    <button id="admin-profile-button" onclick="toggleProfileDropdown('admin')" 
                        class="flex items-center focus:outline-none hover:cursor-pointer group">
                        <div class="w-12 h-12 rounded-full bg-red-600 flex items-center justify-center text-white font-bold text-lg border-2 border-company_white hover:bg-red-700 transition-all duration-200">
                            <?= $userInitials ?>
                        </div>
                        <i class="ph ph-caret-down text-white text-[16px] ml-2 group-hover:text-gray-200 transition-colors duration-200"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="admin-profile-dropdown" 
                        class="absolute right-0 top-full mt-2 w-56 bg-white rounded-lg shadow-lg py-2 border border-gray-200 hidden z-50 transform opacity-0 scale-95 transition-all duration-200 origin-top-right">
                        <!-- User Info Section -->
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-red-600 flex items-center justify-center text-white font-bold">
                                    <?= $userInitials ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($userName) ?></p>
                                    <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($userInfo['email']) ?></p>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-1">Administrator</span>
                                </div>
                            </div>
                        </div>

                        <!-- Menu Items -->
                        <div class="py-1">
                            <a href="admin-profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                                <i class="ph ph-user-circle text-[18px] mr-3 text-gray-400"></i>
                                Profile Settings
                            </a>
                            <a href="admin-dashboard.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                                <i class="ph ph-gear text-[18px] mr-3 text-gray-400"></i>
                                Account Settings
                            </a>
                            <a href="admin-help.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                                <i class="ph ph-question text-[18px] mr-3 text-gray-400"></i>
                                Help & Support
                            </a>
                        </div>

                        <!-- Logout Section -->
                        <div class="border-t border-gray-100 py-1">
                            <button onclick="confirmSignOut()" class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors duration-150 text-left">
                                <i class="ph ph-sign-out text-[18px] mr-3 text-red-500"></i>
                                Sign Out
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu (Initially Hidden) -->
        <div id="mobile-menu" class="lg:hidden">
            <div class="px-2 pt-2 pb-3 space-y-2 bg-secondary">
                <a href="admin-dashboard.php" class="<?php if ($current_page == 'admin-dashboard') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium bg-gray-700 text-white';
                } else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-white hover:bg-gray-700';
                } ?>">Dashboard</a>

                <a href="admin-students.php" class="<?php if ($current_page == 'admin-students') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium bg-gray-700 text-white';
                } else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-white hover:bg-gray-700';
                } ?>">Students</a>

                <a href="admin-faculty.php" class="<?php if ($current_page == 'admin-faculty') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium bg-gray-700 text-white';
                } else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-white hover:bg-gray-700';
                } ?>">Faculty</a>

                <a href="admin-analytics.php" class="<?php if ($current_page == 'admin-analytics') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium bg-gray-700 text-white';
                } else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-white hover:bg-gray-700';
                } ?>">Analytics</a>

                <div class="border-t border-gray-500 w-full my-2"></div>
                <a href="admin-profile.php" class="block w-full text-center rounded-md px-3 py-2 text-md font-medium text-white hover:bg-gray-700">Profile</a>
                <button onclick="confirmSignOut()" class="block w-full text-center rounded-md bg-red-600 px-3 py-2 text-base font-medium text-white hover:bg-red-700">Sign Out</button>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleProfileDropdown(userType) {
    const dropdown = document.getElementById(`${userType}-profile-dropdown`);
    const button = document.getElementById(`${userType}-profile-button`);
    
    if (dropdown.classList.contains('hidden')) {
        dropdown.classList.remove('hidden', 'opacity-0', 'scale-95');
        dropdown.classList.add('opacity-100', 'scale-100');
    } else {
        dropdown.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            dropdown.classList.add('hidden');
        }, 200);
    }
}

// SweetAlert confirmation for sign out
function confirmSignOut() {
    Swal.fire({
        title: 'Sign Out Confirmation',
        text: 'Are you sure you want to sign out of your admin account?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Sign Out',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
            popup: 'swal2-popup-custom',
            title: 'swal2-title-custom',
            content: 'swal2-content-custom'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Signing Out...',
                text: 'Please wait while we sign you out securely.',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Redirect to logout after a brief delay
            setTimeout(() => {
                window.location.href = '../logout.php';
            }, 1000);
        }
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const adminButton = document.getElementById('admin-profile-button');
    const adminDropdown = document.getElementById('admin-profile-dropdown');
    
    if (adminButton && adminDropdown && !adminButton.contains(event.target) && !adminDropdown.contains(event.target)) {
        adminDropdown.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            adminDropdown.classList.add('hidden');
        }, 200);
    }
});
</script>

<style>
.swal2-popup-custom {
    border-radius: 12px !important;
    padding: 2rem !important;
}

.swal2-title-custom {
    font-size: 1.5rem !important;
    font-weight: 600 !important;
    color: #1f2937 !important;
}

.swal2-content-custom {
    font-size: 1rem !important;
    color: #6b7280 !important;
}
</style>