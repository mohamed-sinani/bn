<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/cart.php';
require_once __DIR__ . '/src/functions.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

$categoryFilter = $_GET['category'] ?? '';
$brandFilter = $_GET['brand'] ?? '';
$stockFilter = $_GET['in_stock'] ?? '';
$sort = $_GET['sort'] ?? 'best';
$search = $_GET['search'] ?? '';
$specFilter = $_GET['spec'] ?? '';
$minPrice = $_GET['min_price'] ? (float)$_GET['min_price'] : 0;
$maxPrice = $_GET['max_price'] ? (float)$_GET['max_price'] : 999999999;

$categories = fetchAll("SELECT * FROM categories ORDER BY name");

$where = [];
$params = [];

if ($categoryFilter) {
    $where[] = "p.category_id = ?";
    $params[] = (int)$categoryFilter;
}
if ($brandFilter) {
    $where[] = "p.brand = ?";
    $params[] = $brandFilter;
}
if ($stockFilter === '1') {
    $where[] = "p.stock_status = 'in_stock'";
}
if ($search) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ? OR p.specs LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($minPrice > 0) {
    $where[] = "p.price >= ?";
    $params[] = $minPrice;
}
if ($maxPrice < 999999999) {
    $where[] = "p.price <= ?";
    $params[] = $maxPrice;
}
if ($specFilter) {
    $where[] = "p.specs LIKE ?";
    $params[] = "%$specFilter%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderBy = 'ORDER BY p.created_at DESC';
switch ($sort) {
    case 'newest': $orderBy = 'ORDER BY p.created_at DESC'; break;
    case 'price-low': $orderBy = 'ORDER BY p.price ASC'; break;
    case 'price-high': $orderBy = 'ORDER BY p.price DESC'; break;
    case 'name': $orderBy = 'ORDER BY p.name ASC'; break;
    case 'best':
    default: $orderBy = 'ORDER BY p.featured DESC, p.created_at DESC'; break;
}

$total = fetchOne("SELECT COUNT(*) as count FROM products p $whereClause", $params)['count'];
$totalPages = ceil($total / $perPage);

$allParams = array_merge($params, [$perPage, $offset]);
$products = fetchAll(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     $whereClause 
     $orderBy 
     LIMIT ? OFFSET ?",
    $allParams
);

$brands = fetchAll("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand");

$minPriceDb = fetchOne("SELECT MIN(price) as min_price FROM products")['min_price'] ?? 0;
$maxPriceDb = fetchOne("SELECT MAX(price) as max_price FROM products")['max_price'] ?? 99999999;

$selectedCatName = '';
if ($categoryFilter) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $categoryFilter) { $selectedCatName = $cat['name']; break; }
    }
}
$activeChips = [];
if ($categoryFilter && $selectedCatName) $activeChips[] = ['label' => $selectedCatName, 'param' => 'category'];
if ($brandFilter) $activeChips[] = ['label' => htmlspecialchars($brandFilter), 'param' => 'brand'];
if ($stockFilter === '1') $activeChips[] = ['label' => 'In Stock', 'param' => 'in_stock'];
if ($specFilter) $activeChips[] = ['label' => 'Spec: ' . htmlspecialchars($specFilter), 'param' => 'spec'];
$hasActiveFilters = !empty($activeChips) || $minPrice > 0 || $maxPrice < 999999999;
$cartProductIds = array_keys(cartGetItems());
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Network Equipment Catalog — BN-Infrastructure</title>
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
      --shadow-sm: 0 1px 3px rgba(10,37,64,0.08), 0 1px 2px rgba(10,37,64,0.04);
      --shadow-md: 0 4px 12px rgba(10,37,64,0.1), 0 2px 6px rgba(10,37,64,0.06);
      --shadow-lg: 0 10px 30px rgba(10,37,64,0.12), 0 4px 12px rgba(10,37,64,0.08);
    }
    html { scroll-behavior: smooth; }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-primary); font-size: 15px; line-height: 1.6; }
    .announcement-bar { background: var(--orange); color: #fff; text-align: center; padding: 9px 24px; font-size: 13px; font-weight: 500; letter-spacing: 0.01em; }
    .announcement-bar i { margin-right: 6px; opacity: 0.9; }
    .announcement-bar span { margin: 0 18px; opacity: 0.7; }
    .navbar { background: var(--navy); padding: 0 48px; display: flex; align-items: center; height: 70px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 12px rgba(0,0,0,0.2); }
    .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; margin-right: 40px; flex-shrink: 0; }
    .nav-logo-icon { width: 38px; height: 38px; background: var(--orange); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; }
    .nav-logo-text { display: flex; flex-direction: column; line-height: 1.1; }
    .nav-logo-text .brand { font-size: 18px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
    .nav-logo-text .tagline { font-size: 10px; font-weight: 400; color: rgba(255,255,255,0.5); letter-spacing: 0.08em; text-transform: uppercase; }
    .nav-links { display: flex; align-items: center; gap: 4px; flex: 1; }
    .nav-links a { text-decoration: none; color: rgba(255,255,255,0.75); font-size: 14px; font-weight: 500; padding: 8px 14px; border-radius: 6px; transition: color 0.2s, background 0.2s; white-space: nowrap; }
    .nav-links a:hover { color: #fff; background: rgba(255,255,255,0.08); }
    .nav-links a.active { color: #fff; background: rgba(255,255,255,0.1); }
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
    .page-wrapper { max-width: 1728px; margin: 0 auto; padding: 0 48px; }
    .page-header { padding: 28px 0 20px; border-bottom: 1px solid var(--border); margin-bottom: 22px; }
    .breadcrumb { display: flex; align-items: center; gap: 6px; margin-bottom: 10px; font-size: 13px; color: var(--text-muted); }
    .breadcrumb a { color: var(--text-muted); text-decoration: none; transition: color 0.2s; }
    .breadcrumb a:hover { color: var(--orange); }
    .breadcrumb .sep { font-size: 10px; opacity: 0.6; }
    .breadcrumb .current { color: var(--text-secondary); font-weight: 500; }
    .page-header-row { display: flex; align-items: center; justify-content: space-between; }
    .page-title { font-size: 28px; font-weight: 800; color: var(--navy); letter-spacing: -0.02em; }
    .page-subtitle { font-size: 14px; color: var(--text-secondary); margin-top: 3px; }
    .page-header-actions { display: flex; gap: 10px; align-items: center; }
    .btn-outline-navy { background: transparent; border: 1.5px solid var(--border); color: var(--text-secondary); padding: 8px 16px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; text-decoration: none; }
    .btn-outline-navy:hover { border-color: var(--orange); color: var(--orange); }
    .btn-primary-sm { background: var(--orange); border: none; color: #fff; padding: 8px 16px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
    .btn-primary-sm:hover { background: var(--orange-dark); transform: translateY(-1px); }
    .horizontal-filter-bar { background: var(--card); border: 1px solid var(--border); border-radius: 14px; box-shadow: var(--shadow-md); padding: 0 20px; margin-bottom: 14px; display: flex; align-items: center; gap: 0; height: 64px; position: relative; }
    .hfb-label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; margin-right: 16px; flex-shrink: 0; }
    .hfb-filters-group { display: flex; align-items: center; gap: 6px; flex: 1; }
    .filter-dropdown-wrap { position: relative; }
    .filter-dropdown-btn { display: inline-flex; align-items: center; gap: 7px; background: var(--bg); border: 1.5px solid var(--border); border-radius: 8px; padding: 8px 13px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; color: var(--text-primary); cursor: pointer; transition: all 0.2s; white-space: nowrap; height: 38px; }
    .filter-dropdown-btn:hover { border-color: var(--orange); color: var(--orange); background: rgba(240,90,34,0.04); }
    .filter-dropdown-btn.active-filter { border-color: var(--orange); color: var(--orange); background: rgba(240,90,34,0.07); }
    .filter-dropdown-btn i.icon-left { color: var(--orange); font-size: 11px; }
    .filter-dropdown-btn i.chevron { font-size: 10px; color: var(--text-muted); transition: transform 0.2s; margin-left: 2px; }
    .filter-dropdown-btn.open i.chevron { transform: rotate(180deg); }
    .filter-badge-count { background: var(--orange); color: #fff; font-size: 10px; font-weight: 700; min-width: 18px; height: 18px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; padding: 0 5px; }
    .filter-dropdown-panel { position: absolute; top: calc(100% + 8px); left: 0; background: var(--card); border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-lg); z-index: 500; min-width: 220px; padding: 12px 0; opacity: 0; transform: translateY(-8px); pointer-events: none; transition: opacity 0.18s ease, transform 0.18s ease; }
    .filter-dropdown-panel.open { opacity: 1; transform: translateY(0); pointer-events: all; }
    .dropdown-option-list { max-height: 240px; overflow-y: auto; }
    .dropdown-option { display: flex; align-items: center; gap: 10px; padding: 8px 14px; cursor: pointer; transition: background 0.15s; }
    .dropdown-option:hover { background: var(--bg); }
    .dropdown-option input[type="radio"] { display: none; }
    .custom-checkbox { width: 16px; height: 16px; border: 2px solid var(--border); border-radius: 50%; background: #fff; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
    .dropdown-option input[type="radio"]:checked + .custom-checkbox { background: var(--orange); border-color: var(--orange); }
    .dropdown-option input[type="radio"]:checked + .custom-checkbox::after { content: ''; display: block; width: 4px; height: 8px; border: 2px solid #fff; border-left: none; border-top: none; transform: rotate(45deg) translateY(-1px); }
    .dropdown-option-label { font-size: 13px; color: var(--text-secondary); flex: 1; }
    .dropdown-option-count { font-size: 11px; font-weight: 600; color: var(--text-muted); background: var(--bg); padding: 2px 6px; border-radius: 10px; }
    .price-dropdown-panel { min-width: 280px; padding: 16px 18px; }
    .price-dropdown-panel .pdp-title { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 14px; }
    .price-range-inputs { display: flex; gap: 10px; }
    .price-range-inputs input { flex: 1; border: 1px solid var(--border); border-radius: 6px; padding: 7px 10px; font-family: 'Inter', sans-serif; font-size: 12px; color: var(--text-primary); outline: none; transition: border-color 0.2s; }
    .price-range-inputs input:focus { border-color: var(--orange); }
    .price-apply-btn { margin-top: 14px; width: 100%; background: var(--navy); color: #fff; border: none; padding: 8px 16px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
    .price-apply-btn:hover { background: var(--orange); }
    .hfb-avail { display: flex; align-items: center; gap: 8px; padding: 0 14px; flex-shrink: 0; }
    .hfb-avail-label { font-size: 13px; color: var(--text-secondary); white-space: nowrap; font-weight: 500; }
    .toggle-switch { position: relative; width: 38px; height: 22px; cursor: pointer; flex-shrink: 0; }
    .toggle-switch input { display: none; }
    .toggle-track { position: absolute; inset: 0; background: var(--border); border-radius: 11px; transition: background 0.2s; }
    .toggle-switch input:checked + .toggle-track { background: var(--orange); }
    .toggle-thumb { position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: transform 0.2s; }
    .toggle-switch input:checked ~ .toggle-thumb { transform: translateX(16px); }
    .hfb-divider { width: 1px; height: 36px; background: var(--border); flex-shrink: 0; margin: 0 14px; }
    .hfb-sort-group { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .hfb-sort-label { font-size: 12px; font-weight: 600; color: var(--text-muted); white-space: nowrap; text-transform: uppercase; letter-spacing: 0.05em; }
    .sort-select { border: 1.5px solid var(--border); border-radius: 8px; padding: 8px 32px 8px 12px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; color: var(--text-primary); background: var(--bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%238fa0b3'/%3E%3C/svg%3E") no-repeat right 12px center; -webkit-appearance: none; cursor: pointer; outline: none; transition: border-color 0.2s; height: 38px; }
    .sort-select:focus { border-color: var(--orange); }
    .hfb-right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
    .hfb-results { font-size: 13px; color: var(--text-secondary); white-space: nowrap; }
    .hfb-results strong { color: var(--navy); font-weight: 700; }
    .view-toggle { display: flex; gap: 4px; }
    .view-btn { width: 34px; height: 34px; border-radius: 7px; border: 1px solid var(--border); background: var(--card); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 13px; transition: all 0.15s; }
    .view-btn.active { background: var(--navy); color: #fff; border-color: var(--navy); }
    .view-btn:hover:not(.active) { border-color: var(--orange); color: var(--orange); }
    .active-chips-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; min-height: 30px; }
    .chips-label { font-size: 12px; font-weight: 600; color: var(--text-muted); white-space: nowrap; }
    .chip { display: inline-flex; align-items: center; gap: 5px; background: rgba(240,90,34,0.09); color: var(--orange); border: 1px solid rgba(240,90,34,0.25); padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
    .chip:hover { background: rgba(240,90,34,0.16); border-color: rgba(240,90,34,0.4); }
    .chip .chip-remove { font-size: 10px; opacity: 0.7; margin-left: 1px; }
    .clear-all-link { font-size: 12px; font-weight: 600; color: var(--orange); cursor: pointer; background: none; border: none; font-family: 'Inter', sans-serif; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(240,90,34,0.2); background: rgba(240,90,34,0.06); transition: all 0.2s; white-space: nowrap; margin-left: auto; }
    .clear-all-link:hover { background: rgba(240,90,34,0.12); border-color: rgba(240,90,34,0.35); }
    .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 28px; }
    .product-card { background: var(--card); border-radius: 14px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden; transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; display: flex; flex-direction: column; }
    .product-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: rgba(240,90,34,0.15); }
    .product-image-wrap { position: relative; background: #f8fafc; overflow: hidden; display: block; text-decoration: none; }
    .product-image-wrap img { width: 100%; height: 180px; object-fit: cover; transition: transform 0.3s; }
    .product-card:hover .product-image-wrap img { transform: scale(1.04); }
    .product-badges { position: absolute; top: 10px; left: 10px; display: flex; flex-direction: column; gap: 4px; }
    .badge { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.04em; text-transform: uppercase; display: inline-flex; align-items: center; gap: 4px; }
    .badge-brand { background: var(--navy); color: #fff; }
    .badge-sale { background: var(--orange); color: #fff; }
    .badge-new { background: #059669; color: #fff; }
    .badge-stock { background: rgba(5,150,105,0.12); color: #059669; border: 1px solid rgba(5,150,105,0.3); }
    .badge-low { background: rgba(245,158,11,0.12); color: #d97706; border: 1px solid rgba(245,158,11,0.3); }
    .badge-out { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.25); }
    .product-wishlist { position: absolute; top: 8px; right: 8px; width: 30px; height: 30px; background: rgba(255,255,255,0.9); border: none; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-muted); font-size: 12px; transition: all 0.2s; opacity: 0; backdrop-filter: blur(4px); }
    .product-card:hover .product-wishlist { opacity: 1; }
    .product-wishlist:hover { color: var(--orange); background: #fff; }
    .product-body { padding: 13px 14px 13px; flex: 1; display: flex; flex-direction: column; }
    .product-sku { font-size: 10px; color: var(--text-muted); font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 4px; }
    .product-name { font-size: 13px; font-weight: 700; color: var(--navy); line-height: 1.35; margin-bottom: 7px; flex: 1; }
    .product-brand-row { display: flex; align-items: center; gap: 5px; margin-bottom: 7px; flex-wrap: wrap; }
    .product-spec { font-size: 11.5px; color: var(--text-secondary); margin-bottom: 9px; display: flex; align-items: center; gap: 5px; line-height: 1.4; }
    .product-spec i { color: var(--orange); font-size: 10px; flex-shrink: 0; }
    .product-price-block { margin-bottom: 3px; }
    .product-price-label { font-size: 10px; color: var(--text-muted); font-weight: 500; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.04em; }
    .product-price { font-size: 18px; font-weight: 800; color: var(--navy); letter-spacing: -0.02em; line-height: 1.1; }
    .product-price .currency { font-size: 11px; font-weight: 600; color: var(--text-secondary); margin-right: 2px; }
    .product-price-old { font-size: 11px; color: var(--text-muted); text-decoration: line-through; margin-left: 6px; font-weight: 400; }
    .product-moq { font-size: 11px; color: var(--text-muted); font-weight: 500; margin-bottom: 10px; margin-top: 3px; display: flex; align-items: center; gap: 4px; }
    .product-moq i { color: var(--orange); font-size: 9px; }
    .product-actions { display: flex; gap: 6px; }
    .btn-cart { flex: 1; background: var(--navy); color: #fff; border: none; padding: 8px 10px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 11.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all 0.2s; }
    .btn-cart:hover { background: var(--orange); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(240,90,34,0.3); }
    .btn-cart.btn-in-cart { background: #059669; pointer-events: none; }
    .btn-quote { background: transparent; color: var(--navy); border: 1.5px solid var(--border); padding: 8px 10px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 11.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all 0.2s; white-space: nowrap; }
    .btn-quote:hover { border-color: var(--orange); color: var(--orange); transform: translateY(-1px); }
    .pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 20px 0; border-top: 1px solid var(--border); }
    .pagination-info { font-size: 13px; color: var(--text-secondary); }
    .pagination-controls { display: flex; align-items: center; gap: 4px; }
    .page-btn { width: 36px; height: 36px; border: 1px solid var(--border); background: var(--card); color: var(--text-secondary); border-radius: 7px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; transition: all 0.15s; text-decoration: none; }
    .page-btn:hover:not(.active):not(:disabled) { border-color: var(--orange); color: var(--orange); }
    .page-btn.active { background: var(--navy); border-color: var(--navy); color: #fff; font-weight: 700; }
    .page-btn:disabled { opacity: 0.35; cursor: default; }
    .page-btn.nav-arrow { font-size: 11px; }
    .page-ellipsis { font-size: 13px; color: var(--text-muted); padding: 0 4px; }
    footer { background: var(--navy-dark); padding: 64px 48px 0; border-top: 1px solid rgba(255,255,255,0.06); }
    .footer-inner { max-width: 1728px; margin: 0 auto; }
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
    .hamburger { display: none; background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; padding: 6px; margin-left: auto; }
    .nav-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; }
    .nav-overlay.open { display: block; }
    .mobile-nav { position: fixed; top: 0; right: -280px; width: 280px; height: 100vh; background: var(--navy); z-index: 1001; transition: right 0.3s ease; padding: 80px 24px 24px; overflow-y: auto; }
    .mobile-nav.open { right: 0; }
    .mobile-nav a { display: block; color: rgba(255,255,255,0.8); text-decoration: none; padding: 12px 0; font-size: 15px; font-weight: 500; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .mobile-nav a:hover { color: var(--orange); }
    .mobile-nav .close-btn { position: absolute; top: 16px; right: 16px; background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; }
    .mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
    .user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}.img-placeholder{width:100%;height:200px;background:linear-gradient(135deg,#f8fafc,#e2e8f0);display:flex;align-items:center;justify-content:center;font-size:40px;color:#94a3b8}.reveal,.card-reveal,.product-reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
    @media (max-width: 1400px) { .products-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 1200px) { .navbar, .announcement-bar, .page-wrapper, footer { padding-left: 32px; padding-right: 32px; } .products-grid { grid-template-columns: repeat(2, 1fr); } .footer-grid { grid-template-columns: 1fr 1fr; } .hfb-sort-group { display: none; } }
    @media (max-width: 768px) { .navbar { padding: 0 16px; height: 60px; } .nav-links { display: none; } .hamburger{display:block} .page-wrapper { padding: 0 20px; } footer { padding: 40px 20px 0; } .products-grid { grid-template-columns: 1fr; } .footer-grid { grid-template-columns: 1fr; gap: 32px; } .footer-bottom { flex-direction: column; gap: 12px; text-align: center; } .search-bar { width: 160px; } .horizontal-filter-bar { flex-wrap: wrap; height: auto; padding: 12px 16px; gap: 8px; } }
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
      <a href="catalog.php" class="active">Products</a>
      <a href="about.php">Solutions</a>
      <a href="request-quote.php">Request Quote</a>
      <a href="track.php">Track Order</a>
      <a href="login.php">Account</a>
    </div>
    <div class="nav-actions">
      <form method="GET" action="/catalog.php" style="display:contents;">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="search" placeholder="Search products, brands, SKU…" value="<?php echo htmlspecialchars($search); ?>" onkeydown="if(event.key==='Enter'){this.form.submit()}">
        </div>
      </form>
      <?php echo userNavHtml(); ?>
      <a href="cart.php" class="cart-btn"><i class="fas fa-shopping-cart"></i><span class="cart-badge"><?php echo cartCount(); ?></span></a>
    </div>
    <button class="hamburger" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
  </nav>
  <div class="nav-overlay" id="navOverlay" onclick="toggleMenu()"></div>
  <div class="mobile-nav" id="mobileNav">
    <button class="close-btn" onclick="toggleMenu()"><i class="fas fa-times"></i></button>
    <a href="index.php" onclick="toggleMenu()">Home</a>
    <a href="catalog.php" onclick="toggleMenu()">Products</a>
    <a href="about.php" onclick="toggleMenu()">Solutions</a>
    <a href="request-quote.php" onclick="toggleMenu()">Request Quote</a>
    <a href="track.php" onclick="toggleMenu()">Track Order</a>
    <?php echo mobileAccountHtml(); ?>
    <a href="cart.php" onclick="toggleMenu()">Cart</a>
  </div>

  <div class="page-wrapper">

    <div class="page-header">
      <nav class="breadcrumb">
        <a href="index.php">Home</a>
        <i class="fas fa-chevron-right sep"></i>
        <span class="current">Network Equipment Catalog</span>
      </nav>
      <div class="page-header-row">
        <div>
          <h1 class="page-title">Network Equipment Catalog</h1>
          <p class="page-subtitle">Enterprise-grade networking for businesses, ISPs, and system integrators across Tanzania</p>
        </div>
      </div>
    </div>

    <form method="GET" id="filterForm">
      <div class="horizontal-filter-bar">
        <span class="hfb-label"><i class="fas fa-sliders-h" style="color:var(--orange);margin-right:6px;"></i>Filters:</span>

        <div class="hfb-filters-group">

          <div class="filter-dropdown-wrap" id="categoryWrap">
            <button type="button" class="filter-dropdown-btn <?php echo $categoryFilter ? 'active-filter' : ''; ?>" id="categoryBtn" onclick="toggleDropdown('categoryPanel', 'categoryBtn')">
              <i class="fas fa-layer-group icon-left"></i>
              Category
              <?php if ($categoryFilter): ?><span class="filter-badge-count">1</span><?php endif; ?>
              <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div class="filter-dropdown-panel" id="categoryPanel">
              <div class="dropdown-option-list">
                <label class="dropdown-option">
                  <input type="radio" name="category" value="" <?php echo !$categoryFilter ? 'checked' : ''; ?> onchange="this.form.submit()">
                  <span class="custom-checkbox"></span>
                  <span class="dropdown-option-label">All Categories</span>
                </label>
                <?php foreach ($categories as $cat):
                    $count = fetchOne("SELECT COUNT(*) as c FROM products WHERE category_id = ?", [$cat['id']])['c'];
                ?>
                <label class="dropdown-option">
                  <input type="radio" name="category" value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                  <span class="custom-checkbox"></span>
                  <span class="dropdown-option-label"><?php echo htmlspecialchars($cat['name']); ?></span>
                  <span class="dropdown-option-count"><?php echo $count; ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="filter-dropdown-wrap" id="brandWrap">
            <button type="button" class="filter-dropdown-btn <?php echo $brandFilter ? 'active-filter' : ''; ?>" id="brandBtn" onclick="toggleDropdown('brandPanel', 'brandBtn')">
              <i class="fas fa-tag icon-left"></i>
              Brand
              <?php if ($brandFilter): ?><span class="filter-badge-count">1</span><?php endif; ?>
              <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div class="filter-dropdown-panel" id="brandPanel">
              <div class="dropdown-option-list">
                <label class="dropdown-option">
                  <input type="radio" name="brand" value="" <?php echo !$brandFilter ? 'checked' : ''; ?> onchange="this.form.submit()">
                  <span class="custom-checkbox"></span>
                  <span class="dropdown-option-label">All Brands</span>
                </label>
                <?php foreach ($brands as $b): ?>
                <label class="dropdown-option">
                  <input type="radio" name="brand" value="<?php echo htmlspecialchars($b['brand']); ?>" <?php echo $brandFilter === $b['brand'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                  <span class="custom-checkbox"></span>
                  <span class="dropdown-option-label"><?php echo htmlspecialchars($b['brand']); ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="filter-dropdown-wrap" id="specWrap">
            <button type="button" class="filter-dropdown-btn <?php echo $specFilter ? 'active-filter' : ''; ?>" id="specBtn" onclick="toggleDropdown('specPanel', 'specBtn')">
              <i class="fas fa-microchip icon-left"></i>
              Specs
              <?php if ($specFilter): ?><span class="filter-badge-count">1</span><?php endif; ?>
              <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div class="filter-dropdown-panel" id="specPanel">
              <div class="dropdown-option-list">
                <label class="dropdown-option">
                  <input type="radio" name="spec" value="" <?php echo !$specFilter ? 'checked' : ''; ?> onchange="this.form.submit()">
                  <span class="custom-checkbox"></span>
                  <span class="dropdown-option-label">All Specs</span>
                </label>
                <?php
                $specOptions = [
                    'PoE' => 'PoE (Power over Ethernet)',
                    'WiFi 6' => 'WiFi 6 / 802.11ax',
                    'WiFi 5' => 'WiFi 5 / 802.11ac',
                    '10GbE' => '10 Gigabit Ethernet',
                    '2.5GbE' => '2.5 Gigabit Ethernet',
                    '1GbE' => '1 Gigabit Ethernet',
                    '10G' => '10G SFP+',
                    '24-port' => '24 Ports',
                    '48-port' => '48 Ports',
                    '8-port' => '8 Ports',
                    'Managed' => 'Managed Switch',
                    'Unmanaged' => 'Unmanaged Switch',
                    'Layer 3' => 'Layer 3 Routing',
                    'Layer 2' => 'Layer 2 Switching',
                    'UbiOS' => 'Ubiquiti UniFi',
                    'RouterOS' => 'MikroTik RouterOS',
                    'FortiOS' => 'Fortinet FortiOS',
                    'CAPWAP' => 'CAPWAP / Cloud Managed',
                    '802.1X' => '802.1X Authentication',
                    'VPN' => 'VPN Support',
                ];
                foreach ($specOptions as $val => $label): ?>
                <label class="dropdown-option">
                  <input type="radio" name="spec" value="<?php echo $val; ?>" <?php echo $specFilter === $val ? 'checked' : ''; ?> onchange="this.form.submit()">
                  <span class="custom-checkbox"></span>
                  <span class="dropdown-option-label"><?php echo $label; ?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <div style="padding:10px 14px;border-top:1px solid var(--border);">
                <input type="text" name="spec" value="<?php echo htmlspecialchars($specFilter); ?>" placeholder="Or type custom spec..." style="width:100%;padding:8px 12px;border:1.5px solid var(--border);border-radius:6px;font-size:12px;font-family:'Inter',sans-serif;" onkeypress="if(event.key==='Enter'){this.form.submit();}">
              </div>
            </div>
          </div>

          <div class="filter-dropdown-wrap" id="priceWrap">
            <button type="button" class="filter-dropdown-btn <?php echo ($minPrice > 0 || $maxPrice < 999999999) ? 'active-filter' : ''; ?>" id="priceBtn" onclick="toggleDropdown('pricePanel', 'priceBtn')">
              <i class="fas fa-coins icon-left"></i>
              Price Range
              <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div class="filter-dropdown-panel price-dropdown-panel" id="pricePanel">
              <div class="pdp-title">Price Range (TSh)</div>
              <div class="price-range-inputs">
                <input type="number" name="min_price" placeholder="Min" value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>">
                <input type="number" name="max_price" placeholder="Max" value="<?php echo $maxPrice < 999999999 ? $maxPrice : ''; ?>">
              </div>
              <button type="submit" class="price-apply-btn">Apply Price Filter</button>
            </div>
          </div>

          <div class="hfb-avail">
            <span class="hfb-avail-label">In Stock Only</span>
            <label class="toggle-switch">
              <input type="checkbox" name="in_stock" value="1" <?php echo $stockFilter === '1' ? 'checked' : ''; ?> onchange="this.form.submit()">
              <span class="toggle-track"></span>
              <span class="toggle-thumb"></span>
            </label>
          </div>

        </div>

        <div class="hfb-divider"></div>

        <div class="hfb-sort-group">
          <span class="hfb-sort-label">Sort:</span>
          <select name="sort" class="sort-select" onchange="this.form.submit()">
            <option value="best" <?php echo $sort === 'best' ? 'selected' : ''; ?>>Best Selling</option>
            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="price-low" <?php echo $sort === 'price-low' ? 'selected' : ''; ?>>Price: Low → High</option>
            <option value="price-high" <?php echo $sort === 'price-high' ? 'selected' : ''; ?>>Price: High → Low</option>
            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A–Z</option>
          </select>
        </div>

        <div class="hfb-divider"></div>

        <div class="hfb-right">
          <span class="hfb-results"><strong><?php echo $total; ?></strong> products</span>
          <div class="view-toggle">
            <button type="button" class="view-btn active" id="gridView" title="Grid view"><i class="fas fa-th"></i></button>
            <button type="button" class="view-btn" id="listView" title="List view"><i class="fas fa-list"></i></button>
          </div>
        </div>
      </div>
    </form>

    <?php if ($hasActiveFilters || $search): ?>
    <div class="active-chips-row" id="chipsRow">
      <span class="chips-label">Active Filters:</span>
      <?php foreach ($activeChips as $chip): ?>
      <span class="chip" data-param="<?php echo $chip['param']; ?>"><?php echo $chip['label']; ?> <i class="fas fa-times chip-remove"></i></span>
      <?php endforeach; ?>
      <?php if ($minPrice > 0 || $maxPrice < 999999999): ?>
      <span class="chip" data-param="price">Price: <?php echo $minPrice > 0 ? 'TSh '.number_format($minPrice, 0, '.', ',') : '0'; ?> – <?php echo $maxPrice < 999999999 ? 'TSh '.number_format($maxPrice, 0, '.', ',') : '∞'; ?> <i class="fas fa-times chip-remove"></i></span>
      <?php endif; ?>
      <a href="?" class="clear-all-link"><i class="fas fa-times"></i> Clear All</a>
    </div>
    <?php endif; ?>

    <div class="products-grid" id="productsGrid">
      <?php if (empty($products)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted);">
        <i class="fas fa-box-open" style="font-size:48px;display:block;margin-bottom:16px;"></i>
        <h3 style="font-size:18px;color:var(--navy);margin-bottom:6px;">No products found</h3>
        <p>Try adjusting your filters or search terms</p>
        <a href="?" style="display:inline-flex;margin-top:16px;background:var(--orange);color:#fff;padding:8px 16px;border-radius:7px;text-decoration:none;font-size:13px;font-weight:600;">Clear All Filters</a>
      </div>
      <?php else: ?>
      <?php foreach ($products as $p):
          $tags = $p['tags'] ? explode(',', $p['tags']) : [];
      ?>
      <div class="product-card">
        <a href="product.php?id=<?php echo $p['id']; ?>" class="product-image-wrap">
          <?php echo imageOrPlaceholder($p['image'], $p['name'], $p['brand'] ?? ''); ?>
          <div class="product-badges">
            <span class="badge badge-brand"><i class="fas fa-shield-check"></i> <?php echo htmlspecialchars($p['brand'] ?: 'Generic'); ?></span>
            <?php if ($p['discount_percentage']): ?>
            <span class="badge badge-sale">-<?php echo $p['discount_percentage']; ?>%</span>
            <?php endif; ?>
          </div>
          <button class="product-wishlist"><i class="far fa-heart"></i></button>
        </a>
        <div class="product-body">
          <div class="product-sku">SKU: <?php echo htmlspecialchars($p['sku']); ?></div>
          <a href="product.php?id=<?php echo $p['id']; ?>" class="product-name" style="text-decoration:none;color:inherit;"><?php echo htmlspecialchars($p['name']); ?></a>
          <div class="product-brand-row">
            <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
            <span class="badge badge-brand" style="background:rgba(10,37,64,0.06);color:var(--navy);"><?php echo htmlspecialchars(trim($tag)); ?></span>
            <?php endforeach; ?>
            <?php echo getStockBadge($p['stock_status']); ?>
          </div>
          <?php if ($p['specs']): ?>
          <div class="product-spec"><i class="fas fa-microchip"></i> <?php echo htmlspecialchars($p['specs']); ?></div>
          <?php endif; ?>
          <div class="product-price-block">
            <div class="product-price-label">Unit Price (excl. VAT)</div>
            <div class="product-price">
              <span class="currency">TSh</span><?php echo number_format((float)$p['price'], 0, '.', ','); ?>
              <?php if ($p['old_price']): ?>
              <span class="product-price-old">TSh <?php echo number_format((float)$p['old_price'], 0, '.', ','); ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="product-moq"><i class="fas fa-box"></i> Min Order: <?php echo $p['moq']; ?> unit<?php echo $p['moq'] > 1 ? 's' : ''; ?></div>
          <div class="product-stock-left" style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;margin-bottom:10px;padding:6px 10px;border-radius:7px;<?php echo $p['stock_status'] === 'out_of_stock' ? 'background:rgba(220,38,38,0.08);color:#dc2626;' : ($p['stock_status'] === 'low_stock' ? 'background:rgba(217,119,6,0.08);color:#d97706;' : 'background:rgba(5,150,105,0.08);color:#059669;'); ?>">
            <i class="fas <?php echo $p['stock_status'] === 'out_of_stock' ? 'fa-times-circle' : ($p['stock_status'] === 'low_stock' ? 'fa-exclamation-triangle' : 'fa-check-circle'); ?>"></i>
            <?php if ($p['stock_status'] === 'out_of_stock'): ?>
              Out of Stock
            <?php elseif ($p['stock_status'] === 'low_stock'): ?>
              Only <?php echo $p['stock_qty']; ?> left — order soon!
            <?php else: ?>
              <?php echo $p['stock_qty']; ?> units in stock
            <?php endif; ?>
          </div>
          <div class="product-actions">
            <a href="product.php?id=<?php echo $p['id']; ?>" class="btn-cart" style="text-decoration:none;text-align:center;"><i class="fas fa-eye"></i> View Details</a>
            <?php $inCart = in_array($p['id'], $cartProductIds); ?>
            <form method="POST" action="cart.php" style="flex:1;display:flex;" class="add-to-cart-form">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
              <input type="hidden" name="qty" value="1">
              <button type="submit" class="btn-cart <?php echo $inCart ? 'btn-in-cart' : ''; ?>"><i class="fas <?php echo $inCart ? 'fa-check' : 'fa-shopping-cart'; ?>"></i> <?php echo $inCart ? 'Added to Cart' : 'Add to Cart'; ?></button>
            </form>
            <form method="POST" action="cart.php" style="display:flex;">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
              <input type="hidden" name="qty" value="1">
              <button type="submit" class="btn-quote"><i class="fas fa-file-alt"></i> Quote</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination-bar">
      <div class="pagination-info">
        Showing <strong><?php echo $total ? (($page-1)*$perPage+1) . '–' . min($page*$perPage, $total) : '0'; ?></strong> of <strong><?php echo $total; ?></strong> products
      </div>
      <div class="pagination-controls">
        <?php if ($page > 1):
            $queryParams = $_GET; $queryParams['page'] = $page - 1;
        ?>
        <a href="?<?php echo http_build_query($queryParams); ?>" class="page-btn nav-arrow"><i class="fas fa-chevron-left"></i></a>
        <?php else: ?>
        <button class="page-btn nav-arrow" disabled><i class="fas fa-chevron-left"></i></button>
        <?php endif; ?>

        <?php
        $queryParams = $_GET;
        $startPage = max(1, min($page - 2, $totalPages - 4));
        $endPage = min($totalPages, $startPage + 4);
        if ($startPage > 1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="page-btn">1</a>
        <?php if ($startPage > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++):
            $queryParams['page'] = $i;
            $url = '?' . http_build_query($queryParams);
        ?>
        <a href="<?php echo $url; ?>" class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($endPage < $totalPages):
            if ($endPage < $totalPages - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" class="page-btn"><?php echo $totalPages; ?></a>
        <?php endif; ?>

        <?php if ($page < $totalPages):
            $queryParams = $_GET; $queryParams['page'] = $page + 1;
        ?>
        <a href="?<?php echo http_build_query($queryParams); ?>" class="page-btn nav-arrow"><i class="fas fa-chevron-right"></i></a>
        <?php else: ?>
        <button class="page-btn nav-arrow" disabled><i class="fas fa-chevron-right"></i></button>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <footer>
    <div class="footer-inner">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo"><div class="icon"><i class="fas fa-network-wired"></i></div><span class="name">BN-Infrastructure</span></div>
          <p>Tanzania's leading B2B network infrastructure supplier. Empowering businesses with enterprise-grade connectivity solutions since 2012.</p>
          <div class="footer-socials"><a href="https://linkedin.com" target="_blank" class="footer-social-btn" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a><a href="https://twitter.com" target="_blank" class="footer-social-btn" aria-label="Twitter"><i class="fab fa-twitter"></i></a><a href="https://facebook.com" target="_blank" class="footer-social-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><a href="https://wa.me/255763364721" target="_blank" class="footer-social-btn" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a></div>
        </div>
        <div class="footer-col"><h4>Quick Links</h4><ul><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> All Products</a></li><li><a href="/New%20Arrivals/"><i class="fas fa-chevron-right"></i> New Arrivals</a></li><li><a href="/Best%20Sellers/"><i class="fas fa-chevron-right"></i> Best Sellers</a></li><li><a href="/Special%20Offers/"><i class="fas fa-chevron-right"></i> Special Offers</a></li><li><a href="/Request%20a%20Quote/"><i class="fas fa-chevron-right"></i> Request a Quote</a></li><li><a href="/Bulk%20Orders/"><i class="fas fa-chevron-right"></i> Bulk Orders</a></li></ul></div>
        <div class="footer-col"><h4>Company</h4><ul><li><a href="about.php"><i class="fas fa-chevron-right"></i> About BN-Infrastructure</a></li><li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Our Brands</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Solutions</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Blog &amp; Resources</a></li><li><a href="about.php"><i class="fas fa-chevron-right"></i> Careers</a></li><li><a href="privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li></ul></div>
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
    function toggleMenu(){document.getElementById('mobileNav').classList.toggle('open');document.getElementById('navOverlay').classList.toggle('open')}

    function toggleDropdown(panelId, btnId) {
      const panel = document.getElementById(panelId);
      const btn = document.getElementById(btnId);
      const isOpen = panel.classList.contains('open');
      document.querySelectorAll('.filter-dropdown-panel').forEach(p => p.classList.remove('open'));
      document.querySelectorAll('.filter-dropdown-btn').forEach(b => b.classList.remove('open'));
      if (!isOpen) {
        panel.classList.add('open');
        btn.classList.add('open');
      }
    }

    document.addEventListener('click', function(e) {
      const isInsideDropdown = e.target.closest('.filter-dropdown-wrap');
      if (!isInsideDropdown) {
        document.querySelectorAll('.filter-dropdown-panel').forEach(p => p.classList.remove('open'));
        document.querySelectorAll('.filter-dropdown-btn').forEach(b => b.classList.remove('open'));
      }
    });

    document.querySelectorAll('.product-actions form').forEach(function(f){
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

    document.getElementById('gridView').addEventListener('click', function() {
      this.classList.add('active');
      document.getElementById('listView').classList.remove('active');
      document.getElementById('productsGrid').style.gridTemplateColumns = 'repeat(4, 1fr)';
    });
    document.getElementById('listView').addEventListener('click', function() {
      this.classList.add('active');
      document.getElementById('gridView').classList.remove('active');
      document.getElementById('productsGrid').style.gridTemplateColumns = '1fr';
    });

    document.querySelectorAll('.product-wishlist').forEach(btn => {
      btn.addEventListener('click', function() {
        const icon = this.querySelector('i');
        if (icon.classList.contains('far')) {
          icon.classList.replace('far', 'fas');
          this.style.color = '#F05A22';
          this.style.opacity = '1';
        } else {
          icon.classList.replace('fas', 'far');
          this.style.color = '';
          this.style.opacity = '';
        }
      });
    });
  </script>

<script>
<?php echo userMenuJs(); ?>
<?php echo scrollRevealJs(); ?>
</script>
</body></html>
