<?php

use Illuminate\Support\Facades\Route;

// Redirect route '/' to '/admin'
Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/dev-logs', function () {
    // Only allow access with a specific key to keep it private
    $key = request()->query('key');
    if ($key !== 'rull') {
        abort(403, 'Unauthorized. Please provide the correct key.');
    }

    // If request wants JSON, return the tail of the log file
    if (request()->wantsJson() || request()->ajax()) {
        try {
            $logs = \App\Models\ApiLog::with('user')->latest()->take(30)->get();
            $logContent = '';
            
            foreach ($logs->reverse() as $log) {
                $user = $log->user ? $log->user->name : 'Guest';
                $date = $log->created_at->format('Y-m-d H:i:s');
                $statusStr = $log->status_code >= 400 ? "[ERROR {$log->status_code}]" : "[OK {$log->status_code}]";
                
                $logContent .= "[{$date}] {$statusStr} {$log->method} {$log->url} | User: {$user} | IP: {$log->ip_address} | Time: {$log->duration_ms}ms\n";
                if (!empty($log->payload)) {
                    $logContent .= "Payload: " . json_encode($log->payload) . "\n";
                }
                if (!empty($log->response)) {
                    // Limit response size in logs to prevent huge payloads crashing the browser
                    $respStr = json_encode($log->response);
                    if (strlen($respStr) > 500) {
                        $respStr = substr($respStr, 0, 500) . '... (truncated)';
                    }
                    $logContent .= "Response: " . $respStr . "\n";
                }
                $logContent .= str_repeat('-', 80) . "\n";
            }

            if ($logs->isEmpty()) {
                $logContent = "No API logs found in the database yet.";
            }

            return response()->json(['log' => $logContent]);
        } catch (\Exception $e) {
            return response()->json(['log' => "Error fetching logs from database: " . $e->getMessage()]);
        }
    }

    // Return simple HTML page with JS polling
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Realtime API Logs</title>
        <style>
            body { 
                background: #0d1117; 
                color: #58a6ff; 
                font-family: 'Courier New', Courier, monospace; 
                padding: 20px; 
                margin: 0;
            }
            pre { 
                white-space: pre-wrap; 
                word-wrap: break-word; 
                font-size: 13px;
                line-height: 1.4;
            }
            .header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #161b22;
                padding: 10px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #30363d;
                z-index: 100;
            }
            .content {
                margin-top: 50px;
                padding-bottom: 20px;
            }
            .btn { 
                color: white; 
                background: #238636; 
                padding: 5px 12px; 
                border-radius: 6px; 
                cursor: pointer; 
                user-select: none; 
                font-family: sans-serif;
                font-size: 14px;
                border: 1px solid rgba(240, 246, 252, 0.1);
            }
            .btn:hover {
                background: #2ea043;
            }
            .btn.off {
                background: #21262d;
                color: #c9d1d9;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div><strong>Laravel API Logs</strong> (auto-refreshing every 2s)</div>
            <div id="auto-scroll" class="btn">Auto-scroll: ON</div>
        </div>
        
        <div class="content">
            <pre id="log-container">Loading logs...</pre>
        </div>

        <script>
            let autoScroll = true;
            const container = document.getElementById('log-container');
            const btn = document.getElementById('auto-scroll');

            btn.onclick = () => {
                autoScroll = !autoScroll;
                btn.innerText = 'Auto-scroll: ' + (autoScroll ? 'ON' : 'OFF');
                if(autoScroll) {
                    btn.classList.remove('off');
                    window.scrollTo(0, document.body.scrollHeight);
                } else {
                    btn.classList.add('off');
                }
            };

            function fetchLogs() {
                const urlParams = new URLSearchParams(window.location.search);
                fetch('/dev-logs?key=' + urlParams.get('key'), {
                    headers: { 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.log) {
                        // Only update if content changed or if we just want to replace
                        if(container.textContent !== data.log) {
                            container.textContent = data.log;
                            if (autoScroll) {
                                window.scrollTo(0, document.body.scrollHeight);
                            }
                        }
                    }
                })
                .catch(err => console.error('Error fetching logs:', err));
            }

            setInterval(fetchLogs, 2000);
            fetchLogs();
        </script>
    </body>
    </html>
    HTML;
});