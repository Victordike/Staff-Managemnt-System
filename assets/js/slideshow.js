// Background Slideshow
(function() {
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slideshow-image');
    const totalSlides = slides.length;
    
    if (totalSlides === 0) return;
    
    function showNextSlide() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % totalSlides;
        slides[currentSlide].classList.add('active');
    }
    
    // Change slide every 5 seconds
    setInterval(showNextSlide, 5000);
})();
