<?php
session_start();
require 'database.php';
require 'recommendation_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user name
$userName = 'User';
$stmt = $conn->prepare("SELECT name FROM users WHERE UID=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $userName = $row['name'];
}
$stmt->close();

// Fetch user's rentals
$rentalQuery = "
    SELECT r.rental_id, r.rental_start, r.rental_end, r.status,
           tw.name AS tw_name, tw.model AS tw_model, tw.price AS tw_price,
           fw.name AS fw_name, fw.model AS fw_model, fw.price AS fw_price
    FROM rentals r
    LEFT JOIN two_wheeler tw ON r.TWID = tw.TWID
    LEFT JOIN four_wheeler fw ON r.FWID = fw.FWID
    WHERE r.UID = ?
    ORDER BY r.rental_start DESC
";
$stmt = $conn->prepare($rentalQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$rentalResult = $stmt->get_result();
$userRentals = [];
while ($row = $rentalResult->fetch_assoc()) {
    $userRentals[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: #f4f7f6; margin:0; color:#333;
            padding-bottom: 100px; /* Prevent content overlap with chat */
        }
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2c3e50; color:#fff; flex-shrink:0; }
        .sidebar h3 { padding:20px; margin:0; background:#233140; text-align:center; }
        .sidebar .nav-link { color:#ecf0f1; padding:15px 20px; text-decoration:none; display:block; border-bottom:1px solid #34495e; }
        .sidebar .nav-link:hover { background:#34495e; }
        .main-content { flex-grow:1; padding:20px 40px; }
        .container { max-width:1200px; margin:0 auto; background:#fff; padding:30px 40px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.06); }
        .header h1 { margin:0; font-size:28px; color:#2c3e50; }
        h2 { font-size:22px; color:#2c3e50; margin-top:40px; }
        table { width:100%; border-collapse:collapse; margin-top:15px; }
        table th, table td { border:1px solid #ddd; padding:12px; text-align:left; }
        table th { background:#f8f9fa; font-weight:600; }
        #recommendations-container { display:flex; flex-wrap:wrap; gap:20px; margin-top:20px; }
        .vehicle-card { border:1px solid #ddd; border-radius:12px; width:260px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08); transition:0.3s; }
        .vehicle-card:hover { transform:translateY(-8px); box-shadow:0 12px 25px rgba(0,0,0,0.15); }
        .vehicle-card img { width:100%; height:160px; object-fit:cover; }
        .vehicle-card h3 { margin:12px 0 6px; font-size:19px; text-align:center; }
        .vehicle-card p { margin:0 0 12px; color:#666; text-align:center; }
        .rent-btn { display:block; margin:0 auto 12px; background:#3498db; color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600; }
        .rent-btn:hover { background:#2980b9; }

        /* === WHATSAPP STYLE CHAT PANEL === */
        #userChatPanel {
            position:fixed; bottom:0; right:20px; width:400px; height:620px;
            background:white; border-radius:18px 18px 0 0; box-shadow:0 15px 50px rgba(0,0,0,0.3);
            z-index:9999; overflow:hidden; display:none; flex-direction:column;
            font-family:'Segoe UI', Tahoma, sans-serif; border:1px solid #ddd;
        }
        #userChatPanel .header {
            background:#162447; color:white; padding:14px 20px; font-weight:600; font-size:18px;
            display:flex; justify-content:space-between; align-items:center;
        }
        #userChatPanel .header .info { display:flex; align-items:center; gap:12px; }
        #userChatPanel .header .info img {
            width:44px; height:44px; border-radius:50%; background:#fff; padding:4px;
        }
       /* Chat container */
#chatMessages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #f5f2ee; /* soft background */
    background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 50c0-13.807 11.193-25 25-25s25 11.193 25 25-11.193 25-25 25S0 63.807 0 50zm50 0c0-13.807 11.193-25 25-25s25 11.193 25 25-11.193 25-25 25-25-11.193-25-25z' fill='%23d9d2c9' fill-opacity='0.03'/%3E%3C/svg%3E");
}

/* Common message styling */
#chatMessages .msg {
    max-width: 70%;
    padding: 10px 14px;
    border-radius: 18px;
    line-height: 1.5;
    word-wrap: break-word;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    animation: fadeIn 0.4s ease;
    position: relative;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* User message */
#chatMessages .user {
    background: #10145dff;
    color: white;
    align-self: flex-end;
    border-bottom-right-radius: 6px;
    border-bottom-left-radius: 20px;
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
    padding: 10px 14px;

}

/* Admin message */
#chatMessages .admin {
    background: #ffffff;
    color: #111;
    align-self: flex-start;
    border: 1px solid #e0e0e0;
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 20px;
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
    padding: 10px 14px;
}

/* Timestamp */
#chatMessages .time {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 4px;
    display: block;
    text-align: right;
    color: #cdcdcdff;
}

/* Fade-in animation */
@keyframes fadeIn {
    0% { opacity: 0; transform: translateY(5px); }
    100% { opacity: 1; transform: translateY(0); }
}

        #chatInputArea {
            padding:14px 16px; background:white; border-top:1px solid #eee;
            display:flex; gap:10px; align-items:center;
        }
        #chatInput {
            flex:1; padding:14px 20px; border-radius:30px; border:1px solid #ddd;
            outline:none; font-size:15px;
        }
        #chatInput:focus { border-color:#162447; }
        #sendBtn {
            background:#162447; color:white; border:none; width:72px; height:52px;
            border-radius:50%; cursor:pointer; font-size:20px;
            box-shadow:0 3px 10px rgba(0, 17, 168, 0.4);
        }
        #chatToggleBtn {
            position:fixed; bottom:25px; right:25px; background:#162447;
            width:66px; height:66px; border-radius:50%; display:flex;
            align-items:center; justify-content:center; box-shadow:0 10px 30px rgba(12, 10, 125, 0.5);
            cursor:pointer; z-index:9998; transition:0.3s;
        }
        #chatToggleBtn:hover { transform:scale(1.1); }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar { position:fixed; top:0; left:-250px; height:100vh; z-index:9997; transition:0.3s; }
            .sidebar.open { left:0; }
            .main-content { margin-left:0; padding:15px; }
            #userChatPanel { width:100%; right:0; left:0; border-radius:18px 18px 0 0; height:80vh; }
            #chatToggleBtn { bottom:20px; right:20px; width:60px; height:60px; }
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <div class="sidebar">
        <h3>User Menu</h3>
        <nav>
            <a class="nav-link" href="user_dashboard.php">Dashboard</a>
            <a class="nav-link" href="view_twoWheeler.php">View Bikes</a>
            <a class="nav-link" href="view_fourWheeler.php">View Cars</a>
            <a class="nav-link" href="your_rentals.php">Your Rentals</a>
            <a class="nav-link" href="edit_profile.php">Edit Profile</a>
            <a class="nav-link" href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($userName); ?>!</h1>
            </div>

            <h2>Your Rentals</h2>
            <table>
                <thead><tr>
                    <th>Vehicle Name</th><th>Model</th><th>Price</th>
                    <th>Rental Start</th><th>Rental End</th><th>Status</th><th>Action</th>
                </tr></thead>
                <tbody>
                <?php foreach ($userRentals as $r): 
                    $name = $r['tw_name'] ?? $r['fw_name'];
                    $model = $r['tw_model'] ?? $r['fw_model'];
                    $price = $r['tw_price'] ?? $r['fw_price'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($name); ?></td>
                        <td><?php echo htmlspecialchars($model); ?></td>
                        <td><?php echo htmlspecialchars($price); ?></td>
                        <td><?php echo $r['rental_start'] ?? '-'; ?></td>
                        <td><?php echo $r['rental_end'] ?? '-'; ?></td>
                        <td><?php echo ucfirst($r['status']); ?></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Recommended For You</h2>
            <div id="recommendations-container">
                <p class="loading">Loading recommendations...</p>
            </div>
        </div>
    </div>
</div>

<!-- WHATSAPP STYLE CHAT PANEL -->
<div id="userChatPanel">
    <div class="header">
        <div class="info">
            <!-- <div style="width:44px;height:44px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;"> -->
                <span style="font-size:20px;">Support</span>
            </div>
            <div>
                <!-- <div>Rental Support</div> -->
                <small>Typically replies instantly</small>
            </div>
        <!-- </div> -->
        <button onclick="closeUserChat()" style="background:none;border:none;color:white;font-size:28px;cursor:pointer;">×</button>
    </div>

    <div id="chatMessages">
        <div style="text-align:center;color:#999;margin-top:30px;">
            <div style="background:white;padding:10px 20px;border-radius:25px;display:inline-block;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                Welcome! How can we help you today?
            </div>
        </div>
    </div>

    <div id="chatInputArea">
        <input type="text" id="chatInput" placeholder="Type a message..." autocomplete="off"
               onkeypress="if(event.key==='Enter') sendUserMsg()">
        <button id="sendBtn" onclick="sendUserMsg()">➤</button>
    </div>
</div>

<!-- Floating Chat Button -->
<div id="chatToggleBtn" onclick="toggleUserChat()">
    <span style="color:white;font-size:26px;">💬</span>
</div>

<script>
// Toggle Chat
function toggleUserChat() {
    const panel = document.getElementById("userChatPanel");
    panel.style.display = panel.style.display === "flex" ? "none" : "flex";
    if (panel.style.display === "flex") {
        loadMessages();
        document.getElementById("chatInput").focus();
    }
}
function closeUserChat() {
    document.getElementById("userChatPanel").style.display = "none";
}

// Send Message
function sendUserMsg() {
    const input = document.getElementById("chatInput");
    const msg = input.value.trim();
    if (!msg) return;

    fetch("user_chat.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "message=" + encodeURIComponent(msg)
    }).then(() => {
        input.value = "";
        loadMessages();
    });
}

// Load Messages
function loadMessages() {
    fetch("user_chat.php?t=" + Date.now())
        .then(r => r.text())
        .then(html => {
            const div = document.getElementById("chatMessages");
            if (div.innerHTML.trim() !== html.trim()) {
                div.innerHTML = html;
                div.scrollTop = div.scrollHeight;
            }
        });
}

// Auto-refresh when open
setInterval(() => {
    if (document.getElementById("userChatPanel").style.display === "flex") {
        loadMessages();
    }
}, 500);

// Load Recommendations
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('recommendations-container');
    fetch('recommendations.php')
        .then(res => res.json())
        .then(data => {
            container.innerHTML = '';
            if (!data) { container.innerHTML = '<p>No recommendations yet.</p>'; return; }

            const types = ['two_wheeler', 'four_wheeler'];
            types.forEach(type => {
                (data[type].hybrid || []).forEach(v => createCard(v, container));
                (data[type].cf || []).forEach(v => {
                    v.isCF = true;
                    createCard(v, container);
                });
            });

            function createCard(v, parent) {
                const card = document.createElement('div');
                card.className = 'vehicle-card';
                if (v.isCF) card.style.backgroundColor = '#fff9f0';
                const photo = v.photo || 'path/to/default.png';
                card.innerHTML = `
                    <img src="${photo}" alt="${v.brand}">
                    <h3>${v.brand} ${v.model || ''}</h3>
                    <p><strong>Price:</strong> Rs. ${v.price}/day</p>
                    <a href="rent_vehicle.php?type=${v.type==='four_wheeler'?'fourWheeler':'twoWheeler'}&id=${v.id}" class="rent-btn">Rent Now</a>
                `;
                parent.appendChild(card);
            }

            if (container.children.length === 0) {
                container.innerHTML = '<p>No recommendations yet.</p>';
            }
        })
        .catch(() => container.innerHTML = '<p>Error loading recommendations.</p>');
});
</script>

</body>
</html>