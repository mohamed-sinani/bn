<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/functions.php';

$brandName = 'BN-Infrastructure';
$order = null;
$orderItems = [];
$tracking = [];
$lookedUp = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderNumber = trim($_POST['order_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($orderNumber && $email) {
        $order = fetchOne("SELECT * FROM orders WHERE order_number = ? AND email = ?", [$orderNumber, $email]);
        $lookedUp = true;
        if ($order) {
            $orderItems = fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
            $tracking = fetchAll("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at ASC", [$order['id']]);
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Tracking — <?php echo $brandName; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--orange:#F05A22;--bg:#F4F6F9;--card:#FFF;--text-primary:#0A2540;--text-secondary:#5a6a7e;--text-muted:#8fa0b3;--border:#e2e8f0;--shadow-sm:0 1px 3px rgba(10,37,64,0.08)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-primary);font-size:15px;line-height:1.6}
.navbar{background:var(--navy);padding:0 48px;display:flex;align-items:center;height:70px}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.nav-logo-icon{width:38px;height:38px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff}
.nav-logo-text{display:flex;flex-direction:column;line-height:1.1}
.nav-logo-text .brand{font-size:18px;font-weight:800;color:#fff}
.nav-logo-text .tagline{font-size:10px;font-weight:400;color:#fff;letter-spacing:0.08em;text-transform:uppercase}
.page-wrap{max-width:860px;margin:0 auto;padding:48px 24px}
h1{font-size:26px;font-weight:800;color:var(--navy);text-align:center;margin-bottom:8px}
.subtitle{text-align:center;color:var(--text-muted);font-size:14px;margin-bottom:32px}
.search-card{background:var(--card);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow-sm);padding:28px;max-width:480px;margin:0 auto 32px}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:5px}
.form-group input{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:8px;font-family:'Inter',sans-serif;font-size:14px;outline:none}
.form-group input:focus{border-color:var(--orange)}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:12px;border-radius:9px;font-family:'Inter',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;width:100%;text-decoration:none}
.btn-primary{background:var(--orange);color:#fff}
.btn-primary:hover{background:#d44d1a}
.section-card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px}
.section-card .card-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
.section-card .card-header h3{font-size:15px;font-weight:700;color:var(--navy)}
.section-card .card-header i{color:var(--orange)}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:18px 22px}
.info-item label{display:block;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:3px}
.info-item .value{font-size:14px;font-weight:600;color:var(--navy)}
.order-item{display:flex;justify-content:space-between;padding:10px 22px;border-bottom:1px solid var(--border);font-size:13px}
.order-item .oi-name{font-weight:600;color:var(--navy)}
.order-item .oi-qty{color:var(--text-muted)}
.order-item .oi-price{font-weight:700;color:var(--navy)}
.summary-row{display:flex;justify-content:space-between;padding:7px 22px;font-size:13px;color:var(--text-secondary)}
.summary-row.total{font-size:17px;font-weight:800;color:var(--navy);border-top:2px solid var(--border);padding-top:12px}
.summary-row .value{font-weight:600;color:var(--text-primary)}
.tracking-timeline{padding:18px 22px}
.tracking-item{display:flex;gap:14px;padding-bottom:18px;position:relative}
.tracking-item:not(:last-child)::before{content:'';position:absolute;left:15px;top:30px;bottom:0;width:2px;background:var(--border)}
.tracking-dot{width:30px;height:30px;border-radius:50%;background:var(--bg);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px}
.tracking-dot.active{background:rgba(5,150,105,0.1);border-color:#059669;color:#059669}
.tracking-content .t-status{font-size:14px;font-weight:600;color:var(--navy)}
.tracking-content .t-note{font-size:12px;color:var(--text-muted)}
.tracking-content .t-time{font-size:11px;color:var(--text-muted)}
.not-found{text-align:center;padding:40px 22px;color:var(--text-muted)}
.not-found i{font-size:40px;display:block;margin-bottom:12px}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:12px}
.alert-warning{background:rgba(234,179,8,0.1);color:#a16207;border:1px solid rgba(234,179,8,0.15)}
.hamburger{display:none;background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:6px}
.nav-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999}
.nav-overlay.open{display:block}
.mobile-nav{position:fixed;top:0;right:-280px;width:280px;height:100vh;background:var(--navy);z-index:1001;transition:right .3s ease;padding:80px 24px 24px;overflow-y:auto}
.mobile-nav.open{right:0}
.mobile-nav a{display:block;color:rgba(255,255,255,0.8);text-decoration:none;padding:12px 0;font-size:15px;font-weight:500;border-bottom:1px solid rgba(255,255,255,0.06)}
.mobile-nav a:hover{color:var(--orange)}
.mobile-nav .close-btn{position:absolute;top:16px;right:16px;background:none;border:none;color:#fff;font-size:24px;cursor:pointer}
    .mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
@media(max-width:768px){.navbar{padding:0 16px;height:60px}.hamburger{display:block}.info-grid{grid-template-columns:1fr}h1{font-size:20px}}
.user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}.reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
footer{background:var(--navy-dark);padding:48px 48px 0;margin-top:48px}.footer-inner{max-width:1440px;margin:0 auto}.footer-grid{display:grid;grid-template-columns:280px 1fr 1fr 300px;gap:48px;padding-bottom:48px;border-bottom:1px solid rgba(255,255,255,0.08)}.footer-brand .logo{display:flex;align-items:center;gap:10px;margin-bottom:16px}.footer-brand .logo .icon{width:36px;height:36px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:17px;color:#fff}.footer-brand .logo .name{font-size:18px;font-weight:800;color:#fff}.footer-brand p{font-size:13px;color:#fff;line-height:1.7;margin-bottom:20px}.footer-socials{display:flex;gap:8px}.footer-social-btn{width:34px;height:34px;background:rgba(255,255,255,0.06);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;cursor:pointer;transition:all .2s;text-decoration:none}.footer-social-btn:hover{background:var(--orange);color:#fff}.footer-col h4{font-size:13px;font-weight:700;color:#fff;letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px}.footer-col ul{list-style:none;display:flex;flex-direction:column;gap:10px}.footer-col ul li a{font-size:13px;color:#fff;text-decoration:none;transition:color .2s;display:flex;align-items:center;gap:7px}.footer-col ul li a:hover{color:rgba(255,255,255,0.9)}.footer-col ul li a i{font-size:11px;color:var(--orange);opacity:.7}.contact-item{display:flex;gap:10px;margin-bottom:14px}.contact-item i{color:var(--orange);font-size:14px;margin-top:2px;flex-shrink:0}.contact-item span{font-size:13px;color:#fff;line-height:1.5}.newsletter-label{font-size:13px;color:#fff;margin-bottom:12px;line-height:1.5}.newsletter-form{display:flex;gap:8px}.newsletter-form input{flex:1;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:7px;padding:10px 14px;color:#fff;font-family:'Inter',sans-serif;font-size:13px;outline:none;transition:border-color .2s}.newsletter-form input::placeholder{color:#fff}.newsletter-form input:focus{border-color:rgba(255,255,255,0.3)}.newsletter-form button{background:var(--orange);color:#fff;border:none;border-radius:7px;padding:10px 16px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;white-space:nowrap}.newsletter-form button:hover{background:var(--orange-dark)}.footer-bottom{padding:20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}.footer-bottom p{font-size:12px;color:#fff}.footer-bottom-links{display:flex;gap:20px;flex-wrap:wrap}.footer-bottom-links a{font-size:12px;color:#fff;text-decoration:none;transition:color .2s}.footer-bottom-links a:hover{color:#fff}@media(max-width:1024px){.footer-grid{grid-template-columns:1fr 1fr}}@media(max-width:768px){.navbar{padding:0 16px;height:60px}.hamburger{display:block}footer{padding-left:20px;padding-right:20px}.footer-grid{grid-template-columns:1fr;gap:32px}.footer-bottom{flex-direction:column;text-align:center}}
</style>
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-icon"><i class="fas fa-network-wired"></i></div>
    <div class="nav-logo-text"><span class="brand">BN-Infrastructure</span><span class="tagline">Tanzania</span></div>
  </a>
  <?php echo userNavHtml(); ?>
  <button class="hamburger" onclick="toggleMenu()" style="margin-left:auto;"><i class="fas fa-bars"></i></button>
</nav>
<div class="nav-overlay" id="navOverlay" onclick="toggleMenu()"></div>
<div class="mobile-nav" id="mobileNav">
  <button class="close-btn" onclick="toggleMenu()"><i class="fas fa-times"></i></button>
  <a href="index.php" onclick="toggleMenu()">Home</a>
  <a href="catalog.php" onclick="toggleMenu()">Products</a>
  <a href="about.php" onclick="toggleMenu()">Solutions</a>
  <a href="track.php" onclick="toggleMenu()">Track Order</a>
  <?php echo mobileAccountHtml(); ?>
  <a href="cart.php" onclick="toggleMenu()">Cart</a>
</div>

<div class="page-wrap">
  <h1>Order Tracking</h1>
  <p class="subtitle">Track your order status in real-time</p>

  <div class="search-card reveal">
    <form method="POST">
      <div class="form-group">
        <label>Order Number</label>
        <input type="text" name="order_number" placeholder="e.g. NZ-2024-001234" value="<?php echo htmlspecialchars($_POST['order_number'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Track Order</button>
    </form>
  </div>

  <?php if ($lookedUp): ?>
    <?php if ($order): ?>
      <div class="section-card">
        <div class="card-header"><i class="fas fa-info-circle"></i><h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3></div>
        <div class="info-grid">
          <div class="info-item"><label>Status</label><div class="value"><?php echo ucfirst($order['status']); ?></div></div>
          <div class="info-item"><label>Date</label><div class="value"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div></div>
          <div class="info-item"><label>Total</label><div class="value">TSh <?php echo number_format($order['total'], 0, '.', ','); ?></div></div>
          <div class="info-item"><label>Payment</label><div class="value"><?php echo ucfirst($order['payment_status']); ?></div></div>
        </div>
      </div>

      <?php if (!empty($tracking)): ?>
      <div class="section-card">
        <div class="card-header"><i class="fas fa-truck"></i><h3>Tracking Timeline</h3></div>
        <div class="tracking-timeline">
          <?php foreach ($tracking as $i => $t): ?>
          <div class="tracking-item">
            <div class="tracking-dot <?php echo $i === 0 ? 'active' : ''; ?>"><i class="fas fa-<?php echo $i === 0 ? 'check' : 'circle'; ?>"></i></div>
            <div class="tracking-content">
              <div class="t-status"><?php echo ucfirst($t['status']); ?></div>
              <div class="t-note"><?php echo htmlspecialchars($t['note']); ?></div>
              <div class="t-time"><?php echo date('M d, Y h:i A', strtotime($t['created_at'])); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="section-card">
        <div class="card-header"><i class="fas fa-box"></i><h3>Items</h3></div>
        <?php foreach ($orderItems as $item): ?>
        <div class="order-item">
          <div><span class="oi-name"><?php echo htmlspecialchars($item['product_name']); ?></span> <span class="oi-qty">× <?php echo $item['quantity']; ?></span></div>
          <span class="oi-price">TSh <?php echo number_format($item['total_price'], 0, '.', ','); ?></span>
        </div>
        <?php endforeach; ?>
        <div class="summary-row total"><span>Total</span><span class="value">TSh <?php echo number_format($order['total'], 0, '.', ','); ?></span></div>
      </div>
    <?php else: ?>
      <div class="section-card">
        <div class="not-found">
          <i class="fas fa-search"></i>
          <h3 style="color:var(--navy);margin-bottom:6px;">Order Not Found</h3>
          <p>Please check your order number and email address.</p>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script>
<?php echo userMenuJs(); ?>
<?php echo scrollRevealJs(); ?>
</script>
<script>function toggleMenu(){document.getElementById('mobileNav').classList.toggle('open');document.getElementById('navOverlay').classList.toggle('open')}</script>
<footer>
    <div class="footer-inner">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo"><div class="icon"><i class="fas fa-network-wired"></i></div><span class="name">BN-Infrastructure</span></div>
          <p>Tanzania's leading B2B network infrastructure supplier. Empowering businesses with enterprise-grade connectivity solutions since 2012.</p>
          <div class="footer-socials"><a href="https://linkedin.com" target="_blank" class="footer-social-btn" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a><a href="https://twitter.com" target="_blank" class="footer-social-btn" aria-label="Twitter"><i class="fab fa-twitter"></i></a><a href="https://facebook.com" target="_blank" class="footer-social-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><a href="https://wa.me/255763364721" target="_blank" class="footer-social-btn" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a></div>
        </div>
        <div class="footer-col"><h4>Quick Links</h4><ul><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> All Products</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> New Arrivals</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Best Sellers</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Special Offers</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Request a Quote</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Bulk Orders</a></li></ul></div>
        <div class="footer-col"><h4>Company</h4><ul><li><a href="about.php"><i class="fas fa-chevron-right"></i> About BN-Infrastructure</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Our Brands</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Solutions</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Blog &amp; Resources</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Careers</a></li><li><a href="privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li></ul></div>
        <div class="footer-col"><h4>Contact Us</h4><div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>Plot 45, Mikocheni Light Industrial Area, Dar es Salaam, Tanzania</span></div><div class="contact-item"><i class="fas fa-phone-alt"></i><span>+255 763 364 721 <br>+255 763 364 721</span></div><div class="contact-item"><i class="fas fa-envelope"></i><span>sales@bn-infrastructure.com</span></div><div style="margin-top: 20px;"><h4>Newsletter</h4><div class="newsletter-label">Get product updates and exclusive deals</div><div class="newsletter-form"><input type="email" placeholder="Your email address"><button>Subscribe</button></div></div></div>
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
</body></html>
