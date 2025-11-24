<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('lobby.{id}', function ($user, $id) {
    return true;
});
