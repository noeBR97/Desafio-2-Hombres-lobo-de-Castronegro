const nickElement = document.getElementById('nombre-usuario');
const statsPlayedElement = document.getElementById('usuario-partidas-jugadas');
const statsWinsElement = document.getElementById('usuario-victorias-partidas');
const statsLossesElement = document.getElementById('usuario-derrotas-partidas');
const gameListElement = document.getElementById('lista-partidas'); 

const userData = {
  username: 'diego.grciia',
  stats: {
    played: 21,
    wins: 10,
    losses: 11
  }
};

const availableGames = [
  { id: 1, name: 'Partida de Noelia', players: 15, maxPlayers: 30 },
  { id: 2, name: 'Lobos contra Aguila', players: 28, maxPlayers: 30 },
  { id: 3, name: 'Solo gente seria', players: 18, maxPlayers: 30 }
];

if (nickElement) {
  nickElement.textContent = userData.username; 
}
if (statsPlayedElement) {
  statsPlayedElement.textContent = `Partidas jugadas: ${userData.stats.played}`;
}
if (statsWinsElement) {
  statsWinsElement.textContent = `Victorias: ${userData.stats.wins}`; 
}
if (statsLossesElement) {
  statsLossesElement.textContent = `Derrotas: ${userData.stats.losses}`; 
}

if (gameListElement) {
    
    gameListElement.innerHTML = ''; 

    for (const game of availableGames) {
        
        const li = document.createElement('li');
        
        li.innerHTML = `
            <span>- ${game.name} (${game.players}/${game.maxPlayers})</span>
            <button class="btn-unirse">Unirse</button>
        `;
        
        gameListElement.appendChild(li);
    }
}

const createGameButton = document.querySelector('.btn-crear');

if (createGameButton) {
    createGameButton.addEventListener('click', () => {
        console.log('Botón "Crear Partida" pulsado');
    });
}