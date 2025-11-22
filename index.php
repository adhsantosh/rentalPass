<?php
require 'database.php';
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT * FROM two_wheeler WHERE available = 1 ORDER BY TWID DESC LIMIT 12");
$stmt->execute();
$twoWheelers = $stmt->get_result();

$stmt2 = $conn->prepare("SELECT * FROM four_wheeler WHERE available = 1 ORDER BY FWID DESC LIMIT 12");
$stmt2->execute();
$fourWheelers = $stmt2->get_result();
?>

<?php require_once 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Rental Pass - Premium Bike & Car Rental in Nepal</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    :root {
      --primary: #162447;
      --accent: #fddb3a;
      --light: #f6f3f0;
    }
    body {
      font-family: 'Inter', sans-serif;
      background: var(--light);
      color: #333;
      margin: 0;
    }

    /* HERO - Himalayan Adventure */
    .hero {
      height: 100vh;
      background: linear-gradient(rgba(22,36,71,0.82), rgba(22,36,71,0.88)),
                  url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat fixed;
      color: white;
      display: flex;
      align-items: center;
      text-align: center;
      position: relative;
    }
    .hero h1 {
      font-size: 4.8rem;
      font-weight: 800;
      text-shadow: 0 4px 20px rgba(0,0,0,0.7);
      margin-bottom: 20px;
    }
    .hero p {
      font-size: 1.5rem;
      max-width: 800px;
      margin: 0 auto 40px;
      opacity: 0.95;
    }
    .hero-btn {
      background: var(--accent);
      color: var(--primary);
      padding: 18px 48px;
      border-radius: 50px;
      font-size: 1.3rem;
      font-weight: 700;
      text-decoration: none;
      display: inline-block;
      box-shadow: 0 10px 30px rgba(253,219,58,0.5);
      transition: 0.4s;
    }
    .hero-btn:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(253,219,58,0.7);
    }

    /* Section */
    .section-pad { padding: 100px 20px; }
    .section-title {
      text-align: center;
      font-size: 3rem;
      color: var(--primary);
      font-weight: 800;
      margin-bottom: 20px;
    }
    .section-subtitle {
      text-align: center;
      font-size: 1.3rem;
      color: #555;
      max-width: 800px;
      margin: 0 auto 60px;
    }

    /* Vehicle Grid */
    .vehicles-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 35px;
      max-width: 1400px;
      margin: 0 auto;
    }
    .vehicle-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 12px 35px rgba(0,0,0,0.12);
      transition: all 0.4s;
    }
    .vehicle-card:hover {
      transform: translateY(-15px);
      box-shadow: 0 25px 50px rgba(0,0,0,0.2);
    }
    .vehicle-card img {
      width: 100%;
      height: 230px;
      object-fit: cover;
    }
    .vehicle-info {
      padding: 28px;
      text-align: center;
    }
    .vehicle-info h3 {
      font-size: 1.6rem;
      color: var(--primary);
      margin-bottom: 10px;
    }
    .price {
      font-size: 2rem;
      color: var(--primary);
      font-weight: 800;
      margin: 15px 0;
    }
    .btn-rent {
      background: #dfc021ff;
      color: white;
      border: none;
      padding: 14px 36px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1.1rem;
      cursor: pointer;
    }
    .btn-rent:hover {
      background: var(--accent);
    }

    /* Desert 4x4 Section */
    .desert-section {
      background: linear-gradient(rgba(0,0,0,0.78), rgba(0,0,0,0.85)),
                  url('https://images.unsplash.com/photo-1544636331-80100aa9afbd?q=80&w=2070&auto=format&fit=crop') center/cover fixed;
      color: white;
      padding: 140px 20px;
      text-align: center;
    }
    .desert-section h2 {
      font-size: 3.8rem;
      margin-bottom: 20px;
    }

    /* Trust Badges */
    .trust-badges {
      background: white;
      padding: 80px 20px;
      text-align: center;
    }
    .badges {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 60px;
      max-width: 1000px;
      margin: 40px auto 0;
      font-size: 1.4rem;
      font-weight: 600;
      color: var(--primary);
    }
    .badges i { font-size: 3rem; color: var(--accent); margin-bottom: 15px; display: block; }

    /* Testimonials */
    .testimonials {
      background: var(--primary);
      color: white;
      padding: 100px 20px;
      text-align: center;
    }
    .testimonial {
      max-width: 900px;
      margin: 0 auto;
      font-size: 1.5rem;
      line-height: 1.8;
      font-style: italic;
    }
    .author { margin-top: 25px; font-weight: 600; font-size: 1.3rem; }

    @media (max-width: 768px) {
      .hero h1 { font-size: 3rem; }
      .hero p { font-size: 1.2rem; }
      .section-title { font-size: 2.4rem; }
      .desert-section h2 { font-size: 2.8rem; }
    }
  </style>
</head>
<body>

<!-- HERO -->
<section class="hero">
  <div style="max-width:1000px;margin:0 auto;padding:0 20px;">
    <h1>Conquer the Himalayas<br>& the Desert</h1>
    <p>Premium Royal Enfields for RARA • 4x4 SUVs for Mustang & Tilicho</p>
    <a href="#bikes" class="hero-btn">Start Your Adventure</a>
  </div>
</section>

<!-- BIKES -->
<section class="section-pad" id="bikes">
  <h2 class="section-title">Ride on Himalayas</h2>
  <p class="section-subtitle">Royal Enfield Himalayan • KTM Adventure • BMW GS — Ready for Mustang, Tilicho, and beyond</p>
  <div class="vehicles-grid">
    <?php while ($bike = $twoWheelers->fetch_assoc()): ?>
      <div class="vehicle-card">
        <img src="<?= htmlspecialchars($bike['photo']) ?>" alt="<?= $bike['name'] ?>">
        <div class="vehicle-info">
          <h3><?= htmlspecialchars($bike['name']) ?></h3>
          <p><?= htmlspecialchars($bike['model']) ?></p>
          <div class="price">₹<?= $bike['price'] ?>/day</div>
          <button class="btn-rent" onclick="handleRent(<?= $bike['TWID'] ?>, 'two')">Rent Now</button>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<!-- DESERT 4x4 -->
<section class="desert-section">
  <h2>Drive the Thar Desert & Rann of Kutch</h2>
  <p style="font-size:1.5rem;max-width:800px;margin:30px auto;">
    Mahindra Thar • Toyota Fortuner • Force Gurkha<br>
    Fully equipped with roof racks, off-road tires & recovery gear
  </p>
  <a href="#cars" style="background:var(--accent);color:var(--primary);padding:18px 50px;border-radius:50px;font-size:1.3rem;font-weight:700;">
    View All 4x4s
  </a>
</section>

<!-- CARS -->
<section class="section-pad" id="cars">
  <h2 class="section-title">4x4 SUVs for Extreme Adventures</h2>
  <div class="vehicles-grid">
    <?php while ($car = $fourWheelers->fetch_assoc()): ?>
      <div class="vehicle-card">
        <img src="<?= htmlspecialchars($car['photo']) ?>" alt="<?= $car['name'] ?>">
        <div class="vehicle-info">
          <h3><?= htmlspecialchars($car['name']) ?></h3>
          <p><?= htmlspecialchars($car['model']) ?></p>
          <div class="price">₹<?= $car['price'] ?>/day</div>
          <button class="btn-rent" onclick="handleRent(<?= $car['FWID'] ?>, 'four')">Rent Now</button>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<!-- TRUST BADGES -->
<section class="trust-badges">
  <h2 class="section-title">Why Riders Trust Us</h2>
  <div class="badges">
    <div><i class="fas fa-shield-alt"></i> 100% Verified Vehicles</div>
    <div><i class="fas fa-headset"></i> 24/7 Support</div>
    <div><i class="fas fa-tools"></i> Free Gear Included</div>
    <div><i class="fas fa-route"></i> Curated Routes</div>
  </div>
</section>

<!-- TESTIMONIAL -->
<section class="testimonials">
  <div class="testimonial">
    "Rented a Himalayan for 15 days in Mustang — bike was flawless, support team helped us at 3 AM when we got stuck. Best rental service in Nepal!"
    <div class="author">— Gorkhali Riders, Nepal</div>
  </div>
</section>

<!-- YOUR ORIGINAL CHAT — 100% UNTOUCHED -->
<div id="chatbot-icon" onclick="toggleChatbot()">
  <span class="chat-icon">💬</span>
  <span class="chat-label">Chat with us  ➤</span>
</div>
<div id="chatbot-box">
  <div id="chat-header">
    <div style="display:flex; align-items:center; gap:12px;">
      <div style="width:40px;height:40px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;">R</div>
      <div>
        <div>Rental Pass Assistant</div>
        <small>Typically replies instantly</small>
      </div>
    </div>
    <button onclick="toggleChatbot()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">×</button>
  </div>

  <div id="chat-body">
    <div class="bot-msg">Welcome to Rental Pass!</div>
    <div class="bot-msg">How can I assist you today?</div>
    <div style="margin:16px 0; display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-start;">
      <button class="quick-btn" onclick="quickSend('available bikes')">Available Bikes</button>
      <button class="quick-btn" onclick="quickSend('available cars')">Available Cars</button>
      <button class="quick-btn" onclick="quickSend('price of Activa')">Price Inquiry</button>
      <button class="quick-btn" onclick="quickSend('how to rent')">How to Rent?</button>
      <button class="quick-btn live-chat-btn" onclick="window.location.href='login.php'">
        Chat with Representative
      </button>
    </div>
  </div>

  <div id="chat-input-area">
    <input type="text" id="chat-input" placeholder="Type your message..." autocomplete="off">
    <button id="chat-send-btn" onclick="sendMessage()">➤</button>
  </div>
</div>

<!-- YOUR ORIGINAL CHAT STYLES & SCRIPT — NOT A SINGLE PIXEL CHANGED -->
<style>
  /* === YOUR ORIGINAL CHAT STYLES — 100% PRESERVED === */
  #chatbot-icon {
  position: fixed;
  bottom: 25px;
  right: 25px;
  background: #162447; /* primary dark */
  color: #fddb3a; /* mustard accent */
  width: 66px;
  height: 66px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  box-shadow: 0 10px 30px rgba(22,36,71,0.5);
  cursor: pointer;
  z-index: 9999;
  font-weight: 600;
}

/* Chat label always visible */
#chatbot-icon .chat-label {
  position: absolute;
  right: 75px; /* space to the left of the icon */
  background: #162447;
  color: #fddb3a;
  padding: 8px 14px;
  border-radius: 25px;
  font-size: 14px;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Optional small arrow pointing to icon */
#chatbot-icon .chat-label::after {
  content: "";
  position: absolute;
  top: 50%;
  right: -6px;
  transform: translateY(-50%);
  border-width: 6px;
  border-style: solid;
  border-color: transparent transparent transparent #162447;
}

  #chatbot-box {
    position: fixed;
    bottom: 100px;
    right: 20px;
    width: 380px;
    max-width: 95vw;
    background: white;
    border-radius: 18px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.25);
    overflow: hidden;
    display: none;
    flex-direction: column;
    z-index: 9999;
    font-family: 'Inter', sans-serif;
  }
  #chat-header { background: #162447; color: white; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-weight: 600; }
  #chat-body { flex: 1; max-height: 480px; overflow-y: auto; padding: 16px; background: #f6f3f0; display: flex; flex-direction: column; gap: 12px; }
  .bot-msg { background: #ffffff; align-self: flex-start; color: #111; border-radius: 18px 18px 18px 4px; padding: 12px 16px; }
  .user-msg { background: #162447; color: #f4f4f4ff; align-self: flex-end; border-radius: 18px 18px 4px 18px; padding: 12px 16px; }
  .quick-btn { background: #fddb3a; color: #162447; padding: 10px 16px; border-radius: 25px; font-size: 14px; cursor: pointer; margin: 6px 4px; transition: 0.2s; }
  .quick-btn:hover { background: #e6c843; transform: translateY(-2px); }
  .live-chat-btn { background: #162447 !important; color: white !important; }
  #chat-input-area { display: flex; padding: 14px 16px; gap: 12px; background: #f6f3f0; }
  #chat-input { flex: 1; padding: 14px 20px; border: 1.5px solid #ddd; border-radius: 30px; outline: none; font-size: 15px; }
  #chat-send-btn { background: #fddb3a; color: #162447; border: none; width: 52px; height: 52px; border-radius: 50%; cursor: pointer; font-size: 18px; box-shadow: 0 4px 12px rgba(22,36,71,0.3); }
  #chat-send-btn:hover { background: #e6c843; }
</style>

<script>
function handleRent(id, type) {
  <?php if ($isLoggedIn): ?>
    window.location.href = `rent_vehicle.php?type=${type === 'two' ? 'twoWheeler' : 'fourWheeler'}&id=${id}`;
  <?php else: ?>
    alert("Please log in to rent a vehicle.");
    window.location.href = "login.php";
  <?php endif; ?>
}

function toggleChatbot() {
  const box = document.getElementById("chatbot-box");
  box.style.display = box.style.display === "flex" ? "none" : "flex";
}

function quickSend(text) {
  document.getElementById("chat-input").value = text;
  sendMessage();
}

function sendMessage() {
  const input = document.getElementById("chat-input");
  const msg = input.value.trim();
  if (!msg) return;

  const chatBody = document.getElementById("chat-body");
  chatBody.innerHTML += `<div class="user-msg">${msg}</div>`;
  scrollToBottom();

  fetch("chatbot.php?msg=" + encodeURIComponent(msg))
    .then(r => r.text())
    .then(reply => {
      chatBody.innerHTML += `<div class="bot-msg">${reply}</div>`;
      scrollToBottom();
    });

  input.value = "";
}

function scrollToBottom() {
  const body = document.getElementById("chat-body");
  body.scrollTop = body.scrollHeight;
}

document.getElementById("chat-input")?.addEventListener("keypress", e => {
  if (e.key === "Enter") sendMessage();
});
</script>
<?php include 'footer.php'; ?>


</body>
</html>