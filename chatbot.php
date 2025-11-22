<?php
require 'database.php';

$msg = strtolower(trim($_GET['msg'] ?? ''));

// Clean input
$msg = preg_replace('/[^a-zA-Z0-9\s\?\.]/', '', $msg);

// === 1. Greeting ===
if (preg_match('/\b(hi|hello|hey|namaste|good morning|good afternoon|good evening)\b/', $msg)) {
    echo "Hello! Welcome to Rental Pass 👋<br>How can I assist you today?";
    exit;
}

// === 2. Available Bikes / Two-Wheelers ===
if (preg_match('/\b(available bikes?|two wheelers?|bikes?|scooty|motorcycle)\b/', $msg)) {
    $q = $conn->query("SELECT name, model, price FROM two_wheeler WHERE available = 1 LIMIT 8");
    if ($q->num_rows == 0) {
        echo "Sorry, no two-wheelers are available right now. Please check back later!";
    } else {
        echo "<strong>Available Two-Wheelers:</strong><br><br>";
        while ($r = $q->fetch_assoc()) {
            echo "• <strong>{$r['name']}</strong> ({$r['model']}) — Rs. {$r['price']}/day<br>";
        }
        echo "<br>Want to rent one? Just click <strong>Rent Now</strong> on any vehicle!";
    }
    exit;
}

// === 3. Available Cars / Four-Wheelers ===
if (preg_match('/\b(available cars?|four wheelers?|car|sedan|suv)\b/', $msg)) {
    $q = $conn->query("SELECT name, model, price FROM four_wheeler WHERE available = 1 LIMIT 8");
    if ($q->num_rows == 0) {
        echo "Sorry, no cars are available at the moment. Please check back soon!";
    } else {
        echo "<strong>Available Four-Wheelers:</strong><br><br>";
        while ($r = $q->fetch_assoc()) {
            echo "• <strong>{$r['name']}</strong> ({$r['model']}) — Rs. {$r['price']}/day<br>";
        }
        echo "<br>Ready to book? Just click <strong>Rent Now</strong>!";
    }
    exit;
}

// === 4. Price Inquiry (e.g. "price of Activa", "how much is Splendor") ===
if (preg_match('/price of (.+)/i', $msg, $match) || preg_match('/how much is (.+)/i', $msg, $match)) {
    $keyword = trim($match[1]);
    $keyword = $conn->real_escape_string($keyword);

    $found = false;

    // Search Two-Wheelers
    $q1 = $conn->query("SELECT name, model, price FROM two_wheeler WHERE name LIKE '%$keyword%' OR model LIKE '%$keyword%'");
    if ($q1->num_rows > 0) {
        $r = $q1->fetch_assoc();
        echo "<strong>{$r['name']} ({$r['model']})</strong><br>Price: <strong>Rs. {$r['price']}/day</strong>";
        $found = true;
    }

    // Search Four-Wheelers
    if (!$found) {
        $q2 = $conn->query("SELECT name, model, price FROM four_wheeler WHERE name LIKE '%$keyword%' OR model LIKE '%$keyword%'");
        if ($q2->num_rows > 0) {
            $r = $q2->fetch_assoc();
            echo "<strong>{$r['name']} ({$r['model']})</strong><br>Price: <strong>Rs. {$r['price']}/day</strong>";
            $found = true;
        }
    }

    if (!$found) {
        echo "Sorry, I couldn't find a vehicle named '$keyword'.<br>Try asking: <i>'available bikes'</i> or <i>'price of Honda Activa'</i>";
    }
    exit;
}

// === 5. How to Rent? ===
if (preg_match('/\b(how to rent|how do i rent|rent process|booking|book vehicle)\b/', $msg)) {
    echo "<strong>How to Rent a Vehicle:</strong><br><br>
    1. Browse available bikes or cars<br>
    2. Click <strong>Rent Now</strong> on your favorite vehicle<br>
    3. Log in or register<br>
    4. Choose rental dates<br>
    5. Complete payment<br>
    6. Pick up your vehicle!<br><br>
    Need help? Click <strong>Chat with Representative</strong> for instant support!";
    exit;
}

// === 6. Login / Account Related ===
if (preg_match('/\b(login|sign in|my account|profile|logout)\b/', $msg)) {
    echo "To access your account, please <a href='login.php' style='color:#00a884;font-weight:bold;'>click here to log in</a>.<br><br>
    New user? <a href='register.php' style='color:#00a884;font-weight:bold;'>Register here</a> — it takes just 30 seconds!";
    exit;
}

// === 7. Contact / Support ===
if (preg_match('/\b(contact|support|help|representative|human|agent|talk to someone)\b/', $msg)) {
    echo "You can talk to a real human anytime!<br><br>
    Just click the button below:<br>
    <button onclick=\"window.location.href='login.php'\" style='background:#162447;color:white;padding:12px 20px;border:none;border-radius:25px;margin-top:10px;cursor:pointer;font-weight:bold;'>
    Chat with Representative
    </button>";
    exit;
}

// === 8. Thank You / Bye ===
if (preg_match('/\b(thank you|thanks|bye|goodbye|ok)\b/', $msg)) {
    $replies = [
        "You're welcome! Happy riding!",
        "Anytime! Enjoy your trip!",
        "Glad I could help! See you soon!",
        "Safe travels!"
    ];
    echo $replies[array_rand($replies)];
    exit;
}

// === DEFAULT: Help Menu ===
echo "I'm here to help! You can ask me:<br><br>
• <strong>available bikes</strong><br>
• <strong>available cars</strong><br>
• <strong>price of Activa</strong><br>
• <strong>how to rent</strong><br>
• <strong>login</strong> or <strong>contact support</strong><br><br>
Or just click any button below!";
?>