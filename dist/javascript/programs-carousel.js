// Swiper Configuration
var swiper = new Swiper(".swiper", {
    centeredSlides: true,
    initialSlide: 1,
    speed: 900,
    grabCursor: true,
    effect: "coverflow",
    coverflowEffect: {
        rotate: -15,
        stretch: -45,
        depth: 90,
        modifier: 1,
        slideShadows: true,
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    },
    navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
    },
    breakpoints: {
        0: {
            slidesPerView: 1,
            spaceBetween: 1,
        },
        600: {
            slidesPerView: 3,
            spaceBetween: 10,
        },
        1200: {
            slidesPerView: 3,
            spaceBetween: 15,
        },
    }
});

// Flip Effect on Button Click (Toggle between Front and Back)
document.querySelectorAll(".flip-button").forEach(button => {
    button.addEventListener("click", function (event) {
        event.stopPropagation();
        let cardInner = this.closest(".card").querySelector(".card-inner");
        cardInner.classList.toggle("rotate-y-180");
    });
});

// Allow Clicking Anywhere on the Back to Flip Back
document.querySelectorAll(".back").forEach(back => {
    back.addEventListener("click", function () {
        let cardInner = this.closest(".card").querySelector(".card-inner");
        cardInner.classList.toggle("rotate-y-180");
    });
});