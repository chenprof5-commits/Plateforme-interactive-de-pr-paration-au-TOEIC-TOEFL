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
$types_autorises = ['qcm', 'mini_test', 'examen', 'examen_audio', 'examen_photos', 'texte_trou', 'prononciation'];

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

    // 2. Calculer la progression (types distincts complétés / 4)
    $stmt_prog = $pdo->prepare("
        SELECT COUNT(DISTINCT CASE 
            WHEN `type_activite` IN ('examen', 'examen_audio', 'examen_photos') THEN 'examen' 
            ELSE `type_activite` 
        END) AS types_completes
        FROM `sessions_activite`
        WHERE `utilisateur_id` = ?
          AND `type_activite` IN ('qcm', 'mini_test', 'examen', 'examen_audio', 'examen_photos', 'texte_trou')
    ");
    $stmt_prog->execute([$user_id]);
    $result_prog = $stmt_prog->fetch();
    $types_completes = (int) $result_prog['types_completes'];
    $progression = round(($types_completes / 4) * 100, 2);

    // 3. Calculer le score_total cumulé (toutes sessions)
    $stmt_score = $pdo->prepare("
        SELECT COALESCE(SUM(`score`), 0) AS score_total
        FROM `sessions_activite`
        WHERE `utilisateur_id` = ?
    ");
    $stmt_score->execute([$user_id]);
    $result_score = $stmt_score->fetch();
    $score_total = (int) $result_score['score_total'];

    // 4. Logique de rang ─────────────────────────────────────────────────────
    // Définition des paliers : rang actuel => points nécessaires pour avancer
    $paliers = [
        'Débutant'      => ['seuil' => 10000, 'suivant' => 'Amateur'],
        'Amateur'       => ['seuil' => 20000, 'suivant' => 'Intermédiaire'],
        'Intermédiaire' => ['seuil' => 30000, 'suivant' => 'Haut Niveau'],
        'Haut Niveau'   => ['seuil' => 40000, 'suivant' => 'Expert'],
        'Expert'        => ['seuil' => 60000, 'suivant' => 'Maître'],
        'Maître'        => ['seuil' => null,  'suivant' => null],  // rang final
    ];

    // Récupérer le rang et score_palier actuels de l'utilisateur
    $stmt_rang = $pdo->prepare("
        SELECT `rang`, `score_palier` FROM `utilisateurs` WHERE `ID` = ?
    ");
    $stmt_rang->execute([$user_id]);
    $user_data = $stmt_rang->fetch();

    $rang_actuel   = $user_data['rang']         ?? 'Débutant';
    $score_palier  = (int)($user_data['score_palier'] ?? 0);
    $promoted      = false;
    $nouveau_rang  = $rang_actuel;

    // Ajouter les points de la session au palier courant
    $score_palier += $score;

    // Vérifier si un palier est atteint (boucle pour les promotions successives)
    while (
        isset($paliers[$nouveau_rang]) &&
        $paliers[$nouveau_rang]['seuil'] !== null &&
        $score_palier >= $paliers[$nouveau_rang]['seuil']
    ) {
        $score_palier -= $paliers[$nouveau_rang]['seuil'];  // réinitialiser (soustraire le seuil)
        $nouveau_rang  = $paliers[$nouveau_rang]['suivant'];
        $promoted      = true;
    }

    // 5. Mettre à jour l'utilisateur
    $stmt_update = $pdo->prepare("
        UPDATE `utilisateurs`
        SET `score_total`  = :score_total,
            `progression`  = :progression,
            `rang`         = :rang,
            `score_palier` = :score_palier
        WHERE `ID` = :user_id
    ");
    $stmt_update->bindParam(':score_total',  $score_total,  PDO::PARAM_INT);
    $stmt_update->bindParam(':progression',  $progression,  PDO::PARAM_STR);
    $stmt_update->bindParam(':rang',         $nouveau_rang, PDO::PARAM_STR);
    $stmt_update->bindParam(':score_palier', $score_palier, PDO::PARAM_INT);
    $stmt_update->bindParam(':user_id',      $user_id,      PDO::PARAM_INT);
    $stmt_update->execute();

    $pdo->commit();

    // --- Réponse de succès ---
    // Calculer le prochain seuil pour l'affichage côté client
    $seuil_suivant = $paliers[$nouveau_rang]['seuil'] ?? null;

    http_response_code(200);
    echo json_encode([
        'success'       => true,
        'session_id'    => $session_id,
        'score_total'   => $score_total,
        'progression'   => (float) $progression,
        'rang'          => $nouveau_rang,
        'score_palier'  => $score_palier,
        'seuil_suivant' => $seuil_suivant,
        'promoted'      => $promoted,
        'ancien_rang'   => $promoted ? $rang_actuel : null,
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
