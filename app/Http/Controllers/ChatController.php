<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat');
    }

    public function stream(Request $request)
    {
        $userMessage = $request->input('message', 'Hello');

        return response()->stream(function () use ($userMessage) {
            
            // Buffering hatao
            while (ob_get_level()) { ob_end_flush(); }
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
            set_time_limit(0);

            // Ollama ka URL
            $url = 'http://127.0.0.1:11434/api/generate';
            $model = env('OLLAMA_MODEL', 'llama3');

            // cURL request (streaming ke saath)
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => $model,
                'prompt' => $userMessage,
                'stream' => true
            ]));
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) {
                // Har chunk ko SSE format mein bhejo
                echo "data: " . json_encode(['token' => $chunk]) . "\n\n";
                @ob_flush();
                flush();
                return strlen($chunk);
            });

            curl_exec($ch);
            curl_close($ch);

            // Stream complete
            echo "data: " . json_encode(['done' => true]) . "\n\n";
            @ob_flush();
            flush();

            // Database mein save karo
            ChatMessage::create([
                'user_message' => $userMessage,
                'ai_response' => 'Stream saved', 
                'tokens_used' => 0,
            ]);

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}