-- ============================================================
-- migration_rang.sql
-- Ajout du système de rangs avec progression par paliers
-- À exécuter UNE SEULE FOIS sur la base Aiven
-- ============================================================

-- 1. Ajouter la colonne `rang` (niveau de l'utilisateur)
ALTER TABLE `utilisateurs`
  ADD COLUMN IF NOT EXISTS `rang` ENUM(
    'Débutant',
    'Amateur',
    'Intermédiaire',
    'Haut Niveau',
    'Expert',
    'Maître'
  ) NOT NULL DEFAULT 'Débutant'
  COMMENT 'Rang actuel de l''utilisateur'
  AFTER `progression`;

-- 2. Ajouter `score_palier` : points accumulés DEPUIS la dernière promotion
--    (remis à 0 à chaque changement de rang)
ALTER TABLE `utilisateurs`
  ADD COLUMN IF NOT EXISTS `score_palier` INT UNSIGNED NOT NULL DEFAULT 0
  COMMENT 'Points accumulés depuis la dernière promotion (remis à 0 à chaque palier)'
  AFTER `rang`;

-- 3. Mettre tous les comptes existants au rang Débutant par défaut
UPDATE `utilisateurs` SET `rang` = 'Débutant', `score_palier` = 0
WHERE `rang` IS NULL OR `rang` = '';

-- 4. Vérification
SELECT 'Migration rang réussie ✓' AS statut;
SELECT ID, nom, prenons, rang, score_palier FROM `utilisateurs`;
