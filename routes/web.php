<?php

use Illuminate\Support\Facades\Route;
// use App\Models\User;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\PrivateMessageSent;
use Illuminate\Support\Facades\Auth;

// 1. Halaman Utama (Tampilan Chat)
// Route::get('/', function () {
//     return view('chat');
// });

// // 2. Endpoint untuk Mengirim Pesan
// Route::post('/send-message', function (Request $request) {
//     $request->validate([
//         'username' => 'required|string',
//         'message' => 'required|string',
//     ]);

//     // Broadcast event ke semua orang
//     MessageSent::dispatch($request->username, $request->message);

//     return response()->json(['status' => 'Message Sent!']);
// });



// 1. Halaman Chat (Butuh Login)
Route::get('/chat/{id}', function ($id) {
    // Login paksa user berdasarkan ID di URL (Simulasi Login)
    Auth::loginUsingId($id);
    
    // Ambil history chat PUBLIC saja untuk awal
    $messages = Message::whereNull('receiver_id')->with('sender')->get();
    
    return view('chat', [
        'messages' => $messages,
        'currentUser' => Auth::user()
    ]);
});

// 2. Kirim Pesan
Route::post('/send-message', function (Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info('Send Message Request:', $request->all());

    $msg = Message::create([
        'sender_id' => Auth::id(),
        'receiver_id' => $request->receiver_id, // Null jika public
        'message' => trim($request->message),
    ]);

    // Load relasi sender agar nama muncul di frontend
    $msg->load('sender');

    if ($request->receiver_id) {
        // Broadcast ke penerima
        PrivateMessageSent::dispatch($msg);
        // Jangan lupa broadcast ke diri sendiri (pengirim) agar muncul di UI? 
        // Tidak perlu via socket, kita append manual via JS nanti.
    } else {
        MessageSent::dispatch($msg);
    }

    return $msg;
});

// 3. Ambil History Private (API)
Route::get('/private-messages/{userId}', function ($userId) {
    $myId = Auth::id();
    return Message::where(function($q) use ($userId, $myId) {
        $q->where('sender_id', $myId)->where('receiver_id', $userId);
    })->orWhere(function($q) use ($userId, $myId) {
        $q->where('sender_id', $userId)->where('receiver_id', $myId);
    })->with('sender')->get();
});