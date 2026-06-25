-- ============================================================
-- migration_prononciation.sql
-- À exécuter UNE SEULE FOIS dans votre base de données
-- Base : Plateforme_Interactive_TOIC_TOEFL
-- ============================================================

USE `Plateforme_Interactive_TOIC_TOEFL`;

-- ── 1. Table des scores de prononciation ──────────────────────
CREATE TABLE IF NOT EXISTS `scores_prononciation` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `utilisateur_id` INT UNSIGNED      NOT NULL,
    `score`          TINYINT UNSIGNED  NOT NULL COMMENT 'Score LCS 0-100%',
    `confidence`     TINYINT UNSIGNED  NOT NULL DEFAULT 0 COMMENT 'Confiance API Speech 0-100%',
    `phrase`         VARCHAR(500)      NOT NULL COMMENT 'Texte de la phrase prononcée',
    `enregistre_le`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user`      (`utilisateur_id`),
    KEY `idx_user_date` (`utilisateur_id`, `enregistre_le`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historique des scores de prononciation par utilisateur';

-- ── 2. Ajouter le type "prononciation" dans l'ENUM de sessions_activite ──
-- Vérifie d'abord que la colonne type_activite n'a pas déjà cette valeur
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
    ) NOT NULL;

-- ── 3. Vérification ──────────────────────────────────────────
SELECT 'Migration prononciation réussie ✓' AS statut;
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'Plateforme_Interactive_TOIC_TOEFL'
  AND TABLE_NAME IN ('scores_prononciation', 'sessions_activite');
