import { requerirLogin } from "./authCheck";

document.addEventListener("DOMContentLoaded", () => {
  const usuario = requerirLogin();

  // Si no es admin, lo echamos al dashboard
  if (!usuario || usuario.rol_corp !== "admin") {
    alert("No tienes permisos de administrador.");
    window.location.href = "/HTML/dashboard.html";
    return;
  }

  console.log("Admin logueado:", usuario.nick);
});
