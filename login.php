<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/functions.php';

$signinError = '';
$registerError = '';
$registerSuccess = '';
$redirectAfterLogin = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'signin') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            if ($user['role'] === 'admin') {
                header('Location: /admin/index.php');
            } elseif ($redirectAfterLogin) {
                header('Location: ' . $redirectAfterLogin);
            } else {
                header('Location: /');
            }
            exit;
        } else {
            $signinError = 'Invalid email or password. Please try again.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        $company = trim($_POST['company'] ?? '');
        $bizType = $_POST['biz_type'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);

        if (empty($company) || empty($fullName) || empty($email) || empty($password)) {
            $registerError = 'Please fill in all required fields.';
        } elseif ($password !== $confirm) {
            $registerError = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $registerError = 'Password must be at least 8 characters.';
        } elseif (!$terms) {
            $registerError = 'You must agree to the Terms of Service.';
        } else {
            $existing = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing) {
                $registerError = 'An account with this email already exists.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                execute(
                    "INSERT INTO users (username, email, password, role, company, business_type, full_name, phone) VALUES (?, ?, ?, 'customer', ?, ?, ?, ?)",
                    [$email, $email, $hashed, $company, $bizType, $fullName, $phone]
                );
                $newUser = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
                if ($newUser) {
                    $_SESSION['user_id'] = $newUser['id'];
                    $_SESSION['user_name'] = $newUser['full_name'];
                    $_SESSION['user_email'] = $newUser['email'];
                    $_SESSION['user_role'] = $newUser['role'];
                    $registerRedirect = $redirectAfterLogin ?: '/';
                    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Welcome!</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet"><style>
                    *{margin:0;padding:0;box-sizing:border-box}body{font-family:Inter,sans-serif;background:#0A2540;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:24px}
                    .loader{width:60px;height:60px;border:4px solid rgba(255,255,255,0.1);border-top-color:#F05A22;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 24px}
                    @keyframes spin{to{transform:rotate(360deg)}}
                    h1{color:#fff;font-size:24px;font-weight:800;margin-bottom:8px}
                    p{color:#fff;font-size:14px}
                    .progress{width:240px;height:3px;background:rgba(255,255,255,0.1);border-radius:3px;margin:24px auto 0;overflow:hidden}
                    .progress-bar{height:100%;background:var(--orange,#F05A22);border-radius:3px;animation:progress 2s ease forwards}
                    @keyframes progress{from{width:0}to{width:100%}}
                    .check{display:none;font-size:48px;color:#059669;animation:pop .3s ease}
                    @keyframes pop{0%{transform:scale(0)}50%{transform:scale(1.2)}100%{transform:scale(1)}}
                    </style></head><body><div>
                    <div class="loader" id="loader"></div>
                    <div class="check" id="check"><i class="fas fa-check-circle"></i></div>
                    <h1 id="title">Creating your account...</h1>
                    <p id="subtitle">Setting up your BN-Infrastructure business profile</p>
                    <div class="progress"><div class="progress-bar"></div></div>
                    </div>
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
                    <script>
                    setTimeout(() => {
                        document.getElementById("loader").style.display = "none";
                        document.getElementById("check").style.display = "block";
                        document.getElementById("title").textContent = "Welcome, ' . htmlspecialchars($fullName, ENT_QUOTES) . '!";
                        document.getElementById("subtitle").textContent = "Redirecting to marketplace...";
                    }, 1800);
                    setTimeout(() => { window.location.href = "' . $registerRedirect . '"; }, 2800);
                    </script>
                    </body></html>';
                    exit;
                }
                $registerSuccess = 'Account created successfully! You can now sign in.';
            }
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In / Register — BN-Infrastructure</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --navy: #0A2540; --navy-light: #133057; --navy-dark: #071a2e; --navy-mid: #0f2e50;
      --orange: #F05A22; --orange-dark: #d44d1a; --orange-light: #ff6b35;
      --bg: #F4F6F9; --card: #FFFFFF; --text-primary: #0A2540; --text-secondary: #5a6a7e; --text-muted: #8fa0b3;
      --border: #e2e8f0; --border-focus: #F05A22;
      --shadow-sm: 0 1px 3px rgba(10,37,64,0.08), 0 1px 2px rgba(10,37,64,0.04);
      --shadow-md: 0 4px 12px rgba(10,37,64,0.1), 0 2px 6px rgba(10,37,64,0.06);
      --shadow-lg: 0 10px 30px rgba(10,37,64,0.12), 0 4px 12px rgba(10,37,64,0.08);
      --shadow-xl: 0 20px 60px rgba(10,37,64,0.15), 0 8px 20px rgba(10,37,64,0.1);
    }
    html { scroll-behavior: smooth; height: 100%; }
    body {
      font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-primary);
      font-size: 15px; line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column;
    }
    .navbar {
      background: var(--navy); padding: 0 48px; display: flex; align-items: center;
      height: 66px; position: sticky; top: 0; z-index: 1000;
      box-shadow: 0 2px 12px rgba(0,0,0,0.2); flex-shrink: 0;
    }
    .nav-logo {
      display: flex; align-items: center; gap: 10px; text-decoration: none; margin-right: 40px;
    }
    .nav-logo-icon {
      width: 36px; height: 36px; background: var(--orange); border-radius: 8px;
      display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff;
    }
    .nav-logo-text .brand { font-size: 17px; font-weight: 800; color: #fff; letter-spacing: -0.02em; line-height: 1.1; }
    .nav-logo-text .tagline { font-size: 10px; font-weight: 400; color: #fff; letter-spacing: 0.08em; text-transform: uppercase; }
    .nav-links { display: flex; align-items: center; gap: 4px; flex: 1; }
    .nav-links a {
      text-decoration: none; color: rgba(255,255,255,0.65); font-size: 14px; font-weight: 500;
      padding: 7px 13px; border-radius: 6px; transition: color 0.2s, background 0.2s; white-space: nowrap;
    }
    .nav-links a:hover { color: #fff; background: rgba(255,255,255,0.08); }
    .hamburger {
      display: none; align-items: center; justify-content: center;
      background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);
      border-radius: 8px; color: #fff; font-size: 18px; cursor: pointer; padding: 8px 10px;
      margin-left: auto; transition: background 0.2s; line-height: 1;
    }
    .hamburger:hover { background: rgba(255,255,255,0.15); }
    .hamburger:active { transform: scale(0.95); }
    .nav-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; }
    .nav-overlay.open { display: block; }
    .mobile-nav {
      position: fixed; top: 0; right: -300px; width: 300px; height: 100vh;
      background: #fff; z-index: 1001; transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      padding: 100px 24px 24px; overflow-y: auto; box-shadow: -8px 0 40px rgba(0,0,0,0.2);
    }
    .mobile-nav.open { right: 0; }
    .mobile-nav-header {
      position: absolute; top: 0; left: 0; right: 0; display: flex; align-items: center;
      gap: 10px; padding: 16px 20px; background: var(--navy);
    }
    .mobile-nav-header .mh-icon {
      width: 32px; height: 32px; background: var(--orange); border-radius: 7px;
      display: flex; align-items: center; justify-content: center; font-size: 14px; color: #fff; flex-shrink: 0;
    }
    .mobile-nav-header .mh-text { display: flex; flex-direction: column; }
    .mobile-nav-header .mh-text .mh-brand { font-size: 15px; font-weight: 700; color: #fff; line-height: 1.1; }
    .mobile-nav-header .mh-text .mh-tag { font-size: 9px; font-weight: 400; color: #fff; text-transform: uppercase; letter-spacing: 0.1em; }
    .mobile-nav-header .close-btn {
      margin-left: auto; background: rgba(255,255,255,0.1); border: none; border-radius: 6px;
      color: #fff; font-size: 16px; cursor: pointer; padding: 6px 8px; transition: background 0.2s; line-height: 1;
    }
    .mobile-nav-header .close-btn:hover { background: rgba(255,255,255,0.2); }
    .mobile-nav a {
      display: flex; align-items: center; gap: 12px; color: var(--text-primary); text-decoration: none;
      padding: 14px 4px; font-size: 14px; font-weight: 500; border-bottom: 1px solid var(--border); transition: color 0.2s;
    }
    .mobile-nav a i { width: 20px; color: var(--orange); font-size: 14px; text-align: center; }
    .mobile-nav a:hover { color: var(--orange); }
    .mobile-nav .mobile-user{display:flex;align-items:center;gap:12px;color:var(--orange);padding:14px 4px;font-size:14px;font-weight:600;border-bottom:1px solid var(--border)}.mobile-nav .mobile-user i{width:20px;font-size:14px;text-align:center}.mobile-nav .mobile-logout{color:var(--text-muted)!important;font-size:13px!important;border-bottom:none!important;padding:10px 4px!important}.mobile-nav .mobile-logout i{color:var(--text-muted)!important}

    .auth-wrapper { flex: 1; display: flex; min-height: 0; }
    .left-panel {
      width: 45%; background: var(--navy); position: relative; overflow: hidden;
      display: flex; flex-direction: column; padding: 52px 52px 40px;
    }
    .left-panel::before {
      content: ''; position: absolute; inset: 0;
      background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
      background-size: 40px 40px; z-index: 0;
    }
    .left-panel::after {
      content: ''; position: absolute; inset: 0;
      background: radial-gradient(ellipse 500px 400px at 80% 20%, rgba(240,90,34,0.12) 0%, transparent 70%),
                  radial-gradient(ellipse 400px 500px at 10% 80%, rgba(19,48,87,0.8) 0%, transparent 60%),
                  radial-gradient(ellipse 300px 300px at 50% 60%, rgba(255,255,255,0.02) 0%, transparent 70%);
      z-index: 0;
    }
    .left-panel-inner { position: relative; z-index: 1; display: flex; flex-direction: column; height: 100%; }
    .lp-logo { display: flex; align-items: center; gap: 11px; margin-bottom: 56px; text-decoration: none; }
    .lp-logo-icon {
      width: 44px; height: 44px; background: var(--orange); border-radius: 10px;
      display: flex; align-items: center; justify-content: center; font-size: 20px; color: #fff;
      box-shadow: 0 4px 16px rgba(240,90,34,0.4);
    }
    .lp-logo-text .name { font-size: 20px; font-weight: 800; color: #fff; letter-spacing: -0.02em; line-height: 1.1; }
    .lp-logo-text .sub { font-size: 11px; color: rgba(255,255,255,0.45); font-weight: 400; letter-spacing: 0.08em; text-transform: uppercase; }
    .lp-tagline {
      font-size: clamp(22px, 2.2vw, 32px); font-weight: 800; color: #fff;
      line-height: 1.15; letter-spacing: -0.03em; margin-bottom: 18px;
    }
    .lp-tagline span { color: var(--orange); }
    .lp-desc {
      font-size: 15px; color: rgba(255,255,255,0.6); line-height: 1.7;
      margin-bottom: 40px; max-width: 400px;
    }
    .lp-trust { display: flex; flex-direction: column; gap: 16px; margin-bottom: auto; }
    .trust-point {
      display: flex; align-items: flex-start; gap: 14px; padding: 18px 20px;
      background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
      border-radius: 12px; transition: background 0.2s;
    }
    .trust-point:hover { background: rgba(255,255,255,0.08); }
    .tp-icon {
      width: 40px; height: 40px; background: rgba(240,90,34,0.15); border-radius: 9px;
      display: flex; align-items: center; justify-content: center; font-size: 17px; color: var(--orange); flex-shrink: 0;
    }
    .tp-text h5 { font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 2px; }
    .tp-text p { font-size: 12.5px; color: rgba(255,255,255,0.5); line-height: 1.5; }
    .lp-nodes { position: absolute; right: -60px; top: 50%; transform: translateY(-50%); opacity: 0.06; z-index: 0; }
    .lp-contact {
      margin-top: 40px; padding-top: 28px; border-top: 1px solid rgba(255,255,255,0.08);
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
    }
    .lp-contact-info { display: flex; flex-direction: column; gap: 5px; }
    .lp-contact-row {
      display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: rgba(255,255,255,0.5);
    }
    .lp-contact-row i { color: var(--orange); font-size: 11px; }
    .lp-whatsapp {
      display: inline-flex; align-items: center; gap: 7px;
      background: rgba(37,211,102,0.12); border: 1px solid rgba(37,211,102,0.25);
      color: #25d366; font-size: 13px; font-weight: 600; padding: 8px 14px;
      border-radius: 8px; text-decoration: none; transition: all 0.2s;
    }
    .lp-whatsapp:hover { background: rgba(37,211,102,0.2); border-color: rgba(37,211,102,0.4); }

    .right-panel {
      width: 55%; background: var(--bg); display: flex; flex-direction: column;
      align-items: center; justify-content: flex-start; padding: 44px 64px 40px; overflow-y: auto;
    }
    .auth-card { width: 100%; max-width: 560px; }
    .tab-switcher {
      display: flex; background: #e8edf3; border-radius: 12px;
      padding: 4px; margin-bottom: 36px; position: relative;
    }
    .tab-btn {
      flex: 1; padding: 10px 16px; border: none; background: transparent;
      font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600;
      color: var(--text-secondary); cursor: pointer; border-radius: 9px;
      transition: color 0.2s; position: relative; z-index: 1;
    }
    .tab-btn.active { color: var(--navy); }
    .tab-slider {
      position: absolute; top: 4px; height: calc(100% - 8px); width: calc(50% - 4px);
      background: #fff; border-radius: 9px;
      box-shadow: 0 2px 8px rgba(10,37,64,0.1);
      transition: left 0.25s cubic-bezier(0.4, 0, 0.2, 1); left: 4px;
    }
    .tab-slider.right { left: calc(50%); }
    .form-panel { display: none; }
    .form-panel.active { display: block; }
    .form-header { margin-bottom: 28px; }
    .form-header h2 {
      font-size: 24px; font-weight: 800; color: var(--navy);
      letter-spacing: -0.02em; margin-bottom: 5px;
    }
    .form-header p { font-size: 14px; color: var(--text-secondary); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; letter-spacing: -0.01em; }
    .form-group label .required { color: var(--orange); margin-left: 2px; }
    .input-wrap { position: relative; }
    .input-wrap .input-icon {
      position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
      color: var(--text-muted); font-size: 13px; pointer-events: none; transition: color 0.2s;
    }
    .form-group input, .form-group select {
      width: 100%; padding: 11px 14px 11px 38px; border: 1.5px solid var(--border);
      border-radius: 9px; font-family: 'Inter', sans-serif; font-size: 14px;
      color: var(--text-primary); background: #fff; outline: none;
      transition: border-color 0.2s, box-shadow 0.2s; appearance: none;
    }
    .form-group input:focus, .form-group select:focus {
      border-color: var(--orange); box-shadow: 0 0 0 3px rgba(240,90,34,0.1);
    }
    .form-group .input-wrap:focus-within .input-icon { color: var(--orange); }
    .form-group input::placeholder { color: #b0bec9; }
    .form-group select { cursor: pointer; }
    .form-group select option[value=""] { color: #b0bec9; }
    .phone-wrap { display: flex; gap: 0; }
    .phone-prefix {
      background: #f0f3f7; border: 1.5px solid var(--border); border-right: none;
      border-radius: 9px 0 0 9px; padding: 11px 12px; font-size: 13px;
      font-weight: 600; color: var(--text-secondary); display: flex;
      align-items: center; gap: 5px; white-space: nowrap; flex-shrink: 0;
    }
    .phone-wrap input { border-radius: 0 9px 9px 0 !important; padding-left: 14px !important; }
    .pw-toggle {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; color: var(--text-muted); font-size: 13px;
      cursor: pointer; padding: 4px; transition: color 0.2s;
    }
    .pw-toggle:hover { color: var(--navy); }
    .form-group input.has-toggle { padding-right: 38px; }
    .select-wrap { position: relative; }
    .select-wrap::after {
      content: '\f107'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
      position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
      color: var(--text-muted); pointer-events: none; font-size: 13px;
    }
    .select-wrap select { padding-right: 36px; }
    .form-row-inline {
      display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;
    }
    .checkbox-label {
      display: flex; align-items: center; gap: 8px; font-size: 13.5px;
      color: var(--text-secondary); cursor: pointer; user-select: none;
    }
    .checkbox-label input[type="checkbox"] {
      width: 16px; height: 16px; border: 1.5px solid var(--border);
      border-radius: 4px; cursor: pointer; accent-color: var(--orange); flex-shrink: 0;
    }
    .forgot-link {
      font-size: 13.5px; color: var(--orange); text-decoration: none; font-weight: 600; transition: color 0.2s;
    }
    .forgot-link:hover { color: var(--orange-dark); text-decoration: underline; }
    .btn-auth {
      width: 100%; background: var(--orange); color: #fff; border: none;
      padding: 14px 24px; border-radius: 10px; font-family: 'Inter', sans-serif;
      font-size: 15px; font-weight: 700; cursor: pointer; display: flex;
      align-items: center; justify-content: center; gap: 8px;
      transition: all 0.2s; letter-spacing: -0.01em;
    }
    .btn-auth:hover { background: var(--orange-dark); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(240,90,34,0.35); }
    .btn-auth:active { transform: translateY(0); box-shadow: none; }
    .form-help { text-align: center; margin-top: 22px; font-size: 13px; color: var(--text-muted); }
    .form-help a { color: var(--orange); font-weight: 600; text-decoration: none; }
    .form-help a:hover { text-decoration: underline; }
    .terms-group { margin-bottom: 20px; }
    .terms-group .checkbox-label { align-items: flex-start; font-size: 13px; color: var(--text-secondary); line-height: 1.5; }
    .terms-group .checkbox-label a { color: var(--orange); text-decoration: none; font-weight: 600; }
    .terms-group .checkbox-label a:hover { text-decoration: underline; }
    .pw-strength { margin-top: 7px; display: flex; align-items: center; gap: 8px; }
    .pw-strength-bars { display: flex; gap: 4px; flex: 1; }
    .pw-bar { flex: 1; height: 3px; border-radius: 2px; background: var(--border); transition: background 0.3s; }
    .pw-bar.filled-weak { background: #e53e3e; }
    .pw-bar.filled-medium { background: #d97706; }
    .pw-bar.filled-strong { background: #059669; }
    .pw-strength-label { font-size: 11px; color: var(--text-muted); white-space: nowrap; min-width: 48px; text-align: right; }
    .form-section-label {
      font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
      color: var(--text-muted); margin: 20px 0 14px; display: flex; align-items: center; gap: 8px;
    }
    .form-section-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }
    .error-msg {
      background: #fef2f2; color: #dc2626; padding: 10px 14px; border-radius: 8px;
      font-size: 13px; margin-bottom: 16px; display: flex; align-items: center; gap: 7px;
    }
    .success-msg {
      background: rgba(5,150,105,0.1); color: #059669; padding: 10px 14px; border-radius: 8px;
      font-size: 13px; margin-bottom: 16px; display: flex; align-items: center; gap: 7px;
    }
    .right-panel::-webkit-scrollbar { width: 6px; }
    .right-panel::-webkit-scrollbar-track { background: transparent; }
    .right-panel::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
    .right-panel::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
    .reveal { opacity: 0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
    .user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}
    @media (max-width: 1024px) { .left-panel { width: 42%; padding: 40px 40px 32px; } .right-panel { width: 58%; padding: 36px 44px 36px; } }
    @media (max-width: 768px) {
      .nav-links { display: none; } .hamburger { display: flex; }
      .auth-wrapper { flex-direction: column; }
      .left-panel { width: 100%; padding: 36px 24px 32px; min-height: auto;display:none; }
      .lp-nodes { display: none; }
      .right-panel { width: 100%; padding: 32px 24px 40px; }
      .navbar { padding: 0 20px; }
      .form-row { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
      .form-row { grid-template-columns: 1fr; }
      .right-panel { padding: 24px 16px 32px; }
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20% { transform: translateX(-8px); }
      40% { transform: translateX(8px); }
      60% { transform: translateX(-5px); }
      80% { transform: translateX(5px); }
    }
    footer{background:var(--navy-dark);padding:48px 48px 0;margin-top:48px}.footer-inner{max-width:1440px;margin:0 auto}.footer-grid{display:grid;grid-template-columns:280px 1fr 1fr 300px;gap:48px;padding-bottom:48px;border-bottom:1px solid rgba(255,255,255,0.08)}.footer-brand .logo{display:flex;align-items:center;gap:10px;margin-bottom:16px}.footer-brand .logo .icon{width:36px;height:36px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:17px;color:#fff}.footer-brand .logo .name{font-size:18px;font-weight:800;color:#fff}.footer-brand p{font-size:13px;color:#fff;line-height:1.7;margin-bottom:20px}.footer-socials{display:flex;gap:8px}.footer-social-btn{width:34px;height:34px;background:rgba(255,255,255,0.06);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;cursor:pointer;transition:all .2s;text-decoration:none}.footer-social-btn:hover{background:var(--orange);color:#fff}.footer-col h4{font-size:13px;font-weight:700;color:#fff;letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px}.footer-col ul{list-style:none;display:flex;flex-direction:column;gap:10px}.footer-col ul li a{font-size:13px;color:#fff;text-decoration:none;transition:color .2s;display:flex;align-items:center;gap:7px}.footer-col ul li a:hover{color:rgba(255,255,255,0.9)}.footer-col ul li a i{font-size:11px;color:var(--orange);opacity:.7}.contact-item{display:flex;gap:10px;margin-bottom:14px}.contact-item i{color:var(--orange);font-size:14px;margin-top:2px;flex-shrink:0}.contact-item span{font-size:13px;color:#fff;line-height:1.5}.newsletter-label{font-size:13px;color:#fff;margin-bottom:12px;line-height:1.5}.newsletter-form{display:flex;gap:8px}.newsletter-form input{flex:1;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:7px;padding:10px 14px;color:#fff;font-family:'Inter',sans-serif;font-size:13px;outline:none;transition:border-color .2s}.newsletter-form input:focus{border-color:var(--orange)}.newsletter-form button{background:var(--orange);color:#fff;border:none;padding:10px 18px;border-radius:7px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;white-space:nowrap}.newsletter-form button:hover{background:var(--orange-dark)}.footer-bottom{display:flex;align-items:center;justify-content:space-between;padding:20px 0;flex-wrap:wrap;gap:12px}.footer-bottom p{font-size:12px;color:#fff}.footer-bottom-links{display:flex;gap:16px}.footer-bottom-links a{font-size:12px;color:#fff;text-decoration:none;transition:color .2s}.footer-bottom-links a:hover{color:var(--orange)}@media(max-width:768px){.footer-grid{grid-template-columns:1fr;gap:32px}.footer-brand{grid-column:1}.footer-bottom{flex-direction:column;text-align:center}footer{padding:32px 20px 0}}
  </style>
</head>
<body>

  <nav class="navbar">
    <a href="index.php" class="nav-logo">
      <div class="nav-logo-icon"><i class="fas fa-network-wired"></i></div>
      <div class="nav-logo-text"><span class="brand">BN-Infrastructure</span><span class="tagline">Tanzania</span></div>
    </a>
    <div class="nav-links">
      <a href="index.php">Home</a>
      <a href="catalog.php">Products</a>
      <a href="about.php">Solutions</a>
      <a href="track.php">Track Order</a>
      <?php echo userNavHtml(); ?>
    </div>
    <button class="hamburger" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
  </nav>
  <div class="nav-overlay" id="navOverlay" onclick="toggleMenu()"></div>
  <div class="mobile-nav" id="mobileNav">
    <div class="mobile-nav-header">
      <div class="mh-icon"><i class="fas fa-network-wired"></i></div>
      <div class="mh-text"><span class="mh-brand">BN-Infrastructure</span><span class="mh-tag">Tanzania</span></div>
      <button class="close-btn" onclick="toggleMenu()"><i class="fas fa-times"></i></button>
    </div>
    <a href="index.php" onclick="toggleMenu()"><i class="fas fa-home"></i> Home</a>
    <a href="catalog.php" onclick="toggleMenu()"><i class="fas fa-box"></i> Products</a>
    <a href="about.php" onclick="toggleMenu()"><i class="fas fa-lightbulb"></i> Solutions</a>
    <a href="track.php" onclick="toggleMenu()"><i class="fas fa-truck"></i> Track Order</a>
    <?php echo mobileAccountHtml(); ?>
    <a href="cart.php" onclick="toggleMenu()"><i class="fas fa-shopping-cart"></i> Cart</a>
  </div>

  <div class="auth-wrapper">
    <div class="left-panel">
      <svg class="lp-nodes" width="340" height="500" viewBox="0 0 340 500" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="170" cy="250" r="120" stroke="white" stroke-width="1"></circle>
        <circle cx="170" cy="250" r="80" stroke="white" stroke-width="1"></circle>
        <circle cx="170" cy="250" r="40" stroke="white" stroke-width="1"></circle>
        <circle cx="170" cy="130" r="8" fill="white"></circle>
        <circle cx="170" cy="370" r="8" fill="white"></circle>
        <circle cx="50" cy="250" r="8" fill="white"></circle>
        <circle cx="290" cy="250" r="8" fill="white"></circle>
        <circle cx="85" cy="165" r="5" fill="white"></circle>
        <circle cx="255" cy="165" r="5" fill="white"></circle>
        <circle cx="85" cy="335" r="5" fill="white"></circle>
        <circle cx="255" cy="335" r="5" fill="white"></circle>
        <line x1="170" y1="130" x2="50" y2="250" stroke="white" stroke-width="0.5"></line>
        <line x1="170" y1="130" x2="290" y2="250" stroke="white" stroke-width="0.5"></line>
        <line x1="170" y1="370" x2="50" y2="250" stroke="white" stroke-width="0.5"></line>
        <line x1="170" y1="370" x2="290" y2="250" stroke="white" stroke-width="0.5"></line>
        <line x1="170" y1="130" x2="85" y2="165" stroke="white" stroke-width="0.5"></line>
        <line x1="170" y1="130" x2="255" y2="165" stroke="white" stroke-width="0.5"></line>
      </svg>
      <div class="left-panel-inner">
        <a href="index.php" class="lp-logo">
          <div class="lp-logo-icon"><i class="fas fa-network-wired"></i></div>
          <div class="lp-logo-text"><div class="name">BN-Infrastructure</div><div class="sub">B2B Network Supplier</div></div>
        </a>
        <h1 class="lp-tagline">Tanzania's <span>#1</span> B2B<br>Network Infrastructure<br>Supplier</h1>
        <p class="lp-desc">Access exclusive bulk pricing, manage orders, and request quotes — all from one dashboard.</p>
        <div class="lp-trust">
          <div class="trust-point">
            <div class="tp-icon"><i class="fas fa-certificate"></i></div>
            <div class="tp-text"><h5>Verified Brands</h5><p>Genuine products from Cisco, MikroTik, Ubiquiti, HP Aruba &amp; 45+ verified brands.</p></div>
          </div>
          <div class="trust-point">
            <div class="tp-icon"><i class="fas fa-tags"></i></div>
            <div class="tp-text"><h5>Bulk Pricing</h5><p>Volume discounts for ISPs, system integrators, and enterprise procurement teams.</p></div>
          </div>
          <div class="trust-point">
            <div class="tp-icon"><i class="fas fa-handshake"></i></div>
            <div class="tp-text"><h5>Credit Terms Available</h5><p>Flexible net-30 and net-60 credit terms for pre-qualified business accounts.</p></div>
          </div>
        </div>
        <div class="lp-contact">
          <div class="lp-contact-info">
            <div class="lp-contact-row"><i class="fas fa-map-marker-alt"></i><span>Mikocheni, Dar es Salaam, Tanzania</span></div>
            <div class="lp-contact-row"><i class="fas fa-envelope"></i><span>sales@bn-infrastructure.com</span></div>
          </div>
          <a href="https://wa.me/255763364721" class="lp-whatsapp"><i class="fab fa-whatsapp" style="font-size:16px;"></i> WhatsApp Us</a>
        </div>
      </div>
    </div>

    <div class="right-panel">
      <div class="auth-card">
        <div class="tab-switcher" role="tablist">
          <div class="tab-slider" id="tabSlider"></div>
          <button class="tab-btn active" id="tabSignin" role="tab" aria-selected="true" onclick="switchTab('signin')">
            <i class="fas fa-sign-in-alt" style="margin-right:6px;font-size:12px;"></i> Sign In
          </button>
          <button class="tab-btn" id="tabRegister" role="tab" aria-selected="false" onclick="switchTab('register')">
            <i class="fas fa-user-plus" style="margin-right:6px;font-size:12px;"></i> Create Account
          </button>
        </div>

        <!-- Sign In -->
        <div class="form-panel active" id="panelSignin">
          <div class="form-header">
            <h2>Welcome back</h2>
            <p>Sign in to your BN-Infrastructure business account</p>
          </div>
          <?php if ($signinError): ?>
          <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $signinError; ?></div>
          <?php endif; ?>
          <?php if ($registerSuccess): ?>
          <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $registerSuccess; ?></div>
          <?php endif; ?>
          <form method="POST" action="">
            <input type="hidden" name="action" value="signin">
            <?php if ($redirectAfterLogin): ?>
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectAfterLogin); ?>">
            <?php endif; ?>
            <div class="form-group">
              <label>Email Address <span class="required">*</span></label>
              <div class="input-wrap">
                <input type="email" name="email" placeholder="e.g. john@company.co.tz" required autocomplete="email">
                <i class="fas fa-envelope input-icon"></i>
              </div>
            </div>
            <div class="form-group">
              <label>Password <span class="required">*</span></label>
              <div class="input-wrap">
                <input type="password" name="password" id="siPassword" class="has-toggle" placeholder="Enter your password" required autocomplete="current-password">
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="pw-toggle" onclick="togglePw('siPassword', this)"><i class="far fa-eye"></i></button>
              </div>
            </div>
            <div class="form-row-inline">
              <label class="checkbox-label"><input type="checkbox"> <span>Remember me for 30 days</span></label>
              <a href="#" class="forgot-link">Forgot password?</a>
            </div>
            <button type="submit" class="btn-auth"><i class="fas fa-sign-in-alt"></i> Sign In to Dashboard</button>
          </form>
          <div class="form-help">Don't have an account? <a href="#" onclick="switchTab('register'); return false;">Create a business account</a></div>
          <div class="form-help" style="margin-top:10px;">Need help? <a href="mailto:sales@bn-infrastructure.com">Contact sales@bn-infrastructure.com</a></div>
        </div>

        <!-- Register -->
        <div class="form-panel" id="panelRegister">
          <div class="form-header">
            <h2>Create Business Account</h2>
            <p>Join 2,000+ businesses on the BN-Infrastructure platform</p>
          </div>
          <?php if ($registerError): ?>
          <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $registerError; ?></div>
          <?php endif; ?>
          <form method="POST" action="">
            <input type="hidden" name="action" value="register">
            <div class="form-section-label">Company Details</div>
            <div class="form-group">
              <label>Company / Organisation Name <span class="required">*</span></label>
              <div class="input-wrap">
                <input type="text" name="company" placeholder="e.g. Acme Communications Ltd" required>
                <i class="fas fa-building input-icon"></i>
              </div>
            </div>
            <div class="form-group">
              <label>Business Type <span class="required">*</span></label>
              <div class="input-wrap select-wrap">
                <select name="biz_type" required>
                  <option value="">Select your business type…</option>
                  <option value="ISP / Telecommunications">ISP / Telecommunications</option>
                  <option value="Enterprise / Corporate">Enterprise / Corporate</option>
                  <option value="Government / Public Sector">Government / Public Sector</option>
                  <option value="Reseller / System Integrator">Reseller / System Integrator</option>
                  <option value="SME / Small Business">SME / Small Business</option>
                  <option value="Other">Other</option>
                </select>
                <i class="fas fa-briefcase input-icon"></i>
              </div>
            </div>
            <div class="form-section-label">Contact Information</div>
            <div class="form-row">
              <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <div class="input-wrap">
                  <input type="text" name="full_name" placeholder="First &amp; last name" required>
                  <i class="fas fa-user input-icon"></i>
                </div>
              </div>
              <div class="form-group">
                <label>Work Email <span class="required">*</span></label>
                <div class="input-wrap">
                  <input type="email" name="email" placeholder="you@company.co.tz" required>
                  <i class="fas fa-envelope input-icon"></i>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Phone Number</label>
              <div class="phone-wrap">
                <div class="phone-prefix"><i class="fas fa-flag" style="color:var(--text-muted);font-size:11px;"></i> +255</div>
                <div class="input-wrap" style="flex:1;"><input type="tel" name="phone" placeholder="7XX XXX XXX" style="border-radius:0 9px 9px 0!important;padding-left:14px!important;"></div>
              </div>
            </div>
            <div class="form-section-label">Set Password</div>
            <div class="form-row">
              <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <div class="input-wrap">
                  <input type="password" name="password" id="regPassword" class="has-toggle" placeholder="Min. 8 characters" required minlength="8" oninput="checkStrength(this.value)">
                  <i class="fas fa-lock input-icon"></i>
                  <button type="button" class="pw-toggle" onclick="togglePw('regPassword', this)"><i class="far fa-eye"></i></button>
                </div>
                <div class="pw-strength" id="pwStrength" style="display:none;">
                  <div class="pw-strength-bars"><div class="pw-bar" id="bar1"></div><div class="pw-bar" id="bar2"></div><div class="pw-bar" id="bar3"></div><div class="pw-bar" id="bar4"></div></div>
                  <span class="pw-strength-label" id="pwLabel">Weak</span>
                </div>
              </div>
              <div class="form-group">
                <label>Confirm Password <span class="required">*</span></label>
                <div class="input-wrap">
                  <input type="password" name="confirm_password" id="regConfirm" class="has-toggle" placeholder="Re-enter password" required>
                  <i class="fas fa-lock input-icon"></i>
                  <button type="button" class="pw-toggle" onclick="togglePw('regConfirm', this)"><i class="far fa-eye"></i></button>
                </div>
              </div>
            </div>
            <div class="terms-group">
              <label class="checkbox-label">
                <input type="checkbox" name="terms" required>
                <span>I agree to BN-Infrastructure's <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>. I confirm this is a business account registration.</span>
              </label>
            </div>
            <button type="submit" class="btn-auth"><i class="fas fa-user-plus"></i> Create Business Account</button>
          </form>
          <div class="form-help">Already have an account? <a href="#" onclick="switchTab('signin'); return false;">Sign in here</a></div>
          <div class="form-help" style="margin-top:10px;">Need help? <a href="mailto:sales@bn-infrastructure.com">Contact sales@bn-infrastructure.com</a></div>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <div class="footer-inner">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo"><div class="icon"><i class="fas fa-network-wired"></i></div><span class="name">BN-Infrastructure</span></div>
          <p>Tanzania's leading B2B network infrastructure supplier. Empowering businesses with enterprise-grade connectivity solutions since 2012.</p>
          <div class="footer-socials"><a href="https://linkedin.com" target="_blank" class="footer-social-btn" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a><a href="https://twitter.com" target="_blank" class="footer-social-btn" aria-label="Twitter"><i class="fab fa-twitter"></i></a><a href="https://facebook.com" target="_blank" class="footer-social-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><a href="https://wa.me/255763364721" target="_blank" class="footer-social-btn" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a></div>
        </div>
        <div class="footer-col"><h4>Quick Links</h4><ul><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> All Products</a></li><li><a href="catalog.php?sort=newest"><i class="fas fa-chevron-right"></i> New Arrivals</a></li><li><a href="catalog.php?sort=best"><i class="fas fa-chevron-right"></i> Best Sellers</a></li><li><a href="catalog.php?in_stock=1"><i class="fas fa-chevron-right"></i> Special Offers</a></li><li><a href="cart.php"><i class="fas fa-chevron-right"></i> Request a Quote</a></li><li><a href="cart.php"><i class="fas fa-chevron-right"></i> Bulk Orders</a></li></ul></div>
        <div class="footer-col"><h4>Company</h4><ul><li><a href="about.php"><i class="fas fa-chevron-right"></i> About BN-Infrastructure</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Our Brands</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Solutions</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Blog &amp; Resources</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Careers</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li></ul></div>
        <div class="footer-col"><h4>Contact Us</h4><div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>Plot 45, Mikocheni Light Industrial Area, Dar es Salaam, Tanzania</span></div><div class="contact-item"><i class="fas fa-phone-alt"></i><span>+255 763 364 721 <br>+255 763 364 721</span></div><div class="contact-item"><i class="fas fa-envelope"></i><span>sales@bn-infrastructure.com</span></div><div style="margin-top: 20px;"><h4>Newsletter</h4><div class="newsletter-label">Get product updates and exclusive deals</div><div class="newsletter-form"><input type="email" placeholder="Your email address"><button>Subscribe</button></div></div></div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> BN-Infrastructure. All rights reserved. | Powered by BN-Infrastructure</p>
        <div class="footer-bottom-links">
          <a href="privacy.php">Privacy Policy</a>
          <a href="terms.php">Terms of Service</a>
          <a href="shipping.php">Shipping Policy</a>
          <a href="returns.php">Returns</a>
        </div>
      </div>
    </div>
  </footer>

  <script>
    function switchTab(tab) {
      var slider = document.getElementById('tabSlider');
      var signinBtn = document.getElementById('tabSignin');
      var registerBtn = document.getElementById('tabRegister');
      var signinPanel = document.getElementById('panelSignin');
      var registerPanel = document.getElementById('panelRegister');
      if (tab === 'signin') {
        slider.classList.remove('right');
        signinBtn.classList.add('active');
        registerBtn.classList.remove('active');
        signinPanel.classList.add('active');
        registerPanel.classList.remove('active');
        signinBtn.setAttribute('aria-selected', 'true');
        registerBtn.setAttribute('aria-selected', 'false');
      } else {
        slider.classList.add('right');
        registerBtn.classList.add('active');
        signinBtn.classList.remove('active');
        registerPanel.classList.add('active');
        signinPanel.classList.remove('active');
        registerBtn.setAttribute('aria-selected', 'true');
        signinBtn.setAttribute('aria-selected', 'false');
        document.querySelector('.right-panel').scrollTo({ top: 0, behavior: 'smooth' });
      }
    }
    function togglePw(inputId, btn) {
      var input = document.getElementById(inputId);
      var icon = btn.querySelector('i');
      if (input.type === 'password') { input.type = 'text'; icon.className = 'far fa-eye-slash'; }
      else { input.type = 'password'; icon.className = 'far fa-eye'; }
    }
    function checkStrength(pw) {
      var strengthEl = document.getElementById('pwStrength');
      var bars = [document.getElementById('bar1'), document.getElementById('bar2'), document.getElementById('bar3'), document.getElementById('bar4')];
      var label = document.getElementById('pwLabel');
      if (!pw) { strengthEl.style.display = 'none'; return; }
      strengthEl.style.display = 'flex';
      var score = 0;
      if (pw.length >= 8) score++;
      if (/[A-Z]/.test(pw)) score++;
      if (/[0-9]/.test(pw)) score++;
      if (/[^A-Za-z0-9]/.test(pw)) score++;
      bars.forEach(function(b, i) {
        b.className = 'pw-bar';
        if (i < score) {
          if (score <= 1) b.classList.add('filled-weak');
          else if (score <= 2) b.classList.add('filled-medium');
          else b.classList.add('filled-strong');
        }
      });
      var labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
      var colors = ['', '#e53e3e', '#d97706', '#059669', '#059669'];
      label.textContent = labels[score] || 'Weak';
      label.style.color = colors[score] || '#e53e3e';
    }
    document.querySelectorAll('a[href="#"]').forEach(function(a) { a.addEventListener('click', function(e) { e.preventDefault(); }); });
    function toggleMenu(){document.getElementById('mobileNav').classList.toggle('open');document.getElementById('navOverlay').classList.toggle('open');}
    document.querySelectorAll('.input-wrap input, .input-wrap select').forEach(function(input) {
      input.addEventListener('focus', function() {
        var icon = this.parentElement.querySelector('.input-icon');
        if (icon) icon.style.color = 'var(--orange)';
      });
      input.addEventListener('blur', function() {
        var icon = this.parentElement.querySelector('.input-icon');
        if (icon) icon.style.color = '';
      });
    });
    <?php echo userMenuJs(); ?>
    <?php echo scrollRevealJs(); ?>
  </script>
</body></html>
