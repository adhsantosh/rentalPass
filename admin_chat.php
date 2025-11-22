<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=rental_pass", "root", "");

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message']);
    $uid = (int)$_POST['user_id'];
    if ($msg && $uid) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender, sender_id, receiver_id, message) VALUES ('admin', 0, ?, ?)");
        $stmt->execute([$uid, $msg]);
    }
    exit;
}

// Get users who messaged
$users = $pdo->query("
    SELECT DISTINCT u.UID, u.name, u.phone,
           (SELECT message FROM messages WHERE receiver_id = u.UID AND sender='user' ORDER BY timestamp DESC LIMIT 1) as last_msg,
           (SELECT timestamp FROM messages WHERE receiver_id = u.UID AND sender='user' ORDER BY timestamp DESC LIMIT 1) as last_time
    FROM users u
    JOIN messages m ON u.UID = m.sender_id AND m.sender = 'user'
    ORDER BY last_time DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat - Customer Support</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin:0; font-family: 'Inter', sans-serif; background:#f0f2f5; height:100vh; overflow:hidden; }
        .container { display:flex; height:100vh; }
        .sidebar {
            width: 320px; background:white; border-right:1px solid #ddd;
            overflow-y:auto;
        }
        .sidebar-header {
            padding:20px; background:#162447; color:white; font-weight:600; font-size:18px;
        }
        .user-list { }
        .user-item {
            padding:15px 20px; border-bottom:1px solid #eee; cursor:pointer; transition:0.2s;
            display:flex; align-items:center; gap:12px;
        }
        .user-item:hover, .user-item.active { background:#f6f6f6; }
        .user-item.active { background:#e6f7f2; }
        .user-avatar {
            width:50px; height:50px; background:#162447; color:white;
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            font-weight:bold; font-size:18px;
        }
        .user-info h4 { margin:0; font-size:15px; }
        .user-info p { margin:0; font-size:13px; color:#777; }
        .chat-area {
            flex:1; display:flex; flex-direction:column; background:#f0f2f5;
        }
        .chat-header {
            padding:15px 20px; background:white; border-bottom:1px solid #ddd;
            display:flex; align-items:center; gap:15px;
        }
        .chat-messages {
            flex:1; padding:20px; overflow-y:auto;
            display:flex; flex-direction:column; gap:12px;
        }
        .msg {
            max-width:70%; padding:10px 14px; border-radius:18px;
            line-height:1.4; word-wrap:break-word;
        }
        .msg.user { background:#162447; color:white; align-self:flex-end; border-bottom-right-radius:4px; }
        .msg.admin { background:white; color:#111; align-self:flex-start; border:1px solid #e5e5ea; border-bottom-left-radius:4px; }
        .msg time { font-size:11px; opacity:0.8; margin-top:4px; display:block; }
        .chat-input {
            padding:15px; background:white; border-top:1px solid #ddd;
            display:flex; gap:10px;
        }
        #chatInput { flex:1; padding:12px 16px; border:1px solid #ddd; border-radius:25px; outline:none; }
        #chatInput:focus { border-color:#162447; }
        #sendChatBtn { background:#162447; color:white; border:none; width:48px; height:48px; border-radius:50%; cursor:pointer; font-size:20px; }
        .no-chat { text-align:center; color:#777; margin-top:100px; font-size:18px; }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div class="sidebar-header">Customer Chatss</div>
        <div class="user-list">
            <?php foreach ($users as $user): ?>
                <div class="user-item" onclick="openChat(<?= $user['UID'] ?>, '<?= htmlspecialchars($user['name']) ?>')">
                    <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 2)) ?></div>
                    <div class="user-info">
                        <h4><?= htmlspecialchars($user['name']) ?></h4>
                        <p><?= htmlspecialchars($user['last_msg'] ?? 'No messages') ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="chat-area" id="chatArea">
        <div class="no-chat">
            <h3>Select a customer to start chatting</h3>
            <p>Click on any user from the left panel</p>
        </div>
    </div>
</div>

<script>
let currentUserId = null;

function openChat(uid, name) {
    currentUserId = uid;
    document.querySelectorAll('.user-item').forEach(el => el.classList.remove('active'));
    event.target.closest('.user-item').classList.add('active');

    document.getElementById('chatArea').innerHTML = `
        <div class="chat-header">
            <div class="user-avatar">${name.substring(0,2).toUpperCase()}</div>
            <div>
                <h3 style="margin:0;font-size:18px;">${name}</h3>
                <small style="color:#777;">Active now</small>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages"></div>
        <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Type a message..." />
            <button id="sendChatBtn" onclick="sendAdminMessage()">Send</button>
        </div>
    `;

    loadChat(uid);
    setInterval(() => loadChat(uid), 2000);

    document.getElementById('chatInput').addEventListener('keypress', e => {
        if (e.key === 'Enter') sendAdminMessage();
    });
}

function loadChat(uid) {
    if (!uid) return;
    fetch(`admin_chat_load.php?uid=${uid}&t=${Date.now()}`)
        .then(r => r.text())
        .then(html => {
            const msgDiv = document.getElementById('chatMessages');
            if (msgDiv) {
                msgDiv.innerHTML = html;
                msgDiv.scrollTop = msgDiv.scrollHeight;
            }
        });
}

function sendAdminMessage() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if (!msg || !currentUserId) return;

    fetch('admin_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `message=${encodeURIComponent(msg)}&user_id=${currentUserId}`
    }).then(() => {
        input.value = '';
        loadChat(currentUserId);
    });
}
</script>
</body>
</html>