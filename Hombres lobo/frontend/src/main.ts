import './style.css'
import api from './api'
import { validarPass, registrarUsuario } from './validarFormularioRegistro'

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

  formulario?.addEventListener('submit', async (e) => {
    e.preventDefault()
    const nombre = (document.getElementById('nombre_registro') as HTMLInputElement).value
    const apellido1 = (document.getElementById('apellido_registro') as HTMLInputElement).value
    const apellido2 = (document.getElementById('apellido2_registro') as HTMLInputElement).value
    const email = (document.getElementById('email') as HTMLInputElement).value
    const nick = (document.getElementById('username_registro') as HTMLInputElement).value
    const password = (document.getElementById('password_registro') as HTMLInputElement).value

    const respuesta = await registrarUsuario({
      nombre,
      apellido1,
      apellido2,
      email,
      nick,
      password
    })

    if (respuesta.ok) {
      console.log('Usuario creado: ', respuesta.usuario)
    } else {
      console.log('Error: ', respuesta)
    }
    //validarPass()
  })
})