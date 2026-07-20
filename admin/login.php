<?php
require_once __DIR__ . '/../src/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username/email or password.';
    }
}

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — BN-Infrastructure Tanzania</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --navy: #0A2540;
            --navy-light: #133057;
            --orange: #F05A22;
            --orange-dark: #d44d1a;
            --bg: #F4F6F9;
            --card: #FFFFFF;
            --text-primary: #0A2540;
            --text-secondary: #5a6a7e;
            --border: #e2e8f0;
            --shadow-lg: 0 10px 30px rgba(10,37,64,0.12), 0 4px 12px rgba(10,37,64,0.08);
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            display: flex;
            background: var(--card);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            min-height: 500px;
        }
        .login-brand {
            background: var(--navy);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 380px;
            flex-shrink: 0;
            color: #fff;
        }
        .login-brand .brand-icon {
            width: 48px;
            height: 48px;
            background: var(--orange);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 24px;
        }
        .login-brand h1 { font-size: 22px; font-weight: 800; margin-bottom: 8px; }
        .login-brand p { font-size: 13px; color: rgba(255,255,255,0.6); line-height: 1.5; }
        .login-brand .brand-stats { margin-top: 30px; display: flex; gap: 20px; }
        .login-brand .brand-stats div { text-align: center; }
        .login-brand .brand-stats .num { font-size: 18px; font-weight: 700; color: var(--orange); }
        .login-brand .brand-stats .lbl { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 2px; }
        .login-form {
            padding: 50px 45px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-form h2 { font-size: 20px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
        .login-form .subtitle { font-size: 13px; color: var(--text-secondary); margin-bottom: 28px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; }
        .form-group .input-wrap {
            display: flex;
            align-items: center;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            transition: border-color 0.2s;
            background: #fff;
        }
        .form-group .input-wrap:focus-within { border-color: var(--orange); }
        .form-group .input-wrap i {
            padding: 0 0 0 14px;
            color: var(--text-muted, #8fa0b3);
            font-size: 14px;
        }
        .form-group .input-wrap input {
            flex: 1;
            border: none;
            outline: none;
            padding: 12px 14px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            background: transparent;
            color: var(--text-primary);
        }
        .btn-login {
            width: 100%;
            padding: 13px;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-login:hover { background: var(--orange-dark); transform: translateY(-1px); }
        .error-msg {
            background: #fef2f2;
            color: #dc2626;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: var(--text-secondary);
            text-decoration: none;
        }
        .back-link:hover { color: var(--orange); }
        @media (max-width: 768px) {
            .login-container { flex-direction: column; margin: 20px; }
            .login-brand { width: 100%; padding: 30px; }
            .login-form { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-brand">
            <div class="brand-icon"><i class="fas fa-network-wired"></i></div>
            <h1>BN-Infrastructure Admin</h1>
            <p>Sign in to manage your product catalog, orders, and store settings.</p>
            <div class="brand-stats">
                <div><div class="num">2K+</div><div class="lbl">Businesses</div></div>
                <div><div class="num">500+</div><div class="lbl">Products</div></div>
                <div><div class="num">30</div><div class="lbl">Regions</div></div>
            </div>
        </div>
        <div class="login-form">
            <h2>Welcome back</h2>
            <p class="subtitle">Admin access to BN-Infrastructure dashboard</p>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username or Email</label>
                    <div class="input-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="admin" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Sign In to Dashboard</button>
            </form>
            <a href="/Homepage/index.html" class="back-link"><i class="fas fa-arrow-left"></i> Back to Storefront</a>
        </div>
    </div>
</body>
</html>
