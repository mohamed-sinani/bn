<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/functions.php';

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
$userCompany = $user['company_name'] ?? '';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'invite' && $userCompany) {
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'viewer';
        $name = trim($_POST['name'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required.';
        } elseif ($role === 'admin' && ($user['role'] ?? 'customer') !== 'admin' && ($user['role'] ?? 'customer') !== 'company_admin') {
            $errors[] = 'Only company admins can assign admin roles.';
        } else {
            $existing = fetchOne("SELECT id FROM company_users WHERE company_name = ? AND email = ?", [$userCompany, $email]);
            if ($existing) {
                $errors[] = 'This email is already a member of your company.';
            } else {
                execute(
                    "INSERT INTO company_users (company_name, user_id, name, email, role, invited_by, status) VALUES (?, ?, ?, ?, ?, ?, 'active')",
                    [$userCompany, null, $name ?: $email, $email, $role, $userId]
                );
                $success = "Invitation sent to {$email} ({$role}).";
            }
        }
    }

    if ($action === 'remove') {
        $memberId = intval($_POST['member_id'] ?? 0);
        if ($memberId) {
            $member = fetchOne("SELECT * FROM company_users WHERE id = ? AND company_name = ?", [$memberId, $userCompany]);
            if ($member && $member['invited_by'] != $userId) {
                execute("DELETE FROM company_users WHERE id = ?", [$memberId]);
                $success = 'Member removed.';
            } elseif ($member && $member['invited_by'] == $userId) {
                $errors[] = 'You cannot remove yourself.';
            }
        }
    }

    if ($action === 'update_role') {
        $memberId = intval($_POST['member_id'] ?? 0);
        $newRole = $_POST['new_role'] ?? 'viewer';
        if ($memberId && in_array($newRole, ['admin', 'editor', 'viewer'])) {
            $member = fetchOne("SELECT * FROM company_users WHERE id = ? AND company_name = ?", [$memberId, $userCompany]);
            if ($member) {
                execute("UPDATE company_users SET role = ? WHERE id = ?", [$newRole, $memberId]);
                $success = "Role updated to {$newRole}.";
            }
        }
    }
}

$members = $userCompany
    ? fetchAll("SELECT cu.*, u.full_name AS system_name FROM company_users cu LEFT JOIN users u ON cu.user_id = u.id WHERE cu.company_name = ? ORDER BY cu.role = 'admin' DESC, cu.created_at ASC", [$userCompany])
    : [];

$pendingInvites = $userCompany
    ? fetchAll("SELECT * FROM company_users WHERE company_name = ? AND user_id IS NULL ORDER BY created_at ASC", [$userCompany])
    : [];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Team Members — BN-Infrastructure</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--orange:#F05A22;--bg:#F4F6F9;--card:#FFF;--border:#e2e8f0;--green:#059669;--red:#dc2626;--text:#0A2540;--text-sec:#5a6a7e;--text-muted:#8fa0b3}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
.top-bar{background:var(--navy);color:#fff;padding:12px 0;font-size:13px}
.top-bar-inner{max-width:1200px;margin:0 auto;padding:0 24px;display:flex;justify-content:space-between;align-items:center}
.top-bar a{color:#aab8c5;text-decoration:none;margin-left:14px}.top-bar a:hover{color:#fff}
.navbar{background:#fff;border-bottom:1px solid var(--border);padding:14px 0;position:sticky;top:0;z-index:100}
.nav-inner{max-width:1200px;margin:0 auto;padding:0 24px;display:flex;align-items:center;gap:32px}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.logo-icon{width:36px;height:36px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px}
.logo-text{font-size:18px;font-weight:800;color:var(--navy)}
.main{max-width:1000px;margin:40px auto;padding:0 24px}
.page-header{margin-bottom:32px}
.page-header h1{font-size:28px;font-weight:900;margin-bottom:4px}
.page-header p{color:var(--text-sec);font-size:15px}
.alert{padding:14px 18px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.alert-success{background:rgba(5,150,105,0.08);border:1px solid rgba(5,150,105,0.2);color:var(--green)}
.alert-error{background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.2);color:var(--red)}
.alert-info{background:rgba(10,37,64,0.05);border:1px solid var(--border);color:var(--text-sec)}
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:28px;margin-bottom:24px;box-shadow:0 2px 8px rgba(10,37,64,0.05)}
.card-title{font-size:16px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.form-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px}
.form-group{display:flex;flex-direction:column;gap:5px}
.form-group.full{grid-column:1/-1}
.form-group label{font-size:12px;font-weight:600;color:var(--text-sec);text-transform:uppercase;letter-spacing:0.3px}
.form-group input,.form-group select{padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:'Inter',sans-serif;font-size:14px;color:var(--text);background:#fff;transition:border-color .2s}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--orange)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .2s}
.btn-primary{background:var(--orange);color:#fff}.btn-primary:hover{background:#d44d1a}
.btn-danger{background:rgba(220,38,38,0.08);color:var(--red);border:1px solid rgba(220,38,38,0.2)}.btn-danger:hover{background:rgba(220,38,38,0.15)}
.btn-sm{padding:6px 12px;font-size:12px}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--text)}.btn-outline:hover{border-color:var(--orange)}
.member-grid{display:flex;flex-direction:column;gap:10px}
.member-row{display:grid;grid-template-columns:40px 1fr auto;gap:14px;align-items:center;padding:14px 18px;background:var(--bg);border-radius:10px;border:1px solid transparent;transition:border-color .2s}
.member-row:hover{border-color:var(--border)}
.member-avatar{width:40px;height:40px;border-radius:50%;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700}
.member-info h4{font-size:14px;font-weight:600;margin-bottom:2px}.member-info p{font-size:12px;color:var(--text-sec)}
.member-actions{display:flex;gap:8px;align-items:center}
.role-badge{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.3px}
.role-admin{background:rgba(240,90,34,0.1);color:var(--orange)}
.role-editor{background:rgba(10,37,64,0.08);color:var(--navy)}
.role-viewer{background:rgba(143,160,179,0.15);color:var(--text-sec)}
.no-company{text-align:center;padding:60px 20px}.no-company i{font-size:48px;color:var(--border);margin-bottom:16px;display:block}.no-company h2{font-size:20px;margin-bottom:8px}.no-company p{color:var(--text-sec);font-size:14px;margin-bottom:20px}
.footer{margin-top:60px;padding:32px 0;border-top:1px solid var(--border);text-align:center;font-size:13px;color:var(--text-muted)}
</style>
</head>
<body>

<div class="top-bar">
  <div class="top-bar-inner">
    <span>Welcome, <?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></span>
    <div><a href="/dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a><a href="/account.php"><i class="fas fa-user"></i> Account</a><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
  </div>
</div>

<nav class="navbar">
  <div class="nav-inner">
    <a href="/" class="logo"><div class="logo-icon"><i class="fas fa-network-wired"></i></div><span class="logo-text">BN-Infrastructure</span></a>
  </div>
</nav>

<div class="main">
  <div class="page-header">
    <h1><i class="fas fa-users"></i> Team Members</h1>
    <p>Manage who has access to your company's account and orders.</p>
  </div>

  <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
  <?php foreach ($errors as $err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($err); ?></div><?php endforeach; ?>

  <?php if (!$userCompany): ?>
    <div class="card no-company">
      <i class="fas fa-building"></i>
      <h2>No Company Associated</h2>
      <p>Your account is not linked to a company. Add a company name to your profile to manage team members.</p>
      <a href="account.php" class="btn btn-primary"><i class="fas fa-user-edit"></i> Edit Profile</a>
    </div>
  <?php else: ?>

    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i>
      Company: <strong><?php echo htmlspecialchars($userCompany); ?></strong> — All members share access to orders, quotes, and resources for this company.
    </div>

    <div class="card">
      <div class="card-title"><i class="fas fa-user-plus"></i> Invite Team Member</div>
      <form method="POST" action="">
        <input type="hidden" name="action" value="invite">
        <div class="form-row">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="John Mwangi" required>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="john@company.co.tz" required>
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="role">
              <option value="viewer">Viewer — Can view orders & quotes</option>
              <option value="editor">Editor — Can place orders & request quotes</option>
              <option value="admin">Admin — Full access to manage team</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Invitation</button>
      </form>
    </div>

    <div class="card">
      <div class="card-title"><i class="fas fa-users"></i> Active Members (<?php echo count($members); ?>)</div>
      <?php if (empty($members)): ?>
        <p style="color:var(--text-sec);font-size:14px;text-align:center;padding:20px;">No team members yet.</p>
      <?php else: ?>
        <div class="member-grid">
          <?php foreach ($members as $m): ?>
            <div class="member-row">
              <div class="member-avatar"><?php echo strtoupper(substr($m['name'], 0, 1)); ?></div>
              <div class="member-info">
                <h4><?php echo htmlspecialchars($m['name']); ?> <?php if ($m['user_id'] == $userId): ?><span style="font-size:11px;color:var(--orange);font-weight:600;">(You)</span><?php endif; ?></h4>
                <p><?php echo htmlspecialchars($m['email']); ?> · Joined <?php echo date('M d, Y', strtotime($m['created_at'])); ?></p>
              </div>
              <div class="member-actions">
                <span class="role-badge role-<?php echo $m['role']; ?>"><?php echo $m['role']; ?></span>
                <?php if ($m['user_id'] != $userId && ($user['role'] ?? '') === 'company_admin'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>">
                    <select name="new_role" onchange="this.form.submit()" style="padding:4px 8px;border:1px solid var(--border);border-radius:6px;font-size:11px;font-family:'Inter',sans-serif;background:#fff;">
                      <option value="viewer" <?php echo $m['role'] === 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                      <option value="editor" <?php echo $m['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                      <option value="admin" <?php echo $m['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                  </form>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this member?')">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-times"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($pendingInvites)): ?>
    <div class="card">
      <div class="card-title"><i class="fas fa-clock"></i> Pending Invitations (<?php echo count($pendingInvites); ?>)</div>
      <div class="member-grid">
        <?php foreach ($pendingInvites as $inv): ?>
          <div class="member-row" style="opacity:0.7;">
            <div class="member-avatar" style="background:var(--border);color:var(--text-sec);"><i class="fas fa-envelope"></i></div>
            <div class="member-info">
              <h4><?php echo htmlspecialchars($inv['email']); ?></h4>
              <p>Invited as <?php echo $inv['role']; ?> · Sent <?php echo date('M d, Y', strtotime($inv['created_at'])); ?></p>
            </div>
            <div class="member-actions">
              <span class="role-badge role-<?php echo $inv['role']; ?>"><?php echo $inv['role']; ?></span>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this invitation?')">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="member_id" value="<?php echo $inv['id']; ?>">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Cancel</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card" style="background:rgba(10,37,64,0.02);">
      <div class="card-title"><i class="fas fa-info-circle"></i> Role Permissions</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
        <div style="padding:14px;background:#fff;border-radius:8px;border:1px solid var(--border);">
          <div style="font-size:13px;font-weight:700;margin-bottom:8px;color:var(--navy);">Admin</div>
          <ul style="font-size:12px;color:var(--text-sec);list-style:none;padding:0;"><li>✓ Manage team members</li><li>✓ Place & track orders</li><li>✓ Request quotes</li><li>✓ View all invoices</li></ul>
        </div>
        <div style="padding:14px;background:#fff;border-radius:8px;border:1px solid var(--border);">
          <div style="font-size:13px;font-weight:700;margin-bottom:8px;color:var(--navy);">Editor</div>
          <ul style="font-size:12px;color:var(--text-sec);list-style:none;padding:0;"><li>✗ Manage team</li><li>✓ Place & track orders</li><li>✓ Request quotes</li><li>✓ View all invoices</li></ul>
        </div>
        <div style="padding:14px;background:#fff;border-radius:8px;border:1px solid var(--border);">
          <div style="font-size:13px;font-weight:700;margin-bottom:8px;color:var(--navy);">Viewer</div>
          <ul style="font-size:12px;color:var(--text-sec);list-style:none;padding:0;"><li>✗ Manage team</li><li>✗ Place orders</li><li>✓ Request quotes</li><li>✓ View all invoices</li></ul>
        </div>
      </div>
    </div>

  <?php endif; ?>
</div>

<footer class="footer"><div class="main"><p>&copy; <?php echo date('Y'); ?> BN-Infrastructure. All rights reserved.</p></div></footer>
</body></html>
