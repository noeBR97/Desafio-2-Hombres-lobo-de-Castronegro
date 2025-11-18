import axios from 'axios'
import api from '../api'

const formulario = document.getElementById('formulario_registro') as HTMLFormElement
const errorMsg = document.querySelector('.error_msg') as HTMLElement
const validMsg = document.getElementById('usuario_registrado') as HTMLElement

export function validarPass () {
    const pass = (document.getElementById('password_registro') as HTMLInputElement).value
    const errorMsgPass = document.getElementById('error_msg_pass') as HTMLElement

    let passReg = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/
    let resultado = passReg.test(pass)
    if (!resultado) {
        errorMsgPass.textContent = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carcter especial.'
        errorMsgPass.classList.add('visible')
        return false
    } else {
        errorMsgPass.textContent = ''
        errorMsgPass.classList.remove('visible')
        return true
    }
}

export async function validarUserName(): Promise<boolean> {
    const userName = (document.getElementById('username_registro') as HTMLInputElement).value
    const errorMsgUserName = document.getElementById('error_msg_username') as HTMLElement
    try {
        const respuesta = await api.post('http://localhost:8000/api/validar-username', {
            nick: userName
        })

        if (!respuesta.data.disponible) {
            errorMsgUserName.textContent = 'Ese nombre de usuario ya existe.'
            errorMsgUserName.classList.add('visible')
            return false
        } else {
            errorMsgUserName.textContent = ''
            errorMsgUserName.classList.remove('visible')
            return true
        }
    } catch(e) {
        errorMsgUserName.textContent = 'Error al contactar con el servidor.'
        errorMsgUserName.classList.add('visible')
        return false
    }
}

export async function validarEmail(): Promise<boolean> {
    const correo = (document.getElementById('correo') as HTMLInputElement).value
    const errorMsgEmail = document.getElementById('error_msg_email') as HTMLElement

    try {
        const respuesta = await axios.post('http://localhost:8000/api/validar-email', {
            correo: correo
        })

        if (!respuesta.data.disponible) {
            errorMsgEmail.textContent = 'Ese email ya está registrado en el sistema.'
            errorMsgEmail.classList.add('visible')
            return false
        } else {
            errorMsgEmail.textContent = ''
            errorMsgEmail.classList.remove('visible')
            return true
        }
    } catch(e) {
        errorMsgEmail.textContent = 'Error al contactar con el servidor.'
        errorMsgEmail.classList.add('visible')
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
        const res = await api.post('http://localhost:8000/api/usuarios/registrar', datos)
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