<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: interface_login.html");
    exit();
}

$prenom = isset($_SESSION['user_prenom']) ? htmlspecialchars($_SESSION['user_prenom']) : 'Utilisateur';
$nom = isset($_SESSION['user_nom']) ? htmlspecialchars($_SESSION['user_nom']) : '';
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Plateforme d'Apprentissage - Page Principale</title>
  <meta name="description" content="Plateforme interactive de préparation au TOEIC/TOEFL - Tableau de bord principal" />
  <link rel="stylesheet" href="style.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script defer src="script.js"></script>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-content">
      <div class="user-profile">
        <div class="user-avatar">
          <i class="fas fa-user"></i>
        </div>
        <h2 id="username"><?= $prenom . ' ' . $nom ?></h2>
        <p><strong>Niveau :</strong> <span class="niveau" id="niveauBadge">Chargement...</span></p>
      </div>

      <!-- Section Score -->
      <div class="score-section" id="scoreSection">
        <h3><i class="fas fa-star"></i> Score Total</h3>
        <div class="score-display">
          <span class="score-value" id="scoreTotalDisplay">0</span>
          <span class="score-label">points</span>
        </div>
      </div>

      <div class="progress-section">
        <h3><i class="fas fa-chart-line"></i> Progression</h3>
        <div class="progress-bar">
          <div class="progress" id="progressBar" style="width: 0%;"></div>
        </div>
        <span class="pourcentage" id="pourcentageDisplay">0 %</span>
      </div>

      <div class="menu">
        <a href="historique.php" class="menu-item-link">
          <div class="menu-item">
            <span class="icon"><i class="fas fa-history"></i></span>
            <span>Historique</span>
          </div>
        </a>
        <div class="menu-item">
          <span class="icon"><i class="fas fa-cog"></i></span>
          <span>Paramètres</span>
        </div>
        <div class="menu-item" id="logoutBtn">
          <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
          <span>Déconnexion</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Main content -->
  <div class="main-container">
    <header>
      <div class="user-icon" id="userIcon">
        <i class="fas fa-user"></i>
      </div>
      <h1 id="welcomeTitle">Bienvenue, <?= $prenom ?> !</h1>
      <h2><strong>Que voulez-vous faire aujourd'hui ?</strong></h2>
      <!-- Score rapide dans le header -->
      <div class="header-score" id="headerScore">
        <div class="header-score-item">
          <i class="fas fa-trophy"></i>
          <span id="headerScoreValue">0</span> pts
        </div>
        <div class="header-score-item">
          <i class="fas fa-chart-pie"></i>
          <span id="headerProgressValue">0</span>%
        </div>
      </div>
    </header>
    
    <div class="cards">
      <a href="Examen.html" class="card-link">
        <div class="card examen">
          <div class="card-content">
            <i class="fas fa-file-alt card-icon"></i>
            <span>EXAMEN</span>
          </div>
        </div>
      </a>
      <a href="Mini-test.html" class="card-link">
        <div class="card mini-test">
          <div class="card-content">
            <i class="fas fa-clipboard-check card-icon"></i>
            <span>MINI-TEST</span>
          </div>
        </div>
      </a>
      <a href="Lecture.html" class="card-link">
        <div class="card lecture">
          <div class="card-content">
            <i class="fas fa-book-open card-icon"></i>
            <span>LECTURE</span>
          </div>
        </div>
      </a>
    </div>

    <div class="cards">
      <a href="qcm.html" class="card-link">
        <div class="card qcm">
          <div class="card-content">
            <i class="fas fa-question-circle card-icon"></i>
            <span>QCM</span>
          </div>
        </div>
      </a>
      <a href="video.html" class="card-link">
        <div class="card video">
          <div class="card-content">
            <i class="fas fa-play-circle card-icon"></i>
            <span>Vidéo</span>
          </div>
        </div>
      </a>
      <a href="texte-a-trou.html" class="card-link">
        <div class="card texte-a-trou">
          <div class="card-content">
            <i class="fas fa-edit card-icon"></i>
            <span>Texte à trou</span>
          </div>
        </div>
      </a>
    </div>

    <!-- Carte Historique -->
    <div class="cards cards-single">
      <a href="historique.php" class="card-link">
        <div class="card historique">
          <div class="card-content">
            <i class="fas fa-history card-icon"></i>
            <span>HISTORIQUE</span>
            <span class="card-subtitle">Consultez vos résultats</span>
          </div>
        </div>
      </a>
    </div>
  </div>
</body>
</html>
