<?php
// At the top of each dashboard page
include('../../php/dbConnection.php');

// Get user information for profile display
if (isset($_SESSION['userID'])) {
    $userQuery = $conn->prepare("SELECT fname, lname, email, level, points FROM user WHERE userID = ?");
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
<nav class="bg-company_white flex flex-grow items-center justify-center sticky top-0 z-50">
    <div class="px-[50px] py-[20px] sm:px-6 lg:px-8 max-w-[1200px] w-full">
        <div class="relative gap-[25px] flex flex-1 items-center w-full">

            <!-- Logo -->
            <div class="flex shrink-0">
                <a href="student-dashboard.php">
                    <img class="w-[50px] h-auto" src="../../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                </a>
            </div>

            <!-- Navigation buttons -->
            <div class="hidden lg:flex justify-start gap-[20px] w-full">
                <a href="student-dashboard.php" class="<?php if ($current_page == 'student-dashboard') {
                    echo 'group menu-item-active flex items-center';
                } else {
                    echo 'group menu-item-inactive flex items-center';
                } ?>">
                    <i class="ph ph-address-book-tabs text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-address-book-tabs text-[24px] hidden group-hover:block"></i>
                    <p>Dashboard</p>
                </a>

                <a href="student-programs.php" class="<?php if ($current_page == 'student-programs') {
                    echo 'group menu-item-active flex items-center';
                } else {
                    echo 'group menu-item-inactive flex items-center';
                } ?>">
                    <i class="ph ph-notebook text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-notebook text-[24px] hidden group-hover:block"></i>
                    <p>Programs</p>
                </a>

                <a href="student-transactions.php" class="<?php if ($current_page == 'student-transactions') {
                    echo 'group menu-item-active flex items-center';
                } else {
                    echo 'group menu-item-inactive flex items-center';
                } ?>">
                    <i class="ph ph-receipt text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-receipt text-[24px] hidden group-hover:block"></i>
                    <p>Transactions</p>
                </a>

                <a href="leaderboard.php" class="<?php if ($current_page == 'leaderboard') {
                    echo 'group menu-item-active flex items-center';
                } else {
                    echo 'group menu-item-inactive flex items-center';
                } ?>">
                    <i class="ph ph-ranking text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-ranking text-[24px] hidden group-hover:block"></i>
                    <p>Leaderboards</p>
                </a>
            </div>

            <!-- Hidden Google Translate Element (MUST be present) -->
            <div id="google_translate_element" style="display:none;"></div>

            <!-- Profile Dropdown -->
            <div class="flex items-center">
                <!-- Change Language Dropdown -->
                <div class="hidden lg:flex items-center relative z-40 mr-4">
                    <button id="lang-button"
                        class="flex items-center gap-1 px-3 py-2 rounded-lg hover:bg-gray-200 hover:cursor-pointer">
                        <i class="ph-light ph-globe text-secondary text-[24px]"></i>
                        <span id="selected-lang" class="text-secondary font-medium">EN</span>
                        <i class="ph ph-caret-down text-secondary text-[14px]"></i>
                    </button>

                    <div id="lang-dropdown"
                        class="absolute right-0 top-full mt-2 w-40 bg-white rounded-md shadow-lg py-2 hidden border border-gray-200">
                        <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="en" data-label="EN">English</a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="fil" data-label="FIL">Filipino</a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="ar" data-label="AR">العربية</a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="ur" data-label="UR">اردو</a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="id" data-label="ID">Indonesia</a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="ms" data-label="MS">Melayu</a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="tr" data-label="TR">Türkçe</a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="fr" data-label="FR">Français</a>
                    </div>
                </div>

                <!-- Profile Section -->
                <div class="relative">
                    <button id="student-profile-button" onclick="toggleProfileDropdown('student')" 
                        class="flex items-center focus:outline-none hover:cursor-pointer group">
                        <div class="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-lg border-2 border-secondary hover:bg-blue-700 transition-all duration-200">
                            <?= $userInitials ?>
                        </div>
                        <i class="ph ph-caret-down text-secondary text-[16px] ml-2 group-hover:text-gray-600 transition-colors duration-200"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="student-profile-dropdown" 
                        class="absolute right-0 top-full mt-2 w-64 bg-white rounded-lg shadow-lg py-2 border border-gray-200 hidden z-50 transform opacity-0 scale-95 transition-all duration-200 origin-top-right">
                        <!-- User Info Section -->
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                                    <?= $userInitials ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($userName) ?></p>
                                    <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($userInfo['email']) ?></p>
                                    <div class="flex items-center mt-1 space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Level <?= $userInfo['level'] ?></span>
                                        <span class="text-xs text-gray-500"><?= number_format($userInfo['points']) ?> pts</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Menu Items -->
                        <div class="py-1">
                            <a href="student-profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                                <i class="ph ph-user-circle text-[18px] mr-3 text-gray-400"></i>
                                Profile Settings
                            </a>
                            <a href="student-achievements.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150">
                                <i class="ph ph-trophy text-[18px] mr-3 text-gray-400"></i>
                                Achievements
                            </a>
                        </div>

                        <!-- Logout Section -->
                        <div class="border-t border-gray-100 py-1">
                            <button onclick="confirmStudentSignOut()" class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors duration-150 text-left">
                                <i class="ph ph-sign-out text-[18px] mr-3 text-red-500"></i>
                                Sign Out
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="lg:hidden hidden">
            <div class="px-2 pt-2 pb-3 space-y-2 bg-company_white border-t border-gray-200">
                <a href="student-dashboard.php" class="<?php if ($current_page == 'student-dashboard') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                } else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                } ?>">Dashboard</a>

                <a href="student-programs.php" class="<?php if ($current_page == 'student-programs') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                } else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                } ?>">Programs</a>

                <a href="student-transactions.php" class="<?php if ($current_page == 'student-transactions') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                } else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                } ?>">Transactions</a>
                
                <div class="border-t border-gray-700 w-full my-2"></div>
                <a href="student-profile.php" class="block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white">Profile</a>
                <button onclick="confirmStudentSignOut()" class="block w-full text-center rounded-md bg-red-600 px-3 py-2 text-base font-medium text-white hover:bg-red-700">Sign Out</button>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleProfileDropdown(userType) {
    const dropdown = document.getElementById(`${userType}-profile-dropdown`);
    
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

// SweetAlert confirmation for student sign out
function confirmStudentSignOut() {
    Swal.fire({
        title: 'End Learning Session?',
        text: 'Are you sure you want to sign out and end your learning session?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Sign Out',
        cancelButtonText: 'Stay Learning',
        reverseButtons: true,
        customClass: {
            popup: 'swal2-popup-custom',
            title: 'swal2-title-custom',
            content: 'swal2-content-custom'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show motivational message for students
            Swal.fire({
                title: 'Great Progress Today!',
                html: 'Keep up the excellent work on your Arabic learning journey!<br><br><div class="text-sm text-gray-600">Signing out securely...</div>',
                icon: 'success',
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                timer: 2500,
                didOpen: () => {
                    setTimeout(() => {
                        Swal.showLoading();
                    }, 1500);
                }
            });
            
            // Redirect to logout after the motivational message
            setTimeout(() => {
                window.location.href = '../logout.php';
            }, 2500);
        }
    });
}

// Language dropdown toggle
document.getElementById('lang-button').addEventListener('click', function() {
    const dropdown = document.getElementById('lang-dropdown');
    dropdown.classList.toggle('hidden');
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    // Student profile dropdown
    const studentButton = document.getElementById('student-profile-button');
    const studentDropdown = document.getElementById('student-profile-dropdown');
    
    if (studentButton && studentDropdown && !studentButton.contains(event.target) && !studentDropdown.contains(event.target)) {
        studentDropdown.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            studentDropdown.classList.add('hidden');
        }, 200);
    }
    
});
</script>
<!-- <script src="../../dist/javascript/translate.js"></script> -->

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

<script>
    console.log('=== Translation System Loading ===');

window.googleTranslateReady = false;

document.addEventListener('DOMContentLoaded', function() {
    const langButton = document.getElementById('lang-button');
    const langDropdown = document.getElementById('lang-dropdown');
    const selectedLang = document.getElementById('selected-lang');
    const langOptions = document.querySelectorAll('#lang-dropdown [data-lang]');
    
    if (!langButton || !langDropdown || !selectedLang || langOptions.length === 0) {
        console.error('Language selector elements not found');
        return;
    }
    
    console.log('✅ Language selector ready');
    
    // Toggle dropdown
    langButton.addEventListener('click', function(e) {
        e.stopPropagation();
        langDropdown.classList.toggle('hidden');
    });
    
    // Close dropdown
    document.addEventListener('click', function(e) {
        if (!langButton.contains(e.target) && !langDropdown.contains(e.target)) {
            langDropdown.classList.add('hidden');
        }
    });
    
    // Handle language selection
    langOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const langCode = this.getAttribute('data-lang');
            const langLabel = this.getAttribute('data-label');
            const langName = this.textContent.trim();
            
            console.log('Language selected:', langCode);
            
            selectedLang.textContent = langLabel;
            langDropdown.classList.add('hidden');
            
            localStorage.setItem('preferredLanguage', langCode);
            localStorage.setItem('preferredLanguageLabel', langLabel);
            localStorage.setItem('preferredLanguageName', langName);
            
            handleRTL(langCode);
            translatePage(langCode, langName);
        });
    });
    
    // Auto-restore
    const savedLang = localStorage.getItem('preferredLanguage');
    const savedLabel = localStorage.getItem('preferredLanguageLabel');
    
    if (savedLang && savedLabel) {
        selectedLang.textContent = savedLabel;
        handleRTL(savedLang);
        
        if (savedLang !== 'en') {
            setTimeout(() => {
                translatePage(savedLang, localStorage.getItem('preferredLanguageName'), true);
            }, 3000); // Wait 3 seconds for widget
        }
    }
});

function translatePage(langCode, langName, silent = false) {
    console.log('Translate requested:', langCode);
    
    if (!silent && typeof Swal !== 'undefined') {
        Swal.fire({
            title: `Translating to ${langName}...`,
            text: 'Please wait',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });
    }
    
    // Wait up to 30 seconds for the widget
    let attempts = 0;
    const maxAttempts = 300; // 30 seconds
    
    const interval = setInterval(function() {
        const select = document.querySelector('.goog-te-combo');
        attempts++;
        
        if (select) {
            clearInterval(interval);
            console.log('✅ Widget found! Translating...');
            
            select.value = langCode === 'en' ? '' : langCode;
            select.dispatchEvent(new Event('change'));
            
            if (typeof Swal !== 'undefined') {
                setTimeout(() => Swal.close(), 1000);
            }
            
            console.log('✅ Translation triggered');
            
        } else if (attempts >= maxAttempts) {
            clearInterval(interval);
            console.error('❌ Widget never appeared after 30 seconds');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Translation Failed',
                    html: `
                        <p>Google Translate cannot load on your system.</p>
                        <p class="mt-3"><strong>Please use your browser's built-in translation:</strong></p>
                        <ul style="text-align:left; margin:10px 20px;">
                            <li>Right-click on the page</li>
                            <li>Select "Translate to ${langName}"</li>
                        </ul>
                    `,
                    confirmButtonText: 'OK'
                });
            }
        } else if (attempts % 50 === 0) {
            console.log(`Still waiting... (${attempts}/300)`);
        }
    }, 100);
}

function handleRTL(langCode) {
    const rtlLanguages = ['ar', 'ur'];
    if (rtlLanguages.includes(langCode)) {
        document.documentElement.setAttribute('dir', 'rtl');
    } else {
        document.documentElement.setAttribute('dir', 'ltr');
    }
}

console.log('✅ Translation script loaded');

</script>

<script type="text/javascript">
  // Force multiple initialization attempts
  var initAttempts = 0;
  var maxAttempts = 5;

  function googleTranslateElementInit() {
      try {
          console.log('Attempt', initAttempts + 1, 'to initialize Google Translate');
          
          var element = document.getElementById('google_translate_element');
          if (!element) {
              console.error('Container element not found!');
              return;
          }
          
          // Clear any existing content
          element.innerHTML = '';
          
          // Create the widget
          new google.translate.TranslateElement({
              pageLanguage: 'en',
              includedLanguages: 'en,fil,ar,ur,id,ms,tr,fr,es',
              layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
              autoDisplay: false,
              multilanguagePage: true
          }, 'google_translate_element');
          
          console.log('✅ Google Translate widget created');
          
          // Verify it worked
          setTimeout(function() {
              var select = document.querySelector('.goog-te-combo');
              if (select) {
                  console.log('✅ SUCCESS! Dropdown created with', select.options.length, 'languages');
                  window.googleTranslateReady = true;
              } else {
                  console.error('❌ Widget created but dropdown not found');
                  
                  // Try again
                  if (initAttempts < maxAttempts) {
                      initAttempts++;
                      setTimeout(googleTranslateElementInit, 1000);
                  }
              }
          }, 500);
          
      } catch (error) {
          console.error('Google Translate init error:', error);
          
          // Retry
          if (initAttempts < maxAttempts) {
              initAttempts++;
              setTimeout(googleTranslateElementInit, 1000);
          }
      }
  }

  // Load script with retry
  function loadGoogleTranslate() {
      var script = document.createElement('script');
      script.type = 'text/javascript';
      script.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
      script.onerror = function() {
          console.error('Failed to load Google Translate script');
      };
      script.onload = function() {
          console.log('Google Translate script loaded');
      };
      document.head.appendChild(script);
  }

  // Load when DOM is ready
  if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', loadGoogleTranslate);
  } else {
      loadGoogleTranslate();
  }
  </script>

  <style>
  /* Hide Google Translate UI */
  #google_translate_element,
  .goog-te-banner-frame,
  .skiptranslate,
  .goog-te-balloon-frame {
      display: none !important;
  }

  body {
      top: 0px !important;
  }

  .goog-text-highlight {
      background: none !important;
      box-shadow: none !important;
  }
  </style>