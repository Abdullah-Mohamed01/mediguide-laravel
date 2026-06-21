<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'chat_id' => 'required|integer',
            'sender'  => 'required|in:user,bot',
            'message' => 'required|string',
        ]);

        $message = Message::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => $message
        ], 201);
    }
}