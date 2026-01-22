<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

// 1. Channel Umum (Presence) - Return array user info jika auth valid
Broadcast::channel('chat', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});

// 2. Channel Pribadi - Hanya user pemilik ID yang boleh dengar
Broadcast::channel('chat.private.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});