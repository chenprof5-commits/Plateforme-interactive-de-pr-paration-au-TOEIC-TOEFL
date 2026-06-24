// === CONFIGURATION DES PARTIES DE L'EXAMEN ===
// Architecture TOEIC : 6+25+39+30+46+54 = 200 questions au total
// Parts 1 et 2 : JSON existants activés
// Parts 3 à 6 : chemins JSON prêts — à activer dès que les fichiers JSON sont créés
const parts = [
  {
    id: "part1",
    json: "examen-photographies.json",
    type: "image",
    audioDir: "audios-examen/",
    label: "Partie 1 — Photographies",
    totalQuestions: 6
  },
  {
    id: "part2",
    json: "examen2.json",
    type: "audio",
    audioDir: "audio-examen2/",
    label: "Partie 2 — Questions-Réponses",
    totalQuestions: 25
  },
  // ─── Décommenter dès que les fichiers JSON sont disponibles ───────────────
  // { id: "part3", json: "donnees/conversations-examen.json",   type: "audio", audioDir: "audios-conversations/", label: "Partie 3 — Conversations",        totalQuestions: 39 },
  // { id: "part4", json: "donnees/talks-examen.json",           type: "audio", audioDir: "audios-talks/",         label: "Partie 4 — Discours Courts",       totalQuestions: 30 },
  // { id: "part5", json: "donnees/texte-a-trou-examen.json",    type: "text",  audioDir: "",                      label: "Partie 5 — Phrases Incomplètes",   totalQuestions: 46 },
  // { id: "part6", json: "donnees/comprehension-examen.json",   type: "text",  audioDir: "",                      label: "Partie 6 — Compréhension Écrite",  totalQuestions: 54 },
];

// Total théorique de l'examen complet pour la barre de progression
const TOTAL_EXAM_QUESTIONS = 200;

// === VARIABLES GLOBALES ===
let score           = 0;
let totalAnswered   = 0;
let questionIndex   = 0;
let currentQuestions = [];
let currentPartIndex = 0;
let timer;
const timePerQuestion = 27;  // secondes

// === DÉMARRER L'EXAMEN ===
loadPart(parts[currentPartIndex]);

// ─────────────────────────────────────────────────────────────
function loadPart(part) {
  fetch(part.json + "?v=" + Date.now())
    .then(res => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    })
    .then(data => {
      currentQuestions = Array.isArray(data) ? data : [];
      questionIndex    = 0;

      document.querySelectorAll(".exam-section").forEach(sec => sec.style.display = "none");
      const section = document.getElementById(part.id);
      if (section) section.style.display = "block";

      loadQuestion(part);
      updateProgress();
    })
    .catch(err => {
      console.error(`Impossible de charger ${part.json} :`, err);
      // Passer à la partie suivante si le JSON est introuvable
      currentPartIndex++;
      if (currentPartIndex < parts.length) {
        loadPart(parts[currentPartIndex]);
      } else {
        showFinalScore();
      }
    });
}

function loadQuestion(part) {
  clearInterval(timer);

  const section   = document.getElementById(part.id);
  const container = section ? section.querySelector(".question-block") : null;
  if (!container) return;
  container.innerHTML = "";

  // Toutes les questions de cette partie sont terminées → partie suivante
  if (questionIndex >= currentQuestions.length) {
    currentPartIndex++;
    if (currentPartIndex < parts.length) {
      loadPart(parts[currentPartIndex]);
    } else {
      showFinalScore();
    }
    return;
  }

  const q = currentQuestions[questionIndex];

  // ── AUDIO ───────────────────────────────────────────────────────────────────
  if (part.audioDir && q.audio) {
    const audioContainer = document.createElement("div");
    audioContainer.className = "audio-container";

    const audioElement      = document.createElement("audio");
    audioElement.src        = part.audioDir + q.audio;
    audioElement.controls   = true;
    audioElement.preload    = "auto";
    audioElement.autoplay   = true;

    audioElement.onerror = () => {
      const err       = document.createElement("p");
      err.textContent = "❌ Audio introuvable.";
      err.style.color = "var(--danger-color, #ef4444)";
      audioContainer.appendChild(err);
    };

    const replayBtn       = document.createElement("button");
    replayBtn.className   = "replay-btn";
    replayBtn.innerHTML   = '<i class="fas fa-redo"></i> Rejouer';
    replayBtn.onclick     = () => { audioElement.currentTime = 0; audioElement.play().catch(() => {}); };

    audioContainer.appendChild(audioElement);
    audioContainer.appendChild(replayBtn);
    container.appendChild(audioContainer);
  }

  // ── IMAGE ────────────────────────────────────────────────────────────────────
  if (part.type === "image" && q.image) {
    const img     = document.createElement("img");
    img.src       = q.image;
    img.alt       = `Question ${questionIndex + 1}`;
    img.className = "question-image";
    container.appendChild(img);
  }

  // ── TEXTE ────────────────────────────────────────────────────────────────────
  if (part.type === "text" && (q.texte || q.question)) {
    const p       = document.createElement("p");
    p.textContent = q.texte || q.question;
    p.className   = "question-text";
    container.appendChild(p);
  }

  // ── OPTIONS (clic direct = validation) ──────────────────────────────────────
  const optionsDiv    = document.createElement("div");
  optionsDiv.className = "options";

  for (const key in q.options) {
    const btn         = document.createElement("button");
    // Afficher A / B / C / D si la valeur est un simple placeholder
    const val         = q.options[key];
    btn.textContent   = (val === key) ? key.toUpperCase() : val;
    btn.dataset.key   = key;
    btn.onclick       = () => validateAnswer(btn, q.reponse, optionsDiv, part);
    optionsDiv.appendChild(btn);
  }
  container.appendChild(optionsDiv);

  // ── TIMER ────────────────────────────────────────────────────────────────────
  const timerDisplay     = document.createElement("div");
  timerDisplay.className = "timer-display";
  timerDisplay.innerHTML = '<i class="fas fa-clock"></i> <span class="time-left">' + timePerQuestion + '</span>s';
  container.appendChild(timerDisplay);

  startTimer(timerDisplay, () => {
    showCorrectAnswer(q.reponse, optionsDiv);
    setTimeout(() => { questionIndex++; loadQuestion(part); }, 1500);
  });
}

// ─────────────────────────────────────────────────────────────
function validateAnswer(button, correctKey, optionsDiv, part) {
  clearInterval(timer);
  const buttons = optionsDiv.querySelectorAll("button");
  buttons.forEach(btn => (btn.disabled = true));
  totalAnswered++;

  if (button.dataset.key === correctKey) {
    button.classList.add("correct");
    score++;
  } else {
    button.classList.add("incorrect");
    buttons.forEach(b => { if (b.dataset.key === correctKey) b.classList.add("correct"); });
  }

  updateProgress();
  setTimeout(() => { questionIndex++; loadQuestion(part); }, 1500);
}

function showCorrectAnswer(correctKey, optionsDiv) {
  optionsDiv.querySelectorAll("button").forEach(btn => {
    btn.disabled = true;
    if (btn.dataset.key === correctKey) btn.classList.add("correct");
  });
  totalAnswered++;
}

function startTimer(timerDisplay, callback) {
  let timeLeft   = timePerQuestion;
  const timeSpan = timerDisplay.querySelector(".time-left");
  if (timeSpan) timeSpan.textContent = timeLeft;

  timer = setInterval(() => {
    timeLeft--;
    if (timeSpan) timeSpan.textContent = timeLeft;

    if (timeLeft <= 5) {
      timerDisplay.style.color = "#ef4444";
    } else if (timeLeft <= 10) {
      timerDisplay.style.color = "#f59e0b";
    }

    if (timeLeft <= 0) {
      clearInterval(timer);
      callback();
    }
  }, 1000);
}

function updateProgress() {
  const totalQuestions = parts.reduce((sum, p) => {
    // Estimation : utiliser le nombre de questions chargées pour la partie en cours
    return sum;
  }, 0);

  const fill = document.getElementById("progressFill");
  const text = document.getElementById("progressText");
  if (!fill || !text) return;

  // Progression par parties (chaque partie = 1/N de la barre)
  const partsDone = currentPartIndex;
  const pct       = Math.round((partsDone / parts.length) * 100);
  fill.style.width  = pct + "%";
  text.textContent  = pct + "%";
}

function showFinalScore() {
  clearInterval(timer);
  document.querySelectorAll(".exam-section").forEach(sec => (sec.style.display = "none"));

  const resultSection = document.getElementById("result-section");
  if (resultSection) resultSection.style.display = "block";

  const finalScoreEl = document.getElementById("final-score");
  if (finalScoreEl) {
    finalScoreEl.textContent = `Votre score : ${score} / ${totalAnswered}`;
  }

  // Mettre la barre à 100 %
  const fill = document.getElementById("progressFill");
  const text = document.getElementById("progressText");
  if (fill) fill.style.width = "100%";
  if (text) text.textContent  = "100%";

  // Sauvegarder le score en base de données
  if (totalAnswered > 0) {
    fetch('api/save_score.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type_activite:   'examen',
        score:           score,
        total_questions: totalAnswered
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        console.log('Score examen sauvegardé ! Total :', data.score_total);
      } else {
        console.warn('Erreur sauvegarde examen :', data.error);
      }
    })
    .catch(err => console.error('Erreur réseau :', err));
  }
}
