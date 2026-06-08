<?php
/**
 * Endpoint GET : Récupérer l'historique des activités de l'utilisateur
 * 
 * Paramètres GET optionnels :
 *   - type_activite : filtrer par type d'activité
 * 
 * Réponse JSON : tableau de sessions
 */

require_once __DIR__ . '/session_check.php';

// --- Vérification de la méthode HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée. Utilisez GET.']);
    exit();
}

try {
    // Filtre optionnel par type d'activité
    $type_filtre = $_GET['type_activite'] ?? null;
    $types_autorises = ['qcm', 'mini_test', 'examen', 'examen_audio', 'examen_photos', 'texte_trou'];

    // Construire la requête SQL dynamiquement
    $sql = "
        SELECT 
            `id`,
            `type_activite`,
            `score`,
            `total_questions`,
            `duree_secondes`,
            `commence_le`,
            `termine_le`
        FROM `sessions_activite`
        WHERE `utilisateur_id` = :user_id
    ";
    $params = [':user_id' => $user_id];

    // Ajouter le filtre par type si fourni et valide
    if ($type_filtre !== null && $type_filtre !== '') {
        if (!in_array($type_filtre, $types_autorises, true)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Type d\'activité invalide',
                'types_autorises' => $types_autorises
            ]);
            exit();
        }
        $sql .= " AND `type_activite` = :type_activite";
        $params[':type_activite'] = $type_filtre;
    }

    $sql .= " ORDER BY `commence_le` DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sessions = $stmt->fetchAll();

    // Formater les résultats avec le pourcentage calculé
    $historique = [];
    foreach ($sessions as $session) {
        $total = (int) $session['total_questions'];
        $score_val = (int) $session['score'];
        $pourcentage = $total > 0 ? round(($score_val / $total) * 100, 2) : 0;

        $historique[] = [
            'id'              => (int) $session['id'],
            'type_activite'   => $session['type_activite'],
            'score'           => $score_val,
            'total_questions' => $total,
            'duree_secondes'  => $session['duree_secondes'] !== null ? (int) $session['duree_secondes'] : null,
            'date'            => $session['commence_le'],
            'termine_le'      => $session['termine_le'],
            'pourcentage'     => $pourcentage
        ];
    }

    // --- Réponse JSON ---
    http_response_code(200);
    echo json_encode($historique);

} catch (PDOException $e) {
    error_log("Erreur get_history.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération de l\'historique']);
}
