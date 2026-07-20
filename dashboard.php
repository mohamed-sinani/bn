<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if (!$user) {
    // Session is invalid or user was deleted
    session_destroy();
    header('Location: login.php');
    exit;
}

$orders = fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC", [$user_id]);
$brandName = 'BN-Infrastructure';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard — <?php echo $brandName; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--navy-dark:#06182c;--orange:#F05A22;--orange-dark:#d44d1a;--bg:#F4F6F9;--card:#FFF;--text-primary:#0A2540;--text-secondary:#5a6a7e;--text-muted:#8fa0b3;--border:#e2e8f0;--shadow-sm:0 1px 3px rgba(10,37,64,0.08)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-primary);font-size:15px;line-height:1.6}
.navbar{background:var(--navy);padding:0 48px;display:flex;align-items:center;height:70px}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.nav-logo-icon{width:38px;height:38px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff}
.nav-logo-text{display:flex;flex-direction:column;line-height:1.1}
.nav-logo-text .brand{font-size:18px;font-weight:800;color:#fff}
.nav-logo-text .tagline{font-size:10px;font-weight:400;color:#fff;letter-spacing:0.08em;text-transform:uppercase}
.page-wrap{max-width:1100px;margin:0 auto;padding:48px 24px}
h1{font-size:28px;font-weight:800;color:var(--navy);margin-bottom:8px}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 20px;border-radius:9px;font-family:'Inter',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all 0.2s;}
.btn-primary{background:var(--orange);color:#fff}
.btn-primary:hover{background:var(--orange-dark)}

.dashboard-grid { display: grid; grid-template-columns: 320px 1fr; gap: 32px; margin-top: 32px; }
@media (max-width: 900px) { .dashboard-grid { grid-template-columns: 1fr; } }

.profile-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 28px; box-shadow: var(--shadow-sm); }
.profile-card h3 { font-size: 16px; font-weight: 700; color: var(--navy); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.profile-card h3 i { color: var(--orange); }
.profile-detail { margin-bottom: 16px; }
.profile-detail:last-child { margin-bottom: 0; }
.profile-detail label { display: block; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; }
.profile-detail .value { font-size: 14px; font-weight: 600; color: var(--navy); }

.orders-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; box-shadow: var(--shadow-sm); }
.orders-card .card-header { padding: 20px 28px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; }
.orders-card .card-header h3 { font-size: 16px; font-weight: 700; color: var(--navy); margin: 0; }
.orders-card .card-header i { color: var(--orange); }

.orders-table { width: 100%; border-collapse: collapse; }
.orders-table th { background: #f8fafc; padding: 14px 28px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); border-bottom: 1px solid var(--border); text-transform: uppercase; letter-spacing: 0.05em; }
.orders-table td { padding: 18px 28px; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; }
.orders-table tr:last-child td { border-bottom: none; }
.orders-table tr:hover td { background: #fdfdfd; }
.order-num { font-weight: 700; color: var(--navy); font-family: monospace; font-size: 15px; }
.order-date { color: var(--text-secondary); font-size: 13px; }
.order-total { font-weight: 800; color: var(--navy); }

.empty-state { padding: 60px 20px; text-align: center; color: var(--text-muted); }
.empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 20px; }
.empty-state p { margin-bottom: 24px; font-size: 15px; }

.badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; letter-spacing: 0.03em; text-transform: uppercase; display: inline-flex; align-items: center; }
.badge-stock { background: rgba(5,150,105,0.1); color: #059669; }
.badge-low { background: rgba(217,119,6,0.1); color: #d97706; }
.badge-out { background: rgba(220,38,38,0.1); color: #dc2626; }

.hamburger{display:none;background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:6px}
.nav-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999}
.nav-overlay.open{display:block}
.mobile-nav{position:fixed;top:0;right:-280px;width:280px;height:100vh;background:var(--navy);z-index:1001;transition:right .3s ease;padding:80px 24px 24px;overflow-y:auto}
.mobile-nav.open{right:0}
.mobile-nav a{display:block;color:rgba(255,255,255,0.8);text-decoration:none;padding:12px 0;font-size:15px;font-weight:500;border-bottom:1px solid rgba(255,255,255,0.06)}
.mobile-nav a:hover{color:var(--orange)}
.mobile-nav .close-btn{position:absolute;top:16px;right:16px;background:none;border:none;color:#fff;font-size:24px;cursor:pointer}
.mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}
.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
@media(max-width:768px){.navbar{padding:0 16px;height:60px}.hamburger{display:block}h1{font-size:24px} .orders-table th, .orders-table td { padding: 14px 16px; } }

<?php echo userMenuCss(); ?>
<?php echo revealCss(); ?>
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
  <div style="display:flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <h1>Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>!</h1>
    <a href="catalog.php" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Browse Products</a>
  </div>

  <div class="dashboard-grid">
    <div class="dashboard-sidebar">
      <div class="profile-card reveal">
        <h3><i class="far fa-id-card"></i> Account Details</h3>
        <div class="profile-detail"><label>Full Name</label><div class="value"><?php echo htmlspecialchars($user['full_name'] ?: '-'); ?></div></div>
        <div class="profile-detail"><label>Email Address</label><div class="value"><?php echo htmlspecialchars($user['email']); ?></div></div>
        <div class="profile-detail"><label>Phone Number</label><div class="value"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></div></div>
        <div class="profile-detail"><label>Company</label><div class="value"><?php echo htmlspecialchars($user['company'] ?: '-'); ?></div></div>
        <div class="profile-detail"><label>Account Type</label><div class="value"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></div></div>
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border);">
            <a href="company-users.php" class="btn" style="background: var(--bg); color: var(--text-secondary); border: 1px solid var(--border); width: 100%; margin-bottom: 10px;"><i class="fas fa-users"></i> Team Members</a>
            <a href="?logout=1" class="btn" style="background: var(--bg); color: var(--text-secondary); border: 1px solid var(--border); width: 100%;"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </div>
      </div>
    </div>
    
    <div class="dashboard-main">
      <div class="orders-card reveal" style="transition-delay: 0.1s;">
        <div class="card-header">
          <i class="fas fa-box"></i><h3>Order History</h3>
        </div>
        <?php if (empty($orders)): ?>
          <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <p>You haven't placed any orders yet.</p>
            <a href="catalog.php" class="btn btn-primary" style="display: inline-flex;">Start Shopping</a>
          </div>
        <?php else: ?>
          <div style="overflow-x: auto;">
            <table class="orders-table">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Total</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                  <td>
                    <div class="order-num"><?php echo htmlspecialchars($o['order_number']); ?></div>
                  </td>
                  <td>
                    <div class="order-date"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></div>
                  </td>
                  <td>
                    <?php echo getStatusBadge($o['status']); ?>
                  </td>
                  <td class="order-total">
                    TSh <?php echo number_format($o['total'], 0, '.', ','); ?>
                  </td>
                  <td>
                    <form action="track.php" method="POST" style="margin:0;">
                        <input type="hidden" name="order_number" value="<?php echo htmlspecialchars($o['order_number']); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                        <button type="submit" style="background:none; border:none; color:var(--orange); font-weight:700; font-size:13px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; font-family:'Inter',sans-serif;"><i class="fas fa-external-link-alt"></i> Track</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="logo">
          <div class="icon"><i class="fas fa-network-wired"></i></div>
          <div class="name">BN-Infrastructure</div>
        </div>
        <p>Your trusted partner for enterprise networking equipment, fiber optics, and server infrastructure across East Africa.</p>
        <div class="footer-socials">
          <a href="#" class="footer-social-btn"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" class="footer-social-btn"><i class="fab fa-twitter"></i></a>
          <a href="#" class="footer-social-btn"><i class="fab fa-facebook-f"></i></a>
        </div>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Products</a></li>
          <li><a href="about.php"><i class="fas fa-chevron-right"></i> Solutions</a></li>
          <li><a href="track.php"><i class="fas fa-chevron-right"></i> Order Tracking</a></li>
          <li><a href="login.php"><i class="fas fa-chevron-right"></i> My Account</a></li>
          <li><a href="about.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact Us</h4>
        <div class="contact-item"><i class="fas fa-map-marker-alt"></i> <span>123 Maktaba Street<br>Dar es Salaam, Tanzania</span></div>
        <div class="contact-item"><i class="fas fa-phone-alt"></i> <span>+255 763 364 721</span></div>
        <div class="contact-item"><i class="fas fa-envelope"></i> <span>sales@bn-infrastructure.com</span></div>
      </div>
      <div class="footer-col">
        <h4>Newsletter</h4>
        <p class="newsletter-label">Subscribe to get updates on new products and special offers.</p>
        <form class="newsletter-form" onsubmit="event.preventDefault();alert('Subscribed successfully!');">
          <input type="email" placeholder="Your email address" required>
          <button type="submit">Subscribe</button>
        </form>
      </div>
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
function toggleMenu(){
  document.getElementById('mobileNav').classList.toggle('open');
  document.getElementById('navOverlay').classList.toggle('open');
}
<?php echo userMenuJs(); ?>
<?php echo scrollRevealJs(); ?>
</script>
</body>
</html>
