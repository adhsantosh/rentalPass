<?php
session_start();
require 'database.php'; // Assumes MySQLi connection as $conn
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection (fallback to PDO if needed)
function getDatabaseConnection() {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=rental_pass", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Demand Prediction: Simple Linear Regression
function predictDemand($pdo) {
    // Fetch rental data (count rentals per day of week)
    $sql = "SELECT DAYOFWEEK(rental_start) as day_of_week, COUNT(*) as rental_count
            FROM rentals
            WHERE rental_start IS NOT NULL
            GROUP BY day_of_week";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare training data (day_of_week: 1=Sunday, 2=Monday, ..., 7=Saturday)
    $X = []; // Day of week (1-7)
    $y = []; // Rental counts
    foreach ($data as $row) {
        $X[] = $row['day_of_week'];
        $y[] = $row['rental_count'];
    }

    // Simple Linear Regression: y = mx + b
    $n = count($X);
    if ($n < 2) return []; // Not enough data for regression

    $sum_x = array_sum($X);
    $sum_y = array_sum($y);
    $sum_xy = 0;
    $sum_xx = 0;
    for ($i = 0; $i < $n; $i++) {
        $sum_xy += $X[$i] * $y[$i];
        $sum_xx += $X[$i] * $X[$i];
    }

    // Calculate slope (m) and intercept (b)
    $m = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
    $b = ($sum_y - $m * $sum_x) / $n;

    // Predict demand for next 7 days
    $predictions = [];
    $today = new DateTime('2025-08-19'); // Current date
    $threshold = 2; // Assume high demand if predicted count > 2 (adjust based on data)
    for ($i = 0; $i < 7; $i++) {
        $date = clone $today;
        $date->modify("+$i days");
        $day_of_week = (int)$date->format('N') + 1; // 1=Sunday, ..., 7=Saturday
        $predicted_count = max(0, $m * $day_of_week + $b); // Predicted rentals
        $predictions[] = [
            'date' => $date->format('Y-m-d'),
            'day' => $date->format('l'),
            'predicted_count' => round($predicted_count, 1),
            'high_demand' => $predicted_count > $threshold ? 'High Demand Expected' : 'Normal Demand'
        ];
    }
    return $predictions;
}

// Fraud/Anomaly Detection: Rule-Based
function detectAnomalies($pdo) {
    $anomalies = [];
    
    // Rule 1: Multiple bookings by the same user within 24 hours
    $sql = "SELECT UID, COUNT(*) as booking_count, MIN(rental_start) as first_booking, MAX(rental_start) as last_booking
            FROM rentals
            WHERE rental_start >= NOW() - INTERVAL 1 DAY
            GROUP BY UID
            HAVING booking_count > 3"; // Threshold: More than 3 bookings in 24 hours
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $anomalies[] = "User ID {$row['UID']} made {$row['booking_count']} bookings between {$row['first_booking']} and {$row['last_booking']}.";
    }

    // Rule 2: User booking multiple vehicles at once
    $sql = "SELECT UID, rental_start, COUNT(*) as vehicle_count
            FROM rentals
            WHERE status = 'active'
            GROUP BY UID, rental_start
            HAVING vehicle_count > 2"; // Threshold: More than 2 vehicles in one booking
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $anomalies[] = "User ID {$row['UID']} booked {$row['vehicle_count']} vehicles on {$row['rental_start']}.";
    }

    return $anomalies;
}

$pdo = getDatabaseConnection();
$demandPredictions = predictDemand($pdo);
$anomalies = detectAnomalies($pdo);
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
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }

        /* ADMIN CHAT PANEL - WhatsApp Style */
        #adminChatPanel {
            position: fixed; top: 0; right: -750px; width: 750px; height: 100vh;
            background: white; box-shadow: -10px 0 30px rgba(0,0,0,0.2); z-index: 9999;
            transition: right 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); display: flex; border-left: 1px solid #ddd;
        }
        #adminUserList {
            width: 320px; background: #f8f9fa; border-right: 1px solid #ddd;
        }
        #adminUserList .header {
            background: #162447; color: white; padding: 18px 20px; font-weight: 600; font-size: 18px;
        }
        .user-item {
            padding: 16px 20px; border-bottom: 1px solid #eee; cursor: pointer; transition: 0.2s;
        }
        .user-item:hover, .user-item.active { background: #e6f7f2; }
        .user-item h6 { margin: 0; font-size: 15px; font-weight: 600; }
        .user-item p { margin: 4px 0 0; font-size: 13px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        #adminChatArea {
            flex: 1; display: flex; flex-direction: column; background: #f0f2f5;
        }
        #chatHeader {
            padding: 16px 20px; background: white; border-bottom: 1px solid #eee; font-weight: 600;
            display: flex; justify-content: space-between; align-items: center;
        }
        #adminChatMessages {
            flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px;
        }
        .msg {
            max-width: 70%; padding: 10px 14px; border-radius: 18px; line-height: 1.4; word-wrap: break-word;
        }
        .msg.user { background: white; align-self: flex-start; border: 1px solid #e5e5ea; border-bottom-left-radius: 4px; }
        .msg.admin { background: #162447; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
        .msg time { font-size: 11px; opacity: 0.8; margin-top: 4px; display: block; }

        #chatInputArea {
            padding: 15px; background: white; border-top: 1px solid #ddd; display: flex; gap: 10px;
        }
        #adminMsgInput {
            flex: 1; padding: 12px 16px; border: 1px solid #ddd; border-radius: 25px; outline: none; font-size: 15px;
        }
        #adminMsgInput:focus { border-color: #162447; }
        #sendBtn {
            background: #162447; color: white; border: none; width: 62px; height: 48px; border-radius: 50%; cursor: pointer; font-size: 18px;
        }
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
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Welcome to the Admin Dashboard</h5>
                <p class="card-text">Here you can manage vehicles, users, and rentals effectively.</p>
            </div>
        </div>

        <!-- <div class="card mt-3">
  <div class="card-body">
      <h5>Quick Actions</h5>
      <button class="btn btn-primary m-1" onclick="sendBotQuery('available cars')">Available Cars</button>
      <button class="btn btn-primary m-1" onclick="sendBotQuery('available bikes')">Available Bikes</button>
      <button class="btn btn-primary m-1" onclick="sendBotQuery('today rentals')">Today's Rentals</button>
<button class="btn btn-success m-1" onclick="openAdminChat()" style="position:relative;">
    Chat with Customers
    <span id="msgCount"
          style="
              background:red; color:white; font-size:12px; 
              padding:2px 6px; border-radius:50%;
              position:absolute; top:-8px; right:-8px; display:none;">
    </span>
</button>
  </div>
</div> -->


        <!-- Demand Prediction -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Demand Prediction (Next 7 Days)</h5>
                <?php if (empty($demandPredictions)): ?>
                    <p class="card-text">Not enough data to predict demand.</p>
                <?php else: ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Predicted Rentals</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demandPredictions as $prediction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prediction['date']); ?></td>
                                    <td><?php echo htmlspecialchars($prediction['day']); ?></td>
                                    <td><?php echo htmlspecialchars($prediction['predicted_count']); ?></td>
                                    <td class="<?php echo $prediction['high_demand'] === 'High Demand Expected' ? 'high-direct' : ''; ?>">
                                        <?php echo htmlspecialchars($prediction['high_demand']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fraud/Anomaly Detection -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Fraud/Anomaly Detection</h5>
                <?php if (empty($anomalies)): ?>
                    <p class="card-text">No suspicious activities detected.</p>
                <?php else: ?>
                    <?php foreach ($anomalies as $anomaly): ?>
                        <div class="alert alert-warning">
                            <?php echo htmlspecialchars($anomaly); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>function openAdminChat(){
    window.location.href = "admin_chat.php";
}</script>
<script>
function loadUnread() {
    fetch("count_unread.php")
        .then(res => res.text())
        .then(count => {
            let badge = document.getElementById("msgCount");
            if(count > 0){
                badge.style.display = "inline-block";
                badge.textContent = count;
            } else {
                badge.style.display = "none";
            }
        });
}

setInterval(loadUnread, 2000);
</script>
<!-- ADMIN CHAT PANEL (Side-by-side) -->
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
            <button onclick="closeAdminChat()" style="background:none; border:none; font-size:28px; cursor:pointer; color:#666;">×</button>
        </div>

        <div id="adminChatMessages">
            <div style="text-align:center; color:#999; margin-top:100px;">No conversation selected</div>
        </div>

        <div id="chatInputArea" style="display:none;">
            <input type="text" id="adminMsgInput" placeholder="Type a message..." onkeypress="if(event.key==='Enter') sendAdminReply()">
            <button id="sendBtn" onclick="sendAdminReply()">➤</button>
        </div>
    </div>
</div>

<!-- Toggle Button -->
<button onclick="openAdminChat()" id="chatToggleAdmin" style="position:fixed; top:20px; right:20px; background:#162447; color:white; padding:12px 24px; border:none; border-radius:50px; cursor:pointer; z-index:9998; box-shadow:0 4px 15px rgba(19, 1, 100, 0.4); font-weight:600;">
    Chat (<span id="adminUnreadCount">0</span>)
</button>

<script>
let currentChatUserId = null;

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
            document.getElementById("userListContainer").innerHTML = html || "<div style='padding:40px; text-align:center; color:#999;'>No active chats</div>";
        });
}

function openUserChat(uid, name) {
    currentChatUserId = uid;
    document.getElementById("activeUserName").textContent = name;
    document.getElementById("chatInputArea").style.display = "flex";
    loadMessages(uid);
    loadUserList(); // Refresh active state
}

function loadMessages(uid) {
    fetch("admin_chat_load.php?uid=" + uid + "&t=" + Date.now())
        .then(r => r.text())
        .then(html => {
            document.getElementById("adminChatMessages").innerHTML = html;
            const div = document.getElementById("adminChatMessages");
            div.scrollTop = div.scrollHeight;
        });
}

function sendAdminReply() {
    const input = document.getElementById("adminMsgInput");
    const msg = input.value.trim();
    if (!msg || !currentChatUserId) return;

    fetch("admin_chat.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "message=" + encodeURIComponent(msg) + "&user_id=" + currentChatUserId
    }).then(() => {
        input.value = "";
        loadMessages(currentChatUserId);
    });
}

// Auto refresh
setInterval(() => {
    if (document.getElementById("adminChatPanel").style.right === "0px") {
        loadUserList();
        if (currentChatUserId) loadMessages(currentChatUserId);
    }
}, 500);

loadUserList();
</script>
</body>
</html>