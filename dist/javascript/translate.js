console.log('=== Translation system loading ===');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    const langButton = document.getElementById('lang-button');
    const langDropdown = document.getElementById('lang-dropdown');
    const selectedLang = document.getElementById('selected-lang');
    const langOptions = document.querySelectorAll('#lang-dropdown [data-lang]');
    
    console.log('Elements found:');
    console.log('- Button:', langButton ? 'YES' : 'NO');
    console.log('- Dropdown:', langDropdown ? 'YES' : 'NO');
    console.log('- Selected:', selectedLang ? 'YES' : 'NO');
    console.log('- Options:', langOptions.length);
    
    if (!langButton || !langDropdown || !selectedLang) {
        console.error('❌ Missing required elements!');
        return;
    }
    
    if (langOptions.length === 0) {
        console.error('❌ No language options found!');
        return;
    }
    
    console.log('✅ All elements found');
    
    // Toggle dropdown
    langButton.addEventListener('click', function(e) {
        e.stopPropagation();
        console.log('Button clicked');
        langDropdown.classList.toggle('hidden');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!langButton.contains(e.target) && !langDropdown.contains(e.target)) {
            langDropdown.classList.add('hidden');
        }
    });
    
    // Handle language selection
    langOptions.forEach((option, index) => {
        console.log(`Setting up option ${index}:`, option.getAttribute('data-lang'));
        
        option.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const langCode = this.getAttribute('data-lang');
            const langLabel = this.getAttribute('data-label');
            const langName = this.textContent.trim();
            
            console.log('===== LANGUAGE CLICKED =====');
            console.log('Code:', langCode);
            console.log('Label:', langLabel);
            console.log('Name:', langName);
            
            // Update displayed language
            selectedLang.textContent = langLabel;
            console.log('Display updated to:', langLabel);
            
            // Close dropdown
            langDropdown.classList.add('hidden');
            
            // Save preference
            localStorage.setItem('preferredLanguage', langCode);
            localStorage.setItem('preferredLanguageLabel', langLabel);
            localStorage.setItem('preferredLanguageName', langName);
            console.log('Preference saved');
            
            // Handle RTL
            handleRTL(langCode);
            
            // Show translation instructions
            console.log('Showing instructions...');
            if (langCode === 'en') {
                showEnglishMessage();
            } else {
                showTranslationInstructions(langName, langCode);
            }
        });
    });
    
    console.log('✅ Event listeners attached');
    
    // Check for saved language
    const savedLang = localStorage.getItem('preferredLanguage');
    const savedLabel = localStorage.getItem('preferredLanguageLabel');
    
    if (savedLang && savedLabel) {
        console.log('Restoring saved language:', savedLang);
        selectedLang.textContent = savedLabel;
        handleRTL(savedLang);
    }
});

// Show translation instructions
function showTranslationInstructions(languageName, langCode) {
    console.log('showTranslationInstructions called for:', languageName);
    
    const browserName = getBrowserName();
    console.log('Browser detected:', browserName);
    
    let message = `To translate this page to ${languageName}:\n\n`;
    
    if (browserName === 'Chrome' || browserName === 'Edge') {
        message += '1. Look for the translate icon in your address bar\n';
        message += '2. Click it and select "' + languageName + '"\n';
        message += '3. Or right-click anywhere → "Translate to ' + languageName + '"';
    } else if (browserName === 'Firefox') {
        message += '1. Right-click anywhere on the page\n';
        message += '2. Select "Translate Page"\n';
        message += '3. Choose "' + languageName + '"';
    } else if (browserName === 'Safari') {
        message += '1. Click the AA icon in the address bar\n';
        message += '2. Select "Translate to ' + languageName + '"';
    } else {
        message += 'Use your browser\'s built-in translation feature.\n';
        message += 'Look for a translate icon in the address bar or right-click menu.';
    }
    
    // Check if SweetAlert is available
    if (typeof Swal !== 'undefined') {
        console.log('Using SweetAlert');
        Swal.fire({
            icon: 'info',
            title: 'Translate to ' + languageName,
            text: message,
            confirmButtonText: 'Got it!',
            width: 500
        });
    } else {
        console.log('SweetAlert not found, using alert');
        alert('TRANSLATE TO ' + languageName.toUpperCase() + '\n\n' + message);
    }
}

// Show English message
function showEnglishMessage() {
    console.log('showEnglishMessage called');
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'English Selected',
            text: 'Page is already in English',
            timer: 2000,
            showConfirmButton: false
        });
    } else {
        alert('English Selected\n\nThe page is already displayed in English.');
    }
}

// Handle RTL
function handleRTL(langCode) {
    console.log('handleRTL called for:', langCode);
    const rtlLanguages = ['ar', 'ur'];
    
    if (rtlLanguages.includes(langCode)) {
        console.log('Setting RTL mode');
        document.documentElement.setAttribute('dir', 'rtl');
        document.body.classList.add('rtl');
    } else {
        console.log('Setting LTR mode');
        document.documentElement.setAttribute('dir', 'ltr');
        document.body.classList.remove('rtl');
    }
}

// Detect browser
function getBrowserName() {
    const userAgent = navigator.userAgent;
    
    if (userAgent.indexOf('Edg') > -1) {
        return 'Edge';
    } else if (userAgent.indexOf('Chrome') > -1) {
        return 'Chrome';
    } else if (userAgent.indexOf('Safari') > -1) {
        return 'Safari';
    } else if (userAgent.indexOf('Firefox') > -1) {
        return 'Firefox';
    }
    
    return 'Unknown';
}

console.log('✅ Translation script loaded successfully');
console.log('Browser:', getBrowserName());
