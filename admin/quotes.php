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
    $adminNotes = trim($_POST['admin_notes'] ?? '');
    $allowed = ['pending', 'reviewed', 'approved', 'rejected', 'converted'];
    if (in_array($status, $allowed)) {
        execute("UPDATE quotations SET status = ?, admin_notes = ? WHERE id = ?", [$status, $adminNotes, $id]);
        $message = 'Quote status updated.';
    }
}

if ($action === 'convert' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $quote = fetchOne("SELECT * FROM quotations WHERE id = ?", [$id]);
    if ($quote && $quote['status'] !== 'converted') {
        $orderNum = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $items = fetchAll("SELECT * FROM quotation_items WHERE quotation_id = ?", [$id]);
        execute(
            "INSERT INTO orders (order_number, user_id, full_name, email, phone, company_name, address, subtotal, discount, vat, shipping, total, payment_method, payment_status, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'bank_transfer', 'pending', 'pending', ?)",
            [$orderNum, $quote['user_id'], $quote['contact_name'] ?? '', $quote['contact_email'] ?? '', $quote['contact_phone'] ?? '', $quote['company_name'] ?? '', '', $quote['subtotal'], $quote['discount'], $quote['vat'], 0, $quote['total'], 'Converted from quote ' . $quote['quotation_number']]
        );
        $orderId = fetchOne("SELECT MAX(id) as id FROM orders")['id'];
        foreach ($items as $item) {
            execute(
                "INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$orderId, $item['product_id'], $item['product_name'], $item['product_sku'], $item['quantity'], $item['unit_price'], $item['total_price']]
            );
            execute("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?", [$item['quantity'], $item['product_id']]);
        }
        execute("INSERT INTO order_tracking (order_id, status, note) VALUES (?, 'pending', ?)", [$orderId, 'Order created from quote ' . $quote['quotation_number']]);
        execute("UPDATE quotations SET status = 'converted' WHERE id = ?", [$id]);
        $message = 'Quote converted to order #' . $orderNum;
    }
}

if ($action === 'view' && $id) {
    $quote = fetchOne("SELECT q.*, u.full_name as user_name, u.email as user_email, u.phone as user_phone FROM quotations q LEFT JOIN users u ON q.user_id = u.id WHERE q.id = ?", [$id]);
    if (!$quote) { header('Location: /admin/quotes.php'); exit; }
    $items = fetchAll("SELECT * FROM quotation_items WHERE quotation_id = ?", [$id]);
} else {
    $page = max(1, (int)($_GET['p'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    $statusFilter = $_GET['status'] ?? '';

    $where = [];
    $params = [];
    if ($statusFilter) {
        $where[] = "q.status = ?";
        $params[] = $statusFilter;
    }
    $ws = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $total = fetchOne("SELECT COUNT(*) as c FROM quotations q $ws", $params)['c'];
    $quotes = fetchAll("SELECT q.*, u.full_name as user_name FROM quotations q LEFT JOIN users u ON q.user_id = u.id $ws ORDER BY q.created_at DESC LIMIT $perPage OFFSET $offset", $params);
    $totalPages = ceil($total / $perPage);
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quotes — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--orange:#F05A22;--bg:#F4F6F9;--card:#FFF;--border:#e2e8f0;--shadow-sm:0 1px 3px rgba(10,37,64,0.06)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:#1a1a2e;font-size:14px;display:flex}
.sidebar{width:240px;background:var(--navy);min-height:100vh;padding:24px 0;flex-shrink:0;position:sticky;top:0;height:100vh}
.sidebar h2{color:#fff;font-size:18px;padding:0 20px;margin-bottom:24px;display:flex;align-items:center;gap:8px}
.sidebar h2 i{color:var(--orange)}
.sidebar a{display:block;padding:10px 20px;color:rgba(255,255,255,0.7);text-decoration:none;font-size:13px;font-weight:500}
.sidebar a:hover,.sidebar a.active{background:rgba(255,255,255,0.08);color:#fff;border-left:3px solid var(--orange)}
.main{flex:1;padding:24px 32px;max-width:calc(100vw - 240px)}
h1{font-size:22px;font-weight:800;color:var(--navy);margin-bottom:4px}
.subtitle{color:#666;font-size:13px;margin-bottom:20px}
.card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:18px 20px;box-shadow:var(--shadow-sm)}
.filters{display:flex;gap:10px;margin-bottom:16px}
.filters select{padding:8px 12px;border:1.5px solid var(--border);border-radius:6px;font-family:'Inter',sans-serif;font-size:13px;outline:none}
.filters select:focus{border-color:var(--orange)}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:10px 12px;font-size:11px;font-weight:700;color:#666;text-transform:uppercase;border-bottom:2px solid var(--border);white-space:nowrap}
td{padding:10px 12px;font-size:13px;border-bottom:1px solid var(--border);color:var(--navy)}
tr:hover td{background:rgba(240,90,34,0.02)}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600}
.badge.pending{background:rgba(234,179,8,0.12);color:#a16207}
.badge.reviewed{background:rgba(59,130,246,0.12);color:#1d4ed8}
.badge.approved{background:rgba(5,150,105,0.12);color:#059669}
.badge.rejected{background:rgba(220,38,38,0.12);color:#dc2626}
.badge.converted{background:rgba(168,85,247,0.12);color:#7c3aed}
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
  <a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a>
  <a href="payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
  <a href="quotes.php" class="active"><i class="fas fa-file-alt"></i> <span>Quotes</span></a>
  <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
  <a href="company-users.php"><i class="fas fa-user-shield"></i> <span>Authorized Users</span></a>
  <a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
  <a href="login.php?action=logout" style="margin-top:40px;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
</div>

<div class="main">
  <?php if ($action === 'view' && isset($quote)): ?>
    <a href="quotes.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Quotes</a>
    <h1>Quote #<?php echo htmlspecialchars($quote['quotation_number']); ?></h1>
    <p class="subtitle">Submitted on <?php echo date('M d, Y h:i A', strtotime($quote['created_at'])); ?></p>
    <?php if ($message): ?><div class="alert"><?php echo $message; ?></div><?php endif; ?>

    <div class="card" style="margin-bottom:20px;">
      <div class="info-grid">
        <div class="info-item"><label>Status</label><div class="value"><span class="badge <?php echo $quote['status']; ?>"><?php echo ucfirst($quote['status']); ?></span></div></div>
        <div class="info-item"><label>Total</label><div class="value">TSh <?php echo number_format($quote['total'], 0, '.', ','); ?></div></div>
        <div class="info-item"><label>User</label><div class="value"><?php echo htmlspecialchars($quote['user_name'] ?? 'Guest'); ?></div></div>
        <div class="info-item"><label>Contact Name</label><div class="value"><?php echo htmlspecialchars($quote['contact_name'] ?: '-'); ?></div></div>
        <div class="info-item"><label>Email</label><div class="value"><?php echo htmlspecialchars($quote['contact_email'] ?: '-'); ?></div></div>
        <div class="info-item"><label>Phone</label><div class="value"><?php echo htmlspecialchars($quote['contact_phone'] ?: '-'); ?></div></div>
      </div>
      <?php if ($quote['notes']): ?>
      <div style="margin-top:12px;padding:12px;background:var(--bg);border-radius:6px;">
        <label style="font-size:11px;font-weight:600;color:#888;text-transform:uppercase;">Customer Notes</label>
        <p style="font-size:13px;margin-top:4px;"><?php echo nl2br(htmlspecialchars($quote['notes'])); ?></p>
      </div>
      <?php endif; ?>
    </div>

    <div class="card" style="margin-bottom:20px;">
      <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:12px;">Items</h3>
      <table><thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr><td><?php echo htmlspecialchars($item['product_name']); ?></td><td style="color:#888;font-size:12px;"><?php echo htmlspecialchars($item['product_sku']); ?></td><td><?php echo $item['quantity']; ?></td><td>TSh <?php echo number_format($item['unit_price'], 0, '.', ','); ?></td><td>TSh <?php echo number_format($item['total_price'], 0, '.', ','); ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>

    <div class="card">
      <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:12px;">Update Quote</h3>
      <form method="POST" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
        <input type="hidden" name="status" id="statusVal" value="">
        <div>
          <label style="display:block;font-size:11px;font-weight:600;color:#888;margin-bottom:4px;">Status</label>
          <select id="statusSelect" style="padding:8px 12px;border:1.5px solid var(--border);border-radius:6px;font-family:'Inter',sans-serif;font-size:13px;" onchange="document.getElementById('statusVal').value=this.value">
            <option value="">Select...</option>
            <option value="reviewed">Reviewed</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="converted">Converted to Order</option>
          </select>
        </div>
        <div style="flex:1;min-width:200px;">
          <label style="display:block;font-size:11px;font-weight:600;color:#888;margin-bottom:4px;">Admin Notes</label>
          <textarea name="admin_notes" rows="2" style="width:100%;padding:8px 12px;border:1.5px solid var(--border);border-radius:6px;font-family:'Inter',sans-serif;font-size:13px;resize:vertical;"><?php echo htmlspecialchars($quote['admin_notes'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
      </form>
    </div>

    <?php if (in_array($quote['status'], ['pending', 'reviewed', 'approved'])): ?>
    <div class="card" style="margin-top:20px;border:2px solid rgba(168,85,247,0.3);">
      <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:12px;"><i class="fas fa-exchange-alt" style="color:#7c3aed;"></i> Convert to Order</h3>
      <p style="font-size:13px;color:#666;margin-bottom:14px;">This will create a new order from this quotation with all items, pricing, and customer details.</p>
      <form method="POST" action="?action=convert&id=<?php echo $id; ?>" onsubmit="return confirm('Convert this quote to an order?')">
        <button type="submit" class="btn" style="background:#7c3aed;color:#fff;"><i class="fas fa-arrow-right"></i> Convert to Order</button>
      </form>
    </div>
    <?php endif; ?>

  <?php else: ?>
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
      <div><h1>Quote Requests</h1><p class="subtitle"><?php echo $total; ?> total quotes</p></div>
    </div>
    <?php if ($message): ?><div class="alert"><?php echo $message; ?></div><?php endif; ?>
    <form method="GET" class="filters">
      <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
        <option value="reviewed" <?php echo $statusFilter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
        <option value="converted" <?php echo $statusFilter === 'converted' ? 'selected' : ''; ?>>Converted</option>
      </select>
    </form>
    <div class="card" style="overflow-x:auto;">
      <table><thead><tr><th>Quote #</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($quotes)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#888;">No quotes found</td></tr>
        <?php endif; ?>
        <?php foreach ($quotes as $q): ?>
        <?php $ic = fetchOne("SELECT COUNT(*) as c FROM quotation_items WHERE quotation_id = ?", [$q['id']])['c']; ?>
        <tr>
          <td style="font-weight:700;"><?php echo htmlspecialchars($q['quotation_number']); ?></td>
          <td><?php echo htmlspecialchars($q['user_name'] ?? 'Guest'); ?></td>
          <td><?php echo $ic; ?></td>
          <td>TSh <?php echo number_format($q['total'], 0, '.', ','); ?></td>
          <td><span class="badge <?php echo $q['status']; ?>"><?php echo ucfirst($q['status']); ?></span></td>
          <td style="font-size:12px;color:#888;"><?php echo date('M d, Y', strtotime($q['created_at'])); ?></td>
          <td><a href="?action=view&id=<?php echo $q['id']; ?>" class="btn btn-outline btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody></table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="pagination"><?php for ($i=1;$i<=$totalPages;$i++): ?><a href="?p=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>" class="<?php echo $i===$page?'active':''; ?>"><?php echo $i; ?></a><?php endfor; ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div>

</body></html>
