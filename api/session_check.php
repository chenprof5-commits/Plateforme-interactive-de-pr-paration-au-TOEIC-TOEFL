<?php
/**
 * Vérification de la session utilisateur
 * À inclure dans chaque endpoint API nécessitant une authentification
 * Fournit la variable $user_id aux fichiers qui l'incluent
 */

require_once __DIR__ . '/config.php';

// ── Constante de timeout (4 heures) ──────────────────────────────────────────
define('SESSION_MAX_AGE', 4 * 3600);

// ── Vérifier si l'utilisateur est authentifié ─────────────────────────────────
if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    empty($_SESSION['user_id'])
) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit();
}

// ── Vérifier le timeout de session ───────────────────────────────────────────
if (!empty($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_MAX_AGE) {
    // Session expirée : détruire et refuser
    $_SESSION = [];
    session_destroy();
    http_response_code(401);
    echo json_encode(['error' => 'Session expirée. Veuillez vous reconnecter.']);
    exit();
}

// ── Rafraîchir le timestamp d'activité ────────────────────────────────────────
$_SESSION['login_time'] = time();

// Identifiant de l'utilisateur connecté, disponible pour tous les endpoints
$user_id = (int) $_SESSION['user_id'];
