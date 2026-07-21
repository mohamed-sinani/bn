<?php
require_once '../config/database.php';
require_once '../src/functions.php';
require_once '../src/auth.php';
requireLogin();

$report = $_GET['report'] ?? '';
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

if ($report === 'orders_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="orders_' . $dateFrom . '_' . $dateTo . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order #', 'Customer', 'Email', 'Phone', 'Company', 'Subtotal', 'VAT', 'Shipping', 'Total', 'Status', 'Payment Method', 'Date']);
    $rows = fetchAll("SELECT * FROM orders WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC", [$dateFrom, $dateTo]);
    foreach ($rows as $r) {
        fputcsv($output, [$r['order_number'], $r['full_name'], $r['email'], $r['phone'], $r['company_name'], $r['subtotal'], $r['vat'], $r['shipping'], $r['total'], $r['status'], $r['payment_method'], $r['created_at']]);
    }
    fclose($output);
    exit;
}

if ($report === 'payments_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payments_' . $dateFrom . '_' . $dateTo . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Reference', 'Order #', 'Amount', 'Method', 'Status', 'Paid At', 'Created']);
    $rows = fetchAll("SELECT p.*, o.order_number FROM payments p LEFT JOIN orders o ON p.order_id = o.id WHERE DATE(p.created_at) BETWEEN ? AND ? ORDER BY p.created_at DESC", [$dateFrom, $dateTo]);
    foreach ($rows as $r) {
        fputcsv($output, [$r['payment_reference'], $r['order_number'] ?? '', $r['amount'], $r['payment_method'], $r['status'], $r['paid_at'] ?? '', $r['created_at']]);
    }
    fclose($output);
    exit;
}

if ($report === 'customers_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Email', 'Phone', 'Company', 'Business Type', 'Registered']);
    $rows = fetchAll("SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC");
    foreach ($rows as $r) {
        fputcsv($output, [$r['full_name'], $r['email'], $r['phone'], $r['company'], $r['business_type'], $r['created_at']]);
    }
    fclose($output);
    exit;
}

if ($report === 'products_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'SKU', 'Brand', 'Category', 'Price', 'Stock Status', 'Stock Qty', 'Featured', 'Warranty']);
    $rows = fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name");
    foreach ($rows as $r) {
        fputcsv($output, [$r['name'], $r['sku'], $r['brand'], $r['category_name'] ?? '', $r['price'], $r['stock_status'], $r['stock_qty'], $r['featured'] ? 'Yes' : 'No', $r['warranty'] ?? '']);
    }
    fclose($output);
    exit;
}

$stats = [
    'totalRevenue' => fetchOne("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE status != 'cancelled' AND DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo])['t'],
    'totalOrders' => fetchOne("SELECT COUNT(*) as c FROM orders WHERE DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo])['c'],
    'totalCustomers' => fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'customer'")['c'],
    'totalProducts' => fetchOne("SELECT COUNT(*) as c FROM products")['c'],
    'completedPayments' => fetchOne("SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo])['t'],
    'pendingPayments' => fetchOne("SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE status = 'pending' AND DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo])['t'],
    'topProducts' => fetchAll("SELECT p.name, p.sku, SUM(oi.quantity) as sold, SUM(oi.total_price) as revenue FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE o.status != 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY oi.product_id ORDER BY revenue DESC LIMIT 10", [$dateFrom, $dateTo]),
    'ordersByStatus' => fetchAll("SELECT status, COUNT(*) as cnt FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status", [$dateFrom, $dateTo]),
];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports — Admin</title>
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
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:6px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none}
.btn-primary{background:var(--orange);color:#fff}
.btn-outline{background:transparent;color:var(--navy);border:1.5px solid var(--border)}
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:18px}
.stat-card label{font-size:11px;font-weight:600;color:#888;text-transform:uppercase}
.stat-card .value{font-size:24px;font-weight:800;color:var(--navy);margin-top:4px}
.stat-card .value.green{color:#059669}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:10px 12px;font-size:11px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid var(--border)}
td{padding:10px 12px;font-size:13px;border-bottom:1px solid var(--border);color:var(--navy)}
.filter-bar{display:flex;gap:10px;align-items:end;margin-bottom:20px;flex-wrap:wrap}
.filter-bar input{padding:8px 12px;border:1.5px solid var(--border);border-radius:6px;font-family:'Inter',sans-serif;font-size:13px;outline:none}
.filter-bar input:focus{border-color:var(--orange)}
.export-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.export-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px;text-align:center;transition:border-color .2s}
.export-card:hover{border-color:var(--orange)}
.export-card i{font-size:28px;color:var(--orange);margin-bottom:10px;display:block}
.export-card h4{font-size:14px;font-weight:700;color:var(--navy);margin-bottom:4px}
.export-card p{font-size:12px;color:#888;margin-bottom:12px}
@media(max-width:768px){.sidebar{width:60px}.sidebar h2 span,.sidebar a span{display:none}.main{max-width:calc(100vw - 60px);padding:16px}.stat-grid,.export-grid{grid-template-columns:1fr}}
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
  <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
  <a href="company-users.php"><i class="fas fa-user-shield"></i> <span>Authorized Users</span></a>
  <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
  <a href="logout.php" style="margin-top:40px;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
</div>
<div class="main">
  <h1>Reports & Analytics</h1>
  <p class="subtitle">Export data and view performance metrics</p>

  <div class="filter-bar" style="margin-bottom:24px;">
    <div>
      <label style="display:block;font-size:11px;font-weight:600;color:#888;margin-bottom:4px;">From</label>
      <input type="date" name="from" id="dateFrom" value="<?php echo $dateFrom; ?>">
    </div>
    <div>
      <label style="display:block;font-size:11px;font-weight:600;color:#888;margin-bottom:4px;">To</label>
      <input type="date" name="to" id="dateTo" value="<?php echo $dateTo; ?>">
    </div>
    <button class="btn btn-primary" onclick="window.location.href='?from='+document.getElementById('dateFrom').value+'&to='+document.getElementById('dateTo').value"><i class="fas fa-sync"></i> Refresh</button>
  </div>

  <div class="export-grid">
    <a href="?report=orders_csv&from=<?php echo $dateFrom; ?>&to=<?php echo $dateTo; ?>" class="export-card"><i class="fas fa-truck"></i><h4>Orders Report</h4><p>Export all orders as CSV</p><span class="btn btn-outline"><i class="fas fa-download"></i> Download</span></a>
    <a href="?report=payments_csv&from=<?php echo $dateFrom; ?>&to=<?php echo $dateTo; ?>" class="export-card"><i class="fas fa-credit-card"></i><h4>Payments Report</h4><p>Export payment records as CSV</p><span class="btn btn-outline"><i class="fas fa-download"></i> Download</span></a>
    <a href="?report=customers_csv" class="export-card"><i class="fas fa-users"></i><h4>Customers Report</h4><p>Export customer list as CSV</p><span class="btn btn-outline"><i class="fas fa-download"></i> Download</span></a>
    <a href="?report=products_csv" class="export-card"><i class="fas fa-box"></i><h4>Products Report</h4><p>Export product catalog as CSV</p><span class="btn btn-outline"><i class="fas fa-download"></i> Download</span></a>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><label>Revenue (Period)</label><div class="value green">TSh <?php echo number_format($stats['totalRevenue'], 0, '.', ','); ?></div></div>
    <div class="stat-card"><label>Orders (Period)</label><div class="value"><?php echo $stats['totalOrders']; ?></div></div>
    <div class="stat-card"><label>Total Customers</label><div class="value"><?php echo $stats['totalCustomers']; ?></div></div>
    <div class="stat-card"><label>Total Products</label><div class="value"><?php echo $stats['totalProducts']; ?></div></div>
    <div class="stat-card"><label>Completed Payments</label><div class="value green">TSh <?php echo number_format($stats['completedPayments'], 0, '.', ','); ?></div></div>
    <div class="stat-card"><label>Pending Payments</label><div class="value" style="color:#d97706;">TSh <?php echo number_format($stats['pendingPayments'], 0, '.', ','); ?></div></div>
  </div>

  <?php if (!empty($stats['ordersByStatus'])): ?>
  <div class="card" style="margin-bottom:20px;">
    <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:12px;">Orders by Status</h3>
    <table><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
    <?php foreach ($stats['ordersByStatus'] as $s): ?>
    <tr><td style="text-transform:capitalize;"><?php echo $s['status']; ?></td><td style="font-weight:700;"><?php echo $s['cnt']; ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <?php endif; ?>

  <?php if (!empty($stats['topProducts'])): ?>
  <div class="card">
    <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:12px;">Top Selling Products</h3>
    <table><thead><tr><th>Product</th><th>SKU</th><th>Units Sold</th><th>Revenue</th></tr></thead><tbody>
    <?php foreach ($stats['topProducts'] as $tp): ?>
    <tr>
      <td style="font-weight:600;"><?php echo htmlspecialchars($tp['name']); ?></td>
      <td style="color:#888;font-size:12px;"><?php echo htmlspecialchars($tp['sku']); ?></td>
      <td><?php echo $tp['sold']; ?></td>
      <td style="font-weight:700;">TSh <?php echo number_format($tp['revenue'], 0, '.', ','); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <?php endif; ?>
</div>
</body></html>
