/**
 * talks.js — Logique du module Talks
 *
 * FORMAT DU FICHIER JSON (talks.json) :
 * ─────────────────────────────────────
 * [
 *   {
 *     "id": "t1",
 *     "titre": "Airport Announcement",
 *     "audio": "audios-talks/t1.mp3",
 *     "questions": [
 *       {
 *         "texte": "What is the main topic of this announcement?",
 *         "options": { "a": "A flight delay", "b": "A gate change", "c": "Boarding instructions" },
 *         "reponse": "c"
 *       },
 *       {
 *         "texte": "What time does the flight depart?",
 *         "options": { "a": "10:30 AM", "b": "11:00 AM", "c": "11:45 AM" },
 *         "reponse": "b"
 *       },
 *       {
 *         "texte": "Where should passengers go?",
 *         "options": { "a": "Gate 12", "b": "Gate 7", "c": "The information desk" },
 *         "reponse": "a"
 *       }
 *     ]
 *   },
 *   { ... talk suivant ... }
 * ]
 *
 * NOTES :
 *  - Chaque talk doit avoir EXACTEMENT 3 questions.
 *  - Les fichiers audio (.mp3) doivent être dans le dossier audios-talks/
 *  - La clé "reponse" doit être "a", "b" ou "c" (minuscule).
 *  - La clé "id" est optionnelle mais recommandée.
 */

// ── Configuration ──
const TIMER_SECONDS  = 45;   // secondes pour répondre aux 3 questions
const JSON_FILE      = 'talks.json';
const SAVE_ENDPOINT  = 'api/save_score.php';

// ── État global ──
let talks         = [];
let currentIndex  = 0;
let score         = 0;
let totalAnswered = 0;
let timerInterval = null;
let timeLeft      = TIMER_SECONDS;
let audioPlayed   = false;  // true quand l'audio a été joué au moins une fois
let audioEnded    = false;  // true quand l'audio est terminé → déverrouille questions

// ── Éléments DOM ──
const audioEl        = document.getElementById('audioEl');
const playBtn        = document.getElementById('playBtn');
const playIcon       = document.getElementById('playIcon');
const audioFill      = document.getElementById('audioFill');
const audioDuration  = document.getElementById('audioDuration');
const audioLabel     = document.getElementById('audioLabel');
const audioWave      = document.getElementById('audioWave');
const replayBtn      = document.getElementById('replayBtn');
const audioStatus    = document.getElementById('audioStatus');
const audioStatusTxt = document.getElementById('audioStatusText');
const questionsLocked= document.getElementById('questionsLocked');
const questionsContainer = document.getElementById('questionsContainer');
const timerDisplay   = document.getElementById('timerDisplay');
const timerValue     = document.getElementById('timerValue');
const resultSection  = document.getElementById('resultSection');
const resultScore    = document.getElementById('resultScore');
const resultLabel    = document.getElementById('resultLabel');
const nextTalkBtn    = document.getElementById('nextTalkBtn');
const progressBar    = document.getElementById('progressBar');
const progressLabel  = document.getElementById('progressLabel');
const talkNumber     = document.getElementById('talkNumber');
const talkTitle      = document.getElementById('talkTitle');

// ── Formatage temps ──
function fmtTime(s) {
  const m = Math.floor(s / 60);
  const sec = Math.floor(s % 60);
  return `${m}:${sec.toString().padStart(2,'0')}`;
}

// ── Chargement du JSON ──
fetch(JSON_FILE + '?v=' + Date.now())
  .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
  .then(data => {
    talks = Array.isArray(data) ? data : [];
    if (talks.length === 0) {
      talkTitle.textContent = 'Aucun talk disponible pour le moment.';
      return;
    }
    loadTalk(currentIndex);
  })
  .catch(err => {
    talkTitle.textContent = '❌ Impossible de charger talks.json : ' + err.message;
    console.error(err);
  });

// ── Charger un talk ──
function loadTalk(index) {
  const talk = talks[index];
  audioEnded = false;
  audioPlayed = false;
  clearTimerInterval();
  timeLeft = TIMER_SECONDS;

  // Mise à jour de la progression
  progressBar.style.width = (index / talks.length * 100) + '%';
  progressLabel.textContent = `Talk ${index + 1} / ${talks.length}`;
  talkNumber.textContent = `Talk ${index + 1}`;
  talkTitle.textContent = talk.titre || `Talk ${index + 1}`;

  // Audio
  audioEl.src = talk.audio || '';
  audioEl.load();
  audioFill.style.width = '0%';
  audioDuration.textContent = '--:--';
  playIcon.className = 'fas fa-play';
  audioLabel.textContent = 'Appuyez sur ▶ pour écouter le discours';
  audioWave.style.display = 'none';
  replayBtn.style.display = 'none';

  // Status
  audioStatus.className = 'audio-status';
  audioStatusTxt.textContent = 'Écoutez l\'intégralité du discours avant de répondre';

  // Questions verrouillées
  questionsLocked.style.display = 'block';
  questionsContainer.style.display = 'none';
  questionsContainer.innerHTML = '';
  timerDisplay.className = 'timer-display hidden';
  timerValue.textContent = TIMER_SECONDS;
  resultSection.style.display = 'none';
}

// ── Contrôle Play/Pause ──
playBtn.addEventListener('click', () => {
  if (audioEl.paused) {
    audioEl.play().catch(() => {});
    playIcon.className = 'fas fa-pause';
    audioWave.style.display = 'flex';
    audioLabel.textContent = 'Discours en cours...';
    audioPlayed = true;
  } else {
    audioEl.pause();
    playIcon.className = 'fas fa-play';
    audioWave.style.display = 'none';
    audioLabel.textContent = 'Lecture en pause';
  }
});

// ── Réécouter ──
replayBtn.addEventListener('click', () => {
  audioEl.currentTime = 0;
  audioEl.play().catch(() => {});
  playIcon.className = 'fas fa-pause';
  audioWave.style.display = 'flex';
});

// ── Métadonnées audio chargées → afficher durée ──
audioEl.addEventListener('loadedmetadata', () => {
  if (!isNaN(audioEl.duration)) {
    audioDuration.textContent = fmtTime(audioEl.duration);
  }
});

// ── Progression audio ──
audioEl.addEventListener('timeupdate', () => {
  if (audioEl.duration) {
    const pct = (audioEl.currentTime / audioEl.duration) * 100;
    audioFill.style.width = pct + '%';
    audioDuration.textContent = fmtTime(audioEl.duration - audioEl.currentTime);
  }
});

// ─────────────────────────────────────────────────────────────────
// ⭐ AUDIO TERMINÉ → déverrouiller les questions ET démarrer le timer
// ─────────────────────────────────────────────────────────────────
audioEl.addEventListener('ended', () => {
  audioEnded = true;
  playIcon.className = 'fas fa-redo';
  audioWave.style.display = 'none';
  audioDuration.textContent = '0:00';
  audioLabel.textContent = 'Discours terminé';
  replayBtn.style.display = 'inline-flex';

  // Statut : terminé
  audioStatus.className = 'audio-status done';
  audioStatusTxt.textContent = '✓ Discours terminé — répondez aux questions !';

  // Déverrouiller les questions
  unlockQuestions();

  // ⏱️ Démarrer le timer UNIQUEMENT maintenant
  startTimer();
});

// Clic sur la barre de progression audio (scrubbing)
document.getElementById('audioTrack').addEventListener('click', function(e) {
  if (!audioEl.duration) return;
  const rect = this.getBoundingClientRect();
  const pct  = (e.clientX - rect.left) / rect.width;
  audioEl.currentTime = pct * audioEl.duration;
});

// ── Déverrouiller les questions ──
function unlockQuestions() {
  const talk = talks[currentIndex];
  if (!talk || !talk.questions) return;

  questionsLocked.style.display = 'none';
  questionsContainer.style.display = 'block';
  questionsContainer.innerHTML = '';

  talk.questions.forEach((q, qi) => {
    if (qi > 0) {
      const div = document.createElement('div');
      div.className = 'q-divider';
      questionsContainer.appendChild(div);
    }

    const block = document.createElement('div');
    block.className = 'question-block';

    const num = document.createElement('div');
    num.className = 'question-num';
    num.textContent = `Question ${qi + 1} / ${talk.questions.length}`;
    block.appendChild(num);

    const txt = document.createElement('div');
    txt.className = 'question-text';
    txt.textContent = q.texte;
    block.appendChild(txt);

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
      btn.dataset.qi  = qi;
      btn.addEventListener('click', () => validateAnswer(btn, q.reponse, opts, qi));
      opts.appendChild(btn);
    });

    block.appendChild(opts);
    questionsContainer.appendChild(block);
  });
}

// ── Valider une réponse ──
function validateAnswer(btn, correctKey, optsDiv, qi) {
  if (btn.disabled) return;
  clearTimerInterval();

  // Désactiver tous les boutons de CE groupe de question
  const allBtns = optsDiv.querySelectorAll('button');
  allBtns.forEach(b => b.disabled = true);

  totalAnswered++;

  if (btn.dataset.key === correctKey) {
    btn.classList.add('correct');
    score++;
  } else {
    btn.classList.add('incorrect');
    allBtns.forEach(b => { if (b.dataset.key === correctKey) b.classList.add('correct'); });
  }

  // Vérifier si toutes les questions ont été répondues
  const talk = talks[currentIndex];
  const allAnswered = questionsContainer.querySelectorAll('.options button:disabled').length;
  const totalOpts   = questionsContainer.querySelectorAll('.options button').length;

  if (allAnswered >= totalOpts) {
    setTimeout(showResult, 800);
  }
}

// ── Timer (démarre APRÈS l'audio) ──
function startTimer() {
  timeLeft = TIMER_SECONDS;
  timerDisplay.className = 'timer-display';
  timerValue.textContent = timeLeft;

  timerInterval = setInterval(() => {
    timeLeft--;
    timerValue.textContent = timeLeft;

    if (timeLeft <= 10) timerDisplay.className = 'timer-display urgent';
    if (timeLeft <= 0) {
      clearTimerInterval();
      // Désactiver toutes les options non répondues
      questionsContainer.querySelectorAll('.options button:not(:disabled)').forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.4';
      });
      // Révéler les bonnes réponses
      const talk = talks[currentIndex];
      if (talk && talk.questions) {
        talk.questions.forEach((q, qi) => {
          questionsContainer.querySelectorAll('.options')[qi]
            ?.querySelectorAll('button')
            .forEach(b => { if (b.dataset.key === q.reponse) b.classList.add('correct'); });
        });
      }
      setTimeout(showResult, 1000);
    }
  }, 1000);
}

function clearTimerInterval() {
  if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
}

// ── Afficher le résultat ──
function showResult() {
  clearTimerInterval();
  const talk    = talks[currentIndex];
  const total   = talk?.questions?.length || 3;
  const talkScore = score - (window._prevScore || 0);
  window._prevScore = score;

  timerDisplay.className = 'timer-display hidden';
  questionsContainer.style.display = 'none';
  resultSection.style.display = 'block';
  resultScore.textContent = `${talkScore} / ${total}`;

  const pct = Math.round((talkScore / total) * 100);
  if (pct >= 70) {
    resultLabel.textContent = '🎉 Excellent ! Bonne compréhension du discours.';
  } else if (pct >= 40) {
    resultLabel.textContent = '👍 Bien ! Continuez à vous entraîner.';
  } else {
    resultLabel.textContent = '📚 Réécoutez le discours pour mieux comprendre.';
  }

  if (currentIndex >= talks.length - 1) {
    nextTalkBtn.innerHTML = '<i class="fas fa-trophy"></i> Voir le score final';
    nextTalkBtn.onclick = showFinalScore;
  } else {
    nextTalkBtn.innerHTML = '<i class="fas fa-arrow-right"></i> Talk suivant';
    nextTalkBtn.onclick = () => {
      currentIndex++;
      resultSection.style.display = 'none';
      questionsContainer.style.display = 'none';
      loadTalk(currentIndex);
    };
  }
}

// ── Score final ──
function showFinalScore() {
  progressBar.style.width = '100%';
  progressLabel.textContent = `Terminé ! ${talks.length} / ${talks.length} talks`;

  resultScore.textContent = `${score} / ${totalAnswered}`;
  const pct = totalAnswered > 0 ? Math.round((score / totalAnswered) * 100) : 0;
  resultLabel.textContent = `Score global : ${pct}% — ${pct >= 70 ? 'Excellent !' : pct >= 40 ? 'Bien !' : 'À améliorer'}`;
  nextTalkBtn.style.display = 'none';

  // Enregistrer le score
  if (totalAnswered > 0) {
    fetch(SAVE_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type_activite: 'mini_test', score, total_questions: totalAnswered })
    })
    .then(r => r.json())
    .then(d => { if (d.success) console.log('Score talks sauvegardé:', d.score_total); })
    .catch(e => console.warn('Erreur save_score:', e));
  }
}
