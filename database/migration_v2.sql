-- ============================================================
-- Migration V2 — Plateforme Interactive TOIC / TOEFL
-- Ajout : score_total, progression, type 'examen'
-- ============================================================

USE `Plateforme_Interactive_TOIC_TOEFL`;

-- 1. Ajout des colonnes score_total et progression à la table utilisateurs
ALTER TABLE `utilisateurs`
  ADD COLUMN IF NOT EXISTS `score_total` INT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Score cumulé de toutes les sessions',
  ADD COLUMN IF NOT EXISTS `progression` DECIMAL(5,2) NOT NULL DEFAULT 0.00
    COMMENT 'Pourcentage de progression globale (0.00 à 100.00)';

-- 2. Modification de l'ENUM type_activite pour inclure 'examen'
ALTER TABLE `sessions_activite`
  MODIFY COLUMN `type_activite` ENUM(
    'qcm',
    'mini_test',
    'examen',
    'examen_audio',
    'examen_photos',
    'texte_trou'
  ) NOT NULL COMMENT 'Module concerné';

ALTER TABLE `resultats`
  MODIFY COLUMN `type_activite` ENUM(
    'qcm',
    'mini_test',
    'examen',
    'examen_audio',
    'examen_photos',
    'texte_trou'
  ) NOT NULL;

-- ============================================================
-- FIN DE LA MIGRATION V2
-- ============================================================
