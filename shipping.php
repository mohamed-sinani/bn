<?php
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/cart.php';
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shipping Policy — BN-Infrastructure</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--navy:#0A2540;--navy-light:#133057;--navy-dark:#071a2e;--orange:#F05A22;--orange-dark:#d44d1a;--orange-light:#ff6b35;--bg:#F4F6F9;--card:#FFFFFF;--text-primary:#0A2540;--text-secondary:#5a6a7e;--text-muted:#8fa0b3;--border:#e2e8f0;--shadow-sm:0 1px 3px rgba(10,37,64,0.08),0 1px 2px rgba(10,37,64,0.04);--shadow-md:0 4px 12px rgba(10,37,64,0.1),0 2px 6px rgba(10,37,64,0.06);--shadow-lg:0 10px 30px rgba(10,37,64,0.12),0 4px 12px rgba(10,37,64,0.08)}
    html{scroll-behavior:smooth}
    body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-primary);font-size:15px;line-height:1.6}
    .announcement-bar{background:var(--orange);color:#fff;text-align:center;padding:9px 24px;font-size:13px;font-weight:500;letter-spacing:.01em}
    .announcement-bar i{margin-right:6px;opacity:.9}
    .announcement-bar span{margin:0 18px;opacity:.7}
    .navbar{background:var(--navy);padding:0 48px;display:flex;align-items:center;gap:0;height:70px;position:sticky;top:0;z-index:1000;box-shadow:0 2px 12px rgba(0,0,0,0.2)}
    .nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;margin-right:40px;flex-shrink:0}
    .nav-logo-icon{width:38px;height:38px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff}
    .nav-logo-text{display:flex;flex-direction:column;line-height:1.1}
    .nav-logo-text .brand{font-size:18px;font-weight:800;color:#fff;letter-spacing:-.02em}
    .nav-logo-text .tagline{font-size:10px;font-weight:400;color:#fff;letter-spacing:.08em;text-transform:uppercase}
    .nav-links{display:flex;align-items:center;gap:4px;flex:1}
    .nav-links a{text-decoration:none;color:rgba(255,255,255,0.75);font-size:14px;font-weight:500;padding:8px 14px;border-radius:6px;transition:color .2s,background .2s;white-space:nowrap}
    .nav-links a:hover{color:#fff;background:rgba(255,255,255,0.08)}
    .nav-links a.active{color:#fff}
    .hamburger{display:none;background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:6px;margin-left:auto}
    .nav-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999}
    .nav-overlay.open{display:block}
    .mobile-nav{position:fixed;top:0;right:-280px;width:280px;height:100vh;background:var(--navy);z-index:1001;transition:right .3s ease;padding:80px 24px 24px;overflow-y:auto}
    .mobile-nav.open{right:0}
    .mobile-nav .mobile-nav-header{display:flex;align-items:center;gap:10px;padding-bottom:16px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:8px}
    .mobile-nav .mobile-nav-header .mh-icon{width:34px;height:34px;background:var(--orange);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;flex-shrink:0}
    .mobile-nav .mobile-nav-header .mh-text{display:flex;flex-direction:column;line-height:1.1;flex:1}
    .mobile-nav .mobile-nav-header .mh-text .mh-brand{font-size:15px;font-weight:700;color:#fff}
    .mobile-nav .mobile-nav-header .mh-text .mh-tag{font-size:9px;font-weight:400;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.06em}
    .mobile-nav .close-btn{position:absolute;top:12px;right:12px;background:none;border:none;color:#fff;font-size:22px;cursor:pointer;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:6px}
    .mobile-nav .close-btn:hover{background:rgba(255,255,255,0.1)}
    .mobile-nav a{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,0.8);text-decoration:none;padding:12px 0;font-size:15px;font-weight:500;border-bottom:1px solid rgba(255,255,255,0.06)}
    .mobile-nav a i{width:18px;color:var(--orange);font-size:14px}
    .mobile-nav a:hover{color:var(--orange)}
    .nav-actions{display:flex;align-items:center;gap:12px;flex-shrink:0}
    .search-bar{display:flex;align-items:center;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:8px;padding:7px 12px;gap:8px;transition:background .2s,border-color .2s;width:220px}
    .search-bar:focus-within{background:rgba(255,255,255,0.15);border-color:rgba(255,255,255,0.3)}
    .search-bar i{color:#fff;font-size:13px}
    .search-bar input{background:none;border:none;outline:none;color:#fff;font-family:'Inter',sans-serif;font-size:13px;width:100%}
    .search-bar input::placeholder{color:rgba(255,255,255,0.45)}
    .btn-signin{background:transparent;border:1.5px solid rgba(255,255,255,0.3);color:rgba(255,255,255,0.85);padding:7px 16px;border-radius:7px;font-family:'Inter',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:all .2s;white-space:nowrap;text-decoration:none}
    .btn-signin:hover{background:rgba(255,255,255,0.1);border-color:#fff;color:#fff}
    .cart-btn{background:var(--orange);border:none;color:#fff;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;transition:background .2s,transform .2s;font-size:15px}
    .cart-btn:hover{background:var(--orange-dark);transform:translateY(-1px)}
    .cart-badge{position:absolute;top:-5px;right:-5px;background:#fff;color:var(--orange);font-size:10px;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--orange)}
    .page-hero{background:var(--navy);padding:60px 48px;text-align:center;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 100%,rgba(240,90,34,0.1) 0%,transparent 60%)}
    .page-hero h1{font-size:clamp(28px,3vw,44px);font-weight:800;color:#fff;letter-spacing:-.03em;position:relative;z-index:1}
    .page-hero p{font-size:17px;color:rgba(255,255,255,0.65);max-width:600px;margin:12px auto 0;position:relative;z-index:1}
    .container{max-width:900px;margin:0 auto;padding:48px 24px}
    .content-card{background:var(--card);border-radius:16px;box-shadow:var(--shadow-md);padding:40px;margin-bottom:32px}
    .content-card h2{font-size:22px;font-weight:800;color:var(--navy);letter-spacing:-.02em;margin-bottom:16px}
    .content-card p{font-size:14px;color:var(--text-secondary);line-height:1.75;margin-bottom:12px}
    .content-card p:last-child{margin-bottom:0}
    .content-card ul{margin:8px 0 16px;padding-left:20px}
    .content-card ul li{font-size:14px;color:var(--text-secondary);line-height:1.7;margin-bottom:6px}
    .shipping-table{width:100%;border-collapse:collapse;margin:16px 0 24px;font-size:14px}
    .shipping-table th{background:var(--navy);color:#fff;padding:12px 16px;text-align:left;font-weight:600;font-size:13px}
    .shipping-table td{padding:12px 16px;border-bottom:1px solid var(--border);color:var(--text-secondary)}
    .shipping-table tr:nth-child(even) td{background:rgba(10,37,64,0.02)}
    .shipping-table tr:hover td{background:rgba(240,90,34,0.04)}
    .highlight-card{background:linear-gradient(135deg,var(--navy),var(--navy-light));border-radius:14px;padding:28px 32px;margin-bottom:24px;color:#fff}
    .highlight-card h3{font-size:18px;font-weight:700;margin-bottom:8px}
    .highlight-card p{font-size:14px;color:rgba(255,255,255,0.7);margin-bottom:0!important}
    .highlight-card i{color:var(--orange);margin-right:8px}
    .step-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin:20px 0}
    .step-item{display:flex;align-items:flex-start;gap:14px;padding:16px;background:rgba(10,37,64,0.03);border-radius:10px}
    .step-item .s-num{width:32px;height:32px;background:var(--orange);color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
    .step-item .s-text h4{font-size:14px;font-weight:700;color:var(--navy);margin-bottom:3px}
    .step-item .s-text p{font-size:12px;color:var(--text-secondary);margin:0!important}
    .user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}
    .reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
    .mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
    @media(max-width:1200px){.navbar,.page-hero,.container,footer{padding-left:32px;padding-right:32px}}
    @media(max-width:768px){
      .navbar{padding:0 16px;height:60px}.nav-links{display:none}.hamburger{display:block}.btn-signin span{display:none}
      .container{padding:24px 16px}.content-card{padding:24px}
      .page-hero,footer{padding-left:20px;padding-right:20px}
      .step-grid{grid-template-columns:1fr}
      .search-bar{width:160px}
    }
    footer{background:var(--navy-dark);padding:64px 48px 0;border-top:1px solid rgba(255,255,255,0.06)}
    .footer-inner{max-width:1632px;margin:0 auto}
    .footer-grid{display:grid;grid-template-columns:280px 1fr 1fr 300px;gap:48px;padding-bottom:48px;border-bottom:1px solid rgba(255,255,255,0.08)}
    .footer-brand .logo{display:flex;align-items:center;gap:10px;margin-bottom:16px}
    .footer-brand .logo .icon{width:36px;height:36px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:17px;color:#fff}
    .footer-brand .logo .name{font-size:18px;font-weight:800;color:#fff}
    .footer-brand p{font-size:13px;color:#fff;line-height:1.7;margin-bottom:20px}
    .footer-socials{display:flex;gap:8px}
    .footer-social-btn{width:34px;height:34px;background:rgba(255,255,255,0.06);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;cursor:pointer;transition:all .2s;text-decoration:none}
    .footer-social-btn:hover{background:var(--orange);color:#fff}
    .footer-col h4{font-size:13px;font-weight:700;color:#fff;letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px}
    .footer-col ul{list-style:none;display:flex;flex-direction:column;gap:10px}
    .footer-col ul li a{font-size:13px;color:#fff;text-decoration:none;transition:color .2s;display:flex;align-items:center;gap:7px}
    .footer-col ul li a:hover{color:rgba(255,255,255,0.9)}
    .footer-col ul li a i{font-size:11px;color:var(--orange);opacity:.7}
    .contact-item{display:flex;gap:10px;margin-bottom:14px}
    .contact-item i{color:var(--orange);font-size:14px;margin-top:2px;flex-shrink:0}
    .contact-item span{font-size:13px;color:#fff;line-height:1.5}
    .newsletter-label{font-size:13px;color:#fff;margin-bottom:12px;line-height:1.5}
    .newsletter-form{display:flex;gap:8px}
    .newsletter-form input{flex:1;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:7px;padding:10px 14px;color:#fff;font-family:'Inter',sans-serif;font-size:13px;outline:none;transition:border-color .2s}
    .newsletter-form input::placeholder{color:#fff}
    .newsletter-form input:focus{border-color:rgba(255,255,255,0.3)}
    .newsletter-form button{background:var(--orange);color:#fff;border:none;border-radius:7px;padding:10px 16px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;white-space:nowrap}
    .newsletter-form button:hover{background:var(--orange-dark)}
    .footer-bottom{padding:20px 0;display:flex;align-items:center;justify-content:space-between}
    .footer-bottom p{font-size:12px;color:#fff}
    .footer-bottom-links{display:flex;gap:20px}
    .footer-bottom-links a{font-size:12px;color:#fff;text-decoration:none;transition:color .2s}
    .footer-bottom-links a:hover{color:#fff}
    @media(max-width:1200px){.footer-grid{grid-template-columns:1fr 1fr}}
    @media(max-width:768px){.footer-grid{grid-template-columns:1fr;gap:32px}.footer-bottom{flex-direction:column;gap:12px;text-align:center}}
  </style>
</head>
<body>

<div class="announcement-bar">
  <i class="fas fa-truck"></i> Free delivery on orders above TSh 500,000
  <span>|</span> <i class="fas fa-map-marker-alt"></i> Serving all regions in Tanzania
  <span>|</span> <i class="fas fa-headset"></i> Technical support: +255 763 364 721
</div>

<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-icon"><i class="fas fa-network-wired"></i></div>
    <div class="nav-logo-text"><span class="brand">BN-Infrastructure</span><span class="tagline">Tanzania</span></div>
  </a>
  <div class="nav-links">
    <a href="catalog.php">Products</a>
    <a href="about.php">Solutions</a>
    <a href="track.php">Track Order</a>
    <a href="login.php">Account</a>
  </div>
  <div class="nav-actions">
    <?php echo userNavHtml(); ?>
    <a href="cart.php" class="cart-btn"><i class="fas fa-shopping-cart"></i><span class="cart-badge"><?php echo cartCount(); ?></span></a>
    <button class="hamburger" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
  </div>
</nav>
<div class="nav-overlay" id="navOverlay" onclick="toggleMenu()"></div>
<div class="mobile-nav" id="mobileNav">
  <div class="mobile-nav-header">
    <div class="mh-icon"><i class="fas fa-network-wired"></i></div>
    <div class="mh-text"><span class="mh-brand">BN-Infrastructure</span><span class="mh-tag">Tanzania</span></div>
    <button class="close-btn" onclick="toggleMenu()"><i class="fas fa-times"></i></button>
  </div>
  <a href="index.php" onclick="toggleMenu()"><i class="fas fa-home"></i> Home</a>
  <a href="catalog.php" onclick="toggleMenu()"><i class="fas fa-box"></i> Products</a>
  <a href="about.php" onclick="toggleMenu()"><i class="fas fa-lightbulb"></i> Solutions</a>
  <a href="track.php" onclick="toggleMenu()"><i class="fas fa-truck"></i> Track Order</a>
  <a href="login.php" onclick="toggleMenu()"><i class="fas fa-user"></i> Account</a>
  <a href="cart.php" onclick="toggleMenu()"><i class="fas fa-shopping-cart"></i> Cart</a>
</div>

<section class="page-hero reveal">
  <h1>Shipping Policy</h1>
  <p>We deliver to all 31 regions of Tanzania. Learn about our delivery timeframes, costs, and shipping process.</p>
</section>

<div class="container">

  <div class="highlight-card reveal">
    <h3><i class="fas fa-truck"></i> Free Shipping on Orders Above TSh 500,000</h3>
    <p>Enjoy complimentary delivery on all orders exceeding TSh 500,000 (pre-VAT) to any location within Tanzania. This offer applies to standard delivery timelines.</p>
  </div>

  <div class="content-card reveal">
    <h2>Delivery Areas</h2>
    <p>BN-Infrastructure delivers to all 31 regions of mainland Tanzania, including Zanzibar and Pemba. Our logistics network covers urban centres, peri-urban areas, and selected rural locations through partnerships with leading courier and freight companies.</p>
    <p>For remote or hard-to-reach locations, please contact our logistics team for a custom delivery quote and timeframe estimate.</p>
  </div>

  <div class="content-card reveal">
    <h2>Delivery Timeframes</h2>
    <table class="shipping-table">
      <thead><tr><th>Zone</th><th>Regions</th><th>Estimated Delivery Time</th></tr></thead>
      <tbody>
        <tr><td><strong>Zone 1 — Dar es Salaam</strong></td><td>Dar es Salaam (all districts: Ilala, Kinondoni, Ubungo, Kigamboni, Temeke)</td><td>1-2 business days</td></tr>
        <tr><td><strong>Zone 2 — Coastal & Northern</strong></td><td>Coastal Region, Tanga, Arusha, Kilimanjaro, Manyara</td><td>2-4 business days</td></tr>
        <tr><td><strong>Zone 3 — Lake Zone</strong></td><td>Mwanza, Geita, Kagera, Simiyu, Shinyanga, Mara</td><td>3-5 business days</td></tr>
        <tr><td><strong>Zone 4 — Central Zone</strong></td><td>Dodoma, Singida, Tabora, Morogoro</td><td>3-5 business days</td></tr>
        <tr><td><strong>Zone 5 — Southern Highlands</strong></td><td>Mbeya, Songwe, Njombe, Iringa, Rukwa, Katavi</td><td>4-6 business days</td></tr>
        <tr><td><strong>Zone 6 — Southern & South East</strong></td><td>Lindi, Mtwara, Ruvuma</td><td>4-7 business days</td></tr>
        <tr><td><strong>Zone 7 — Zanzibar & Pemba</strong></td><td>Unguja North, Unguja South, Urban West, Pemba North, Pemba South</td><td>5-7 business days</td></tr>
      </tbody>
    </table>
    <p><em>Note: Delivery timeframes commence after order processing and payment confirmation. Orders placed before 12:00 PM EAT are processed the same business day.</em></p>
  </div>

  <div class="content-card reveal">
    <h2>Shipping Costs</h2>
    <table class="shipping-table">
      <thead><tr><th>Order Value</th><th>Delivery Fee (Dar es Salaam)</th><th>Delivery Fee (Other Regions)</th></tr></thead>
      <tbody>
        <tr><td>Below TSh 100,000</td><td>TSh 5,000</td><td>TSh 10,000 - TSh 20,000</td></tr>
        <tr><td>TSh 100,000 - TSh 250,000</td><td>TSh 3,000</td><td>TSh 8,000 - TSh 15,000</td></tr>
        <tr><td>TSh 250,000 - TSh 500,000</td><td>TSh 2,000</td><td>TSh 5,000 - TSh 10,000</td></tr>
        <tr><td>Above TSh 500,000</td><td><strong>FREE</strong></td><td><strong>FREE</strong></td></tr>
      </tbody>
    </table>
    <p>For bulk orders exceeding 50kg or oversized items, additional freight charges may apply. Our team will contact you with a precise shipping quote before dispatch.</p>
  </div>

  <div class="content-card reveal">
    <h2>Order Processing & Tracking</h2>
    <p>Once your order is placed, here is what happens:</p>
    <div class="step-grid">
      <div class="step-item"><div class="s-num">1</div><div class="s-text"><h4>Order Confirmation</h4><p>You receive an order confirmation email with your order number and summary.</p></div></div>
      <div class="step-item"><div class="s-num">2</div><div class="s-text"><h4>Processing & Packing</h4><p>Our warehouse team picks, verifies, and securely packs your items (same-day for orders before 12 PM).</p></div></div>
      <div class="step-item"><div class="s-num">3</div><div class="s-text"><h4>Dispatch Notification</h4><p>You receive a dispatch confirmation with tracking number and courier details.</p></div></div>
      <div class="step-item"><div class="s-num">4</div><div class="s-text"><h4>Track Your Order</h4><p>Use your tracking number on our Track Order page or the courier's website to monitor delivery progress.</p></div></div>
    </div>
  </div>

  <div class="content-card reveal">
    <h2>International Shipping</h2>
    <p>We primarily serve clients within Tanzania. For international shipping requests to neighbouring East African countries (Kenya, Uganda, Rwanda, Burundi, Zambia, Malawi, Mozambique, Democratic Republic of Congo), please contact our export team at export@bn-infrastructure.com for a custom shipping quote and lead time estimate.</p>
    <p>International orders are subject to additional customs clearance, duties, and taxes in the destination country, which are the responsibility of the buyer.</p>
  </div>

  <div class="content-card reveal">
    <h2>Contact Shipping Team</h2>
    <p>If you have any questions about our shipping policy or need to arrange a special delivery, please contact us:</p>
    <ul>
      <li><strong>Email:</strong> shipping@bn-infrastructure.com</li>
      <li><strong>Phone:</strong> +255 763 364 721 (ask for Logistics Department)</li>
      <li><strong>Office Hours:</strong> Monday to Friday, 8:00 AM - 5:00 PM EAT</li>
    </ul>
  </div>

</div>

<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="logo"><div class="icon"><i class="fas fa-network-wired"></i></div><span class="name">BN-Infrastructure</span></div>
        <p>Tanzania's leading B2B network infrastructure supplier. Empowering businesses with enterprise-grade connectivity solutions since 2012.</p>
        <div class="footer-socials"><a href="#" class="footer-social-btn"><i class="fab fa-linkedin-in"></i></a><a href="#" class="footer-social-btn"><i class="fab fa-twitter"></i></a><a href="#" class="footer-social-btn"><i class="fab fa-facebook-f"></i></a><a href="#" class="footer-social-btn"><i class="fab fa-whatsapp"></i></a></div>
      </div>
      <div class="footer-col"><h4>Quick Links</h4><ul><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> All Products</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> New Arrivals</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Best Sellers</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Special Offers</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Request a Quote</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Bulk Orders</a></li></ul></div>
      <div class="footer-col"><h4>Company</h4><ul><li><a href="about.php"><i class="fas fa-chevron-right"></i> About BN-Infrastructure</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Our Brands</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Solutions</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Blog &amp; Resources</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Careers</a></li><li><a href="privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li></ul></div>
      <div class="footer-col"><h4>Contact Us</h4><div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>Plot 45, Mikocheni Light Industrial Area, Dar es Salaam, Tanzania</span></div><div class="contact-item"><i class="fas fa-phone-alt"></i><span>+255 763 364 721 <br>+255 763 364 721</span></div><div class="contact-item"><i class="fas fa-envelope"></i><span>sales@bn-infrastructure.com</span></div><div style="margin-top:20px;"><h4>Newsletter</h4><div class="newsletter-label">Get product updates and exclusive deals</div><div class="newsletter-form"><input type="email" placeholder="Your email address"><button>Subscribe</button></div></div></div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> BN-Infrastructure. All rights reserved. | Powered by BN-Infrastructure</p>
        <div class="footer-bottom-links">
          <a href="privacy.php">Privacy Policy</a>
          <a href="terms.php">Terms of Service</a>
          <a href="shipping.php">Shipping Policy</a>
          <a href="returns.php">Returns</a>
        </div>
      </div>
    </div>
  </footer>

<script>
function toggleMenu(){document.getElementById('mobileNav').classList.toggle('open');document.getElementById('navOverlay').classList.toggle('open')}
document.addEventListener('DOMContentLoaded',function(){var items=document.querySelectorAll('.reveal');if(items.length){var obs=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.style.opacity='1';e.target.style.transform='translateY(0)';obs.unobserve(e.target)}})},{threshold:0.1});items.forEach(function(el){el.style.opacity='0';el.style.transform='translateY(24px)';el.style.transition='opacity .6s ease, transform .6s ease';obs.observe(el)})}});
</script>
</body></html>
