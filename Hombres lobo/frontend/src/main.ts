import './style.css'
import api from './api'
import { 
  validarPass, 
  validarUserName, 
  validarEmail, 
  registrarUsuario, 
  limpiarFormulario } from './validarFormularioRegistro'

// const app = document.querySelector<HTMLDivElement>('#app')!

// app.innerHTML = `
//   <div class="app">
//     <h1>Frontend Vite conectado a Laravel</h1>
//     <button id="cargar-datos" type="button">
//       Probar endpoint /ping
//     </button>
//     <pre id="resultado" class="resultado"></pre>
//   </div>
// `

// const boton = document.querySelector<HTMLButtonElement>('#cargar-datos')!
// const resultado = document.querySelector<HTMLPreElement>('#resultado')!

// boton.addEventListener('click', async () => {
//   resultado.textContent = 'Cargando...'

//   try {
//     const respuesta = await api.get('/ping') // 👈 coincide con Route::get('/ping', ...)

//     resultado.textContent = JSON.stringify(respuesta.data, null, 2)
//   } catch (error) {
//     console.error(error)
//     resultado.textContent = 'Error llamando a la API (mira la consola)'
//   }
// })

document.addEventListener('DOMContentLoaded', () => {
  const formulario = document.getElementById('formulario_registro')
  const validMsg = document.getElementById('valid_msg') as HTMLElement
  const errorMsg = document.getElementById('error_msg') as HTMLElement
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
    const email = (document.getElementById('email') as HTMLInputElement).value
    const nick = (document.getElementById('username_registro') as HTMLInputElement).value
    const password = (document.getElementById('password_registro') as HTMLInputElement).value

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
      email,
      nick,
      password
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
})