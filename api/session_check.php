<?php
/**
 * Vérification de la session utilisateur
 * À inclure dans chaque endpoint API nécessitant une authentification
 * Fournit la variable $user_id aux fichiers qui l'incluent
 */

require_once __DIR__ . '/config.php';

// Vérifier si l'utilisateur est authentifié
if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    empty($_SESSION['user_id'])
) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit();
}

// Identifiant de l'utilisateur connecté, disponible pour tous les endpoints
$user_id = (int) $_SESSION['user_id'];
