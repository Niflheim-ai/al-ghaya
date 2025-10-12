document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("menu-toggle");
    const mobileMenu = document.getElementById("mobile-menu");
    const langBtnMobile = document.getElementById("lang-btn-mobile");
    const langMenuMobile = document.getElementById("lang-menu-mobile");

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
});