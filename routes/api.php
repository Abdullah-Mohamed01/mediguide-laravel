<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;

Route::post('/send-message', [MessageController::class, 'send']);
Route::post('/chat/create', [ChatController::class, 'createChat']);
Route::post('/chat/send', [ChatController::class, 'sendMessage']);
Route::get('/chat/{id}/messages', [ChatController::class, 'getMessages']);
Route::get('/test-ai', [ChatController::class, 'testAiConnection']);