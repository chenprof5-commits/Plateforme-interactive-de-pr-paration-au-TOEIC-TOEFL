<?php
/**
 * Endpoint POST : Enregistrer le score d'une activité
 * 
 * Corps JSON attendu :
 *   - type_activite      (string, obligatoire) : type du module
 *   - score              (int, obligatoire)    : nombre de bonnes réponses
 *   - total_questions     (int, obligatoire)    : nombre total de questions
 *   - duree_secondes      (int, optionnel)      : durée de la session en secondes
 * 
 * Réponse JSON :
 *   - success, score_total, progression, session_id
 */

require_once __DIR__ . '/session_check.php';

// --- Vérification de la méthode HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée. Utilisez POST.']);
    exit();
}

// --- Lecture du corps JSON ---
$input = json_decode(file_get_contents('php://input'), true);

if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Corps JSON invalide']);
    exit();
}

// --- Extraction et validation des champs ---
$type_activite   = $input['type_activite']   ?? null;
$score           = $input['score']           ?? null;
$total_questions = $input['total_questions'] ?? null;
$duree_secondes  = $input['duree_secondes']  ?? null;

// Liste des types d'activité autorisés (doit correspondre à l'ENUM de la table)
$types_autorises = ['qcm', 'mini_test', 'examen', 'examen_audio', 'examen_photos', 'texte_trou'];

// Validation du type d'activité
if (empty($type_activite) || !in_array($type_activite, $types_autorises, true)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Type d\'activité invalide',
        'types_autorises' => $types_autorises
    ]);
    exit();
}

// Validation du score et du total de questions
if ($score === null || $total_questions === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Les champs score et total_questions sont obligatoires']);
    exit();
}

$score           = (int) $score;
$total_questions = (int) $total_questions;

if ($score < 0 || $total_questions <= 0 || $score > $total_questions) {
    http_response_code(400);
    echo json_encode(['error' => 'Valeurs de score ou total_questions invalides']);
    exit();
}

// Validation optionnelle de la durée
if ($duree_secondes !== null) {
    $duree_secondes = (int) $duree_secondes;
    if ($duree_secondes < 0) {
        $duree_secondes = null;
    }
}

// --- Insertion et mise à jour dans une transaction ---
try {
    $pdo->beginTransaction();

    // 1. Insérer la session d'activité
    $stmt = $pdo->prepare("
        INSERT INTO `sessions_activite` 
            (`utilisateur_id`, `type_activite`, `score`, `total_questions`, `duree_secondes`, `termine_le`)
        VALUES 
            (:utilisateur_id, :type_activite, :score, :total_questions, :duree_secondes, NOW())
    ");
    $stmt->bindParam(':utilisateur_id', $user_id,         PDO::PARAM_INT);
    $stmt->bindParam(':type_activite',  $type_activite,   PDO::PARAM_STR);
    $stmt->bindParam(':score',          $score,           PDO::PARAM_INT);
    $stmt->bindParam(':total_questions', $total_questions, PDO::PARAM_INT);
    $stmt->bindParam(':duree_secondes', $duree_secondes,  PDO::PARAM_INT);
    $stmt->execute();

    $session_id = (int) $pdo->lastInsertId();

    // 2. Calculer la progression basée sur les types distincts complétés
    //    4 types principaux comptent pour la progression : qcm, mini_test, examen, texte_trou
    $types_principaux = ['qcm', 'mini_test', 'examen', 'texte_trou'];
    $placeholders = implode(',', array_fill(0, count($types_principaux), '?'));

    $stmt_prog = $pdo->prepare("
        SELECT COUNT(DISTINCT `type_activite`) AS types_completes
        FROM `sessions_activite`
        WHERE `utilisateur_id` = ?
          AND `type_activite` IN ($placeholders)
    ");

    // Paramètres : user_id + les 4 types principaux
    $params_prog = array_merge([$user_id], $types_principaux);
    $stmt_prog->execute($params_prog);
    $result_prog = $stmt_prog->fetch();

    $types_completes = (int) $result_prog['types_completes'];
    $progression = round(($types_completes / 4) * 100, 2);

    // 3. Calculer le score total cumulé (somme de toutes les bonnes réponses)
    $stmt_score = $pdo->prepare("
        SELECT COALESCE(SUM(`score`), 0) AS score_total
        FROM `sessions_activite`
        WHERE `utilisateur_id` = ?
    ");
    $stmt_score->execute([$user_id]);
    $result_score = $stmt_score->fetch();

    $score_total = (int) $result_score['score_total'];

    // 4. Mettre à jour le profil de l'utilisateur
    $stmt_update = $pdo->prepare("
        UPDATE `utilisateurs`
        SET `score_total` = :score_total,
            `progression` = :progression
        WHERE `ID` = :user_id
    ");
    $stmt_update->bindParam(':score_total',  $score_total,  PDO::PARAM_INT);
    $stmt_update->bindParam(':progression',  $progression,  PDO::PARAM_STR);
    $stmt_update->bindParam(':user_id',      $user_id,      PDO::PARAM_INT);
    $stmt_update->execute();

    $pdo->commit();

    // --- Réponse de succès ---
    http_response_code(200);
    echo json_encode([
        'success'     => true,
        'session_id'  => $session_id,
        'score_total' => $score_total,
        'progression' => (float) $progression
    ]);

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur save_score.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de l\'enregistrement du score']);
}
