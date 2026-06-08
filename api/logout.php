<?php
/**
 * Endpoint : Déconnexion de l'utilisateur
 * Détruit la session et supprime le cookie de session
 * 
 * Réponse JSON : {"success": true}
 */

session_start();

// En-têtes de réponse
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Supprimer toutes les variables de session
$_SESSION = [];

// Supprimer le cookie de session côté client
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Détruire la session côté serveur
session_destroy();

// Réponse de succès
http_response_code(200);
echo json_encode(['success' => true]);
