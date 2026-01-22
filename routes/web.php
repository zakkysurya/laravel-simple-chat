<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

// 0. Landing Page (Pilih User)
Route::get('/', [ChatController::class, 'index']);

// 1. Halaman Chat (Butuh Login)
Route::get('/chat/{id}', [ChatController::class, 'viewChat']);

// 2. Kirim Pesan
Route::post('/send-message', [ChatController::class, 'sendMessage']);

// 3. Ambil History Private
Route::get('/private-messages/{userId}', [ChatController::class, 'privateMessages']);

// 4. Keep-Alive
Route::get('/keep-alive', [ChatController::class, 'keepAlive']);
