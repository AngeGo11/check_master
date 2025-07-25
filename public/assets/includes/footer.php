<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./assets/includes/footer.css?v=<?php echo time(); ?>">
</head>

<body>


    <footer>
        <div class="footer-content">
            <div class="footer-section about-section">
                <div class="logo">
                    <a href="index.php">
                        <img src="./assets/images/logo ufhb.png" alt="Logo MIAGE">
                    </a>
                </div>
                <p>Plateforme officielle de gestion des soutenances de la filière MIAGE de l'Université Félix Houphouët-Boigny. Découvrez nos services et ressources pour étudiants et enseignants.</p>
                <div class="contact-info">
                    <p><i class="fas fa-map-marker-alt"></i> Campus de Cocody, Abidjan, Côte d'Ivoire</p>
                    <p><i class="fas fa-phone"></i> +225 27 22 48 XX XX</p>
                    <p><i class="fas fa-envelope"></i> miage@univ-fhb.edu.ci</p>
                </div>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>


            <div class="footer-sections">

                <div class="footer-section links">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="#about">À propos</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="public/contact.php">Contact</a></li>
                        <li><a href="public/faq.php">FAQ</a></li>
                        <li><a href="public/actualites.php">Actualités</a></li>
                    </ul>
                </div>

                <div class="footer-section espace">
                    <h3>Espaces</h3>
                    <ul>
                        <li><a href="public/espace_etudiant.php">Espace Étudiant</a></li>
                        <li><a href="public/espace_enseignant.php">Espace Enseignant</a></li>
                        <li><a href="public/admin.php">Administration</a></li>
                        <li><a href="public/calendrier.php">Calendrier</a></li>
                        <li><a href="public/ressources.php">Ressources</a></li>
                    </ul>
                </div>

            </div>

            <div class="footer-section newsletter">
                <h3>Newsletter</h3>
                <p>Restez informé des actualités, événements et dates importantes de la filière MIAGE</p>
                <form action="#" method="post" class="newsletter-form">
                    <input type="email" name="email" placeholder="Votre adresse email" required>
                    <button type="submit" class="btn-subscribe">S'abonner</button>
                </form>
                <p class="small-text" style="font-size: 0.8rem; text-align:center; margin-top: 10px;">En vous inscrivant, vous acceptez notre <a href="public/confidentialite.php" style="text-decoration: underline;">politique de confidentialité</a></p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <script>
                    document.write(new Date().getFullYear());
                </script> MIAGE UFHB | Tous droits réservés | <a href="public/mentions-legales.php">Mentions légales</a></p>
        </div>

        <div class="back-to-top" id="backToTop"  aria-label="Retour en haut de page">
            <i class="fas fa-chevron-up"></i>
        </div>

        
    </footer>





</body>

</html>