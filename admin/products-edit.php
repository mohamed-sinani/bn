<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';
requireLogin();

$user = getCurrentUser();
$id = (int)($_GET['id'] ?? 0);
$product = fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
if (!$product) {
    header('Location: /admin/products-edit.php?error=Product not found');
    exit;
}

$categories = fetchAll("SELECT * FROM categories ORDER BY name");
$extraImages = fetchAll("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC", [$id]);
$success = '';
$error = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete_image' && isset($_GET['img_id'])) {
    $imgId = (int)$_GET['img_id'];
    execute("DELETE FROM product_images WHERE id = ? AND product_id = ?", [$imgId, $id]);
    header("Location: ?id=$id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $category_id = $_POST['category_id'] ? (int)$_POST['category_id'] : null;
    $price = (float)($_POST['price'] ?? 0);
    $old_price = $_POST['old_price'] ? (float)$_POST['old_price'] : null;
    $stock_status = $_POST['stock_status'] ?? 'in_stock';
    $stock_qty = (int)($_POST['stock_qty'] ?? 0);
    $moq = (int)($_POST['moq'] ?? 1);
    $specs = trim($_POST['specs'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $warranty = trim($_POST['warranty'] ?? '');

    if (empty($name) || empty($sku) || $price <= 0) {
        $error = 'Product name, SKU, and a valid price are required.';
    } else {
        $dup = fetchOne("SELECT id FROM products WHERE sku = ? AND id != ?", [$sku, $id]);
        if ($dup) {
            $error = 'Another product with this SKU already exists.';
        } else {
            $image = $product['image'];
            if (!empty($_FILES['image']['name'])) {
                $targetDir = __DIR__ . '/../Homepage/';
                $uploaded = uploadImage($_FILES['image'], $targetDir);
                if ($uploaded) $image = $uploaded;
            }

            $discount = null;
            if ($old_price && $old_price > $price) {
                $discount = round((($old_price - $price) / $old_price) * 100);
            }

            execute(
                "UPDATE products SET name=?, sku=?, brand=?, category_id=?, price=?, old_price=?, image=?, stock_status=?, stock_qty=?, moq=?, specs=?, description=?, features=?, tags=?, discount_percentage=?, featured=?, warranty=? WHERE id=?",
                [$name, $sku, $brand, $category_id, $price, $old_price, $image, $stock_status, $stock_qty, $moq, $specs, $description, $features, $tags, $discount, $featured, $warranty ?: null, $id]
            );

            $success = 'Product updated successfully.';
            $product = fetchOne("SELECT * FROM products WHERE id = ?", [$id]);

            if (!empty($_FILES['extra_images']['name'][0])) {
                $targetDir = __DIR__ . '/../Homepage/';
                foreach ($_FILES['extra_images']['tmp_name'] as $k => $tmpName) {
                    if (empty($tmpName)) continue;
                    $fakeFile = ['tmp_name' => $tmpName, 'name' => $_FILES['extra_images']['name'][$k]];
                    $uploaded = uploadImage($fakeFile, $targetDir);
                    if ($uploaded) {
                        $maxOrder = fetchOne("SELECT COALESCE(MAX(sort_order),0) as mx FROM product_images WHERE product_id = ?", [$id])['mx'];
                        execute("INSERT INTO product_images (product_id, image, sort_order) VALUES (?, ?, ?)", [$id, $uploaded, $maxOrder + 1 + $k]);
                    }
                }
            }
            $extraImages = fetchAll("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC", [$id]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product — BN-Infrastructure Admin</title>
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
        .main-header { margin-bottom: 24px; }
        .main-header h1 { font-size: 22px; font-weight: 700; }
        .main-header p { font-size: 13px; color: var(--text-secondary); margin-top: 2px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; }
        .card-body { padding: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
        .form-group { margin-bottom: 18px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; }
        .form-group label .required { color: #dc2626; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 8px;
            font-family: 'Inter', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s; background: #fff;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--orange); }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group .help { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
        .form-row-checkbox { display: flex; align-items: center; gap: 8px; margin-bottom: 18px; }
        .form-row-checkbox input[type="checkbox"] { width: auto; accent-color: var(--orange); }
        .form-row-checkbox label { font-size: 13px; font-weight: 500; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: all 0.15s; }
        .btn-primary { background: var(--orange); color: #fff; }
        .btn-primary:hover { background: var(--orange-dark); }
        .btn-outline { background: transparent; color: var(--text-secondary); border: 1px solid var(--border); }
        .btn-outline:hover { border-color: var(--orange); color: var(--orange); }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .form-actions { display: flex; gap: 10px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); }
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .alert-success { background: rgba(5,150,105,0.1); color: #059669; border: 1px solid rgba(5,150,105,0.2); }
        .alert-error { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.2); }
        .current-img { display: inline-block; margin-top: 6px; max-width: 120px; border-radius: 6px; border: 1px solid var(--border); }
        @media (max-width: 768px) { .sidebar { width: 60px; } .sidebar-brand h2, .sidebar-brand .tag, .sidebar-nav a span { display: none; } .main { padding: 20px 16px; } .form-grid { grid-template-columns: 1fr; } }
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
            <h1>Edit Product</h1>
            <p>SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?> <a href="products.php" style="color:#059669;font-weight:600;">Back to products</a></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Product Name <span class="required">*</span></label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>SKU <span class="required">*</span></label>
                            <input type="text" name="sku" value="<?php echo htmlspecialchars($product['sku']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Brand</label>
                            <input type="text" name="brand" value="<?php echo htmlspecialchars($product['brand'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id">
                                <option value="">Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (TSh) <span class="required">*</span></label>
                            <input type="number" name="price" value="<?php echo $product['price']; ?>" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Old/Original Price (TSh)</label>
                            <input type="number" name="old_price" value="<?php echo $product['old_price'] ?? ''; ?>" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Stock Status</label>
                            <select name="stock_status">
                                <option value="in_stock" <?php echo $product['stock_status'] === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                <option value="low_stock" <?php echo $product['stock_status'] === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                                <option value="out_of_stock" <?php echo $product['stock_status'] === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" name="stock_qty" value="<?php echo $product['stock_qty']; ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label>Min Order Qty</label>
                            <input type="number" name="moq" value="<?php echo $product['moq']; ?>" min="1">
                        </div>
                        <div class="form-group">
                            <label>Product Image</label>
                            <input type="file" name="image" accept="image/*">
                            <?php if ($product['image']): ?>
                            <div class="help">Current: <?php echo htmlspecialchars($product['image']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Additional Images</label>
                            <input type="file" name="extra_images[]" accept="image/*" multiple>
                            <?php if (!empty($extraImages)): ?>
                            <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;">
                                <?php foreach ($extraImages as $ei): ?>
                                <div style="position:relative;width:80px;height:80px;border:1px solid var(--border);border-radius:6px;overflow:hidden;">
                                    <?php echo imageOrPlaceholder($ei['image'], 'Gallery', $product['brand'] ?? ''); ?>
                                    <a href="?action=delete_image&id=<?php echo $id; ?>&img_id=<?php echo $ei['id']; ?>" onclick="return confirm('Remove this image?')" style="position:absolute;top:2px;right:2px;background:rgba(220,38,38,0.9);color:#fff;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;text-decoration:none;">x</a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <div class="help">Upload additional gallery images</div>
                        </div>
                        <div class="form-group">
                            <label>Warranty</label>
                            <input type="text" name="warranty" value="<?php echo htmlspecialchars($product['warranty'] ?? ''); ?>" placeholder="e.g. 1 Year Manufacturer Warranty">
                        </div>
                        <div class="form-group full">
                            <label>Short Specs</label>
                            <input type="text" name="specs" value="<?php echo htmlspecialchars($product['specs'] ?? ''); ?>">
                        </div>
                        <div class="form-group full">
                            <label>Tags (comma separated)</label>
                            <input type="text" name="tags" value="<?php echo htmlspecialchars($product['tags'] ?? ''); ?>">
                        </div>
                        <div class="form-group full">
                            <label>Description</label>
                            <textarea name="description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group full">
                            <label>Features (one per line)</label>
                            <textarea name="features"><?php echo htmlspecialchars($product['features'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group full">
                            <div class="form-row-checkbox">
                                <input type="checkbox" name="featured" id="featured" value="1" <?php echo $product['featured'] ? 'checked' : ''; ?>>
                                <label for="featured">Featured Product</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Product</button>
                        <a href="products.php" class="btn btn-outline">Cancel</a>
                        <a href="products-delete.php?id=<?php echo $id; ?>" class="btn btn-danger" style="margin-left:auto;" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
