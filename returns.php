<?php
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/cart.php';
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Returns & Refunds — BN-Infrastructure</title>
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
    .process-steps{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:24px 0}
    .process-step{background:var(--card);border-radius:14px;padding:24px;box-shadow:var(--shadow-sm);border:1px solid var(--border);display:flex;gap:16px;align-items:flex-start;transition:transform .2s,box-shadow .2s}
    .process-step:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
    .process-step .ps-num{width:40px;height:40px;background:var(--orange);color:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;flex-shrink:0}
    .process-step .ps-text h4{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:4px}
    .process-step .ps-text p{font-size:13px;color:var(--text-secondary);margin:0!important;line-height:1.5}
    .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:16px 0}
    .info-item{display:flex;align-items:flex-start;gap:12px;padding:16px;background:rgba(10,37,64,0.03);border-radius:10px}
    .info-item i{color:var(--orange);font-size:18px;flex-shrink:0;margin-top:2px}
    .info-item .i-text h4{font-size:14px;font-weight:700;color:var(--navy);margin-bottom:2px}
    .info-item .i-text p{font-size:12px;color:var(--text-secondary);margin:0!important}
    .user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}
    .reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
    .mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
    @media(max-width:1200px){.navbar,.page-hero,.container,footer{padding-left:32px;padding-right:32px}}
    @media(max-width:768px){
      .navbar{padding:0 16px;height:60px}.nav-links{display:none}.hamburger{display:block}.btn-signin span{display:none}
      .container{padding:24px 16px}.content-card{padding:24px}
      .page-hero,footer{padding-left:20px;padding-right:20px}
      .process-steps,.info-grid{grid-template-columns:1fr}
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
  <h1>Returns & Refunds</h1>
  <p>We stand behind the quality of our products. If something is not right, our returns process is straightforward and hassle-free.</p>
</section>

<div class="container">

  <div class="content-card reveal">
    <h2>30-Day Return Policy</h2>
    <p>BN-Infrastructure offers a <strong>30-day return policy</strong> from the date of delivery for most products. If you are not completely satisfied with your purchase, or if the product is faulty, damaged, or incorrect, you may return it for a refund, replacement, or credit note.</p>
    <p>To be eligible for a return, the following conditions must be met:</p>
    <div class="info-grid">
      <div class="info-item"><i class="fas fa-box-open"></i><div class="i-text"><h4>Unused & Original Packaging</h4><p>Products must be returned in their original packaging with all accessories, manuals, and cables included. Items showing signs of use may be subject to a restocking fee.</p></div></div>
      <div class="info-item"><i class="fas fa-receipt"></i><div class="i-text"><h4>Proof of Purchase</h4><p>A valid order number, invoice, or receipt is required for all returns. Please have your order details ready when initiating a return.</p></div></div>
      <div class="info-item"><i class="fas fa-calendar-alt"></i><div class="i-text"><h4>Within 30 Days</h4><p>Return requests must be submitted within 30 calendar days of delivery. Requests after this period will be assessed on a case-by-case basis.</p></div></div>
      <div class="info-item"><i class="fas fa-ban"></i><div class="i-text"><h4>Non-Returnable Items</h4><p>Custom-configured products, special-order items, software licenses, and opened consumables (cables, connectors) are non-returnable unless defective.</p></div></div>
    </div>
  </div>

  <div class="content-card reveal">
    <h2>Return Process</h2>
    <p>Follow these simple steps to initiate your return:</p>
    <div class="process-steps">
      <div class="process-step">
        <div class="ps-num">1</div>
        <div class="ps-text"><h4>Submit Return Request</h4><p>Contact our returns team at returns@bn-infrastructure.com or call +255 763 364 721. Provide your order number, the items you wish to return, and the reason for the return.</p></div>
      </div>
      <div class="process-step">
        <div class="ps-num">2</div>
        <div class="ps-text"><h4>Receive RMA Number</h4><p>We will issue a Return Merchandise Authorisation (RMA) number and provide detailed return instructions, including the return address and shipping guidelines.</p></div>
      </div>
      <div class="process-step">
        <div class="ps-num">3</div>
        <div class="ps-text"><h4>Pack & Ship</h4><p>Securely package the item(s) in original packaging with all accessories. Write the RMA number clearly on the outside of the package. Ship to the address provided.</p></div>
      </div>
      <div class="process-step">
        <div class="ps-num">4</div>
        <div class="ps-text"><h4>Inspection & Refund</h4><p>Once received, our team inspects the item within 3-5 business days. Approved refunds are processed within 14 business days to your original payment method.</p></div>
      </div>
    </div>
  </div>

  <div class="content-card reveal">
    <h2>Refund Timeline</h2>
    <p>Refund timelines vary depending on the payment method:</p>
    <ul>
      <li><strong>Bank transfers:</strong> 3-7 business days after approval.</li>
      <li><strong>Mobile money (M-Pesa, Tigo Pesa, Airtel Money):</strong> 1-3 business days after approval.</li>
      <li><strong>Card payments:</strong> 5-10 business days after approval (dependent on card issuer).</li>
      <li><strong>Corporate credit accounts:</strong> Credit note issued within 5 business days.</li>
    </ul>
    <p>Partial refunds may be issued at our discretion, including deductions for restocking fees (up to 15%), damaged packaging, or missing accessories.</p>
  </div>

  <div class="content-card reveal">
    <h2>Warranty Returns</h2>
    <p>For products experiencing manufacturing defects or performance issues within the warranty period:</p>
    <ul>
      <li>Contact our technical support team at support@bn-infrastructure.com for diagnostic assistance. Many issues can be resolved remotely without the need for a physical return.</li>
      <li>If a defect is confirmed, we will facilitate the warranty claim process with the manufacturer. This may include repair, replacement, or credit at the manufacturer's discretion.</li>
      <li>Warranty periods vary by brand. Please refer to the product page or manufacturer documentation for specific warranty terms.</li>
      <li>Shipping costs for warranty returns are covered by BN-Infrastructure if the product is found to be defective upon inspection.</li>
    </ul>
  </div>

  <div class="content-card reveal">
    <h2>Contact Returns Department</h2>
    <p>For any questions or to initiate a return, please contact our dedicated returns team:</p>
    <ul>
      <li><strong>Email:</strong> returns@bn-infrastructure.com</li>
      <li><strong>Phone:</strong> +255 763 364 721 (option 3 for Returns)</li>
      <li><strong>Returns Address:</strong> Returns Department, BN-Infrastructure Ltd, Plot 45, Mikocheni Light Industrial Area, Dar es Salaam, Tanzania</li>
      <li><strong>Office Hours:</strong> Monday to Friday, 8:00 AM - 5:00 PM EAT</li>
    </ul>
    <p style="margin-top:16px;font-size:13px;color:var(--text-muted);"><i class="fas fa-info-circle"></i> We recommend using a tracked shipping service for return shipments. BN-Infrastructure is not responsible for items lost or damaged during return transit.</p>
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
