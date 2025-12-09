import { AxiosError } from "axios";
import api from "../api";
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { getGameIdFromUrl } from "./lobby";
import { calcularAparienciaJugador, type ContextoJugador } from './roles';


(window as any).Pusher = Pusher;

interface Usuario {
    id: number;
    id_usuario: number | null;
    nick: string;
    rol: string;
    vivo: number;
    es_alcalde: number;
    es_bot: number;
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
let intervalo: any;
let votoActual: number | null = null
let finFaseTimestamp: number = 0; 
const DURACION_FASE = 60;
let estoyVivo: boolean = true; 

if (!partidaID || !token) {
    console.error('ID de partida o token no disponibles');
}

function conectarWebSockets(gameId: string, token: string) {
    console.log(`Iniciando conexión WebSocket para la partida ${gameId}...`);

    const echo = new Echo({
        broadcaster: 'reverb',
        key: 'wapw1chslaoar5p0jt4i', 
        wsHost: window.location.hostname,         
        wsPort: 8085,                
        wssPort: 8085,
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `http://${window.location.hostname}:8000/api/broadcasting/auth`,
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json'
            }
        }
    });

echo.private(`game.${gameId}`)
    .listen('.message.sent', (e: any) => {
    if (!listaMensajes) return;
    const contexto = obtenerContextoJugador();
    const miRolNorm = (contexto.miRol || '').toLowerCase().trim();
    const esNoche = fase === 'noche';
    const soloLobos = e.solo_lobos === true;
    if (esNoche && soloLobos) {
        if (estoyVivo && miRolNorm !== 'lobo' && miRolNorm !== 'nina') {
            return;
        }
    }
    const nuevoMensaje = document.createElement('li');
    nuevoMensaje.className = 'mensaje';

    if (e.mensaje.usuario_id === user.id) {
        nuevoMensaje.classList.add('mensaje-propio');
    } else {
        nuevoMensaje.classList.add('mensaje-ajeno');
    }

    const nombre = document.createElement('div');
    nombre.classList.add('mensaje-nombre');
    nombre.textContent = e.mensaje.usuario_nick;

    const cuerpo = document.createElement('div');
    cuerpo.classList.add('mensaje-texto');
    cuerpo.textContent = e.mensaje.contenido;

    nuevoMensaje.appendChild(nombre);
    nuevoMensaje.appendChild(cuerpo);

    document.getElementById('mensajes')?.appendChild(nuevoMensaje);
    listaMensajes.scrollTop = listaMensajes.scrollHeight;

})
    .listen('.CambioDeFase', (e: any) => {
        console.log("Cambio de fase recibido del servidor:", e.partida.fase_actual);
        
        fase = e.partida.fase_actual; 
        
        iniciarTemporizadorVisual(); 
        actualizarFondoYVotos();
        narradorDecir(`La fase ha cambiado a ${fase.toUpperCase()}`);
        cargarJuego();
    })
    .listen('.AlcaldeElegido', (e: any) => {
        console.log('Nuevo alcalde elegido:', e.jugador_id);
        narradorDecir(`El jugador ${e.jugador_nick} ha sido elegido alcalde.`);
        cargarJuego();
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
    })
    .listen('.PartidaActualizada', (e:any) => {
        renderizarJugadores(e.jugadores)
        if (e.jugador_muerto) {
            narradorDecir(`El jugador ${e.jugador_muerto.nick} ha muerto.`);
        }
    })
    .listen('.NarradorHabla', (e: any) => {
        narradorDecir(e.mensaje);
    });

    echo.private(`lobby.${gameId}`)
        .listen('.JugadorUnido', (e: any) => {
            console.log("Jugador nuevo en la partida:", e.user);
            agregarJugadorAlTablero(e.user);
        });
}

let enviando = false;

async function enviarMensaje() {
    if (!estoyVivo) {
        alert("Estás muerto, no puedes hablar.");
        return;
    }
    const contenido = inputMensaje.value.trim();
    if (!contenido || !partidaID || !token) return;
    if (enviando) return;
    const contexto = obtenerContextoJugador();
    const miRolNorm = (contexto.miRol || '').toLowerCase().trim();
    if (fase === 'noche') {
        if (miRolNorm === 'nina') {
            alert("La niña puede escuchar a los lobos, pero no hablar de noche.");
            return;
        }
        if (miRolNorm !== 'lobo') {
            alert("Solo los lobos pueden hablar por la noche.");
            return;
        }
    }
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

    const miJugador = jugadores.find(j => j.id_usuario === contexto.miId);
    if (miJugador) {
        contexto.miRol = miJugador.rol;
        sessionStorage.setItem('mi_rol', miJugador.rol);
        estoyVivo = miJugador.vivo === 1;
        console.log('DEBUG miRol:', contexto.miRol, 'estoyVivo:', estoyVivo);
    }
    const humanos = jugadores.filter(j => j.es_bot === 0);
    const bots    = jugadores.filter(j => j.es_bot === 1);

    humanos.forEach(jugador => {
        crearCartaJugador(jugador, contexto);
    });

    bots.forEach(bot => {
        crearCartaJugador(bot, contexto, true);
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

function crearCartaJugador(jugador: Usuario, contexto: ContextoJugador, esBot: boolean = false) {
    if (!tableroJugadores) return;

    const div = document.createElement('div');
    div.className = 'jugador';

    div.dataset.id = jugador.id.toString();

    div.addEventListener('click', () => {
        gestionarVoto(jugador, contexto);
    });

    if (votoActual !== null && votoActual === jugador.id) {
        div.classList.add('votado');
    }

    const span = document.createElement('span');
    span.textContent = jugador.nick + (esBot ? ' (BOT)' : '');

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
    if (fase === 'noche' && contexto.miRol !== 'lobo') {
        alert("Solo los lobos pueden votar por la noche");
        return;
    }

    const miRolNorm = (contexto.miRol || '').toLowerCase().trim();
    const rolObjetivoNorm = (objetivo.rol || '').toLowerCase().trim();

    if (fase === 'noche' && miRolNorm === 'lobo' && rolObjetivoNorm === 'lobo') {
        alert("No puedes votar a otro lobo, es que quieres perder?.");
        return;
    }

    if (objetivo.id_usuario === contexto.miId) {
        alert("No puedes votarte a ti mismo");
        return;
    }

    if (!confirm(`¿Quieres votar a ${objetivo.nick}?`)) return;

    try {
        await api.post('/api/partida/votar', {
            partida_id: partidaID,
            voto_a: objetivo.id,
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

function iniciarTemporizadorVisual() {
    const ahora = Date.now();
    finFaseTimestamp = ahora + (DURACION_FASE * 1000);

    if (intervalo) clearInterval(intervalo);

    const contador = document.getElementById('contador');

    intervalo = setInterval(() => {
        const ahoraMismo = Date.now();
        const segundosRestantes = Math.ceil((finFaseTimestamp - ahoraMismo) / 1000);

        if (contador) {
            contador.textContent = `Fase: ${fase.toUpperCase()} | ${segundosRestantes}s`;
        }

        if (segundosRestantes <= 0) {
            clearInterval(intervalo);
            
            intentarCambiarFase();
        }
    }, 1000);
}

async function intentarCambiarFase() {
    try {
        await api.post(`/api/partidas/${partidaID}/siguiente-fase`, {
            fase_actual_cliente: fase 
        }, {
             headers: { 'Authorization': `Bearer ${token}` }
        });
    } catch (error) {
        console.log("Petición de cambio de fase enviada (o ignorada si ya cambió).");
    }
}

function actualizarFondoYVotos() {
    const body = document.body;
    if (fase === 'dia') {
        body.style.backgroundImage = "url('../img/DIA.png')";
    } else {
        body.style.backgroundImage = "url('../img/NOCHE.png')";
        votoActual = null;
        actualizarEstilosVotacion();
    }
}

function narradorDecir(texto: string) {
    if (!listaMensajes) return;

    const li = document.createElement('li');
    li.classList.add('mensaje-narrador');
    li.textContent = `Narrador: ${texto}`;

    listaMensajes.appendChild(li);
    listaMensajes.scrollTop = listaMensajes.scrollHeight;
}


document.addEventListener('DOMContentLoaded', () => {
    if (!partidaID || !token) return;
    conectarWebSockets(partidaID, token);
    cargarJuego();
    iniciarTemporizadorVisual();
    actualizarFondoYVotos();
});