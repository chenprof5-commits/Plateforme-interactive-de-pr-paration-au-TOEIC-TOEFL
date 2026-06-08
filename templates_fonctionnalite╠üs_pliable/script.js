// Fonction pour ouvrir un mode spécifique
function openMode(mode) {
    const content = document.getElementById('content');
    content.innerHTML = `<h1>Mode ${mode}</h1><p>Vous avez sélectionné le mode ${mode}. Bonne préparation !</p>`;
}
