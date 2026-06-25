<?php
/**
 * api/enregistrer_score_prononciation.php
 *
 * Endpoint POST — Enregistrer un score de prononciation
 *
 * Corps JSON attendu :
 *   - score      (int 0-100)  : score LCS en pourcentage
 *   - confidence (int 0-100)  : confiance API en pourcentage
 *   - phrase     (string)     : texte de la phrase prononcée
 *   - total      (int)        : toujours 100 (score sur 100)
 *
 * Réponse JSON :
 *   - success, score_id, score_moyen, meilleur_score, nb_sessions, niveau_atteint
 */

require_once __DIR__ . '/session_check.php';

// ── Méthode HTTP ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée. Utilisez POST.']);
    exit();
}

// ── Lecture du corps JSON ──
$input = json_decode(file_get_contents('php://input'), true);

if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Corps JSON invalide']);
    exit();
}

// ── Extraction et validation ──
$score      = isset($input['score'])      ? (int) $input['score']      : null;
$confidence = isset($input['confidence']) ? (int) $input['confidence'] : 0;
$phrase     = isset($input['phrase'])     ? trim($input['phrase'])      : '';
$total      = isset($input['total'])      ? (int) $input['total']       : 100;

if ($score === null || $score < 0 || $score > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Score invalide (doit être entre 0 et 100)']);
    exit();
}

if (empty($phrase)) {
    http_response_code(400);
    echo json_encode(['error' => 'La phrase est obligatoire']);
    exit();
}

$confidence = max(0, min(100, $confidence));

try {
    $pdo->beginTransaction();

    // ── 1. Créer la table si elle n'existe pas ──
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `scores_prononciation` (
            `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
            `utilisateur_id`INT UNSIGNED     NOT NULL,
            `score`         TINYINT UNSIGNED NOT NULL COMMENT 'Score LCS 0-100',
            `confidence`    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Confiance API 0-100',
            `phrase`        VARCHAR(500)     NOT NULL,
            `enregistre_le` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_user_date` (`utilisateur_id`, `enregistre_le`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // ── 2. Insérer le score de prononciation ──
    $stmt = $pdo->prepare("
        INSERT INTO `scores_prononciation`
            (`utilisateur_id`, `score`, `confidence`, `phrase`)
        VALUES
            (:user_id, :score, :confidence, :phrase)
    ");
    $stmt->bindParam(':user_id',    $user_id,    PDO::PARAM_INT);
    $stmt->bindParam(':score',      $score,      PDO::PARAM_INT);
    $stmt->bindParam(':confidence', $confidence, PDO::PARAM_INT);
    $stmt->bindParam(':phrase',     $phrase,     PDO::PARAM_STR);
    $stmt->execute();

    $score_id = (int) $pdo->lastInsertId();

    // ── 3. Calculer les statistiques de prononciation de cet utilisateur ──
    $stmt_stats = $pdo->prepare("
        SELECT
            COUNT(*)             AS nb_sessions,
            AVG(`score`)         AS score_moyen,
            MAX(`score`)         AS meilleur_score,
            MIN(`score`)         AS score_min
        FROM `scores_prononciation`
        WHERE `utilisateur_id` = :user_id
    ");
    $stmt_stats->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_stats->execute();
    $stats = $stmt_stats->fetch();

    $nb_sessions    = (int)   $stats['nb_sessions'];
    $score_moyen    = round((float) $stats['score_moyen'], 1);
    $meilleur_score = (int)   $stats['meilleur_score'];

    // ── 4. Déterminer le niveau de prononciation atteint ──
    // Basé sur le score moyen sur toutes les sessions
    $niveau_atteint = 'Débutant';
    if ($score_moyen >= 85)       $niveau_atteint = 'Expert';
    elseif ($score_moyen >= 70)   $niveau_atteint = 'Avancé';
    elseif ($score_moyen >= 50)   $niveau_atteint = 'Intermédiaire';

    // ── 5. Progression globale : aussi enregistrer dans sessions_activite
    //       pour que le tableau de bord voie l'activité ──
    //       On utilise le type 'qcm' qui existe déjà dans l'ENUM,
    //       puisque la prononciation n'a pas encore son propre type.
    //       (Alternative : ALTER TABLE pour ajouter 'prononciation' à l'ENUM)
    //       Ici on tente d'ajouter le type 'prononciation' à l'ENUM si possible.
    try {
        $pdo->exec("
            ALTER TABLE `sessions_activite`
            MODIFY COLUMN `type_activite`
            ENUM('qcm','mini_test','examen','examen_audio','examen_photos','texte_trou','prononciation')
            NOT NULL
        ");
    } catch (PDOException $alterEx) {
        // L'ENUM existe peut-être déjà — silencieux
        error_log('[prononciation] ALTER ENUM ignoré : ' . $alterEx->getMessage());
    }

    // Insérer dans sessions_activite (score sur 100 = nb bonnes / 100 questions)
    $stmt_sa = $pdo->prepare("
        INSERT INTO `sessions_activite`
            (`utilisateur_id`, `type_activite`, `score`, `total_questions`, `termine_le`)
        VALUES
            (:user_id, 'prononciation', :score, 100, NOW())
    ");
    $stmt_sa->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_sa->bindParam(':score',   $score,   PDO::PARAM_INT);
    $stmt_sa->execute();

    // ── 6. Mise à jour du score_total et progression de l'utilisateur ──
    $stmt_score_total = $pdo->prepare("
        SELECT COALESCE(SUM(`score`), 0) AS score_total
        FROM `sessions_activite`
        WHERE `utilisateur_id` = ?
    ");
    $stmt_score_total->execute([$user_id]);
    $score_total = (int) $stmt_score_total->fetchColumn();

    // Progression basée sur types distincts complétés (4 types principaux)
    $stmt_prog = $pdo->prepare("
        SELECT COUNT(DISTINCT CASE
            WHEN `type_activite` IN ('examen','examen_audio','examen_photos') THEN 'examen'
            ELSE `type_activite`
        END) AS types_completes
        FROM `sessions_activite`
        WHERE `utilisateur_id` = ?
          AND `type_activite` IN ('qcm','mini_test','examen','examen_audio','examen_photos','texte_trou','prononciation')
    ");
    $stmt_prog->execute([$user_id]);
    $types_completes = (int) $stmt_prog->fetchColumn();

    // Progression sur 5 types maintenant (prononciation inclus)
    $progression = round(($types_completes / 5) * 100, 2);

    $stmt_update = $pdo->prepare("
        UPDATE `utilisateurs`
        SET `score_total` = :score_total,
            `progression` = :progression
        WHERE `ID` = :user_id
    ");
    $stmt_update->bindParam(':score_total', $score_total, PDO::PARAM_INT);
    $stmt_update->bindParam(':progression', $progression, PDO::PARAM_STR);
    $stmt_update->bindParam(':user_id',     $user_id,     PDO::PARAM_INT);
    $stmt_update->execute();

    $pdo->commit();

    // ── Réponse de succès ──
    http_response_code(200);
    echo json_encode([
        'success'        => true,
        'score_id'       => $score_id,
        'score_moyen'    => $score_moyen,
        'meilleur_score' => $meilleur_score,
        'nb_sessions'    => $nb_sessions,
        'niveau_atteint' => $niveau_atteint,
        'score_total'    => $score_total,
        'progression'    => (float) $progression
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erreur enregistrer_score_prononciation.php : ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de l\'enregistrement du score de prononciation']);
}
