let questions     = [];
let currentQuestion = 0;
let score         = 0;
let timer;

const audio     = document.getElementById("audioPlayer");
const replayBtn = document.getElementById("replayAudio");
const questionEl = document.getElementById("question");
const optionsEl  = document.getElementById("options");
const submitBtn  = document.getElementById("submitBtn");
const timerEl    = document.getElementById("time");
const scoreEl    = document.getElementById("score");
const homeBtn    = document.getElementById("homeBtn");
const restartBtn = document.getElementById("restartBtn");
const endButtons = document.getElementById("end-buttons");

// Masquer le bouton Valider — validation directe au clic
if (submitBtn) submitBtn.style.display = "none";

// CORRECTION : le fichier JSON s'appelle texte-a-trou.json (sans accent)
fetch("texte-a-trou.json?v=" + Date.now())
    .then(res => res.json())
    .then(data => {
        questions = shuffle(data).slice(0, 15);
        displayQuestion();
    })
    .catch(err => {
        console.error("Erreur chargement texte-a-trou.json :", err);
        if (questionEl) questionEl.textContent = "Erreur de chargement des questions.";
    });

function shuffle(array) {
    return array.sort(() => Math.random() - 0.5);
}

function displayQuestion() {
    clearInterval(timer);
    if (timerEl) timerEl.textContent = "";

    const q = questions[currentQuestion];
    if (!q) return;

    // Audio
    audio.src = q.audio;
    audio.load();
    audio.play().catch(() => {});

    if (replayBtn) {
        replayBtn.onclick = () => {
            audio.currentTime = 0;
            audio.play().catch(() => {});
        };
    }

    // Question (texte à trou)
    if (questionEl) questionEl.textContent = `${currentQuestion + 1}. ${q.texte}`;

    // Options — VALIDATION AU CLIC DIRECT
    optionsEl.innerHTML = "";
    Object.entries(q.options).forEach(([key, text]) => {
        const btn = document.createElement("button");
        btn.textContent = text;
        btn.dataset.option = key;
        btn.onclick = () => validateAnswer(btn, q.reponse);
        optionsEl.appendChild(btn);
    });

    // Timer démarre à la fin de l'audio
    audio.onended = () => startTimer(q.reponse);
}

function startTimer(correctAnswer) {
    let timeLeft = 10;
    if (timerEl) timerEl.textContent = timeLeft;

    timer = setInterval(() => {
        timeLeft--;
        if (timerEl) timerEl.textContent = timeLeft;

        if (timeLeft <= 0) {
            clearInterval(timer);
            showCorrectAnswer(correctAnswer);
            disableOptions();
            setTimeout(goNext, 1500);
        }
    }, 1000);
}

function validateAnswer(selectedBtn, correctAnswer) {
    clearInterval(timer);
    disableOptions();

    if (selectedBtn.dataset.option === correctAnswer) {
        score++;
        selectedBtn.classList.add("correct");
    } else {
        selectedBtn.classList.add("incorrect");
        [...optionsEl.children].forEach(b => {
            if (b.dataset.option === correctAnswer) b.classList.add("correct");
        });
    }

    setTimeout(goNext, 1500);
}

function goNext() {
    currentQuestion++;
    if (currentQuestion < questions.length) {
        displayQuestion();
    } else {
        showScore();
    }
}

function disableOptions() {
    [...optionsEl.children].forEach(b => (b.disabled = true));
}

function showCorrectAnswer(correctAnswer) {
    [...optionsEl.children].forEach(btn => {
        if (btn.dataset.option === correctAnswer) btn.classList.add("correct");
    });
}

function showScore() {
    document.getElementById("quiz-box").style.display = "none";
    scoreEl.style.display = "block";
    scoreEl.textContent = `✅ Score : ${score} / ${questions.length}`;
    endButtons.style.display = "flex";

    fetch('api/save_score.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            type_activite: 'texte_trou',
            score: score,
            total_questions: questions.length
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Score texte-à-trou sauvegardé ! Score total :', data.score_total);
        } else {
            console.warn('Erreur sauvegarde :', data.error);
        }
    })
    .catch(error => console.error('Erreur réseau :', error));
}

if (homeBtn)    homeBtn.onclick    = () => { window.location.href = "interface_principale.php"; };
if (restartBtn) restartBtn.onclick = () => { window.location.reload(); };
