// === CONFIGURATION DES PARTIES ===
const parts = [
  { id: "part1", json: "examen-photographies.json", type: "image", audioDir: "audios-examen/" },
  { id: "part2", json: "examen2.json", type: "audio", audioDir: "audio-examen2/" },
  { id: "part3", json: "données/conversation-examen.json", type: "audio", audioDir: "audios/" },
  { id: "part4", json: "données/conversation-examen.json", type: "audio", audioDir: "audios/" },
  { id: "part5", json: "données/examen-texte-à-trou.json", type: "text" },
  { id: "part6", json: "données/examen-complétion.json", type: "text" },
  { id: "part7", json: "données/Reading-examen.json", type: "text" }
];

// === VARIABLES GLOBALES ===
let score = 0;
let questionIndex = 0;
let currentQuestions = [];
let currentPartIndex = 0;
let timer;
let timePerQuestion = 27;

// === DÉMARRER L’EXAMEN ===
loadPart(parts[currentPartIndex]);

function loadPart(part) {
  fetch(part.json)
    .then(res => res.json())
    .then(data => {
      currentQuestions = data;
      questionIndex = 0;
      document.querySelectorAll(".exam-section").forEach(sec => sec.style.display = "none");
      document.getElementById(part.id).style.display = "block";
      loadQuestion(part);
    });
}

function loadQuestion(part) {
  const container = document.querySelector(`#${part.id} .question-block`);
  container.innerHTML = "";

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

  // === AUDIO CHARGÉ AVEC CONTROLS ===
  let audioElement;
  if (part.audioDir && q.audio) {
    const audioContainer = document.createElement("div");
    audioContainer.className = "audio-container";

    audioElement = document.createElement("audio");
    audioElement.src = part.audioDir + q.audio;
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

  // === IMAGE ===
  if (part.type === "image" && q.image) {
    const img = document.createElement("img");
    img.src = q.image;
    container.appendChild(img);
  }

  // === TEXTE ===
  if (part.type === "text" && q.question) {
    const p = document.createElement("p");
    p.textContent = q.question;
    container.appendChild(p);
  }

  // === OPTIONS ===
  const optionsDiv = document.createElement("div");
  optionsDiv.className = "options";
  for (let key in q.options) {
    const btn = document.createElement("button");
    btn.textContent = q.options[key];
    btn.dataset.key = key;
    btn.onclick = () => validateAnswer(btn, q.reponse, optionsDiv);
    optionsDiv.appendChild(btn);
  }
  container.appendChild(optionsDiv);

  // === TIMER ===
  const timerDisplay = document.createElement("div");
  timerDisplay.id = "timer";
  timerDisplay.innerHTML = '<i class="fas fa-clock"></i> <span id="timeLeft">27</span>s restantes';
  container.appendChild(timerDisplay);

  startTimer(timerDisplay, () => {
    showCorrectAnswer(q.reponse, optionsDiv);
    setTimeout(() => {
      questionIndex++;
      loadQuestion(part);
    }, 1500);
  });
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
    loadQuestion(parts[currentPartIndex]);
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

function startTimer(timerDisplay, callback) {
  let timeLeft = timePerQuestion;
  const timeSpan = timerDisplay.querySelector('#timeLeft');
  timeSpan.textContent = timeLeft;
  
  timer = setInterval(() => {
    timeLeft--;
    timeSpan.textContent = timeLeft;
    
    // Changement de couleur selon le temps restant
    if (timeLeft <= 5) {
      timerDisplay.style.color = 'var(--error-color)';
      timerDisplay.style.animation = 'pulse 1s infinite';
    } else if (timeLeft <= 10) {
      timerDisplay.style.color = 'var(--warning-color)';
    }
    
    if (timeLeft <= 0) {
      clearInterval(timer);
      callback();
    }
  }, 1000);
}

function showFinalScore() {
  document.querySelectorAll(".exam-section").forEach(sec => sec.style.display = "none");
  const resultSection = document.getElementById("result-section");
  resultSection.style.display = "block";
  document.getElementById("final-score").textContent = `Votre score est ${score} / ${totalQuestions()}`;

  // Sauvegarder le score en base de données
  fetch('api/save_score.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      type_activite: 'examen',
      score: score,
      total_questions: totalQuestions()
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Score examen sauvegardé ! Score total:', data.score_total);
    }
  })
  .catch(error => console.error('Erreur réseau:', error));
}

function totalQuestions() {
  return 100;
}
