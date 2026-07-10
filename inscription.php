<?php
/**
 * Inscription utilisateur — sécurisé
 */
ini_set('display_errors', 0);   // Ne jamais exposer les erreurs PHP en prod
error_reporting(E_ALL);         // Logger uniquement

// Connexion à la base de données
$host     = getenv('DB_HOST') ?: 'localhost';
$port     = getenv('DB_PORT') ?: '3306';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbname   = getenv('DB_NAME') ?: 'defaultdb';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Erreur connexion DB (inscription.php) : " . $e->getMessage());
    die("<script>alert('Erreur interne. Veuillez réessayer.');</script>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Récupération + nettoyage ──────────────────────────────────────────────
    $nom               = trim(htmlspecialchars($_POST['nom']             ?? '', ENT_QUOTES, 'UTF-8'));
    $prenom            = trim(htmlspecialchars($_POST['prenom']          ?? '', ENT_QUOTES, 'UTF-8'));
    $numero_INE        = trim($_POST['numero_INE']                       ?? '');
    $email             = trim($_POST['email']                            ?? '');
    $motdepasse        = $_POST['motdepasse']                            ?? '';
    $confirm_motdepasse= $_POST['confirm_motdepasse']                   ?? '';
    $classe            = trim($_POST['classe']                           ?? '');

    // ── Validation ────────────────────────────────────────────────────────────
    $erreurs = [];

    if (empty($nom) || strlen($nom) > 100)
        $erreurs[] = "Nom invalide (1–100 caractères).";
    if (empty($prenom) || strlen($prenom) > 100)
        $erreurs[] = "Prénom invalide (1–100 caractères).";
    if (empty($numero_INE) || strlen($numero_INE) > 20)
        $erreurs[] = "Numéro INE invalide.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150)
        $erreurs[] = "Adresse email invalide.";
    if (strlen($motdepasse) < 6)
        $erreurs[] = "Le mot de passe doit comporter au moins 6 caractères.";
    if ($motdepasse !== $confirm_motdepasse)
        $erreurs[] = "Les mots de passe ne correspondent pas.";

    if (!empty($erreurs)) {
        $msg = implode(" / ", $erreurs);
        echo "<script>alert(" . json_encode($msg) . "); history.back();</script>";
        exit();
    }

    // ── Vérifier INE autorisé ─────────────────────────────────────────────────
    
    $stmtINE = $pdo->prepare("SELECT numero_INE FROM liste_ine WHERE numero_INE = :ine LIMIT 1");
    $stmtINE->bindParam(':ine', $numero_INE, PDO::PARAM_STR);
    $stmtINE->execute();
    $ineAutorise = $stmtINE->fetch();

    if (!$ineAutorise) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:9999';
                var popup = document.createElement('div');
                popup.style.cssText = 'background:white;padding:24px;border-radius:12px;text-align:center;max-width:320px;box-shadow:0 4px 16px rgba(0,0,0,0.3)';
                popup.innerHTML = '<p style=\"margin-bottom:16px\">Le numéro INE n\\'est pas autorisé à s\\'inscrire.</p><button onclick=\"window.location.href=\\'interface_de_connexion.html\\'\" style=\"background:#ef4444;color:white;border:none;padding:10px 24px;border-radius:8px;cursor:pointer\">OK</button>';
                overlay.appendChild(popup);
                document.body.appendChild(overlay);
            });
        </script>";
        exit();
    }

    // ── Vérifier si un compte existe déjà pour cet INE ───────────────────────
    $stmtExist = $pdo->prepare("SELECT ID FROM utilisateurs WHERE INE = :ine LIMIT 1");
    $stmtExist->bindParam(':ine', $numero_INE, PDO::PARAM_STR);
    $stmtExist->execute();

    if ($stmtExist->fetch()) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:9999';
                var popup = document.createElement('div');
                popup.style.cssText = 'background:white;padding:24px;border-radius:12px;text-align:center;max-width:320px;box-shadow:0 4px 16px rgba(0,0,0,0.3)';
                popup.innerHTML = '<p style=\"margin-bottom:16px\">Vous avez déjà un compte. Veuillez vous connecter.</p><button onclick=\"window.location.href=\\'interface_login.html\\'\" style=\"background:#ef4444;color:white;border:none;padding:10px 24px;border-radius:8px;cursor:pointer\">OK</button>';
                overlay.appendChild(popup);
                document.body.appendChild(overlay);
            });
        </script>";
        exit();
    }

    // ── Insertion ─────────────────────────────────────────────────────────────
    $hashedPassword = password_hash($motdepasse, PASSWORD_DEFAULT);

    try {
        $stmtInsert = $pdo->prepare(
            "INSERT INTO utilisateurs (nom, prenons, INE, classe, email, mot_de_passe, rang, score_palier)
             VALUES (:nom, :prenom, :ine, :classe, :email, :mdp, 'Débutant', 0)"
        );
        $stmtInsert->bindParam(':nom',    $nom,            PDO::PARAM_STR);
        $stmtInsert->bindParam(':prenom', $prenom,         PDO::PARAM_STR);
        $stmtInsert->bindParam(':ine',    $numero_INE,     PDO::PARAM_STR);
        $stmtInsert->bindParam(':classe', $classe,         PDO::PARAM_STR);
        $stmtInsert->bindParam(':email',  $email,          PDO::PARAM_STR);
        $stmtInsert->bindParam(':mdp',    $hashedPassword, PDO::PARAM_STR);
        $stmtInsert->execute();

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:9999';
                var popup = document.createElement('div');
                popup.style.cssText = 'background:white;padding:24px;border-radius:12px;text-align:center;max-width:320px;box-shadow:0 4px 16px rgba(0,0,0,0.3)';
                popup.innerHTML = '<p style=\"margin-bottom:16px\">✅ Inscription réussie ! Vous pouvez maintenant vous connecter.</p><button onclick=\"window.location.href=\\'interface_login.html\\'\" style=\"background:#10b981;color:white;border:none;padding:10px 24px;border-radius:8px;cursor:pointer\">OK</button>';
                overlay.appendChild(popup);
                document.body.appendChild(overlay);
            });
        </script>";

    } catch (PDOException $e) {
        error_log("Erreur INSERT inscription.php : " . $e->getMessage());
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:9999';
                var popup = document.createElement('div');
                popup.style.cssText = 'background:white;padding:24px;border-radius:12px;text-align:center;max-width:320px;box-shadow:0 4px 16px rgba(0,0,0,0.3)';
                popup.innerHTML = '<p style=\"margin-bottom:16px\">Une erreur est survenue. Veuillez réessayer.</p><button onclick=\"history.back()\" style=\"background:#ef4444;color:white;border:none;padding:10px 24px;border-radius:8px;cursor:pointer\">OK</button>';
                overlay.appendChild(popup);
                document.body.appendChild(overlay);
            });
        </script>";
    }
}
?>
