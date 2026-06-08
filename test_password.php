<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test des mots de passe</h2>";

// Connexion à la base de données
$host = "localhost"; 
$username = "root";
$password = "";
$dbname = "Plateforme_Interactive_TOIC_TOEFL";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Afficher quelques utilisateurs avec leurs mots de passe
echo "<h3>Utilisateurs et leurs mots de passe :</h3>";
try {
    $stmt = $pdo->query("SELECT ID, nom, prenons, INE, mot_de_passe FROM utilisateurs LIMIT 5");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>INE</th><th>Mot de passe (début)</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['ID'] . "</td>";
        echo "<td>" . $row['nom'] . "</td>";
        echo "<td>" . $row['prenons'] . "</td>";
        echo "<td>" . $row['INE'] . "</td>";
        echo "<td>" . substr($row['mot_de_passe'], 0, 30) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test avec un mot de passe spécifique
if (isset($_POST['test_ine']) && isset($_POST['test_password'])) {
    $test_ine = $_POST['test_ine'];
    $test_password = $_POST['test_password'];
    
    echo "<h3>Test avec INE: $test_ine et mot de passe: $test_password</h3>";
    
    try {
        $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE INE = :numero_INE");
        $stmt->bindParam(':numero_INE', $test_ine, PDO::PARAM_STR);
        $stmt->execute();
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($utilisateur) {
            $hashed_password = $utilisateur['mot_de_passe'];
            echo "<p><strong>Mot de passe hashé en base :</strong> $hashed_password</p>";
            
            // Test 1: password_verify
            echo "<h4>Test avec password_verify() :</h4>";
            if (password_verify($test_password, $hashed_password)) {
                echo "<p style='color: green;'>✅ password_verify() : CORRECT</p>";
            } else {
                echo "<p style='color: red;'>❌ password_verify() : INCORRECT</p>";
            }
            
            // Test 2: Comparaison directe
            echo "<h4>Test avec comparaison directe :</h4>";
            if ($test_password === $hashed_password) {
                echo "<p style='color: green;'>✅ Comparaison directe : CORRECT</p>";
            } else {
                echo "<p style='color: red;'>❌ Comparaison directe : INCORRECT</p>";
            }
            
            // Test 3: md5
            echo "<h4>Test avec md5() :</h4>";
            if (md5($test_password) === $hashed_password) {
                echo "<p style='color: green;'>✅ md5() : CORRECT</p>";
            } else {
                echo "<p style='color: red;'>❌ md5() : INCORRECT</p>";
            }
            
            // Test 4: sha1
            echo "<h4>Test avec sha1() :</h4>";
            if (sha1($test_password) === $hashed_password) {
                echo "<p style='color: green;'>✅ sha1() : CORRECT</p>";
            } else {
                echo "<p style='color: red;'>❌ sha1() : INCORRECT</p>";
            }
            
        } else {
            echo "<p style='color: red;'>Aucun utilisateur trouvé avec l'INE '$test_ine'</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h3>Formulaire de test :</h3>";
echo "<form method='POST'>";
echo "<p><label>INE: <input type='text' name='test_ine' value='N0001'></label></p>";
echo "<p><label>Mot de passe: <input type='password' name='test_password'></label></p>";
echo "<p><button type='submit'>Tester le mot de passe</button></p>";
echo "</form>";

echo "<p><a href='interface_login.html'>← Retour à la page de connexion</a></p>";
?> 