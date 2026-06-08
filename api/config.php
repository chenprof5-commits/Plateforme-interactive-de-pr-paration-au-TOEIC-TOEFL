<?php
/**
 * Configuration centralisée de la base de données et des en-têtes HTTP
 * Fichier à inclure dans tous les endpoints de l'API
 */

// Démarrage de la session
session_start();

// --- Constantes de connexion à la base de données ---
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'Plateforme_Interactive_TOIC_TOEFL');
define('DB_CHARSET', 'utf8mb4');

// --- En-têtes HTTP ---
// Type de contenu JSON pour toutes les réponses API
header('Content-Type: application/json; charset=utf-8');

// En-têtes CORS pour les requêtes same-origin
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store, no-cache, must-revalidate');

// --- Connexion PDO à MySQL ---
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Ne jamais exposer les détails de l'erreur en production
    error_log("Erreur de connexion DB (api/config.php): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur interne du serveur']);
    exit();
}
