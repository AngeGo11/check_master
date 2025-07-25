<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./assets/includes/header.css?v=<?php echo time(); ?>">
</head>

<body>


    <header>
        <div class="logo">
            <a href="index.php">
                <img src="./assets/images/logo ufhb.png" alt="Logo MIAGE">
            </a>
        </div>

        <nav class="desktop-nav">
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="#about">À propos</a></li>
                <li><a href="indexCM.php#services">Services</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>

        <div class="auth-buttons">
            <a href="pageConnexion.php" class="login-btn">S'authentifier</a>
        </div>

        <div class="mobile-menu-btn">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
    </header>

    <div class="mobile-nav">
        <ul>
            <li><a href="index.php">Accueil</a></li>
            <li><a href="#about">À propos</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="public/contact.php">Contact</a></li>
            <li><a href="login.php" style="text-wrap: nowrap;" >S'authentifier</a></li>
        </ul>
    </div>




</body>

</html>