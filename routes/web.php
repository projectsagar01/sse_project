<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StreamController;

use App\Http\Controllers\ChatController;

// Chat UI
Route::get('/chat', [ChatController::class, 'index']);

// SSE Streaming Endpoint
Route::post('/chat/stream', [ChatController::class, 'stream']);

Route::get('/', function () {
    return view('welcome');
});

// Route 1: Sirf HTML page dikhane ke liye (Frontend)
Route::get('/stream-test', function () {
    return view('stream');
});

// Route 2: Actual SSE data bhejne ke liye (Backend)
Route::get('/stream', [StreamController::class, 'stream']);