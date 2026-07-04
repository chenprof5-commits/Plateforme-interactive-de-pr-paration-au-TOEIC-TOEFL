-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 04 juil. 2026 à 16:19
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12
--
-- Corrigé pour Aiven (sql_require_primary_key=ON) :
-- PRIMARY KEY et AUTO_INCREMENT déclarés inline dans chaque CREATE TABLE.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `plateforme_interactive_toic_toefl`
--

-- --------------------------------------------------------

--
-- Structure de la table `comprehension_sessions`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
--

CREATE TABLE `comprehension_sessions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `score` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `total` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `duree_secondes` smallint(5) UNSIGNED DEFAULT NULL,
  `enregistre_le` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_comprehension_user` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Scores du module Compréhension Écrite';

-- --------------------------------------------------------

--
-- Structure de la table `liste_ine`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
--

CREATE TABLE `liste_ine` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero_INE` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_liste_ine_numero` (`numero_INE`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Numéros INE autorisés à créer un compte';

--
-- Déchargement des données de la table `liste_ine`
--

INSERT INTO `liste_ine` (`id`, `numero_INE`) VALUES
(1, 'N0001'),
(2, 'N0002'),
(3, 'N0003'),
(4, 'N0004'),
(5, 'N0005'),
(6, 'N111');

-- --------------------------------------------------------

--
-- Structure de la table `questions_examen_audio`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
--

CREATE TABLE `questions_examen_audio` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL COMMENT 'Ex : q1000001',
  `audio` varchar(500) NOT NULL,
  `option_a` varchar(500) NOT NULL,
  `option_b` varchar(500) NOT NULL,
  `option_c` varchar(500) NOT NULL,
  `reponse` enum('a','b','c') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_examen_audio_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Questions Examen audio seul (3 options)';

--
-- Déchargement des données de la table `questions_examen_audio`
--

INSERT INTO `questions_examen_audio` (`id`, `code`, `audio`, `option_a`, `option_b`, `option_c`, `reponse`, `created_at`) VALUES
(1, 'q1000001', 'audio-examen2/q1000001.mp3', 'a', 'b', 'c', 'c', '2026-06-02 14:05:36'),
(2, 'q1000002', 'audio-examen2/q1000002.mp3', 'a', 'b', 'c', 'b', '2026-06-02 14:05:36'),
(3, 'q1000003', 'audio-examen2/q1000003.mp3', 'a', 'b', 'c', 'c', '2026-06-02 14:05:36'),
(4, 'q1000004', 'audio-examen2/q1000004.mp3', 'a', 'b', 'c', 'a', '2026-06-02 14:05:36'),
(5, 'q1000005', 'audio-examen2/q1000005.mp3', 'a', 'b', 'c', 'b', '2026-06-02 14:05:36'),
(6, 'q1000006', 'audio-examen2/q1000006.mp3', 'a', 'b', 'c', 'b', '2026-06-02 14:05:36'),
(7, 'q1000007', 'audio-examen2/q1000007.mp3', 'a', 'b', 'c', 'b', '2026-06-02 14:05:36');

-- --------------------------------------------------------

--
-- Structure de la table `questions_examen_photos`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
--

CREATE TABLE `questions_examen_photos` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL COMMENT 'Ex : q10001',
  `audio` varchar(500) NOT NULL,
  `image` varchar(500) NOT NULL,
  `option_a` varchar(500) NOT NULL,
  `option_b` varchar(500) NOT NULL,
  `option_c` varchar(500) NOT NULL,
  `option_d` varchar(500) NOT NULL,
  `reponse` enum('a','b','c','d') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_examen_photos_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Questions Examen photo + audio (4 options)';

--
-- Déchargement des données de la table `questions_examen_photos`
--

INSERT INTO `questions_examen_photos` (`id`, `code`, `audio`, `image`, `option_a`, `option_b`, `option_c`, `option_d`, `reponse`, `created_at`) VALUES
(1, 'q10001', 'audios-examen/q10001.mp3', 'examen-photos/q10001.jpg', 'a', 'b', 'c', 'd', 'a', '2026-06-02 14:05:36'),
(2, 'q10002', 'audios-examen/q10002.mp3', 'examen-photos/q10002.jpg', 'a', 'b', 'c', 'd', 'b', '2026-06-02 14:05:36'),
(3, 'q10003', 'audios-examen/q10003.mp3', 'examen-photos/q10003.jpg', 'a', 'b', 'c', 'd', 'c', '2026-06-02 14:05:36'),
(4, 'q10004', 'audios-examen/q10004.mp3', 'examen-photos/q10004.jpg', 'a', 'b', 'c', 'd', 'd', '2026-06-02 14:05:36'),
(5, 'q10005', 'audios-examen/q10005.mp3', 'examen-photos/q10005.jpg', 'a', 'b', 'c', 'd', 'a', '2026-06-02 14:05:36'),
(6, 'q10006', 'audios-examen/q10006.mp3', 'examen-photos/q10006.jpg', 'a', 'b', 'c', 'd', 'b', '2026-06-02 14:05:36');

-- --------------------------------------------------------

--
-- Structure de la table `questions_mini_test`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
--

CREATE TABLE `questions_mini_test` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL COMMENT 'Ex : q101, q102',
  `audio` varchar(500) NOT NULL COMMENT 'Chemin relatif vers le fichier audio',
  `image` varchar(500) NOT NULL COMMENT 'Chemin relatif vers la photo',
  `option_a` varchar(500) NOT NULL,
  `option_b` varchar(500) NOT NULL,
  `option_c` varchar(500) NOT NULL,
  `reponse` enum('a','b','c') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_minitest_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Questions du Mini-Test (audio + image, 3 options)';

--
-- Déchargement des données de la table `questions_mini_test`
--

INSERT INTO `questions_mini_test` (`id`, `code`, `audio`, `image`, `option_a`, `option_b`, `option_c`, `reponse`, `created_at`) VALUES
(1, 'q7', 'audios/q7.mp3', 'photographies/q7.jpg', 'she feeds her dog', 'she is playing with her dog', 'she is running with her dog', 'b', '2026-06-02 14:05:36'),
(2, 'q101', 'audios/q101.mp3', 'photographies/q101.jpg', 'he is a mechanic', 'he is a driver', 'he is a teacher', 'a', '2026-06-02 14:05:36'),
(3, 'q102', 'audios/q102.mp3', 'photographies/q102.jpg', 'it\'s an umbrella', 'it\'s a fruit', 'it\'s a car', 'c', '2026-06-02 14:05:36'),
(4, 'q103', 'audios/q103.mp3', 'photographies/q103.jpg', 'True', 'False', '', 'b', '2026-06-02 14:05:36'),
(5, 'q104', 'audios/q104.mp3', 'photographies/q104.jpg', 'It is sixteen forty-seven on the watch.', 'It is sixteen thirty-seven on the watch', 'It is sixteen fifty-seven on the watch', 'a', '2026-06-02 14:05:36'),
(6, 'q105', 'audios/q105.mp3', 'photographies/q105.jpg', 'Tidiane is playing baseball.', 'Tidiane is sleeping.', 'Tidiane is listening to music.', 'c', '2026-06-02 14:05:36'),
(7, 'q106', 'audios/q106.mp3', 'photographies/q106.jpg', 'They are playing football', 'They are in a meeting.', 'They are sleeping.', 'b', '2026-06-02 14:05:36'),
(8, 'q107', 'audios/q107.mp3', 'photographies/q107.jpg', 'These are lemons.', 'These are strawberries and mangoes.', 'These are strawberries', 'c', '2026-06-02 14:05:36'),
(9, 'q108', 'audios/q108.mp3', 'photographies/q108.jpg', 'Alice is running.', 'Alice is reading', 'Alice is eating something', 'b', '2026-06-02 14:05:36'),
(10, 'q109', 'audios/q109.mp3', 'photographies/q109.jpg', 'piano', 'guitar', 'trumpet', 'a', '2026-06-02 14:05:36'),
(11, 'q110', 'audios/q110.mp3', 'photographies/q110.jpg', 'he is eating', 'In the dining room.', 'At the library.', 'b', '2026-06-02 14:05:36'),
(12, 'q111', 'audios/q111.mp3', 'photographies/q111.jpg', 'red', 'green', 'blue.', 'c', '2026-06-02 14:05:36'),
(13, 'q112', 'audios/q112.mp3', 'photographies/q112.jpg', 'Isaac is eating trees', 'Isaac is planting trees right now.', 'Isaac is cutting plants', 'b', '2026-06-02 14:05:36'),
(14, 'q113', 'audios/q113.mp3', 'photographies/q113.jpg', 'A wind turbine.', 'An environmentally unfriendly solution.', 'A saw.', 'a', '2026-06-02 14:05:36'),
(15, 'q114', 'audios/q114.mp3', 'photographies/q114.jpg', 'a dog', 'an eagle.', 'a bird', 'c', '2026-06-02 14:05:36'),
(16, 'q115', 'audios/q115.mp3', 'photographies/q115.jpg', 'True', 'False', '', 'b', '2026-06-02 14:05:36'),
(17, 'q116', 'audios/q116.mp3', 'photographies/q116.jpg', 'a dog', 'a cellphone.', 'a computer', 'b', '2026-06-02 14:05:36');

-- --------------------------------------------------------

--
-- Structure de la table `questions_qcm`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
--

CREATE TABLE `questions_qcm` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL COMMENT 'Ex : q1, q2',
  `texte` text NOT NULL COMMENT 'Énoncé de la question',
  `option_a` varchar(500) NOT NULL,
  `option_b` varchar(500) NOT NULL,
  `option_c` varchar(500) NOT NULL,
  `reponse` enum('a','b','c') NOT NULL COMMENT 'Lettre de la bonne réponse',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qcm_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Questions QCM de grammaire (texte uniquement)';

--
-- Déchargement des données de la table `questions_qcm`
--

INSERT INTO `questions_qcm` (`id`, `code`, `texte`, `option_a`, `option_b`, `option_c`, `reponse`, `created_at`) VALUES
(1, 'q1', 'Which of these nouns is not countable?', 'water', 'animal', 'pen', 'a', '2026-06-02 14:05:36'),
(2, 'q2', 'Which of these statements is false?', 'a European country', 'a honest man', 'a uniform', 'b', '2026-06-02 14:05:36'),
(3, 'q3', 'Which of these statements is false?', 'I am', 'You is', 'They are', 'b', '2026-06-02 14:05:36'),
(4, 'q4', 'Which of these statements is True?', 'he is my brothers', 'my name is Alfred', 'Ali are a boy', 'b', '2026-06-02 14:05:36'),
(5, 'q5', 'Which of these statements is True?', 'I\'m sure I know the person who served us.', 'the dog who is eating is beautiful', 'I liking eating rice', 'a', '2026-06-02 14:05:36'),
(6, 'q7', 'Choose the correct sentence:', 'He go to school every day.', 'He goes to school every day.', 'He going to school every day.', 'b', '2026-06-02 14:05:36'),
(7, 'q8', 'What is the past tense of \'eat\'?', 'ate', 'eated', 'eaten', 'a', '2026-06-02 14:05:36'),
(8, 'q9', 'Which sentence uses the correct article?', 'She saw an elephant.', 'She saw a elephant.', 'She saw the elephant.', 'a', '2026-06-02 14:05:36'),
(9, 'q10', 'Choose the correct form:', 'I has a dog.', 'I have a dog.', 'I haved a dog.', 'b', '2026-06-02 14:05:36'),
(10, 'q11', 'Which is the correct sentence?', 'They is playing football.', 'They are playing football.', 'They am playing football.', 'b', '2026-06-02 14:05:36'),
(11, 'q12', 'Choose the correct comparative form:', 'more big', 'bigger', 'biggest', 'b', '2026-06-02 14:05:36'),
(12, 'q13', 'Which sentence is in the future tense?', 'I will go to the store.', 'I went to the store.', 'I am going to the store yesterday.', 'a', '2026-06-02 14:05:36'),
(13, 'q14', 'Choose the correct pronoun:', 'Me am happy.', 'I am happy.', 'Mine am happy.', 'b', '2026-06-02 14:05:36'),
(14, 'q15', 'Select the correct sentence:', 'She can to swim.', 'She cans swim.', 'She can swim.', 'c', '2026-06-02 14:05:36'),
(15, 'q16', 'Which is the correct plural form?', 'childs', 'children', 'childes', 'b', '2026-06-02 14:05:36'),
(16, 'q17', 'Which sentence is correct?', 'She go to school every day.', 'She goes to school every day.', 'She going to school every day.', 'b', '2026-06-02 14:05:36'),
(17, 'q18', 'Choose the correct past tense form:', 'He drinked water.', 'He drank water.', 'He drunk water.', 'b', '2026-06-02 14:05:36'),
(18, 'q20', 'Which word is a comparative?', 'big', 'bigger', 'biggest', 'b', '2026-06-02 14:05:36'),
(19, 'q22', 'Identify the correct sentence:', 'There is many people.', 'There are many people.', 'There be many people.', 'b', '2026-06-02 14:05:36'),
(20, 'q24', 'Which is the correct question form?', 'Do she like coffee?', 'Does she likes coffee?', 'Does she like coffee?', 'c', '2026-06-02 14:05:36'),
(21, 'q26', 'Which sentence is in the future tense?', 'I will go tomorrow.', 'I go tomorrow.', 'I went tomorrow.', 'a', '2026-06-02 14:05:36');

-- --------------------------------------------------------

--
-- Structure de la table `questions_texte_trou`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
--

CREATE TABLE `questions_texte_trou` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL COMMENT 'Ex : q1, q2, q21',
  `audio` varchar(500) NOT NULL,
  `texte` text NOT NULL COMMENT 'Phrase avec le trou à compléter',
  `option_a` varchar(500) NOT NULL,
  `option_b` varchar(500) NOT NULL,
  `option_c` varchar(500) NOT NULL,
  `reponse` enum('a','b','c') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_texte_trou_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Questions Texte à trou (audio + phrase, 3 options)';

--
-- Déchargement des données de la table `questions_texte_trou`
--

INSERT INTO `questions_texte_trou` (`id`, `code`, `audio`, `texte`, `option_a`, `option_b`, `option_c`, `reponse`, `created_at`) VALUES
(1, 'q1', 'audios/q1.mp3', 'Fill in: \'We have lived here _____ 2010.\'', 'for', 'since', 'from', 'b', '2026-06-02 14:05:36'),
(2, 'q2', 'audios/q2.mp3', 'Choose the correct article: \'He bought _____ umbrella.\'', 'a', 'an', 'the', 'b', '2026-06-02 14:05:36'),
(3, 'q19', 'audios/q19.mp3', 'When did Ali sport?', 'today', 'yesterday', 'not yet', 'b', '2026-06-02 14:05:36'),
(4, 'q21', 'audios/q21.mp3', 'Select the correct form: \'They _____ working now.\'', 'is', 'are', 'am', 'b', '2026-06-02 14:05:36');

-- --------------------------------------------------------

--
-- Structure de la table `resultats`
-- CORRIGÉ : PRIMARY KEY déplacée inline
--

CREATE TABLE `resultats` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` int(10) UNSIGNED NOT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `type_activite` enum('qcm','mini_test','examen','examen_audio','examen_photos','texte_trou','prononciation') NOT NULL,
  `question_code` varchar(20) NOT NULL COMMENT 'code de la question (ex : q7)',
  `reponse_donnee` varchar(5) NOT NULL COMMENT 'Lettre choisie par l''etudiant',
  `est_correcte` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = bonne réponse, 0 = mauvaise',
  `repondu_le` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_resultat_session` (`session_id`),
  KEY `idx_resultat_utilisateur` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Détail de chaque réponse par session';

-- --------------------------------------------------------

--
-- Structure de la table `scores_prononciation`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
--

CREATE TABLE `scores_prononciation` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `score` tinyint(3) UNSIGNED NOT NULL COMMENT 'Score LCS 0-100%',
  `confidence` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Confiance API Speech 0-100%',
  `phrase` varchar(500) NOT NULL COMMENT 'Texte de la phrase prononcée',
  `enregistre_le` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`utilisateur_id`),
  KEY `idx_user_date` (`utilisateur_id`,`enregistre_le`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des scores de prononciation par utilisateur';

-- --------------------------------------------------------

--
-- Structure de la table `sessions_activite`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
--

CREATE TABLE `sessions_activite` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `type_activite` enum('qcm','mini_test','examen','examen_audio','examen_photos','texte_trou','prononciation') NOT NULL COMMENT 'Module concerné',
  `score` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nombre de bonnes réponses (ou score %)',
  `total_questions` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total de questions (ou 100 pour les scores %)',
  `duree_secondes` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Durée de la session (s)',
  `commence_le` timestamp NOT NULL DEFAULT current_timestamp(),
  `termine_le` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session_utilisateur` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Enregistrement de chaque tentative (session) par utilisateur';

--
-- Déchargement des données de la table `sessions_activite`
--

INSERT INTO `sessions_activite` (`id`, `utilisateur_id`, `type_activite`, `score`, `total_questions`, `duree_secondes`, `commence_le`, `termine_le`) VALUES
(17, 1, 'examen_photos', 8, 20, NULL, '2026-06-10 14:43:54', '2026-06-10 14:43:54'),
(18, 1, 'mini_test', 12, 15, NULL, '2026-06-10 14:50:27', '2026-06-10 14:50:27'),
(19, 1, 'examen_photos', 10, 20, NULL, '2026-06-10 19:56:41', '2026-06-10 19:56:41'),
(20, 1, 'examen_audio', 1, 7, NULL, '2026-06-10 20:00:29', '2026-06-10 20:00:29'),
(21, 1, 'qcm', 14, 15, NULL, '2026-06-10 20:07:26', '2026-06-10 20:07:26'),
(22, 1, 'mini_test', 10, 15, NULL, '2026-06-10 20:12:17', '2026-06-10 20:12:17'),
(23, 1, 'mini_test', 14, 15, NULL, '2026-06-10 20:15:07', '2026-06-10 20:15:07'),
(24, 1, 'texte_trou', 4, 4, NULL, '2026-06-10 23:33:09', '2026-06-10 23:33:09'),
(25, 1, 'texte_trou', 4, 4, NULL, '2026-06-10 23:35:31', '2026-06-10 23:35:31');

-- --------------------------------------------------------

--
-- Structure de la table `talks_sessions`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
--

CREATE TABLE `talks_sessions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `score` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `total` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `duree_secondes` smallint(5) UNSIGNED DEFAULT NULL,
  `enregistre_le` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_talks_user` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Scores du module Talks (audio + 3 questions)';

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
-- CORRIGÉ : PRIMARY KEY + AUTO_INCREMENT déplacés inline
-- Note : liste_ine est créée avant, donc la FK INE -> numero_INE est valide.
--

CREATE TABLE `utilisateurs` (
  `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenons` varchar(100) NOT NULL,
  `INE` varchar(30) NOT NULL,
  `classe` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL COMMENT 'Haché avec password_hash()',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `score_total` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Score cumulé de toutes les sessions',
  `progression` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Pourcentage de progression globale (0.00 à 100.00)',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_utilisateurs_ine` (`INE`),
  UNIQUE KEY `uq_utilisateurs_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comptes étudiants de la plateforme';

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`ID`, `nom`, `prenons`, `INE`, `classe`, `email`, `mot_de_passe`, `created_at`, `score_total`, `progression`) VALUES
(1, 'test', '1a', 'N111', 'GENIE-INFORMATIQUE-2', 'alphayayaouattara0@gmail.com', '$2y$10$7agfa/VnRjgMNJ38SX3xjupW.X83BfW0CtawHyCBv0et./oCg/yQq', '2026-06-02 14:10:01', 77, 80.00);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `comprehension_sessions`
--
ALTER TABLE `comprehension_sessions`
  ADD CONSTRAINT `fk_comprehension_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `resultats`
--
ALTER TABLE `resultats`
  ADD CONSTRAINT `fk_resultat_session` FOREIGN KEY (`session_id`) REFERENCES `sessions_activite` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resultat_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `sessions_activite`
--
ALTER TABLE `sessions_activite`
  ADD CONSTRAINT `fk_session_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `talks_sessions`
--
ALTER TABLE `talks_sessions`
  ADD CONSTRAINT `fk_talks_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_utilisateurs_liste_ine` FOREIGN KEY (`INE`) REFERENCES `liste_ine` (`numero_INE`) ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
