<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PartidaController;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ChatController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', function () {
    return response()->json([
        'mensaje' => 'API de Laravel funcionando',
        'ok'      => true,
    ]);
});

Route::post('/usuarios/registrar', [AuthController::class, 'registrar']);
Route::post('/validar-username', [UsuarioController::class,'validarUserName']);
Route::post('/validar-email', [UsuarioController::class,'validarEmail']);

// Rutas públicas (sin token)
Route::post('/login',    [AuthController::class, 'login']);
Route::get('usuarios/avatares', [UsuarioController::class, 'listaAvatares']);

Broadcast::routes(['middleware' => ['auth:sanctum']]);
// Rutas protegidas (requieren token Bearer)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout',[AuthController::class, 'logout']);
    Route::get('/usuarios', [AdminController::class, 'get_all']);
    Route::get('/usuarios/{user}', [AdminController::class, 'get_one']);
    Route::get('/usuarios-buscar', [AdminController::class, 'buscar']);
    Route::put('/usuarios/{user}', [AdminController::class, 'update']);
    Route::delete('/usuarios/{user}', [AdminController::class, 'delete']);
    Route::get('/partidas', [PartidaController::class, 'index']);
    Route::post('/partidas', [PartidaController::class, 'store']);
    Route::get('/partidas/{id}', [PartidaController::class, 'show']);
    Route::post('/partidas/{id}/unirse', [PartidaController::class, 'unirse']);
    Route::post('/partidas/{id}/salir', [PartidaController::class, 'salir']);
    Route::post('/chat/send-private', [ChatController::class, 'sendPrivate']);
    Route::post('/partidas/{id}/iniciar', [PartidaController::class, 'iniciar']);

    Route::post('/usuarios/actualizar-imagen', [UsuarioController::class, 'actualizarImagenPerfil']);
    Route::post('usuarios/elegir-avatar', [UsuarioController::class, 'elegirAvatar']);
    Route::put('/usuario/update', [UsuarioController::class, 'update']);
});
Route::middleware(['auth:sanctum', 'admin'])->get('/users',
[UsuarioController::class, 'index']);
