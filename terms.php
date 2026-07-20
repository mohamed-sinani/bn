<?php
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/cart.php';
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Terms of Service — BN-Infrastructure</title>
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
  <h1>Terms of Service</h1>
  <p>These terms govern the use of the BN-Infrastructure platform and the purchase of products from our store. Please read them carefully.</p>
</section>

<div class="container">
  <div class="legal-card reveal">
    <div class="update-date">Last Updated: 1 January 2026</div>

    <div class="s-header"><span class="section-num">1</span><h2 style="margin:0;padding:0;border:none;">Account Terms</h2></div>
    <p>By creating an account on BN-Infrastructure, you agree to:</p>
    <ul>
      <li>Provide accurate, current, and complete registration information.</li>
      <li>Maintain the confidentiality of your account credentials and password.</li>
      <li>Notify us immediately of any unauthorised use of your account.</li>
      <li>Accept responsibility for all activities that occur under your account.</li>
      <li>Be at least 18 years of age or have legal parental/guardian consent.</li>
    </ul>
    <p>BN-Infrastructure reserves the right to suspend or terminate accounts that violate these terms or engage in fraudulent activity.</p>

    <div class="s-header"><span class="section-num">2</span><h2 style="margin:0;padding:0;border:none;">Payment Terms</h2></div>
    <p>All prices listed on our website are in Tanzanian Shillings (TSh) and exclude 18% VAT unless otherwise stated. Payment terms are as follows:</p>
    <ul>
      <li><strong>Online orders:</strong> Payment is due at the time of purchase via our secure payment gateway. We accept bank transfers, mobile money (M-Pesa, Tigo Pesa, Airtel Money), and card payments.</li>
      <li><strong>Corporate accounts:</strong> Approved corporate clients may be eligible for 30-day net terms subject to credit assessment and a signed credit agreement.</li>
      <li><strong>Bulk/tender orders:</strong> Payment terms will be specified in the quotation or tender document. A deposit of 50% may be required for large-value orders.</li>
      <li><strong>Late payments:</strong> Overdue accounts are subject to interest at 2% per month on the outstanding balance.</li>
    </ul>

    <div class="s-header"><span class="section-num">3</span><h2 style="margin:0;padding:0;border:none;">Shipping & Delivery</h2></div>
    <p>Shipping and delivery are governed by our Shipping Policy, which is incorporated into these Terms by reference. Key terms include:</p>
    <ul>
      <li>Delivery timeframes are estimates and not guaranteed. BN-Infrastructure is not liable for delays caused by third-party carriers or circumstances beyond our control.</li>
      <li>Risk of loss passes to the buyer upon delivery to the carrier. Insurance is available upon request.</li>
      <li>Buyer is responsible for providing accurate delivery addresses. Incorrect addresses may result in additional charges.</li>
    </ul>

    <div class="s-header"><span class="section-num">4</span><h2 style="margin:0;padding:0;border:none;">Returns & Refunds</h2></div>
    <p>Our Returns Policy is incorporated into these Terms by reference. In summary:</p>
    <ul>
      <li>Products may be returned within 30 days of delivery, subject to conditions outlined in our Returns Policy.</li>
      <li>Custom-configured or special-order items are non-returnable unless defective.</li>
      <li>Refunds are processed within 14 business days after inspection and approval.</li>
      <li>BN-Infrastructure reserves the right to charge a restocking fee of up to 15% for non-defective returns.</li>
    </ul>

    <div class="s-header"><span class="section-num">5</span><h2 style="margin:0;padding:0;border:none;">Product Warranties</h2></div>
    <p>All products sold by BN-Infrastructure carry the manufacturer's warranty. Warranty periods vary by brand and product category:</p>
    <ul>
      <li><strong>Cisco:</strong> Limited lifetime warranty on hardware. 90-day on software.</li>
      <li><strong>Ubiquiti:</strong> 1-2 year limited warranty depending on product line.</li>
      <li><strong>MikroTik:</strong> 1 year limited warranty.</li>
      <li><strong>TP-Link Omada:</strong> 3 year limited warranty.</li>
      <li><strong>Other brands:</strong> Warranty period as specified on the product page.</li>
    </ul>
    <p>Warranty claims must be submitted through our support team. BN-Infrastructure will facilitate the warranty process but does not provide independent warranty coverage beyond that offered by the manufacturer.</p>

    <div class="s-header"><span class="section-num">6</span><h2 style="margin:0;padding:0;border:none;">Limitation of Liability</h2></div>
    <p>To the maximum extent permitted by Tanzanian law:</p>
    <ul>
      <li>BN-Infrastructure's total liability for any claim arising from the use of our products or services shall not exceed the purchase price of the products giving rise to the claim.</li>
      <li>We shall not be liable for indirect, incidental, consequential, or punitive damages, including lost profits, data loss, or business interruption.</li>
      <li>Products are sold "as is" except as expressly covered by manufacturer warranties. BN-Infrastructure makes no representations or warranties of merchantability or fitness for a particular purpose.</li>
    </ul>

    <div class="s-header"><span class="section-num">7</span><h2 style="margin:0;padding:0;border:none;">Governing Law</h2></div>
    <p>These Terms of Service shall be governed by and construed in accordance with the laws of the United Republic of Tanzania. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts of Dar es Salaam, Tanzania.</p>
    <p>The United Nations Convention on Contracts for the International Sale of Goods (CISG) does not apply to transactions under these Terms.</p>

    <div class="s-header"><span class="section-num">8</span><h2 style="margin:0;padding:0;border:none;">Changes to Terms</h2></div>
    <p>BN-Infrastructure reserves the right to modify these Terms of Service at any time. Changes will be effective immediately upon posting to this page. Your continued use of our platform after changes constitutes acceptance of the updated Terms. We encourage you to review this page periodically.</p>

    <div class="s-header"><span class="section-num">9</span><h2 style="margin:0;padding:0;border:none;">Contact</h2></div>
    <p>For questions about these Terms of Service, please contact our legal department:</p>
    <ul>
      <li><strong>Email:</strong> legal@bn-infrastructure.com</li>
      <li><strong>Phone:</strong> +255 763 364 721</li>
      <li><strong>Address:</strong> Plot 45, Mikocheni Light Industrial Area, Dar es Salaam, Tanzania</li>
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
