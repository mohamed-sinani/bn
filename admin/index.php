<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';
requireLogin();

$user = getCurrentUser();

$totalProducts = fetchOne("SELECT COUNT(*) as count FROM products")['count'];
$totalOrders = fetchOne("SELECT COUNT(*) as count FROM orders")['count'];
$totalQuotes = fetchOne("SELECT COUNT(*) as count FROM quotations")['count'];
$pendingOrders = fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'];
$totalCategories = fetchOne("SELECT COUNT(*) as count FROM categories")['count'];
$lowStock = fetchOne("SELECT COUNT(*) as count FROM products WHERE stock_status = 'low_stock' OR (stock_status = 'in_stock' AND stock_qty <= 5)")['count'];
$revenue = fetchOne("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE status != 'cancelled'")['total'];
$recentOrders = fetchAll("SELECT id, order_number, full_name, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
$recentProducts = fetchAll("SELECT id, name, sku, price, stock_status, stock_qty, created_at FROM products ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — BN-Infrastructure Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --navy: #0A2540; --navy-light: #133057; --orange: #F05A22;
            --orange-dark: #d44d1a; --bg: #F4F6F9; --card: #FFFFFF;
            --text-primary: #0A2540; --text-secondary: #5a6a7e; --text-muted: #8fa0b3;
            --border: #e2e8f0; --shadow-sm: 0 1px 3px rgba(10,37,64,0.08);
            --shadow-md: 0 4px 12px rgba(10,37,64,0.1);
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-primary); font-size: 14px; display: flex; min-height: 100vh; }
        .sidebar {
            width: 250px; background: var(--navy); color: #fff; padding: 0; flex-shrink: 0;
            display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh;
        }
        .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 12px; }
        .sidebar-brand .icon { width: 36px; height: 36px; background: var(--orange); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .sidebar-brand h2 { font-size: 16px; font-weight: 800; } 
        .sidebar-brand .tag { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.4); }
        .sidebar-nav { padding: 12px 0; flex: 1; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 12px; padding: 11px 20px;
            color: rgba(255,255,255,0.6); text-decoration: none; font-size: 13px; font-weight: 500;
            transition: all 0.15s; border-left: 3px solid transparent;
        }
        .sidebar-nav a:hover { color: #fff; background: rgba(255,255,255,0.06); }
        .sidebar-nav a.active { color: #fff; background: rgba(255,255,255,0.08); border-left-color: var(--orange); }
        .sidebar-nav a i { width: 18px; text-align: center; font-size: 14px; }
        .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.08); }
        .sidebar-footer .user-info { font-size: 12px; color: rgba(255,255,255,0.5); margin-bottom: 8px; }
        .sidebar-footer a { color: rgba(255,255,255,0.6); text-decoration: none; font-size: 12px; }
        .sidebar-footer a:hover { color: #fff; }
        .main { flex: 1; padding: 28px 32px; }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .main-header h1 { font-size: 22px; font-weight: 700; }
        .main-header p { font-size: 13px; color: var(--text-secondary); margin-top: 2px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 28px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); }
        .stat-card .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; margin-bottom: 12px; }
        .stat-card .stat-value { font-size: 24px; font-weight: 700; }
        .stat-card .stat-label { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { font-size: 14px; font-weight: 600; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 12px 16px; font-weight: 600; color: var(--text-secondary); font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; background: var(--bg); border-bottom: 1px solid var(--border); }
        td { padding: 12px 16px; border-bottom: 1px solid var(--border); }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-stock { background: rgba(5,150,105,0.12); color: #059669; }
        .badge-low { background: rgba(245,158,11,0.12); color: #d97706; }
        .badge-out { background: rgba(239,68,68,0.1); color: #dc2626; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: all 0.15s; }
        .btn-primary { background: var(--orange); color: #fff; }
        .btn-primary:hover { background: var(--orange-dark); }
        .btn-outline { background: transparent; color: var(--text-secondary); border: 1px solid var(--border); }
        .btn-outline:hover { border-color: var(--orange); color: var(--orange); }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        @media (max-width: 768px) { .sidebar { width: 60px; } .sidebar-brand h2, .sidebar-brand .tag, .sidebar-nav a span { display: none; } .main { padding: 20px 16px; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="icon"><i class="fas fa-network-wired"></i></div>
            <div>
                <h2>BN-Infrastructure</h2>
                <div class="tag">Admin Panel</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
            <a href="products.php"><i class="fas fa-box"></i> <span>Products</span></a>
            <a href="products-add.php"><i class="fas fa-plus-circle"></i> <span>Add Product</span></a>
            <a href="categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a>
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a>
            <a href="payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
            <a href="quotes.php"><i class="fas fa-file-alt"></i> <span>Quotes</span></a>
            <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
            <a href="company-users.php"><i class="fas fa-user-shield"></i> <span>Authorized Users</span></a>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></div>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </div>
    </aside>
    <div class="main">
        <div class="main-header">
            <div>
                <h1>Dashboard</h1>
                <p>Overview of your BN-Infrastructure store</p>
            </div>
            <div>
                <a href="products-add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a>
                <a href="/Homepage/" class="btn btn-outline" style="margin-left:8px;"><i class="fas fa-external-link-alt"></i> View Store</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(240,90,34,0.1);color:var(--orange);"><i class="fas fa-box"></i></div>
                <div class="stat-value"><?php echo $totalProducts; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(5,150,105,0.1);color:#059669;"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-value"><?php echo $totalOrders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(10,37,64,0.1);color:var(--navy);"><i class="fas fa-tags"></i></div>
                <div class="stat-value"><?php echo $totalCategories; ?></div>
                <div class="stat-label">Categories</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(239,68,68,0.1);color:#dc2626;"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?php echo $lowStock; ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(59,130,246,0.1);color:#1d4ed8;"><i class="fas fa-file-alt"></i></div>
                <div class="stat-value"><?php echo $totalQuotes; ?></div>
                <div class="stat-label">Quote Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:#d97706;"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo $pendingOrders; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(5,150,105,0.1);color:#059669;"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-value">TSh <?php echo number_format($revenue, 0, '.', ','); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:24px;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Recent Orders</h3>
                <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text-muted);">No orders yet.</td></tr>
                        <?php else: ?>
                        <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><a href="orders.php?action=view&id=<?php echo $o['id']; ?>" style="color:var(--navy);text-decoration:none;font-weight:600;"><?php echo htmlspecialchars($o['order_number']); ?></a></td>
                            <td style="color:#666;"><?php echo htmlspecialchars($o['full_name']); ?></td>
                            <td>TSh <?php echo number_format($o['total'], 0, '.', ','); ?></td>
                            <td><?php echo getStatusBadge($o['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-box"></i> Recent Products</h3>
                <a href="products.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Price</th><th>Stock</th><th>Qty</th></tr></thead>
                    <tbody>
                        <?php if (empty($recentProducts)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text-muted);">No products yet.</td></tr>
                        <?php else: ?>
                        <?php foreach ($recentProducts as $p): ?>
                        <tr>
                            <td><a href="products-edit.php?id=<?php echo $p['id']; ?>" style="color:var(--navy);text-decoration:none;font-weight:500;"><?php echo htmlspecialchars($p['name']); ?></a></td>
                            <td><?php echo formatTsh($p['price']); ?></td>
                            <td><?php echo getStatusBadge($p['stock_status']); ?></td>
                            <td><?php echo $p['stock_qty']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
</body>
</html>
