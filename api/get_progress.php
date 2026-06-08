<?php
/**
 * Endpoint GET : Récupérer la progression de l'utilisateur
 * 
 * Réponse JSON :
 *   - nom, prenom, score_total, progression
 *   - sessions_count, types_completed, best_scores
 */

require_once __DIR__ . '/session_check.php';

// --- Vérification de la méthode HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée. Utilisez GET.']);
    exit();
}

try {
    // 1. Récupérer les informations de l'utilisateur (nom, prénom, score, progression)
    $stmt_user = $pdo->prepare("
        SELECT `nom`, `prenons`, `score_total`, `progression`
        FROM `utilisateurs`
        WHERE `ID` = :user_id
    ");
    $stmt_user->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_user->execute();
    $utilisateur = $stmt_user->fetch();

    if (!$utilisateur) {
        http_response_code(404);
        echo json_encode(['error' => 'Utilisateur non trouvé']);
        exit();
    }

    // 2. Compter le nombre total de sessions
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*) AS sessions_count
        FROM `sessions_activite`
        WHERE `utilisateur_id` = :user_id
    ");
    $stmt_count->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_count->execute();
    $result_count = $stmt_count->fetch();
    $sessions_count = (int) $result_count['sessions_count'];

    // 3. Récupérer les types d'activités complétés (distincts)
    $stmt_types = $pdo->prepare("
        SELECT DISTINCT `type_activite`
        FROM `sessions_activite`
        WHERE `utilisateur_id` = :user_id
        ORDER BY `type_activite` ASC
    ");
    $stmt_types->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_types->execute();
    $types_completed = $stmt_types->fetchAll(PDO::FETCH_COLUMN, 0);

    // 4. Récupérer le meilleur score par type d'activité
    $stmt_best = $pdo->prepare("
        SELECT 
            `type_activite`,
            MAX(`score`) AS meilleur_score,
            `total_questions`
        FROM `sessions_activite`
        WHERE `utilisateur_id` = :user_id
        GROUP BY `type_activite`, `total_questions`
    ");
    $stmt_best->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_best->execute();
    $best_rows = $stmt_best->fetchAll();

    // Construire le tableau des meilleurs scores par type
    // On garde le meilleur pourcentage pour chaque type
    $best_scores = [];
    foreach ($best_rows as $row) {
        $type = $row['type_activite'];
        $score_val = (int) $row['meilleur_score'];
        $total_val = (int) $row['total_questions'];
        $pourcentage = $total_val > 0 ? round(($score_val / $total_val) * 100, 2) : 0;

        // Si ce type n'existe pas encore ou si le pourcentage est meilleur, on met à jour
        if (
            !isset($best_scores[$type]) ||
            $pourcentage > $best_scores[$type]['pourcentage']
        ) {
            $best_scores[$type] = [
                'score'       => $score_val,
                'total'       => $total_val,
                'pourcentage' => $pourcentage
            ];
        }
    }

    // --- Réponse JSON ---
    http_response_code(200);
    echo json_encode([
        'nom'             => $utilisateur['nom'],
        'prenom'          => $utilisateur['prenons'],
        'score_total'     => (int) $utilisateur['score_total'],
        'progression'     => (float) $utilisateur['progression'],
        'sessions_count'  => $sessions_count,
        'types_completed' => $types_completed,
        'best_scores'     => (object) $best_scores
    ]);

} catch (PDOException $e) {
    error_log("Erreur get_progress.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération de la progression']);
}
