document.addEventListener("DOMContentLoaded", function () {
    var swiper = new Swiper('.swiper-container', {
        slidesPerView: 1.5,
        spaceBetween: 10,
        speed: 10000,
        centeredSlides: true,
        direction: 'horizontal',
        allowTouchMove: false,
        loop: true,
        autoplay: {
            delay: 0,
            disableOnInteraction: false,
            reverseDirection: false,
        },
        grabCursor: false,
    });
});