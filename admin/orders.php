<?php
require_once '../config/database.php';
require_once '../src/functions.php';
require_once '../src/auth.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$message = '';

if ($action === 'update_status' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $allowed = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($status, $allowed)) {
        execute("UPDATE orders SET status = ? WHERE id = ?", [$status, $id]);
        execute("INSERT INTO order_tracking (order_id, status, note) VALUES (?, ?, ?)", [$id, $status, $note ?: 'Status updated to ' . $status]);
        $message = 'Order status updated.';
    }
}

if ($action === 'view' && $id) {
    $order = fetchOne("SELECT * FROM orders WHERE id = ?", [$id]);
    if (!$order) { header('Location: /admin/orders.php'); exit; }
    $items = fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$id]);
    $tracking = fetchAll("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at ASC", [$id]);
} else {
    $page = max(1, (int)($_GET['p'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    $search = $_GET['search'] ?? '';
    $statusFilter = $_GET['status'] ?? '';

    $where = [];
    $params = [];
    if ($search) {
        $where[] = "(order_number LIKE ? OR full_name LIKE ? OR email LIKE ?)";
        $s = "%$search%";
        $params[] = $s; $params[] = $s; $params[] = $s;
    }
    if ($statusFilter) {
        $where[] = "status = ?";
        $params[] = $statusFilter;
    }
    $ws = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $total = fetchOne("SELECT COUNT(*) as c FROM orders $ws", $params)['c'];
    $orders = fetchAll("SELECT * FROM orders $ws ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);
    $totalPages = ceil($total / $perPage);
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
th{text-align:left;padding:10px 12px;font-size:11px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid var(--border);white-space:nowrap}
td{padding:10px 12px;font-size:13px;border-bottom:1px solid var(--border);color:var(--navy)}
tr:hover td{background:rgba(240,90,34,0.02)}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600}
.badge.pending{background:rgba(234,179,8,0.12);color:#a16207}
.badge.processing{background:rgba(59,130,246,0.12);color:#1d4ed8}
.badge.shipped{background:rgba(168,85,247,0.12);color:#7c3aed}
.badge.delivered{background:rgba(5,150,105,0.12);color:#059669}
.badge.cancelled{background:rgba(220,38,38,0.12);color:#dc2626}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;font-family:'Inter',sans-serif;font-size:12px;font-weight:600;cursor:pointer;border:none;text-decoration:none}
.btn-primary{background:var(--orange);color:#fff}
.btn-primary:hover{background:#d44d1a}
.btn-outline{background:transparent;color:var(--navy);border:1.5px solid var(--border)}
.btn-sm{padding:5px 10px;font-size:11px}
.pagination{display:flex;gap:6px;justify-content:center;margin-top:20px}
.pagination a{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid var(--border);border-radius:6px;text-decoration:none;font-size:13px;color:var(--navy);font-weight:500}
.pagination a.active{background:var(--orange);color:#fff;border-color:var(--orange)}
.alert{padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:14px;background:rgba(5,150,105,0.08);color:#059669;border:1px solid rgba(5,150,105,0.15)}
.info-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:20px}
.info-item label{font-size:11px;font-weight:600;color:#888;text-transform:uppercase}
.info-item .value{font-size:14px;font-weight:600;color:var(--navy);margin-top:2px}
.back-link{margin-bottom:16px;display:inline-block;color:var(--orange);text-decoration:none;font-size:13px;font-weight:600}
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
  <a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a>
  <a href="payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
  <a href="quotes.php"><i class="fas fa-file-alt"></i> <span>Quotes</span></a>
  <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
  <a href="company-users.php"><i class="fas fa-user-shield"></i> <span>Authorized Users</span></a>
  <a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
  <a href="logout.php" style="margin-top:40px;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
</div>

<div class="main">
  <?php if ($action === 'view' && isset($order)): ?>
    <a href="orders.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Orders</a>
    <h1>Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
    <p class="subtitle">Placed on <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>

    <?php if ($message): ?><div class="alert"><?php echo $message; ?></div><?php endif; ?>

    <div class="card" style="margin-bottom:20px;">
      <div class="info-grid">
        <div class="info-item"><label>Status</label><div class="value"><span class="badge <?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></div></div>
        <div class="info-item"><label>Payment</label><div class="value"><span class="badge <?php echo $order['payment_status']; ?>"><?php echo ucfirst($order['payment_status']); ?></span></div></div>
        <div class="info-item"><label>Total</label><div class="value">TSh <?php echo number_format($order['total'], 0, '.', ','); ?></div></div>
        <div class="info-item"><label>Customer</label><div class="value"><?php echo htmlspecialchars($order['full_name']); ?></div></div>
        <div class="info-item"><label>Email</label><div class="value"><?php echo htmlspecialchars($order['email']); ?></div></div>
        <div class="info-item"><label>Phone</label><div class="value"><?php echo htmlspecialchars($order['phone']); ?></div></div>
        <div class="info-item"><label>Company</label><div class="value"><?php echo htmlspecialchars($order['company_name'] ?: '-'); ?></div></div>
        <div class="info-item"><label>Region</label><div class="value"><?php echo htmlspecialchars($order['region']); ?></div></div>
        <div class="info-item"><label>Method</label><div class="value"><?php echo str_replace('_', ' ', ucwords($order['payment_method'], '_')); ?></div></div>
      </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
      <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:12px;"><i class="fas fa-box"></i> Items</h3>
      <table><thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr><td><?php echo htmlspecialchars($item['product_name']); ?></td><td style="color:#888;font-size:12px;"><?php echo htmlspecialchars($item['product_sku']); ?></td><td><?php echo $item['quantity']; ?></td><td>TSh <?php echo number_format($item['unit_price'], 0, '.', ','); ?></td><td>TSh <?php echo number_format($item['total_price'], 0, '.', ','); ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
      <div style="margin-top:12px;border-top:2px solid var(--border);padding-top:12px;text-align:right;font-size:15px;font-weight:800;color:var(--navy);">Total: TSh <?php echo number_format($order['total'], 0, '.', ','); ?></div>
    </div>

    <?php if (!empty($tracking)): ?>
    <div class="card" style="margin-bottom:20px;">
      <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:12px;"><i class="fas fa-history"></i> Tracking History</h3>
      <?php foreach ($tracking as $t): ?>
      <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px;">
        <span class="badge <?php echo $t['status']; ?>" style="min-width:80px;text-align:center;"><?php echo ucfirst($t['status']); ?></span>
        <span style="color:#666;flex:1;"><?php echo htmlspecialchars($t['note']); ?></span>
        <span style="color:#888;font-size:12px;"><?php echo date('M d, Y h:i A', strtotime($t['created_at'])); ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card">
      <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:12px;"><i class="fas fa-edit"></i> Update Status</h3>
      <form method="POST" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
        <input type="hidden" name="status" id="statusVal" value="">
        <div>
          <label style="display:block;font-size:11px;font-weight:600;color:#888;margin-bottom:4px;">New Status</label>
          <select id="statusSelect" style="padding:8px 12px;border:1.5px solid var(--border);border-radius:6px;font-family:'Inter',sans-serif;font-size:13px;" onchange="document.getElementById('statusVal').value=this.value">
            <option value="">Select...</option>
            <option value="confirmed">Confirmed</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div style="flex:1;min-width:200px;">
          <label style="display:block;font-size:11px;font-weight:600;color:#888;margin-bottom:4px;">Note (optional)</label>
          <input type="text" name="note" placeholder="e.g. Dispatched via courier" style="width:100%;padding:8px 12px;border:1.5px solid var(--border);border-radius:6px;font-family:'Inter',sans-serif;font-size:13px;">
        </div>
        <button type="submit" class="btn btn-primary">Update Status</button>
      </form>
    </div>

  <?php else: ?>
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
      <div><h1>Orders</h1><p class="subtitle"><?php echo $total; ?> total orders</p></div>
    </div>

    <?php if ($message): ?><div class="alert"><?php echo $message; ?></div><?php endif; ?>

    <form method="GET" class="filters">
      <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
      <select name="status">
        <option value="">All Statuses</option>
        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
        <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
        <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
        <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>

    <div class="card" style="overflow-x:auto;">
      <table>
        <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Payment</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($orders)): ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:#888;">No orders found</td></tr>
          <?php endif; ?>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td style="font-weight:700;"><?php echo htmlspecialchars($o['order_number']); ?></td>
            <td><?php echo htmlspecialchars($o['full_name']); ?></td>
            <td>TSh <?php echo number_format($o['total'], 0, '.', ','); ?></td>
            <td><span class="badge <?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
            <td><span class="badge <?php echo $o['payment_status']; ?>"><?php echo ucfirst($o['payment_status']); ?></span></td>
            <td style="font-size:12px;color:#888;"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
            <td><a href="?action=view&id=<?php echo $o['id']; ?>" class="btn btn-outline btn-sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

</body></html>
