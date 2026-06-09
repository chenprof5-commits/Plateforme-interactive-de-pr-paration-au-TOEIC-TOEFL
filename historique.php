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

// Comptage par type pour les badges de filtre
$counts = ['qcm' => 0, 'mini_test' => 0, 'examen' => 0, 'texte_trou' => 0];
foreach ($historique as $s) {
    $t = $s['type_activite'];
    if (isset($counts[$t])) $counts[$t]++;
}

// Mapping des types d'activités
$type_labels = [
    'qcm' => ['label' => 'QCM', 'icon' => 'fas fa-question-circle', 'badge' => 'badge-qcm'],
    'mini_test' => ['label' => 'Mini-test', 'icon' => 'fas fa-clipboard-check', 'badge' => 'badge-mini-test'],
    'examen' => ['label' => 'Examen', 'icon' => 'fas fa-file-alt', 'badge' => 'badge-examen'],
    'examen_audio' => ['label' => 'Examen Audio', 'icon' => 'fas fa-headphones', 'badge' => 'badge-examen'],
    'examen_photos' => ['label' => 'Examen Photos', 'icon' => 'fas fa-camera', 'badge' => 'badge-examen'],
    'texte_trou' => ['label' => 'Texte à trou', 'icon' => 'fas fa-edit', 'badge' => 'badge-texte-trou']
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

  <!-- Header -->
  <header class="hist-header">
    <div class="header-nav">
      <a href="interface_principale.php" class="btn-back">
        <i class="fas fa-arrow-left"></i>
        <span>Retour à l'accueil</span>
      </a>
      <div class="header-badge">
        <i class="fas fa-user"></i>
        <?= $prenom . ' ' . $nom ?>
      </div>
    </div>
    <h1 class="hist-title"><i class="fas fa-history"></i> Historique des activités</h1>
    <p class="hist-subtitle">Suivez votre progression, <strong><?= $prenom ?></strong></p>
  </header>

  <div class="main-container">

    <!-- Statistiques résumées -->
    <div class="stats-grid">
      <div class="stat-card stat-total" style="animation-delay: 0.1s;">
        <div class="stat-icon">
          <i class="fas fa-layer-group"></i>
        </div>
        <div class="stat-value"><?= (int)$stats['total_sessions'] ?></div>
        <div class="stat-label">Sessions totales</div>
      </div>

      <div class="stat-card stat-avg" style="animation-delay: 0.2s;">
        <div class="stat-icon">
          <i class="fas fa-chart-bar"></i>
        </div>
        <div class="stat-value"><?= round($stats['score_moyen'], 1) ?>%</div>
        <div class="stat-label">Score moyen</div>
      </div>

      <div class="stat-card stat-best" style="animation-delay: 0.3s;">
        <div class="stat-icon">
          <i class="fas fa-trophy"></i>
        </div>
        <div class="stat-value"><?= round($stats['meilleur_score'], 1) ?>%</div>
        <div class="stat-label">Meilleur score</div>
      </div>

      <div class="stat-card stat-types" style="animation-delay: 0.4s;">
        <div class="stat-icon">
          <i class="fas fa-check-double"></i>
        </div>
        <div class="stat-value"><?= (int)$stats['types_completes'] ?>/4</div>
        <div class="stat-label">Types complétés</div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filter-section">
      <div class="filter-label"><i class="fas fa-filter"></i> Filtrer par activité</div>
      <div class="filter-buttons">
        <button class="filter-btn active" data-filter="all" onclick="filterHistory('all', this)">
          <i class="fas fa-globe"></i> Tous <span class="count-badge"><?= count($historique) ?></span>
        </button>
        <button class="filter-btn" data-filter="qcm" onclick="filterHistory('qcm', this)">
          <i class="fas fa-question-circle"></i> QCM <span class="count-badge"><?= $counts['qcm'] ?></span>
        </button>
        <button class="filter-btn" data-filter="mini_test" onclick="filterHistory('mini_test', this)">
          <i class="fas fa-clipboard-check"></i> Mini-test <span class="count-badge"><?= $counts['mini_test'] ?></span>
        </button>
        <button class="filter-btn" data-filter="examen" onclick="filterHistory('examen', this)">
          <i class="fas fa-file-alt"></i> Examen <span class="count-badge"><?= $counts['examen'] ?></span>
        </button>
        <button class="filter-btn" data-filter="texte_trou" onclick="filterHistory('texte_trou', this)">
          <i class="fas fa-edit"></i> Texte à trou <span class="count-badge"><?= $counts['texte_trou'] ?></span>
        </button>
      </div>
    </div>

    <!-- Historique -->
    <div class="history-section">
      <?php if (empty($historique)): ?>
        <div class="history-table-wrapper">
          <div class="empty-state">
            <div class="empty-state-icon">
              <i class="fas fa-inbox"></i>
            </div>
            <h3>Aucune activité pour le moment</h3>
            <p>Commencez un QCM, un mini-test ou un examen pour voir votre historique ici !</p>
            <a href="interface_principale.php" class="btn-start">
              <i class="fas fa-play"></i> Commencer maintenant
            </a>
          </div>
        </div>
      <?php else: ?>
        <div class="results-info">
          <span class="results-count"><strong id="visibleCount"><?= count($historique) ?></strong> résultat(s)</span>
        </div>

        <!-- Desktop table -->
        <div class="history-table-wrapper">
          <table class="history-table">
            <thead>
              <tr>
                <th>Activité</th>
                <th>Score</th>
                <th>Pourcentage</th>
                <th>Durée</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($historique as $index => $session):
                $type = $session['type_activite'];
                $typeInfo = $type_labels[$type] ?? ['label' => $type, 'icon' => 'fas fa-circle', 'badge' => 'badge-examen'];
                $total = (int)$session['total_questions'];
                $scoreVal = (int)$session['score'];
                $pourcentage = $total > 0 ? round(($scoreVal / $total) * 100, 1) : 0;
                $duree = $session['duree_secondes'] ? gmdate("i:s", (int)$session['duree_secondes']) : '--:--';
                $dateObj = strtotime($session['commence_le']);
                $dateMain = date('d/m/Y', $dateObj);
                $dateTime = date('H:i', $dateObj);

                if ($pourcentage >= 75) {
                  $scoreClass = 'good';
                } elseif ($pourcentage >= 50) {
                  $scoreClass = 'mid';
                } else {
                  $scoreClass = 'bad';
                }
              ?>
                <tr class="history-row" data-type="<?= $type ?>" style="animation-delay: <?= 0.05 * ($index + 1) ?>s;">
                  <td>
                    <div class="activity-badge <?= $typeInfo['badge'] ?>">
                      <span class="badge-icon"><i class="<?= $typeInfo['icon'] ?>"></i></span>
                      <?= $typeInfo['label'] ?>
                    </div>
                  </td>
                  <td>
                    <div class="score-cell">
                      <span class="score-fraction score-<?= $scoreClass ?>"><?= $scoreVal ?>/<?= $total ?></span>
                    </div>
                  </td>
                  <td class="percentage-cell">
                    <div class="percentage-wrapper">
                      <div class="percentage-bar-track">
                        <div class="percentage-bar-fill fill-<?= $scoreClass ?>" style="width: <?= $pourcentage ?>%;"></div>
                      </div>
                      <span class="percentage-text pct-<?= $scoreClass ?>"><?= $pourcentage ?>%</span>
                    </div>
                  </td>
                  <td>
                    <div class="duration-cell">
                      <i class="fas fa-clock"></i>
                      <span><?= $duree ?></span>
                    </div>
                  </td>
                  <td>
                    <div class="date-cell">
                      <span class="date-main"><?= $dateMain ?></span>
                      <span class="date-time"><?= $dateTime ?></span>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="history-cards-mobile">
          <?php foreach ($historique as $index => $session):
            $type = $session['type_activite'];
            $typeInfo = $type_labels[$type] ?? ['label' => $type, 'icon' => 'fas fa-circle', 'badge' => 'badge-examen'];
            $total = (int)$session['total_questions'];
            $scoreVal = (int)$session['score'];
            $pourcentage = $total > 0 ? round(($scoreVal / $total) * 100, 1) : 0;
            $duree = $session['duree_secondes'] ? gmdate("i:s", (int)$session['duree_secondes']) : '--:--';
            $dateObj = strtotime($session['commence_le']);
            $dateMain = date('d/m/Y', $dateObj);
            $dateTime = date('H:i', $dateObj);

            if ($pourcentage >= 75) {
              $scoreClass = 'good';
            } elseif ($pourcentage >= 50) {
              $scoreClass = 'mid';
            } else {
              $scoreClass = 'bad';
            }
          ?>
            <div class="history-card-mobile history-row" data-type="<?= $type ?>" style="animation-delay: <?= 0.05 * ($index + 1) ?>s;">
              <div class="mobile-card-header">
                <div class="activity-badge <?= $typeInfo['badge'] ?>">
                  <span class="badge-icon"><i class="<?= $typeInfo['icon'] ?>"></i></span>
                  <?= $typeInfo['label'] ?>
                </div>
                <span class="percentage-text pct-<?= $scoreClass ?>"><?= $pourcentage ?>%</span>
              </div>
              <div class="mobile-card-body">
                <div class="mobile-card-item">
                  <span class="item-label">Score</span>
                  <span class="item-value score-fraction score-<?= $scoreClass ?>"><?= $scoreVal ?>/<?= $total ?></span>
                </div>
                <div class="mobile-card-item">
                  <span class="item-label">Durée</span>
                  <span class="item-value"><i class="fas fa-clock"></i> <?= $duree ?></span>
                </div>
                <div class="mobile-card-item">
                  <span class="item-label">Date</span>
                  <span class="item-value"><?= $dateMain ?> <?= $dateTime ?></span>
                </div>
                <div class="mobile-progress-wrapper">
                  <div class="percentage-bar-track">
                    <div class="percentage-bar-fill fill-<?= $scoreClass ?>" style="width: <?= $pourcentage ?>%;"></div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Filtrage côté client
    function filterHistory(type, btn) {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const rows = document.querySelectorAll('.history-row');
      let visibleCount = 0;
      rows.forEach(row => {
        if (type === 'all' || row.dataset.type === type) {
          row.style.display = '';
          row.style.animation = 'fadeInUp 0.4s ease-out forwards';
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      });

      const countEl = document.getElementById('visibleCount');
      if (countEl) countEl.textContent = visibleCount;
    }
  </script>
</body>
</html>
