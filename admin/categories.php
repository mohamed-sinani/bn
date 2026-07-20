<?php
require_once '../config/database.php';
require_once '../src/functions.php';
require_once '../src/auth.php';
requireLogin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name && $slug) {
            execute("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)", [$name, $slug, $description]);
            $message = 'Category added.';
        } else {
            $error = 'Name and slug are required.';
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($id && $name && $slug) {
            execute("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?", [$name, $slug, $description, $id]);
            $message = 'Category updated.';
        } else {
            $error = 'Name and slug are required.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        execute("UPDATE products SET category_id = NULL WHERE category_id = ?", [$id]);
        execute("DELETE FROM categories WHERE id = ?", [$id]);
        $message = 'Category deleted.';
    }
}

$editCategory = null;
if (isset($_GET['edit'])) {
    $editCategory = fetchOne("SELECT * FROM categories WHERE id = ?", [(int)$_GET['edit']]);
}

$categories = fetchAll("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c ORDER BY c.name ASC");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categories — Admin</title>
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
.card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:18px 20px;box-shadow:var(--shadow-sm);margin-bottom:20px}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:10px 12px;font-size:11px;font-weight:700;color:#666;text-transform:uppercase;border-bottom:2px solid var(--border);white-space:nowrap}
td{padding:10px 12px;font-size:13px;border-bottom:1px solid var(--border);color:var(--navy)}
tr:hover td{background:rgba(240,90,34,0.02)}
.alert{padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:14px}
.alert-success{background:rgba(5,150,105,0.08);color:#059669;border:1px solid rgba(5,150,105,0.15)}
.alert-error{background:rgba(220,38,38,0.08);color:#dc2626;border:1px solid rgba(220,38,38,0.15)}
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:4px}
.form-group input,.form-group textarea{width:100%;padding:8px 12px;border:1.5px solid var(--border);border-radius:6px;font-family:'Inter',sans-serif;font-size:13px;outline:none}
.form-group input:focus,.form-group textarea:focus{border-color:var(--orange)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;font-family:'Inter',sans-serif;font-size:12px;font-weight:600;cursor:pointer;border:none;text-decoration:none}
.btn-primary{background:var(--orange);color:#fff}
.btn-primary:hover{background:#d44d1a}
.btn-outline{background:transparent;color:var(--navy);border:1.5px solid var(--border)}
.btn-sm{padding:5px 10px;font-size:11px}
.btn-danger{background:rgba(220,38,38,0.1);color:#dc2626}
.btn-danger:hover{background:rgba(220,38,38,0.2)}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start}
@media(max-width:768px){.sidebar{width:60px}.sidebar h2 span,.sidebar a span{display:none}.main{max-width:calc(100vw - 60px);padding:16px}.two-col{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="sidebar">
  <h2><i class="fas fa-cube"></i><span>Admin</span></h2>
  <a href="index.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
  <a href="products.php"><i class="fas fa-box"></i> <span>Products</span></a>
  <a href="products-add.php"><i class="fas fa-plus-circle"></i> <span>Add Product</span></a>
  <a href="categories.php" class="active"><i class="fas fa-tags"></i> <span>Categories</span></a>
  <a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a>
  <a href="payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
  <a href="quotes.php"><i class="fas fa-file-alt"></i> <span>Quotes</span></a>
  <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
  <a href="company-users.php"><i class="fas fa-user-shield"></i> <span>Authorized Users</span></a>
  <a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
  <a href="login.php?action=logout" style="margin-top:40px;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
</div>

<div class="main">
  <h1>Categories</h1>
  <p class="subtitle">Manage product categories</p>

  <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

  <div class="two-col">
    <div class="card">
      <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:12px;"><?php echo $editCategory ? 'Edit Category' : 'Add Category'; ?></h3>
      <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editCategory ? 'edit' : 'add'; ?>">
        <?php if ($editCategory): ?><input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>"><?php endif; ?>
        <div class="form-group">
          <label>Name</label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
          <label>Slug</label>
          <input type="text" name="slug" value="<?php echo htmlspecialchars($editCategory['slug'] ?? ''); ?>" required placeholder="e.g. switches">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="3"><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $editCategory ? 'Update Category' : 'Add Category'; ?></button>
        <?php if ($editCategory): ?><a href="categories.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a><?php endif; ?>
      </form>
    </div>

    <div class="card" style="overflow-x:auto;">
      <table>
        <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($categories)): ?>
          <tr><td colspan="4" style="text-align:center;padding:40px;color:#888;">No categories yet</td></tr>
          <?php endif; ?>
          <?php foreach ($categories as $c): ?>
          <tr>
            <td style="font-weight:600;"><?php echo htmlspecialchars($c['name']); ?></td>
            <td style="color:#888;font-size:12px;"><?php echo htmlspecialchars($c['slug']); ?></td>
            <td><?php echo $c['product_count']; ?></td>
            <td style="white-space:nowrap;">
              <a href="?edit=<?php echo $c['id']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i></a>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</body></html>
