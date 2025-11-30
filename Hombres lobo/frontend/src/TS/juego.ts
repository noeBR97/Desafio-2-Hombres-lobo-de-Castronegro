import axios, { AxiosError } from "axios";
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { getGameIdFromUrl } from "./lobby";

(window as any).Pusher = Pusher;

//referencias al DOM
const listaMensajes = document.getElementById('mensajes') as HTMLUListElement;
const inputMensaje = document.getElementById('input-mensaje') as HTMLInputElement;
const btnEnviarMensaje = document.getElementById('enviar-mensaje') as HTMLButtonElement;

//parámetros del juego
const partidaID = getGameIdFromUrl();
const token = localStorage.getItem('token');
const user = JSON.parse(localStorage.getItem('user') || '{}');

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

    echo.private(`game.${partidaID}`)
    .listen('.message.sent', (e: any) => {
        if(!listaMensajes) return;

        const nuevoMensaje = document.createElement('li');
        nuevoMensaje.className = 'mensaje-item'

        if(e.message.user_id === user.id) {
            nuevoMensaje.classList.add('mensaje-propio');
        }

        nuevoMensaje.innerHTML = `<strong>${e.message.usuario_nick}:</strong> ${e.message.contenido}`;
        listaMensajes.appendChild(nuevoMensaje);
        listaMensajes.scrollTop = listaMensajes.scrollHeight;
    })
}

btnEnviarMensaje?.addEventListener('click', async () => {
    const contenido = inputMensaje.value.trim();
    if (!contenido || !partidaID || !token) return;
    try {
        await axios.post('http://localhost:8000/api/chat/send-private', {
            partida_id: parseInt(partidaID),
            contenido
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