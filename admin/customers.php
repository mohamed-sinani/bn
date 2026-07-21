<?php
require_once '../config/database.php';
require_once '../src/functions.php';
require_once '../src/auth.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$message = '';

if ($action === 'view' && $id) {
    $customer = fetchOne("SELECT * FROM users WHERE id = ? AND role = 'customer'", [$id]);
    if (!$customer) { header('Location: /admin/customers.php'); exit; }
    $orders = fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC", [$id]);
    $totalSpent = fetchOne("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE user_id = ? AND status != 'cancelled'", [$id])['t'];
    $totalOrders = count($orders);
} else {
    $page = max(1, (int)($_GET['p'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    $search = $_GET['search'] ?? '';

    $where = ["role = 'customer'"];
    $params = [];
    if ($search) {
        $where[] = "(full_name LIKE ? OR email LIKE ? OR company LIKE ? OR phone LIKE ?)";
        $s = "%$search%";
        $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
    }
    $ws = 'WHERE ' . implode(' AND ', $where);
    $total = fetchOne("SELECT COUNT(*) as c FROM users $ws", $params)['c'];
    $customers = fetchAll("SELECT * FROM users $ws ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);
    $totalPages = ceil($total / $perPage);
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--orange:#F05A22;--bg:#F4F6F9;--card:#FFF;--border:#e2e8f0;--shadow-sm:0 1px 3px rgba(10,37,64,0.06)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:#1a1a2e;font-size:14px;display:flex}
.sidebar{width:240px;background:var(--navy);min-height:100vh;padding:24px 0;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar h2{color:#fff;font-size:18px;padding:0 20px;margin-bottom:24px;display:flex;align-items:center;gap:8px}
.sidebar h2 i{color:var(--orange)}
.sidebar a{display:block;padding:10px 20px;color:rgba(255,255,255,0.7);text-decoration:none;font-size:13px;font-weight:500;transition:all .15s}
.sidebar a:hover,.sidebar a.active{background:rgba(255,255,255,0.08);color:#fff;border-left:3px solid var(--orange)}
.main{flex:1;padding:24px 32px;max-width:calc(100vw - 240px)}
h1{font-size:22px;font-weight:800;color:var(--navy);margin-bottom:4px}
.subtitle{color:#666;font-size:13px;margin-bottom:20px}
.card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:18px 20px;box-shadow:var(--shadow-sm)}
.filters{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.filters input,.filters select{padding:8px 12px;border:1.5px solid var(--border);border-radius:6px;font-family:'Inter',sans-serif;font-size:13px;outline:none}
.filters input:focus,.filters select:focus{border-color:var(--orange)}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:10px 12px;font-size:11px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid var(--border)}
td{padding:10px 12px;font-size:13px;border-bottom:1px solid var(--border);color:var(--navy)}
tr:hover td{background:rgba(240,90,34,0.02)}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600}
.badge.active{background:rgba(5,150,105,0.12);color:#059669}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;font-family:'Inter',sans-serif;font-size:12px;font-weight:600;cursor:pointer;border:none;text-decoration:none}
.btn-primary{background:var(--orange);color:#fff}
.btn-outline{background:transparent;color:var(--navy);border:1.5px solid var(--border)}
.btn-sm{padding:5px 10px;font-size:11px}
.info-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:20px}
.info-item label{font-size:11px;font-weight:600;color:#888;text-transform:uppercase}
.info-item .value{font-size:14px;font-weight:600;color:var(--navy);margin-top:2px}
.back-link{margin-bottom:16px;display:inline-block;color:var(--orange);text-decoration:none;font-size:13px;font-weight:600}
.pagination{display:flex;gap:6px;justify-content:center;margin-top:20px}
.pagination a{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid var(--border);border-radius:6px;text-decoration:none;font-size:13px;color:var(--navy);font-weight:500}
.pagination a.active{background:var(--orange);color:#fff;border-color:var(--orange)}
@media(max-width:768px){.sidebar{width:60px}.sidebar h2 span,.sidebar a span{display:none}.main{max-width:calc(100vw - 60px);padding:16px}.info-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="sidebar">
  <h2><i class="fas fa-cube"></i><span>Admin</span></h2>
  <a href="index.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
  <a href="products.php"><i class="fas fa-box"></i> <span>Products</span></a>
  <a href="products-add.php"><i class="fas fa-plus-circle"></i> <span>Add Product</span></a>
  <a href="categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a>
  <a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a>
  <a href="payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
  <a href="quotes.php"><i class="fas fa-file-alt"></i> <span>Quotes</span></a>
  <a href="customers.php" class="active"><i class="fas fa-users"></i> <span>Customers</span></a>
  <a href="company-users.php"><i class="fas fa-user-shield"></i> <span>Authorized Users</span></a>
  <a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
  <a href="logout.php" style="margin-top:40px;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
</div>
<div class="main">
  <?php if ($action === 'view' && isset($customer)): ?>
    <a href="customers.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Customers</a>
    <h1><?php echo htmlspecialchars($customer['full_name']); ?></h1>
    <p class="subtitle">Customer since <?php echo date('M d, Y', strtotime($customer['created_at'])); ?></p>

    <div class="card" style="margin-bottom:20px;">
      <div class="info-grid">
        <div class="info-item"><label>Email</label><div class="value"><?php echo htmlspecialchars($customer['email']); ?></div></div>
        <div class="info-item"><label>Phone</label><div class="value"><?php echo htmlspecialchars($customer['phone'] ?: '-'); ?></div></div>
        <div class="info-item"><label>Company</label><div class="value"><?php echo htmlspecialchars($customer['company'] ?: '-'); ?></div></div>
        <div class="info-item"><label>Business Type</label><div class="value"><?php echo htmlspecialchars($customer['business_type'] ?: '-'); ?></div></div>
        <div class="info-item"><label>Total Orders</label><div class="value"><?php echo $totalOrders; ?></div></div>
        <div class="info-item"><label>Total Spent</label><div class="value">TSh <?php echo number_format($totalSpent, 0, '.', ','); ?></div></div>
      </div>
    </div>

    <div class="card">
      <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:12px;">Order History</h3>
      <table><thead><tr><th>Order #</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php if (empty($orders)): ?>
      <tr><td colspan="5" style="text-align:center;padding:20px;color:#888;">No orders yet</td></tr>
      <?php endif; ?>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td style="font-weight:700;"><?php echo htmlspecialchars($o['order_number']); ?></td>
        <td>TSh <?php echo number_format($o['total'], 0, '.', ','); ?></td>
        <td><span class="badge <?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
        <td style="font-size:12px;color:#888;"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
        <td><a href="orders.php?action=view&id=<?php echo $o['id']; ?>" class="btn btn-outline btn-sm">View</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>

  <?php else: ?>
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
      <div><h1>Customers</h1><p class="subtitle"><?php echo $total; ?> registered customers</p></div>
    </div>
    <form method="GET" class="filters">
      <input type="text" name="search" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
    </form>
    <div class="card" style="overflow-x:auto;">
      <table><thead><tr><th>Name</th><th>Email</th><th>Company</th><th>Phone</th><th>Joined</th><th></th></tr></thead>
      <tbody>
      <?php if (empty($customers)): ?>
      <tr><td colspan="6" style="text-align:center;padding:40px;color:#888;">No customers found</td></tr>
      <?php endif; ?>
      <?php foreach ($customers as $c): ?>
      <tr>
        <td style="font-weight:600;"><?php echo htmlspecialchars($c['full_name']); ?></td>
        <td><?php echo htmlspecialchars($c['email']); ?></td>
        <td><?php echo htmlspecialchars($c['company'] ?: '-'); ?></td>
        <td><?php echo htmlspecialchars($c['phone'] ?: '-'); ?></td>
        <td style="font-size:12px;color:#888;"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
        <td><a href="?action=view&id=<?php echo $c['id']; ?>" class="btn btn-outline btn-sm">View</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="pagination"><?php for ($i=1;$i<=$totalPages;$i++): ?><a href="?p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $i===$page?'active':''; ?>"><?php echo $i; ?></a><?php endfor; ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body></html>
