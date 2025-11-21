import axios from 'axios';

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
});
