<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\TranslationService;

class ChatController extends Controller
{
    protected $translator;

    public function __construct(TranslationService $translator)
    {
        $this->translator = $translator;
    }

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
    $request->validate([
        'chat_id' => 'required|exists:chats,id',
        'message' => 'required|string'
]);

    try {

           $userMessage = Message::create([
               'chat_id' => $request->chat_id,
               'sender' => 'user',
               'message' => $request->message
        ]);

        dd("قبل botReply");

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
    dd($e->getMessage(), $e->getTraceAsString());
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

            dd($symptoms);

            $response = Http::timeout(10)->post('https://wistful-scrunch-candied.ngrok-free.dev/predict', [
    'symptoms' => $symptoms,
        ]
        );

            if ($response->successful()) {

                dd($response->json());

                $result = $response->json();

                $result = $this->translator->translateResponse($result);

                return $result;
            }

            return [
                'status' => false,
                'message' => 'Could not analyze symptoms. Please try again.'
            ];

        } catch (\Exception $e) {
    dd($e->getMessage());
}
    }
}