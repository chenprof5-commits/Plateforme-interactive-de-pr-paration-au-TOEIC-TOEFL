<?php
session_start();
$prenom = isset($_SESSION['user_prenom']) ? $_SESSION['user_prenom'] : '';
$nom = isset($_SESSION['user_nom']) ? $_SESSION['user_nom'] : '';

// Activer l'affichage des erreurs pour le debug temporaire
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
$host = "localhost"; 
$username = "root";
$password = "";
$dbname = "Plateforme_Interactive_TOIC_TOEFL";

// Connexion à MySQL avec PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Log l'erreur mais ne l'affiche pas à l'utilisateur
    error_log("Erreur de connexion DB: " . $e->getMessage());
    header("Location: interface_login.html?error=" . urlencode("Erreur de connexion à la base de données"));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation et nettoyage des données
    $numero_INE = trim($_POST['numero_INE']);
    $mot_de_passe = $_POST['motdepasse'];
    
    // Debug: afficher les données reçues
    error_log("Tentative de connexion - INE: $numero_INE");
    
    // Validation des données
    if (empty($numero_INE) || empty($mot_de_passe)) {
        header("Location: interface_login.html?error=" . urlencode("Tous les champs sont obligatoires"));
        exit();
    }
    
    try {
        // Vérification de l'existence de l'utilisateur
        $stmt = $pdo->prepare("SELECT ID, nom, prenons, INE, classe, email, mot_de_passe FROM utilisateurs WHERE INE = :numero_INE");
        $stmt->bindParam(':numero_INE', $numero_INE, PDO::PARAM_STR);
        $stmt->execute();
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: afficher le résultat
        error_log("Résultat de la requête: " . ($utilisateur ? "Utilisateur trouvé" : "Aucun utilisateur trouvé"));
        
        if (!$utilisateur) {
            header("Location: interface_login.html?error=" . urlencode("Identifiants incorrects"));
            exit();
        }
        
        // Vérification du mot de passe
        if (password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            // Connexion réussie - Création de la session
            $_SESSION['user_id'] = $utilisateur['ID'];
            $_SESSION['user_nom'] = $utilisateur['nom'];
            $_SESSION['user_prenom'] = $utilisateur['prenons'];
            $_SESSION['user_ine'] = $utilisateur['INE'];
            $_SESSION['user_classe'] = $utilisateur['classe'];
            $_SESSION['user_email'] = $utilisateur['email'];
            $_SESSION['authenticated'] = true;
            $_SESSION['login_time'] = time();
            
            error_log("Connexion réussie pour l'utilisateur: " . $utilisateur['nom'] . " " . $utilisateur['prenons']);
            
            // Redirection vers la page principale
            header("Location: interface_principale.php");
            exit();
        } else {
            error_log("Mot de passe incorrect pour l'utilisateur: " . $utilisateur['nom']);
            header("Location: interface_login.html?error=" . urlencode("Identifiants incorrects"));
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la connexion: " . $e->getMessage());
        header("Location: interface_login.html?error=" . urlencode("Erreur lors de la connexion"));
        exit();
    }
}
?>