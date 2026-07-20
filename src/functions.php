<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
    }
}

function currentUserName() {
    return $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? $_SESSION['user_email'] ?? 'User';
}

function currentUserEmail() {
    return $_SESSION['user_email'] ?? '';
}

function productImage($image) {
    if (!$image) return null;
    $localDir = realpath(__DIR__ . '/../Homepage');
    if (!$localDir) return null;
    $path = $localDir . '/' . $image;
    if (file_exists($path)) {
        $urlBase = str_replace($_SERVER['DOCUMENT_ROOT'], '', $localDir);
        return $urlBase . '/' . $image;
    }
    return null;
}

function placeholderSvg($name, $brand = '') {
    $colors = [
        ['#0A2540','#1a3a5c','#F05A22'],
        ['#1a365d','#2d4a7a','#e8a838'],
        ['#2d3748','#4a5568','#48bb78'],
        ['#1a202c','#2d3748','#4299e1'],
        ['#0d3b56','#1a5a7a','#ed8936'],
        ['#2b1a3d','#4a2d5c','#9f7aea'],
        ['#3d1a1a','#5c2d2d','#fc8181'],
        ['#1a3d2b','#2d5c4a','#68d391'],
        ['#3d2b1a','#5c4a2d','#f6ad55'],
        ['#1a1a3d','#2d2d5c','#63b3ed'],
    ];
    $hash = crc32($name ?: 'default');
    $palette = $colors[abs($hash) % count($colors)];
    $bg1 = $palette[0];
    $bg2 = $palette[1];
    $accent = $palette[2];
    $label = htmlspecialchars($name ?: 'Product', ENT_QUOTES);
    $short = strlen($label) > 28 ? substr($label, 0, 25) . '...' : $label;
    $brand = htmlspecialchars($brand ?: 'BN-Infrastructure', ENT_QUOTES);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">
        <defs><linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:' . $bg1 . '"/>
            <stop offset="100%" style="stop-color:' . $bg2 . '"/>
        </linearGradient></defs>
        <rect width="400" height="300" fill="url(#bg)"/>
        <circle cx="200" cy="120" r="60" fill="rgba(255,255,255,0.06)"/>
        <circle cx="200" cy="120" r="40" fill="rgba(255,255,255,0.04)"/>
        <g transform="translate(200,120)" fill="none" stroke="' . $accent . '" stroke-width="2" opacity="0.4">
            <rect x="-32" y="-22" width="64" height="44" rx="4"/>
            <circle cx="-18" cy="-4" r="3" fill="' . $accent . '"/>
            <circle cx="0" cy="-4" r="3" fill="' . $accent . '"/>
            <circle cx="18" cy="-4" r="3" fill="' . $accent . '"/>
            <rect x="-18" y="10" width="8" height="6" rx="1"/>
            <rect x="-5" y="10" width="8" height="6" rx="1"/>
            <rect x="8" y="10" width="8" height="6" rx="1"/>
            <line x1="-18" y1="-16" x2="-24" y2="-28" stroke-width="1.5"/>
            <line x1="0" y1="-16" x2="0" y2="-28" stroke-width="1.5"/>
            <line x1="18" y1="-16" x2="24" y2="-28" stroke-width="1.5"/>
            <circle cx="-24" cy="-28" r="3" fill="' . $accent . '"/>
            <circle cx="0" cy="-28" r="3" fill="' . $accent . '"/>
            <circle cx="24" cy="-28" r="3" fill="' . $accent . '"/>
        </g>
        <text x="200" y="220" text-anchor="middle" fill="rgba(255,255,255,0.9)" font-family="Inter,Arial,sans-serif" font-size="14" font-weight="600">' . $short . '</text>
        <text x="200" y="242" text-anchor="middle" fill="rgba(255,255,255,0.35)" font-family="Inter,Arial,sans-serif" font-size="11">' . $brand . '</text>
    </svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function imageOrPlaceholder($image, $alt = '', $brand = '') {
    $img = productImage($image);
    if ($img) {
        return '<img src="' . $img . '" alt="' . htmlspecialchars($alt) . '" loading="lazy">';
    }
    $src = placeholderSvg($alt, $brand);
    return '<img src="' . $src . '" alt="' . htmlspecialchars($alt) . '" loading="lazy" style="width:100%;height:200px;object-fit:cover;">';
}

function userNavHtml() {
    if (isLoggedIn()) {
        $name = htmlspecialchars(currentUserName(), ENT_QUOTES);
        return '<div class="user-menu">
            <span class="user-name" onclick="event.stopPropagation();document.getElementById(\'userDropdown\')?.classList.toggle(\'show\')"><i class="far fa-user-circle"></i> ' . $name . ' <i class="fas fa-chevron-down" style="font-size:10px;margin-left:4px;"></i></span>
            <div class="user-dropdown" id="userDropdown">
                <a href="dashboard.php"><i class="fas fa-columns"></i> Dashboard</a>
                <a href="track.php"><i class="fas fa-truck"></i> My Orders</a>
                <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </div>';
    }
    return '<a href="login.php" class="btn-signin"><i class="far fa-user"></i> Sign In</a>';
}

function userMenuCss() {
    return '.user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}.img-placeholder{width:100%;height:200px;background:linear-gradient(135deg,#f8fafc,#e2e8f0);display:flex;align-items:center;justify-content:center;font-size:40px;color:#94a3b8}';
}

function userMenuJs() {
    return 'document.addEventListener(\'click\',function(e){var dd=document.getElementById(\'userDropdown\');if(dd&&!e.target.closest(\'.user-menu\'))dd.classList.remove(\'show\')});';
}

function mobileAccountHtml() {
    if (isLoggedIn()) {
        $name = htmlspecialchars(currentUserName(), ENT_QUOTES);
        return '<div class="mobile-user"><i class="far fa-user-circle"></i> ' . $name . '</div><a href="dashboard.php" onclick="toggleMenu()" class="mobile-account"><i class="fas fa-columns"></i> Dashboard</a><a href="?logout=1" onclick="toggleMenu()" class="mobile-logout"><i class="fas fa-sign-out-alt"></i> Sign Out</a>';
    }
    return '<a href="login.php" onclick="toggleMenu()" class="mobile-account"><i class="far fa-user"></i> Account</a>';
}

function scrollRevealJs() {
    return 'document.addEventListener(\'DOMContentLoaded\',function(){var items=document.querySelectorAll(\'.reveal, .card-reveal, .product-reveal\');if(items.length){var obs=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.style.opacity=\'1\';e.target.style.transform=\'translateY(0)\';obs.unobserve(e.target)}})},{threshold:0.1});items.forEach(function(el){el.style.opacity=\'0\';el.style.transform=\'translateY(24px)\';el.style.transition=\'opacity .6s ease, transform .6s ease\';obs.observe(el)})}});';
}

function revealCss() {
    return '.reveal,.card-reveal,.product-reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}';
}

function formatTsh($amount) {
    return 'TSh ' . number_format((float)$amount, 0, '.', ',');
}

function sendEmailNotification($to, $subject, $body) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: BN-Infrastructure <noreply@bn-infrastructure.com>\r\n";
    return @mail($to, $subject, $body, $headers);
}

function sendOrderConfirmation($order) {
    $items = fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
    $itemsHtml = '';
    foreach ($items as $item) {
        $itemsHtml .= '<tr><td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;">' . htmlspecialchars($item['product_name']) . '</td><td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;text-align:center;">' . $item['quantity'] . '</td><td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;text-align:right;">TSh ' . number_format($item['total_price'], 0, '.', ',') . '</td></tr>';
    }
    $body = '<div style="font-family:Inter,Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;"><div style="background:#0A2540;padding:20px;border-radius:12px 12px 0 0;text-align:center;"><h1 style="color:#fff;font-size:20px;margin:0;">BN-Infrastructure</h1></div><div style="background:#fff;padding:32px;border:1px solid #e2e8f0;"><h2 style="color:#0A2540;font-size:22px;margin-bottom:8px;">Order Confirmed!</h2><p style="color:#5a6a7e;font-size:14px;margin-bottom:24px;">Thank you for your order. Here are the details:</p><div style="background:#F4F6F9;padding:16px;border-radius:8px;margin-bottom:20px;"><p style="font-size:13px;color:#5a6a7e;margin:4px 0;"><strong>Order Number:</strong> ' . htmlspecialchars($order['order_number']) . '</p><p style="font-size:13px;color:#5a6a7e;margin:4px 0;"><strong>Date:</strong> ' . date('M d, Y h:i A', strtotime($order['created_at'])) . '</p><p style="font-size:13px;color:#5a6a7e;margin:4px 0;"><strong>Payment Method:</strong> ' . str_replace('_', ' ', ucwords($order['payment_method'], '_')) . '</p></div><table style="width:100%;border-collapse:collapse;margin-bottom:20px;"><thead><tr style="background:#F4F6F9;"><th style="padding:8px 12px;text-align:left;font-size:12px;font-weight:700;color:#5a6a7e;">Product</th><th style="padding:8px 12px;text-align:center;font-size:12px;font-weight:700;color:#5a6a7e;">Qty</th><th style="padding:8px 12px;text-align:right;font-size:12px;font-weight:700;color:#5a6a7e;">Total</th></tr></thead><tbody>' . $itemsHtml . '</tbody></table><div style="border-top:2px solid #e2e8f0;padding-top:16px;text-align:right;"><p style="font-size:13px;color:#5a6a7e;margin:4px 0;">Subtotal: TSh ' . number_format($order['subtotal'], 0, '.', ',') . '</p><p style="font-size:13px;color:#5a6a7e;margin:4px 0;">VAT (18%): TSh ' . number_format($order['vat'], 0, '.', ',') . '</p><p style="font-size:13px;color:#5a6a7e;margin:4px 0;">Shipping: ' . ($order['shipping'] > 0 ? 'TSh ' . number_format($order['shipping'], 0, '.', ',') : 'FREE') . '</p><p style="font-size:18px;font-weight:800;color:#0A2540;margin-top:8px;">Total: TSh ' . number_format($order['total'], 0, '.', ',') . '</p></div></div><div style="text-align:center;padding:16px;font-size:12px;color:#8fa0b3;">Track your order at bn-infrastructure.com/track.php</div></div>';
    sendEmailNotification($order['email'], 'Order Confirmation — ' . $order['order_number'], $body);
}

function generateInvoiceHtml($order) {
    $items = fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
    $itemsHtml = '';
    foreach ($items as $item) {
        $itemsHtml .= '<tr><td style="padding:10px;border-bottom:1px solid #e2e8f0;">' . htmlspecialchars($item['product_name']) . '<br><small style="color:#888;">' . htmlspecialchars($item['product_sku']) . '</small></td><td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:center;">' . $item['quantity'] . '</td><td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;">TSh ' . number_format($item['unit_price'], 0, '.', ',') . '</td><td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:600;">TSh ' . number_format($item['total_price'], 0, '.', ',') . '</td></tr>';
    }
    return '<!DOCTYPE html><html><head><style>@media print{body{margin:0}}</style></head><body style="font-family:Inter,Arial,sans-serif;max-width:800px;margin:0 auto;padding:40px;color:#0A2540;"><div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:40px;"><div><h1 style="font-size:28px;font-weight:900;margin:0;">INVOICE</h1><p style="color:#5a6a7e;margin-top:4px;">' . htmlspecialchars($order['order_number']) . '</p></div><div style="text-align:right;"><h2 style="font-size:18px;font-weight:800;margin:0;">BN-Infrastructure</h2><p style="font-size:12px;color:#5a6a7e;margin:2px 0;">Plot 45, Mikocheni</p><p style="font-size:12px;color:#5a6a7e;margin:2px 0;">Dar es Salaam, Tanzania</p><p style="font-size:12px;color:#5a6a7e;margin:2px 0;">+255 763 364 721</p></div></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;padding:20px;background:#F4F6F9;border-radius:8px;"><div><p style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:4px;">Bill To</p><p style="font-size:14px;font-weight:600;">' . htmlspecialchars($order['full_name']) . '</p><p style="font-size:13px;color:#5a6a7e;">' . htmlspecialchars($order['company_name'] ?: '') . '</p><p style="font-size:13px;color:#5a6a7e;">' . htmlspecialchars($order['email']) . '</p><p style="font-size:13px;color:#5a6a7e;">' . htmlspecialchars($order['address'] ?: '') . ', ' . htmlspecialchars($order['city'] ?: '') . '</p></div><div style="text-align:right;"><p style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:4px;">Invoice Details</p><p style="font-size:13px;color:#5a6a7e;">Date: ' . date('M d, Y', strtotime($order['created_at'])) . '</p><p style="font-size:13px;color:#5a6a7e;">Payment: ' . str_replace('_', ' ', ucwords($order['payment_method'], '_')) . '</p><p style="font-size:13px;color:#5a6a7e;">Status: ' . ucfirst($order['payment_status']) . '</p></div></div><table style="width:100%;border-collapse:collapse;margin-bottom:24px;"><thead><tr style="background:#0A2540;"><th style="padding:10px;color:#fff;text-align:left;font-size:12px;">Product</th><th style="padding:10px;color:#fff;text-align:center;font-size:12px;">Qty</th><th style="padding:10px;color:#fff;text-align:right;font-size:12px;">Unit Price</th><th style="padding:10px;color:#fff;text-align:right;font-size:12px;">Total</th></tr></thead><tbody>' . $itemsHtml . '</tbody></table><div style="text-align:right;border-top:2px solid #0A2540;padding-top:16px;"><p style="font-size:13px;color:#5a6a7e;margin:6px 0;">Subtotal: TSh ' . number_format($order['subtotal'], 0, '.', ',') . '</p><p style="font-size:13px;color:#5a6a7e;margin:6px 0;">VAT (18%): TSh ' . number_format($order['vat'], 0, '.', ',') . '</p><p style="font-size:13px;color:#5a6a7e;margin:6px 0;">Shipping: ' . ($order['shipping'] > 0 ? 'TSh ' . number_format($order['shipping'], 0, '.', ',') : 'FREE') . '</p><p style="font-size:20px;font-weight:900;color:#0A2540;margin-top:8px;">TOTAL: TSh ' . number_format($order['total'], 0, '.', ',') . '</p></div><div style="margin-top:40px;padding-top:20px;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#8fa0b3;"><p>Thank you for your business!</p><p>BN-Infrastructure | sales@bn-infrastructure.com | +255 763 364 721</p></div></body></html>';
}

function getStockBadge($status) {
    $map = [
        'in_stock' => ['class' => 'badge-stock', 'label' => 'In Stock'],
        'low_stock' => ['class' => 'badge-low', 'label' => 'Low Stock'],
        'out_of_stock' => ['class' => 'badge-out', 'label' => 'Out of Stock'],
    ];
    $s = $map[$status] ?? $map['out_of_stock'];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

function getStatusBadge($status) {
    $map = [
        'in_stock' => ['class' => 'badge-stock', 'label' => 'In Stock'],
        'low_stock' => ['class' => 'badge-low', 'label' => 'Low Stock'],
        'out_of_stock' => ['class' => 'badge-out', 'label' => 'Out of Stock'],
        'pending' => ['class' => 'badge-low', 'label' => 'Pending'],
        'confirmed' => ['class' => 'badge-stock', 'label' => 'Confirmed'],
        'processing' => ['class' => 'badge-low', 'label' => 'Processing'],
        'shipped' => ['class' => 'badge-stock', 'label' => 'Shipped'],
        'delivered' => ['class' => 'badge-stock', 'label' => 'Delivered'],
        'cancelled' => ['class' => 'badge-out', 'label' => 'Cancelled'],
    ];
    $s = $map[$status] ?? ['class' => 'badge-low', 'label' => $status];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function getReviewStats($productId) {
    $row = fetchOne("SELECT COUNT(*) as total, AVG(rating) as avg_rating FROM reviews WHERE product_id = ? AND status = 'approved'", [$productId]);
    return [
        'total' => (int)($row['total'] ?? 0),
        'avg' => round((float)($row['avg_rating'] ?? 0), 1),
    ];
}

function getReviewDistribution($productId) {
    $rows = fetchAll("SELECT rating, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND status = 'approved' GROUP BY rating ORDER BY rating DESC", [$productId]);
    $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($rows as $r) {
        $dist[(int)$r['rating']] = (int)$r['cnt'];
    }
    return $dist;
}

function getProductReviews($productId, $limit = 50) {
    return fetchAll(
        "SELECT r.*, u.full_name, u.company FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC LIMIT ?",
        [$productId, $limit]
    );
}

function starHtml($rating, $size = 13) {
    $html = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($i - $rating < 1 && $i - $rating > 0) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star"></i>';
        }
    }
    $html .= '</span>';
    return $html;
}

function uploadImage($file, $targetDir) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return null;
    $filename = uniqid('prod_') . '.' . $ext;
    $targetPath = rtrim($targetDir, '/') . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    return null;
}
