<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/cart.php';
require_once __DIR__ . '/src/functions.php';

$sku = $_GET['sku'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$itemCount = cartCount();
$cartProductIds = array_keys(cartGetItems());

if ($id) {
    $product = fetchOne("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?", [$id]);
} elseif ($sku) {
    $product = fetchOne("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.sku = ?", [$sku]);
} else {
    $product = fetchOne("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 1");
}

if (!$product) {
    header('Location: /catalog.php');
    exit;
}

$related = fetchAll(
    "SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 6",
    [$product['category_id'], $product['id']]
);
if (empty($related)) {
    $related = fetchAll("SELECT * FROM products WHERE id != ? ORDER BY RAND() LIMIT 6", [$product['id']]);
}

$tags = $product['tags'] ? explode(',', $product['tags']) : [];
$features = $product['features'] ? explode("\n", $product['features']) : [];
$specLines = $product['specs'] ? explode("\n", $product['specs']) : [];

$reviewSuccess = '';
$reviewError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review') {
    if (!isLoggedIn()) {
        $reviewError = 'Please sign in to leave a review.';
    } else {
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $title = trim($_POST['review_title'] ?? '');
        $comment = trim($_POST['review_comment'] ?? '');
        $reviewerName = currentUserName();
        $userId = (int)($_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0);

        if (empty($comment)) {
            $reviewError = 'Please write a review comment.';
        } else {
            execute(
                "INSERT INTO reviews (product_id, user_id, rating, title, comment, reviewer_name, reviewer_location) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$product['id'], $userId ?: null, $rating, $title ?: null, $comment, $reviewerName, 'Tanzania']
            );
            $reviewSuccess = 'Your review has been submitted. Thank you for your feedback!';
        }
    }
}

$reviewStats = getReviewStats($product['id']);
$reviewDist = getReviewDistribution($product['id']);
$reviews = getProductReviews($product['id']);

$extraImages = fetchAll("SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order ASC", [$product['id']]);
$allImages = [];
if ($product['image']) $allImages[] = $product['image'];
foreach ($extraImages as $ei) { $allImages[] = $ei['image']; }
if (empty($allImages)) $allImages[] = '';
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($product['name']); ?> — BN-Infrastructure</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --navy: #0A2540; --navy-light: #133057; --navy-dark: #071a2e;
      --orange: #F05A22; --orange-dark: #d44d1a; --orange-light: #ff6b35;
      --bg: #F4F6F9; --card: #FFFFFF; --text-primary: #0A2540;
      --text-secondary: #5a6a7e; --text-muted: #8fa0b3; --border: #e2e8f0;
      --green: #059669;
      --shadow-sm: 0 1px 3px rgba(10,37,64,0.08), 0 1px 2px rgba(10,37,64,0.04);
      --shadow-md: 0 4px 12px rgba(10,37,64,0.1), 0 2px 6px rgba(10,37,64,0.06);
      --shadow-lg: 0 10px 30px rgba(10,37,64,0.12), 0 4px 12px rgba(10,37,64,0.08);
    }
    html { scroll-behavior: smooth; }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-primary); font-size: 15px; line-height: 1.6; }
    .announcement-bar { background: var(--orange); color: #fff; text-align: center; padding: 9px 24px; font-size: 13px; font-weight: 500; letter-spacing: 0.01em; }
    .announcement-bar i { margin-right: 6px; opacity: 0.9; }
    .announcement-bar span { margin: 0 18px; opacity: 0.7; }
    .navbar { background: var(--navy); padding: 0 48px; display: flex; align-items: center; gap: 0; height: 70px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 12px rgba(0,0,0,0.2); }
    .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; margin-right: 40px; flex-shrink: 0; }
    .nav-logo-icon { width: 38px; height: 38px; background: var(--orange); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; }
    .nav-logo-text { display: flex; flex-direction: column; line-height: 1.1; }
    .nav-logo-text .brand { font-size: 18px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
    .nav-logo-text .tagline { font-size: 10px; font-weight: 400; color: rgba(255,255,255,0.5); letter-spacing: 0.08em; text-transform: uppercase; }
    .nav-links { display: flex; align-items: center; gap: 4px; flex: 1; }
    .nav-links a { text-decoration: none; color: rgba(255,255,255,0.75); font-size: 14px; font-weight: 500; padding: 8px 14px; border-radius: 6px; transition: color 0.2s, background 0.2s; white-space: nowrap; }
    .nav-links a:hover { color: #fff; background: rgba(255,255,255,0.08); }
    .hamburger { display: none; background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; padding: 6px; margin-left: auto; }
    .nav-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; }
    .nav-overlay.open { display: block; }
    .mobile-nav { position: fixed; top: 0; right: -280px; width: 280px; height: 100vh; background: var(--navy); z-index: 1001; transition: right 0.3s ease; padding: 80px 24px 24px; overflow-y: auto; }
    .mobile-nav.open { right: 0; }
    .mobile-nav a { display: block; color: rgba(255,255,255,0.8); text-decoration: none; padding: 12px 0; font-size: 15px; font-weight: 500; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .mobile-nav a:hover { color: var(--orange); }
    .mobile-nav .close-btn { position: absolute; top: 16px; right: 16px; background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; }
    .mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
    .nav-actions { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
    .search-bar { display: flex; align-items: center; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 7px 12px; gap: 8px; transition: background 0.2s, border-color 0.2s; width: 220px; }
    .search-bar:focus-within { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); }
    .search-bar i { color: rgba(255,255,255,0.5); font-size: 13px; }
    .search-bar input { background: none; border: none; outline: none; color: #fff; font-family: 'Inter', sans-serif; font-size: 13px; width: 100%; }
    .search-bar input::placeholder { color: rgba(255,255,255,0.45); }
    .btn-signin { background: transparent; border: 1.5px solid rgba(255,255,255,0.3); color: rgba(255,255,255,0.85); padding: 7px 16px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s; white-space: nowrap; text-decoration: none; }
    .btn-signin:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.5); color: #fff; }
    .cart-btn { background: var(--orange); border: none; color: #fff; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; transition: background 0.2s, transform 0.2s; font-size: 15px; }
    .cart-btn:hover { background: var(--orange-dark); transform: translateY(-1px); }
    .cart-badge { position: absolute; top: -5px; right: -5px; background: #fff; color: var(--orange); font-size: 10px; font-weight: 700; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1.5px solid var(--orange); transition: transform 0.2s; }
    .breadcrumb-bar { background: var(--card); border-bottom: 1px solid var(--border); padding: 0 48px; }
    .breadcrumb-inner { max-width: 1632px; margin: 0 auto; display: flex; align-items: center; height: 48px; gap: 0; }
    .breadcrumb { display: flex; align-items: center; gap: 6px; list-style: none; }
    .breadcrumb li { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted); }
    .breadcrumb li a { color: var(--text-secondary); text-decoration: none; transition: color 0.2s; }
    .breadcrumb li a:hover { color: var(--orange); }
    .breadcrumb li.active { color: var(--navy); font-weight: 600; }
    .breadcrumb li i { font-size: 9px; color: var(--text-muted); }
    .product-page { padding: 36px 48px 60px; max-width: 1728px; margin: 0 auto; }
    .product-main { display: grid; grid-template-columns: 55% 45%; gap: 40px; align-items: start; margin-bottom: 48px; }
    .product-gallery { position: sticky; top: 90px; }
    .gallery-main { background: var(--card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; margin-bottom: 14px; position: relative; display: flex; align-items: center; justify-content: center; min-height: 460px; box-shadow: var(--shadow-md); }
    .gallery-main img { width: 100%; height: 460px; object-fit: contain; padding: 32px; transition: transform 0.3s; }
    .gallery-main:hover img { transform: scale(1.03); }
    .gallery-badges { position: absolute; top: 16px; left: 16px; display: flex; flex-direction: column; gap: 6px; }
    .badge { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.04em; text-transform: uppercase; display: inline-flex; align-items: center; gap: 4px; }
    .badge-brand { background: var(--navy); color: #fff; }
    .badge-sale { background: var(--orange); color: #fff; }
    .badge-new { background: #059669; color: #fff; }
    .badge-stock-green { background: rgba(5,150,105,0.12); color: #059669; border: 1px solid rgba(5,150,105,0.3); font-size: 11px; padding: 4px 10px; border-radius: 6px; font-weight: 600; }
    .badge-low { background: rgba(245,158,11,0.12); color: #d97706; border: 1px solid rgba(245,158,11,0.3); }
    .badge-out { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.25); }
    .gallery-actions { position: absolute; top: 16px; right: 16px; display: flex; flex-direction: column; gap: 8px; }
    .gallery-action-btn { width: 36px; height: 36px; background: rgba(255,255,255,0.95); border: 1px solid var(--border); border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); font-size: 14px; transition: all 0.2s; box-shadow: var(--shadow-sm); }
    .gallery-action-btn:hover { color: var(--orange); border-color: var(--orange); transform: scale(1.05); }
    .gallery-thumbnails { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
    .thumb { background: var(--card); border: 2px solid var(--border); border-radius: 10px; overflow: hidden; cursor: pointer; transition: border-color 0.2s, transform 0.2s; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; }
    .thumb:hover { border-color: var(--orange); transform: translateY(-1px); }
    .thumb.active { border-color: var(--orange); box-shadow: 0 0 0 3px rgba(240,90,34,0.15); }
    .thumb img { width: 100%; height: 100%; object-fit: contain; padding: 8px; transition: transform 0.2s; }
    .thumb:hover img { transform: scale(1.05); }
    .product-info { display: flex; flex-direction: column; gap: 0; }
    .product-info-top { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 16px; }
    .product-brand-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .brand-badge { display: inline-flex; align-items: center; gap: 7px; background: rgba(10,37,64,0.06); border: 1px solid rgba(10,37,64,0.12); color: var(--navy); font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 6px; letter-spacing: 0.04em; text-transform: uppercase; }
    .brand-badge i { color: var(--orange); }
    .share-btn { background: none; border: 1px solid var(--border); color: var(--text-secondary); padding: 6px 12px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s; }
    .share-btn:hover { border-color: var(--navy); color: var(--navy); }
    .product-title { font-size: 26px; font-weight: 800; color: var(--navy); line-height: 1.2; letter-spacing: -0.02em; margin-bottom: 12px; }
    .product-meta-row { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
    .sku-tag { font-size: 12px; color: var(--text-muted); font-weight: 500; background: var(--bg); padding: 4px 10px; border-radius: 5px; border: 1px solid var(--border); }
    .sku-tag span { color: var(--text-secondary); font-weight: 600; }
    .stars { display: flex; align-items: center; gap: 4px; }
    .stars i { color: #f59e0b; font-size: 13px; }
    .stars .rating-num { font-size: 13px; font-weight: 700; color: var(--navy); margin-left: 2px; }
    .stars .review-count { font-size: 12px; color: var(--text-muted); }
    .availability-row { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: rgba(5,150,105,0.06); border: 1px solid rgba(5,150,105,0.2); border-radius: 8px; margin-bottom: 24px; }
    .avail-dot { width: 8px; height: 8px; background: var(--green); border-radius: 50%; box-shadow: 0 0 0 3px rgba(5,150,105,0.2); flex-shrink: 0; }
    .availability-row span { font-size: 13px; font-weight: 600; color: var(--green); }
    .availability-row .avail-units { font-size: 12px; color: var(--text-secondary); font-weight: 400; margin-left: 4px; }
    .price-section { margin-bottom: 24px; }
    .price-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
    .price-main { display: flex; align-items: baseline; gap: 10px; margin-bottom: 6px; }
    .price-currency { font-size: 16px; font-weight: 700; color: var(--text-secondary); }
    .price-amount { font-size: 36px; font-weight: 900; color: var(--navy); letter-spacing: -0.03em; line-height: 1; }
    .price-old-line { font-size: 18px; color: var(--text-muted); text-decoration: line-through; font-weight: 400; }
    .price-vat { font-size: 11px; color: var(--text-muted); font-weight: 400; }
    .price-save { display: inline-block; margin-top: 6px; background: rgba(5,150,105,0.1); color: #059669; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .bulk-pricing { background: var(--bg); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; margin-bottom: 24px; }
    .bulk-pricing-header { display: flex; align-items: center; gap: 7px; padding: 10px 14px; border-bottom: 1px solid var(--border); background: rgba(10,37,64,0.03); }
    .bulk-pricing-header i { color: var(--orange); font-size: 12px; }
    .bulk-pricing-header span { font-size: 12px; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: 0.05em; }
    .bulk-table { width: 100%; border-collapse: collapse; }
    .bulk-table tr { border-bottom: 1px solid var(--border); }
    .bulk-table tr:last-child { border-bottom: none; }
    .bulk-table tr.highlight { background: rgba(240,90,34,0.04); }
    .bulk-table td { padding: 10px 14px; font-size: 13px; }
    .bulk-table td:first-child { color: var(--text-secondary); font-weight: 500; }
    .bulk-table td:nth-child(2) { color: var(--navy); font-weight: 700; text-align: right; }
    .bulk-table td:last-child { text-align: right; }
    .discount-pill { display: inline-block; background: rgba(240,90,34,0.12); color: var(--orange); font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 20px; }
    .best-value-pill { display: inline-flex; align-items: center; gap: 4px; background: var(--orange); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 20px; }
    .order-section { display: flex; flex-direction: column; gap: 14px; }
    .quantity-row { display: flex; align-items: center; gap: 12px; }
    .qty-label { font-size: 13px; font-weight: 600; color: var(--navy); min-width: 60px; }
    .qty-stepper { display: flex; align-items: center; border: 2px solid var(--border); border-radius: 8px; overflow: hidden; transition: border-color 0.2s; }
    .qty-stepper:focus-within { border-color: var(--navy); }
    .qty-btn { width: 40px; height: 44px; background: var(--bg); border: none; cursor: pointer; font-size: 16px; color: var(--navy); display: flex; align-items: center; justify-content: center; transition: background 0.2s, color 0.2s; font-weight: 600; }
    .qty-btn:hover { background: var(--navy); color: #fff; }
    .qty-input { width: 64px; height: 44px; border: none; border-left: 2px solid var(--border); border-right: 2px solid var(--border); text-align: center; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 700; color: var(--navy); outline: none; background: #fff; }
    .qty-hint { font-size: 12px; color: var(--text-muted); }
    .btn-add-cart { background: var(--orange); color: #fff; border: none; padding: 15px 28px; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; width: 100%; }
    .btn-add-cart:hover { background: var(--orange-dark); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(240,90,34,0.35); }
    .btn-add-cart:active { transform: translateY(0); }
    .btn-add-cart.btn-in-cart { background: #059669; cursor: default; }
    .btn-quote-large { background: transparent; color: var(--navy); border: 2px solid var(--navy); padding: 13px 28px; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; width: 100%; text-decoration: none; }
    .btn-quote-large:hover { background: var(--navy); color: #fff; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(10,37,64,0.2); }
    .action-meta { display: flex; gap: 20px; flex-wrap: wrap; }
    .action-meta-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-secondary); }
    .action-meta-item i { color: var(--green); font-size: 12px; }
    .moq { font-size: 12px; color: var(--text-muted); }
    .moq i { color: var(--orange); margin-right: 6px; }
    .help-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 22px 24px; box-shadow: var(--shadow-sm); }
    .help-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
    .help-icon { width: 40px; height: 40px; background: rgba(10,37,64,0.06); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 17px; color: var(--navy); flex-shrink: 0; }
    .help-card-header h4 { font-size: 15px; font-weight: 700; color: var(--navy); }
    .help-card-header p { font-size: 12px; color: var(--text-secondary); }
    .help-actions { display: flex; flex-direction: column; gap: 8px; }
    .help-btn { display: flex; align-items: center; gap: 10px; padding: 11px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; border: none; font-family: 'Inter', sans-serif; }
    .help-btn-phone { background: rgba(10,37,64,0.05); color: var(--navy); border: 1px solid var(--border); }
    .help-btn-phone:hover { background: var(--navy); color: #fff; }
    .help-btn-phone i { font-size: 14px; }
    .help-btn-whatsapp { background: rgba(37,211,102,0.08); color: #128C7E; border: 1px solid rgba(37,211,102,0.25); }
    .help-btn-whatsapp:hover { background: #25D366; color: #fff; }
    .help-btn-whatsapp i { font-size: 15px; }
    .help-hours { margin-top: 12px; font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }
    .help-hours i { color: var(--orange); }
    .tabs-section { background: var(--card); border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 48px; }
    .tabs-nav { display: flex; border-bottom: 1px solid var(--border); background: var(--bg); overflow-x: auto; }
    .tab-btn { padding: 16px 28px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; color: var(--text-muted); background: none; border: none; cursor: pointer; border-bottom: 3px solid transparent; white-space: nowrap; transition: color 0.2s, border-color 0.2s; display: flex; align-items: center; gap: 7px; }
    .tab-btn:hover { color: var(--navy); }
    .tab-btn.active { color: var(--navy); border-bottom-color: var(--orange); background: var(--card); }
    .tab-content { display: none; padding: 36px; }
    .tab-content.active { display: block; }
    .tab-desc p { font-size: 14px; color: var(--text-secondary); line-height: 1.7; margin-bottom: 20px; }
    .tab-features ul { list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .tab-features ul li { padding: 6px 0; display: flex; align-items: flex-start; gap: 8px; font-size: 14px; color: var(--text-secondary); line-height: 1.7; }
    .tab-features ul li i { color: var(--orange); font-size: 12px; margin-top: 4px; }
    .specs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
    .specs-group { padding: 0 24px 0 0; }
    .specs-group:last-child { padding: 0 0 0 24px; border-left: 1px solid var(--border); }
    .specs-group-title { font-size: 12px; font-weight: 700; color: var(--orange); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 14px; margin-top: 24px; }
    .specs-group-title:first-child { margin-top: 0; }
    .specs-table { width: 100%; border-collapse: collapse; }
    .specs-table tr { border-bottom: 1px solid var(--border); }
    .specs-table tr:last-child { border-bottom: none; }
    .specs-table tr:hover { background: rgba(10,37,64,0.02); }
    .specs-table td { padding: 10px 0; font-size: 13px; vertical-align: top; }
    .specs-table td:first-child { color: var(--text-secondary); font-weight: 500; width: 45%; padding-right: 12px; }
    .specs-table td:last-child { color: var(--navy); font-weight: 600; }
    .downloads-list { display: flex; flex-direction: column; gap: 12px; }
    .download-item { display: flex; align-items: center; gap: 16px; padding: 16px 20px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; text-decoration: none; transition: all 0.2s; cursor: pointer; }
    .download-item:hover { border-color: var(--orange); background: rgba(240,90,34,0.03); transform: translateX(2px); }
    .download-icon { width: 44px; height: 44px; background: rgba(240,90,34,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--orange); flex-shrink: 0; }
    .download-info h4 { font-size: 14px; font-weight: 700; color: var(--navy); margin-bottom: 3px; }
    .download-info p { font-size: 12px; color: var(--text-secondary); }
    .download-meta { margin-left: auto; display: flex; align-items: center; gap: 12px; }
    .download-size { font-size: 11px; color: var(--text-muted); }
    .download-btn { display: flex; align-items: center; gap: 5px; background: var(--navy); color: #fff; border: none; padding: 7px 14px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
    .download-btn:hover { background: var(--orange); }
    .shipping-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 28px; }
    .shipping-card { background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 20px; transition: border-color 0.2s; }
    .shipping-card:hover { border-color: rgba(240,90,34,0.3); }
    .shipping-card-icon { width: 44px; height: 44px; background: rgba(10,37,64,0.06); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--navy); margin-bottom: 12px; }
    .shipping-card h4 { font-size: 14px; font-weight: 700; color: var(--navy); margin-bottom: 5px; }
    .shipping-card p { font-size: 12px; color: var(--text-secondary); line-height: 1.5; }
    .shipping-note { background: rgba(240,90,34,0.05); border: 1px solid rgba(240,90,34,0.2); border-radius: 10px; padding: 16px 20px; display: flex; align-items: flex-start; gap: 12px; }
    .shipping-note i { color: var(--orange); margin-top: 2px; font-size: 14px; }
    .shipping-note p { font-size: 13px; color: var(--text-secondary); line-height: 1.55; }
    .shipping-note strong { color: var(--navy); }
    .related-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    .related-card { background: var(--bg); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; display: block; color: inherit; }
    .related-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .related-card img { width: 100%; height: 140px; object-fit: contain; background: var(--card); padding: 16px; }
    .related-card-body { padding: 14px; }
    .related-card-name { font-size: 13px; font-weight: 700; color: var(--navy); margin-bottom: 6px; line-height: 1.3; }
    .related-card-price { font-size: 15px; font-weight: 800; color: var(--navy); }
    .related-card-price .curr { font-size: 11px; font-weight: 600; color: var(--text-secondary); }
    .reviews-layout { display: grid; grid-template-columns: 260px 1fr; gap: 40px; }
    .review-summary { text-align: center; padding: 28px 20px; background: var(--bg); border: 1px solid var(--border); border-radius: 12px; }
    .review-summary-score { font-size: 56px; font-weight: 900; color: var(--navy); letter-spacing: -0.04em; line-height: 1; }
    .review-summary-stars { display: flex; justify-content: center; gap: 3px; margin: 8px 0; }
    .review-summary-stars i { color: #f59e0b; font-size: 16px; }
    .review-summary-count { font-size: 13px; color: var(--text-muted); margin-bottom: 20px; }
    .review-bars { display: flex; flex-direction: column; gap: 7px; }
    .review-bar-row { display: flex; align-items: center; gap: 8px; font-size: 12px; }
    .review-bar-label { color: var(--text-muted); white-space: nowrap; }
    .review-bar-track { flex: 1; height: 6px; background: var(--border); border-radius: 3px; overflow: hidden; }
    .review-bar-fill { height: 100%; background: #f59e0b; border-radius: 3px; }
    .review-bar-count { color: var(--text-secondary); min-width: 24px; text-align: right; }
    .review-list { display: flex; flex-direction: column; gap: 20px; }
    .review-item { padding: 20px; background: var(--bg); border: 1px solid var(--border); border-radius: 12px; }
    .review-item-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
    .review-item-author { font-weight: 700; font-size: 14px; color: var(--navy); }
    .review-item-stars { display: flex; gap: 2px; margin-top: 3px; }
    .review-item-stars i { color: #f59e0b; font-size: 12px; }
    .review-item-date { font-size: 12px; color: var(--text-muted); }
    .review-item-text { font-size: 13px; color: var(--text-secondary); line-height: 1.6; }
    footer { background: var(--navy-dark); padding: 64px 48px 0; border-top: 1px solid rgba(255,255,255,0.06); }
    .footer-inner { max-width: 1632px; margin: 0 auto; }
    .footer-grid { display: grid; grid-template-columns: 280px 1fr 1fr 300px; gap: 48px; padding-bottom: 48px; border-bottom: 1px solid rgba(255,255,255,0.08); }
    .footer-brand .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
    .footer-brand .logo .icon { width: 36px; height: 36px; background: var(--orange); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 17px; color: #fff; }
    .footer-brand .logo .name { font-size: 18px; font-weight: 800; color: #fff; }
    .footer-brand p { font-size: 13px; color: rgba(255,255,255,0.5); line-height: 1.7; margin-bottom: 20px; }
    .footer-socials { display: flex; gap: 8px; }
    .footer-social-btn { width: 34px; height: 34px; background: rgba(255,255,255,0.06); border-radius: 7px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.5); font-size: 14px; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .footer-social-btn:hover { background: var(--orange); color: #fff; }
    .footer-col h4 { font-size: 13px; font-weight: 700; color: #fff; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 18px; }
    .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .footer-col ul li a { font-size: 13px; color: rgba(255,255,255,0.5); text-decoration: none; transition: color 0.2s; display: flex; align-items: center; gap: 7px; }
    .footer-col ul li a:hover { color: rgba(255,255,255,0.9); }
    .footer-col ul li a i { font-size: 11px; color: var(--orange); opacity: 0.7; }
    .contact-item { display: flex; gap: 10px; margin-bottom: 14px; }
    .contact-item i { color: var(--orange); font-size: 14px; margin-top: 2px; flex-shrink: 0; }
    .contact-item span { font-size: 13px; color: rgba(255,255,255,0.5); line-height: 1.5; }
    .newsletter-label { font-size: 13px; color: rgba(255,255,255,0.6); margin-bottom: 12px; line-height: 1.5; }
    .newsletter-form { display: flex; gap: 8px; }
    .newsletter-form input { flex: 1; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; padding: 10px 14px; color: #fff; font-family: 'Inter', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s; }
    .newsletter-form input::placeholder { color: rgba(255,255,255,0.35); }
    .newsletter-form input:focus { border-color: rgba(255,255,255,0.3); }
    .newsletter-form button { background: var(--orange); color: #fff; border: none; border-radius: 7px; padding: 10px 16px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background 0.2s; white-space: nowrap; }
    .newsletter-form button:hover { background: var(--orange-dark); }
    .footer-bottom { padding: 20px 0; display: flex; align-items: center; justify-content: space-between; }
    .footer-bottom p { font-size: 12px; color: rgba(255,255,255,0.35); }
    .footer-bottom-links { display: flex; gap: 20px; }
    .footer-bottom-links a { font-size: 12px; color: rgba(255,255,255,0.35); text-decoration: none; transition: color 0.2s; }
    .footer-bottom-links a:hover { color: rgba(255,255,255,0.6); }
    .img-placeholder { width: 100%; height: 100%; min-height: 300px; background: linear-gradient(135deg,#f8fafc,#e2e8f0); display: flex; align-items: center; justify-content: center; font-size: 60px; color: #94a3b8; }
    .user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}
    @media (max-width: 1200px) {
      .navbar, .breadcrumb-bar, footer { padding-left: 32px; padding-right: 32px; }
      .product-page { padding: 28px 32px 48px; }
      .product-main { grid-template-columns: 1fr 1fr; gap: 28px; }
      .related-grid { grid-template-columns: repeat(2, 1fr); }
      .footer-grid { grid-template-columns: 1fr 1fr; }
      .specs-grid { grid-template-columns: 1fr; }
      .specs-group:last-child { padding: 0; border-left: none; border-top: 1px solid var(--border); padding-top: 16px; margin-top: 8px; }
    }
    @media (max-width: 900px) {
      .product-main { grid-template-columns: 1fr; }
      .product-gallery { position: static; }
      .shipping-grid { grid-template-columns: 1fr 1fr; }
      .reviews-layout { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
      .navbar { padding: 0 20px; height: 60px; }
      .nav-links, .search-bar { display: none; }
      .hamburger { display: block; }
      .breadcrumb-bar, .product-page, footer { padding-left: 20px; padding-right: 20px; }
      .related-grid { grid-template-columns: 1fr 1fr; }
      .footer-grid { grid-template-columns: 1fr; gap: 32px; }
      .footer-bottom { flex-direction: column; gap: 12px; text-align: center; }
      .shipping-grid { grid-template-columns: 1fr; }
      .gallery-thumbnails { grid-template-columns: repeat(4, 1fr); }
      .tab-content { padding: 20px; }
      .specs-grid { grid-template-columns: 1fr; }
      .specs-group:last-child { padding: 0; border-left: none; border-top: 1px solid var(--border); padding-top: 16px; margin-top: 8px; }
    }
  </style>
</head>
<body>

  <div class="announcement-bar">
    <i class="fas fa-truck"></i> Free delivery on orders above TSh 500,000
    <span>|</span> <i class="fas fa-map-marker-alt"></i> Serving all regions in Tanzania
    <span>|</span> <i class="fas fa-headset"></i> Technical support: +255 763 364 721
  </div>

  <nav class="navbar">
    <a href="index.php" class="nav-logo">
      <div class="nav-logo-icon"><i class="fas fa-network-wired"></i></div>
      <div class="nav-logo-text"><span class="brand">BN-Infrastructure</span><span class="tagline">Tanzania</span></div>
    </a>
    <div class="nav-links">
      <a href="index.php">Home</a>
      <a href="catalog.php">Products</a>
      <a href="about.php">Solutions</a>
    </div>
    <div class="nav-actions">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search products, brands, SKU…">
      </div>
      <?php echo userNavHtml(); ?>
      <a href="cart.php" class="cart-btn"><i class="fas fa-shopping-cart"></i><span class="cart-badge"><?php echo $itemCount; ?></span></a>
      <button class="hamburger" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
    </div>
  </nav>
  <div class="nav-overlay" id="navOverlay" onclick="toggleMenu()"></div>
  <div class="mobile-nav" id="mobileNav">
    <button class="close-btn" onclick="toggleMenu()"><i class="fas fa-times"></i></button>
    <a href="index.php" onclick="toggleMenu()">Home</a>
    <a href="catalog.php" onclick="toggleMenu()">Products</a>
    <a href="about.php" onclick="toggleMenu()">Solutions</a>
    <a href="track.php" onclick="toggleMenu()">Track Order</a>
    <?php echo mobileAccountHtml(); ?>
    <a href="cart.php" onclick="toggleMenu()">Cart</a>
  </div>

  <div class="breadcrumb-bar">
    <div class="breadcrumb-inner">
      <ol class="breadcrumb">
        <li><a href="index.php">Home</a></li>
        <li><i class="fas fa-chevron-right"></i></li>
        <li><a href="catalog.php">Products</a></li>
        <?php if ($product['category_name']): ?>
        <li><i class="fas fa-chevron-right"></i></li>
        <li><a href="catalog.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
        <?php endif; ?>
        <li><i class="fas fa-chevron-right"></i></li>
        <li class="active"><?php echo htmlspecialchars($product['sku']); ?></li>
      </ol>
    </div>
  </div>

  <div class="product-page">
    <div class="product-main">

      <!-- LEFT: IMAGE GALLERY -->
      <div class="product-gallery">
        <div class="gallery-main" id="galleryMain">
          <?php echo imageOrPlaceholder($allImages[0], $product['name'], $product['brand'] ?? ''); ?>
          <div class="gallery-badges">
            <?php if ($product['brand']): ?>
            <span class="badge badge-brand"><i class="fas fa-shield-check"></i> <?php echo htmlspecialchars($product['brand']); ?></span>
            <?php endif; ?>
            <?php if ($product['discount_percentage']): ?>
            <span class="badge badge-sale">-<?php echo $product['discount_percentage']; ?>% OFF</span>
            <?php endif; ?>
            <?php foreach ($tags as $tag): ?>
            <span class="badge badge-brand" style="background:rgba(10,37,64,0.06);color:var(--navy);"><?php echo htmlspecialchars(trim($tag)); ?></span>
            <?php endforeach; ?>
          </div>
          <div class="gallery-actions">
            <button class="gallery-action-btn" title="Add to Wishlist"><i class="far fa-heart"></i></button>
            <button class="gallery-action-btn" title="Compare"><i class="fas fa-exchange-alt"></i></button>
          </div>
        </div>
        <?php if (count($allImages) > 1): ?>
        <div class="gallery-thumbnails">
          <?php foreach ($allImages as $idx => $img): ?>
          <div class="thumb <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="switchImage(this, '<?php echo $img ? htmlspecialchars(productImage($img) ?: placeholderSvg($product['name'], $product['brand'] ?? '')) : htmlspecialchars(placeholderSvg($product['name'], $product['brand'] ?? '')); ?>')">
            <?php echo imageOrPlaceholder($img, $product['name'], $product['brand'] ?? ''); ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: PRODUCT DETAILS -->
      <div class="product-info">

        <!-- Product Info Card -->
        <div class="product-info-top">
          <div class="product-brand-row">
            <div class="brand-badge">
              <i class="fas fa-shield-check"></i>
              <?php echo htmlspecialchars($product['brand'] ?: 'Generic'); ?>
            </div>
            <button class="share-btn"><i class="fas fa-share-alt"></i> Share</button>
          </div>

          <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

          <div class="product-meta-row">
            <div class="sku-tag">SKU: <span><?php echo htmlspecialchars($product['sku']); ?></span></div>
            <?php if ($reviewStats['total'] > 0): ?>
            <div class="stars">
              <?php echo starHtml($reviewStats['avg']); ?>
              <span class="rating-num"><?php echo number_format($reviewStats['avg'], 1); ?></span>
              <span class="review-count">(<?php echo $reviewStats['total']; ?> review<?php echo $reviewStats['total'] !== 1 ? 's' : ''; ?>)</span>
            </div>
            <?php endif; ?>
          </div>

          <div class="availability-row">
            <div class="avail-dot"></div>
            <?php echo getStockBadge($product['stock_status']); ?>
          </div>

          <!-- PRICE -->
          <div class="price-section">
            <div class="price-label">Unit Price (excl. 18% VAT)</div>
            <div class="price-main">
              <span class="price-currency">TSh</span>
              <span class="price-amount"><?php echo number_format((float)$product['price'], 0, '.', ','); ?></span>
              <span class="price-vat">excl. VAT</span>
              <?php if ($product['old_price']): ?>
              <span class="price-old-line">TSh <?php echo number_format((float)$product['old_price'], 0, '.', ','); ?></span>
              <?php endif; ?>
            </div>
            <?php if ($product['discount_percentage']): ?>
            <div class="price-save"><i class="fas fa-tag"></i> Save <?php echo $product['discount_percentage']; ?>% — TSh <?php echo number_format((float)($product['old_price'] - $product['price']), 0, '.', ','); ?></div>
            <?php endif; ?>
          </div>

          <!-- BULK PRICING -->
          <div class="bulk-pricing">
            <div class="bulk-pricing-header">
              <i class="fas fa-tags"></i>
              <span>Volume Pricing Tiers</span>
            </div>
            <table class="bulk-table">
              <tr class="highlight">
                <td><i class="fas fa-box" style="color:var(--text-muted);margin-right:6px;font-size:11px;"></i>1 – 4 units</td>
                <td>TSh <?php echo number_format((float)$product['price'], 0, '.', ','); ?> / unit</td>
                <td><span style="font-size:12px;color:var(--text-muted);">Standard</span></td>
              </tr>
              <tr>
                <td><i class="fas fa-boxes" style="color:var(--text-muted);margin-right:6px;font-size:11px;"></i>5 – 9 units</td>
                <td>TSh <?php echo number_format((float)($product['price'] * 0.93), 0, '.', ','); ?> / unit</td>
                <td><span class="discount-pill">Save 7%</span></td>
              </tr>
              <tr>
                <td><i class="fas fa-pallet" style="color:var(--orange);margin-right:6px;font-size:11px;"></i>10+ units</td>
                <td>TSh <?php echo number_format((float)($product['price'] * 0.85), 0, '.', ','); ?> / unit</td>
                <td><span class="best-value-pill"><i class="fas fa-fire"></i> Save 15%</span></td>
              </tr>
            </table>
          </div>

          <!-- ORDER SECTION -->
          <div class="order-section">
            <div class="quantity-row">
              <span class="qty-label">Quantity</span>
              <div class="qty-stepper">
                <button class="qty-btn" onclick="updateQty(-1);document.getElementById('cart-qty').value=document.getElementById('qty').value;document.getElementById('quote-qty').value=document.getElementById('qty').value">−</button>
                <input class="qty-input" type="number" id="qty" value="1" min="1" max="999" onchange="document.getElementById('cart-qty').value=this.value;document.getElementById('quote-qty').value=this.value">
                <button class="qty-btn" onclick="updateQty(1);document.getElementById('cart-qty').value=document.getElementById('qty').value;document.getElementById('quote-qty').value=document.getElementById('qty').value">+</button>
              </div>
            </div>

            <form method="POST" action="cart.php" class="add-cart-form">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
              <input type="hidden" name="qty" id="cart-qty" value="1">
              <?php $inCart = in_array($product['id'], $cartProductIds); ?>
              <button type="submit" class="btn-add-cart <?php echo $inCart ? 'btn-in-cart' : ''; ?>" id="addToCartBtn"><i class="fas <?php echo $inCart ? 'fa-check' : 'fa-shopping-cart'; ?>"></i> <?php echo $inCart ? 'Added to Cart' : 'Add to Cart'; ?></button>
            </form>

            <form method="POST" action="cart.php">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
              <input type="hidden" name="qty" id="quote-qty" value="1">
              <button type="submit" class="btn-quote-large"><i class="fas fa-file-alt"></i> Request Custom Quote</button>
            </form>

            <div class="action-meta">
              <div class="action-meta-item"><i class="fas fa-shield-alt"></i> Genuine Product</div>
              <div class="action-meta-item"><i class="fas fa-undo"></i> 30-Day Returns</div>
              <?php if ($product['warranty']): ?>
              <div class="action-meta-item"><i class="fas fa-award"></i> <?php echo htmlspecialchars($product['warranty']); ?></div>
              <?php else: ?>
              <div class="action-meta-item"><i class="fas fa-award"></i> 1-Year Warranty</div>
              <?php endif; ?>
            </div>

            <div class="moq"><i class="fas fa-box"></i> Minimum order: <?php echo $product['moq']; ?> unit<?php echo $product['moq'] > 1 ? 's' : ''; ?></div>
          </div>
        </div>

        <!-- HELP CARD -->
        <div class="help-card">
          <div class="help-card-header">
            <div class="help-icon"><i class="fas fa-headset"></i></div>
            <div>
              <h4>Need Help?</h4>
              <p>Our technical experts are ready to assist you</p>
            </div>
          </div>
          <div class="help-actions">
            <a href="tel:+255763364721" class="help-btn help-btn-phone">
              <i class="fas fa-phone-alt"></i>
              +255 763 364 721
              <span style="margin-left:auto;font-weight:400;font-size:11px;opacity:0.7;">Call Now</span>
            </a>
            <a href="https://wa.me/255763364721" class="help-btn help-btn-whatsapp">
              <i class="fab fa-whatsapp"></i>
              Chat on WhatsApp
              <span style="margin-left:auto;font-weight:400;font-size:11px;opacity:0.7;">Instant Reply</span>
            </a>
          </div>
          <div class="help-hours">
            <i class="fas fa-clock"></i>
            Available Mon–Sat, 8:00 AM – 6:00 PM (EAT)
          </div>
        </div>

      </div>
    </div>

    <!-- TABS SECTION -->
    <div class="tabs-section">
      <div class="tabs-nav">
        <button class="tab-btn active" data-tab="specs"><i class="fas fa-list-ul"></i> Specifications</button>
        <button class="tab-btn" data-tab="downloads"><i class="fas fa-download"></i> Downloads</button>
        <button class="tab-btn" data-tab="shipping"><i class="fas fa-truck"></i> Shipping Info</button>
        <button class="tab-btn" data-tab="related"><i class="fas fa-th-large"></i> Related Products</button>
        <button class="tab-btn" data-tab="reviews"><i class="fas fa-star"></i> Reviews</button>
      </div>

      <!-- SPECS TAB -->
      <div class="tab-content active" id="tab-specs">
        <?php if ($product['description'] || !empty($features)): ?>
        <div class="tab-desc">
          <?php if ($product['description']): ?>
          <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
          <?php endif; ?>
          <?php if (!empty($features)): ?>
          <div class="tab-features">
            <div class="specs-group-title">Key Features</div>
            <ul>
              <?php foreach ($features as $f): ?>
              <?php if (trim($f)): ?>
              <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(trim($f)); ?></li>
              <?php endif; ?>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($product['specs']): ?>
        <div class="specs-grid">
          <div class="specs-group">
            <div class="specs-group-title">Specifications</div>
            <table class="specs-table">
              <?php foreach ($specLines as $line): ?>
              <?php if (trim($line)): ?>
              <?php $parts = explode(':', $line, 2); ?>
              <tr><td><?php echo htmlspecialchars(trim($parts[0])); ?></td><td><?php echo isset($parts[1]) ? htmlspecialchars(trim($parts[1])) : ''; ?></td></tr>
              <?php endif; ?>
              <?php endforeach; ?>
            </table>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- DOWNLOADS TAB -->
      <div class="tab-content" id="tab-downloads">
        <div class="downloads-list">
          <div class="download-item">
            <div class="download-icon"><i class="fas fa-file-pdf"></i></div>
            <div class="download-info">
              <h4>Product Datasheet</h4>
              <p>Technical specifications, features, and ordering information</p>
            </div>
            <div class="download-meta">
              <span class="download-size">PDF · 2.4 MB</span>
              <button class="download-btn"><i class="fas fa-download"></i> Download</button>
            </div>
          </div>
          <div class="download-item">
            <div class="download-icon"><i class="fas fa-book"></i></div>
            <div class="download-info">
              <h4>Installation Guide</h4>
              <p>Step-by-step installation and initial setup instructions</p>
            </div>
            <div class="download-meta">
              <span class="download-size">PDF · 5.8 MB</span>
              <button class="download-btn"><i class="fas fa-download"></i> Download</button>
            </div>
          </div>
          <div class="download-item">
            <div class="download-icon"><i class="fas fa-certificate"></i></div>
            <div class="download-info">
              <h4>Compliance &amp; Certifications</h4>
              <p>Regulatory compliance documentation and certifications</p>
            </div>
            <div class="download-meta">
              <span class="download-size">PDF · 890 KB</span>
              <button class="download-btn"><i class="fas fa-download"></i> Download</button>
            </div>
          </div>
        </div>
      </div>

      <!-- SHIPPING TAB -->
      <div class="tab-content" id="tab-shipping">
        <div class="shipping-grid">
          <div class="shipping-card">
            <div class="shipping-card-icon"><i class="fas fa-warehouse"></i></div>
            <h4>In-Stock Dispatch</h4>
            <p>Orders placed before 2:00 PM EAT are dispatched the same business day from our Dar es Salaam warehouse.</p>
          </div>
          <div class="shipping-card">
            <div class="shipping-card-icon"><i class="fas fa-truck"></i></div>
            <h4>Dar es Salaam Delivery</h4>
            <p>Next-day delivery within Dar es Salaam metro area. Free delivery on orders above TSh 500,000.</p>
          </div>
          <div class="shipping-card">
            <div class="shipping-card-icon"><i class="fas fa-map-marked-alt"></i></div>
            <h4>Upcountry Regions</h4>
            <p>Delivery to all 30 Tanzanian regions via DHL Express or SGR freight. Transit time: 2–5 business days.</p>
          </div>
        </div>
        <div class="shipping-note">
          <i class="fas fa-info-circle"></i>
          <p>
            <strong>Bulk &amp; Tender Orders (10+ units):</strong> For large orders, contact our sales team for dedicated logistics coordination. We support government procurement and enterprise site deliveries.
            Call <strong>+255 763 364 721</strong> or email <strong>sales@bn-infrastructure.com</strong>.
          </p>
        </div>
      </div>

      <!-- RELATED PRODUCTS TAB -->
      <div class="tab-content" id="tab-related">
        <?php if (!empty($related)): ?>
        <div class="related-grid">
          <?php foreach ($related as $r): ?>
          <a href="?id=<?php echo $r['id']; ?>" class="related-card">
            <?php echo imageOrPlaceholder($r['image'], $r['name'], $r['brand'] ?? ''); ?>
            <div class="related-card-body">
              <div class="related-card-name"><?php echo htmlspecialchars($r['name']); ?></div>
              <div class="related-card-price"><span class="curr">TSh</span><?php echo number_format((float)$r['price'], 0, '.', ','); ?></div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="font-size:14px;color:var(--text-muted);">No related products available.</p>
        <?php endif; ?>
      </div>

      <!-- REVIEWS TAB -->
      <div class="tab-content" id="tab-reviews">
        <div class="reviews-layout">
          <div class="review-summary">
            <div class="review-summary-score"><?php echo $reviewStats['total'] > 0 ? number_format($reviewStats['avg'], 1) : '—'; ?></div>
            <div class="review-summary-stars">
              <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="fas fa-star" style="opacity:<?php echo $i <= round($reviewStats['avg']) ? 1 : 0.25; ?>"></i>
              <?php endfor; ?>
            </div>
            <div class="review-summary-count"><?php echo $reviewStats['total']; ?> review<?php echo $reviewStats['total'] !== 1 ? 's' : ''; ?></div>
            <div class="review-bars">
              <?php foreach ([5,4,3,2,1] as $star): ?>
              <div class="review-bar-row">
                <span class="review-bar-label"><?php echo $star; ?> <i class="fas fa-star" style="font-size:10px;color:#f59e0b;"></i></span>
                <div class="review-bar-track"><div class="review-bar-fill" style="width:<?php echo $reviewStats['total'] > 0 ? round($reviewDist[$star] / $reviewStats['total'] * 100) : 0; ?>%"></div></div>
                <span class="review-bar-count"><?php echo $reviewDist[$star]; ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div>
            <!-- Write a Review -->
            <?php if (isLoggedIn()): ?>
            <div class="review-form-card" style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:24px;">
              <h4 style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:16px;"><i class="fas fa-pen" style="color:var(--orange);margin-right:8px;"></i>Write a Review</h4>
              <?php if ($reviewSuccess): ?>
              <div style="background:rgba(5,150,105,0.1);color:#059669;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;"><i class="fas fa-check-circle"></i> <?php echo $reviewSuccess; ?></div>
              <?php endif; ?>
              <?php if ($reviewError): ?>
              <div style="background:#fef2f2;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;"><i class="fas fa-exclamation-circle"></i> <?php echo $reviewError; ?></div>
              <?php endif; ?>
              <form method="POST" action="">
                <input type="hidden" name="action" value="review">
                <div class="form-group">
                  <label style="font-size:13px;font-weight:600;color:var(--navy);margin-bottom:6px;display:block;">Rating <span style="color:var(--orange);">*</span></label>
                  <div id="starRating" style="display:flex;gap:4px;font-size:24px;cursor:pointer;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star" data-val="<?php echo $i; ?>" style="color:#f59e0b;transition:transform .15s;" onmouseover="highlightStars(<?php echo $i; ?>)" onmouseout="resetStars()" onclick="selectStar(<?php echo $i; ?>)"></i>
                    <?php endfor; ?>
                  </div>
                  <input type="hidden" name="rating" id="ratingInput" value="5">
                </div>
                <div class="form-group">
                  <label style="font-size:13px;font-weight:600;color:var(--navy);margin-bottom:6px;display:block;">Title</label>
                  <input type="text" name="review_title" placeholder="Summarize your experience" style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:9px;font-family:'Inter',sans-serif;font-size:14px;outline:none;transition:border-color .2s;">
                </div>
                <div class="form-group">
                  <label style="font-size:13px;font-weight:600;color:var(--navy);margin-bottom:6px;display:block;">Your Review <span style="color:var(--orange);">*</span></label>
                  <textarea name="review_comment" rows="4" required placeholder="Tell others about your experience with this product..." style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:9px;font-family:'Inter',sans-serif;font-size:14px;outline:none;resize:vertical;transition:border-color .2s;"></textarea>
                </div>
                <button type="submit" style="background:var(--orange);color:#fff;border:none;padding:12px 28px;border-radius:10px;font-family:'Inter',sans-serif;font-size:14px;font-weight:700;cursor:pointer;transition:background .2s;">
                  <i class="fas fa-paper-plane" style="margin-right:6px;"></i>Submit Review
                </button>
              </form>
            </div>
            <?php else: ?>
            <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:20px 24px;margin-bottom:24px;text-align:center;">
              <p style="font-size:14px;color:var(--text-secondary);margin-bottom:12px;">Sign in to leave a review for this product.</p>
              <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" style="display:inline-flex;align-items:center;gap:6px;background:var(--orange);color:#fff;padding:10px 24px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:background .2s;">
                <i class="fas fa-sign-in-alt"></i> Sign In to Review
              </a>
            </div>
            <?php endif; ?>

            <div class="review-list">
              <?php if (!empty($reviews)): ?>
              <?php foreach ($reviews as $rev): ?>
              <div class="review-item">
                <div class="review-item-header">
                  <div>
                    <div class="review-item-author"><?php echo htmlspecialchars($rev['reviewer_name'] ?: ($rev['full_name'] ?? 'Customer')); ?> — <?php echo htmlspecialchars($rev['reviewer_location'] ?: 'Tanzania'); ?></div>
                    <div class="review-item-stars">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="fas fa-star" style="opacity:<?php echo $i <= $rev['rating'] ? 1 : 0.25; ?>"></i>
                      <?php endfor; ?>
                    </div>
                  </div>
                  <div style="text-align:right;">
                    <?php if ($rev['verified_purchase']): ?>
                    <span class="badge badge-stock-green" style="font-size:10px;"><i class="fas fa-check"></i> Verified Purchase</span>
                    <?php endif; ?>
                    <div class="review-item-date"><?php echo date('M j, Y', strtotime($rev['created_at'])); ?></div>
                  </div>
                </div>
                <?php if ($rev['title']): ?>
                <div style="font-weight:700;font-size:14px;color:var(--navy);margin-bottom:6px;"><?php echo htmlspecialchars($rev['title']); ?></div>
                <?php endif; ?>
                <p class="review-item-text"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                <?php if ($rev['company']): ?>
                <div style="margin-top:8px;font-size:12px;color:var(--text-muted);"><i class="fas fa-building" style="margin-right:4px;"></i><?php echo htmlspecialchars($rev['company']); ?></div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
              <?php else: ?>
              <div style="text-align:center;padding:40px 20px;color:var(--text-muted);">
                <i class="far fa-comment-dots" style="font-size:40px;margin-bottom:12px;display:block;opacity:0.4;"></i>
                <p style="font-size:14px;">No reviews yet. Be the first to review this product!</p>
              </div>
              <?php endif; ?>
            </div>
          </div>
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
        <div class="footer-col"><h4>Quick Links</h4><ul><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> All Products</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> New Arrivals</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Best Sellers</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Special Offers</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Request a Quote</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Bulk Orders</a></li></ul></div>
        <div class="footer-col"><h4>Company</h4><ul><li><a href="about.php"><i class="fas fa-chevron-right"></i> About BN-Infrastructure</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Our Brands</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Solutions</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Blog &amp; Resources</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Careers</a></li><li><a href="privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li></ul></div>
        <div class="footer-col"><h4>Contact Us</h4><div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>Plot 45, Mikocheni Light Industrial Area, Dar es Salaam, Tanzania</span></div><div class="contact-item"><i class="fas fa-phone-alt"></i><span>+255 763 364 721 <br>+255 763 364 721</span></div><div class="contact-item"><i class="fas fa-envelope"></i><span>sales@bn-infrastructure.com</span></div><div style="margin-top:20px;"><h4>Newsletter</h4><div class="newsletter-label">Get product updates and exclusive deals</div><div class="newsletter-form"><input type="email" placeholder="Your email address"><button>Subscribe</button></div></div></div>
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
    function updateQty(delta) {
      const input = document.getElementById('qty');
      let val = parseInt(input.value) + delta;
      if (val < 1) val = 1;
      if (val > 999) val = 999;
      input.value = val;
    }

    function toggleMenu(){document.getElementById('mobileNav').classList.toggle('open');document.getElementById('navOverlay').classList.toggle('open')}

    document.querySelectorAll('.add-cart-form').forEach(function(f){
      f.addEventListener('submit',function(e){
        e.preventDefault();
        var btn=this.querySelector('button[type="submit"]');
        if (btn.classList.contains('btn-in-cart')) return;
        var orig=btn.innerHTML;
        btn.disabled=true;
        btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Adding...';
        var fd=new FormData(this);fd.set('_ajax','1');
        var self=this;
        fetch(this.action,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){
          return r.text();
        }).then(function(txt){
          try{var d=JSON.parse(txt);if(d.redirect){window.location.href=d.redirect;return}if(d.ok){var badge=document.querySelector('.cart-badge');if(badge){badge.textContent=d.count||1;badge.style.transform='scale(1.4)';setTimeout(function(){badge.style.transform='scale(1)'},200);}btn.innerHTML='<i class="fas fa-check"></i> Added to Cart';btn.style.background='#059669';btn.classList.add('btn-in-cart');btn.disabled=false;return}}catch(ex){}
          self.submit();
        }).catch(function(){
          self.submit();
        });
      });
    });

    // Tabs
    document.querySelectorAll('.tab-btn').forEach(function(btn){
      btn.addEventListener('click',function(){
        document.querySelectorAll('.tab-btn').forEach(function(b){b.classList.remove('active')});
        document.querySelectorAll('.tab-content').forEach(function(c){c.classList.remove('active')});
        this.classList.add('active');
        var target=document.getElementById('tab-'+this.dataset.tab);
        if(target) target.classList.add('active');
      });
    });

    // Download buttons
    document.querySelectorAll('.download-btn').forEach(function(btn){
      btn.addEventListener('click',function(){
        var orig=this.innerHTML;
        this.innerHTML='<i class="fas fa-check"></i> Downloaded';
        this.style.background='#059669';
        setTimeout(function(){this.innerHTML=orig;this.style.background=''}.bind(this),2000);
      });
    });

    // Share button
    document.querySelectorAll('.share-btn').forEach(function(btn){
      btn.addEventListener('click',function(){
        if(navigator.share){
          navigator.share({title:document.title,url:window.location.href});
        }
      });
    });

    // Star rating
    var currentRating = 5;
    function highlightStars(n) {
      var stars = document.querySelectorAll('#starRating i');
      stars.forEach(function(s, i) { s.style.opacity = i < n ? '1' : '0.25'; s.style.transform = i < n ? 'scale(1.15)' : 'scale(1)'; });
    }
    function resetStars() { highlightStars(currentRating); }
    function selectStar(n) { currentRating = n; document.getElementById('ratingInput').value = n; highlightStars(n); }

    // Gallery image switcher
    function switchImage(thumb, src) {
      var main = document.getElementById('galleryMain');
      var img = main.querySelector('img');
      if (img) img.src = src;
      document.querySelectorAll('.thumb').forEach(function(t) { t.classList.remove('active'); });
      thumb.classList.add('active');
    }
  </script>

  <script>
  <?php echo userMenuJs(); ?>
  <?php echo scrollRevealJs(); ?>
  </script>
</body></html>
