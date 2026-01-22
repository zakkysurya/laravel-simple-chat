<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    // use RefreshDatabase; // Don't wipe the DB, I want to keep the user's data if possible, or I'll just create temp users.
    // Actually, RefreshDatabase is safer for tests. But I'll manually create users to be safe for the "live" environment.

    public function test_send_private_message_saves_receiver_id()
    {
        $this->withoutMiddleware();

        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $response = $this->actingAs($sender)->postJson('/send-message', [
            'message' => 'Hello Private',
            'receiver_id' => $receiver->id,
        ]);

        $response->assertStatus(201); // Message::create returns 201 created usually? Or just the object (200/201).
        // The route returns the model, so 201.

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'message' => 'Hello Private',
        ]);
        
        // Clean up
        Message::where('message', 'Hello Private')->delete();
        $sender->delete();
        $receiver->delete();
    }
}
