// Al-Ghaya Profile Dropdown Functionality
// Shared JavaScript for all user navigation bars

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    initializeProfileDropdowns();
    initializeLanguageDropdowns();
    initializeMobileMenus();
});

function initializeProfileDropdowns() {
    // Profile dropdown toggle function
    window.toggleProfileDropdown = function(userType) {
        const dropdown = document.getElementById(`${userType}-profile-dropdown`);
        
        if (!dropdown) return;
        
        if (dropdown.classList.contains('hidden')) {
            // Show dropdown
            dropdown.classList.remove('hidden', 'opacity-0', 'scale-95');
            dropdown.classList.add('opacity-100', 'scale-100');
        } else {
            // Hide dropdown
            dropdown.classList.remove('opacity-100', 'scale-100');
            dropdown.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                dropdown.classList.add('hidden');
            }, 200);
        }
    };
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const userTypes = ['student', 'teacher', 'admin'];
        
        userTypes.forEach(userType => {
            const button = document.getElementById(`${userType}-profile-button`);
            const dropdown = document.getElementById(`${userType}-profile-dropdown`);
            
            if (button && dropdown && !button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('opacity-100', 'scale-100');
                dropdown.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    dropdown.classList.add('hidden');
                }, 200);
            }
        });
    });
    
    // Close dropdown with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const userTypes = ['student', 'teacher', 'admin'];
            userTypes.forEach(userType => {
                const dropdown = document.getElementById(`${userType}-profile-dropdown`);
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.remove('opacity-100', 'scale-100');
                    dropdown.classList.add('opacity-0', 'scale-95');
                    setTimeout(() => {
                        dropdown.classList.add('hidden');
                    }, 200);
                }
            });
        }
    });
}

function initializeLanguageDropdowns() {
    // Language dropdown toggle
    const langButton = document.getElementById('lang-button');
    const langDropdown = document.getElementById('lang-dropdown');
    
    if (langButton && langDropdown) {
        langButton.addEventListener('click', function(event) {
            event.stopPropagation();
            langDropdown.classList.toggle('hidden');
        });
        
        // Language selection
        const langOptions = langDropdown.querySelectorAll('[data-lang]');
        langOptions.forEach(option => {
            option.addEventListener('click', function() {
                const selectedLang = this.dataset.lang;
                const langText = this.textContent;
                
                // Update button text
                document.getElementById('selected-lang').textContent = selectedLang.toUpperCase();
                
                // Close dropdown
                langDropdown.classList.add('hidden');
                
                // Here you can add language switching logic
                console.log('Language changed to:', selectedLang);
            });
        });
    }
}

function initializeMobileMenus() {
    // Mobile menu toggle
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
}

// Utility function for showing notifications
function showNotification(type, title, message, duration = 3000) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 ${getNotificationClasses(type)}`;
    
    notification.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0">
                ${getNotificationIcon(type)}
            </div>
            <div class="ml-3 w-0 flex-1">
                <p class="text-sm font-medium">${title}</p>
                <p class="text-sm mt-1">${message}</p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                    class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

function getNotificationClasses(type) {
    switch (type) {
        case 'success':
            return 'bg-green-50 border border-green-200 text-green-800';
        case 'error':
            return 'bg-red-50 border border-red-200 text-red-800';
        case 'warning':
            return 'bg-yellow-50 border border-yellow-200 text-yellow-800';
        case 'info':
            return 'bg-blue-50 border border-blue-200 text-blue-800';
        default:
            return 'bg-gray-50 border border-gray-200 text-gray-800';
    }
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success':
            return '<svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        case 'error':
            return '<svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>';
        case 'warning':
            return '<svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        case 'info':
            return '<svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        default:
            return '<svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    }
}
