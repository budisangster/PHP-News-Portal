document.addEventListener('DOMContentLoaded', () => {
    const slider = document.querySelector('.featured-slider');
    const slides = slider.querySelectorAll('.featured-slide');
    let currentSlide = 0;

    // Create navigation dots
    const nav = document.createElement('div');
    nav.className = 'slider-nav';
    slides.forEach((_, index) => {
        const dot = document.createElement('div');
        dot.className = 'slider-nav-item';
        dot.addEventListener('click', () => goToSlide(index));
        nav.appendChild(dot);
    });
    slider.appendChild(nav);

    function goToSlide(n) {
        slides[currentSlide].classList.remove('active');
        nav.children[currentSlide].classList.remove('active');
        currentSlide = (n + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        nav.children[currentSlide].classList.add('active');
    }

    function nextSlide() {
        goToSlide(currentSlide + 1);
    }

    // Initialize the slider
    goToSlide(0);

    // Auto-advance slides every 5 seconds
    setInterval(nextSlide, 5000);
});
