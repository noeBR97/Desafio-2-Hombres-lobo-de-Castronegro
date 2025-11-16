interface User {
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

function cargarDatosUsuario() {
  const usuario = sessionStorage.getItem('user');

  if (!usuario) {
    console.error("No hay usuario en la sesión. Redirigiendo al inicio.");
    window.location.href = '/index.html'; 
    return;
  }

  try {
    const datosUsuario: User = JSON.parse(usuario);
    
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

    if (cerrarSesion) {
        cerrarSesion.addEventListener('click', () => {
            console.log('Cerrando sesión...');
            
            sessionStorage.removeItem('user');
            
            window.location.href = '/index.html'; 
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    cargarDatosUsuario(); 
    controlBotones();   
});