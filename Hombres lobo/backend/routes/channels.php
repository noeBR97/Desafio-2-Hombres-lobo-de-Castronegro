<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Partida;

Broadcast::channel('lobby.{id}', function ($user, $id) {
    return true;
});

Broadcast::channel('dashboard', function ($user) {
    return true;
});

Broadcast::channel('game.{partidaID}', function ($user, $partidaID) {
    if (!$user) {
        return false;
    }

    $partida = Partida::find($partidaID);
    if (!$partida) {
        return false;
    }

    return $partida->jugadores()->where('id_usuario', $user->id)->exists();
});
