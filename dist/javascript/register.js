function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.src = 'https://img.icons8.com/?size=100&id=30M9wv1iFkcH&format=png&color=000000'; // Eye open icon
    } else {
        passwordInput.type = 'password';
        eyeIcon.src = 'https://img.icons8.com/?size=100&id=0ciqibcg6iLl&format=png&color=000000'; // Eye close icon
    }
}

function togglePassword1() {
    const confirmPasswordInput = document.getElementById('confirm-password');
    const eyeIcon1 = document.getElementById('eye-icon1');

    if (confirmPasswordInput.type === 'password') {
        confirmPasswordInput.type = 'text';
        eyeIcon1.src = 'https://img.icons8.com/?size=100&id=30M9wv1iFkcH&format=png&color=000000'; // Eye open icon
    } else {
        confirmPasswordInput.type = 'password';
        eyeIcon1.src = 'https://img.icons8.com/?size=100&id=0ciqibcg6iLl&format=png&color=000000'; // Eye close icon
    }
}