# 🐺 Hombres Lobo de Castronegro – Aplicación Web Multijugador

Aplicación web inspirada en el clásico juego **Hombres Lobo de Castronegro**, diseñada para jugar en línea sin narrador humano.
El sistema gestiona automáticamente las fases, roles, votaciones, chat en tiempo real, bots y condiciones de victoria.
Todo sincronizado para ofrecer una experiencia fiel al juego original, pero optimizada para entorno digital ⚡

---

## 🌐 Características principales

### 🔐 Registro, autenticación y perfil

El sistema permite:

- Crear cuentas de usuario.
- Iniciar sesión mediante **Laravel Sanctum**.
- Consultar y editar avatar, nickname y estadísticas.
- Gestionar roles corporativos: **usuario** o **administrador**.

---

## 🎮 Gestión de partidas

Los jugadores pueden:

- Crear partidas configurando el número de jugadores.
- Unirse a partidas existentes en estado *en espera*.
- Salir antes de que la partida comience.
- Ver el estado del lobby en tiempo real.

Cada partida incluye:

- Entre **15 y 30 jugadores**.
- Asignación automática de roles.
- Presencia garantizada de bots (mínimo un lobo y un aldeano bot).
- Sincronización total en tiempo real para todos los jugadores ⚙️

---

## 🧩 Roles del juego

Roles disponibles:

Lobo, Aldeano y Niña.

Características:

- Se asignan de forma aleatoria.
- Son privados para cada jugador.
- Algunos actúan solo por la noche.

---

## 🌙☀️ Motor de fases

El backend gestiona de manera automática:

### Noche 🌙

- Actúan roles especiales.
- Comunicación restringida.
- Chat privado para lobos.

### Día ☀️

- Se revelan víctimas.
- Se habilita el chat global.
- Se realizan votaciones públicas.

El ciclo se repite hasta que se cumplan las condiciones de victoria.

---

## 💬 Chat en tiempo real

Implementado con:

- **Laravel Reverb**
- **WebSockets (protocolo Pusher)**
- **Laravel Echo en frontend**

Incluye:

- Chat global (solo cuando está permitido).
- Mensajería privada entre lobos u otros roles según mecánicas.

---

## 🏆 Condiciones de victoria

Evaluación automática:

- Ganan los **lobos** si igualan o superan en número a los aldeanos.
- Ganan los **aldeanos** si eliminan a todos los lobos.

Las estadísticas se actualizan al finalizar cada partida.

---

## 🤖 Sistema de bots

Los bots actúan como jugadores automáticos:

- Para completar los 15 jugadores mínimos.
- Para realizar acciones nocturnas.
- Para votar durante el día.

---

## 🔒 Seguridad integrada

- Autenticación con Sanctum
- Middleware para rutas de administrador
- Validación de todas las peticiones
- Roles de partida ocultos para privacidad
- Tokens seguros en backend y frontend

---

# 🏗️ Arquitectura del proyecto

## Backend

- Laravel 12
- Sanctum
- Laravel Reverb
- MySQL 8
- Controladores, factories, seeders, eventos WebSocket

## Frontend

- Vite
- TypeScript
- Laravel Echo
- Representación visual del tablero y lobby

## Infraestructura Docker

Servicios disponibles:

- **backend** (Laravel)
- **frontend** (Vite)
- **reverb** (WebSockets)
- **nginx** (reverse proxy)
- **db** (MySQL)
- **adminer** (cliente SQL)

---

# 📝 Seeder con guardado de accesos

Cada vez que se ejecuta:
