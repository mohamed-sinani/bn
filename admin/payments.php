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
    $allowed = ['pending', 'completed', 'failed', 'refunded'];
    if (in_array($status, $allowed)) {
        $paidAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
        execute("UPDATE payments SET status = ?, paid_at = ? WHERE id = ?", [$status, $paidAt, $id]);
        if ($status === 'completed' && $paidAt) {
            $payment = fetchOne("SELECT order_id FROM payments WHERE id = ?", [$id]);
            if ($payment && $payment['order_id']) {
                execute("UPDATE orders SET payment_status = 'completed' WHERE id = ?", [$payment['order_id']]);
            }
        }
        $message = 'Payment status updated.';
    }
}

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];
if ($statusFilter) { $where[] = "p.status = ?"; $params[] = $statusFilter; }
if ($search) {
    $where[] = "(p.payment_reference LIKE ? OR o.order_number LIKE ? OR o.full_name LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
}
$ws = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$total = fetchOne("SELECT COUNT(*) as c FROM payments p LEFT JOIN orders o ON p.order_id = o.id $ws", $params)['c'];
$payments = fetchAll("SELECT p.*, o.order_number, o.full_name as customer_name FROM payments p LEFT JOIN orders o ON p.order_id = o.id $ws ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset", $params);
$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments — Admin</title>
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
.badge.pending{background:rgba(234,179,8,0.12);color:#a16207}
.badge.completed{background:rgba(5,150,105,0.12);color:#059669}
.badge.failed{background:rgba(220,38,38,0.12);color:#dc2626}
.badge.refunded{background:rgba(168,85,247,0.12);color:#7c3aed}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;font-family:'Inter',sans-serif;font-size:12px;font-weight:600;cursor:pointer;border:none;text-decoration:none}
.btn-primary{background:var(--orange);color:#fff}
.btn-outline{background:transparent;color:var(--navy);border:1.5px solid var(--border)}
.btn-sm{padding:5px 10px;font-size:11px}
.info-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:20px}
.info-item label{font-size:11px;font-weight:600;color:#888;text-transform:uppercase}
.info-item .value{font-size:14px;font-weight:600;color:var(--navy);margin-top:2px}
.back-link{margin-bottom:16px;display:inline-block;color:var(--orange);text-decoration:none;font-size:13px;font-weight:600}
.alert{padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:14px;background:rgba(5,150,105,0.08);color:#059669;border:1px solid rgba(5,150,105,0.15)}
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
  <a href="payments.php" class="active"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
  <a href="quotes.php"><i class="fas fa-file-alt"></i> <span>Quotes</span></a>
  <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
  <a href="company-users.php"><i class="fas fa-user-shield"></i> <span>Authorized Users</span></a>
  <a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
  <a href="logout.php" style="margin-top:40px;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
</div>
<div class="main">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
    <div><h1>Payments</h1><p class="subtitle"><?php echo $total; ?> total payments</p></div>
  </div>
  <?php if ($message): ?><div class="alert"><?php echo $message; ?></div><?php endif; ?>
  <form method="GET" class="filters">
    <input type="text" name="search" placeholder="Search payments..." value="<?php echo htmlspecialchars($search); ?>">
    <select name="status">
      <option value="">All Statuses</option>
      <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
      <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
      <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
      <option value="refunded" <?php echo $statusFilter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
  </form>
  <div class="card" style="overflow-x:auto;">
    <table><thead><tr><th>Reference</th><th>Order</th><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php if (empty($payments)): ?>
    <tr><td colspan="8" style="text-align:center;padding:40px;color:#888;">No payments found</td></tr>
    <?php endif; ?>
    <?php foreach ($payments as $p): ?>
    <tr>
      <td style="font-weight:700;font-size:12px;"><?php echo htmlspecialchars($p['payment_reference']); ?></td>
      <td><?php echo htmlspecialchars($p['order_number'] ?? '-'); ?></td>
      <td><?php echo htmlspecialchars($p['customer_name'] ?? '-'); ?></td>
      <td style="font-weight:700;">TSh <?php echo number_format($p['amount'], 0, '.', ','); ?></td>
      <td><?php echo str_replace('_', ' ', ucwords($p['payment_method'], '_')); ?></td>
      <td><span class="badge <?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
      <td style="font-size:12px;color:#888;"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
      <td>
        <form method="POST" action="?action=update_status&id=<?php echo $p['id']; ?>" style="display:inline-flex;gap:4px;align-items:center;">
          <select name="status" style="padding:4px 8px;border:1px solid var(--border);border-radius:4px;font-size:11px;font-family:'Inter',sans-serif;" onchange="if(confirm('Update payment status?'))this.form.submit()">
            <option value="">Change...</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="pagination"><?php for ($i=1;$i<=$totalPages;$i++): ?><a href="?p=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $i===$page?'active':''; ?>"><?php echo $i; ?></a><?php endfor; ?></div>
  <?php endif; ?>
</div>
</body></html>
