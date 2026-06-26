<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
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

            $aiResult = $this->botReply($request->message);

            $botMessage = Message::create([
                'chat_id' => $request->chat_id,
                'sender' => 'bot',
                'message' => json_encode($aiResult)
            ]);

            return response()->json([
                'status' => true,
                'user_message' => $userMessage,
                'prediction' => $aiResult,
                'bot_message' => $botMessage
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);

        }
    }

    public function getMessages(int $chat_id)
    {
        $messages = Message::where('chat_id', $chat_id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'messages' => $messages
        ]);
    }

    private function botReply(string $message)
{
    try {

        $symptoms = array_map('trim', explode(',', $message));

        $response = Http::timeout(10)->post(
            'https://booted-change-rebuild.ngrok-free.dev/predict',
            [
                'symptoms' => $symptoms,
            ]
        );

        return [
            "http_status" => $response->status(),
            "body" => $response->body(),
            "json" => $response->json(),
        ];

    } catch (\Exception $e) {

        return [
            "exception" => $e->getMessage()
        ];

    }
}
}