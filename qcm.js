// Variables globales
let questions = [];
let currentQuestion = 0;
let score = 0;

// Éléments DOM
const questionEl  = document.getElementById("question");
const optionsEl   = document.getElementById("options");
const submitBtn   = document.getElementById("submitBtn");
const scoreEl     = document.getElementById("score");
const homeBtn     = document.getElementById("homeBtn");
const restartBtn  = document.getElementById("restartBtn");
const endButtons  = document.getElementById("end-buttons");

// Masquer le bouton Valider (validation au clic)
if (submitBtn) submitBtn.style.display = "none";

// Démarrer le quiz au chargement du DOM (une seule fois)
document.addEventListener('DOMContentLoaded', initQuiz);

// Charger les questions depuis le JSON
function initQuiz() {
    fetch("questions.json?v=" + Date.now())
        .then(response => response.json())
        .then(data => {
            questions = shuffle(data).slice(0, 15);
            displayQuestion();
        })
        .catch(error => {
            console.error("Erreur de chargement:", error);
            if (questionEl) questionEl.textContent = "Erreur de chargement des questions.";
        });
}

function shuffle(array) {
    return array.sort(() => Math.random() - 0.5);
}

// Afficher une question
function displayQuestion() {
    if (!questions[currentQuestion]) return;
    const q = questions[currentQuestion];
    questionEl.textContent = `${currentQuestion + 1}. ${q.texte}`;
    optionsEl.innerHTML = "";

    Object.entries(q.options).forEach(([key, value]) => {
        const button = document.createElement("button");
        button.textContent = value;
        button.dataset.option = key;
        // VALIDATION AU CLIC DIRECT (sans bouton Valider)
        button.onclick = () => validateAnswer(button, q.reponse);
        optionsEl.appendChild(button);
    });
}

// Valider la réponse au clic
function validateAnswer(selectedButton, correctAnswer) {
    const allButtons = optionsEl.querySelectorAll("button");

    // Désactiver tous les boutons immédiatement pour éviter double-clic
    allButtons.forEach(btn => (btn.disabled = true));

    if (selectedButton.dataset.option === correctAnswer) {
        score++;
        selectedButton.classList.add("correct");
    } else {
        selectedButton.classList.add("incorrect");
        allButtons.forEach(btn => {
            if (btn.dataset.option === correctAnswer) btn.classList.add("correct");
        });
    }

    // Passer à la question suivante après 1.5 secondes
    setTimeout(nextQuestion, 1500);
}

// Question suivante
function nextQuestion() {
    currentQuestion++;
    if (currentQuestion < questions.length) {
        displayQuestion();
    } else {
        showFinalScore();
    }
}

// Afficher le score final
function showFinalScore() {
    document.getElementById("quiz-box").style.display = "none";
    scoreEl.style.display = "block";
    scoreEl.textContent = `Score final : ${score} / ${questions.length}`;
    endButtons.style.display = "flex";
    saveScore('qcm', score, questions.length);
}

// Sauvegarder le score via l'API
function saveScore(type, scoreVal, total) {
    fetch('api/save_score.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            type_activite: type,
            score: scoreVal,
            total_questions: total
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Score sauvegardé ! Score total :', data.score_total, '— Progression :', data.progression + '%');
        } else {
            console.warn('Erreur sauvegarde :', data.error);
        }
    })
    .catch(error => console.error('Erreur réseau :', error));
}

// Boutons de fin
if (homeBtn)    homeBtn.onclick    = () => { window.location.href = "interface_principale.php"; };
if (restartBtn) restartBtn.onclick = () => { window.location.reload(); };
