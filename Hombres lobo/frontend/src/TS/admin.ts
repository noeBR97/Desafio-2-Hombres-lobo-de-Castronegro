import { requerirLogin } from "./authCheck";

const mensajeError = document.getElementById(
  "mensaje-error-api"
) as HTMLSpanElement | null;

function mostrarError(texto: string) {
  if (mensajeError) mensajeError.textContent = texto;
}

function limpiarError() {
  if (mensajeError) mensajeError.textContent = "";
}

document.addEventListener("DOMContentLoaded", () => {
  const usuario = requerirLogin();

  if (usuario.rol_corp !== "admin") {
    mostrarError("No tienes permisos de administrador.");
    window.location.href = "/HTML/dashboard.html";
    throw new Error("No es admin");
  }

  console.log("Admin logueado:", usuario.nick);
});

const URL_API = "http://localhost:8000/api";

function obtenerToken(): string {
  const token = sessionStorage.getItem("auth_token");
  if (!token) {
    mostrarError("Sesion no valida. Vuelve a iniciar sesion.");
    window.location.href = "/index.html";
    throw new Error("Sin token");
  }
  return token;
}

async function peticionAutenticada(
  ruta: string,
  opciones: RequestInit = {}
): Promise<any> {
  const token = obtenerToken();

  //Los 3 puntos son para que cargue los headers predeterminados,
  //y si se le pasan nuevos los superpone
  const respuesta = await fetch(`${URL_API}${ruta}`, {
    ...opciones,
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
      ...(opciones.headers || {}),
    },
  });

  if (respuesta.status === 401) {
    sessionStorage.clear();
    mostrarError("Sesion caducada. Inicia sesion de nuevo.");
    window.location.href = "/index.html";
    throw new Error("401 no autorizado");
  }

  if (!respuesta.ok) {
    const texto = await respuesta.text();
    let cuerpo: any = null;

    try {
      cuerpo = JSON.parse(texto);
    } catch {
      cuerpo = null;
    }

    console.error("Error API", respuesta.status, texto);

    const mensajeBackend =
      (cuerpo && (cuerpo.message || cuerpo.error)) || null;

    const mensaje =
      mensajeBackend || `Error API ${respuesta.status}`;

    throw new Error(mensaje);
  }

  if (respuesta.status === 204) return null;
  return respuesta.json();
}

// ===================================
// FUNCIONES DE API
// ===================================

// GET /api/usuarios
async function cogerTodosLosUsuarios() {
  return peticionAutenticada("/usuarios", { method: "GET" });
}

// GET /api/usuarios-buscar?busqueda=...
async function cogerUnUsuario(idONick: string) {
  const q = encodeURIComponent(idONick);
  return peticionAutenticada(`/usuarios-buscar?busqueda=${q}`, {
    method: "GET",
  });
}

// PUT /api/usuarios/{user}
async function actualizarUsuario(idONick: string, datos: any) {
  const id = encodeURIComponent(idONick);
  return peticionAutenticada(`/usuarios/${id}`, {
    method: "PUT",
    body: JSON.stringify(datos),
  });
}

// DELETE /api/usuarios/{user}
async function borrarUsuario(idONick: string) {
  const id = encodeURIComponent(idONick);
  return peticionAutenticada(`/usuarios/${id}`, {
    method: "DELETE",
  });
}

const botonVerUsuarios = document.getElementById("btn-mostrar-todos")!;
const botonEncontrarUsuario = document.getElementById("btn-buscar-uno")!;
const botonBorrarUsuario = document.getElementById("btn-borrar-uno")!;
const entradaUsuario = document.getElementById(
  "input-usuario"
) as HTMLInputElement;
const salidaResultados = document.getElementById("salida-resultados")!;
const entradaNick = document.getElementById(
  "nickname-usuario"
) as HTMLInputElement;
const entradaCorreo = document.getElementById(
  "email-usuario"
) as HTMLInputElement;
const entradaContrasena = document.getElementById(
  "contraseña-usuario"
) as HTMLInputElement;
const botonActualizar = document.getElementById("actualizar-usuario")!;

let idUsuarioSeleccionado: string | null = null;

// ===================================
// UTILIDADES DE INTERFAZ
// ===================================
function mostrarResultados(datos: any) {
  salidaResultados.textContent = JSON.stringify(datos, null, 2);
}

// ===================================
// MOSTRAR TODOS LOS USUARIOS
// ===================================
botonVerUsuarios.addEventListener("click", async () => {
  try {
    limpiarError();
    salidaResultados.textContent = "Cargando...";
    const datos = await cogerTodosLosUsuarios();
    mostrarResultados(datos);
  } catch (error) {
    const mensaje = (error as Error).message;
    mostrarError(mensaje);
    mostrarResultados({ error: mensaje });
  }
});

// ===================================
// BUSCAR UN USUARIO
// ===================================
botonEncontrarUsuario.addEventListener("click", async () => {
  limpiarError();

  const valor = entradaUsuario.value.trim();
  idUsuarioSeleccionado = valor || null;

  if (!idUsuarioSeleccionado) {
    const mensaje = "Por favor, introduce un ID o Nick.";
    mostrarError(mensaje);
    mostrarResultados({ error: mensaje });
    return;
  }

  try {
    salidaResultados.textContent = "Buscando...";
    const datos = await cogerUnUsuario(idUsuarioSeleccionado);

    mostrarResultados(datos);

    const usuario =
      (datos && (datos.data || datos)) || {};

    entradaNick.value = usuario.nick ?? "";
    entradaCorreo.value = usuario.correo ?? "";
  } catch (error) {
    const mensaje = (error as Error).message;
    mostrarError(mensaje);
    mostrarResultados({ error: mensaje });
    entradaNick.value = "";
    entradaCorreo.value = "";
  }
});

// ===================================
// ACTUALIZAR USUARIO
// ===================================
botonActualizar.addEventListener("click", async (evento) => {
  evento.preventDefault();
  limpiarError();

  const valor = entradaUsuario.value.trim();
  idUsuarioSeleccionado = valor || null;

  if (!idUsuarioSeleccionado) {
    const mensaje = "Introduce un usuario para actualizar.";
    mostrarError(mensaje);
    mostrarResultados({ error: mensaje });
    return;
  }

  const contrasena = entradaContrasena.value.trim();

  const datosParaActualizar: any = {
    nick: entradaNick.value,
    correo: entradaCorreo.value,
  };

  if (contrasena !== "") {
    datosParaActualizar.clave = contrasena;
  }

  try {
    salidaResultados.textContent = "Actualizando...";
    const datos = await actualizarUsuario(
      idUsuarioSeleccionado,
      datosParaActualizar
    );
    limpiarError();
    mostrarResultados(datos);

    entradaNick.value = "";
    entradaCorreo.value = "";
    entradaContrasena.value = "";
    entradaUsuario.value = "";
    idUsuarioSeleccionado = null;
  } catch (error) {
    const mensaje = (error as Error).message;
    mostrarError(mensaje);
    mostrarResultados({ error: mensaje });
  }
});

// ===================================
// BORRAR USUARIO
// ===================================
botonBorrarUsuario.addEventListener("click", async () => {
  limpiarError();

  const valor = entradaUsuario.value.trim();
  idUsuarioSeleccionado = valor || null;

  if (!idUsuarioSeleccionado) {
    const mensaje = "Introduce un ID o Nick para borrar.";
    mostrarError(mensaje);
    mostrarResultados({ error: mensaje });
    return;
  }

  try {
    salidaResultados.textContent = "Borrando...";
    const datos = await borrarUsuario(idUsuarioSeleccionado);
    limpiarError();
    mostrarResultados(datos);

    entradaNick.value = "";
    entradaCorreo.value = "";
    entradaContrasena.value = "";
    entradaUsuario.value = "";
    idUsuarioSeleccionado = null;
  } catch (error) {
    const mensaje = (error as Error).message;
    mostrarError(mensaje);
    mostrarResultados({ error: mensaje });
  }
});