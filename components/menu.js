document.addEventListener('DOMContentLoaded', () => {
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const navMenu = document.getElementById('nav-menu');
    const icon = hamburgerBtn.querySelector('i');

    // Classes for menu when open (slide down effect)
    const menuOpenClasses = ['flex', 'flex-col', 'w-full', 'max-h-screen', 'overflow-hidden', 'transition-all', 'duration-300', 'ease-in-out'];
    // Classes for menu when closed
    // const menuClosedClasses = ['max-h-0', 'hidden', 'overflow-hidden'];

    hamburgerBtn.addEventListener('click', () => {
        const isMenuOpen = !navMenu.classList.contains('hidden');

        if (!isMenuOpen) {
            // Open the menu
            navMenu.classList.add(...menuOpenClasses);
            icon.classList.remove('ph-list');
            icon.classList.add('ph-x');
            navMenu.classList.remove('hidden');
        } else {
            // Close the menu
            navMenu.classList.remove(...menuOpenClasses);
            icon.classList.remove('ph-x');
            icon.classList.add('ph-list');
            navMenu.classList.add('hidden');
        }
    });
});