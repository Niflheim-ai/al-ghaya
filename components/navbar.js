document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("menu-toggle");
    const mobileMenu = document.getElementById("mobile-menu");
    const langBtnMobile = document.getElementById("lang-btn-mobile");
    const langMenuMobile = document.getElementById("lang-menu-mobile");

    // Only attach listeners if elements exist
    if (menuToggle && mobileMenu) {
        // Animate main mobile menu
        menuToggle.addEventListener("click", function () {
            if (mobileMenu.classList.contains("hidden")) {
                mobileMenu.classList.remove("hidden");
                setTimeout(() => {
                    mobileMenu.style.maxHeight = mobileMenu.scrollHeight + "px";
                }, 10);
            } else {
                mobileMenu.style.maxHeight = "0px";
                setTimeout(() => {
                    mobileMenu.classList.add("hidden");
                }, 900);
            }
        });
    }
    
    // Handle language button if it exists
    if (langBtnMobile && langMenuMobile) {
        langBtnMobile.addEventListener("click", function() {
            langMenuMobile.classList.toggle("hidden");
        });
    }
});