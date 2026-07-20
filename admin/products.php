<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';
requireLogin();

$user = getCurrentUser();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$where = [];
$params = [];
if ($search) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($categoryFilter) {
    $where[] = "p.category_id = ?";
    $params[] = (int)$categoryFilter;
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = fetchOne("SELECT COUNT(*) as count FROM products p $whereClause", $params)['count'];

$allParams = array_merge($params, [$perPage, $offset]);
$products = fetchAll(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     $whereClause 
     ORDER BY p.created_at DESC 
     LIMIT ? OFFSET ?",
    $allParams
);

$categories = fetchAll("SELECT * FROM categories ORDER BY name");
$totalPages = ceil($total / $perPage);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — BN-Infrastructure Admin</title>
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
        .sidebar { width: 250px; background: var(--navy); color: #fff; padding: 0; flex-shrink: 0; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; }
        .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 12px; }
        .sidebar-brand .icon { width: 36px; height: 36px; background: var(--orange); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .sidebar-brand h2 { font-size: 16px; font-weight: 800; }
        .sidebar-brand .tag { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.4); }
        .sidebar-nav { padding: 12px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 11px 20px; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.15s; border-left: 3px solid transparent; }
        .sidebar-nav a:hover { color: #fff; background: rgba(255,255,255,0.06); }
        .sidebar-nav a.active { color: #fff; background: rgba(255,255,255,0.08); border-left-color: var(--orange); }
        .sidebar-nav a i { width: 18px; text-align: center; font-size: 14px; }
        .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.08); }
        .sidebar-footer .user-info { font-size: 12px; color: rgba(255,255,255,0.5); margin-bottom: 8px; }
        .sidebar-footer a { color: rgba(255,255,255,0.6); text-decoration: none; font-size: 12px; }
        .sidebar-footer a:hover { color: #fff; }
        .main { flex: 1; padding: 28px 32px; }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .main-header h1 { font-size: 22px; font-weight: 700; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 11px 14px; font-weight: 600; color: var(--text-secondary); font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; background: var(--bg); border-bottom: 1px solid var(--border); white-space: nowrap; }
        td { padding: 11px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(240,90,34,0.02); }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-stock { background: rgba(5,150,105,0.12); color: #059669; }
        .badge-low { background: rgba(245,158,11,0.12); color: #d97706; }
        .badge-out { background: rgba(239,68,68,0.1); color: #dc2626; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: all 0.15s; }
        .btn-primary { background: var(--orange); color: #fff; }
        .btn-primary:hover { background: var(--orange-dark); }
        .btn-outline { background: transparent; color: var(--text-secondary); border: 1px solid var(--border); }
        .btn-outline:hover { border-color: var(--orange); color: var(--orange); }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .search-form { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .search-form input, .search-form select {
            padding: 8px 12px; border: 1.5px solid var(--border); border-radius: 8px;
            font-family: 'Inter', sans-serif; font-size: 13px; outline: none; background: #fff;
        }
        .search-form input:focus, .search-form select:focus { border-color: var(--orange); }
        .search-form button { background: var(--navy); color: #fff; border: none; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; }
        .pagination { display: flex; justify-content: center; gap: 4px; padding: 16px; }
        .pagination a { padding: 6px 12px; border: 1px solid var(--border); border-radius: 6px; text-decoration: none; color: var(--text-secondary); font-size: 13px; }
        .pagination a:hover, .pagination a.active { border-color: var(--orange); color: var(--orange); }
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .alert-success { background: rgba(5,150,105,0.1); color: #059669; border: 1px solid rgba(5,150,105,0.2); }
        .alert-error { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.2); }
        .actions { display: flex; gap: 4px; }
        @media (max-width: 768px) { .sidebar { width: 60px; } .sidebar-brand h2, .sidebar-brand .tag, .sidebar-nav a span { display: none; } .main { padding: 20px 16px; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="icon"><i class="fas fa-network-wired"></i></div>
            <div><h2>BN-Infrastructure</h2><div class="tag">Admin Panel</div></div>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
            <a href="products.php" class="active"><i class="fas fa-box"></i> <span>Products</span></a>
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
                <h1>Products</h1>
                <p><?php echo $total; ?> product(s) total</p>
            </div>
            <a href="products-add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <form class="search-form" method="GET">
                    <input type="text" name="search" placeholder="Search name, SKU, brand..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if ($search || $categoryFilter): ?>
                    <a href="products.php" class="btn btn-outline btn-sm">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Qty</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);font-size:14px;"><i class="fas fa-box-open" style="font-size:32px;display:block;margin-bottom:10px;"></i>No products found</td></tr>
                        <?php else: ?>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td style="font-weight:500;"><?php echo htmlspecialchars($p['name']); ?></td>
                            <td style="color:var(--text-muted);font-size:12px;"><?php echo htmlspecialchars($p['sku']); ?></td>
                            <td><?php echo htmlspecialchars($p['brand']); ?></td>
                            <td><?php echo htmlspecialchars($p['category_name'] ?? '—'); ?></td>
                            <td><?php echo formatTsh($p['price']); ?></td>
                            <td><?php echo getStatusBadge($p['stock_status']); ?></td>
                            <td><?php echo $p['stock_qty']; ?></td>
                            <td><?php echo $p['featured'] ? '<span style="color:#059669;"><i class="fas fa-star"></i></span>' : '—'; ?></td>
                            <td>
                                <div class="actions">
                                    <a href="products-edit.php?id=<?php echo $p['id']; ?>" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="products-delete.php?id=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this product?')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
