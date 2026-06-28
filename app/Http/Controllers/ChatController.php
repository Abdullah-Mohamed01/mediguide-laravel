<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class ChatController extends Controller
{
    private TranslationService $translator;

    private string $aiUrl = 'https://booted-change-rebuild.ngrok-free.dev/predict';

    public function __construct(TranslationService $translator)
    {
        $this->translator = $translator;
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
       $request->validate([
           'chat_id' => 'required|exists:chats,id',
           'message' => 'required|string',
           'lang' => 'nullable|in:ar,en',
        ]);

        try {

            $userMessage = Message::create([
                'chat_id' => $request->chat_id,
                'sender' => 'user',
                'message' => $request->message
            ]);

           $aiResult = $this->botReply(
               $request->message,
               $request->lang ?? 'en'
            );
            $botMessage = Message::create([
                'chat_id' => $request->chat_id,
                'sender' => 'bot',
                'message' => json_encode($aiResult, JSON_UNESCAPED_UNICODE)
            ]);

            return response()->json([
                'status' => true,
                'user_message' => $userMessage,
                'prediction' => $aiResult,
                'bot_message' => $botMessage
            ]);

        } catch (\Throwable $e) {

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

    public function testAiConnection()
    {
        try {

            $response = Http::timeout(20)
                ->withHeaders([
                    'ngrok-skip-browser-warning' => 'true'
                ])
                ->post($this->aiUrl, [
                    'symptoms' => [
                        'headache',
                        'fever'
                    ]
                ]);

            return response()->json([
                'status' => $response->status(),
                'success' => $response->successful(),
                'body' => $response->json()
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);

        }
    }

    private function botReply(string $message, string $lang = 'en'): array
    {
        $symptoms = array_map('trim', explode(',', $message));

        $response = Http::timeout(20)
            ->withHeaders([
                'ngrok-skip-browser-warning' => 'true'
            ])
            ->post($this->aiUrl, [
                'symptoms' => $symptoms
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'AI Error: ' .
                $response->status() .
                ' - ' .
                $response->body()
            );
        }

        
        $result = $response->json();

        if ($lang === 'ar') {
            $result = $this->translator->translateResponse($result);
        }

        return $result;
    }
}