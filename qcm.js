// Variables globales
let questions = [];
let currentQuestion = 0;
let score = 0;
let selectedAnswer = null;

// Éléments DOM
const questionEl = document.getElementById("question");
const optionsEl = document.getElementById("options");
const submitBtn = document.getElementById("submitBtn");
const scoreEl = document.getElementById("score");
const homeBtn = document.getElementById("homeBtn");
const restartBtn = document.getElementById("restartBtn");
const endButtons = document.getElementById("end-buttons");

// Fonction pour démarrer le QCM
function initQuiz() {
    // Charger les questions
    fetch("questions.json")
        .then(response => response.json())
        .then(data => {
            questions = data.slice(0, 15); // Prendre 15 questions
            displayQuestion();
        })
        .catch(error => {
            console.error("Erreur de chargement:", error);
            questionEl.textContent = "Erreur de chargement des questions";
        });
}

// Afficher une question
function displayQuestion() {
    // Réinitialiser
    selectedAnswer = null;
    submitBtn.disabled = true;
    
    // Afficher la question
    const q = questions[currentQuestion];
    questionEl.textContent = `${currentQuestion + 1}. ${q.texte}`;
    
    // Vider les options
    optionsEl.innerHTML = "";
    
    // Créer les boutons d'options
    Object.entries(q.options).forEach(([key, value]) => {
        const button = document.createElement("button");
        button.textContent = value;
        button.dataset.option = key;
        button.onclick = () => selectOption(button);
        optionsEl.appendChild(button);
    });
}

// Sélectionner une option
function selectOption(selectedButton) {
    // Désélectionner toutes les options
    const allButtons = optionsEl.querySelectorAll("button");
    allButtons.forEach(btn => btn.classList.remove("selected"));
    
    // Sélectionner l'option cliquée
    selectedButton.classList.add("selected");
    selectedAnswer = selectedButton.dataset.option;
    
    // Activer le bouton Valider
    submitBtn.disabled = false;
}

// Valider la réponse
function validateAnswer() {
    if (selectedAnswer === null) return;
    
    const correctAnswer = questions[currentQuestion].reponse;
    const allButtons = optionsEl.querySelectorAll("button");
    
    // Désactiver tous les boutons
    allButtons.forEach(btn => btn.disabled = true);
    submitBtn.disabled = true;
    
    // Afficher les résultats
    if (selectedAnswer === correctAnswer) {
        score++;
        allButtons.forEach(btn => {
            if (btn.dataset.option === selectedAnswer) {
                btn.classList.add("correct");
            }
        });
    } else {
        allButtons.forEach(btn => {
            if (btn.dataset.option === selectedAnswer) {
                btn.classList.add("incorrect");
            }
            if (btn.dataset.option === correctAnswer) {
                btn.classList.add("correct");
            }
        });
    }
    
    // Passer à la question suivante après 2 secondes
    setTimeout(nextQuestion, 2000);
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
    // Masquer la boîte de quiz
    document.getElementById("quiz-box").style.display = "none";
    
    // Afficher le score
    scoreEl.style.display = "block";
    scoreEl.textContent = `Score final: ${score} / ${questions.length}`;
    
    // Afficher les boutons de fin
    endButtons.style.display = "block";

    // Sauvegarder le score en base de données
    saveScore('qcm', score, questions.length);
}

// Sauvegarder le score via l'API
function saveScore(type, scoreVal, total) {
    fetch('api/save_score.php', {
        method: 'POST',
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
            console.log('Score sauvegardé ! Score total:', data.score_total, 'Progression:', data.progression + '%');
        } else {
            console.warn('Erreur sauvegarde:', data.error);
        }
    })
    .catch(error => console.error('Erreur réseau:', error));
}

// Event listeners
submitBtn.onclick = validateAnswer;

homeBtn.onclick = () => {
    window.location.href = "interface_principale.php";
};

restartBtn.onclick = () => {
    window.location.reload();
};

// Démarrer le quiz quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    initQuiz();
});

// Démarrer aussi si le DOM est déjà prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initQuiz);
} else {
    initQuiz();
}
