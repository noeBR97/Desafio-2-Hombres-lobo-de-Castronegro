export type UsuarioSesion = {
  id: number;
  nick: string;
  correo: string;
  rol_corp: string;
};

function obtenerUsuarioSesion(): UsuarioSesion | null {
  const userStr = sessionStorage.getItem('user');
  if (!userStr) return null;

  try {
    return JSON.parse(userStr) as UsuarioSesion;
  } catch {
    return null;
  }
}

export function requerirLogin(): UsuarioSesion {
  const token = sessionStorage.getItem('token');
  const usuario = obtenerUsuarioSesion();

  if (!token || !usuario) {
    alert('Acceso denegado. Debes iniciar sesión.');
    window.location.href = '/index.html';
    throw new Error('No autenticado');
  }

  return usuario;
}
