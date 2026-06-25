-- ============================================================
--  update_database.sql
--  Script de mise à jour de la base de données
--  Plateforme Interactive TOEIC / TOEFL
--  
--  COMMENT EXÉCUTER :
--    Via phpMyAdmin → onglet SQL → coller et exécuter
--    OU via ligne de commande :
--    mysql -u root plateforme_interactive_TOIC_TOEFL < update_database.sql
--
--  Ce script est IDEMPOTENT : peut être exécuté plusieurs fois
--  sans risque de doublons ni d'erreurs.
-- ============================================================

USE `Plateforme_Interactive_TOIC_TOEFL`;

SELECT '=========================================' AS '';
SELECT '  DÉBUT DE LA MISE À JOUR' AS '';
SELECT '=========================================' AS '';

-- ────────────────────────────────────────────────────────────
-- ÉTAPE 1 : Colonnes manquantes dans `utilisateurs`
-- ────────────────────────────────────────────────────────────
SELECT 'ÉTAPE 1 : Vérification de la table utilisateurs...' AS '';

-- Ajouter score_total si absent
ALTER TABLE `utilisateurs`
  ADD COLUMN IF NOT EXISTS `score_total` INT UNSIGNED NOT NULL DEFAULT 0
  COMMENT 'Somme cumulée des bonnes réponses';

-- Ajouter progression si absente
ALTER TABLE `utilisateurs`
  ADD COLUMN IF NOT EXISTS `progression` DECIMAL(5,2) NOT NULL DEFAULT 0.00
  COMMENT 'Pourcentage de progression globale (0-100)';

SELECT '  ✓ Table utilisateurs mise à jour' AS '';

-- ────────────────────────────────────────────────────────────
-- ÉTAPE 2 : Mise à jour de l'ENUM de sessions_activite
--           Ajouter : examen, prononciation
-- ────────────────────────────────────────────────────────────
SELECT 'ÉTAPE 2 : Mise à jour de l ENUM sessions_activite...' AS '';

ALTER TABLE `sessions_activite`
  MODIFY COLUMN `type_activite`
  ENUM(
    'qcm',
    'mini_test',
    'examen',
    'examen_audio',
    'examen_photos',
    'texte_trou',
    'prononciation'
  ) NOT NULL COMMENT 'Module concerné';

-- Colonne score : passer de TINYINT (max 255) à SMALLINT (max 65535)
-- pour les modules avec beaucoup de questions
ALTER TABLE `sessions_activite`
  MODIFY COLUMN `score`           SMALLINT UNSIGNED NOT NULL DEFAULT 0
                                  COMMENT 'Nombre de bonnes réponses (ou score %)',
  MODIFY COLUMN `total_questions` SMALLINT UNSIGNED NOT NULL DEFAULT 0
                                  COMMENT 'Total de questions (ou 100 pour les scores %)';

SELECT '  ✓ Table sessions_activite mise à jour' AS '';

-- ────────────────────────────────────────────────────────────
-- ÉTAPE 3 : Même ENUM dans la table resultats
-- ────────────────────────────────────────────────────────────
SELECT 'ÉTAPE 3 : Mise à jour de l ENUM resultats...' AS '';

ALTER TABLE `resultats`
  MODIFY COLUMN `type_activite`
  ENUM(
    'qcm',
    'mini_test',
    'examen',
    'examen_audio',
    'examen_photos',
    'texte_trou',
    'prononciation'
  ) NOT NULL;

SELECT '  ✓ Table resultats mise à jour' AS '';

-- ────────────────────────────────────────────────────────────
-- ÉTAPE 4 : Création de la table scores_prononciation
-- ────────────────────────────────────────────────────────────
SELECT 'ÉTAPE 4 : Création de la table scores_prononciation...' AS '';

CREATE TABLE IF NOT EXISTS `scores_prononciation` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `utilisateur_id`  INT UNSIGNED      NOT NULL,
  `score`           TINYINT UNSIGNED  NOT NULL DEFAULT 0
                    COMMENT 'Score LCS 0-100%',
  `similarite`      TINYINT UNSIGNED  NOT NULL DEFAULT 0
                    COMMENT 'Similarité Levenshtein 0-100%',
  `phrase`          VARCHAR(500)      NOT NULL
                    COMMENT 'Texte de la phrase prononcée',
  `transcription`   VARCHAR(500)      NOT NULL DEFAULT ''
                    COMMENT 'Ce que le système a entendu',
  `enregistre_le`   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prononciation_user`      (`utilisateur_id`),
  KEY `idx_prononciation_user_date` (`utilisateur_id`, `enregistre_le`),
  CONSTRAINT `fk_prononciation_user`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`ID`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historique des scores de prononciation par utilisateur';

SELECT '  ✓ Table scores_prononciation créée (ou déjà existante)' AS '';

-- ────────────────────────────────────────────────────────────
-- ÉTAPE 5 : Création de la table talks_sessions
--           (pour le module Talks)
-- ────────────────────────────────────────────────────────────
SELECT 'ÉTAPE 5 : Création de la table talks_sessions...' AS '';

CREATE TABLE IF NOT EXISTS `talks_sessions` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `utilisateur_id`  INT UNSIGNED      NOT NULL,
  `score`           TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `total`           TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `duree_secondes`  SMALLINT UNSIGNED NULL,
  `enregistre_le`   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_talks_user` (`utilisateur_id`),
  CONSTRAINT `fk_talks_user`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`ID`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Scores du module Talks (audio + 3 questions)';

SELECT '  ✓ Table talks_sessions créée (ou déjà existante)' AS '';

-- ────────────────────────────────────────────────────────────
-- ÉTAPE 6 : Création de la table comprehension_sessions
--           (pour le module Compréhension Écrite)
-- ────────────────────────────────────────────────────────────
SELECT 'ÉTAPE 6 : Création de la table comprehension_sessions...' AS '';

CREATE TABLE IF NOT EXISTS `comprehension_sessions` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `utilisateur_id`  INT UNSIGNED      NOT NULL,
  `score`           TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `total`           TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `duree_secondes`  SMALLINT UNSIGNED NULL,
  `enregistre_le`   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comprehension_user` (`utilisateur_id`),
  CONSTRAINT `fk_comprehension_user`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`ID`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Scores du module Compréhension Écrite';

SELECT '  ✓ Table comprehension_sessions créée (ou déjà existante)' AS '';

-- ────────────────────────────────────────────────────────────
-- ÉTAPE 7 : Recalcul de progression pour tous les utilisateurs
--           (remet à zéro les progressions incohérentes)
-- ────────────────────────────────────────────────────────────
SELECT 'ÉTAPE 7 : Recalcul des progressions utilisateurs...' AS '';

UPDATE `utilisateurs` u
SET
  `score_total` = (
    SELECT COALESCE(SUM(sa.`score`), 0)
    FROM `sessions_activite` sa
    WHERE sa.`utilisateur_id` = u.`ID`
  ),
  `progression` = LEAST(100, ROUND(
    (
      SELECT COUNT(DISTINCT
        CASE
          WHEN sa.`type_activite` IN ('examen','examen_audio','examen_photos') THEN 'examen'
          ELSE sa.`type_activite`
        END
      )
      FROM `sessions_activite` sa
      WHERE sa.`utilisateur_id` = u.`ID`
        AND sa.`type_activite` IN (
          'qcm','mini_test','examen','examen_audio',
          'examen_photos','texte_trou','prononciation'
        )
    ) / 5.0 * 100
  , 2));

SELECT CONCAT('  ✓ ', ROW_COUNT(), ' utilisateur(s) mis à jour') AS '';

-- ────────────────────────────────────────────────────────────
-- ÉTAPE 8 : Vérification finale — liste des tables
-- ────────────────────────────────────────────────────────────
SELECT '=========================================' AS '';
SELECT '  VÉRIFICATION FINALE' AS '';
SELECT '=========================================' AS '';

SELECT
  TABLE_NAME          AS 'Table',
  TABLE_ROWS          AS 'Lignes (approx.)',
  ROUND(DATA_LENGTH / 1024, 1) AS 'Taille Ko'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'Plateforme_Interactive_TOIC_TOEFL'
ORDER BY TABLE_NAME;

SELECT '=========================================' AS '';
SELECT '  MISE À JOUR TERMINÉE AVEC SUCCÈS ✓' AS '';
SELECT '=========================================' AS '';
