import { requerirLogin } from "./authCheck";

document.addEventListener("DOMContentLoaded", () => {
  const usuario = requerirLogin();

  if (usuario.rol_corp !== "admin") {
    alert("No tienes permisos de administrador.");
    window.location.href = "/HTML/dashboard.html";
    throw new Error("No es admin");
  }

  console.log("Admin logueado:", usuario.nick);
});

const API_BASE = "http://localhost:8000/api";

function getToken(): string {
  const token = sessionStorage.getItem("token");
  if (!token) {
    alert("Sesión no válida. Vuelve a iniciar sesión.");
    window.location.href = "/index.html";
    throw new Error("Sin token");
  }
  return token;
}

async function fetchAutenticado(
  path: string,
  options: RequestInit = {}
): Promise<any> {
  const token = getToken();

  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
      ...(options.headers || {}),
    },
  });

  if (res.status === 401) {
    sessionStorage.clear();
    alert("Sesión caducada. Inicia sesión de nuevo.");
    window.location.href = "/index.html";
    throw new Error("401 no autorizado");
  }

  if (!res.ok) {
    const texto = await res.text();
    console.error("Error API", res.status, texto);
    throw new Error(`Error API ${res.status}`);
  }

  if (res.status === 204) return null;
  return res.json();
}
