<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', function () {
    return response()->json([
        'mensaje' => 'API de Laravel funcionando 🐘',
        'ok'      => true,
    ]);
});

Route::post('/usuarios/registrar', [AuthController::class, 'registrar']);
Route::post('/validar-username', [UsuarioController::class,'validarUserName']);
Route::post('/validar-email', [UsuarioController::class,'validarEmail']);
