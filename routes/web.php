<?php

use Illuminate\Support\Facades\Route;

// Redirect route '/' to '/admin'
Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/dev-logs', function () {
    $key = request()->query('key');
    if ($key !== 'rull') {
        abort(403, 'Unauthorized.');
    }

    $tab = request()->query('tab', 'api');
    $limit = min((int) request()->query('limit', 50), 200);

    // JSON API for polling
    if (request()->wantsJson() || request()->ajax()) {
        try {
            if ($tab === 'app') {
                $logs = \App\Models\AppLog::latest()->take($limit)->get();
                $logContent = '';

                foreach ($logs->reverse() as $log) {
                    $date = $log->created_at->format('Y-m-d H:i:s');
                    $level = strtoupper($log->level);
                    $logContent .= "[{$date}] [{$level}] {$log->message}\n";
                    if (!empty($log->context)) {
                        $ctx = json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        if (strlen($ctx) > 1000) {
                            $ctx = substr($ctx, 0, 1000) . "\n... (truncated)";
                        }
                        $logContent .= "  Context: {$ctx}\n";
                    }
                    $logContent .= "\n";
                }

                return response()->json([
                    'log' => $logContent ?: 'No application logs yet.',
                    'count' => $logs->count(),
                ]);
            }

            if ($tab === 'system') {
                $info = [];
                $info[] = "=== System Info ===";
                $info[] = "PHP Version: " . PHP_VERSION;
                $info[] = "Laravel Version: " . app()->version();
                $info[] = "Environment: " . config('app.env');
                $info[] = "Debug Mode: " . (config('app.debug') ? 'ON' : 'OFF');
                $info[] = "Log Channel: " . config('logging.default');
                $info[] = "Log Stack: " . env('LOG_STACK', 'single');
                $info[] = "DB Connection: " . config('database.default');
                $info[] = "Cache Driver: " . config('cache.default');
                $info[] = "Queue Driver: " . config('queue.default');
                $info[] = "Storage Disk: " . config('filesystems.default');
                $info[] = "Photo Disk: " . (config('app.env') === 'production' ? 's3' : 'public');
                $info[] = "";
                $info[] = "=== Firebase ===";
                $info[] = "Project ID: " . config('services.firebase.project_id');
                $credPath = config('services.firebase.credentials');
                $info[] = "Credentials File: " . $credPath;
                $info[] = "Credentials File Exists: " . (file_exists($credPath) ? 'YES' : 'NO');
                $info[] = "FIREBASE_CREDENTIALS_JSON env: " . (!empty(env('FIREBASE_CREDENTIALS_JSON')) ? 'SET (' . strlen(env('FIREBASE_CREDENTIALS_JSON')) . ' chars)' : 'NOT SET');
                $info[] = "";
                $info[] = "=== Storage (S3/Cloud) ===";
                $info[] = "AWS Bucket: " . config('filesystems.disks.s3.bucket');
                $info[] = "AWS URL: " . config('filesystems.disks.s3.url');
                $info[] = "AWS Endpoint: " . config('filesystems.disks.s3.endpoint');
                $info[] = "";
                $info[] = "=== Database Stats ===";
                try {
                    $info[] = "Users: " . \App\Models\User::count();
                    $info[] = "API Logs: " . \App\Models\ApiLog::count();
                    $info[] = "App Logs: " . \App\Models\AppLog::count();
                    $info[] = "Latest API Log: " . (\App\Models\ApiLog::latest()->first()?->created_at ?? 'none');
                    $info[] = "Latest App Log: " . (\App\Models\AppLog::latest()->first()?->created_at ?? 'none');
                } catch (\Exception $e) {
                    $info[] = "DB Error: " . $e->getMessage();
                }

                return response()->json(['log' => implode("\n", $info)]);
            }

            // Default: API logs
            $logs = \App\Models\ApiLog::with('user')->latest()->take($limit)->get();
            $logContent = '';

            foreach ($logs->reverse() as $log) {
                $user = $log->user ? $log->user->name : 'Guest';
                $date = $log->created_at->format('Y-m-d H:i:s');
                $statusStr = $log->status_code >= 400 ? "[ERROR {$log->status_code}]" : "[OK {$log->status_code}]";

                $logContent .= "[{$date}] {$statusStr} {$log->method} {$log->url} | User: {$user} | IP: {$log->ip_address} | {$log->duration_ms}ms\n";
                if (!empty($log->payload)) {
                    $payloadStr = json_encode($log->payload, JSON_UNESCAPED_SLASHES);
                    if (strlen($payloadStr) > 500) {
                        $payloadStr = substr($payloadStr, 0, 500) . '... (truncated)';
                    }
                    $logContent .= "  > Payload: {$payloadStr}\n";
                }
                if (!empty($log->response)) {
                    $respStr = json_encode($log->response, JSON_UNESCAPED_SLASHES);
                    if (strlen($respStr) > 500) {
                        $respStr = substr($respStr, 0, 500) . '... (truncated)';
                    }
                    $logContent .= "  > Response: {$respStr}\n";
                }
                $logContent .= "\n";
            }

            return response()->json([
                'log' => $logContent ?: 'No API logs yet.',
                'count' => $logs->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['log' => "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString()]);
        }
    }

    // HTML Dashboard
    $html = <<<'HTMLPAGE'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Logs Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #0d1117;
            color: #c9d1d9;
            font-family: -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif;
        }
        .header {
            position: fixed; top: 0; left: 0; right: 0;
            background: #161b22;
            border-bottom: 1px solid #30363d;
            z-index: 100;
            padding: 8px 16px;
        }
        .header-top {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 16px; color: #58a6ff;
            display: flex; align-items: center; gap: 8px;
        }
        .header h1 .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #3fb950; display: inline-block;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .controls { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .tabs {
            display: flex; gap: 2px;
            background: #0d1117; border-radius: 6px; padding: 2px;
        }
        .tab {
            padding: 6px 14px; border-radius: 4px; cursor: pointer;
            font-size: 13px; color: #8b949e; border: none; background: transparent;
            transition: all 0.2s;
        }
        .tab:hover { color: #c9d1d9; background: #21262d; }
        .tab.active { background: #238636; color: white; }
        .btn {
            padding: 5px 10px; border-radius: 6px; cursor: pointer;
            font-size: 12px; border: 1px solid #30363d; background: #21262d;
            color: #c9d1d9; transition: all 0.15s;
        }
        .btn:hover { background: #30363d; border-color: #58a6ff; }
        .btn.active { background: #238636; color: white; border-color: #238636; }
        select.btn { appearance: auto; padding-right: 20px; }
        .content { margin-top: 88px; padding: 12px 16px; padding-bottom: 40px; }
        #log-container {
            font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', 'Courier New', monospace;
            font-size: 12px; line-height: 1.6;
            white-space: pre-wrap; word-wrap: break-word;
            min-height: calc(100vh - 130px);
        }
        .log-error { color: #f85149; }
        .log-warning { color: #d29922; }
        .log-info { color: #58a6ff; }
        .log-debug { color: #8b949e; }
        .log-ok { color: #3fb950; }
        .log-meta { color: #6e7681; }
        .log-url { color: #d2a8ff; }
        .log-payload { color: #7ee787; }
        .status-bar {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #161b22; border-top: 1px solid #30363d;
            padding: 4px 16px; font-size: 11px; color: #8b949e;
            display: flex; justify-content: space-between;
        }
        .filter-bar { display: flex; gap: 6px; align-items: center; }
        input.search {
            background: #0d1117; border: 1px solid #30363d; color: #c9d1d9;
            padding: 5px 10px; border-radius: 6px; font-size: 12px; width: 180px;
        }
        input.search:focus { outline: none; border-color: #58a6ff; }
        .badge {
            display: inline-block; padding: 1px 6px; border-radius: 10px;
            font-size: 10px; font-weight: 600;
        }
        .badge-error { background: #da363333; color: #f85149; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <h1><span class="dot"></span> Dev Logs Dashboard</h1>
            <div class="controls">
                <input type="text" class="search" id="search" placeholder="Filter logs...">
                <select class="btn" id="limit">
                    <option value="30">30</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
                <button class="btn active" id="auto-scroll">Auto-scroll</button>
                <button class="btn" id="pause-btn">Pause</button>
            </div>
        </div>
        <div class="filter-bar">
            <div class="tabs">
                <button class="tab active" data-tab="api">API Logs</button>
                <button class="tab" data-tab="app">App Logs</button>
                <button class="tab" data-tab="system">System Info</button>
            </div>
            <span id="error-count" class="badge badge-error" style="display:none"></span>
        </div>
    </div>

    <div class="content">
        <pre id="log-container">Loading...</pre>
    </div>

    <div class="status-bar">
        <span id="status-left">Connecting...</span>
        <span id="status-right"></span>
    </div>

    <script>
        let autoScroll = true, paused = false, currentTab = 'api';
        const container = document.getElementById('log-container');
        const params = new URLSearchParams(window.location.search);
        const authKey = params.get('key');

        document.querySelectorAll('.tab').forEach(tab => {
            tab.onclick = () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentTab = tab.dataset.tab;
                container.innerHTML = 'Loading...';
                fetchLogs();
            };
        });

        document.getElementById('auto-scroll').onclick = function() {
            autoScroll = !autoScroll;
            this.classList.toggle('active', autoScroll);
            if (autoScroll) window.scrollTo(0, document.body.scrollHeight);
        };

        document.getElementById('pause-btn').onclick = function() {
            paused = !paused;
            this.classList.toggle('active', paused);
            this.textContent = paused ? 'Resume' : 'Pause';
        };

        function colorize(text, tab) {
            if (tab === 'system') {
                return text.replace(/^(===.+===)$/gm, '<span class="log-info">$1</span>')
                          .replace(/(YES|SET|ON)\b/g, '<span class="log-ok">$1</span>')
                          .replace(/(NO|NOT SET|OFF)\b/g, '<span class="log-error">$1</span>');
            }
            if (tab === 'app') {
                return text.replace(/\[ERROR\]/g, '<span class="log-error">[ERROR]</span>')
                          .replace(/\[WARNING\]/g, '<span class="log-warning">[WARNING]</span>')
                          .replace(/\[INFO\]/g, '<span class="log-info">[INFO]</span>')
                          .replace(/\[DEBUG\]/g, '<span class="log-debug">[DEBUG]</span>')
                          .replace(/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/g, '<span class="log-meta">$1</span>')
                          .replace(/(Context:)/g, '<span class="log-payload">$1</span>');
            }
            return text.replace(/\[ERROR \d+\]/g, m => '<span class="log-error">' + m + '</span>')
                      .replace(/\[OK \d+\]/g, m => '<span class="log-ok">' + m + '</span>')
                      .replace(/(GET|POST|PUT|DELETE|PATCH)\s/g, '<span class="log-info">$1</span> ')
                      .replace(/(https?:\/\/[^\s|]+)/g, '<span class="log-url">$1</span>')
                      .replace(/(> Payload:)/g, '<span class="log-payload">$1</span>')
                      .replace(/(> Response:)/g, '<span class="log-meta">$1</span>')
                      .replace(/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/g, '<span class="log-meta">$1</span>')
                      .replace(/(\d+\.\d+ms)/g, '<span class="log-warning">$1</span>');
        }

        function fetchLogs() {
            if (paused) return;
            const limit = document.getElementById('limit').value;
            fetch('/dev-logs?key=' + authKey + '&tab=' + currentTab + '&limit=' + limit, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                const filter = document.getElementById('search').value.toLowerCase();
                let logText = data.log || 'No logs.';
                if (filter) {
                    logText = logText.split('\n').filter(l => l.toLowerCase().includes(filter)).join('\n');
                    if (!logText.trim()) logText = 'No logs matching "' + filter + '"';
                }
                const colored = colorize(logText, currentTab);
                if (container.innerHTML !== colored) {
                    container.innerHTML = colored;
                    if (autoScroll) window.scrollTo(0, document.body.scrollHeight);
                }
                const now = new Date().toLocaleTimeString();
                document.getElementById('status-left').textContent =
                    'Connected | Tab: ' + currentTab.toUpperCase() + ' | Last refresh: ' + now;
                document.getElementById('status-right').textContent =
                    data.count !== undefined ? data.count + ' entries' : '';
                const errMatches = logText.match(/\[ERROR/gi);
                const errBadge = document.getElementById('error-count');
                if (errMatches && errMatches.length > 0) {
                    errBadge.textContent = errMatches.length + ' errors';
                    errBadge.style.display = 'inline-block';
                } else {
                    errBadge.style.display = 'none';
                }
            })
            .catch(() => {
                document.getElementById('status-left').textContent = 'Connection error';
            });
        }

        document.getElementById('limit').onchange = fetchLogs;
        document.getElementById('search').oninput = fetchLogs;
        fetchLogs();
        setInterval(fetchLogs, 3000);
    </script>
</body>
</html>
HTMLPAGE;

    return response($html);
});