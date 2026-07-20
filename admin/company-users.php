<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';

requireLogin();

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'deactivate') {
        $memberId = intval($_POST['member_id'] ?? 0);
        if ($memberId) {
            execute("UPDATE company_users SET status = 'inactive' WHERE id = ?", [$memberId]);
            $success = 'Member deactivated.';
        }
    }

    if ($action === 'activate') {
        $memberId = intval($_POST['member_id'] ?? 0);
        if ($memberId) {
            execute("UPDATE company_users SET status = 'active' WHERE id = ?", [$memberId]);
            $success = 'Member activated.';
        }
    }

    if ($action === 'delete') {
        $memberId = intval($_POST['member_id'] ?? 0);
        if ($memberId) {
            execute("DELETE FROM company_users WHERE id = ?", [$memberId]);
            $success = 'Member permanently deleted.';
        }
    }

    if ($action === 'update_role') {
        $memberId = intval($_POST['member_id'] ?? 0);
        $newRole = $_POST['new_role'] ?? 'viewer';
        if ($memberId && in_array($newRole, ['admin', 'editor', 'viewer'])) {
            execute("UPDATE company_users SET role = ? WHERE id = ?", [$newRole, $memberId]);
            $success = "Role updated to {$newRole}.";
        }
    }
}

$search = trim($_GET['q'] ?? '');
$companyFilter = trim($_GET['company'] ?? '');

$sql = "SELECT cu.*, u.full_name AS system_name FROM company_users cu LEFT JOIN users u ON cu.user_id = u.id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (cu.name LIKE ? OR cu.email LIKE ? OR cu.company_name LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
}
if ($companyFilter) {
    $sql .= " AND cu.company_name LIKE ?";
    $params[] = "%{$companyFilter}%";
}

$sql .= " ORDER BY cu.company_name, cu.role = 'admin' DESC, cu.created_at DESC";

$members = fetchAll($sql, $params);

$companies = fetchAll("SELECT DISTINCT company_name FROM company_users WHERE company_name != '' ORDER BY company_name");

$stats = [
    'total' => fetchOne("SELECT COUNT(*) AS c FROM company_users")['c'] ?? 0,
    'active' => fetchOne("SELECT COUNT(*) AS c FROM company_users WHERE status = 'active'")['c'] ?? 0,
    'pending' => fetchOne("SELECT COUNT(*) AS c FROM company_users WHERE user_id IS NULL")['c'] ?? 0,
    'companies' => fetchOne("SELECT COUNT(DISTINCT company_name) AS c FROM company_users WHERE company_name != ''")['c'] ?? 0,
];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Authorized Users — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--orange:#F05A22;--bg:#F4F6F9;--card:#FFF;--border:#e2e8f0;--green:#059669;--red:#dc2626;--text:#0A2540;--text-sec:#5a6a7e;--text-muted:#8fa0b3}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}
.sidebar{width:250px;background:var(--navy);color:#fff;padding:0;position:fixed;top:0;left:0;height:100vh;overflow-y:auto;z-index:100}
.sidebar-header{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:10px}
.sidebar-header .icon{width:36px;height:36px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px}
.sidebar-header span{font-size:16px;font-weight:800}
.sidebar nav{padding:16px 0}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 24px;color:#aab8c5;text-decoration:none;font-size:13px;font-weight:500;transition:all .2s}
.sidebar nav a:hover,.sidebar nav a.active{background:rgba(255,255,255,0.08);color:#fff}
.sidebar nav a.active{border-left:3px solid var(--orange)}
.sidebar-section{font-size:11px;font-weight:700;color:rgba(255,255,255,0.35);padding:16px 24px 6px;text-transform:uppercase;letter-spacing:0.5px}
.main-area{margin-left:250px;flex:1;padding:0}
.top-bar{background:#fff;border-bottom:1px solid var(--border);padding:14px 32px;display:flex;justify-content:space-between;align-items:center}
.top-bar h2{font-size:18px;font-weight:800}
.top-bar-right{display:flex;align-items:center;gap:16px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .2s}
.btn-primary{background:var(--orange);color:#fff}.btn-primary:hover{background:#d44d1a}
.btn-danger{background:rgba(220,38,38,0.08);color:var(--red);border:1px solid rgba(220,38,38,0.2)}.btn-danger:hover{background:rgba(220,38,38,0.15)}
.btn-sm{padding:6px 12px;font-size:12px}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--text)}.btn-outline:hover{border-color:var(--orange)}
.content{padding:32px}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:18px}
.stat-card .stat-icon{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:10px}
.stat-card h3{font-size:24px;font-weight:800}.stat-card p{font-size:12px;color:var(--text-sec);font-weight:500}
.filter-bar{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:16px 20px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}
.filter-bar input,.filter-bar select{padding:9px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;color:var(--text);background:#fff}
.filter-bar input:focus,.filter-bar select:focus{outline:none;border-color:var(--orange)}
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden}
table{width:100%;border-collapse:collapse}
th{padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;border-bottom:2px solid var(--border);background:var(--bg)}
td{padding:12px 16px;font-size:13px;border-bottom:1px solid var(--border)}
tr:hover{background:rgba(10,37,64,0.02)}
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.status-active{background:rgba(5,150,105,0.1);color:var(--green)}
.status-inactive{background:rgba(220,38,38,0.1);color:var(--red)}
.role-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.3px}
.role-admin{background:rgba(240,90,34,0.1);color:var(--orange)}
.role-editor{background:rgba(10,37,64,0.08);color:var(--navy)}
.role-viewer{background:rgba(143,160,179,0.15);color:var(--text-sec)}
.empty{text-align:center;padding:40px;color:var(--text-sec)}
.alert{padding:12px 18px;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.alert-success{background:rgba(5,150,105,0.08);border:1px solid rgba(5,150,105,0.2);color:var(--green)}
.actions-cell{display:flex;gap:6px;align-items:center}
.select-sm{padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:11px;font-family:'Inter',sans-serif;background:#fff}
</style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-header"><div class="icon"><i class="fas fa-network-wired"></i></div><span>BN-Admin</span></div>
  <nav>
    <div class="sidebar-section">Main</div>
    <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <div class="sidebar-section">Catalog</div>
    <a href="products.php"><i class="fas fa-box"></i> Products</a>
    <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
    <div class="sidebar-section">Sales</div>
    <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="quotes.php"><i class="fas fa-file-invoice"></i> Quotations</a>
    <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
    <div class="sidebar-section">People</div>
    <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
    <a href="company-users.php" class="active"><i class="fas fa-user-shield"></i> Authorized Users</a>
    <div class="sidebar-section">Reports</div>
    <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
  </nav>
</div>

<div class="main-area">
  <div class="top-bar">
    <h2><i class="fas fa-user-shield"></i> Authorized Users</h2>
    <div class="top-bar-right"><span style="font-size:13px;color:var(--text-sec);">Manage company team access</span></div>
  </div>

  <div class="content">
    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon" style="background:rgba(10,37,64,0.08);color:var(--navy);"><i class="fas fa-users"></i></div><h3><?php echo $stats['total']; ?></h3><p>Total Users</p></div>
      <div class="stat-card"><div class="stat-icon" style="background:rgba(5,150,105,0.08);color:var(--green);"><i class="fas fa-check-circle"></i></div><h3><?php echo $stats['active']; ?></h3><p>Active Users</p></div>
      <div class="stat-card"><div class="stat-icon" style="background:rgba(240,90,34,0.08);color:var(--orange);"><i class="fas fa-clock"></i></div><h3><?php echo $stats['pending']; ?></h3><p>Pending Invites</p></div>
      <div class="stat-card"><div class="stat-icon" style="background:rgba(10,37,64,0.05);color:var(--navy);"><i class="fas fa-building"></i></div><h3><?php echo $stats['companies']; ?></h3><p>Companies</p></div>
    </div>

    <form class="filter-bar" method="GET" action="">
      <div style="flex:1;">
        <input type="text" name="q" placeholder="Search name, email, company..." value="<?php echo htmlspecialchars($search); ?>" style="width:100%;">
      </div>
      <div>
        <select name="company">
          <option value="">All Companies</option>
          <?php foreach ($companies as $c): ?>
            <option value="<?php echo htmlspecialchars($c['company_name']); ?>" <?php echo $companyFilter === $c['company_name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['company_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
    </form>

    <div class="table-wrap">
      <?php if (empty($members)): ?>
        <div class="empty"><i class="fas fa-user-shield" style="font-size:36px;margin-bottom:12px;display:block;color:var(--border);"></i>No authorized users found.</div>
      <?php else: ?>
        <table>
          <thead><tr>
            <th>User</th><th>Company</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th>
          </tr></thead>
          <tbody>
          <?php foreach ($members as $m): ?>
            <tr>
              <td>
                <div style="font-weight:600;"><?php echo htmlspecialchars($m['name']); ?></div>
                <div style="font-size:12px;color:var(--text-sec);"><?php echo htmlspecialchars($m['email']); ?></div>
              </td>
              <td><span style="font-size:12px;font-weight:600;"><?php echo htmlspecialchars($m['company_name']); ?></span></td>
              <td><span class="role-badge role-<?php echo $m['role']; ?>"><?php echo $m['role']; ?></span></td>
              <td><span class="status-badge status-<?php echo $m['status']; ?>"><?php echo $m['status']; ?></span></td>
              <td style="font-size:12px;color:var(--text-sec);"><?php echo date('M d, Y', strtotime($m['created_at'])); ?></td>
              <td>
                <div class="actions-cell">
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>">
                    <select name="new_role" onchange="if(confirm('Change role?'))this.form.submit()" class="select-sm">
                      <option value="viewer" <?php echo $m['role'] === 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                      <option value="editor" <?php echo $m['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                      <option value="admin" <?php echo $m['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                  </form>
                  <?php if ($m['status'] === 'active'): ?>
                    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="deactivate"><input type="hidden" name="member_id" value="<?php echo $m['id']; ?>"><button type="submit" class="btn btn-outline btn-sm" title="Deactivate"><i class="fas fa-ban"></i></button></form>
                  <?php else: ?>
                    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="activate"><input type="hidden" name="member_id" value="<?php echo $m['id']; ?>"><button type="submit" class="btn btn-outline btn-sm" title="Activate"><i class="fas fa-check"></i></button></form>
                  <?php endif; ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this user?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="member_id" value="<?php echo $m['id']; ?>"><button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button></form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</body></html>
