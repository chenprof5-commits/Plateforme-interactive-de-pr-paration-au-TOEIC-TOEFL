<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
$host = "localhost"; 
$username = "root";  // root est le mot de passe
$password = "";      // mot de passe vierge
$dbname = "Plateforme_Interactive_TOIC_TOEFL"; // Le nom de la base de données

// Connexion à MySQL avec PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérification de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $numero_INE = $_POST['numero_INE'];
    $email = $_POST['email'];
    $motdepasse = $_POST['motdepasse'];
    $confirm_motdepasse = $_POST['confirm_motdepasse'];
    $classe = $_POST['classe'];

    // Vérification si le mot de passe et la confirmation du mot de passe sont identiques
    if ($motdepasse !== $confirm_motdepasse) {
        die("Les mots de passe ne correspondent pas.");
    }

    // Vérification du numéro INE dans la table liste_INE
    $stmt = $pdo->prepare("SELECT * FROM Liste_INE WHERE numero_INE = :numero_INE");
    $stmt->bindParam(':numero_INE', $numero_INE, PDO::PARAM_STR);
    $stmt->execute();
    $stmt1 = $pdo->prepare("SELECT * FROM utilisateurs WHERE INE = :numero_INE");
    $stmt1->bindParam(':numero_INE', $numero_INE, PDO::PARAM_STR);
    $stmt1->execute();
    if ($stmt1->rowCount()> 0){
        //popup vous avez déjà un compte
        echo"
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let overlay = document.createElement('div');
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.background = 'rgba(0, 0, 0, 0.5)';
                overlay.style.display = 'flex';
                overlay.style.justifyContent = 'center';
                overlay.style.alignItems = 'center';
                document.body.appendChild(overlay);

                let popup = document.createElement('div');
                popup.style.background = 'white';
                popup.style.padding = '20px';
                popup.style.borderRadius = '10px';
                popup.style.boxShadow = '0px 4px 10px rgba(0, 0, 0, 0.3)';
                popup.style.textAlign = 'center';
                popup.style.width = '300px';

                let message = document.createElement('p');
                message.textContent = 'Vous avez déjà un compte \\ Veuillez vous connecter ';
                popup.appendChild(message);

                let button = document.createElement('button');
                button.textContent = 'OK';
                button.style.background = '#ff4d4d';
                button.style.color = 'white';
                button.style.border = 'none';
                button.style.padding = '10px 20px';
                button.style.borderRadius = '5px';
                button.style.cursor = 'pointer';
                button.style.marginTop = '10px';
                button.onclick = function() {
                    document.body.removeChild(overlay);
                    window.location.href = 'interface_login.html';
                };
                popup.appendChild(button);
                
                overlay.appendChild(popup);
            });
        </script>";
        die();
    }

    // Si le numéro INE existe dans la table liste_INE
    if ($stmt->rowCount() > 0 and $stmt1->rowCount() == 0 ) {
        // Hashage du mot de passe
        $hashedPassword = password_hash($motdepasse, PASSWORD_DEFAULT);

        // Insertion dans la table utilisateurs
        $stmtInsert = $pdo->prepare("INSERT INTO utilisateurs (nom, prenons, INE, classe, email, mot_de_passe) 
                                    VALUES (:nom, :prenom, :numero_INE, :classe, :email, :motdepasse)");
        $stmtInsert->bindParam(':nom', $nom, PDO::PARAM_STR);
        $stmtInsert->bindParam(':prenom', $prenom, PDO::PARAM_STR);
        $stmtInsert->bindParam(':numero_INE', $numero_INE, PDO::PARAM_STR);
        $stmtInsert->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtInsert->bindParam(':motdepasse', $hashedPassword, PDO::PARAM_STR);
        $stmtInsert->bindParam(':classe', $classe, PDO::PARAM_STR);

        // Exécution de l'insertion
        if ($stmtInsert->execute()) {
        // POPUP inscription réussie
            echo "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let overlay = document.createElement('div');
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.background = 'rgba(0, 0, 0, 0.5)';
                overlay.style.display = 'flex';
                overlay.style.justifyContent = 'center';
                overlay.style.alignItems = 'center';
                document.body.appendChild(overlay);

                let popup = document.createElement('div');
                popup.style.background = 'white';
                popup.style.padding = '20px';
                popup.style.borderRadius = '10px';
                popup.style.boxShadow = '0px 4px 10px rgba(0, 0, 0, 0.3)';
                popup.style.textAlign = 'center';
                popup.style.width = '300px';

                let message = document.createElement('p');
                message.textContent = 'Inscription réussie \\ Vous pouvez maintenant \\ vous connecter.';
                popup.appendChild(message);

                let button = document.createElement('button');
                button.textContent = 'OK';
                button.style.background = '#ff4d4d';
                button.style.color = 'white';
                button.style.border = 'none';
                button.style.padding = '10px 20px';
                button.style.borderRadius = '5px';
                button.style.cursor = 'pointer';
                button.style.marginTop = '10px';
                button.onclick = function() {
                    document.body.removeChild(overlay);
                    window.location.href = 'interface_login.html';
                };
                popup.appendChild(button);
                
                overlay.appendChild(popup);
            });
        </script>";
        } else {
            //Popup erreur de connexion
            echo "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let overlay = document.createElement('div');
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.background = 'rgba(0, 0, 0, 0.5)';
                overlay.style.display = 'flex';
                overlay.style.justifyContent = 'center';
                overlay.style.alignItems = 'center';
                document.body.appendChild(overlay);

                let popup = document.createElement('div');
                popup.style.background = 'white';
                popup.style.padding = '20px';
                popup.style.borderRadius = '10px';
                popup.style.boxShadow = '0px 4px 10px rgba(0, 0, 0, 0.3)';
                popup.style.textAlign = 'center';
                popup.style.width = '300px';

                let message = document.createElement('p');
                message.textContent = 'Erreur lors de l'inscription \\ Veuillez réessayer';
                popup.appendChild(message);

                let button = document.createElement('button');
                button.textContent = 'OK';
                button.style.background = '#ff4d4d';
                button.style.color = 'white';
                button.style.border = 'none';
                button.style.padding = '10px 20px';
                button.style.borderRadius = '5px';
                button.style.cursor = 'pointer';
                button.style.marginTop = '10px';
                button.onclick = function() {
                    document.body.removeChild(overlay);
                    window.location.href = 'interface_de_connexion.html';
                };
                popup.appendChild(button);
                
                overlay.appendChild(popup);
            });
        </script>";
        }
    } else {
        //pop up numéro INE non autorisé
        echo "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let overlay = document.createElement('div');
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.background = 'rgba(0, 0, 0, 0.5)';
                overlay.style.display = 'flex';
                overlay.style.justifyContent = 'center';
                overlay.style.alignItems = 'center';
                document.body.appendChild(overlay);

                let popup = document.createElement('div');
                popup.style.background = 'white';
                popup.style.padding = '20px';
                popup.style.borderRadius = '10px';
                popup.style.boxShadow = '0px 4px 10px rgba(0, 0, 0, 0.3)';
                popup.style.textAlign = 'center';
                popup.style.width = '300px';

                let message = document.createElement('p');
                message.textContent = 'Le numéro INE n\\'est pas autorisé à s\\'inscrire.';
                popup.appendChild(message);

                let button = document.createElement('button');
                button.textContent = 'OK';
                button.style.background = '#ff4d4d';
                button.style.color = 'white';
                button.style.border = 'none';
                button.style.padding = '10px 20px';
                button.style.borderRadius = '5px';
                button.style.cursor = 'pointer';
                button.style.marginTop = '10px';
                button.onclick = function() {
                    document.body.removeChild(overlay);
                    window.location.href = 'interface_de_connexion.html';
                };
                popup.appendChild(button);
                
                overlay.appendChild(popup);
            });
        </script>";
        exit();
    }

}
?>
