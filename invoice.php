<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/functions.php';

$orderNum = $_GET['order'] ?? '';
if (!$orderNum) { header('Location: /'); exit; }

$order = fetchOne("SELECT * FROM orders WHERE order_number = ?", [$orderNum]);
if (!$order) { header('Location: /'); exit; }

if (isset($_GET['print'])) {
    header('Content-Type: text/html');
    echo generateInvoiceHtml($order);
    exit;
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?php echo htmlspecialchars($order['order_number']); ?> — BN-Infrastructure</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--orange:#F05A22;--bg:#F4F6F9;--card:#FFF;--border:#e2e8f0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:#0A2540}
.page-wrap{max-width:800px;margin:40px auto;padding:0 24px}
.invoice-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:40px;box-shadow:0 4px 12px rgba(10,37,64,0.08)}
.actions{display:flex;gap:10px;justify-content:center;margin-bottom:24px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .2s}
.btn-primary{background:var(--orange);color:#fff}.btn-primary:hover{background:#d44d1a}
.btn-outline{background:transparent;color:var(--navy);border:1.5px solid var(--border)}.btn-outline:hover{border-color:var(--orange)}
.header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px}
.meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;padding:20px;background:var(--bg);border-radius:8px}
.meta-grid p{font-size:13px;color:#5a6a7e;margin:3px 0}
.meta-grid strong{color:#0A2540}
table{width:100%;border-collapse:collapse;margin-bottom:24px}
th{padding:10px;border-bottom:2px solid var(--border);text-align:left;font-size:11px;font-weight:700;color:#888;text-transform:uppercase}
td{padding:10px;border-bottom:1px solid var(--border);font-size:13px}
.totals{text-align:right;border-top:2px solid var(--navy);padding-top:16px}
.totals p{font-size:13px;color:#5a6a7e;margin:4px 0}
.totals .total{font-size:20px;font-weight:900;color:var(--navy);margin-top:8px}
@media print{.actions{display:none}.page-wrap{margin:0;padding:20px}}
</style>
</head>
<body>
<div class="page-wrap">
  <div class="actions">
    <a href="javascript:window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Invoice</a>
    <a href="?order=<?php echo urlencode($orderNum); ?>&print=1" class="btn btn-outline" target="_blank"><i class="fas fa-file-pdf"></i> Open Printable Version</a>
    <a href="track.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
  <div class="invoice-card">
    <div class="header">
      <div><h1 style="font-size:28px;font-weight:900;">INVOICE</h1><p style="color:#5a6a7e;"><?php echo htmlspecialchars($order['order_number']); ?></p></div>
      <div style="text-align:right;"><h2 style="font-size:18px;font-weight:800;">BN-Infrastructure</h2><p style="font-size:12px;color:#5a6a7e;">Plot 45, Mikocheni</p><p style="font-size:12px;color:#5a6a7e;">Dar es Salaam, Tanzania</p><p style="font-size:12px;color:#5a6a7e;">+255 763 364 721</p></div>
    </div>
    <div class="meta-grid">
      <div><p style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:4px;">Bill To</p><p style="font-size:14px;font-weight:600;"><?php echo htmlspecialchars($order['full_name']); ?></p><p><?php echo htmlspecialchars($order['company_name'] ?: ''); ?></p><p><?php echo htmlspecialchars($order['email']); ?></p><p><?php echo htmlspecialchars($order['address'] ?: ''); ?>, <?php echo htmlspecialchars($order['city'] ?: ''); ?></p></div>
      <div style="text-align:right;"><p style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:4px;">Invoice Details</p><p>Date: <strong><?php echo date('M d, Y', strtotime($order['created_at'])); ?></strong></p><p>Payment: <strong><?php echo str_replace('_', ' ', ucwords($order['payment_method'], '_')); ?></strong></p><p>Status: <strong><?php echo ucfirst($order['payment_status']); ?></strong></p></div>
    </div>
    <table><thead><tr><th>Product</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Total</th></tr></thead><tbody>
    <?php $items = fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]); foreach ($items as $item): ?>
    <tr><td><?php echo htmlspecialchars($item['product_name']); ?><br><small style="color:#888;"><?php echo htmlspecialchars($item['product_sku']); ?></small></td><td style="text-align:center;"><?php echo $item['quantity']; ?></td><td style="text-align:right;">TSh <?php echo number_format($item['unit_price'], 0, '.', ','); ?></td><td style="text-align:right;font-weight:600;">TSh <?php echo number_format($item['total_price'], 0, '.', ','); ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
    <div class="totals">
      <p>Subtotal: TSh <?php echo number_format($order['subtotal'], 0, '.', ','); ?></p>
      <p>VAT (18%): TSh <?php echo number_format($order['vat'], 0, '.', ','); ?></p>
      <p>Shipping: <?php echo $order['shipping'] > 0 ? 'TSh ' . number_format($order['shipping'], 0, '.', ',') : 'FREE'; ?></p>
      <?php if ($order['discount'] > 0): ?><p>Discount: -TSh <?php echo number_format($order['discount'], 0, '.', ','); ?></p><?php endif; ?>
      <p class="total">TOTAL: TSh <?php echo number_format($order['total'], 0, '.', ','); ?></p>
    </div>
  </div>
</div>
</body></html>
