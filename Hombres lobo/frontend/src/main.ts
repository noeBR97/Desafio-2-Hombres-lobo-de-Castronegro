// src/main.ts
import './style.css';

// ===============================
//  REFERENCIAS DEL MODAL
// ===============================
const openBtn = document.querySelector<HTMLButtonElement>('#open-login');
const closeBtn = document.querySelector<HTMLButtonElement>('#close-login');
const modal = document.querySelector<HTMLDivElement>('#login-modal');
const dismissButtons =
  document.querySelectorAll<HTMLElement>('[data-dismiss="modal"]');

// ===============================
//  FORMULARIO DE LOGIN
// ===============================
const formLogin = document.querySelector<HTMLFormElement>('#form-login');
const inputCorreo = document.querySelector<HTMLInputElement>('#correo');
const inputClave = document.querySelector<HTMLInputElement>('#clave');
const errorLogin = document.querySelector<HTMLParagraphElement>('#error-login');

// ===============================
//  ABRIR / CERRAR MODAL
// ===============================

// Abrir modal
openBtn?.addEventListener('click', () => {
  modal?.classList.add('show');
});

// Cerrar con la X
closeBtn?.addEventListener('click', () => {
  modal?.classList.remove('show');
});

// Cerrar haciendo click en el fondo oscuro
modal?.addEventListener('click', (e) => {
  if (e.target === modal) {
    modal.classList.remove('show');
  }
});

// Cerrar con cualquier botón que tenga data-dismiss="modal"
// (por ejemplo el botón Cancelar)
dismissButtons.forEach((btn) => {
  btn.addEventListener('click', () => {
    modal?.classList.remove('show');
  });
});

// ===============================
//  LOGIN
// ===============================
formLogin?.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!inputCorreo || !inputClave || !errorLogin) return;

  // limpiar mensaje de error
  errorLogin.hidden = true;
  errorLogin.textContent = '';

  const correo = inputCorreo.value.trim();
  const clave = inputClave.value;

  try {
    const res = await fetch('http://localhost:8000/api/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({ correo, clave }),
    });

    if (!res.ok) {
      const data = await res.json().catch(() => ({}));
      throw new Error((data as any).message || 'Error al iniciar sesión');
    }

    const data = await res.json();

    // Guardar usuario si quieres proteger otras páginas
    if (data.user) {
      sessionStorage.setItem('user', JSON.stringify(data.user));
    }

    modal?.classList.remove('show');
    formLogin.reset();
    errorLogin.hidden = true;
    errorLogin.textContent = '';

    // Redirección según rol
    if (data.user && data.user.is_admin) {
      window.location.href = 'HTML/admin.html';
    } else {
      window.location.href = 'HTML/dashboard.html';
    }
  } catch (err) {
    console.error(err);
    errorLogin.textContent = 'Correo o contraseña incorrectos';
    errorLogin.hidden = false;
  }
});