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