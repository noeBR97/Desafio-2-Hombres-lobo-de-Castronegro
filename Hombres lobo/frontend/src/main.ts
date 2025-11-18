import './style.css'
import { 
  validarPass, 
  validarUserName, 
  validarEmail, 
  registrarUsuario, 
  limpiarFormulario } from './TS/validarFormularioRegistro'

document.addEventListener('DOMContentLoaded', () => {
  const formulario = document.getElementById('formulario_registro')
  const validMsg = document.getElementById('valid_msg') as HTMLElement
  const errorMsg = document.querySelector('.error_msg') as HTMLElement
  const botonRegistro = document.getElementById('registro') as HTMLButtonElement
  const modal = document.querySelector('.modal') as HTMLDivElement
  const overlay =document.querySelector('.modal_overlay') as HTMLDivElement
  const cerrar = document.querySelector('.cerrar') as HTMLSpanElement

  botonRegistro.addEventListener('click', () => {
    modal.style.display = 'block'
    overlay.style.display = 'block'
  })

  cerrar.addEventListener('click', () => {
    modal.style.display = 'none'
    overlay.style.display = 'none'
  })

  overlay.addEventListener('click', () => {
    modal.style.display = 'none'
    overlay.style.display = 'none'
  })

  formulario?.addEventListener('submit', async (e) => {
    e.preventDefault()
    const nombre = (document.getElementById('nombre_registro') as HTMLInputElement).value
    const apellido1 = (document.getElementById('apellido_registro') as HTMLInputElement).value
    const apellido2 = (document.getElementById('apellido2_registro') as HTMLInputElement).value
    const correo = (document.getElementById('email') as HTMLInputElement).value
    const nick = (document.getElementById('username_registro') as HTMLInputElement).value
    const clave = (document.getElementById('password_registro') as HTMLInputElement).value

    const passOK = validarPass()
    const userOK = validarUserName()
    const emailOK = validarEmail()

    if (!passOK || !userOK || !emailOK) {
      return
    }

    const respuesta = await registrarUsuario({
      nombre,
      apellido1,
      apellido2,
      correo,
      nick,
      clave
    })

    if (respuesta.usuario) {
      console.log('Usuario creado: ', respuesta.usuario)
      validMsg.textContent = 'Te has registrado correctamente!'
      validMsg.classList.add('visible')

      limpiarFormulario()
    } else {
      console.log('Error: ', respuesta)
      errorMsg.textContent = 'Error al registrar el usuario.'
      errorMsg.classList.add('visible')
    }
  })
    const botonLogin = document.getElementById('open-login') as HTMLButtonElement | null
    const modalLogin = document.getElementById('login-modal') as HTMLDivElement | null
    const cerrarLogin = document.getElementById('close-login') as HTMLButtonElement | null

    const formLogin = document.getElementById('form-login') as HTMLFormElement | null
    const inputCorreo = document.getElementById('correo') as HTMLInputElement | null
    const inputClave = document.getElementById('clave') as HTMLInputElement | null
    const errorLogin = document.getElementById('error-login') as HTMLParagraphElement | null    

     // Abrir modal de LOGIN
  botonLogin?.addEventListener('click', () => {
    if (!modalLogin) return
    modalLogin.style.display = 'block'
    overlay.style.display = 'block'        // usamos el MISMO overlay del registro
  })

  // Cerrar con la X
  cerrarLogin?.addEventListener('click', () => {
    if (!modalLogin) return
    modalLogin.style.display = 'none'
    overlay.style.display = 'none'
  })

  // Cerrar con el botón "Cancelar" (lleva data-dismiss="modal")
  const botonesCancelar = document.querySelectorAll<HTMLElement>('[data-dismiss="modal"]')
  botonesCancelar.forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!modalLogin) return
      modalLogin.style.display = 'none'
      overlay.style.display = 'none'
    })
  })

  // IMPORTANTE: que el overlay cierre también el login si está abierto
  overlay.addEventListener('click', () => {
    modal.style.display = 'none'
    if (modalLogin) {
      modalLogin.style.display = 'none'
    }
    overlay.style.display = 'none'
  })

  // ===============================
  //  SUBMIT LOGIN (llamada a la API)
  // ===============================
  formLogin?.addEventListener('submit', async (e) => {
    e.preventDefault()
    if (!inputCorreo || !inputClave || !errorLogin) return

    // limpiar mensaje de error
    errorLogin.hidden = true
    errorLogin.textContent = ''

    const correo = inputCorreo.value.trim()
    const clave = inputClave.value

    try {
      const res = await fetch('http://localhost:8000/api/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json'
        },
        body: JSON.stringify({ correo, clave })
      })

      if (!res.ok) {
        const data = await res.json().catch(() => ({}))
        throw new Error((data as any).message || 'Error al iniciar sesión')
      }

      const data = await res.json()

      // Guardar usuario si quieres proteger otras páginas
      if (data.user) {
        sessionStorage.setItem('user', JSON.stringify(data.user))
      }

      if (data.token) {
        localStorage.setItem('auth_token', data.token);
      }

      // Cerrar modal de login
      if (modalLogin) {
        modalLogin.style.display = 'none'
      }
      overlay.style.display = 'none'
      formLogin.reset()
      errorLogin.hidden = true
      errorLogin.textContent = ''

      // Redirección según rol
      if (data.user && data.user.is_admin) {
        window.location.href = '/HTML/admin.html'
      } else {
        window.location.href = '/HTML/dashboard.html'
      }
    } catch (err) {
      console.error(err)
      errorLogin.textContent = 'Correo o contraseña incorrectos'
      errorLogin.hidden = false
    }
  })
  })
