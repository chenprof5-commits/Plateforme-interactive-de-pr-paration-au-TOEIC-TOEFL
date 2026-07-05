// === VARIABLES GLOBALES ===
let questions = [];
let questionIndex = 0;
let score = 0;
let timer;
let timePerQuestion = 27;
const audioDir = "audio-examen2/";

const container = document.getElementById("qr-question-container");
const progressFill = document.getElementById("progressFill");
const progressText = document.getElementById("progressText");
const resultSection = document.getElementById("result-section");
const finalScoreText = document.getElementById("final-score");

// === DÉMARRAGE ===
fetch("examen2.json?v=" + Date.now())
  .then(res => res.json())
  .then(data => {
    // Mélanger et sélectionner toutes les questions
    questions = shuffle(data);
    updateProgress();
    loadQuestion();
  })
  .catch(err => {
    console.error("Erreur de chargement des questions :", err);
    container.innerHTML = "<p class='error'>Impossible de charger les questions.</p>";
  });

function shuffle(array) {
  return array.sort(() => Math.random() - 0.5);
}

function updateProgress() {
  if (questions.length === 0) return;
  const pct = Math.round((questionIndex / questions.length) * 100);
  progressFill.style.width = pct + "%";
  progressText.textContent = pct + "%";
}

function loadQuestion() {
  clearInterval(timer);
  container.innerHTML = "";

  if (questionIndex >= questions.length) {
    showFinalScore();
    return;
  }

  updateProgress();
  const q = questions[questionIndex];

  // 1. Audio container
  let audioElement = null; // déclaré ICI (hors du bloc if) pour être accessible partout

  if (q.audio) {
    const audioContainer = document.createElement("div");
    audioContainer.className = "audio-container";

    audioElement = document.createElement("audio");
    audioElement.src = audioDir + q.audio;
    audioElement.controls = true;
    audioElement.preload = "auto";
    audioElement.autoplay = true;

    audioElement.onerror = () => {
      const err = document.createElement("p");
      err.textContent = "❌ Audio introuvable.";
      err.style.color = "var(--error-color)";
      err.style.fontWeight = "600";
      audioContainer.appendChild(err);
    };

    const replayBtn = document.createElement("button");
    replayBtn.innerHTML = '<i class="fas fa-redo"></i> Rejouer l\'audio';
    replayBtn.onclick = () => {
      audioElement.currentTime = 0;
      audioElement.play().catch(() => {
        console.warn("Lecture bloquée par le navigateur.");
      });
    };

    audioContainer.appendChild(audioElement);
    audioContainer.appendChild(replayBtn);
    container.appendChild(audioContainer);
  }

  // 2. Options Container (A, B, C only for Part 2)
  const optionsDiv = document.createElement("div");
  optionsDiv.className = "options";

  const optionLabels = {
    "a": "Option (A)",
    "b": "Option (B)",
    "c": "Option (C)"
  };

  for (let key in q.options) {
    const btn = document.createElement("button");
    btn.textContent = optionLabels[key] || `Option (${key.toUpperCase()})`;
    btn.dataset.key = key;
    btn.onclick = () => validateAnswer(btn, q.reponse, optionsDiv);
    optionsDiv.appendChild(btn);
  }
  container.appendChild(optionsDiv);

  // 3. Timer (caché jusqu'à la fin de l'audio)
  const timerDisplay = document.createElement("div");
  timerDisplay.id = "timer";
  timerDisplay.style.display = "none";
  timerDisplay.innerHTML = '<i class="fas fa-clock"></i> <span id="timeLeft">27</span>s restantes';
  container.appendChild(timerDisplay);

  // ⭐ Timer démarre UNIQUEMENT quand l'audio se termine
  if (audioElement) {
    audioElement.addEventListener("ended", () => {
      timerDisplay.style.display = "flex";
      startTimer(timerDisplay, () => {
        showCorrectAnswer(q.reponse, optionsDiv);
        setTimeout(() => { questionIndex++; loadQuestion(); }, 1500);
      });
    }, { once: true });

    // Fallback : si l'audio plante, démarre timer après erreur
    audioElement.addEventListener("error", () => {
      timerDisplay.style.display = "flex";
      startTimer(timerDisplay, () => {
        showCorrectAnswer(q.reponse, optionsDiv);
        setTimeout(() => { questionIndex++; loadQuestion(); }, 1500);
      });
    }, { once: true });
  } else {
    // Pas d'audio : démarrer le timer immédiatement
    timerDisplay.style.display = "flex";
    startTimer(timerDisplay, () => {
      showCorrectAnswer(q.reponse, optionsDiv);
      setTimeout(() => { questionIndex++; loadQuestion(); }, 1500);
    });
  }
}


function startTimer(display, onTimeout) {
  let timeLeft = timePerQuestion;
  const timeText = display.querySelector("#timeLeft");
  timeText.textContent = timeLeft;

  timer = setInterval(() => {
    timeLeft--;
    timeText.textContent = timeLeft;

    if (timeLeft <= 5) {
      display.style.color = "var(--error-color)";
      display.style.animation = "timer-pulse 1s infinite";
    }

    if (timeLeft <= 0) {
      clearInterval(timer);
      onTimeout();
    }
  }, 1000);
}

function validateAnswer(button, correctKey, optionsDiv) {
  clearInterval(timer);
  const buttons = optionsDiv.querySelectorAll("button");
  buttons.forEach(btn => btn.disabled = true);

  if (button.dataset.key === correctKey) {
    button.classList.add("correct");
    score++;
  } else {
    button.classList.add("incorrect");
    buttons.forEach(b => {
      if (b.dataset.key === correctKey) b.classList.add("correct");
    });
  }

  setTimeout(() => {
    questionIndex++;
    loadQuestion();
  }, 1500);
}

function showCorrectAnswer(correctKey, optionsDiv) {
  const buttons = optionsDiv.querySelectorAll("button");
  buttons.forEach(btn => {
    btn.disabled = true;
    if (btn.dataset.key === correctKey) {
      btn.classList.add("correct");
    }
  });
}

function showFinalScore() {
  progressFill.style.width = "100%";
  progressText.textContent = "100%";
  container.style.display = "none";
  resultSection.style.display = "block";
  finalScoreText.textContent = `Votre score : ${score} / ${questions.length}`;

  // Sauvegarder le score
  fetch("api/save_score.php", {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      type_activite: "examen_audio",
      score: score,
      total_questions: questions.length
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      console.log("Score de Questions-Réponses sauvegardé ! Score total:", data.score_total);
    }
  })
  .catch(err => console.error("Erreur lors de la sauvegarde du score :", err));
}
