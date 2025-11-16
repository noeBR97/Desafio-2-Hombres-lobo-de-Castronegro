import axios from 'axios'
import api from './api'

const formulario = document.getElementById('formulario_registro') as HTMLFormElement
const errorMsg = document.getElementById('error_msg') as HTMLElement
const validMsg = document.getElementById('usuario_registrado') as HTMLElement

export function validarPass () {
    const pass = (document.getElementById('password_registro') as HTMLInputElement).value

    let passReg = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/
    let resultado = passReg.test(pass)
    if (!resultado) {
        errorMsg.textContent = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carcter especial.'
        errorMsg.classList.add('visible')
        return false
    } else {
        errorMsg.textContent = ''
        errorMsg.classList.remove('visible')
        return true
    }
}

export async function validarUserName(): Promise<boolean> {
    const userName = (document.getElementById('username_registro') as HTMLInputElement).value
    try {
        const respuesta = await axios.post('http://localhost:8000/api/validar-username', {
            nick: userName
        })

        if (!respuesta.data.disponible) {
            errorMsg.textContent = 'Ese nombre de usuario ya existe.'
            errorMsg.classList.add('visible')
            return false
        } else {
            errorMsg.textContent = ''
            errorMsg.classList.remove('visible')
            return true
        }
    } catch(e) {
        errorMsg.textContent = 'Error al contactar con el servidor.'
        errorMsg.classList.add('visible')
        return false
    }
}

export async function validarEmail(): Promise<boolean> {
    const email = (document.getElementById('email') as HTMLInputElement).value

    try {
        const respuesta = await axios.post('http://localhost:8000/api/validar-email', {
            email: email
        })

        if (!respuesta.data.disponible) {
            errorMsg.textContent = 'Ese email ya está registrado en el sistema.'
            errorMsg.classList.add('visible')
            return false
        } else {
            errorMsg.textContent = ''
            errorMsg.classList.remove('visible')
            return true
        }
    } catch(e) {
        errorMsg.textContent = 'Error al contactar con el servidor.'
        errorMsg.classList.add('visible')
        return false
    }
}

export function limpiarFormulario() {
    if (formulario) {
        formulario.reset()
    }

    if (errorMsg) {
        errorMsg.textContent = ''
        errorMsg.classList.remove('visible')
    }

    if (validMsg) {
        validMsg.textContent = ''
        validMsg.classList.remove('visible')
    }
}

export async function registrarUsuario(datos:any) {
    try {
        console.log(datos)
        const res = await api.post('/usuarios/registrar', datos)
        console.log(res)
        return res.data
    } catch (error:any) {
        if(error.response) {
            return error.response.data
        }
        console.error('Error insesperado: ', error)
        return {ok: false};
    }
}