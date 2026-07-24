<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Streaming Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #output {
            min-height: 300px;
            max-height: 500px;
            overflow-y: auto;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
        }
        #output .user {
            color: #4fc3f7;
            font-weight: bold;
        }
        #output .ai {
            color: #81c784;
        }
        #output .cursor {
            display: inline-block;
            width: 2px;
            height: 1em;
            background: #81c784;
            animation: blink 0.7s infinite;
            margin-left: 2px;
        }
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        .typing-indicator {
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="mb-4">🤖 AI Streaming Chat</h2>

                <!-- Chat Output -->
                <div id="output">
                    <div class="typing-indicator">💬 Start typing below...</div>
                </div>

                <!-- Form -->
                <form id="chatForm" class="mt-3">
                    @csrf
                    <div class="input-group">
                        <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." required>
                        <button type="submit" class="btn btn-primary" id="sendBtn">Send</button>
                    </div>
                </form>

                <div id="status" class="mt-2 text-muted small"></div>
            </div>
        </div>
    </div>

  <script>
        const output = document.getElementById('output');
        const form = document.getElementById('chatForm');
        const input = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const status = document.getElementById('status');

        let currentMessage = '';
        let isStreaming = false;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const message = input.value.trim();
            if (!message || isStreaming) return;

            // Reset UI
            output.innerHTML = '';
            currentMessage = '';
            isStreaming = true;
            sendBtn.disabled = true;
            input.disabled = true;
            status.textContent = '⏳ AI is thinking...';

            // User message
            const userDiv = document.createElement('div');
            userDiv.className = 'user';
            userDiv.textContent = '👤 ' + message;
            output.appendChild(userDiv);

            // AI container
            const aiDiv = document.createElement('div');
            aiDiv.className = 'ai';
            aiDiv.textContent = '🤖 ';
            output.appendChild(aiDiv);

            // Cursor
            const cursor = document.createElement('span');
            cursor.className = 'cursor';
            aiDiv.appendChild(cursor);

            try {
                const response = await fetch('/chat/stream', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({ message: message })
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            const jsonStr = line.slice(6);
                            try {
                                const data = JSON.parse(jsonStr);
                                
                                // Extract response text
                                let responseText = '';
                                if (data.response !== undefined) {
                                    responseText = data.response;
                                } else if (data.token !== undefined) {
                                    responseText = data.token;
                                }

                                if (responseText) {
                                    const textNode = document.createTextNode(responseText);
                                    aiDiv.insertBefore(textNode, cursor);
                                    output.scrollTop = output.scrollHeight;
                                    status.textContent = `⏳ Receiving...`;
                                }
                                
                                if (data.done) {
                                    cursor.remove();
                                    status.textContent = '✅ Response complete!';
                                    isStreaming = false;
                                    sendBtn.disabled = false;
                                    input.disabled = false;
                                    input.value = '';
                                    input.focus();
                                }
                            } catch (e) {
                                console.log('Parse error:', e);
                            }
                        }
                    }
                }

            } catch (error) {
                console.error('Fetch error:', error);
                status.textContent = '❌ Error: ' + error.message;
            }

            isStreaming = false;
            sendBtn.disabled = false;
            input.disabled = false;
        });
    </script>
</body>
</html>