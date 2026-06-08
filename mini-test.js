let questions = [];
let currentQuestion = 0;
let score = 0;
let timer;
let timeLeft = 10;

const audio = document.getElementById("audioPlayer");
const replayBtn = document.getElementById("replayAudio");
const questionImg = document.getElementById("questionImage");
const optionsEl = document.getElementById("options");
const submitBtn = document.getElementById("submitBtn");
const timerEl = document.getElementById("time");
const scoreEl = document.getElementById("score");
const homeBtn = document.getElementById("homeBtn");
const restartBtn = document.getElementById("restartBtn");
const endButtons = document.getElementById("end-buttons");

fetch("mini-test.json")
  .then(res => res.json())
  .then(data => {
    questions = shuffle(data).slice(0, 15); 
    displayQuestion();
  });

function shuffle(array) {
  return array.sort(() => Math.random() - 0.5);
}

function displayQuestion() {
  clearInterval(timer);
  timeLeft = 10;
  timerEl.textContent = timeLeft;

  const q = questions[currentQuestion];

  // 🎧 Lecture audio
  audio.src = q.audio;
  audio.load();
  audio.play();

  replayBtn.onclick = () => {
    audio.currentTime = 0;
    audio.play();
  };

  // 🖼️ Affichage image
  if (q.image) {
    questionImg.src = q.image;
    questionImg.alt = `Question ${currentQuestion + 1}`;
    questionImg.style.display = "block";
  } else {
    questionImg.style.display = "none";
  }

  // 📋 Affichage des options
  optionsEl.innerHTML = "";
  Object.entries(q.options).forEach(([key, text]) => {
    const btn = document.createElement("button");
    btn.textContent = text;
    btn.dataset.option = key;
    btn.onclick = () => selectOption(btn);
    optionsEl.appendChild(btn);
  });

  submitBtn.disabled = true;

  // ⏱️ Démarre le timer après l'audio
  audio.onended = () => {
    timer = setInterval(() => {
      timeLeft--;
      timerEl.textContent = timeLeft;
      if (timeLeft === 0) {
        clearInterval(timer);
        showCorrectAnswer();
        disableOptions();
        submitBtn.disabled = false;
      }
    }, 1000);
  };
}

let selected = null;

function selectOption(btn) {
  [...optionsEl.children].forEach(b => b.classList.remove("selected"));
  btn.classList.add("selected");
  selected = btn;
  submitBtn.disabled = false;
}

submitBtn.onclick = () => {
  clearInterval(timer);
  const correct = questions[currentQuestion].reponse;
  if (selected) {
    const chosen = selected.dataset.option;
    if (chosen === correct) {
      selected.classList.add("correct");
      score++;
    } else {
      selected.classList.add("incorrect");
      [...optionsEl.children].forEach(b => {
        if (b.dataset.option === correct) b.classList.add("correct");
      });
    }
  } else {
    [...optionsEl.children].forEach(b => {
      if (b.dataset.option === correct) b.classList.add("correct");
    });
  }

  disableOptions();
  submitBtn.disabled = true;

  setTimeout(() => {
    currentQuestion++;
    if (currentQuestion < questions.length) {
      selected = null;
      displayQuestion();
    } else {
      showScore();
    }
  }, 1500);
};

function disableOptions() {
  [...optionsEl.children].forEach(b => (b.disabled = true));
}

function showCorrectAnswer() {
  const correct = questions[currentQuestion].reponse;
  [...optionsEl.children].forEach(btn => {
    if (btn.dataset.option === correct) {
      btn.classList.add("correct");
    }
  });
}

function showScore() {
  document.getElementById("quiz-box").style.display = "none";
  scoreEl.style.display = "block";
  scoreEl.textContent = `✅ Score: ${score} / ${questions.length}`;
  endButtons.style.display = "block";

  // Sauvegarder le score en base de données
  fetch('api/save_score.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      type_activite: 'mini_test',
      score: score,
      total_questions: questions.length
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Score mini-test sauvegardé ! Score total:', data.score_total);
    }
  })
  .catch(error => console.error('Erreur réseau:', error));
}

homeBtn.onclick = () => {
  window.location.href = "interface_principale.php";
};

restartBtn.onclick = () => {
  window.location.reload();
};
