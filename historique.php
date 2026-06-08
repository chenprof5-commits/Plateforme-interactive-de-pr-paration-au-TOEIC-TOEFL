<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: interface_login.html");
    exit();
}

// Connexion à la base de données
$host = "localhost";
$username = "root";
$password = "";
$dbname = "Plateforme_Interactive_TOIC_TOEFL";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur DB historique: " . $e->getMessage());
    die("Erreur de connexion à la base de données");
}

$user_id = (int) $_SESSION['user_id'];
$prenom = htmlspecialchars($_SESSION['user_prenom'] ?? 'Utilisateur');
$nom = htmlspecialchars($_SESSION['user_nom'] ?? '');

// Récupérer les statistiques
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_sessions,
        COALESCE(AVG(CASE WHEN total_questions > 0 THEN (score / total_questions) * 100 ELSE 0 END), 0) as score_moyen,
        COALESCE(MAX(CASE WHEN total_questions > 0 THEN (score / total_questions) * 100 ELSE 0 END), 0) as meilleur_score,
        COUNT(DISTINCT type_activite) as types_completes
    FROM sessions_activite 
    WHERE utilisateur_id = :user_id
");
$stmt_stats->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_stats->execute();
$stats = $stmt_stats->fetch();

// Récupérer l'historique complet
$stmt_hist = $pdo->prepare("
    SELECT 
        id, type_activite, score, total_questions, duree_secondes, 
        commence_le, termine_le
    FROM sessions_activite 
    WHERE utilisateur_id = :user_id 
    ORDER BY commence_le DESC
");
$stmt_hist->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_hist->execute();
$historique = $stmt_hist->fetchAll();

// Mapping des types d'activités
$type_labels = [
    'qcm' => ['label' => 'QCM', 'icon' => 'fas fa-question-circle', 'color' => 'warning'],
    'mini_test' => ['label' => 'Mini-test', 'icon' => 'fas fa-clipboard-check', 'color' => 'accent'],
    'examen' => ['label' => 'Examen', 'icon' => 'fas fa-file-alt', 'color' => 'primary'],
    'examen_audio' => ['label' => 'Examen Audio', 'icon' => 'fas fa-headphones', 'color' => 'primary'],
    'examen_photos' => ['label' => 'Examen Photos', 'icon' => 'fas fa-camera', 'color' => 'primary'],
    'texte_trou' => ['label' => 'Texte à trou', 'icon' => 'fas fa-edit', 'color' => 'danger']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historique — Plateforme TOEIC/TOEFL</title>
  <meta name="description" content="Historique de vos activités et résultats sur la plateforme de préparation au TOEIC/TOEFL">
  <link rel="stylesheet" href="historiqueStyle.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

  <!-- Bouton retour -->
  <a href="interface_principale.php" class="back-btn">
    <i class="fas fa-arrow-left"></i>
    <span>Retour à l'accueil</span>
  </a>

  <div class="container">
    <!-- Header -->
    <header class="history-header">
      <div class="header-content">
        <h1><i class="fas fa-history"></i> Historique des activités</h1>
        <p class="subtitle">Suivez votre progression, <?= $prenom ?></p>
      </div>
    </header>

    <!-- Statistiques résumées -->
    <div class="stats-grid">
      <div class="stat-card" style="animation-delay: 0.1s;">
        <div class="stat-icon sessions-icon">
          <i class="fas fa-layer-group"></i>
        </div>
        <div class="stat-info">
          <span class="stat-value"><?= (int)$stats['total_sessions'] ?></span>
          <span class="stat-label">Sessions totales</span>
        </div>
      </div>

      <div class="stat-card" style="animation-delay: 0.2s;">
        <div class="stat-icon moyenne-icon">
          <i class="fas fa-chart-bar"></i>
        </div>
        <div class="stat-info">
          <span class="stat-value"><?= round($stats['score_moyen'], 1) ?>%</span>
          <span class="stat-label">Score moyen</span>
        </div>
      </div>

      <div class="stat-card" style="animation-delay: 0.3s;">
        <div class="stat-icon best-icon">
          <i class="fas fa-trophy"></i>
        </div>
        <div class="stat-info">
          <span class="stat-value"><?= round($stats['meilleur_score'], 1) ?>%</span>
          <span class="stat-label">Meilleur score</span>
        </div>
      </div>

      <div class="stat-card" style="animation-delay: 0.4s;">
        <div class="stat-icon types-icon">
          <i class="fas fa-check-double"></i>
        </div>
        <div class="stat-info">
          <span class="stat-value"><?= (int)$stats['types_completes'] ?>/4</span>
          <span class="stat-label">Types complétés</span>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters">
      <button class="filter-btn active" data-filter="all" onclick="filterHistory('all', this)">
        <i class="fas fa-globe"></i> Tous
      </button>
      <button class="filter-btn" data-filter="qcm" onclick="filterHistory('qcm', this)">
        <i class="fas fa-question-circle"></i> QCM
      </button>
      <button class="filter-btn" data-filter="mini_test" onclick="filterHistory('mini_test', this)">
        <i class="fas fa-clipboard-check"></i> Mini-test
      </button>
      <button class="filter-btn" data-filter="examen" onclick="filterHistory('examen', this)">
        <i class="fas fa-file-alt"></i> Examen
      </button>
      <button class="filter-btn" data-filter="texte_trou" onclick="filterHistory('texte_trou', this)">
        <i class="fas fa-edit"></i> Texte à trou
      </button>
    </div>

    <!-- Liste de l'historique -->
    <div class="history-list" id="historyList">
      <?php if (empty($historique)): ?>
        <div class="empty-state">
          <i class="fas fa-inbox"></i>
          <h3>Aucune activité pour le moment</h3>
          <p>Commencez un QCM, un mini-test ou un examen pour voir votre historique ici !</p>
          <a href="interface_principale.php" class="empty-cta">
            <i class="fas fa-play"></i> Commencer maintenant
          </a>
        </div>
      <?php else: ?>
        <?php foreach ($historique as $index => $session): 
          $type = $session['type_activite'];
          $typeInfo = $type_labels[$type] ?? ['label' => $type, 'icon' => 'fas fa-circle', 'color' => 'primary'];
          $total = (int)$session['total_questions'];
          $scoreVal = (int)$session['score'];
          $pourcentage = $total > 0 ? round(($scoreVal / $total) * 100, 1) : 0;
          $duree = $session['duree_secondes'] ? gmdate("i:s", (int)$session['duree_secondes']) : '--:--';
          $date = date('d/m/Y H:i', strtotime($session['commence_le']));
          
          // Couleur selon le pourcentage
          if ($pourcentage >= 75) {
            $scoreClass = 'score-excellent';
          } elseif ($pourcentage >= 50) {
            $scoreClass = 'score-moyen';
          } else {
            $scoreClass = 'score-faible';
          }
        ?>
          <div class="history-item" data-type="<?= $type ?>" style="animation-delay: <?= 0.1 * ($index + 1) ?>s;">
            <!-- Badge du type -->
            <div class="item-type">
              <div class="type-badge badge-<?= $typeInfo['color'] ?>">
                <i class="<?= $typeInfo['icon'] ?>"></i>
              </div>
              <div class="type-info">
                <span class="type-label"><?= $typeInfo['label'] ?></span>
                <span class="type-date"><i class="fas fa-calendar-alt"></i> <?= $date ?></span>
              </div>
            </div>

            <!-- Score -->
            <div class="item-score">
              <div class="score-fraction <?= $scoreClass ?>">
                <span class="score-num"><?= $scoreVal ?></span>
                <span class="score-sep">/</span>
                <span class="score-den"><?= $total ?></span>
              </div>
            </div>

            <!-- Barre de pourcentage -->
            <div class="item-progress">
              <div class="mini-progress-bar">
                <div class="mini-progress <?= $scoreClass ?>" style="width: <?= $pourcentage ?>%;"></div>
              </div>
              <span class="progress-pct <?= $scoreClass ?>"><?= $pourcentage ?>%</span>
            </div>

            <!-- Durée -->
            <div class="item-duration">
              <i class="fas fa-clock"></i>
              <span><?= $duree ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Filtrage côté client
    function filterHistory(type, btn) {
      // Mettre à jour les boutons actifs
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      // Filtrer les éléments
      const items = document.querySelectorAll('.history-item');
      items.forEach(item => {
        if (type === 'all' || item.dataset.type === type) {
          item.style.display = 'flex';
          item.style.animation = 'fadeInUp 0.4s ease-out forwards';
        } else {
          item.style.display = 'none';
        }
      });

      // Afficher un message si aucun résultat
      const visibleItems = document.querySelectorAll('.history-item[style*="display: flex"], .history-item:not([style*="display: none"])');
      const emptyState = document.querySelector('.empty-state');
      // Si un empty state temporaire existe, on ne le touche pas
    }
  </script>
</body>
</html>
