<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug de la connexion</h2>";

// Test 1: Vérifier si on reçoit les données POST
echo "<h3>1. Données POST reçues :</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p>✅ Méthode POST détectée</p>";
    echo "<ul>";
    foreach ($_POST as $key => $value) {
        echo "<li><strong>$key:</strong> " . htmlspecialchars($value) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Pas de méthode POST</p>";
    echo "<p>Méthode actuelle : " . $_SERVER['REQUEST_METHOD'] . "</p>";
}

// Test 2: Connexion à la base de données
echo "<h3>2. Test de connexion à la base de données :</h3>";
$host = "localhost"; 
$username = "root";
$password = "";
$dbname = "Plateforme_Interactive_TOIC_TOEFL";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Connexion à la base de données réussie</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion : " . $e->getMessage() . "</p>";
    exit();
}

// Test 3: Vérifier la table utilisateurs
echo "<h3>3. Test de la table utilisateurs :</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>✅ Table utilisateurs trouvée avec $count utilisateurs</p>";
    
    // Afficher quelques utilisateurs
    $stmt = $pdo->query("SELECT ID, nom, prenons, INE, email FROM utilisateurs LIMIT 3");
    echo "<p><strong>Exemples d'utilisateurs :</strong></p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>INE</th><th>Email</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['ID'] . "</td>";
        echo "<td>" . $row['nom'] . "</td>";
        echo "<td>" . $row['prenons'] . "</td>";
        echo "<td>" . $row['INE'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur avec la table utilisateurs : " . $e->getMessage() . "</p>";
}

// Test 4: Si on a des données POST, tester la requête
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_INE'])) {
    echo "<h3>4. Test de la requête de connexion :</h3>";
    
    $numero_INE = trim($_POST['numero_INE']);
    echo "<p>INE recherché : <strong>$numero_INE</strong></p>";
    
    try {
        $stmt = $pdo->prepare("SELECT ID, nom, prenons, INE, classe, email, mot_de_passe FROM utilisateurs WHERE INE = :numero_INE");
        $stmt->bindParam(':numero_INE', $numero_INE, PDO::PARAM_STR);
        $stmt->execute();
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($utilisateur) {
            echo "<p style='color: green;'>✅ Utilisateur trouvé !</p>";
            echo "<ul>";
            echo "<li><strong>Nom:</strong> " . $utilisateur['nom'] . "</li>";
            echo "<li><strong>Prénom:</strong> " . $utilisateur['prenons'] . "</li>";
            echo "<li><strong>INE:</strong> " . $utilisateur['INE'] . "</li>";
            echo "<li><strong>Classe:</strong> " . $utilisateur['classe'] . "</li>";
            echo "<li><strong>Email:</strong> " . $utilisateur['email'] . "</li>";
            echo "<li><strong>Mot de passe hashé:</strong> " . substr($utilisateur['mot_de_passe'], 0, 20) . "...</li>";
            echo "</ul>";
            
            // Test du mot de passe
            if (isset($_POST['motdepasse'])) {
                $mot_de_passe = $_POST['motdepasse'];
                echo "<p>Test du mot de passe...</p>";
                
                if (password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                    echo "<p style='color: green;'>✅ Mot de passe correct !</p>";
                    echo "<p>La connexion devrait fonctionner.</p>";
                } else {
                    echo "<p style='color: red;'>❌ Mot de passe incorrect</p>";
                    echo "<p>Mot de passe saisi : <strong>$mot_de_passe</strong></p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Aucun utilisateur trouvé avec l'INE '$numero_INE'</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur lors de la requête : " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h3>Formulaire de test :</h3>";
echo "<form method='POST' action='debug_connexion.php'>";
echo "<p><label>INE: <input type='text' name='numero_INE' value='N0001'></label></p>";
echo "<p><label>Mot de passe: <input type='password' name='motdepasse'></label></p>";
echo "<p><button type='submit'>Tester la connexion</button></p>";
echo "</form>";

echo "<p><a href='interface_login.html'>← Retour à la page de connexion</a></p>";
?> 