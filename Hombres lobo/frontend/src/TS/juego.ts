import { AxiosError } from "axios";
import api from "../api";
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { getGameIdFromUrl } from "./lobby";
import { calcularAparienciaJugador, type ContextoJugador } from './roles';


(window as any).Pusher = Pusher;

interface Usuario {
    id: number;
    nick: string;
    rol: string;
    vivo: number;
    es_alcalde: number;
}

interface Juego {
    id: number;
    nombre_partida: string;
    jugadores: Usuario[]; 
}

function obtenerContextoJugador(): ContextoJugador {
    const userJson = sessionStorage.getItem('user');
    let miId: number | null = null;

    if (userJson) {
        const user = JSON.parse(userJson) as { id: number; nick: string };
        miId = user.id;
    }

    const miRol = sessionStorage.getItem('mi_rol');

    return { miId, miRol };
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

let fase: 'dia' | 'noche' = 'noche';
let tiempoRestante = 60; 
let intervalo: any;
let votoActual: number | null = null; 

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
    .listen('.AlcaldeElegido', (e: any) => {
            console.log('Nuevo alcalde elegido:', e.jugador_id);
            cargarJuego();
        })
    .listen('.tiempo.actualizado', (e: any) => {
        const contador = document.getElementById("contador");
        if (contador) {
            contador.textContent = e.tiempoRestante;
        }
    });
    echo.private(`lobby.${gameId}`)
        .listen('.JugadorUnido', (e: any) => {
            console.log("Jugador nuevo en la partida:", e.user);
            agregarJugadorAlTablero(e.user);
        });
}

let enviando = false;

async function enviarMensaje() {
    const contenido = inputMensaje.value.trim();
    if (!contenido || !partidaID || !token) return;
    if (enviando) return;
    enviando = true;
    if (btnEnviarMensaje) btnEnviarMensaje.disabled = true;
    try {
        await api.post('/api/chat/send-private', {
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
    } finally {
        enviando = false;
        if (btnEnviarMensaje) btnEnviarMensaje.disabled = false;
    }
}

btnEnviarMensaje?.addEventListener('click', () => {
    enviarMensaje();
});

inputMensaje?.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        enviarMensaje();
    }
});


async function cargarJuego() {
    if (!partidaID || !token) {
        window.location.href = '/HTML/dashboard.html';
        return;
    }

    try {
        const response = await api.get<Juego>(`/api/partidas/${partidaID}/estado`, {
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

    const contexto = obtenerContextoJugador();

    jugadores.forEach(jugador => {
        crearCartaJugador(jugador, contexto);
    });
}


function agregarJugadorAlTablero(jugador: Usuario) {
    if (!tableroJugadores) return;

    if (tableroJugadores.innerHTML.includes(jugador.nick)) return;
    
    const cartas = Array.from(tableroJugadores.children);
    const primerVacio = cartas.find(div => div.textContent?.includes('Esperando jugador...'));
    const contexto = obtenerContextoJugador();

    if (primerVacio) {
        primerVacio.innerHTML = `<span>${jugador.nick}</span>`;
    } else {
        crearCartaJugador(jugador, contexto);
    }
}

function crearCartaJugador(jugador: Usuario, contexto: ContextoJugador) {
    if (!tableroJugadores) return;

    const div = document.createElement('div');
    div.className = 'jugador';

    div.dataset.id = jugador.id.toString();

    div.addEventListener('click', () => {
        gestionarVoto(jugador, contexto);
    });

    if (votoActual === jugador.id) {
        div.classList.add('votado');
    }

    const span = document.createElement('span');
    span.textContent = jugador.nick;

    const apariencia = calcularAparienciaJugador(jugador, contexto);

    if (apariencia.backgroundImage) {
        div.style.backgroundImage = apariencia.backgroundImage;
    }

    apariencia.clasesExtra.forEach(clase => div.classList.add(clase));

    if (apariencia.colorNombre) {
        span.style.color = apariencia.colorNombre;
    }

    div.appendChild(span);
    tableroJugadores.appendChild(div);
}

async function gestionarVoto(objetivo: Usuario, contexto: ContextoJugador) {
    if (fase !== 'dia') {
        alert("Solo se puede votar durante el día");
        return;
    }

    if (objetivo.id === contexto.miId) {
        alert("No puedes votarte a ti mismo");
        return;
    }

    if (!confirm(`¿Quieres votar a ${objetivo.nick}?`)) return;

    try {
        await api.post('/api/partida/votar', {
            partida_id: partidaID,
            voto_a: objetivo.id 
        }, {
            headers: { 'Authorization': `Bearer ${token}` }
        });

        console.log(`Voto registrado a ${objetivo.nick}`);
        votoActual = objetivo.id;
        actualizarEstilosVotacion(); 

    } catch (error: any) {
        console.error("Error al votar:", error);
        alert("No se pudo registrar el voto.");
    }
}

function actualizarEstilosVotacion() {
    const cartas = document.querySelectorAll('.jugador');
    cartas.forEach((carta) => {
        const el = carta as HTMLElement;
        el.classList.remove('votado'); 
        
        if (el.dataset.id === votoActual?.toString()) {
            el.classList.add('votado');
        }
    });
}

function iniciarContador() {
    const contador = document.getElementById('contador') as HTMLHeadingElement;
    if (!contador) return; 

    contador.textContent = `Fase: ${fase.toUpperCase()} | ${tiempoRestante}s`;

    intervalo = setInterval(() => {
        tiempoRestante--;
        contador.textContent = `Fase: ${fase.toUpperCase()} | ${tiempoRestante}s`;

        if (tiempoRestante <= 0) {
            clearInterval(intervalo);
            cambiarFase();
        }
    }, 1000);
}

function cambiarFase() {
    const body = document.body;

    if (fase === 'dia') {
        fase = 'noche';
        body.style.backgroundImage = "url('../img/NOCHE.png')";
        votoActual = null;
        actualizarEstilosVotacion();
    } else {
        fase = 'dia';
        body.style.backgroundImage = "url('../img/DIA.png')";
    }

    tiempoRestante = 60;
    iniciarContador();
}

document.addEventListener('DOMContentLoaded', () => {
    if (!partidaID || !token) return;
    conectarWebSockets(partidaID, token);
    cargarJuego();
    iniciarContador();
});