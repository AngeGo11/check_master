document.addEventListener("DOMContentLoaded", function() {
    const sections = document.querySelectorAll(".about .top-left, .about .middle-right, .about .bottom-left, .about .bottom-right");

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
                observer.unobserve(entry.target); // Supprime l'observation après l'affichage
            }
        });
    }, {
        threshold: 0.2
    });

    sections.forEach(section => {
        observer.observe(section);
    });
});