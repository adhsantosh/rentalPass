<?php
session_start();
require 'database.php'; // MySQLi $conn

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

function getDatabaseConnection() {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=rental_pass", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// === DEMAND PREDICTION & FRAUD DETECTION ===
function predictDemand($pdo) {
    $sql = "SELECT DAYOFWEEK(rental_start) as day_of_week, COUNT(*) as rental_count
            FROM rentals WHERE rental_start IS NOT NULL GROUP BY day_of_week";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $X = []; $y = [];
    foreach ($data as $row) { $X[] = $row['day_of_week']; $y[] = $row['rental_count']; }

    $n = count($X);
    if ($n < 2) return [];

    $sum_x = array_sum($X); $sum_y = array_sum($y);
    $sum_xy = $sum_xx = 0;
    for ($i = 0; $i < $n; $i++) {
        $sum_xy += $X[$i] * $y[$i];
        $sum_xx += $X[$i] * $X[$i];
    }

    $m = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
    $b = ($sum_y - $m * $sum_x) / $n;

    $predictions = [];
    $today = new DateTime();
    $threshold = 2;
    for ($i = 0; $i < 7; $i++) {
        $date = clone $today;
        $date->modify("+$i days");
        $day_of_week = (int)$date->format('N') + 1;
        $predicted = max(0, round($m * $day_of_week + $b, 1));
        $predictions[] = [
            'date' => $date->format('Y-m-d'),
            'day' => $date->format('l'),
            'predicted_count' => $predicted,
            'high_demand' => $predicted > $threshold ? 'High Demand Expected' : 'Normal Demand'
        ];
    }
    return $predictions;
}

function detectAnomalies($pdo) {
    $anomalies = [];
    $sql = "SELECT UID, COUNT(*) as booking_count, MIN(rental_start) as first_booking, MAX(rental_start) as last_booking
            FROM rentals WHERE rental_start >= NOW() - INTERVAL 1 DAY GROUP BY UID HAVING booking_count > 3";
    foreach ($pdo->query($sql) as $row) {
        $anomalies[] = "User ID {$row['UID']} made {$row['booking_count']} bookings in last 24 hours.";
    }
    $sql = "SELECT UID, rental_start, COUNT(*) as vehicle_count FROM rentals WHERE status = 'active' GROUP BY UID, rental_start HAVING vehicle_count > 2";
    foreach ($pdo->query($sql) as $row) {
        $anomalies[] = "User ID {$row['UID']} booked {$row['vehicle_count']} vehicles at once.";
    }
    return $anomalies;
}
function detectFraudML($pdo) {
    // Fetch full rental history
    $sql = "SELECT UID, rental_start, TIMESTAMPDIFF(HOUR, rental_start, rental_end) AS duration
            FROM rentals WHERE rental_start IS NOT NULL";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) < 10) return ["Not enough data to apply ML fraud detection."];

    // === Step 1: Build features per user ===
    $features = []; // ML dataset
    $userStats = []; // final fraud summary

    foreach ($rows as $r) {
        $uid = $r["UID"];

        if (!isset($features[$uid])) {
            $features[$uid] = [
                "count" => 0,
                "total_duration" => 0,
                "days" => []
            ];
        }

        $features[$uid]["count"]++;
        $features[$uid]["total_duration"] += max(1, (int)$r["duration"]);

        $day = (int)date("N", strtotime($r["rental_start"]));
        $features[$uid]["days"][] = $day;
    }

    // === Step 2: Convert features for ML ===
    $data = [];
    foreach ($features as $uid => $f) {
        $avg_duration = $f["total_duration"] / max(1, $f["count"]);
        $day_variance = 0;

        if (count($f["days"]) > 1) {
            $mean_day = array_sum($f["days"]) / count($f["days"]);
            foreach ($f["days"] as $d) $day_variance += pow($d - $mean_day, 2);
        }

        $day_variance = $day_variance / max(1, count($f["days"]));

        // ML feature vector
        $data[$uid] = [
            $f["count"],        // How many rentals?
            $avg_duration,      // Average rental time?
            $day_variance       // Consistency of booking days?
        ];
    }

    // === Step 3: Compute mean + stdDev per feature ===
    $means = $stds = [0, 0, 0];
    $nUsers = count($data);

    // Compute means
    foreach ($data as $vec) {
        for ($i = 0; $i < 3; $i++) $means[$i] += $vec[$i];
    }
    for ($i = 0; $i < 3; $i++) $means[$i] /= $nUsers;

    // Compute stdDev
    foreach ($data as $vec) {
        for ($i = 0; $i < 3; $i++) $stds[$i] += pow($vec[$i] - $means[$i], 2);
    }
    for ($i = 0; $i < 3; $i++) $stds[$i] = sqrt($stds[$i] / $nUsers);

    // === Step 4: Assign ML Anomaly Scores ===
    $frauds = [];

    foreach ($data as $uid => $vec) {
        $z1 = ($vec[0] - $means[0]) / ($stds[0] ?: 1);
        $z2 = ($vec[1] - $means[1]) / ($stds[1] ?: 1);
        $z3 = ($vec[2] - $means[2]) / ($stds[2] ?: 1);

        // Combined anomaly score (ML)
        $score = abs($z1) + abs($z2) + abs($z3);

        if ($score > 4) { // ML threshold
            $frauds[] = "User ID $uid shows suspicious behavior (ML score: " . round($score, 2) . ")";
        }
    }

    return $frauds;
}

$pdo = getDatabaseConnection();
$demandPredictions = predictDemand($pdo);
$anomalies = array_merge(
    detectAnomalies($pdo),      
    detectFraudML($pdo)         
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; margin: 0; }
        .sidebar { position: fixed; top: 0; left: 0; width: 250px; height: 100vh; background: #343a40; padding: 20px; color: white; overflow-y: auto; }
        .sidebar h3 { text-align: center; margin-bottom: 30px; }
        .sidebar a { color: white; display: block; padding: 12px 15px; border-radius: 6px; margin-bottom: 5px; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background: #007bff; }
        .content { margin-left: 250px; padding: 30px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }

        /* ADMIN CHAT PANEL */
        #adminChatPanel {
            position: fixed; top: 0; right: -750px; width: 750px; height: 100vh;
            background: white; box-shadow: -10px 0 30px rgba(0,0,0,0.2); z-index: 9999;
            transition: right 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); display: flex; border-left: 1px solid #ddd;
        }
        #adminUserList { width: 320px; background: #f8f9fa; border-right: 1px solid #ddd; }
        #adminUserList .header { background: #162447; color: white; padding: 18px 20px; font-weight: 600; font-size: 18px; }
        .user-item { padding: 16px 20px; border-bottom: 1px solid #eee; cursor: pointer; transition: 0.2s; }
        .user-item:hover, .user-item.active { background: #e6f7f2; }
        .user-item h6 { margin: 0; font-size: 15px; font-weight: 600; }
        .user-item p { margin: 4px 0 0; font-size: 13px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        #adminChatArea { flex: 1; display: flex; flex-direction: column; background: #f0f2f5; }
        #chatHeader { padding: 16px 20px; background: white; border-bottom: 1px solid #eee; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        #adminChatMessages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
        .msg { max-width: 70%; padding: 10px 14px; border-radius: 18px; line-height: 1.4; word-wrap: break-word; }
        .msg.user { background: white; align-self: flex-start; border: 1px solid #e5e5ea; border-bottom-left-radius: 4px; }
        .msg.admin { background: #162447; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
        .msg time { font-size: 11px; opacity: 0.8; margin-top: 4px; display: block; }

        #chatInputArea { padding: 15px; background: white; border-top: 1px solid #ddd; display: flex; gap: 10px; }
        #adminMsgInput { flex: 1; padding: 12px 16px; border: 1px solid #ddd; border-radius: 25px; outline: none; font-size: 15px; }
        #adminMsgInput:focus { border-color: #162447; }
        #sendBtn { background: #162447; color: white; border: none; width: 62px; height: 48px; border-radius: 50%; cursor: pointer; font-size: 18px; }

        #chatToggleAdmin { position:fixed; top:20px; right:20px; background:#162447; color:white; padding:12px 24px; border:none; border-radius:50px; cursor:pointer; z-index:9998; box-shadow:0 4px 15px rgba(19,1,100,0.4); font-weight:600; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 class="text-white text-center">Admin Menu</h3>
    <nav class="nav flex-column">
        <a class="nav-link active" href="admin_dashboard.php">Admin Dashboard</a>
        <a class="nav-link" href="manage_twoWheeler.php">Manage Two-Wheeler</a>
        <a class="nav-link" href="manage_fourWheeler.php">Manage Four-Wheeler</a>
        <a class="nav-link" href="manage_users.php">Manage Users</a>
        <a class="nav-link" href="view_rentals.php">View Rentals</a>
        <a class="nav-link" href="admin_logout.php">Logout</a>
    </nav>
</div>

<div class="content">
    <h1 class="text-center">Admin Dashboard</h1>

    <!-- Demand Prediction -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Demand Prediction (Next 7 Days)</h5>
            <?php if (empty($demandPredictions)): ?>
                <p>Not enough data to predict demand.</p>
            <?php else: ?>
                <table class="table table-bordered">
                    <thead><tr><th>Date</th><th>Day</th><th>Predicted Rentals</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($demandPredictions as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['date']) ?></td>
                                <td><?= htmlspecialchars($p['day']) ?></td>
                                <td><?= $p['predicted_count'] ?></td>
                                <td><?= $p['high_demand'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fraud Detection -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Fraud/Anomaly Detection</h5>
            <?php if (empty($anomalies)): ?>
                <p class="text-success">No suspicious activities detected.</p>
            <?php else: foreach ($anomalies as $a): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($a) ?></div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- CHAT PANEL -->
<div id="adminChatPanel">
    <div id="adminUserList">
        <div class="header">Customer Chats</div>
        <div id="userListContainer"></div>
    </div>
    <div id="adminChatArea">
        <div id="chatHeader">
            <div>
                <div id="activeUserName">Select a customer</div>
                <small style="color:#777;">Click to start chatting</small>
            </div>
            <button onclick="closeAdminChat()" style="background:none;border:none;font-size:28px;cursor:pointer;color:#666;">×</button>
        </div>
        <div id="adminChatMessages">
            <div style="text-align:center;color:#999;margin-top:100px;">No conversation selected</div>
        </div>
        <div id="chatInputArea" style="display:none;">
            <input type="text" id="adminMsgInput" placeholder="Type a message..." onkeypress="if(event.key==='Enter') sendAdminReply()">
            <button id="sendBtn" onclick="sendAdminReply()">Send</button>
        </div>
    </div>
</div>

<button onclick="openAdminChat()" id="chatToggleAdmin">
    Chat (<span id="adminUnreadCount">0</span>)
</button>

<!-- SOCKET.IO + REAL-TIME CHAT -->
<script src="http://localhost:3000/socket.io/socket.io.js"></script>
<script>
const socket = io('http://localhost:3000');
socket.emit('join-admin');

let currentChatUserId = null;

socket.on('new-message', (data) => {
    if (currentChatUserId && data.from == currentChatUserId) {
        appendMessage(data.message, 'user');
    }
    loadUserList(); // Instantly update list + badge
});

function appendMessage(msg, type) {
    const chat = document.getElementById('adminChatMessages');
    const div = document.createElement('div');
    div.className = `msg ${type}`;
    div.innerHTML = msg + `<time>${new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</time>`;
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
}

function openAdminChat() {
    document.getElementById("adminChatPanel").style.right = "0";
    loadUserList();
}

function closeAdminChat() {
    document.getElementById("adminChatPanel").style.right = "-750px";
    currentChatUserId = null;
}

function loadUserList() {
    fetch("admin_chat_users.php")
        .then(r => r.json())
        .then(users => {
            document.getElementById("adminUnreadCount").textContent = users.length || 0;
            let html = "";
            users.forEach(u => {
                const active = currentChatUserId == u.UID ? "active" : "";
                html += `<div class="user-item ${active}" onclick="openUserChat(${u.UID}, '${u.name.replace(/'/g, "\\'")}')">
                    <h6>${u.name}</h6>
                    <p>${(u.last_msg || "No messages").substring(0, 40)}...</p>
                </div>`;
            });
            document.getElementById("userListContainer").innerHTML = html || "<div style='padding:40px;text-align:center;color:#999;'>No active chats</div>";
        });
}

function openUserChat(uid, name) {
    currentChatUserId = uid;
    document.getElementById("activeUserName").textContent = name;
    document.getElementById("chatInputArea").style.display = "flex";
    loadMessages(uid);
}

function loadMessages(uid) {
    fetch("admin_chat_load.php?uid=" + uid)
        .then(r => r.text())
        .then(html => {
            document.getElementById("adminChatMessages").innerHTML = html;
            document.getElementById("adminChatMessages").scrollTop = document.getElementById("adminChatMessages").scrollHeight;
        });
}

function sendAdminReply() {
    const input = document.getElementById("adminMsgInput");
    const msg = input.value.trim();
    if (!msg || !currentChatUserId) return;

    socket.emit('send-message', {
        from: 'admin',
        to: currentChatUserId,
        message: msg,
        senderType: 'admin'
    });

    fetch("admin_chat.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "message=" + encodeURIComponent(msg) + "&user_id=" + currentChatUserId
    });

    appendMessage(msg, 'admin');
    input.value = "";
}

// Load user list on start
loadUserList();
</script>

</body>
</html>