import axios, { AxiosError } from "axios";
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { getGameIdFromUrl } from "./lobby";

(window as any).Pusher = Pusher;

interface Usuario {
    id: number;
    nick: string;
}

interface Juego {
    id: number;
    nombre_partida: string;
    jugadores: Usuario[]; 
}

//referencias al DOM
const listaMensajes = document.getElementById('mensajes') as HTMLUListElement;
const inputMensaje = document.getElementById('input-mensaje') as HTMLInputElement;
const btnEnviarMensaje = document.getElementById('enviar-mensaje') as HTMLButtonElement;
const tableroJugadores = document.getElementById('juego-jugadores-chat') as HTMLDivElement;
const tituloJuego = document.getElementById('titulo-juego');

//parámetros del juego
const partidaID = getGameIdFromUrl();
const token = sessionStorage.getItem('auth_token');
const user = JSON.parse(sessionStorage.getItem('user') || '{}');

if (!partidaID || !token) {
    console.error('ID de partida o token no disponibles');
}

function conectarWebSockets(gameId: string, token: string) {
    console.log(`Iniciando conexión WebSocket para la partida ${gameId}...`);

    const echo = new Echo({
        broadcaster: 'reverb',
        key: 'wapw1chslaoar5p0jt4i', 
        wsHost: 'localhost',         
        wsPort: 8085,                
        wssPort: 8085,
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: 'http://localhost:8000/api/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json'
            }
        }
    });

    echo.private(`game.${gameId}`)
    .listen('.message.sent', (e: any) => {
        if(!listaMensajes) return;

        const nuevoMensaje = document.createElement('li');
        nuevoMensaje.className = 'mensaje'

        if(e.mensaje.usuario_id === user.id) {
            nuevoMensaje.classList.add('mensaje-propio');
        } else {
            nuevoMensaje.classList.add('mensaje-ajeno')
        }

        const nombre = document.createElement('div')
        nombre.classList.add('mensaje-nombre')
        nombre.textContent = e.mensaje.usuario_nick

        const cuerpo = document.createElement('div')
        cuerpo.classList.add('mensaje-texto')
        cuerpo.textContent = e.mensaje.contenido

        nuevoMensaje.appendChild(nombre)
        nuevoMensaje.appendChild(cuerpo)
        
        document.getElementById('mensajes')?.appendChild(nuevoMensaje)
        listaMensajes.scrollTop = listaMensajes.scrollHeight
    })
    echo.private(`lobby.${gameId}`)
        .listen('.JugadorUnido', (e: any) => {
            console.log("Jugador nuevo en la partida:", e.user);
            agregarJugadorAlTablero(e.user);
        });
}

btnEnviarMensaje?.addEventListener('click', async () => {
    const contenido = inputMensaje.value.trim();
    if (!contenido || !partidaID || !token) return;
    try {
        await axios.post('http://localhost:8000/api/chat/send-private', {
            contenido,
            partida_id: parseInt(partidaID),
        }, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        inputMensaje.value = '';
    } catch (error) {
        const axiosError = error as AxiosError;
        console.error('Error enviando mensaje:', axiosError.message);
    }
})

async function cargarJuego() {
    if (!partidaID || !token) {
        window.location.href = '/HTML/dashboard.html';
        return;
    }

    try {
        const response = await axios.get<Juego>(`http://localhost:8000/api/partidas/${partidaID}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        const partida = response.data;
        
        if (tituloJuego) tituloJuego.textContent = partida.nombre_partida;

        renderizarJugadores(partida.jugadores);

    } catch (error) {
        console.error("Error cargando juego:", error);
    }
}

function renderizarJugadores(jugadores: Usuario[]) {
    if (!tableroJugadores) return;
    tableroJugadores.innerHTML = '';

    jugadores.forEach(jugador => {
        crearCartaJugador(jugador);
    });

}

function agregarJugadorAlTablero(jugador: Usuario) {
    if (!tableroJugadores) return;

    if (tableroJugadores.innerHTML.includes(jugador.nick)) return;
    
    const cartas = Array.from(tableroJugadores.children);
    const primerVacio = cartas.find(div => div.textContent?.includes('Esperando jugador...'));

    if (primerVacio) {
        primerVacio.innerHTML = `<span>${jugador.nick}</span>`;
    } else {
        crearCartaJugador(jugador);
    }
}

function crearCartaJugador(jugador: Usuario) {
    if (!tableroJugadores) return;
    const div = document.createElement('div');
    div.className = 'jugador';
    div.innerHTML = `<span>${jugador.nick}</span>`;
    tableroJugadores.appendChild(div);
}

document.addEventListener('DOMContentLoaded', () => {
    if (!partidaID || !token) return;
    conectarWebSockets(partidaID, token);
    cargarJuego();
});