<?php
/**
 * Connexion utilisateur — sécurisé
 * Adapté pour Render + FreeSQLDatabase
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Démarrer la session avec des paramètres sécurisés
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,   // ✅ MODIFIÉ : true car Render fournit HTTPS automatiquement
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// Rediriger si déjà connecté
if (!empty($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header("Location: interface_principale.php");
    exit();
}

// ✅ MODIFIÉ : Variables d'environnement pour Render, fallback local automatique
$host   = getenv('DB_HOST')   ?: 'localhost';
$port   = getenv('DB_PORT')   ?: '3306';
$dbuser = getenv('DB_USER')   ?: 'root';
$dbpass = getenv('DB_PASS')   ?: '';
$dbname = getenv('DB_NAME')   ?: 'defaultdb';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",  // ✅ MODIFIÉ : port ajouté
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Erreur connexion DB (connexion.php) : " . $e->getMessage());
    header("Location: interface_login.html?error=" . urlencode("Erreur interne. Réessayez."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_INE   = trim($_POST['numero_INE'] ?? '');
    $mot_de_passe = $_POST['motdepasse'] ?? '';

    if (empty($numero_INE) || empty($mot_de_passe)) {
        header("Location: interface_login.html?error=" . urlencode("Tous les champs sont obligatoires."));
        exit();
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT ID, nom, prenons, INE, classe, email, mot_de_passe FROM utilisateurs WHERE INE = :ine LIMIT 1"
        );
        $stmt->bindParam(':ine', $numero_INE, PDO::PARAM_STR);
        $stmt->execute();
        $utilisateur = $stmt->fetch();

        if (!$utilisateur || !password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            header("Location: interface_login.html?error=" . urlencode("Identifiants incorrects."));
            exit();
        }

        session_regenerate_id(true);

        $_SESSION['user_id']       = $utilisateur['ID'];
        $_SESSION['user_nom']      = $utilisateur['nom'];
        $_SESSION['user_prenom']   = $utilisateur['prenons'];
        $_SESSION['user_ine']      = $utilisateur['INE'];
        $_SESSION['user_classe']   = $utilisateur['classe'];
        $_SESSION['user_email']    = $utilisateur['email'];
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time']    = time();

        error_log("Connexion réussie — ID utilisateur : " . $utilisateur['ID']);

        header("Location: interface_principale.php");
        exit();

    } catch (PDOException $e) {
        error_log("Erreur SQL connexion.php : " . $e->getMessage());
        header("Location: interface_login.html?error=" . urlencode("Erreur lors de la connexion."));
        exit();
    }
}
?>
