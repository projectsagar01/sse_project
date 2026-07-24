<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StreamController extends Controller
{
    public function stream()
    {
        // 1. SSE Response ka wrapper
        return response()->stream(function () {
            
            // 2. Output Buffering Hatao (YEH ZAROORI HAI)
            while (ob_get_level()) { ob_end_flush(); }
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
            set_time_limit(0);

            // 3. Connection keep-alive (SSE Heartbeat)
            echo "retry: 2000\n\n";
            @ob_flush(); flush();

            // 4. Ollama ko Call karo (Streaming Mode ON)
            $response = Http::timeout(60)->post('http://127.0.0.1:11434/api/generate', [
                'model' => env('OLLAMA_MODEL', 'llama3'),
                'prompt' => 'Write a 5-line poem about Laravel.',
                'stream' => true  // 🔥 YAHI MAGIC LINE HAI
            ]);

            // 5. Har chunk ko Browser tak pahunchao
            $body = $response->getBody();
            while (!$body->eof()) {
                $line = $body->read(1024);
                
                // SSE Format: "data: " prefix + double newline
                echo "data: " . $line . "\n\n";
                
                @ob_flush();
                flush(); // Force send
            }

        }, 200, [
            'Content-Type' => 'text/event-stream', // 🔥 Header 1
            'Cache-Control' => 'no-cache',          // 🔥 Header 2
            'X-Accel-Buffering' => 'no'             // 🔥 Header 3 (Nginx fix)
        ]);
    }
}