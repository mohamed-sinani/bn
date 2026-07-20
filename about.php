<?php
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/cart.php';
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About BN-Infrastructure — BN-Infrastructure</title>
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
    .content-card p{font-size:15px;color:var(--text-secondary);line-height:1.7;margin-bottom:12px}
    .content-card p:last-child{margin-bottom:0}
    .about-hero{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-light) 100%);padding:80px 48px;text-align:center;position:relative;overflow:hidden}
    .about-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 70% 30%,rgba(240,90,34,0.12) 0%,transparent 50%)}
    .about-hero-inner{max-width:800px;margin:0 auto;position:relative;z-index:1}
    .about-hero h1{font-size:clamp(30px,3.5vw,48px);font-weight:800;color:#fff;letter-spacing:-.03em;margin-bottom:16px}
    .about-hero h1 span{color:var(--orange)}
    .about-hero p{font-size:17px;color:rgba(255,255,255,0.65);line-height:1.7;margin-bottom:24px}
    .about-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:40px;padding-top:32px;border-top:1px solid rgba(255,255,255,0.1)}
    .about-stat{text-align:center}
    .about-stat .num{font-size:28px;font-weight:800;color:#fff;letter-spacing:-.02em}
    .about-stat .lbl{font-size:12px;color:#fff;margin-top:4px}
    .mission-vision{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px}
    .mv-card{background:var(--card);border-radius:14px;padding:32px;box-shadow:var(--shadow-md);border-top:4px solid var(--orange)}
    .mv-card i{font-size:32px;color:var(--orange);margin-bottom:16px}
    .mv-card h3{font-size:20px;font-weight:800;color:var(--navy);margin-bottom:10px}
    .mv-card p{font-size:14px;color:var(--text-secondary);line-height:1.7}
    .values-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:32px}
    .value-card{background:var(--card);border-radius:14px;padding:28px 24px;text-align:center;box-shadow:var(--shadow-sm);border:1px solid var(--border);transition:transform .2s,box-shadow .2s}
    .value-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
    .value-card .v-icon{width:56px;height:56px;border-radius:14px;background:rgba(240,90,34,0.1);display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--orange);margin:0 auto 14px}
    .value-card h4{font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px}
    .value-card p{font-size:13px;color:var(--text-secondary);line-height:1.6}
    .team-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;margin-bottom:32px}
    .team-card{background:var(--card);border-radius:14px;overflow:hidden;box-shadow:var(--shadow-sm);border:1px solid var(--border);transition:transform .2s,box-shadow .2s}
    .team-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
    .team-card .t-img{height:200px;background:linear-gradient(135deg,var(--navy),var(--navy-light));display:flex;align-items:center;justify-content:center;font-size:48px;color:rgba(255,255,255,0.2)}
    .team-card .t-body{padding:20px}
    .team-card .t-body h4{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:3px}
    .team-card .t-body .t-role{font-size:12px;color:var(--orange);font-weight:600;margin-bottom:6px}
    .team-card .t-body p{font-size:12px;color:var(--text-muted);line-height:1.5}
    .user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}
    .reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
    .mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
    @media(max-width:1200px){.navbar,.about-hero,.page-hero,.container,footer{padding-left:32px;padding-right:32px}.values-grid{grid-template-columns:repeat(2,1fr)}.about-stats{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:768px){
      .navbar{padding:0 16px;height:60px}.nav-links{display:none}.hamburger{display:block}.btn-signin span{display:none}
      .container{padding:24px 16px}.content-card{padding:24px}
      .about-hero,.page-hero,footer{padding-left:20px;padding-right:20px}
      .about-stats{grid-template-columns:1fr 1fr;gap:16px}
      .mission-vision{grid-template-columns:1fr}
      .values-grid{grid-template-columns:1fr}
      .team-grid{grid-template-columns:1fr}
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

<section class="about-hero reveal">
  <div class="about-hero-inner">
    <h1>About <span>BN-Infrastructure</span></h1>
    <p>Since 2012, we have been Tanzania's premier B2B network infrastructure supplier — delivering enterprise-grade connectivity solutions to businesses, ISPs, and government institutions across all 31 regions.</p>
    <div class="about-stats">
      <div class="about-stat"><div class="num">12+</div><div class="lbl">Years in Business</div></div>
      <div class="about-stat"><div class="num">2,000+</div><div class="lbl">Business Clients</div></div>
      <div class="about-stat"><div class="num">50+</div><div class="lbl">Verified Brands</div></div>
      <div class="about-stat"><div class="num">31</div><div class="lbl">Regions Served</div></div>
    </div>
  </div>
</section>

<div class="container">
  <div class="content-card reveal">
    <h2>Our Story</h2>
    <p>Founded in 2012 in Dar es Salaam, BN-Infrastructure began as a small network equipment distributor serving a handful of local ISPs. Founder Emmanuel Mwangi, a certified network engineer with over a decade of experience, recognised a critical gap in the Tanzanian market — businesses struggled to source genuine, enterprise-grade networking equipment with reliable technical support and competitive pricing.</p>
    <p>What started in a modest office in Mikocheni has grown into Tanzania's most trusted B2B network infrastructure supplier. Over the past twelve years, we have built strong partnerships with global technology leaders including Cisco, Ubiquiti, MikroTik, and HP Aruba. Our warehouse in Dar es Salaam stocks over 500 SKUs, and our team of certified engineers provides end-to-end support — from network design consultation to post-deployment troubleshooting.</p>
    <p>Today, BN-Infrastructure serves over 2,000 business clients across all 31 regions of Tanzania, from telecom operators in Dar es Salaam to government agencies in Dodoma and mining operations in Mwanza. We are proud to be the preferred infrastructure partner for organisations that demand reliability, performance, and genuine products.</p>
  </div>

  <div class="mission-vision reveal">
    <div class="mv-card">
      <i class="fas fa-bullseye"></i>
      <h3>Our Mission</h3>
      <p>To empower Tanzanian businesses and institutions with enterprise-grade network infrastructure that drives productivity, connectivity, and growth — backed by exceptional technical support and unmatched product authenticity.</p>
    </div>
    <div class="mv-card">
      <i class="fas fa-eye"></i>
      <h3>Our Vision</h3>
      <p>To be Tanzania's most trusted network infrastructure partner — setting the standard for quality, reliability, and innovation in the East African ICT distribution landscape.</p>
    </div>
  </div>

  <h2 style="font-size:22px;font-weight:800;color:var(--navy);margin-bottom:20px;letter-spacing:-.02em;">Our Core Values</h2>
  <div class="values-grid reveal">
    <div class="value-card">
      <div class="v-icon"><i class="fas fa-shield-alt"></i></div>
      <h4>Integrity</h4>
      <p>We source only from authorised distributors and guarantee 100% genuine products. Every item we sell is backed by manufacturer warranties and our own quality assurance.</p>
    </div>
    <div class="value-card">
      <div class="v-icon"><i class="fas fa-lightbulb"></i></div>
      <h4>Innovation</h4>
      <p>We continuously invest in the latest networking technologies — from WiFi 7 access points to 400G transceivers — ensuring our clients stay ahead of the curve.</p>
    </div>
    <div class="value-card">
      <div class="v-icon"><i class="fas fa-check-circle"></i></div>
      <h4>Reliability</h4>
      <p>With same-day dispatch for Dar es Salaam orders and nationwide delivery within 7 days, we meet deadlines that matter. Our 98% on-time delivery rate speaks for itself.</p>
    </div>
    <div class="value-card">
      <div class="v-icon"><i class="fas fa-handshake"></i></div>
      <h4>Partnership</h4>
      <p>We see our clients as long-term partners. From pre-sales network design to post-installation support, our certified engineers are with you every step of the way.</p>
    </div>
  </div>

  <h2 style="font-size:22px;font-weight:800;color:var(--navy);margin-bottom:20px;letter-spacing:-.02em;">Leadership Team</h2>
  <div class="team-grid reveal">
    <div class="team-card">
      <div class="t-img"><i class="fas fa-user-circle"></i></div>
      <div class="t-body">
        <h4>Emmanuel Mwangi</h4>
        <div class="t-role">Founder & CEO</div>
        <p>CCIE-certified network engineer with 18+ years in ICT infrastructure. Founded BN-Infrastructure in 2012 with a vision to transform Tanzania's networking landscape.</p>
      </div>
    </div>
    <div class="team-card">
      <div class="t-img"><i class="fas fa-user-circle"></i></div>
      <div class="t-body">
        <h4>Aisha Kombo</h4>
        <div class="t-role">Chief Operations Officer</div>
        <p>Operations and supply chain expert who ensures seamless logistics across 31 regions. 14 years of experience in East African distribution and warehousing.</p>
      </div>
    </div>
    <div class="team-card">
      <div class="t-img"><i class="fas fa-user-circle"></i></div>
      <div class="t-body">
        <h4>James Ochieng</h4>
        <div class="t-role">Technical Director</div>
        <p>Senior network architect specialising in ISP and enterprise deployments. Leads our team of certified engineers across pre-sales, deployment, and support.</p>
      </div>
    </div>
    <div class="team-card">
      <div class="t-img"><i class="fas fa-user-circle"></i></div>
      <div class="t-body">
        <h4>Grace Mushi</h4>
        <div class="t-role">Commercial Director</div>
        <p>Drives strategic partnerships with global brands including Cisco, Ubiquiti, and MikroTik. Oversees B2B sales, tenders, and government procurement relationships.</p>
      </div>
    </div>
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
