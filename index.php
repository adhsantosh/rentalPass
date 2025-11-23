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
      --navy: #162447;
      --mustard: #FDDB3A;
      --light: #f8f7f5;
      --gray: #6c757d;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Inter', sans-serif;
      background: var(--light);
      color: #333;
      line-height: 1.6;
    }

    /* HERO — EPIC HIMALAYAN VIBE */
    .hero {
      height: 100vh;
      min-height: 700px;
      background: linear-gradient(135deg, rgba(22,36,71,0.92) 0%, rgba(22,36,71,0.85) 100%),
                  url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat fixed;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      position: relative;
      overflow: hidden;
    }
    .hero::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: linear-gradient(45deg, transparent 48%, var(--mustard) 49%, var(--mustard) 51%, transparent 52%);
      opacity: 0.1;
      animation: shimmer 8s infinite;
    }
    @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

    .hero-content {
      max-width: 1100px;
      padding: 0 20px;
      z-index: 2;
    }
    .hero h1 {
      font-size: 5.5rem;
      font-weight: 900;
      letter-spacing: -2px;
      margin-bottom: 20px;
      text-shadow: 0 10px 30px rgba(0,0,0,0.6);
    }
    .hero p {
      font-size: 1.8rem;
      font-weight: 400;
      margin-bottom: 40px;
      opacity: 0.95;
    }
    .hero-cta {
      background: var(--mustard);
      color: var(--navy);
      padding: 20px 56px;
      font-size: 1.4rem;
      font-weight: 800;
      border-radius: 60px;
      text-decoration: none;
      display: inline-block;
      box-shadow: 0 15px 40px rgba(253,219,58,0.6);
      transition: all 0.4s;
    }
    .hero-cta:hover {
      transform: translateY(-10px);
      box-shadow: 0 25px 60px rgba(253,219,58,0.8);
    }

    /* SECTION STYLING */
    .section {
      padding: 120px 20px;
      text-align: center;
    }
    .section-title {
      font-size: 3.8rem;
      font-weight: 900;
      color: var(--navy);
      margin-bottom: 20px;
    }
    .section-subtitle {
      font-size: 1.4rem;
      color: var(--gray);
      max-width: 800px;
      margin: 0 auto 70px;
    }

    /* VEHICLE GRID */
    .vehicles-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
      gap: 40px;
      max-width: 1500px;
      margin: 0 auto;
    }
    .vehicle-card {
      background: white;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 15px 40px rgba(0,0,0,0.1);
      transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    .vehicle-card:hover {
      transform: translateY(-20px);
      box-shadow: 0 30px 70px rgba(22,36,71,0.2);
    }
    .vehicle-card img {
      width: 100%;
      height: 260px;
      object-fit: cover;
      transition: transform 0.6s;
    }
    .vehicle-card:hover img { transform: scale(1.08); }
    .vehicle-info {
      padding: 32px;
    }
    .vehicle-info h3 {
      font-size: 1.8rem;
      color: var(--navy);
      margin-bottom: 8px;
    }
    .vehicle-info p {
      color: var(--gray);
      font-size: 1.1rem;
      margin-bottom: 16px;
    }
    .price {
      font-size: 2.4rem;
      font-weight: 900;
      color: var(--navy);
      margin: 16px 0;
    }
    .btn-rent {
      background: var(--navy);
      color: white;
      border: none;
      padding: 16px 40px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 1.1rem;
      cursor: pointer;
      transition: 0.3s;
    }
    .btn-rent:hover {
      background: var(--mustard);
      color: var(--navy);
      transform: scale(1.08);
    }

    /* DESERT SECTION */
    .desert-hero {
      background: linear-gradient(rgba(22,36,71,0.92), rgba(22,36,71,0.95)),
                  url('https://images.unsplash.com/photo-1509316785289-025f5b846ef8?q=80&w=2069') center/cover fixed;
      padding: 160px 20px;
      color: white;
      text-align: center;
    }
    .desert-hero h2 {
      font-size: 4.5rem;
      font-weight: 900;
      margin-bottom: 24px;
    }

    /* TRUST & TESTIMONIALS */
    .trust {
      background: white;
      padding: 100px 20px;
    }
    .trust-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 50px;
      max-width: 1200px;
      margin: 0 auto;
    }
    .trust-item i {
      font-size: 4rem;
      color: var(--mustard);
      margin-bottom: 20px;
    }
    .trust-item h4 {
      font-size: 1.5rem;
      color: var(--navy);
      margin-bottom: 12px;
    }

    .testimonial {
      background: var(--navy);
      color: white;
      padding: 120px 20px;
      font-size: 1.8rem;
      line-height: 1.8;
    }
    .testimonial-quote {
      max-width: 900px;
      margin: 0 auto;
      font-style: italic;
      position: relative;
    }
    .testimonial-quote::before {
      content: '“';
      font-size: 900 120px/1 'Inter';
      opacity: 0.15;
      position: absolute;
      top: -40px;
      left: -20px;
    }
    .author {
      margin-top: 30px;
      font-weight: 700;
      font-size: 1.4rem;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .hero h1 { font-size: 3.5rem; }
      .hero p { font-size: 1.4rem; }
      .section-title { font-size: 2.8rem; }
      .desert-hero h2 { font-size: 3rem; }
      .testimonial { font-size: 1.4rem; padding: 80px 20px; }
    }
  </style>
</head>
<body>
<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <h1>Conquer Peaks<br>& Deserts</h1>
    <p>Your Journey, Our Wheels • Easy Rentals, Endless Rides</p>
    <a href="#bikes" class="hero-cta">Begin Your Journey</a>
  </div>
</section>

<!-- BIKES -->
<section class="section" id="bikes">
  <h2 class="section-title">Two Wheelers for the Roof of the World</h2>
  <p class="section-subtitle">Himalayan • Classic 350 • Meteor • Interceptor — Built for altitude, born for adventure</p>
  <div class="vehicles-grid">
    <?php while ($bike = $twoWheelers->fetch_assoc()): ?>
      <div class="vehicle-card">
        <img src="<?= htmlspecialchars($bike['photo']) ?>" alt="<?= $bike['name'] ?>">
        <div class="vehicle-info">
          <h3><?= htmlspecialchars($bike['name']) ?></h3>
          <p><?= htmlspecialchars($bike['model']) ?></p>
          <div class="price">₹<?= number_format($bike['price']) ?>/day</div>
          <button class="btn-rent" onclick="handleRent(<?= $bike['TWID'] ?>, 'two')">Rent Now</button>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<!-- DESERT HERO -->
<section class="desert-hero">
  <h2>Rule the Desert</h2>
  <p style="font-size:1.7rem;max-width:900px;margin:30px auto 50px;">
    Mahindra Thar • Toyota Fortuner • Isuzu V-Cross • Force Gurkha<br>
    Roof tents • Sand ladders • Recovery kits included
  </p>
  <a href="#cars" style="background:var(--mustard);color:var(--navy);padding:20px 60px;border-radius:60px;font-size:1.5rem;font-weight:800;text-decoration:none;">
    Explore 4x4 Fleet
  </a>
</section>

<!-- CARS -->
<section class="section" id="cars">
  <h2 class="section-title">4x4 Legends for Extreme Terrain</h2>
  <p class="section-subtitle">Conquer sand dunes, river crossings, and 5000m passes with confidence</p>
  <div class="vehicles-grid">
    <?php while ($car = $fourWheelers->fetch_assoc()): ?>
      <div class="vehicle-card">
        <img src="<?= htmlspecialchars($car['photo']) ?>" alt="<?= $car['name'] ?>">
        <div class="vehicle-info">
          <h3><?= htmlspecialchars($car['name']) ?></h3>
          <p><?= htmlspecialchars($car['model']) ?></p>
          <div class="price">₹<?= number_format($car['price']) ?>/day</div>
          <button class="btn-rent" onclick="handleRent(<?= $car['FWID'] ?>, 'four')">Rent Now</button>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<!-- TRUST -->
<section class="trust">
  <h2 class="section-title">Trusted by Adventurers</h2>
  <div class="trust-grid">
    <div class="trust-item">
      <i class="fas fa-shield"></i>
      <h4>100% Verified Bikes</h4>
      <p>Every vehicle serviced & checked before handover</p>
    </div>
    <div class="trust-item">
      <i class="fas fa-headset"></i>
      <h4>24/7 Roadside Rescue</h4>
      <p>We're with you even at 4 AM in Upper Mustang</p>
    </div>
    <div class="trust-item">
      <i class="fas fa-route"></i>
      <h4>Curated Routes & Maps</h4>
      <p>Offline GPS, permits, best stays — all arranged</p>
    </div>
    <div class="trust-item">
      <i class="fas fa-tools"></i>
      <h4>Full Adventure Gear</h4>
      <p>Riding jackets, helmets, gloves — included free</p>
    </div>
  </div>
</section>

<!-- TESTIMONIAL -->
<section class="testimonial">
  <div class="testimonial-quote">
    “Rented a Himalayan 450 for 18 days to Upper Mustang. Bike was brand new, support team rescued us at 2 AM when we got stuck in a river. This isn’t just rental — it’s a brotherhood.”
    <div class="author">— The Himalayan Riders Club, 2025</div>
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