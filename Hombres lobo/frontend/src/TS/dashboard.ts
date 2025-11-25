import axios from 'axios';
import { validarPass } from './validarFormularioRegistro';

interface Usuario {
  nombre: string;
  apellido1: string;
  nick: string;
  partidas_jugadas: number;
  partidas_ganadas: number;
  partidas_perdidas: number;
  avatar_url?: string | null;
  avatar_predefinido?: string | null;
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
const imagenPerfil = document.getElementById('avatar');
const menuImagen = document.getElementById('avatar-menu');
const inputImagen = document.getElementById('input-subir-imagen') as HTMLInputElement;
const botonSubirImagen = document.querySelector('[data-action="subir-imagen"]') as HTMLButtonElement;
const avatar = document.getElementById('avatar') as HTMLImageElement;
const botonElegirAvatar = document.querySelector('[data-action="elegir-avatar"]') as HTMLButtonElement;
const modalAvatares = document.getElementById('modal_avatares');
const listaAvatares = document.getElementById('lista_avatares');
const cerrarAvatares = document.querySelector('.cerrar') as HTMLSpanElement
const botonGuardarCambios = document.getElementById('btn-guardar-cambios');
const botonEditarUsuario = document.querySelector('[data-action="editar-usuario"]') as HTMLButtonElement;
const botonCancelarActualizar = document.getElementById('btn-cancelar-actualizar') as HTMLButtonElement;
const nuevoNickInput = document.getElementById('nuevo-nick') as HTMLInputElement;
const nuevaClaveInput = document.getElementById('nueva-clave') as HTMLInputElement;
const botonSelectRol = document.getElementById('rol-select') as HTMLSelectElement;
const listaEstadisticasRol = document.getElementById('estadisticas-rol-list') as HTMLUListElement;
const divEditarUsuario = document.getElementById('editar-usuario') as HTMLDivElement;

async function cargarDatosUsuario() {
  const token = localStorage.getItem('auth_token');
  if (!token) {
    console.error("No se encontró token, redirigiendo al inicio.");
    window.location.href = '/index.html'; 
    return;
  }

  try {
    const response = await axios.get('http://localhost:8000/api/me', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    const datosUsuario: Usuario = response.data.usuario;

    sessionStorage.setItem('user', JSON.stringify(datosUsuario))
    
    if (nickUsuario) nickUsuario.textContent = datosUsuario.nick; 
    if (partidasJugadasUsuario) partidasJugadasUsuario.textContent = `Partidas jugadas: ${datosUsuario.partidas_jugadas}`;
    if (partidasGanadasUsuario) partidasGanadasUsuario.textContent = `Victorias: ${datosUsuario.partidas_ganadas}`;
    if (partidasPerdidasUsuario) partidasPerdidasUsuario.textContent = `Derrotas: ${datosUsuario.partidas_perdidas}`;
    if (avatar) {
      if (datosUsuario.avatar_url) {
        avatar.src = datosUsuario.avatar_url;
      } else if(datosUsuario.avatar_predefinido) {
        avatar.src = `${window.location.origin}/avatares/${datosUsuario.avatar_predefinido}`;
      }
    }
  } catch (error) {
    console.error("Error al cargar los datos del usuario:", error);
    window.location.href = '/index.html'; 
  }
}

function controlBotones() {
  if (imagenPerfil && menuImagen) {
    imagenPerfil.addEventListener('click', () => {
      if (menuImagen.style.display === 'block') {
        menuImagen.style.display = 'none';
      } else {
        menuImagen.style.display = 'block';
      }
    })
  }

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

function subirImagen() {
  if (!inputImagen || !avatar || !botonSubirImagen) return;

  botonSubirImagen.addEventListener('click', () => {
    inputImagen.click();
  })

  inputImagen.addEventListener('change', async () => {
    if (!inputImagen.files || inputImagen.files.length === 0) {
      alert('Selecciona una imagen.')
      return
    }

    const file = inputImagen.files[0]
    const formData = new FormData()
    formData.append('imagen-perfil', file)

    const token = localStorage.getItem('auth_token')
    if (!token) {
      alert('Error de sesión. Inicia sesión de nuevo.')
      window.location.href = '/index.html'
      return
    }
    try {
      const response = await axios.post('http://localhost:8000/api/usuarios/actualizar-imagen', formData, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'multipart/form-data',
        },
      })

      const nuevaUrl = response.data.url
      avatar.src = nuevaUrl
      menuImagen.style.display = 'none'
      inputImagen.value = ''

      const usuarioStr = sessionStorage.getItem('user')
      if (usuarioStr) {
        const usuario = JSON.parse(usuarioStr)
        usuario.imagen_perfil = nuevaUrl
        sessionStorage.setItem('user', JSON.stringify(usuario))
      }

      console.log('Imagen subida con éxito.')

    } catch (error) {
      console.error('Error al subir la imagen:', error)
      alert('No se pudo subir la imagen.')
    }
  })
}

async function abrirSelectorAvatares() {
  if (!modalAvatares || !listaAvatares) return

  modalAvatares.style.display = 'flex'
  listaAvatares.innerHTML = ''

  try {
    const response = await axios.get('http://localhost:8000/api/usuarios/avatares')
  const avatares: string[] = response.data

  avatares.forEach((avatarNombre: string) => {
    const img = document.createElement('img')
    img.src = `${window.location.origin}/avatares/${avatarNombre}`
    img.dataset.avatar = avatarNombre

    img.addEventListener('click', () => elegirAvatar(avatarNombre))
    listaAvatares.appendChild(img)
  })
  } catch (error) {
    console.error('Error al cargar los avatares:', error)
  }
}

async function elegirAvatar(nombreAvatar: string) {
  const token = localStorage.getItem('auth_token')
  if (!token) return window.location.href = '/index.html'

  try {
    await axios.post('http://localhost:8000/api/usuarios/elegir-avatar', 
    { avatar: nombreAvatar },
    { headers: {'Authorization': `Bearer ${token}`}})

    avatar.src = `${window.location.origin}/avatares/${nombreAvatar}`
    modalAvatares!.style.display = 'none'

    const usuarioStr = sessionStorage.getItem('user')
    if (usuarioStr) {
      const usuario = JSON.parse(usuarioStr)
      usuario.avatar_predefinido = nombreAvatar
      usuario.imagen_perfil = null
      sessionStorage.setItem('user', JSON.stringify(usuario))
    }
  } catch (error) {
    console.error('Error al elegir el avatar:', error)
  }
}

function editarUsuario() {
  if (!botonGuardarCambios) return;

  botonGuardarCambios.addEventListener('click', async () => {
    const nuevoNick = nuevoNickInput.value.trim();
    const nuevaClave = nuevaClaveInput.value.trim();
    if (!nuevoNick && !nuevaClave){
      alert('Por favor, ingresa un nuevo nick o una nueva clave.');
      return;
    } 

    //TODO: Validar la nueva clave. Reestructurar validarPass para meter la nueva clave por parametro

    const token = localStorage.getItem('auth_token');
    if (!token) {
      alert('Error de sesión. Inicia sesión de nuevo.');
      window.location.href = '/index.html';
      return;
    }

    try {
      const data: any = {};
      if (nuevoNick) data.nick = nuevoNick;
      if (nuevaClave) data.clave = nuevaClave; 

      const response = await axios.put('http://localhost:8000/api/usuario/update', data, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      if (response.data.usuario) {
        alert('Datos del usuario actualizados con éxito.');
        const usuarioStr = sessionStorage.getItem('user');
        if (usuarioStr) {
          const usuario = JSON.parse(usuarioStr);
          if (response.data.usuario.nick) usuario.nick = response.data.usuario.nick;
          sessionStorage.setItem('user', JSON.stringify(usuario));
        }
        cargarDatosUsuario();
      }
      nuevoNickInput.value = '';
      nuevaClaveInput.value = '';
      const divEditarUsuario = document.getElementById('editar-usuario') as HTMLDivElement;
      divEditarUsuario.style.display = 'none';
    } catch (error) {
      console.error('Error al actualizar los datos del usuario:', error);
    }
  })
}

function mostrarFormularioEditarUsuario() {
  botonEditarUsuario.addEventListener('click', () => {
    divEditarUsuario.style.display = 'flex';
    menuImagen.style.display = 'none';
  })
  
  botonCancelarActualizar.addEventListener('click', () => {
    divEditarUsuario.style.display = 'none';
    nuevoNickInput.value = '';
    nuevaClaveInput.value = '';
  })
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
    subirImagen()

    mostrarFormularioEditarUsuario();
    editarUsuario();

    botonElegirAvatar?.addEventListener('click', abrirSelectorAvatares)

    cerrarAvatares?.addEventListener('click', () => {
      if(modalAvatares) modalAvatares.style.display = 'none'
    })

    botonSelectRol?.addEventListener('change', () => {
      const rolSeleccionado = botonSelectRol.value;
      if (rolSeleccionado) {
        listaEstadisticasRol.style.display = 'block';
      } else {
        listaEstadisticasRol.innerHTML = '';
      }

    })

    document.querySelectorAll<HTMLSpanElement>(".toggle-clave")
    .forEach((boton) => {
    const idInput = boton.getAttribute("data-input");
    if (!idInput) return;

    const input = document.getElementById(idInput) as HTMLInputElement | null;
    if (!input) return;

    boton.addEventListener("click", () => {
      const visible = input.type === "text";
      input.type = visible ? "password" : "text";
      boton.classList.toggle("activo", !visible);
    });
  });
});
