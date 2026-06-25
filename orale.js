/**
 * orale.js — Module de Prononciation
 * Utilise la Web Speech API native (SpeechRecognition / webkitSpeechRecognition)
 * Algorithme LCS (Longest Common Subsequence) pour le scoring mot-à-mot
 */

// ════════════════════════════════════════════════════════════
// 1. DÉTECTION NAVIGATEUR
// ════════════════════════════════════════════════════════════
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

if (!SpeechRecognition) {
  // Navigateur non supporté → afficher l'alerte, masquer le module
  document.getElementById('browserAlert').style.display = 'block';
  document.getElementById('moduleContent').style.display = 'none';
  throw new Error('SpeechRecognition non supportée dans ce navigateur.');
}

// ════════════════════════════════════════════════════════════
// 2. VARIABLES GLOBALES
// ════════════════════════════════════════════════════════════
let allPhrases    = [];    // données brutes du JSON
let filtered      = [];    // phrases après filtre de difficulté
let currentIdx    = 0;     // index actuel dans filtered
let isRecording   = false;
let recognition   = null;
let sessionScores = [];    // { score, confidence } de chaque phrase tentée
let activeDiff    = 'tous';
let hadError      = false; // empêche stopRecording d'écraser un message d'erreur
let retryCount    = 0;     // compteur de tentatives automatiques
const MAX_RETRIES = 3;     // max de retentatives sur erreur réseau

// Éléments DOM fréquemment utilisés
const $ = id => document.getElementById(id);
const phraseDisplay  = $('phraseDisplay');
const phraseMeta     = $('phraseMeta');
const phraseCounter  = $('phraseCounter');
const conseilBox     = $('conseilBox');
const conseilText    = $('conseilText');
const micBtn         = $('micBtn');
const micIcon        = $('micIcon');
const micLabel       = $('micLabel');
const waveform       = $('waveform');
const resultsSection = $('resultsSection');
const heardText      = $('heardText');
const ringFill       = $('ringFill');
const scoreVal       = $('scoreVal');
const confVal        = $('confVal');
const wordsVal       = $('wordsVal');
const progFill       = $('progFill');
const progLabel      = $('progLabel');
const nextBtn        = $('nextBtn');
const prevBtn        = $('prevBtn');
const skipBtn        = $('skipBtn');
const navScorePill   = $('navScorePill');
const historyStrip   = $('historyStrip');
const finalCard      = $('finalCard');

// ════════════════════════════════════════════════════════════
// 3. CANVAS PARTICULES
// ════════════════════════════════════════════════════════════
(function initParticles() {
  const canvas = document.getElementById('bg-particles');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let W, H, particles;

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }

  function mkParticle() {
    return {
      x: Math.random() * W,
      y: Math.random() * H,
      r: Math.random() * 2 + 0.5,
      dx: (Math.random() - .5) * .35,
      dy: (Math.random() - .5) * .35,
      a: Math.random() * .45 + .05,
      color: ['rgba(139,92,246,', 'rgba(167,139,250,', 'rgba(16,185,129,'][Math.floor(Math.random() * 3)]
    };
  }

  function init() {
    resize();
    particles = Array.from({ length: 60 }, mkParticle);
  }

  function draw() {
    ctx.clearRect(0, 0, W, H);
    particles.forEach(p => {
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = p.color + p.a + ')';
      ctx.fill();
      p.x += p.dx; p.y += p.dy;
      if (p.x < 0 || p.x > W) p.dx *= -1;
      if (p.y < 0 || p.y > H) p.dy *= -1;
    });
    requestAnimationFrame(draw);
  }

  window.addEventListener('resize', resize);
  init();
  draw();
})();

// ════════════════════════════════════════════════════════════
// 4. CHARGEMENT DES DONNÉES
// ════════════════════════════════════════════════════════════
fetch('prononciation.json?v=' + Date.now())
  .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
  .then(data => {
    allPhrases = Array.isArray(data) ? data : [];
    applyFilter('tous');
  })
  .catch(err => {
    phraseDisplay.textContent = '❌ Impossible de charger prononciation.json : ' + err.message;
    console.error(err);
  });

// ════════════════════════════════════════════════════════════
// 5. FILTRE PAR DIFFICULTÉ
// ════════════════════════════════════════════════════════════
document.getElementById('diffBar').addEventListener('click', e => {
  const btn = e.target.closest('.diff-btn');
  if (!btn) return;
  const diff = btn.dataset.diff;
  document.querySelectorAll('.diff-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilter(diff);
});

function applyFilter(diff) {
  activeDiff = diff;
  filtered = diff === 'tous'
    ? [...allPhrases]
    : allPhrases.filter(p => p.difficulte === diff);

  if (filtered.length === 0) {
    phraseDisplay.textContent = 'Aucune phrase dans cette catégorie.';
    micBtn.disabled = true;
    return;
  }

  // Mélanger légèrement pour varier
  filtered = filtered.sort(() => Math.random() - .5);
  currentIdx = 0;
  sessionScores = [];
  historyStrip.style.display = 'none';
  historyStrip.innerHTML = '';
  resetResults();
  loadPhrase(0);
}

// ════════════════════════════════════════════════════════════
// 6. CHARGER UNE PHRASE
// ════════════════════════════════════════════════════════════
function loadPhrase(idx) {
  const p = filtered[idx];
  if (!p) return;

  // Réinitialiser l'état
  resetResults();
  stopRecording();

  // Barre de progression
  const pct = filtered.length > 1 ? (idx / (filtered.length - 1)) * 100 : 0;
  progFill.style.width = pct + '%';
  progLabel.textContent = `Phrase ${idx + 1} / ${filtered.length}`;
  phraseCounter.textContent = `${idx + 1} / ${filtered.length}`;

  // Badge difficulté
  phraseMeta.innerHTML = '';
  const diffBadge = document.createElement('span');
  diffBadge.className = `badge badge-diff-${p.difficulte || 'moyen'}`;
  diffBadge.textContent = p.difficulte || 'moyen';
  phraseMeta.appendChild(diffBadge);

  if (p.categorie) {
    const catBadge = document.createElement('span');
    catBadge.className = 'badge badge-cat';
    catBadge.textContent = p.categorie;
    phraseMeta.appendChild(catBadge);
  }

  // Afficher la phrase mot par mot (spans neutres)
  renderPhrase(p.texte, []);

  // Conseil phonétique
  if (p.conseil) {
    conseilText.textContent = p.conseil;
    conseilBox.style.display = 'inline-flex';
  } else {
    conseilBox.style.display = 'none';
  }

  // Activer le micro
  micBtn.disabled = false;
  micLabel.textContent = 'Cliquez sur le microphone pour commencer';

  // Boutons navigation
  prevBtn.style.display = idx > 0 ? 'inline-flex' : 'none';
  nextBtn.style.display = 'none';
}

// ════════════════════════════════════════════════════════════
// 7. RENDU DE LA PHRASE AVEC COLORATION
// ════════════════════════════════════════════════════════════
function renderPhrase(texte, wordResults) {
  // wordResults : tableau de { word, correct: bool } (vide = neutre)
  phraseDisplay.innerHTML = '';
  const words = texte.split(/\s+/).filter(Boolean);

  words.forEach((w, i) => {
    const span = document.createElement('span');
    span.className = 'word';
    span.textContent = w;

    if (wordResults.length > 0) {
      const res = wordResults[i];
      if (res) {
        span.classList.add(res.correct ? 'correct' : 'incorrect');
        // Animation décalée
        span.style.animationDelay = (i * 0.06) + 's';
        span.style.animation = 'bounceIn .5s ease both';
        span.style.animationDelay = (i * 0.06) + 's';
      }
    }

    phraseDisplay.appendChild(span);
  });
}

// ════════════════════════════════════════════════════════════
// 8. ALGORITHME LCS (Longest Common Subsequence) — Mots
// ════════════════════════════════════════════════════════════

/** Normalise un texte : minuscules, sans ponctuation */
function normalize(txt) {
  return txt.toLowerCase().replace(/[^a-z0-9\s]/g, '').trim();
}

/**
 * LCS sur tableaux de mots.
 * Retourne la longueur de la plus longue sous-séquence commune.
 */
function lcsLength(a, b) {
  const m = a.length, n = b.length;
  // Tableau 2D (optimisé : deux lignes seulement)
  let prev = new Array(n + 1).fill(0);
  let curr = new Array(n + 1).fill(0);
  for (let i = 1; i <= m; i++) {
    for (let j = 1; j <= n; j++) {
      curr[j] = a[i - 1] === b[j - 1]
        ? prev[j - 1] + 1
        : Math.max(curr[j - 1], prev[j]);
    }
    [prev, curr] = [curr, prev];
    curr.fill(0);
  }
  return prev[n];
}

/**
 * Détermine mot par mot si chaque mot de `expected`
 * est présent dans `transcribed` (recherche greedy avec ensemble).
 * Retourne un tableau de booléens (longueur = expected.length).
 */
function matchWords(expected, transcribed) {
  // On utilise un multi-set pour gérer les doublons
  const available = {};
  transcribed.forEach(w => { available[w] = (available[w] || 0) + 1; });

  return expected.map(w => {
    if (available[w] && available[w] > 0) {
      available[w]--;
      return true;
    }
    return false;
  });
}

// ════════════════════════════════════════════════════════════
// 9. WEB SPEECH API — RECONNAISSANCE VOCALE
// ════════════════════════════════════════════════════════════
micBtn.addEventListener('click', () => {
  if (isRecording) {
    stopRecording();
  } else {
    startRecording();
  }
});

function startRecording() {
  isRecording = true;

  // UI : état enregistrement
  micBtn.classList.add('recording');
  micIcon.className = 'fas fa-stop';
  micLabel.textContent = 'Enregistrement en cours… Parlez maintenant !';
  micLabel.classList.add('recording');
  waveform.classList.add('active');
  resultsSection.classList.remove('show');
  nextBtn.style.display = 'none';

  // Configurer la reconnaissance
  recognition = new SpeechRecognition();
  recognition.lang           = 'en-US';
  recognition.interimResults = false;
  recognition.maxAlternatives = 1;   // 1 seul résultat = plus stable
  recognition.continuous     = false;

  recognition.onstart = () => {
    console.log('[Orale] Reconnaissance démarrée (tentative', retryCount + 1, ')');
  };

  recognition.onresult = event => {
    retryCount = 0; // succès → reset compteur
    hadError   = false;
    const best       = event.results[0][0];
    const transcript = best.transcript;
    const confidence = best.confidence;
    processResult(transcript, confidence);
  };

  recognition.onerror = event => {
    console.warn('[Orale] Erreur:', event.error);

    // Annulation volontaire → silencieux
    if (event.error === 'aborted') return;

    // ── Erreur réseau : retry automatique ──────────────────
    if (event.error === 'network' && retryCount < MAX_RETRIES) {
      retryCount++;
      hadError = true;

      // Stopper sans reset du label
      isRecording = false;
      micBtn.classList.remove('recording');
      micIcon.className = 'fas fa-microphone';
      waveform.classList.remove('active');
      micLabel.classList.remove('recording');
      if (recognition) { try { recognition.stop(); } catch(e) {} recognition = null; }

      const delai = retryCount * 1800; // 1.8s → 3.6s → 5.4s
      micLabel.style.color = '#f59e0b';
      micLabel.textContent  = `⏳ Connexion au service vocal… tentative ${retryCount}/${MAX_RETRIES}`;

      setTimeout(() => {
        hadError = false;
        micLabel.style.color = '';
        if (!isRecording) startRecording();
      }, delai);
      return;
    }

    // ── Toutes les tentatives épuisées ou autre erreur ──────
    hadError = true;
    retryCount = 0;
    // Stopper proprement
    isRecording = false;
    micBtn.classList.remove('recording');
    micIcon.className = 'fas fa-microphone';
    waveform.classList.remove('active');
    micLabel.classList.remove('recording');
    if (recognition) { try { recognition.stop(); } catch(e) {} recognition = null; }

    let msg, hint;
    switch (event.error) {
      case 'network':
        msg  = '🔄 Service vocal indisponible après 3 tentatives.';
        hint = 'Le service Google Speech est temporairement surchargé. Patientez 15s puis réessayez.';
        break;
      case 'no-speech':
        msg  = '🎤 Aucune voix détectée.';
        hint = 'Parlez plus fort ou rapprochez-vous du microphone.';
        break;
      case 'not-allowed':
        msg  = '🚫 Microphone refusé.';
        hint = 'Cliquez sur 🔒 dans la barre d\'adresse → Autoriser le microphone.';
        break;
      case 'audio-capture':
        msg  = '🎙️ Aucun microphone détecté.';
        hint = 'Branchez un micro ou vérifiez les paramètres système.';
        break;
      default:
        msg  = `⚠️ Erreur : ${event.error}`;
        hint = 'Rechargez la page si le problème persiste.';
    }

    micLabel.innerHTML = `<span style="color:#ef4444;font-weight:700;">${msg}</span><br>
      <small style="color:#94a3b8;font-size:0.78rem;line-height:1.5;">${hint}</small>`;
    micLabel.style.textAlign  = 'center';
    micLabel.style.lineHeight = '1.6';
  };

  recognition.onend = () => {
    // Ne réinitialise que si pas d'erreur en attente
    if (isRecording && !hadError) stopRecording();
  };

  try {
    recognition.start();
  } catch (e) {
    hadError = true;
    stopRecording();
    micLabel.textContent = '❌ Démarrage impossible : ' + e.message;
  }
}

function stopRecording() {
  isRecording = false;
  micBtn.classList.remove('recording');
  micIcon.className = 'fas fa-microphone';
  micLabel.classList.remove('recording');
  waveform.classList.remove('active');

  if (recognition) {
    try { recognition.stop(); } catch (e) { /* ignore */ }
    recognition = null;
  }

  // Remettre le label par défaut SEULEMENT s'il n'y a pas d'erreur affichée
  if (!hadError) {
    micLabel.style.cssText  = '';
    micLabel.innerHTML      = 'Cliquez sur le microphone pour commencer';
  }
  hadError = false; // reset pour la prochaine fois
}

// ════════════════════════════════════════════════════════════
// 10. TRAITEMENT DU RÉSULTAT
// ════════════════════════════════════════════════════════════
function processResult(transcript, confidence) {
  stopRecording();

  const p = filtered[currentIdx];
  const expectedRaw    = p.texte;
  const expectedNorm   = normalize(expectedRaw).split(/\s+/).filter(Boolean);
  const transcribedNorm = normalize(transcript).split(/\s+/).filter(Boolean);

  // LCS pour le score global
  const lcsLen  = lcsLength(expectedNorm, transcribedNorm);
  const lcsScore = expectedNorm.length > 0
    ? Math.round((lcsLen / expectedNorm.length) * 100)
    : 0;

  // Correspondance mot par mot pour la coloration
  const matched = matchWords(expectedNorm, transcribedNorm);

  // Coloration de la phrase
  const wordResults = normalize(expectedRaw).split(/\s+/).map((w, i) => ({
    word: expectedRaw.split(/\s+/)[i],
    correct: matched[i] === true
  }));
  renderPhrase(expectedRaw, wordResults);

  // ── Afficher les résultats ──
  heardText.textContent = transcript || '(rien entendu)';

  // Score ring
  animateRing(lcsScore);
  scoreVal.textContent = lcsScore + '%';
  scoreVal.style.color = scoreColor(lcsScore);

  // Confiance API
  const confPct = confidence !== undefined
    ? Math.round(confidence * 100) + '%'
    : 'N/A';
  confVal.textContent = confPct;
  confVal.style.color = scoreColor(confidence !== undefined ? Math.round(confidence * 100) : 50);

  // Mots reconnus
  const correctCount = matched.filter(Boolean).length;
  wordsVal.textContent = `${correctCount} / ${expectedNorm.length}`;
  wordsVal.style.color = scoreColor(Math.round((correctCount / expectedNorm.length) * 100));

  // Afficher la section résultats
  resultsSection.classList.add('show');

  // Enregistrer dans l'historique local
  sessionScores.push({ score: lcsScore, confidence: confidence || 0 });
  updateHistoryStrip();
  updateNavScore();

  // Bouton suivant
  micLabel.textContent = lcsScore >= 80
    ? '🎉 Excellent ! Passez à la phrase suivante.'
    : lcsScore >= 50
    ? '👍 Bien ! Réessayez ou continuez.'
    : '📢 Réessayez pour améliorer votre prononciation.';

  nextBtn.style.display = 'inline-flex';

  // Sauvegarder le score en base
  saveScore(lcsScore, confidence || 0, expectedRaw);
}

// ════════════════════════════════════════════════════════════
// 11. ANIMATION DE LA JAUGE CIRCULAIRE
// ════════════════════════════════════════════════════════════
function animateRing(pct) {
  const circumference = 2 * Math.PI * 50; // r=50 → 314.16
  const offset = circumference - (pct / 100) * circumference;

  // Couleur de l'anneau selon le score
  ringFill.style.stroke = pct >= 80 ? '#10b981'
    : pct >= 50 ? '#f59e0b'
    : '#ef4444';

  // Animation via transition CSS
  ringFill.style.strokeDashoffset = circumference; // reset
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      ringFill.style.strokeDashoffset = offset;
    });
  });
}

function scoreColor(pct) {
  if (pct >= 80) return '#10b981';
  if (pct >= 50) return '#f59e0b';
  return '#ef4444';
}

// ════════════════════════════════════════════════════════════
// 12. HISTORIQUE VISUEL (POINTS COLORÉS)
// ════════════════════════════════════════════════════════════
function updateHistoryStrip() {
  if (sessionScores.length === 0) return;
  historyStrip.style.display = 'flex';
  historyStrip.innerHTML = '';

  sessionScores.forEach((s, i) => {
    const dot = document.createElement('div');
    dot.className = 'hist-dot';
    dot.title = `Phrase ${i + 1} : ${s.score}%`;
    dot.textContent = s.score + '%';
    dot.style.cssText = `
      background: ${scoreColor(s.score)}22;
      border-color: ${scoreColor(s.score)}66;
      color: ${scoreColor(s.score)};
    `;
    historyStrip.appendChild(dot);
  });
}

// ════════════════════════════════════════════════════════════
// 13. SCORE MOYEN AFFICHÉ EN NAV
// ════════════════════════════════════════════════════════════
function updateNavScore() {
  if (sessionScores.length === 0) return;
  const avg = Math.round(
    sessionScores.reduce((s, x) => s + x.score, 0) / sessionScores.length
  );
  navScorePill.textContent = `Score moyen : ${avg}%`;
  navScorePill.style.color = scoreColor(avg);
}

// ════════════════════════════════════════════════════════════
// 14. NAVIGATION
// ════════════════════════════════════════════════════════════
nextBtn.addEventListener('click', () => {
  currentIdx++;
  if (currentIdx >= filtered.length) {
    showFinalScore();
  } else {
    loadPhrase(currentIdx);
  }
});

prevBtn.addEventListener('click', () => {
  if (currentIdx > 0) {
    currentIdx--;
    loadPhrase(currentIdx);
  }
});

skipBtn.addEventListener('click', () => {
  // Passer sans enregistrer de score
  currentIdx++;
  if (currentIdx >= filtered.length) {
    showFinalScore();
  } else {
    loadPhrase(currentIdx);
  }
});

// ════════════════════════════════════════════════════════════
// 15. SCORE FINAL
// ════════════════════════════════════════════════════════════
function showFinalScore() {
  // Masquer les éléments du module par leurs IDs
  $('moduleContent').style.display = 'none';
  $('actionsBar').style.display    = 'none';

  // Afficher la carte score final
  finalCard.style.display    = 'block';
  finalCard.style.maxWidth   = '640px';
  finalCard.style.margin     = '32px auto 0';
  finalCard.style.animation  = 'pageFadeIn 0.5s ease';

  const avg = sessionScores.length > 0
    ? Math.round(sessionScores.reduce((s, x) => s + x.score, 0) / sessionScores.length)
    : 0;

  // Score final — overrider le gradient CSS pour afficher la couleur correcte
  const finalScoreEl = $('finalScore');
  finalScoreEl.textContent = avg + '%';
  finalScoreEl.style.cssText = `
    font-size: 3rem; font-weight: 800;
    color: ${scoreColor(avg)};
    -webkit-text-fill-color: ${scoreColor(avg)};
    margin-bottom: 8px;
  `;

  const msg = avg >= 80
    ? `🌟 Excellent ! Score moyen : ${avg}% — Votre prononciation est très bonne.`
    : avg >= 60
    ? `👍 Bien ! Score moyen : ${avg}% — Continuez à vous entraîner.`
    : `📢 Score moyen : ${avg}% — Entraînez-vous davantage pour améliorer votre accent.`;

  $('finalMsg').textContent = msg;

  progFill.style.width  = '100%';
  progLabel.textContent = `Terminé — ${sessionScores.length} phrase(s) essayée(s) sur ${filtered.length}`;
}

// ════════════════════════════════════════════════════════════
// 16. RESET
// ════════════════════════════════════════════════════════════
function resetResults() {
  resultsSection.classList.remove('show');
  nextBtn.style.display = 'none';
  heardText.textContent = '—';
  scoreVal.textContent = '0%';
  scoreVal.style.color = '';
  confVal.textContent = '—';
  wordsVal.textContent = '—';
  ringFill.style.strokeDashoffset = '314';
  ringFill.style.stroke = 'var(--primary)';
}

// ════════════════════════════════════════════════════════════
// 17. SAUVEGARDE DU SCORE EN BASE
// ════════════════════════════════════════════════════════════
function saveScore(score, confidence, phraseTexte) {
  fetch('api/enregistrer_score_prononciation.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      score:       score,
      confidence:  Math.round(confidence * 100),
      phrase:      phraseTexte,
      total:       100   // Le score est déjà un pourcentage sur 100
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      console.log('[Orale] Score sauvegardé :', data);
    } else {
      console.warn('[Orale] Erreur sauvegarde :', data.error);
    }
  })
  .catch(e => console.warn('[Orale] Erreur réseau save_score :', e));
}
