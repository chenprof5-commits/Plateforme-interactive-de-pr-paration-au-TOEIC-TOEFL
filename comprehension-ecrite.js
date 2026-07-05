/**
 * comprehension-ecrite.js — Logique du module Compréhension Écrite
 *
 * ══════════════════════════════════════════════════════════════
 * FORMAT DU FICHIER JSON (comprehension-ecrite.json)
 * ══════════════════════════════════════════════════════════════
 *
 * [
 *   {
 *     "id": "p1",
 *     "type": "email",                         ← optionnel : "email", "article", "notice", "rapport", "annonce"
 *     "titre": "Company Memo",                 ← titre du passage (affiché en grand)
 *     "source": "Internal company document",   ← optionnel : source du texte
 *     "texte": "Dear team,\n\n...",             ← le texte complet (\n pour sauts de ligne)
 *     "questions": [
 *       {
 *         "texte": "What is the main purpose of this memo?",
 *         "options": {
 *           "a": "To announce a new product",
 *           "b": "To invite employees to a meeting",
 *           "c": "To report quarterly results",
 *           "d": "To introduce a new employee"
 *         },
 *         "reponse": "b",
 *         "explication": "The memo says 'we invite all staff to attend...' which indicates a meeting."
 *       },
 *       { ... autres questions ... }
 *     ]
 *   },
 *   { ... passage suivant ... }
 * ]
 *
 * NOTES :
 *  - Le champ "type" sert à afficher un badge (Email, Article, etc.)
 *  - Le champ "explication" est OPTIONNEL. S'il est présent, il s'affiche
 *    après la réponse pour aider l'étudiant à comprendre.
 *  - Chaque passage peut avoir 2, 3 ou 4 questions.
 *  - La clé "reponse" doit être "a", "b", "c" ou "d" (minuscule).
 *  - Le texte supporte \n pour les sauts de ligne.
 *  - Mettez vos fichiers JSON dans le même dossier que cette page.
 */

// ── Configuration ──
const TIMER_SECONDS  = 90;    // secondes par passage (texte court = 60s, long = 120s)
const JSON_FILE      = 'comprehension-ecrite.json';
const SAVE_ENDPOINT  = 'api/save_score.php';

// ── État global ──
let passages      = [];
let currentIdx    = 0;
let score         = 0;
let totalAnswered = 0;
let answeredCount = 0;   // nombre de questions répondues dans le passage courant
let timerInterval = null;
let timeLeft      = TIMER_SECONDS;

// ── Éléments DOM ──
const globalFill   = document.getElementById('globalBarFill');
const globalLabel  = document.getElementById('globalBarLabel');
const textPanelTitle= document.getElementById('textPanelTitle');
const textMeta     = document.getElementById('textMeta');
const textTitre    = document.getElementById('textTitre');
const textBody     = document.getElementById('textBody');
const textSource   = document.getElementById('textSource');
const navDots      = document.getElementById('navDots');
const prevTextBtn  = document.getElementById('prevTextBtn');
const qProgressFill= document.getElementById('qProgressFill');
const qProgressLabel= document.getElementById('qProgressLabel');
const timerPill    = document.getElementById('timerPill');
const timerVal     = document.getElementById('timerVal');
const questionsList= document.getElementById('questionsList');
const nextBtn      = document.getElementById('nextBtn');
const scoreFinalDiv= document.getElementById('scoreFinalDiv');
const scoreFinalVal= document.getElementById('scoreFinalVal');
const scoreFinalMsg= document.getElementById('scoreFinalMsg');
const questionsSubtitle = document.getElementById('questionsSubtitle');

// ── Chargement JSON ──
fetch(JSON_FILE + '?v=' + Date.now())
  .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
  .then(data => {
    passages = Array.isArray(data) ? data : [];
    if (passages.length === 0) {
      textTitre.textContent = 'Aucun passage disponible pour le moment.';
      return;
    }
    buildNavDots();
    loadPassage(0);
  })
  .catch(err => {
    textTitre.textContent = '❌ Impossible de charger comprehension-ecrite.json';
    textBody.textContent  = err.message;
    console.error(err);
  });

// ── Navigation par points ──
function buildNavDots() {
  navDots.innerHTML = '';
  passages.forEach((_, i) => {
    const dot = document.createElement('div');
    dot.className = 'nav-dot' + (i === 0 ? ' active' : '');
    dot.title = `Texte ${i + 1}`;
    // Les dots ne permettent pas de navigation directe (ordre imposé)
    navDots.appendChild(dot);
  });
}

function updateNavDots(activeIdx) {
  navDots.querySelectorAll('.nav-dot').forEach((dot, i) => {
    dot.className = 'nav-dot' + (i < activeIdx ? ' done' : '') + (i === activeIdx ? ' active' : '');
  });
}

// ── Charger un passage ──
function loadPassage(index) {
  const p = passages[index];
  answeredCount = 0;
  clearTimerInterval();
  timeLeft = p.timer || TIMER_SECONDS;

  // Barre globale
  globalFill.style.width  = (index / passages.length * 100) + '%';
  globalLabel.textContent = `Texte ${index + 1} / ${passages.length}`;

  // Navigation
  updateNavDots(index);
  prevTextBtn.style.display = index > 0 ? 'inline-flex' : 'none';

  // ── Panneau texte ──
  textPanelTitle.textContent = `Passage ${index + 1}`;
  textMeta.innerHTML = '';

  // Badge de type
  if (p.type) {
    const badge = document.createElement('span');
    badge.className = 'text-badge';
    const icons = { email:'✉️', article:'📰', notice:'📋', rapport:'📊', annonce:'📣' };
    badge.textContent = (icons[p.type] || '📄') + ' ' + p.type.charAt(0).toUpperCase() + p.type.slice(1);
    textMeta.appendChild(badge);
  }

  // Badge nb de questions
  const nbBadge = document.createElement('span');
  nbBadge.className = 'text-badge';
  nbBadge.style.background = 'rgba(14,165,233,0.08)';
  nbBadge.textContent = `${p.questions.length} question${p.questions.length > 1 ? 's' : ''}`;
  textMeta.appendChild(nbBadge);

  // Titre et corps
  textTitre.textContent = p.titre || `Passage ${index + 1}`;
  textBody.textContent  = p.texte || '';

  // Source
  if (p.source) {
    textSource.textContent = 'Source : ' + p.source;
    textSource.style.display = 'block';
  } else {
    textSource.style.display = 'none';
  }

  // Scroll le texte en haut
  document.querySelector('.text-panel-body').scrollTop = 0;

  // ── Panneau questions ──
  questionsList.innerHTML = '';
  nextBtn.style.display = 'none';
  scoreFinalDiv.style.display = 'none';

  questionsSubtitle.textContent = `${p.questions.length} question${p.questions.length > 1 ? 's' : ''} sur ce texte`;

  // Timer
  updateTimer(timeLeft);
  timerPill.className = 'timer-pill';

  // Questions
  p.questions.forEach((q, qi) => {
    const card = document.createElement('div');
    card.className = 'question-card';

    const num = document.createElement('div');
    num.className = 'q-num';
    num.textContent = `Question ${qi + 1} / ${p.questions.length}`;
    card.appendChild(num);

    const txt = document.createElement('div');
    txt.className = 'q-text';
    txt.textContent = q.texte;
    card.appendChild(txt);

    const opts = document.createElement('div');
    opts.className = 'options';
    Object.entries(q.options).forEach(([key, val]) => {
      const btn = document.createElement('button');
      const letter = document.createElement('span');
      letter.className = 'opt-letter';
      letter.textContent = key.toUpperCase();
      const label = document.createElement('span');
      label.textContent = val;
      btn.appendChild(letter);
      btn.appendChild(label);
      btn.dataset.key = key;
      btn.addEventListener('click', () => validateAnswer(btn, q, opts, qi, p.questions.length));
      opts.appendChild(btn);
    });
    card.appendChild(opts);

    // Explication (cachée au départ)
    if (q.explication) {
      const expDiv = document.createElement('div');
      expDiv.className = 'explication';
      expDiv.innerHTML = `<i class="fas fa-lightbulb" style="color:#0ea5e9;margin-right:6px;"></i>${q.explication}`;
      card.appendChild(expDiv);
    }

    questionsList.appendChild(card);
  });

  // Progression questions
  updateQProgress(0, p.questions.length);

  // Démarrer le timer
  startTimer();
}

// ── Valider une réponse ──
function validateAnswer(btn, q, optsDiv, qi, total) {
  // Désactiver toutes les options de cette question
  optsDiv.querySelectorAll('button').forEach(b => b.disabled = true);

  totalAnswered++;
  answeredCount++;

  if (btn.dataset.key === q.reponse) {
    btn.classList.add('correct');
    score++;
  } else {
    btn.classList.add('incorrect');
    optsDiv.querySelectorAll('button').forEach(b => {
      if (b.dataset.key === q.reponse) b.classList.add('correct');
    });
  }

  // Afficher l'explication
  const card = btn.closest('.question-card');
  const expDiv = card?.querySelector('.explication');
  if (expDiv) expDiv.classList.add('show');

  // Mettre à jour la progression
  updateQProgress(answeredCount, total);

  // Si toutes les questions du passage sont répondues → afficher bouton suivant
  if (answeredCount >= total) {
    clearTimerInterval();
    setTimeout(() => {
      if (currentIdx < passages.length - 1) {
        nextBtn.style.display = 'inline-flex';
        nextBtn.onclick = () => {
          currentIdx++;
          nextBtn.style.display = 'none';
          loadPassage(currentIdx);
        };
      } else {
        showFinalScore();
      }
    }, 600);
  }
}

// ── Progression questions ──
function updateQProgress(answered, total) {
  const pct = total > 0 ? Math.round((answered / total) * 100) : 0;
  qProgressFill.style.width = pct + '%';
  qProgressLabel.textContent = `${answered} / ${total}`;
}

// ── Timer ──
function startTimer() {
  timerVal.textContent = timeLeft;

  timerInterval = setInterval(() => {
    timeLeft--;
    updateTimer(timeLeft);

    if (timeLeft <= 15) timerPill.className = 'timer-pill urgent';
    if (timeLeft <= 0) {
      clearTimerInterval();
      forceRevealAnswers();
      setTimeout(() => {
        if (currentIdx < passages.length - 1) {
          currentIdx++;
          loadPassage(currentIdx);
        } else {
          showFinalScore();
        }
      }, 2000);
    }
  }, 1000);
}

function updateTimer(s) {
  timerVal.textContent = s;
}

function clearTimerInterval() {
  if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
}

function forceRevealAnswers() {
  const p = passages[currentIdx];
  if (!p) return;
  p.questions.forEach((q, qi) => {
    const optsDiv = questionsList.querySelectorAll('.options')[qi];
    if (!optsDiv) return;
    optsDiv.querySelectorAll('button').forEach(b => {
      b.disabled = true;
      if (b.dataset.key === q.reponse) b.classList.add('correct');
    });
    const expDiv = questionsList.querySelectorAll('.question-card')[qi]?.querySelector('.explication');
    if (expDiv) expDiv.classList.add('show');
  });
}

// ── Score final ──
function showFinalScore() {
  clearTimerInterval();
  timerPill.style.display = 'none';

  // Barre globale à 100%
  globalFill.style.width = '100%';
  globalLabel.textContent = `Terminé ! ${passages.length} / ${passages.length} textes`;
  updateNavDots(passages.length);

  questionsList.style.display = 'none';
  nextBtn.style.display = 'none';
  scoreFinalDiv.style.display = 'block';

  const pct = totalAnswered > 0 ? Math.round((score / totalAnswered) * 100) : 0;
  scoreFinalVal.textContent = `${score} / ${totalAnswered}`;
  scoreFinalMsg.textContent = pct >= 70
    ? `🎉 Excellent ! Score de ${pct}% — Très bonne compréhension écrite !`
    : pct >= 45
    ? `👍 Bien ! Score de ${pct}% — Continuez à vous entraîner.`
    : `📚 Score de ${pct}% — Relisez les textes et réessayez.`;

  // Sauvegarder
  if (totalAnswered > 0) {
    fetch(SAVE_ENDPOINT, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type_activite: 'qcm', score, total_questions: totalAnswered })
    })
    .then(r => r.json())
    .then(d => { if (d.success) console.log('Score compréhension sauvegardé:', d.score_total); })
    .catch(e => console.warn('Erreur save_score:', e));
  }
}
