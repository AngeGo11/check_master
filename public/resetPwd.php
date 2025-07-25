<?php
use App\Controllers\AuthController;

require_once __DIR__ . '/../../../vendor/autoload.php';

$controller = new AuthController();
$controller->reset();



include 'config/db_connect.php';

if (!isset($_GET['token'])) {
    header('Location: authentication.php');
    exit();
}

$_SESSION['error_message'] = "";
$_SESSION['success_message'] = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (isset($_GET['token'])) {
        $token = $_GET['token'];

        $sql = "SELECT * FROM reset_password WHERE token = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        $result = $stmt->fetch();

        if ($result) {
            $email = $result['email'];


            if ($new_password === $confirm_password) {
                $password = $new_password;
                $hashed_password = hash('sha256', $password);

                $sql = "UPDATE utilisateur SET mdp_utilisateur = ? WHERE login_utilisateur = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$hashed_password, $email]);

                $sql = "DELETE FROM reset_password WHERE token = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$token]);

                $_SESSION['message'] = "Mot de passe réinitialisé avec succès.";
            } else {
                $_SESSION['error_message'] = "Les mots de passe ne correspondent pas.";
            }
        } else {
            echo "Token invalide.";
        }
    } else {
        echo "Token manquant.";
    }
}
?>













<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="../GSCV/assets/css/resetMdp.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>

<body>
    <div class="container">
        <div class="left-panel">
            <div class="logo">
                <img src="../GSCV/assets/images/logo_cm_sbg.png" alt="Logo Check Master">
            </div>

            <h2><span>CHECK</span> MASTER</h2>
            <p> <span style="font-size: 25px; font-weight:700">REINITIALISATION DU MOT DE PASSE !</span> <br>Veuillez renseigner les informations ci-dessous pour finaliser la réinitialisation de votre mot de passe.</p>

            <form method="POST">
                <?php
                if (isset($_SESSION['message'])) {
                    echo "<p class='message'>" . $_SESSION['message'] . "</p>";
                    unset($_SESSION['message']);
                } elseif (isset($_SESSION['error_message'])) {
                    echo "<p class='error_message'>" . $_SESSION['error_message'] . "</p>";
                    unset($_SESSION['error_message']);
                }
                ?>
                <div class="input-group floating">
                    <input type="new_password" id="new_password" name="new_password" placeholder=" ">
                    <label for="new_password">Nouveau mot de passe</label>
                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                </div>

                <div class="input-group floating">
                    <input type="confirm_password" id="confirm_password" name="confirm_password" placeholder=" ">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                </div>



                <button type="submit" name="confirmation-btn" class="confirmation-btn">CONFIRMER</button>
                <div class="back-to-connexion">
                    <a href="authentication.php">Retour à la connexion </a>
                </div>
            </form>


        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const formPanels = document.querySelectorAll('.form-panel');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Update active tab
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    // Show corresponding form panel
                    const tabId = this.getAttribute('data-tab');
                    formPanels.forEach(panel => {
                        panel.classList.remove('active');
                    });

                    if (tabId === 'login') {
                        document.getElementById('login-panel').classList.add('active');
                    } else {
                        document.getElementById('signup-panel').classList.add('active');
                    }
                });
            });
        });
    </script>
</body>

</html>
