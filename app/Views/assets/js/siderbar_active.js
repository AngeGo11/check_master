document.addEventListener("DOMContentLoaded", function() {
    const links = document.querySelectorAll("a");
    const currentPage = new URLSearchParams(window.location.search).get("page"); // Récupère la valeur de "page"

    

    links.forEach(link => {

        //Ignore le lien de deconnexion
        if(link.href.includes('?op=login')){
            return;
        }


        // On extrait la valeur du paramètre "page" du href du lien
        const linkPage = new URL(link.href).searchParams.get("page");

        // Si le paramètre "page" du lien correspond à celui de la page actuelle
        if (linkPage === currentPage) {
            link.classList.add("active");
        }

        link.addEventListener("click", function() {
            // Supprime "active" de tous les liens
            links.forEach(a => a.classList.remove("active"));

            // Ajoute "active" au lien cliqué
            link.classList.add("active");
        });
    });
});