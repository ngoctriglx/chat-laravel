<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;

class ChatController extends Controller {
    
    public function sendMessage(Request $request) {
        $validated = $request->validate([
            'message' => 'required|string',
            'sender_id' => 'required|integer',
            'receiver_id' => 'required|integer',
        ]);

        broadcast(new MessageSent(
            $validated['message'],
            $validated['sender_id'],
            $validated['receiver_id']
        ))->toOthers();

        return response()->json(['message' => 'Message sent successfully!']);
    }
}
