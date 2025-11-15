import './style.css'
import api from './api'

const app = document.querySelector<HTMLDivElement>('#app')!

app.innerHTML = `
  <div class="app">
    <h1>Frontend Vite conectado a Laravel</h1>
    <button id="cargar-datos" type="button">
      Probar endpoint /ping
    </button>
    <pre id="resultado" class="resultado"></pre>
  </div>
`

const boton = document.querySelector<HTMLButtonElement>('#cargar-datos')!
const resultado = document.querySelector<HTMLPreElement>('#resultado')!

boton.addEventListener('click', async () => {
  resultado.textContent = 'Cargando...'

  try {
    const respuesta = await api.get('/ping') // coincide con Route::get('/ping', ...)

    resultado.textContent = JSON.stringify(respuesta.data, null, 2)
  } catch (error) {
    console.error(error)
    resultado.textContent = 'Error llamando a la API (mira la consola)'
  }
})