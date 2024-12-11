
window.addEventListener('scroll', function () {
    const navbar = document.querySelector('.navbar');
    const heroSectionHeight = document.querySelector('.hero-section').offsetHeight; 
    if (window.scrollY > heroSectionHeight) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});
