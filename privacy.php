<?php
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/cart.php';
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Privacy Policy — BN-Infrastructure</title>
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
    .legal-card{background:var(--card);border-radius:16px;box-shadow:var(--shadow-md);padding:48px}
    .legal-card h2{font-size:20px;font-weight:800;color:var(--navy);margin-top:32px;margin-bottom:12px;padding-top:24px;border-top:1px solid var(--border)}
    .legal-card h2:first-child{margin-top:0;padding-top:0;border-top:none}
    .legal-card h3{font-size:16px;font-weight:700;color:var(--navy);margin-top:24px;margin-bottom:8px}
    .legal-card p{font-size:14px;color:var(--text-secondary);line-height:1.75;margin-bottom:12px}
    .legal-card ul{margin:8px 0 16px;padding-left:20px}
    .legal-card ul li{font-size:14px;color:var(--text-secondary);line-height:1.7;margin-bottom:6px}
    .legal-card .section-num{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:var(--orange);color:#fff;font-size:12px;font-weight:700;border-radius:6px;margin-right:10px;flex-shrink:0}
    .legal-card .s-header{display:flex;align-items:center;margin-bottom:12px}
    .legal-card .update-date{font-size:13px;color:var(--text-muted);margin-bottom:32px;padding-bottom:24px;border-bottom:1px solid var(--border)}
    .user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}
    .reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
    .mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
    @media(max-width:1200px){.navbar,.page-hero,.container,footer{padding-left:32px;padding-right:32px}}
    @media(max-width:768px){
      .navbar{padding:0 16px;height:60px}.nav-links{display:none}.hamburger{display:block}.btn-signin span{display:none}
      .container{padding:24px 16px}.legal-card{padding:24px}
      .page-hero,footer{padding-left:20px;padding-right:20px}
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
  <h1>Privacy Policy</h1>
  <p>This policy explains how BN-Infrastructure Ltd collects, uses, and protects your personal data in compliance with Tanzanian data protection regulations.</p>
</section>

<div class="container">
  <div class="legal-card reveal">
    <div class="update-date">Last Updated: 1 January 2026</div>

    <div class="s-header"><span class="section-num">1</span><h2 style="margin:0;padding:0;border:none;">Information We Collect</h2></div>
    <p>We collect information you provide directly to us when you:</p>
    <ul>
      <li><strong>Create an account:</strong> Your name, email address, phone number, company name, and billing/shipping address.</li>
      <li><strong>Place an order:</strong> Purchase history, invoice details, payment information (processed securely through our payment gateway — we do not store full payment card numbers).</li>
      <li><strong>Request a quote:</strong> Project details, technical requirements, and contact information.</li>
      <li><strong>Contact our support team:</strong> Records of your inquiries, technical issues, and our communications.</li>
      <li><strong>Subscribe to newsletters:</strong> Your email address and communication preferences.</li>
    </ul>
    <p>We also automatically collect certain technical information when you visit our website, including your IP address, browser type, device information, and browsing behaviour through cookies and similar technologies.</p>

    <div class="s-header"><span class="section-num">2</span><h2 style="margin:0;padding:0;border:none;">How We Use Your Information</h2></div>
    <p>BN-Infrastructure uses your personal data for the following purposes:</p>
    <ul>
      <li>Processing and fulfilling your orders, including shipping, invoicing, and payment processing.</li>
      <li>Providing quotations, technical support, and after-sales service.</li>
      <li>Communicating order updates, delivery status, and service notifications.</li>
      <li>Sending marketing communications (with your consent) about new products, promotions, and industry insights.</li>
      <li>Improving our website, product offerings, and customer experience through analytics.</li>
      <li>Complying with legal and regulatory obligations in the United Republic of Tanzania.</li>
    </ul>

    <div class="s-header"><span class="section-num">3</span><h2 style="margin:0;padding:0;border:none;">Data Sharing & Disclosure</h2></div>
    <p>We do not sell your personal information to third parties. We may share your data with:</p>
    <ul>
      <li><strong>Delivery partners:</strong> Courier and logistics companies to facilitate order delivery across Tanzania.</li>
      <li><strong>Payment processors:</strong> Secure payment gateways for transaction processing (your payment data is handled in accordance with PCI DSS standards).</li>
      <li><strong>Manufacturers:</strong> Product warranty registration and technical support escalation, only with your consent.</li>
      <li><strong>Legal authorities:</strong> When required by Tanzanian law or to protect our legal rights.</li>
    </ul>

    <div class="s-header"><span class="section-num">4</span><h2 style="margin:0;padding:0;border:none;">Cookies & Tracking</h2></div>
    <p>Our website uses cookies to enhance your browsing experience. We use:</p>
    <ul>
      <li><strong>Essential cookies:</strong> Required for website functionality, including shopping cart and account login.</li>
      <li><strong>Analytics cookies:</strong> To understand how visitors use our site and improve our services.</li>
      <li><strong>Functional cookies:</strong> To remember your preferences and settings.</li>
    </ul>
    <p>You can control cookie preferences through your browser settings. Disabling certain cookies may affect website functionality.</p>

    <div class="s-header"><span class="section-num">5</span><h2 style="margin:0;padding:0;border:none;">Your Rights</h2></div>
    <p>Under Tanzanian data protection law, you have the right to:</p>
    <ul>
      <li><strong>Access:</strong> Request a copy of the personal data we hold about you.</li>
      <li><strong>Rectification:</strong> Request correction of inaccurate or incomplete data.</li>
      <li><strong>Erasure:</strong> Request deletion of your personal data, subject to legal retention requirements.</li>
      <li><strong>Restriction:</strong> Limit how we use your data in certain circumstances.</li>
      <li><strong>Portability:</strong> Receive your data in a structured, commonly used format.</li>
      <li><strong>Withdraw consent:</strong> Opt out of marketing communications at any time.</li>
    </ul>
    <p>To exercise any of these rights, please contact our Data Protection Officer at privacy@bn-infrastructure.com.</p>

    <div class="s-header"><span class="section-num">6</span><h2 style="margin:0;padding:0;border:none;">Data Retention</h2></div>
    <p>We retain your personal data for as long as necessary to fulfil the purposes outlined in this policy, or as required by Tanzanian law. Account information is retained for the duration of your account plus five (5) years thereafter. Transaction records are retained for seven (7) years for tax and accounting purposes.</p>

    <div class="s-header"><span class="section-num">7</span><h2 style="margin:0;padding:0;border:none;">Data Security</h2></div>
    <p>We implement appropriate technical and organisational measures to protect your personal data, including SSL encryption, firewalls, access controls, and regular security audits. Our website is hosted in secure data centres with 24/7 monitoring.</p>

    <div class="s-header"><span class="section-num">8</span><h2 style="margin:0;padding:0;border:none;">Contact Us</h2></div>
    <p>If you have any questions about this Privacy Policy or wish to exercise your data protection rights, please contact us:</p>
    <ul>
      <li><strong>Email:</strong> privacy@bn-infrastructure.com</li>
      <li><strong>Phone:</strong> +255 763 364 721</li>
      <li><strong>Address:</strong> Plot 45, Mikocheni Light Industrial Area, Dar es Salaam, Tanzania</li>
      <li><strong>Data Protection Officer:</strong> Grace Mushi, g.mushi@bn-infrastructure.com</li>
    </ul>
    <p>We reserve the right to update this Privacy Policy. Changes will be posted on this page with an updated revision date. We encourage you to review this policy periodically.</p>
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
