// Al-Ghaya Register Page JavaScript
// Enhanced form validation and user experience

document.addEventListener('DOMContentLoaded', function() {
    // Password strength checker
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }
    
    // Form submission handler
    const registerForm = document.querySelector('form');
    if (registerForm) {
        registerForm.addEventListener('submit', handleFormSubmission);
    }
});

function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const requirements = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        number: /\d/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };
    
    // Update visual feedback if needed
    updatePasswordStrengthIndicator(requirements);
}

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const confirmInput = document.getElementById('confirm-password');
    
    if (confirmPassword && password !== confirmPassword) {
        confirmInput.setCustomValidity('Passwords do not match');
        confirmInput.classList.add('border-red-500');
        confirmInput.classList.remove('border-gray-300');
    } else {
        confirmInput.setCustomValidity('');
        confirmInput.classList.remove('border-red-500');
        confirmInput.classList.add('border-gray-300');
    }
}

function updatePasswordStrengthIndicator(requirements) {
    // This function can be used to show password strength visually
    // Implementation depends on UI requirements
    console.log('Password requirements:', requirements);
}

function handleFormSubmission(event) {
    // Additional client-side validation can be added here
    const form = event.target;
    const formData = new FormData(form);
    
    // Check if all required fields are filled
    const requiredFields = ['first-name', 'last-name', 'email', 'password', 'confirm-password'];
    for (const field of requiredFields) {
        if (!formData.get(field) || formData.get(field).trim() === '') {
            showAlert('error', 'Validation Error', `Please fill in the ${field.replace('-', ' ')} field.`);
            event.preventDefault();
            return;
        }
    }
    
    // Check password match
    if (formData.get('password') !== formData.get('confirm-password')) {
        showAlert('error', 'Validation Error', 'Passwords do not match.');
        event.preventDefault();
        return;
    }
    
    // Check terms acceptance
    if (!document.getElementById('terms').checked) {
        showAlert('error', 'Terms Required', 'Please accept the Terms of Service and Privacy Policy.');
        event.preventDefault();
        return;
    }
}

// Toggle password visibility
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        // Update icon to "hide" state
    } else {
        input.type = 'password';
        // Update icon to "show" state
    }
}

// Email validation
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Real-time email validation
document.getElementById('email')?.addEventListener('blur', function() {
    const email = this.value.trim();
    if (email && !validateEmail(email)) {
        this.setCustomValidity('Please enter a valid email address');
        this.classList.add('border-red-500');
    } else {
        this.setCustomValidity('');
        this.classList.remove('border-red-500');
    }
});

// Show alert function (assumes SweetAlert2 is loaded)
function showAlert(icon, title, text, callback) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: icon,
            title: title,
            text: text,
            showConfirmButton: true
        }).then(() => {
            if (callback) callback();
        });
    } else {
        alert(title + ': ' + text);
        if (callback) callback();
    }
}
