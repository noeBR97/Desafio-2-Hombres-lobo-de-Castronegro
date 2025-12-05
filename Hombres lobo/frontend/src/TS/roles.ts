//El type lo he sacado del GPT, ya que queria alguna forma visual
//de automatizar el aspecto de los roles, de cara a que a futuro
//se implementen mas, el resto es tal cual lo teniamos en juego.ts

export type AparienciaJugador = {
    backgroundImage?: string;
    clasesExtra: string[];
    colorNombre?: string;
};

export type RolJugador = 'lobo' | 'aldeano' | 'nina' | string;

export type ContextoJugador = {
    miId: number | null;
    miRol: RolJugador | null;
};

type JugadorDatos = {
    id: number;
    nick: string;
    rol: RolJugador;
    vivo: number;
    es_alcalde: number;
};

export function calcularAparienciaJugador(
    jugador: JugadorDatos,
    contexto: ContextoJugador
): AparienciaJugador {
    const { miId, miRol } = contexto;

    const esPropio = miId !== null && jugador.id === miId;
    const estaVivo = jugador.vivo === 1;
    const soyLobo = miRol === 'lobo';
    const esAlcalde = jugador.es_alcalde === 1;

    let backgroundImage: string | undefined;
    const clasesExtra: string[] = [];
    let colorNombre: string | undefined;

    if (esPropio) {
        const rol = miRol ?? jugador.rol;

        if (rol === 'lobo') {
            backgroundImage = "url('../img/CARTA-LOBO.png')";
        } else if (rol === 'aldeano') {
            backgroundImage = "url('../img/CARTA-ALDEANO.png')";
        } else if (rol === 'nina') {
            backgroundImage = "url('../img/CARTA-NINA.png')";
        }

    } else if (!estaVivo) {
        if (jugador.rol === 'lobo') {
            backgroundImage = "url('../img/CARTA-LOBO.png')";
        } else if (jugador.rol === 'aldeano') {
            backgroundImage = "url('../img/CARTA-ALDEANO.png')";
        } else if (jugador.rol === 'nina') {
            backgroundImage = "url('../img/CARTA-NINA.png')";
        }

    } else if (soyLobo && jugador.rol === 'lobo') {
        backgroundImage = "url('../img/CARTA-LOBO.png')";
    }

    if (!estaVivo) {
        clasesExtra.push('muerto');
        colorNombre = '#7f8c8d';
    }

    if (esAlcalde) {
        clasesExtra.push('alcalde');
    }

    return {
        backgroundImage,
        clasesExtra,
        colorNombre,
    };
}
