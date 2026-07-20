<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/cart.php';
require_once __DIR__ . '/src/functions.php';

$itemCount = cartCount();
$recommendations = [];
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;
    $orgType = $_POST['org_type'] ?? '';
    $users = (int)($_POST['users'] ?? 50);
    $departments = (int)($_POST['departments'] ?? 5);
    $branches = (int)($_POST['branches'] ?? 1);
    $budget = (float)($_POST['budget'] ?? 5000000);
    $needs = $_POST['needs'] ?? [];

    $categories = [];
    if (in_array('routing', $needs) || $branches > 1) $categories[] = 'Routers';
    if (in_array('switching', $needs) || $users > 20) $categories[] = 'Switches';
    if (in_array('wireless', $needs) || $users > 30) $categories[] = 'Wireless Access Points';
    if (in_array('security', $needs)) $categories[] = 'Firewalls';
    if ($users > 50 || $departments > 5) $categories[] = 'Servers';
    if (in_array('cabling', $needs)) $categories[] = 'Cables & Accessories';
    if (empty($categories)) $categories = ['Switches', 'Routers', 'Wireless Access Points'];

    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $allProducts = fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE c.name IN ($placeholders) AND p.stock_status != 'out_of_stock' ORDER BY p.price ASC", $categories);

    $perCategoryBudget = $budget / max(1, count($categories));
    $recommendations = [];
    foreach ($categories as $cat) {
        $catProducts = array_filter($allProducts, function($p) use ($cat) { return $p['category_name'] === $cat; });
        if (!empty($catProducts)) {
            $best = null;
            foreach ($catProducts as $p) {
                if ((float)$p['price'] <= $perCategoryBudget) {
                    $best = $p;
                }
            }
            if (!$best) $best = reset($catProducts);
            $recommendations[$cat] = $best;
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Network Solution Advisor — BN-Infrastructure</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--orange:#F05A22;--bg:#F4F6F9;--card:#FFF;--border:#e2e8f0;--text-primary:#0A2540;--text-secondary:#5a6a7e;--text-muted:#8fa0b3}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-primary);font-size:15px;line-height:1.6}
.announcement-bar{background:var(--orange);color:#fff;text-align:center;padding:9px 24px;font-size:13px;font-weight:500}
.announcement-bar i{margin-right:6px}.announcement-bar span{margin:0 18px;opacity:.7}
.navbar{background:var(--navy);padding:0 48px;display:flex;align-items:center;height:70px;position:sticky;top:0;z-index:1000;box-shadow:0 2px 12px rgba(0,0,0,.2)}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;margin-right:40px}
.nav-logo-icon{width:38px;height:38px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff}
.nav-logo-text .brand{font-size:18px;font-weight:800;color:#fff}.nav-logo-text .tagline{font-size:10px;color:rgba(255,255,255,.5);letter-spacing:.08em;text-transform:uppercase}
.nav-links{display:flex;align-items:center;gap:4px;flex:1}
.nav-links a{text-decoration:none;color:rgba(255,255,255,.75);font-size:14px;font-weight:500;padding:8px 14px;border-radius:6px;transition:color .2s,background .2s}
.nav-links a:hover{color:#fff;background:rgba(255,255,255,.08)}
.nav-actions{display:flex;align-items:center;gap:12px}
.cart-btn{background:var(--orange);border:none;color:#fff;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;font-size:15px}
.cart-badge{position:absolute;top:-5px;right:-5px;background:#fff;color:var(--orange);font-size:10px;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--orange)}
.page-wrap{max-width:900px;margin:0 auto;padding:40px 24px 60px}
h1{font-size:28px;font-weight:800;color:var(--navy);margin-bottom:8px;text-align:center}
.subtitle{color:var(--text-secondary);font-size:14px;margin-bottom:36px;text-align:center}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:28px;box-shadow:0 4px 12px rgba(10,37,64,0.08);margin-bottom:24px}
.form-group{margin-bottom:20px}
.form-group label{display:block;font-size:13px;font-weight:600;color:var(--navy);margin-bottom:6px}
.form-group label .req{color:var(--orange)}
.form-group input,.form-group select{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:9px;font-family:'Inter',sans-serif;font-size:14px;outline:none;transition:border-color .2s}
.form-group input:focus,.form-group select:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(240,90,34,0.1)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.checkbox-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.checkbox-grid label{display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--bg);border:1.5px solid var(--border);border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;transition:all .2s}
.checkbox-grid label:hover{border-color:var(--orange)}
.checkbox-grid input[type="checkbox"]{accent-color:var(--orange);width:16px;height:16px}
.checkbox-grid input[type="checkbox"]:checked + span{color:var(--orange)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px 28px;border-radius:10px;font-family:'Inter',sans-serif;font-size:16px;font-weight:700;cursor:pointer;border:none;transition:all .2s}
.btn-primary{background:var(--orange);color:#fff}.btn-primary:hover{background:#d44d1a;transform:translateY(-2px);box-shadow:0 8px 24px rgba(240,90,34,0.35)}
.btn-outline{background:transparent;color:var(--navy);border:2px solid var(--navy);width:auto;padding:10px 24px;font-size:14px}.btn-outline:hover{background:var(--navy);color:#fff}
.result-section{margin-top:32px}
.result-card{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:16px;display:grid;grid-template-columns:80px 1fr auto;gap:16px;align-items:center}
.result-card img{width:80px;height:80px;object-fit:contain;background:var(--card);border-radius:8px;padding:8px}
.result-card h4{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:4px}
.result-card p{font-size:12px;color:var(--text-secondary)}
.result-card .price{font-size:18px;font-weight:800;color:var(--navy);text-align:right}
.result-card .cat-label{font-size:10px;font-weight:700;color:var(--orange);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}
.total-bar{background:var(--navy);color:#fff;padding:20px 24px;border-radius:12px;display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.total-bar .total-label{font-size:14px;font-weight:500;opacity:.8}
.total-bar .total-amount{font-size:24px;font-weight:900}
footer{background:var(--navy-dark);padding:48px 48px 0;margin-top:48px}
@media(max-width:768px){.form-row,.checkbox-grid{grid-template-columns:1fr}.result-card{grid-template-columns:1fr;text-align:center}.result-card img{margin:0 auto}.result-card .price{text-align:center}}
</style>
</head>
<body>
<div class="announcement-bar"><i class="fas fa-truck"></i> Free delivery on orders above TSh 500,000 <span>|</span> Serving all regions in Tanzania</div>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><div class="nav-logo-icon"><i class="fas fa-network-wired"></i></div><div class="nav-logo-text"><span class="brand">BN-Infrastructure</span><span class="tagline">Tanzania</span></div></a>
  <div class="nav-links"><a href="index.php">Home</a><a href="catalog.php">Products</a><a href="recommend.php" style="color:#fff;background:rgba(255,255,255,0.1);">Advisor</a><a href="about.php">Solutions</a></div>
  <div class="nav-actions"><a href="cart.php" class="cart-btn"><i class="fas fa-shopping-cart"></i><span class="cart-badge"><?php echo $itemCount; ?></span></a></div>
</nav>

<div class="page-wrap">
  <h1><i class="fas fa-magic" style="color:var(--orange);"></i> Network Solution Advisor</h1>
  <p class="subtitle">Tell us about your organization and we'll recommend the right networking equipment</p>

  <form method="POST">
    <div class="card">
      <h3 style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:16px;"><i class="fas fa-building" style="color:var(--orange);margin-right:8px;"></i>Organization Details</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Organization Type <span class="req">*</span></label>
          <select name="org_type" required>
            <option value="">Select type...</option>
            <option value="ISP">ISP / Telecommunications</option>
            <option value="enterprise">Enterprise / Corporate</option>
            <option value="government">Government / Public Sector</option>
            <option value="education">Educational Institution</option>
            <option value="sme">SME / Small Business</option>
            <option value="reseller">Reseller / System Integrator</option>
          </select>
        </div>
        <div class="form-group">
          <label>Number of Users/Employees <span class="req">*</span></label>
          <input type="number" name="users" value="50" min="1" max="10000" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Number of Departments</label>
          <input type="number" name="departments" value="5" min="1" max="100">
        </div>
        <div class="form-group">
          <label>Number of Branch Locations</label>
          <input type="number" name="branches" value="1" min="1" max="50">
        </div>
      </div>
      <div class="form-group">
        <label>Estimated Budget (TSh) <span class="req">*</span></label>
        <input type="number" name="budget" value="5000000" min="100000" step="100000" required>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Your total equipment budget in Tanzanian Shillings</div>
      </div>
    </div>

    <div class="card">
      <h3 style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:16px;"><i class="fas fa-list-check" style="color:var(--orange);margin-right:8px;"></i>What do you need?</h3>
      <div class="checkbox-grid">
        <label><input type="checkbox" name="needs[]" value="routing"> <span><i class="fas fa-route" style="color:var(--orange);margin-right:4px;"></i> Routing</span></label>
        <label><input type="checkbox" name="needs[]" value="switching" checked> <span><i class="fas fa-network-wired" style="color:var(--orange);margin-right:4px;"></i> Switching</span></label>
        <label><input type="checkbox" name="needs[]" value="wireless" checked> <span><i class="fas fa-wifi" style="color:var(--orange);margin-right:4px;"></i> Wireless</span></label>
        <label><input type="checkbox" name="needs[]" value="security"> <span><i class="fas fa-shield-alt" style="color:var(--orange);margin-right:4px;"></i> Security / Firewall</span></label>
        <label><input type="checkbox" name="needs[]" value="servers"> <span><i class="fas fa-server" style="color:var(--orange);margin-right:4px;"></i> Servers / Storage</span></label>
        <label><input type="checkbox" name="needs[]" value="cabling"> <span><i class="fas fa-plug" style="color:var(--orange);margin-right:4px;"></i> Cabling & Accessories</span></label>
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-magic"></i> Get Recommendations</button>
  </form>

  <?php if ($submitted && !empty($recommendations)): ?>
  <div class="result-section">
    <h2 style="font-size:22px;font-weight:800;color:var(--navy);margin-bottom:20px;"><i class="fas fa-check-circle" style="color:#059669;"></i> Recommended Equipment</h2>

    <?php
    $totalCost = 0;
    foreach ($recommendations as $cat => $p): $totalCost += (float)$p['price']; ?>
    <div class="result-card">
      <?php echo imageOrPlaceholder($p['image'], $p['name'], $p['brand'] ?? ''); ?>
      <div>
        <div class="cat-label"><?php echo htmlspecialchars($cat); ?></div>
        <h4><?php echo htmlspecialchars($p['name']); ?></h4>
        <p><?php echo htmlspecialchars($p['brand'] ?: ''); ?> | <?php echo htmlspecialchars($p['sku']); ?></p>
        <p style="margin-top:4px;"><?php echo htmlspecialchars(substr($p['specs'] ?? $p['description'] ?? '', 0, 120)); ?>...</p>
        <a href="product.php?id=<?php echo $p['id']; ?>" style="font-size:12px;color:var(--orange);font-weight:600;text-decoration:none;margin-top:6px;display:inline-block;">View Details & Order <i class="fas fa-arrow-right" style="font-size:10px;"></i></a>
      </div>
      <div class="price">TSh <?php echo number_format((float)$p['price'], 0, '.', ','); ?></div>
    </div>
    <?php endforeach; ?>

    <div class="total-bar">
      <div><div class="total-label">Estimated Total</div><div class="total-amount">TSh <?php echo number_format($totalCost, 0, '.', ','); ?></div></div>
      <a href="cart.php" class="btn btn-outline" style="border-color:rgba(255,255,255,.3);color:#fff;"><i class="fas fa-shopping-cart"></i> Add All to Cart</a>
    </div>

    <p style="font-size:12px;color:var(--text-muted);text-align:center;">These are recommendations based on your requirements. Contact our sales team for custom configurations and volume discounts.</p>
  </div>
  <?php elseif ($submitted): ?>
  <div style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fas fa-info-circle" style="font-size:40px;margin-bottom:12px;display:block;opacity:.4;"></i><p>No matching products found. Please adjust your requirements or contact our sales team.</p></div>
  <?php endif; ?>
</div>
</body></html>
