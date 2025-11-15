import api from './api'

const formulario = document.getElementById('formulario_registro')

export function validarPass () {
    const pass = (document.getElementById('password_registro') as HTMLInputElement).value
    const errorMsg = document.getElementById('password_error') as HTMLElement
    const validMsg = document.getElementById('usuario_registrado') as HTMLElement
    
    let passReg = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/
    let resultado = passReg.test(pass)
    if (!resultado) {
        errorMsg.textContent = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carcter especial.'
        errorMsg.classList.add('visible')
    } else {
        errorMsg.textContent = ''
        errorMsg.classList.remove('visible')
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