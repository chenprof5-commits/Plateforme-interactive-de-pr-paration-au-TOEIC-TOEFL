-- ============================================================
-- migration_rang_aiven.sql
-- Version compatible MySQL (Aiven) — sans IF NOT EXISTS
-- À exécuter UNE SEULE FOIS sur la base de production
-- ============================================================

-- 1. Ajouter la colonne `rang`
--    Si elle existe déjà, MySQL retournera une erreur qu'on peut ignorer.
ALTER TABLE `utilisateurs`
  ADD COLUMN `rang` ENUM(
    'Débutant',
    'Amateur',
    'Intermédiaire',
    'Haut Niveau',
    'Expert',
    'Maître'
  ) NOT NULL DEFAULT 'Débutant'
  COMMENT 'Rang actuel de l''utilisateur'
  AFTER `progression`;

-- 2. Ajouter la colonne `score_palier`
ALTER TABLE `utilisateurs`
  ADD COLUMN `score_palier` INT UNSIGNED NOT NULL DEFAULT 0
  COMMENT 'Points accumulés depuis la dernière promotion (remis à 0 à chaque palier)'
  AFTER `rang`;

-- 3. Initialiser tous les comptes existants au rang Débutant
UPDATE `utilisateurs`
SET `rang` = 'Débutant',
    `score_palier` = 0;

-- 4. Vérification finale
SELECT
    ID,
    nom,
    prenons,
    rang,
    score_palier,
    score_total
FROM `utilisateurs`;
