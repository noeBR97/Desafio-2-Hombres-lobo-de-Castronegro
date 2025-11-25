import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

(window as any).Pusher = Pusher;

interface Usuario {
  nombre: string;
  apellido1: string;
  nick: string;
  partidas_jugadas: number;
  partidas_ganadas: number;
  partidas_perdidas: number;
}

const nickUsuario = document.getElementById('nombre-usuario');
const partidasJugadasUsuario = document.getElementById('usuario-partidas-jugadas');
const partidasGanadasUsuario = document.getElementById('usuario-victorias-partidas');
const partidasPerdidasUsuario = document.getElementById('usuario-derrotas-partidas');
const partidasUsuario = document.getElementById('lista-partidas');
const cerrarSesion = document.getElementById('btn-cerrar-sesion');
const crearPartida = document.querySelector('.btn-crear');
const modalCrearPartida = document.getElementById('modal-crear-partida');
const formularioCrearPartida = document.getElementById('form-crear-partida');
const modalNombrePartida = document.getElementById('nombre-partida-input') as HTMLInputElement;
const modalCancelarPartida = document.getElementById('btn-cancelar-crear');

function conectarDashboardWebSocket() {
    const token = localStorage.getItem('auth_token');
    if (!token) return;

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

    echo.channel('dashboard')
        .listen('.ActualizarListaPartidas', (e: any) => {
            console.log('Nueva partida detectada:', e.partida);
            cargarPartidas(); 
        });
}

function cargarDatosUsuario() {
  const usuario = sessionStorage.getItem('user');

  if (!usuario) {
    console.error("No hay usuario en la sesión. Redirigiendo al inicio.");
    window.location.href = '/index.html'; 
    return;
  }

  try {
    const datosUsuario: Usuario = JSON.parse(usuario);
    
    if (nickUsuario) nickUsuario.textContent = datosUsuario.nick; 
    if (partidasJugadasUsuario) partidasJugadasUsuario.textContent = `Partidas jugadas: ${datosUsuario.partidas_jugadas}`;
    if (partidasGanadasUsuario) partidasGanadasUsuario.textContent = `Victorias: ${datosUsuario.partidas_ganadas}`;
    if (partidasPerdidasUsuario) partidasPerdidasUsuario.textContent = `Derrotas: ${datosUsuario.partidas_perdidas}`;

  } catch (error) {
    console.error("Error al cargar los datos del usuario:", error);
    window.location.href = '/index.html'; 
  }
}

function controlBotones() {

  if (crearPartida) {
        crearPartida.addEventListener('click', () => {
            if (modalCrearPartida) {
                modalCrearPartida.style.display = 'flex'; 
            }
        });
    }

  if (modalCancelarPartida) {
        modalCancelarPartida.addEventListener('click', () => {
            if (modalCrearPartida) {
                modalCrearPartida.style.display = 'none';
            }
        });
    }
  
  if (formularioCrearPartida) {
        formularioCrearPartida.addEventListener('submit', async (e) => {
            e.preventDefault(); 

            const nombrePartida = modalNombrePartida.value;
            if (!nombrePartida || nombrePartida.trim() === '') {
                alert("Por favor, introduce un nombre para la partida.");
                return;
            }

            const token = localStorage.getItem('auth_token');
            if (!token) {
                alert("Error de sesión. Por favor, vuelve a iniciar sesión.");
                window.location.href = '/index.html';
                return;
            }

            try {

                const response = await axios.post('http://localhost:8000/api/partidas', 
                { nombre_partida: nombrePartida }, 
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                console.log('¡Partida creada!', response.data);
                
                if (modalCrearPartida) modalCrearPartida.style.display = 'none';
                modalNombrePartida.value = ''; 
                
                await cargarPartidas(); 

            } catch (error) {
                console.error('Error al crear la partida:', error);
                alert('No se pudo crear la partida.');
            }
        });
    }
    
  if (cerrarSesion) {
    cerrarSesion.addEventListener('click', () => {
      console.log('Cerrando sesión...');
      sessionStorage.removeItem('user');
      localStorage.removeItem('auth_token'); 
      window.location.href = '/index.html'; 
    });
  }

  if (partidasUsuario) {
        partidasUsuario.addEventListener('click', async (e) => { 
            const target = (e.target as HTMLElement);
            
            if (target.classList.contains('btn-unirse')) {
                const gameId = target.getAttribute('data-game-id');
                const token = localStorage.getItem('auth_token'); 

                if (gameId && token) {
                    try {
                        await axios.post(`http://localhost:8000/api/partidas/${gameId}/unirse`, {}, {
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            }
                        });

                        window.location.href = `/HTML/lobby.html?id=${gameId}`;

                    } catch (error) {
                        console.error("Error al unirse a la partida:", error);
                        alert("No se pudo unir a la partida.");
                    }
                }
            }
        });
    }

}

async function cargarPartidas() {
  const token = localStorage.getItem('auth_token');
  if (!token) {
    console.error("No se encontró token, no se pueden cargar partidas.");
    return;
  }

  if (!partidasUsuario) return;

  try {
    const response = await axios.get('http://localhost:8000/api/partidas', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    });
    const partidasDisponibles = response.data;
    partidasUsuario.innerHTML = ''; 
    
    if (partidasDisponibles.length === 0) {
        partidasUsuario.innerHTML = '<li>No hay partidas disponibles. ¡Crea una!</li>';
        return;
    }
    for (const partida of partidasDisponibles) {
        const li = document.createElement('li');
        li.innerHTML = `
            <span>- ${partida.nombre_partida} (${partida.numero_jugadores}/30)</span>
            <button class="btn-unirse" data-game-id="${partida.id}">Unirse</button>
        `;
        partidasUsuario.appendChild(li);
    }
  } catch (error) {
      console.error("Error al cargar las partidas:", error);
      partidasUsuario.innerHTML = '<li>Error al cargar las partidas.</li>';
  }
}

document.addEventListener('DOMContentLoaded', () => {
    cargarDatosUsuario(); 
    controlBotones();
    cargarPartidas();
    conectarDashboardWebSocket();
});