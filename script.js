// ============================================================
// Script principal — Interface Principale
// Gestion sidebar, progression dynamique, score, déconnexion
// ============================================================

const userIcon = document.getElementById('userIcon');
const sidebar = document.getElementById('sidebar');

// Toggle sidebar quand on clique sur l'icône utilisateur
if (userIcon && sidebar) {
  userIcon.addEventListener('click', (e) => {
    e.stopPropagation();
    sidebar.classList.toggle('open');
  });

  // Fermer la sidebar si on clique ailleurs
  document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !userIcon.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });
}

// ============================================================
// Chargement de la progression depuis l'API
// ============================================================
function loadProgress() {
  fetch('api/get_progress.php')
    .then(response => {
      if (!response.ok) throw new Error('Erreur réseau');
      return response.json();
    })
    .then(data => {
      if (data.error) {
        console.warn('Erreur API:', data.error);
        return;
      }

      // Mise à jour de la barre de progression avec animation
      const progressBar = document.getElementById('progressBar');
      const pourcentageDisplay = document.getElementById('pourcentageDisplay');
      const progression = Math.min(data.progression, 100);

      if (progressBar) {
        // Petite pause pour que l'animation CSS soit visible
        setTimeout(() => {
          progressBar.style.width = progression + '%';
        }, 300);
      }
      if (pourcentageDisplay) {
        animateCounter(pourcentageDisplay, 0, Math.round(progression), '%', ' ');
      }

      // Mise à jour du score total avec animation de compteur
      const scoreTotalDisplay = document.getElementById('scoreTotalDisplay');
      if (scoreTotalDisplay) {
        animateCounter(scoreTotalDisplay, 0, data.score_total, '', '');
      }

      // Score dans le header
      const headerScoreValue = document.getElementById('headerScoreValue');
      const headerProgressValue = document.getElementById('headerProgressValue');
      if (headerScoreValue) {
        animateCounter(headerScoreValue, 0, data.score_total, '', '');
      }
      if (headerProgressValue) {
        animateCounter(headerProgressValue, 0, Math.round(progression), '', '');
      }

      // Calcul et affichage du niveau
      updateNiveau(data.best_scores, data.progression);
    })
    .catch(error => {
      console.error('Erreur lors du chargement de la progression:', error);
    });
}

// ============================================================
// Animation de compteur (nombre qui s'incrémente)
// ============================================================
function animateCounter(element, start, end, suffix, prefix) {
  if (start === end) {
    element.textContent = prefix + end + suffix;
    return;
  }
  
  const duration = 1200; // ms
  const startTime = performance.now();
  
  function update(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    
    // Easing: ease-out cubic
    const eased = 1 - Math.pow(1 - progress, 3);
    const current = Math.round(start + (end - start) * eased);
    
    element.textContent = prefix + current + suffix;
    
    if (progress < 1) {
      requestAnimationFrame(update);
    }
  }
  
  requestAnimationFrame(update);
}

// ============================================================
// Calcul du niveau dynamique
// ============================================================
function updateNiveau(bestScores, progression) {
  const niveauBadge = document.getElementById('niveauBadge');
  if (!niveauBadge) return;

  // Calculer le score moyen en pourcentage sur les meilleurs scores
  let totalPourcentage = 0;
  let count = 0;
  
  if (bestScores) {
    for (const type in bestScores) {
      if (bestScores[type] && bestScores[type].pourcentage !== undefined) {
        totalPourcentage += bestScores[type].pourcentage;
        count++;
      }
    }
  }

  const moyennePourcentage = count > 0 ? totalPourcentage / count : 0;
  
  let niveau, niveauClass;
  if (moyennePourcentage >= 76) {
    niveau = '⭐ Expert';
    niveauClass = 'niveau-expert';
  } else if (moyennePourcentage >= 51) {
    niveau = '🟢 Avancé';
    niveauClass = 'niveau-avance';
  } else if (moyennePourcentage >= 26) {
    niveau = '🟡 Intermédiaire';
    niveauClass = 'niveau-intermediaire';
  } else {
    niveau = '🔴 Débutant';
    niveauClass = 'niveau-debutant';
  }

  niveauBadge.textContent = niveau;
  niveauBadge.className = 'niveau ' + niveauClass;
}

// ============================================================
// Déconnexion
// ============================================================
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
  logoutBtn.addEventListener('click', () => {
    fetch('api/logout.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          window.location.href = 'interface_login.html';
        }
      })
      .catch(() => {
        // En cas d'erreur, rediriger quand même
        window.location.href = 'interface_login.html';
      });
  });
}

// ============================================================
// Initialisation au chargement de la page
// ============================================================
window.addEventListener('DOMContentLoaded', () => {
  loadProgress();
});
