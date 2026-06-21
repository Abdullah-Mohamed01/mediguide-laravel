<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function register(Request $request)
{
    return response()->json([
        'message' => 'User registered successfully',
        'data' => $request->all()
    ]);
}
    public function createChat(Request $request)
{
    $chat = Chat::create([
        'user_id' => $request->user_id ?? 1
    ]);

    return response()->json([
        'status' => true,
        'chat' => $chat
    ]);
}

   public function sendMessage(Request $request)
{
    try {

        $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'message' => 'required|string'
        ]);

        $userMessage = Message::create([
            'chat_id' => $request->chat_id,
            'sender' => 'user',
            'message' => $request->message
        ]);

        $botReply = $this->botReply($request->message);

        $botMessage = Message::create([
            'chat_id' => $request->chat_id,
            'sender' => 'bot',
            'message' => $botReply
        ]);

        return response()->json([
            'status' => true,
            'user_message' => $userMessage,
            'bot_message' => $botMessage
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'error' => $e->getMessage()
        ], 500);

    }
}

    public function getMessages($chat_id)
    {
        $messages = Message::where('chat_id', $chat_id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'messages' => $messages
        ]);
    }

private function botReply($message)
{
    try {
        $symptoms = array_map('trim', explode(',', $message));

        $response = Http::timeout(10)->post('https://booted-change-rebuild.ngrok-free.dev/predict', [
            'symptoms' => $symptoms,
        ]);

        if ($response->successful()) {
            $result = $response->json();
            $top = $result['predictions'][0];
            return "Possible diagnosis: {$top['disease']} (Confidence: {$top['confidence']}%). Recommended: {$top['doctor']}";
        }

        return 'Could not analyze symptoms. Please try again.';

    } catch (\Exception $e) {
        return 'AI service is currently unavailable.';
    }
}
}