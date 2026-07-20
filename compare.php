<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/cart.php';
require_once __DIR__ . '/src/functions.php';

$ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
$products = [];
if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $products = fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id IN ($placeholders)", $ids);
}

$itemCount = cartCount();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Compare Products — BN-Infrastructure</title>
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
.page-wrap{max-width:1400px;margin:0 auto;padding:40px 48px 60px}
h1{font-size:28px;font-weight:800;color:var(--navy);margin-bottom:8px}
.subtitle{color:var(--text-secondary);font-size:14px;margin-bottom:32px}
.empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}
.empty-state i{font-size:60px;margin-bottom:16px;display:block;opacity:.3}
.empty-state p{font-size:16px;margin-bottom:20px}
.compare-table{width:100%;border-collapse:collapse;background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden}
.compare-table th,.compare-table td{padding:14px 20px;text-align:center;border-bottom:1px solid var(--border);font-size:13px}
.compare-table th{background:var(--bg);color:var(--text-muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;text-align:left;width:180px}
.compare-table td{color:var(--text-primary);vertical-align:top}
.compare-table td img{max-width:140px;max-height:100px;object-fit:contain;margin:0 auto 8px;display:block}
.compare-table td .name{font-weight:700;font-size:14px;color:var(--navy);margin-bottom:4px}
.compare-table td .price{font-weight:800;font-size:16px;color:var(--navy)}
.compare-table td .old-price{text-decoration:line-through;color:var(--text-muted);font-size:12px;margin-left:6px}
.compare-table tr:hover td{background:rgba(240,90,34,0.02)}
.badge{display:inline-block;padding:3px 8px;border-radius:4px;font-size:10px;font-weight:700}
.badge-stock{background:rgba(5,150,105,0.12);color:#059669;border:1px solid rgba(5,150,105,0.3)}
.badge-low{background:rgba(245,158,11,0.12);color:#d97706}
.badge-out{background:rgba(239,68,68,0.1);color:#dc2626}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .2s}
.btn-primary{background:var(--orange);color:#fff}.btn-primary:hover{background:#d44d1a}
.btn-outline{background:transparent;color:var(--navy);border:1.5px solid var(--border)}.btn-outline:hover{border-color:var(--orange);color:var(--orange)}
.btn-sm{padding:7px 14px;font-size:12px}
footer{background:var(--navy-dark);padding:48px 48px 0;margin-top:48px}
</style>
</head>
<body>
<div class="announcement-bar"><i class="fas fa-truck"></i> Free delivery on orders above TSh 500,000 <span>|</span> Serving all regions in Tanzania</div>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><div class="nav-logo-icon"><i class="fas fa-network-wired"></i></div><div class="nav-logo-text"><span class="brand">BN-Infrastructure</span><span class="tagline">Tanzania</span></div></a>
  <div class="nav-links"><a href="index.php">Home</a><a href="catalog.php">Products</a><a href="about.php">Solutions</a></div>
  <div class="nav-actions"><a href="cart.php" class="cart-btn"><i class="fas fa-shopping-cart"></i><span class="cart-badge"><?php echo $itemCount; ?></span></a></div>
</nav>

<div class="page-wrap">
  <h1><i class="fas fa-exchange-alt" style="color:var(--orange);margin-right:10px;"></i>Compare Products</h1>
  <p class="subtitle">Side-by-side comparison of selected networking products</p>

  <?php if (empty($products)): ?>
  <div class="empty-state">
    <i class="fas fa-balance-scale"></i>
    <p>No products selected for comparison.</p>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">Browse our catalog and click the Compare button on products to add them.</p>
    <a href="catalog.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Browse Products</a>
  </div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="compare-table">
    <thead><tr>
      <th></th>
      <?php foreach ($products as $p): ?>
      <td>
        <a href="product.php?id=<?php echo $p['id']; ?>" style="text-decoration:none;">
          <?php echo imageOrPlaceholder($p['image'], $p['name'], $p['brand'] ?? ''); ?>
          <div class="name"><?php echo htmlspecialchars($p['name']); ?></div>
        </a>
      </td>
      <?php endforeach; ?>
    </tr></thead>
    <tbody>
      <tr><th>Price</th><?php foreach ($products as $p): ?><td><span class="price">TSh <?php echo number_format((float)$p['price'], 0, '.', ','); ?></span><?php if ($p['old_price']): ?><span class="old-price">TSh <?php echo number_format((float)$p['old_price'], 0, '.', ','); ?></span><?php endif; ?></td><?php endforeach; ?></tr>
      <tr><th>Brand</th><?php foreach ($products as $p): ?><td><?php echo htmlspecialchars($p['brand'] ?: '-'); ?></td><?php endforeach; ?></tr>
      <tr><th>Category</th><?php foreach ($products as $p): ?><td><?php echo htmlspecialchars($p['category_name'] ?: '-'); ?></td><?php endforeach; ?></tr>
      <tr><th>SKU</th><?php foreach ($products as $p): ?><td style="font-size:12px;color:var(--text-muted);"><?php echo htmlspecialchars($p['sku']); ?></td><?php endforeach; ?></tr>
      <tr><th>Stock</th><?php foreach ($products as $p): ?><td><?php echo getStockBadge($p['stock_status']); ?></td><?php endforeach; ?></tr>
      <tr><th>MOQ</th><?php foreach ($products as $p): ?><td><?php echo $p['moq']; ?> unit<?php echo $p['moq'] > 1 ? 's' : ''; ?></td><?php endforeach; ?></tr>
      <tr><th>Warranty</th><?php foreach ($products as $p): ?><td><?php echo htmlspecialchars($p['warranty'] ?: '1 Year'); ?></td><?php endforeach; ?></tr>
      <tr><th>Description</th><?php foreach ($products as $p): ?><td style="text-align:left;font-size:12px;color:var(--text-secondary);max-width:200px;"><?php echo nl2br(htmlspecialchars(substr($p['description'] ?? '', 0, 200))); ?><?php echo strlen($p['description'] ?? '') > 200 ? '...' : ''; ?></td><?php endforeach; ?></tr>
      <tr><th>Action</th><?php foreach ($products as $p): ?><td><a href="product.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">View Details</a></td><?php endforeach; ?></tr>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
</body></html>
