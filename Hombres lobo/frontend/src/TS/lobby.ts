import axios from 'axios';

interface Game {
    id: number;
    nombre_partida: string;
    num_jugadores: number;
    estado: string;
    id_creador_partida: number;
    created_at: string;
    jugadores: {
        id: number;
        nick: string;
    }[];
}

const tituloPartida = document.getElementById('nombre-partida');
const idPartidaDisplay = document.getElementById('id-partida');
const contadorJugadores = document.getElementById('contador-jugadores');
const listaJugadores = document.getElementById('lista-jugadores');
const estadoPartida = document.getElementById('estado-partida');
const btnSalir = document.getElementById('btn-salir');
const btnIniciar = document.getElementById('btn-iniciar');

function getGameIdFromUrl(): string | null {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

async function cargarDatosPartida() {
    const gameId = getGameIdFromUrl();
    const token = localStorage.getItem('auth_token');

    if (!gameId) {
        alert("No se especificó ninguna partida.");
        window.location.href = '/HTML/dashboard.html';
        return;
    }

    if (!token) {
        console.error("No hay token. Redirigiendo al login.");
        window.location.href = '/index.html';
        return;
    }

    try {
        const response = await axios.get<Game>(`http://localhost:8000/api/partidas/${gameId}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        const partida = response.data;
        
        actualizarInterfaz(partida);

        checkSiSoyCreador(partida.id_creador_partida);

    } catch (error: any) {
        console.error("Error cargando la partida:", error);
        
        if (error.response && error.response.status === 404) {
            alert("La partida no existe.");
        } else {
            alert("Error al conectar con el servidor.");
        }
        window.location.href = '/HTML/dashboard.html';
    }
}

function actualizarInterfaz(partida: Game) {
    if (tituloPartida) tituloPartida.textContent = partida.nombre_partida;
    if (idPartidaDisplay) idPartidaDisplay.textContent = partida.id.toString();
    if (contadorJugadores) contadorJugadores.textContent = `${partida.jugadores.length}/30`;
    if (estadoPartida) estadoPartida.textContent = partida.estado.toUpperCase();

    if (listaJugadores) {
        listaJugadores.innerHTML = ''; 
        
        partida.jugadores.forEach(jugador => {
            const li = document.createElement('li');
            li.className = 'slot-jugador';
            
            const esLider = jugador.id === partida.id_creador_partida ? ' (Líder)' : '';
            
            li.innerHTML = `<strong>${jugador.nick}</strong>${esLider}`;
            listaJugadores.appendChild(li);
        });

        const huecos = 30 - partida.jugadores.length;
        for (let i = 0; i < huecos; i++) {
             const li = document.createElement('li');
             li.className = 'slot-jugador empty';
             li.textContent = 'Esperando jugador...';
             listaJugadores.appendChild(li);
        }
    }
}

function checkSiSoyCreador(idCreador: number) {
    const userString = sessionStorage.getItem('user');
    if (userString) {
        const user = JSON.parse(userString);
        if (user.id === idCreador && btnIniciar) {
            btnIniciar.style.display = 'inline-block';
        }
    }
}

function controlBotones() {
    if (btnSalir) {
        btnSalir.addEventListener('click', () => {
            window.location.href = '/HTML/dashboard.html';
        });
    }

    if (btnIniciar) {
        btnIniciar.addEventListener('click', () => {
            console.log("¡Iniciando partida!");
            alert("Funcionalidad de iniciar partida no implementada.");
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    cargarDatosPartida();
    controlBotones();
});