<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\PrivateMessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    // 0. Halaman Awal (Pilih User)
    public function index()
    {
        $users = User::all();
        return view('welcome', compact('users'));
    }

    // 1. Halaman Chat
    public function viewChat($id)
    {
        // Login paksa (Simulasi untuk demo)
        Auth::loginUsingId($id);
        
        // Ambil history chat PUBLIC
        $messages = Message::whereNull('receiver_id')->with('sender')->get();
        
        return view('chat', [
            'messages' => $messages,
            'currentUser' => Auth::user()
        ]);
    }

    // 2. Kirim Pesan
    public function sendMessage(Request $request)
    {
        Log::info('Send Message Request:', $request->all());

        // Pastikan receiver_id benar-benar null jika dikirim sebagai string kosong/null dari FormData
        $receiverId = $request->receiver_id;
        if ($receiverId === 'null' || $receiverId === '') {
            $receiverId = null;
        }

        $text = trim($request->message);

        $msg = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $receiverId,
            'message' => $text,
        ]);

        // Load relasi sender
        $msg->load('sender');

        if ($request->receiver_id) {
            // Private Chat: Broadcast ke channel private spesifik
            PrivateMessageSent::dispatch($msg);
        } else {
            // Public Chat: Broadcast ke channel 'chat'
            MessageSent::dispatch($msg);
        }

        return $msg;
    }

    // 3. Ambil History Private (API)
    public function privateMessages($userId)
    {
        $myId = Auth::id();
        
        // Logic: Ambil pesan di mana (sender=Saya AND receiver=Dia) OR (sender=Dia AND receiver=Saya)
        return Message::where(function($q) use ($userId, $myId) {
            $q->where('sender_id', $myId)->where('receiver_id', $userId);
        })->orWhere(function($q) use ($userId, $myId) {
            $q->where('sender_id', $userId)->where('receiver_id', $myId);
        })->with('sender')->get();
    }

    // 4. Keep-Alive (Refresh Session/CSRF)
    public function keepAlive()
    {
        return response()->json(['csrf_token' => csrf_token()]);
    }
}