<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PartidaController;
use Illuminate\Support\Facades\Broadcast;
use App\Models\JugadorPartida;
use App\Models\VotoPartida;
use Carbon\Carbon;
use App\Http\Controllers\ChatController;

//RUTAS PÚBLICAS

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', function () {
    return response()->json([
        'mensaje' => 'API de Laravel funcionando',
        'ok'      => true,
    ]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/usuarios/registrar', [AuthController::class, 'registrar']);
Route::post('/validar-username', [UsuarioController::class,'validarUserName']);
Route::post('/validar-email', [UsuarioController::class,'validarEmail']);
Route::get('usuarios/avatares', [UsuarioController::class, 'listaAvatares']);

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    //usuario autenticado
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout',[AuthController::class, 'logout']);

    //grupo usuarios
    Route::prefix('usuarios')->group(function() {
        //rutas de admin sobre usuarios
        Route::get('/', [AdminController::class, 'get_all']);
        Route::get('/{user}', [AdminController::class, 'get_one']);
        Route::get('/buscar', [AdminController::class, 'buscar']);
        Route::put('/{user}', [AdminController::class, 'update']);
        Route::delete('/{user}', [AdminController::class, 'delete']);

        //acciones de usuario
        Route::post('/actualizar-imagen', [UsuarioController::class, 'actualizarImagenPerfil']);
        Route::post('/elegir-avatar', [UsuarioController::class, 'elegirAvatar']);
    });

    //actualizar usuario propio
    Route::put('/usuario/update', [UsuarioController::class, 'update']);

    //grupo partidas
    Route::prefix('partidas')->group(function() {
        Route::get('/', [PartidaController::class, 'index']);
        Route::post('/', [PartidaController::class, 'store']);
        Route::get('/{id}', [PartidaController::class, 'show']);
        Route::post('/{id}/unirse', [PartidaController::class, 'unirse']);
        Route::post('/{id}/salir', [PartidaController::class, 'salir']);
        Route::post('/{id}/iniciar', [PartidaController::class, 'iniciar']);
        Route::get('/{id}/estado', [PartidaController::class, 'estado']);
        Route::post('/{id}/siguiente-fase', [PartidaController::class, 'siguienteFase']);
    });

    //votaciones
    Route::post('/partida/votar', [PartidaController::class, 'votar']);

    //chat
    Route::post('/chat/send-private', [ChatController::class, 'sendPrivate']);
});
