<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsersController;

// Rutas públicas (sin token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Rutas protegidas (requieren token Bearer)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout',[AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'admin'])->get('/users', 
[UsersController::class, 'index']);

 

