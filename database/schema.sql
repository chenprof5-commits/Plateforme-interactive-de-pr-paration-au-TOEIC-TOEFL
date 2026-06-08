-- ============================================================
--  Plateforme Interactive TOIC / TOEFL  –  Schéma complet
--  Base de données : Plateforme_Interactive_TOIC_TOEFL
--  Encodage       : UTF-8 mb4  (supporte tous les caractères
--                   Unicode, emojis inclus)
--  Moteur         : InnoDB  (transactions + clés étrangères)
-- ============================================================

-- ------------------------------------------------------------
-- 0. Création / sélection de la base
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `Plateforme_Interactive_TOIC_TOEFL`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `Plateforme_Interactive_TOIC_TOEFL`;

-- ------------------------------------------------------------
-- 1. Liste des numéros INE autorisés à s'inscrire
--    (table de référence renseignée par l'administrateur)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Liste_INE` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `numero_INE` VARCHAR(30)   NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_liste_ine_numero` (`numero_INE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Numéros INE autorisés à créer un compte';

-- ------------------------------------------------------------
-- 2. Utilisateurs (étudiants inscrits)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `ID`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(100)  NOT NULL,
  `prenons`     VARCHAR(100)  NOT NULL,
  `INE`         VARCHAR(30)   NOT NULL,
  `classe`      VARCHAR(100)  NOT NULL,
  `email`       VARCHAR(255)  NOT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL   COMMENT 'Haché avec password_hash()',
  `created_at`  TIMESTAMP     NOT NULL   DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_utilisateurs_ine`   (`INE`),
  UNIQUE KEY `uq_utilisateurs_email` (`email`),
  CONSTRAINT `fk_utilisateurs_liste_ine`
    FOREIGN KEY (`INE`) REFERENCES `Liste_INE` (`numero_INE`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Comptes étudiants de la plateforme';

-- ------------------------------------------------------------
-- 3. Questions QCM  (module QCM – grammaire, texte seul)
--    Correspond à questions.json
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions_qcm` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(20)   NOT NULL             COMMENT 'Ex : q1, q2 …',
  `texte`        TEXT          NOT NULL             COMMENT 'Énoncé de la question',
  `option_a`     VARCHAR(500)  NOT NULL,
  `option_b`     VARCHAR(500)  NOT NULL,
  `option_c`     VARCHAR(500)  NOT NULL,
  `reponse`      ENUM('a','b','c') NOT NULL         COMMENT 'Lettre de la bonne réponse',
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qcm_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Questions QCM de grammaire (texte uniquement)';

-- ------------------------------------------------------------
-- 4. Questions Mini-Test  (audio + photo, 3 options)
--    Correspond à mini-test.json
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions_mini_test` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(20)   NOT NULL             COMMENT 'Ex : q101, q102 …',
  `audio`        VARCHAR(500)  NOT NULL             COMMENT 'Chemin relatif vers le fichier audio',
  `image`        VARCHAR(500)  NOT NULL             COMMENT 'Chemin relatif vers la photo',
  `option_a`     VARCHAR(500)  NOT NULL,
  `option_b`     VARCHAR(500)  NOT NULL,
  `option_c`     VARCHAR(500)  NOT NULL,
  `reponse`      ENUM('a','b','c') NOT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_minitest_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Questions du Mini-Test (audio + image, 3 options)';

-- ------------------------------------------------------------
-- 5. Questions Examen – partie Audio seul (3 options)
--    Correspond à examen2.json
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions_examen_audio` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(20)   NOT NULL             COMMENT 'Ex : q1000001 …',
  `audio`        VARCHAR(500)  NOT NULL,
  `option_a`     VARCHAR(500)  NOT NULL,
  `option_b`     VARCHAR(500)  NOT NULL,
  `option_c`     VARCHAR(500)  NOT NULL,
  `reponse`      ENUM('a','b','c') NOT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_examen_audio_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Questions Examen – audio seul (3 options)';

-- ------------------------------------------------------------
-- 6. Questions Examen – partie Photos (audio + image, 4 options)
--    Correspond à examen-photographies.json
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions_examen_photos` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(20)   NOT NULL             COMMENT 'Ex : q10001 …',
  `audio`        VARCHAR(500)  NOT NULL,
  `image`        VARCHAR(500)  NOT NULL,
  `option_a`     VARCHAR(500)  NOT NULL,
  `option_b`     VARCHAR(500)  NOT NULL,
  `option_c`     VARCHAR(500)  NOT NULL,
  `option_d`     VARCHAR(500)  NOT NULL,
  `reponse`      ENUM('a','b','c','d') NOT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_examen_photos_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Questions Examen – photo + audio (4 options)';

-- ------------------------------------------------------------
-- 7. Questions Texte à trou  (audio + énoncé, 3 options)
--    Correspond à texte-à-trou.json
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions_texte_trou` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(20)   NOT NULL             COMMENT 'Ex : q1, q2, q21 …',
  `audio`        VARCHAR(500)  NOT NULL,
  `texte`        TEXT          NOT NULL             COMMENT 'Phrase avec le trou à compléter',
  `option_a`     VARCHAR(500)  NOT NULL,
  `option_b`     VARCHAR(500)  NOT NULL,
  `option_c`     VARCHAR(500)  NOT NULL,
  `reponse`      ENUM('a','b','c') NOT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_texte_trou_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Questions Texte à trou (audio + phrase, 3 options)';

-- ------------------------------------------------------------
-- 8. Sessions d'activité  (chaque tentative d'un module)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions_activite` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `utilisateur_id`   INT UNSIGNED  NOT NULL,
  `type_activite`    ENUM(
                       'qcm',
                       'mini_test',
                       'examen_audio',
                       'examen_photos',
                       'texte_trou'
                     ) NOT NULL                    COMMENT 'Module concerné',
  `score`            TINYINT UNSIGNED NOT NULL DEFAULT 0
                                                   COMMENT 'Nombre de bonnes réponses',
  `total_questions`  TINYINT UNSIGNED NOT NULL DEFAULT 0
                                                   COMMENT 'Nombre total de questions',
  `duree_secondes`   SMALLINT UNSIGNED NULL         COMMENT 'Durée de la session (s)',
  `commence_le`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `termine_le`       TIMESTAMP     NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_session_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`ID`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Enregistrement de chaque tentative (session) par utilisateur';

-- ------------------------------------------------------------
-- 9. Résultats détaillés  (réponse par réponse)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resultats` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `session_id`       INT UNSIGNED  NOT NULL,
  `utilisateur_id`   INT UNSIGNED  NOT NULL,
  `type_activite`    ENUM(
                       'qcm',
                       'mini_test',
                       'examen_audio',
                       'examen_photos',
                       'texte_trou'
                     ) NOT NULL,
  `question_code`    VARCHAR(20)   NOT NULL  COMMENT 'code de la question (ex : q7)',
  `reponse_donnee`   VARCHAR(5)    NOT NULL  COMMENT 'Lettre choisie par l''etudiant',
  `est_correcte`     TINYINT(1)    NOT NULL  DEFAULT 0
                                             COMMENT '1 = bonne réponse, 0 = mauvaise',
  `repondu_le`       TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resultat_session`     (`session_id`),
  KEY `idx_resultat_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_resultat_session`
    FOREIGN KEY (`session_id`) REFERENCES `sessions_activite` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_resultat_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`ID`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Détail de chaque réponse par session';

-- ============================================================
-- DONNÉES INITIALES
-- ============================================================

-- ------------------------------------------------------------
-- Numéros INE autorisés (à compléter selon votre liste réelle)
-- ------------------------------------------------------------
INSERT INTO `Liste_INE` (`numero_INE`) VALUES
  ('N0001'),
  ('N0002'),
  ('N0003'),
  ('N0004'),
  ('N0005')
ON DUPLICATE KEY UPDATE `numero_INE` = VALUES(`numero_INE`);

-- ------------------------------------------------------------
-- Questions QCM initiales  (depuis questions.json)
-- ------------------------------------------------------------
INSERT INTO `questions_qcm` (`code`, `texte`, `option_a`, `option_b`, `option_c`, `reponse`) VALUES
  ('q1',  'Which of these nouns is not countable?',                       'water',                              'animal',                         'pen',                             'a'),
  ('q2',  'Which of these statements is false?',                          'a European country',                 'a honest man',                   'a uniform',                       'b'),
  ('q3',  'Which of these statements is false?',                          'I am',                               'You is',                         'They are',                        'b'),
  ('q4',  'Which of these statements is True?',                           'he is my brothers',                  'my name is Alfred',              'Ali are a boy',                   'b'),
  ('q5',  'Which of these statements is True?',                           'I\'m sure I know the person who served us.', 'the dog who is eating is beautiful', 'I liking eating rice', 'a'),
  ('q7',  'Choose the correct sentence:',                                 'He go to school every day.',         'He goes to school every day.',   'He going to school every day.',   'b'),
  ('q8',  'What is the past tense of ''eat''?',                           'ate',                                'eated',                          'eaten',                           'a'),
  ('q9',  'Which sentence uses the correct article?',                     'She saw an elephant.',               'She saw a elephant.',            'She saw the elephant.',           'a'),
  ('q10', 'Choose the correct form:',                                     'I has a dog.',                       'I have a dog.',                  'I haved a dog.',                  'b'),
  ('q11', 'Which is the correct sentence?',                               'They is playing football.',          'They are playing football.',     'They am playing football.',       'b'),
  ('q12', 'Choose the correct comparative form:',                         'more big',                           'bigger',                         'biggest',                         'b'),
  ('q13', 'Which sentence is in the future tense?',                       'I will go to the store.',            'I went to the store.',           'I am going to the store yesterday.','a'),
  ('q14', 'Choose the correct pronoun:',                                  'Me am happy.',                       'I am happy.',                    'Mine am happy.',                  'b'),
  ('q15', 'Select the correct sentence:',                                 'She can to swim.',                   'She cans swim.',                 'She can swim.',                   'c'),
  ('q16', 'Which is the correct plural form?',                            'childs',                             'children',                       'childes',                         'b'),
  ('q17', 'Which sentence is correct?',                                   'She go to school every day.',        'She goes to school every day.',  'She going to school every day.',  'b'),
  ('q18', 'Choose the correct past tense form:',                          'He drinked water.',                  'He drank water.',                'He drunk water.',                 'b'),
  ('q20', 'Which word is a comparative?',                                 'big',                                'bigger',                         'biggest',                         'b'),
  ('q22', 'Identify the correct sentence:',                               'There is many people.',              'There are many people.',         'There be many people.',           'b'),
  ('q24', 'Which is the correct question form?',                          'Do she like coffee?',                'Does she likes coffee?',         'Does she like coffee?',           'c'),
  ('q26', 'Which sentence is in the future tense?',                       'I will go tomorrow.',                'I go tomorrow.',                 'I went tomorrow.',                'a')
ON DUPLICATE KEY UPDATE
  `texte`    = VALUES(`texte`),
  `option_a` = VALUES(`option_a`),
  `option_b` = VALUES(`option_b`),
  `option_c` = VALUES(`option_c`),
  `reponse`  = VALUES(`reponse`);

-- ------------------------------------------------------------
-- Questions Mini-Test initiales  (depuis mini-test.json)
-- ------------------------------------------------------------
INSERT INTO `questions_mini_test` (`code`, `audio`, `image`, `option_a`, `option_b`, `option_c`, `reponse`) VALUES
  ('q7',   'audios/q7.mp3',    'photographies/q7.jpg',    'she feeds her dog',        'she is playing with her dog',         'she is running with her dog',        'b'),
  ('q101', 'audios/q101.mp3',  'photographies/q101.jpg',  'he is a mechanic',         'he is a driver',                      'he is a teacher',                    'a'),
  ('q102', 'audios/q102.mp3',  'photographies/q102.jpg',  'it\'s an umbrella',        'it\'s a fruit',                       'it\'s a car',                        'c'),
  ('q103', 'audios/q103.mp3',  'photographies/q103.jpg',  'True',                     'False',                               '',                                   'b'),
  ('q104', 'audios/q104.mp3',  'photographies/q104.jpg',  'It is sixteen forty-seven on the watch.', 'It is sixteen thirty-seven on the watch', 'It is sixteen fifty-seven on the watch', 'a'),
  ('q105', 'audios/q105.mp3',  'photographies/q105.jpg',  'Tidiane is playing baseball.', 'Tidiane is sleeping.',            'Tidiane is listening to music.',     'c'),
  ('q106', 'audios/q106.mp3',  'photographies/q106.jpg',  'They are playing football', 'They are in a meeting.',            'They are sleeping.',                 'b'),
  ('q107', 'audios/q107.mp3',  'photographies/q107.jpg',  'These are lemons.',        'These are strawberries and mangoes.', 'These are strawberries',             'c'),
  ('q108', 'audios/q108.mp3',  'photographies/q108.jpg',  'Alice is running.',        'Alice is reading',                    'Alice is eating something',          'b'),
  ('q109', 'audios/q109.mp3',  'photographies/q109.jpg',  'piano',                    'guitar',                              'trumpet',                            'a'),
  ('q110', 'audios/q110.mp3',  'photographies/q110.jpg',  'he is eating',             'In the dining room.',                 'At the library.',                    'b'),
  ('q111', 'audios/q111.mp3',  'photographies/q111.jpg',  'red',                      'green',                               'blue.',                              'c'),
  ('q112', 'audios/q112.mp3',  'photographies/q112.jpg',  'Isaac is eating trees',    'Isaac is planting trees right now.',  'Isaac is cutting plants',            'b'),
  ('q113', 'audios/q113.mp3',  'photographies/q113.jpg',  'A wind turbine.',          'An environmentally unfriendly solution.', 'A saw.',                         'a'),
  ('q114', 'audios/q114.mp3',  'photographies/q114.jpg',  'a dog',                    'an eagle.',                           'a bird',                             'c'),
  ('q115', 'audios/q115.mp3',  'photographies/q115.jpg',  'True',                     'False',                               '',                                   'b'),
  ('q116', 'audios/q116.mp3',  'photographies/q116.jpg',  'a dog',                    'a cellphone.',                        'a computer',                         'b')
ON DUPLICATE KEY UPDATE
  `audio`    = VALUES(`audio`),
  `image`    = VALUES(`image`),
  `option_a` = VALUES(`option_a`),
  `option_b` = VALUES(`option_b`),
  `option_c` = VALUES(`option_c`),
  `reponse`  = VALUES(`reponse`);

-- ------------------------------------------------------------
-- Questions Examen Audio initiales  (depuis examen2.json)
-- ------------------------------------------------------------
INSERT INTO `questions_examen_audio` (`code`, `audio`, `option_a`, `option_b`, `option_c`, `reponse`) VALUES
  ('q1000001', 'audio-examen2/q1000001.mp3', 'a', 'b', 'c', 'c'),
  ('q1000002', 'audio-examen2/q1000002.mp3', 'a', 'b', 'c', 'b'),
  ('q1000003', 'audio-examen2/q1000003.mp3', 'a', 'b', 'c', 'c'),
  ('q1000004', 'audio-examen2/q1000004.mp3', 'a', 'b', 'c', 'a'),
  ('q1000005', 'audio-examen2/q1000005.mp3', 'a', 'b', 'c', 'b'),
  ('q1000006', 'audio-examen2/q1000006.mp3', 'a', 'b', 'c', 'b'),
  ('q1000007', 'audio-examen2/q1000007.mp3', 'a', 'b', 'c', 'b')
ON DUPLICATE KEY UPDATE
  `audio`    = VALUES(`audio`),
  `option_a` = VALUES(`option_a`),
  `option_b` = VALUES(`option_b`),
  `option_c` = VALUES(`option_c`),
  `reponse`  = VALUES(`reponse`);

-- ------------------------------------------------------------
-- Questions Examen Photos initiales (depuis examen-photographies.json)
-- ------------------------------------------------------------
INSERT INTO `questions_examen_photos` (`code`, `audio`, `image`, `option_a`, `option_b`, `option_c`, `option_d`, `reponse`) VALUES
  ('q10001', 'audios-examen/q10001.mp3', 'examen-photos/q10001.jpg', 'a', 'b', 'c', 'd', 'a'),
  ('q10002', 'audios-examen/q10002.mp3', 'examen-photos/q10002.jpg', 'a', 'b', 'c', 'd', 'b'),
  ('q10003', 'audios-examen/q10003.mp3', 'examen-photos/q10003.jpg', 'a', 'b', 'c', 'd', 'c'),
  ('q10004', 'audios-examen/q10004.mp3', 'examen-photos/q10004.jpg', 'a', 'b', 'c', 'd', 'd'),
  ('q10005', 'audios-examen/q10005.mp3', 'examen-photos/q10005.jpg', 'a', 'b', 'c', 'd', 'a'),
  ('q10006', 'audios-examen/q10006.mp3', 'examen-photos/q10006.jpg', 'a', 'b', 'c', 'd', 'b')
ON DUPLICATE KEY UPDATE
  `audio`    = VALUES(`audio`),
  `image`    = VALUES(`image`),
  `option_a` = VALUES(`option_a`),
  `option_b` = VALUES(`option_b`),
  `option_c` = VALUES(`option_c`),
  `option_d` = VALUES(`option_d`),
  `reponse`  = VALUES(`reponse`);

-- ------------------------------------------------------------
-- Questions Texte à trou initiales  (depuis texte-à-trou.json)
-- ------------------------------------------------------------
INSERT INTO `questions_texte_trou` (`code`, `audio`, `texte`, `option_a`, `option_b`, `option_c`, `reponse`) VALUES
  ('q1',  'audios/q1.mp3',  'Fill in: ''We have lived here _____ 2010.''',               'for',      'since',     'from',    'b'),
  ('q2',  'audios/q2.mp3',  'Choose the correct article: ''He bought _____ umbrella.''', 'a',        'an',        'the',     'b'),
  ('q19', 'audios/q19.mp3', 'When did Ali sport?',                                       'today',    'yesterday', 'not yet', 'b'),
  ('q21', 'audios/q21.mp3', 'Select the correct form: ''They _____ working now.''',      'is',       'are',       'am',      'b')
ON DUPLICATE KEY UPDATE
  `audio`    = VALUES(`audio`),
  `texte`    = VALUES(`texte`),
  `option_a` = VALUES(`option_a`),
  `option_b` = VALUES(`option_b`),
  `option_c` = VALUES(`option_c`),
  `reponse`  = VALUES(`reponse`);

-- ============================================================
-- FIN DU SCRIPT
-- ============================================================
