document.addEventListener('DOMContentLoaded', function() {
    // Menu mobile
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileNav = document.querySelector('.mobile-nav');

    mobileMenuBtn.addEventListener('click', function() {
        mobileNav.classList.toggle('active');
        mobileMenuBtn.classList.toggle('active');
    });

    // Fermer le menu mobile quand on clique sur un lien
    const mobileNavLinks = mobileNav.querySelectorAll('a');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', function() {
            mobileNav.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
        });
    });

    // Fermer le menu mobile quand on clique en dehors
    document.addEventListener('click', function(event) {
        if (!mobileMenuBtn.contains(event.target) && !mobileNav.contains(event.target) && mobileNav.classList.contains('active')) {
            mobileNav.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
        }
    });
    



    // Slider
    const slider = document.querySelector('.slider');
    const sliderItems = slider.querySelectorAll('.list .item');
    const thumbnails = slider.querySelectorAll('.thumbnail .item');
    const prevBtn = slider.querySelector('.prev');
    const nextBtn = slider.querySelector('.next');

    let currentIndex = 0;
    let intervalId;
    const autoplayInterval = 2500; // 2.5 secondes

    // Afficher un slide
    function showSlide(index) {
        // Gérer les limites
        if (index < 0) index = sliderItems.length - 1;
        if (index >= sliderItems.length) index = 0;

        // Désactiver tous les slides
        sliderItems.forEach(item => item.classList.remove('active'));
        thumbnails.forEach(thumb => thumb.classList.remove('active'));

        // Activer le slide courant
        sliderItems[index].classList.add('active');
        thumbnails[index].classList.add('active');

        currentIndex = index;
    }

    // Fonction pour passer au slide suivant
    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    // Fonction pour passer au slide précédent
    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    // Démarrer l'autoplay
    function startAutoplay() {
        if (intervalId) clearInterval(intervalId);
        intervalId = setInterval(nextSlide, autoplayInterval);
    }

    // Arrêter l'autoplay
    function stopAutoplay() {
        clearInterval(intervalId);
    }

    // Événements boutons
    prevBtn.addEventListener('click', function() {
        prevSlide();
        stopAutoplay();
        startAutoplay();
    });

    nextBtn.addEventListener('click', function() {
        nextSlide();
        stopAutoplay();
        startAutoplay();
    });

    // Événements miniatures
    thumbnails.forEach((thumb, index) => {
        thumb.addEventListener('click', function() {
            showSlide(index);
            stopAutoplay();
            startAutoplay();
        });
    });

    // Pause l'autoplay au survol
    slider.addEventListener('mouseenter', stopAutoplay);
    slider.addEventListener('mouseleave', startAutoplay);

    // Démarrer le slider
    showSlide(0);
    startAutoplay();
});