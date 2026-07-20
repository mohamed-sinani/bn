<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/cart.php';

$featuredProducts = fetchAll(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.featured = 1 
     ORDER BY p.created_at DESC 
     LIMIT 4"
);

$categories = fetchAll("SELECT * FROM categories ORDER BY name");
$totalProducts = fetchOne("SELECT COUNT(*) as count FROM products")['count'];
$itemCount = cartCount();
$cartProductIds = array_keys(cartGetItems());
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BN-Infrastructure — Network Infrastructure Supplier</title>
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
    .nav-links a.active { color: #fff; }
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
    .cart-btn { background: var(--orange); border: none; color: #fff; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; transition: background 0.2s, transform 0.2s; font-size: 15px; text-decoration: none; }
    .cart-btn:hover { background: var(--orange-dark); transform: translateY(-1px); }
    .cart-badge { position: absolute; top: -5px; right: -5px; background: #fff; color: var(--orange); font-size: 10px; font-weight: 700; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1.5px solid var(--orange); transition: transform 0.2s; }
    .hero { position: relative; overflow: hidden; min-height: 520px; background: #0A2540; }
    .carousel-slide { position: absolute; inset: 0; opacity: 0; transition: opacity 1s ease-in-out; pointer-events: none; }
    .carousel-slide.active { opacity: 1; pointer-events: auto; }
    .slide-routers { background: radial-gradient(ellipse at 70% 50%, #1a4a9a 0%, #0d3060 40%, #0A2540 75%); }
    .slide-switches { background: radial-gradient(ellipse at 70% 50%, #1a2a60 0%, #071835 50%, #0A2540 80%); }
    .slide-access-points { background: radial-gradient(ellipse at 70% 50%, #0d2a6e 0%, #081c38 50%, #0A2540 80%); }
    .slide-cabling { background: radial-gradient(ellipse at 70% 50%, #0f2b5c 0%, #0a1e35 50%, #0A2540 80%); }
    .slide-firewalls { background: radial-gradient(ellipse at 70% 50%, #0f2545 0%, #0d1f3c 50%, #0A2540 80%); }
    .carousel-slide::before { content: ''; position: absolute; inset: 0; background: linear-gradient(to right, #0A2540 0%, #0A2540 25%, rgba(10,37,64,0.7) 45%, rgba(10,37,64,0.3) 60%, transparent 75%); z-index: 2; pointer-events: none; }
    .slide-img-wrap { position: absolute; right: 0; top: 0; height: 100%; width: 60%; display: flex; align-items: center; justify-content: center; z-index: 1; overflow: hidden; }
    .slide-img-wrap img { width: 85%; max-width: 480px; aspect-ratio: 1 / 1; border-radius: 50%; object-fit: cover; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 4px solid rgba(255,255,255,0.05); filter: brightness(0.85) saturate(1.1); transition: transform 8s ease; }
    .carousel-slide.active .slide-img-wrap img { transform: scale(1.05); }
    .slide-product-tag { position: absolute; top: 40px; right: 48px; background: var(--orange); color: #fff; font-size: 13px; font-weight: 700; padding: 7px 16px; border-radius: 20px; letter-spacing: 0.03em; z-index: 10; box-shadow: 0 4px 16px rgba(240,90,34,0.4); display: flex; align-items: center; gap: 7px; }
    .hero-text-content { position: relative; z-index: 10; padding: 80px 48px; max-width: 640px; }
    .hero-badge { display: inline-flex; align-items: center; gap: 7px; background: rgba(240,90,34,0.15); border: 1px solid rgba(240,90,34,0.3); color: #ff8555; font-size: 12px; font-weight: 600; padding: 5px 12px; border-radius: 20px; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 22px; }
    .hero h1 { font-size: 56px; font-weight: 800; color: #fff; line-height: 1.08; letter-spacing: -0.03em; margin-bottom: 18px; }
    .hero h1 span { color: var(--orange); }
    .hero p { font-size: 17px; color: rgba(255,255,255,0.65); line-height: 1.65; margin-bottom: 36px; max-width: 520px; font-weight: 400; }
    .hero-ctas { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
    .btn-primary { background: var(--orange); color: #fff; border: none; padding: 13px 28px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
    .btn-primary:hover { background: var(--orange-dark); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(240,90,34,0.35); }
    .btn-outline-white { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,0.35); padding: 11px 26px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
    .btn-outline-white:hover { border-color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.08); transform: translateY(-2px); }
    .hero-stats { display: flex; align-items: center; gap: 0; margin-top: 44px; padding-top: 36px; border-top: 1px solid rgba(255,255,255,0.1); }
    .hero-stat { text-align: left; padding: 0 28px 0 0; }
    .hero-stat:not(:last-child) { padding-right: 28px; margin-right: 28px; border-right: 1px solid rgba(255,255,255,0.15); }
    .hero-stat .number { font-size: 26px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
    .hero-stat .label { font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 400; margin-top: 1px; }
    .carousel-arrow { position: absolute; top: 50%; transform: translateY(-50%); width: 44px; height: 44px; border-radius: 50%; background: rgba(0,0,0,0.45); border: 1.5px solid rgba(255,255,255,0.2); color: #fff; font-size: 14px; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 20; transition: background 0.2s, border-color 0.2s, transform 0.2s; backdrop-filter: blur(4px); }
    .carousel-arrow:hover { background: rgba(240,90,34,0.8); border-color: rgba(240,90,34,0.9); transform: translateY(-50%) scale(1.08); }
    .carousel-arrow.prev { left: 16px; }
    .carousel-arrow.next { right: 16px; }
    .carousel-counter { position: absolute; top: 20px; right: 20px; background: rgba(0,0,0,0.5); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 11px; font-weight: 700; padding: 5px 11px; border-radius: 20px; letter-spacing: 0.04em; z-index: 20; }
    .carousel-dots { position: absolute; bottom: 22px; left: 50%; transform: translateX(-50%); display: flex; gap: 7px; z-index: 20; }
    .carousel-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.35); cursor: pointer; transition: background 0.3s, width 0.3s, border-radius 0.3s; border: none; padding: 0; }
    .carousel-dot.active { background: #fff; width: 24px; border-radius: 4px; }
    .carousel-dot:hover { background: rgba(255,255,255,0.65); }
    .carousel-progress { position: absolute; bottom: 0; left: 0; height: 3px; background: var(--orange); border-radius: 0 2px 2px 0; z-index: 20; width: 0%; transition: width linear; }
    .genuine-badge { position: absolute; bottom: 28px; left: 48px; background: #fff; color: var(--navy); padding: 12px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.25); z-index: 20; white-space: nowrap; }
    .genuine-badge i { color: #059669; font-size: 16px; }
    .section { padding: 60px 48px; }
    .section-inner { max-width: 1632px; margin: 0 auto; }
    .section-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 32px; }
    .section-header h2 { font-size: 26px; font-weight: 800; color: var(--navy); letter-spacing: -0.02em; }
    .section-header p { font-size: 14px; color: var(--text-secondary); margin-top: 4px; }
    .view-all-link { color: var(--orange); font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: gap 0.2s; }
    .view-all-link:hover { gap: 8px; }
    .categories-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    .category-card { background: var(--card); border-radius: 14px; padding: 28px 24px; display: flex; flex-direction: column; align-items: flex-start; gap: 14px; text-decoration: none; box-shadow: var(--shadow-sm); border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; cursor: pointer; position: relative; overflow: hidden; }
    .category-card::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: var(--orange); transform: scaleX(0); transition: transform 0.25s ease; transform-origin: left; }
    .category-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: rgba(240,90,34,0.2); }
    .category-card:hover::after { transform: scaleX(1); }
    .category-icon { width: 52px; height: 52px; background: rgba(10,37,64,0.06); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--navy); transition: background 0.2s, color 0.2s; }
    .category-card:hover .category-icon { background: rgba(240,90,34,0.1); color: var(--orange); }
    .category-content { flex: 1; }
    .category-content h3 { font-size: 16px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
    .category-content p { font-size: 13px; color: var(--text-secondary); }
    .category-footer { display: flex; align-items: center; justify-content: space-between; width: 100%; }
    .category-count { font-size: 12px; font-weight: 600; color: var(--text-muted); background: var(--bg); padding: 3px 8px; border-radius: 20px; }
    .products-section { background: var(--bg); }
    .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    .product-card { background: var(--card); border-radius: 14px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
    .product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
    .product-image-wrap { position: relative; background: #f8fafc; overflow: hidden; }
    .product-image-wrap img { width: 100%; height: 200px; object-fit: cover; transition: transform 0.3s; }
    .product-card:hover .product-image-wrap img { transform: scale(1.04); }
    .product-badges { position: absolute; top: 12px; left: 12px; display: flex; flex-direction: column; gap: 5px; }
    .badge { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.04em; text-transform: uppercase; display: inline-flex; align-items: center; gap: 4px; }
    .badge-brand { background: var(--navy); color: #fff; }
    .badge-sale { background: var(--orange); color: #fff; }
    .badge-new { background: #059669; color: #fff; }
    .badge-stock { background: rgba(5,150,105,0.12); color: #059669; border: 1px solid rgba(5,150,105,0.3); }
    .badge-low { background: rgba(245,158,11,0.12); color: #d97706; border: 1px solid rgba(245,158,11,0.3); }
    .badge-out { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.25); }
    .product-wishlist { position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; background: rgba(255,255,255,0.9); border: none; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-muted); font-size: 13px; transition: all 0.2s; opacity: 0; }
    .product-card:hover .product-wishlist { opacity: 1; }
    .product-wishlist:hover { color: var(--orange); background: #fff; }
    .product-body { padding: 18px 18px 14px; flex: 1; display: flex; flex-direction: column; }
    .product-sku { font-size: 11px; color: var(--text-muted); font-weight: 500; letter-spacing: 0.04em; margin-bottom: 5px; }
    .product-name { font-size: 14px; font-weight: 700; color: var(--navy); line-height: 1.35; margin-bottom: 10px; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .product-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
    .product-price-block { margin-bottom: 14px; }
    .product-price-label { font-size: 11px; color: var(--text-muted); font-weight: 500; margin-bottom: 2px; }
    .product-price { font-size: 20px; font-weight: 800; color: var(--navy); letter-spacing: -0.02em; }
    .product-price .currency { font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-right: 2px; }
    .product-actions { display: flex; gap: 8px; }
    .btn-cart { flex: 1; background: var(--navy); color: #fff; border: none; padding: 9px 14px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; }
    .btn-cart:hover { background: var(--orange); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(240,90,34,0.3); }
    .btn-cart.btn-in-cart { background: #059669; pointer-events: none; }
    .btn-quote { background: transparent; color: var(--navy); border: 1.5px solid var(--border); padding: 9px 14px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; white-space: nowrap; text-decoration: none; }
    .btn-quote:hover { border-color: var(--orange); color: var(--orange); transform: translateY(-1px); }
    .brands-strip { background: var(--card); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 30px 48px; }
    .brands-strip-inner { max-width: 1632px; margin: 0 auto; display: flex; align-items: center; }
    .brands-strip-label { font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; margin-right: 40px; padding-right: 40px; border-right: 1px solid var(--border); }
    .brands-list { display: flex; align-items: center; gap: 40px; flex: 1; }
    .brand-logo { font-size: 15px; font-weight: 800; color: var(--text-muted); letter-spacing: -0.02em; transition: color 0.2s; cursor: default; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
    .brand-logo:hover { color: var(--navy); }
    .brand-logo i { font-size: 18px; }
    .trust-bar { background: var(--navy); padding: 50px 48px; }
    .trust-bar-inner { max-width: 1632px; margin: 0 auto; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
    .trust-item { display: flex; align-items: flex-start; gap: 16px; padding: 24px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; transition: background 0.2s; }
    .trust-item:hover { background: rgba(255,255,255,0.08); }
    .trust-icon { width: 48px; height: 48px; background: rgba(240,90,34,0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--orange); flex-shrink: 0; }
    .trust-text h4 { font-size: 15px; font-weight: 700; color: #fff; margin-bottom: 4px; }
    .trust-text p { font-size: 13px; color: rgba(255,255,255,0.55); line-height: 1.5; }
    .promo-section { padding: 0 48px 60px; }
    .promo-section-inner { max-width: 1632px; margin: 0 auto; }
    .promo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .promo-card { border-radius: 14px; overflow: hidden; position: relative; height: 180px; display: flex; align-items: center; padding: 32px; cursor: pointer; transition: transform 0.2s; }
    .promo-card:hover { transform: translateY(-2px); }
    .promo-card-1 { background: linear-gradient(135deg, #0A2540 0%, #1a4070 100%); }
    .promo-card-2 { background: linear-gradient(135deg, #F05A22 0%, #d44d1a 100%); }
    .promo-card-content h3 { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 6px; letter-spacing: -0.02em; }
    .promo-card-content p { font-size: 14px; color: rgba(255,255,255,0.7); margin-bottom: 16px; }
    .promo-card-btn { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.15); color: #fff; border: 1.5px solid rgba(255,255,255,0.3); padding: 8px 16px; border-radius: 7px; font-size: 13px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
    .promo-card-btn:hover { background: rgba(255,255,255,0.25); }
    .promo-card-decor { position: absolute; right: -20px; bottom: -20px; font-size: 120px; opacity: 0.08; color: #fff; }
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
    .user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}.reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
    .section-divider { width: 100%; height: 1px; background: var(--border); }
    @media (max-width: 1200px) { .navbar, .hero-text-content, .section, .brands-strip, .trust-bar, .promo-section, footer { padding-left: 32px; padding-right: 32px; } .products-grid, .categories-grid { grid-template-columns: repeat(2, 1fr); } .footer-grid { grid-template-columns: 1fr 1fr; } .slide-img-wrap { width: 55%; } }
    @media (max-width: 768px) { .navbar { padding: 0 20px; height: 60px; } .nav-links { display: none; } .slide-img-wrap { display: none; } .carousel-slide::before { background: linear-gradient(to right, #0A2540 0%, rgba(10,37,64,0.9) 100%); } .categories-grid, .products-grid, .trust-bar-inner, .promo-grid { grid-template-columns: 1fr; } .footer-grid { grid-template-columns: 1fr; gap: 32px; } .footer-bottom { flex-direction: column; gap: 12px; text-align: center; } .search-bar { width: 160px; } .hero-text-content { padding: 60px 20px; } }
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
      <a href="catalog.php">Products</a>
      <a href="about.php">Solutions</a>
      <a href="track.php">Track Order</a>
      <a href="login.php">Account</a>
    </div>
    <div class="nav-actions">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search products, brands, SKU\u2026">
      </div>
      <?php echo userNavHtml(); ?>
      <a href="cart.php" class="cart-btn"><i class="fas fa-shopping-cart"></i><span class="cart-badge"><?php echo $itemCount; ?></span></a>
      <button class="hamburger" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
    </div>
  </nav>
  <div class="nav-overlay" id="navOverlay" onclick="toggleMenu()"></div>
  <div class="mobile-nav" id="mobileNav">
    <button class="close-btn" onclick="toggleMenu()"><i class="fas fa-times"></i></button>
    <a href="catalog.php" onclick="toggleMenu()">Products</a>
    <a href="about.php" onclick="toggleMenu()">Solutions</a>
    <a href="track.php" onclick="toggleMenu()">Track Order</a>
    <?php echo mobileAccountHtml(); ?>
    <a href="cart.php" onclick="toggleMenu()">Cart</a>
  </div>

  <section class="hero" id="heroSection">
    <div class="carousel-slide slide-routers active">
      <div class="slide-img-wrap">
        <img src="Homepage/mikrotik_ccr2004_router.jpg" alt="MikroTik Enterprise Router">
      </div>
    </div>
    <div class="carousel-slide slide-switches">
      <div class="slide-img-wrap">
        <img src="Homepage/cisco_catalyst_2960x_switch.jpg" alt="Cisco Catalyst Switch">
      </div>
    </div>
    <div class="carousel-slide slide-access-points">
      <div class="slide-img-wrap">
        <img src="Homepage/ubiquiti_unifi_ap_ax_access_point.jpg" alt="Ubiquiti WiFi 6 Access Point">
      </div>
    </div>
    <div class="carousel-slide slide-cabling">
      <div class="slide-img-wrap">
        <img src="Homepage/panduit_cat6_structured_cabling_kit.jpg" alt="Structured Cabling Kit">
      </div>
    </div>
    <div class="carousel-slide slide-firewalls">
      <div class="slide-img-wrap">
        <img src="Homepage/fortinet_fortigate_60f.jpg" alt="Fortinet Next-Gen Firewall">
      </div>
    </div>
    <!-- Slide product tags -->
    <div class="slide-product-tag"><i class="fas fa-microchip"></i> Enterprise Grade</div>
    <div class="slide-product-tag" style="display:none"><i class="fas fa-microchip"></i> Managed Switches</div>
    <div class="slide-product-tag" style="display:none"><i class="fas fa-wifi"></i> WiFi 6/6E</div>
    <div class="slide-product-tag" style="display:none"><i class="fas fa-plug"></i> CAT6A/7</div>
    <div class="slide-product-tag" style="display:none"><i class="fas fa-shield-halved"></i> UTM Protection</div>

    <!-- Hero text content (visible on all slides) -->
    <div class="hero-text-content">
      <div class="hero-badge"><i class="fas fa-certificate"></i> <span id="heroBadgeText">Premium Networking Equipment</span></div>
      <h1>Your <span>Network</span> Infrastructure Partner in <span>Tanzania</span></h1>
      <p id="heroDesc">From enterprise routers to structured cabling — BN-Infrastructure supplies, configures, and supports the full stack of networking hardware across Dar es Salaam and all regions.</p>
      <div class="hero-ctas">
        <a href="catalog.php" class="btn-primary"><i class="fas fa-shopping-bag"></i> Browse Products</a>
        <a href="about.php" class="btn-outline-white"><i class="fas fa-phone"></i> Request Quote</a>
      </div>
      <?php $totalProducts = isset($totalProducts) ? $totalProducts : 0; ?>
      <div class="hero-stats">
        <div class="hero-stat"><div class="number"><?php echo number_format($totalProducts); ?>+</div><div class="label">Products in Stock</div></div>
        <div class="hero-stat"><div class="number">150+</div><div class="label">Enterprise Clients</div></div>
        <div class="hero-stat"><div class="number">5</div><div class="label">Cities Served</div></div>
      </div>
    </div>

    <!-- Carousel Controls -->
    <span class="carousel-counter"><span id="currentSlide">01</span> / <span id="totalSlides">05</span></span>
    <button class="carousel-arrow prev" onclick="prevSlide()"><i class="fas fa-chevron-left"></i></button>
    <button class="carousel-arrow next" onclick="nextSlide()"><i class="fas fa-chevron-right"></i></button>
    <div class="carousel-dots" id="carouselDots"></div>
    <div class="carousel-progress" id="carouselProgress"></div>
    <div class="genuine-badge"><i class="fas fa-shield-check"></i> 100% Genuine Products</div>
  </section>

  <!-- Categories -->
  <section class="section">
    <div class="section-inner">
      <div class="section-header">
        <div>
          <h2>Shop by Category</h2>
          <p>Browse our curated categories of networking hardware</p>
        </div>
        <a href="catalog.php" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="categories-grid">
        <?php if (!empty($categories)): $i = 0; ?>
          <?php $catIcons = ['fas fa-router','fas fa-server','fas fa-wifi','fas fa-plug','fas fa-shield-halved','fas fa-tower-broadcast','fas fa-database','fas fa-tools']; ?>
          <?php foreach ($categories as $cat): ?>
            <?php $iconIdx = $i % count($catIcons); $i++; ?>
            <a href="catalog.php?category=<?php echo urlencode($cat['id']); ?>" class="category-card">
              <div class="category-icon"><i class="<?php echo $catIcons[$iconIdx]; ?>"></i></div>
              <div class="category-content">
                <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                <p><?php echo htmlspecialchars(substr($cat['description'] ?? 'Browse our range of ' . $cat['name'], 0, 80)); ?></p>
              </div>
              <div class="category-footer">
                <span class="category-count"><?php echo $cat['product_count'] ?? '0'; ?> items</span>
                <i class="fas fa-arrow-right" style="color:var(--orange);font-size:12px"></i>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No categories found.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <div class="section-divider"></div>

  <!-- Featured Products -->
  <section class="section products-section">
    <div class="section-inner">
      <div class="section-header">
        <div>
          <h2>Featured Products</h2>
          <p>Handpicked networking essentials for your infrastructure</p>
        </div>
        <a href="catalog.php" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="products-grid">
        <?php if (!empty($featuredProducts)): ?>
          <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
              <div class="product-image-wrap">
                <?php echo imageOrPlaceholder($product['image'] ?? '', $product['name'], $product['brand'] ?? ''); ?>
                <div class="product-badges">
                  <?php if (!empty($product['brand'])): ?><span class="badge badge-brand"><?php echo htmlspecialchars($product['brand']); ?></span><?php endif; ?>
                  <?php if (!empty($product['is_new'])): ?><span class="badge badge-new"><i class="fas fa-star"></i> New</span><?php endif; ?>
                  <?php if (isset($product['stock']) && $product['stock'] < 1): ?><span class="badge badge-out">Out of Stock</span><?php elseif (isset($product['stock']) && $product['stock'] < 11): ?><span class="badge badge-low">Only <?php echo $product['stock']; ?> left</span><?php endif; ?>
                </div>
                <button class="product-wishlist" title="Add to Wishlist"><i class="far fa-heart"></i></button>
              </div>
              <div class="product-body">
                <div class="product-sku"><?php echo htmlspecialchars($product['sku'] ?? 'NET-' . str_pad($product['id'], 4, '0', STR_PAD_LEFT)); ?></div>
                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                <div class="product-meta">
                  <?php if (!empty($product['category_name'])): ?><span class="badge badge-brand"><?php echo htmlspecialchars($product['category_name']); ?></span><?php endif; ?>
                  <?php if (!empty($product['specs'])): ?><span class="badge badge-stock"><i class="fas fa-cog"></i> <?php echo htmlspecialchars($product['specs']); ?></span><?php endif; ?>
                </div>
                <div class="product-price-block">
                  <?php if (!empty($product['price'])): ?>
                    <div class="product-price-label">Price</div>
                    <div class="product-price"><span class="currency">TSh</span> <?php echo formatTsh($product['price']); ?></div>
                  <?php else: ?>
                    <div class="product-price-label">Price</div>
                    <div class="product-price" style="font-size:14px;color:var(--orange);font-weight:600;">Request Quote</div>
                  <?php endif; ?>
                </div>
                <div class="product-actions">
                  <?php $inCart = in_array($product['id'], $cartProductIds); ?>
                  <form method="POST" action="cart.php" style="display:contents" class="add-to-cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="qty" value="1">
                    <button type="submit" class="btn-cart <?php echo $inCart ? 'btn-in-cart' : ''; ?>" data-product-id="<?php echo $product['id']; ?>"><i class="fas <?php echo $inCart ? 'fa-check' : 'fa-shopping-cart'; ?>"></i> <?php echo $inCart ? 'Added to Cart' : 'Add to Cart'; ?></button>
                  </form>
                  <form action="cart.php" method="POST" style="display:contents">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="qty" value="1">
                    <button type="submit" name="add_to_quote" class="btn-quote"><i class="fas fa-file-invoice"></i></button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="grid-column:1/-1;text-align:center;color:var(--text-secondary);padding:40px;font-size:15px;">No featured products at the moment. Check back soon!</p>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <!-- Brands Strip -->
  <div class="brands-strip">
    <div class="brands-strip-inner">
      <div class="brands-strip-label"><i class="fas fa-check-circle" style="color:var(--orange);margin-right:6px"></i> Trusted Brands</div>
      <div class="brands-list">
        <span class="brand-logo"><i class="fas fa-network-wired"></i> Cisco</span>
        <span class="brand-logo"><i class="fas fa-microchip"></i> MikroTik</span>
        <span class="brand-logo"><i class="fas fa-tower-broadcast"></i> Ubiquiti</span>
        <span class="brand-logo"><i class="fas fa-shield-halved"></i> Fortinet</span>
        <span class="brand-logo"><i class="fas fa-server"></i> HP Aruba</span>
        <span class="brand-logo"><i class="fas fa-plug"></i> D-Link</span>
        <span class="brand-logo"><i class="fas fa-wifi"></i> TP-Link</span>
      </div>
    </div>
  </div>

  <!-- Promo Section -->
  <section class="promo-section">
    <div class="promo-section-inner">
      <div class="promo-grid">
        <div class="promo-card promo-card-1">
          <div class="promo-card-content">
            <h3>Need a Custom Solution?</h3>
            <p>Get a tailored quote from our networking experts</p>
            <a href="about.php" class="promo-card-btn">Get a Quote <i class="fas fa-arrow-right"></i></a>
          </div>
          <div class="promo-card-decor"><i class="fas fa-headset"></i></div>
        </div>
        <div class="promo-card promo-card-2">
          <div class="promo-card-content">
            <h3>Bulk Orders &amp; Deals</h3>
            <p>Volume pricing for businesses and institutions</p>
            <a href="catalog.php" class="promo-card-btn">Shop Now <i class="fas fa-arrow-right"></i></a>
          </div>
          <div class="promo-card-decor"><i class="fas fa-boxes"></i></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Trust Bar -->
  <section class="trust-bar">
    <div class="trust-bar-inner">
      <div class="trust-item">
        <div class="trust-icon"><i class="fas fa-shield-check"></i></div>
        <div class="trust-text">
          <h4>Genuine Products</h4>
          <p>100% authentic networking hardware from authorized distributors.</p>
        </div>
      </div>
      <div class="trust-item">
        <div class="trust-icon"><i class="fas fa-truck-fast"></i></div>
        <div class="trust-text">
          <h4>Fast Delivery</h4>
          <p>Free delivery within Dar es Salaam &amp; nationwide shipping available.</p>
        </div>
      </div>
      <div class="trust-item">
        <div class="trust-icon"><i class="fas fa-headset"></i></div>
        <div class="trust-text">
          <h4>Technical Support</h4>
          <p>Pre- and post-sales engineering support from certified pros.</p>
        </div>
      </div>
      <div class="trust-item">
        <div class="trust-icon"><i class="fas fa-rotate-left"></i></div>
        <div class="trust-text">
          <h4>Easy Returns</h4>
          <p>Hassle-free returns within 7 days of delivery.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="footer-inner">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo">
            <div class="icon"><i class="fas fa-network-wired"></i></div>
            <span class="name">BN-Infrastructure</span>
          </div>
          <p>Tanzania’s trusted partner for enterprise networking infrastructure. From routers and switches to structured cabling and firewalls — we deliver, configure, and support.</p>
          <div class="footer-socials">
            <a href="#" class="footer-social-btn"><i class="fab fa-whatsapp"></i></a>
            <a href="#" class="footer-social-btn"><i class="fab fa-instagram"></i></a>
            <a href="#" class="footer-social-btn"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="footer-social-btn"><i class="fab fa-linkedin-in"></i></a>
          </div>
        </div>
        <div class="footer-col">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="catalog.php"><i class="fas fa-chevron-right"></i> Products</a></li>
            <li><a href="about.php"><i class="fas fa-chevron-right"></i> Solutions</a></li>
            <li><a href="track.php"><i class="fas fa-chevron-right"></i> Order Tracking</a></li>
            <li><a href="login.php"><i class="fas fa-chevron-right"></i> My Account</a></li>
            <li><a href="about.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Categories</h4>
          <ul>
            <?php if (!empty($categories)): ?>
              <?php $i = 0; foreach ($categories as $cat): if ($i >= 5) break; $i++; ?>
                <li><a href="catalog.php?category=<?php echo urlencode($cat['id']); ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?></a></li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Contact Us</h4>
          <div class="contact-item"><i class="fas fa-map-marker-alt"></i> <span>Dar es Salaam, Tanzania</span></div>
          <div class="contact-item"><i class="fas fa-phone"></i> <span>+255 763 364 721</span></div>
          <div class="contact-item"><i class="fas fa-envelope"></i> <span>info@bn-infrastructure.com</span></div>
          <div class="contact-item"><i class="fas fa-clock"></i> <span>Mon–Fri 8:00 AM – 6:00 PM<br>Sat 9:00 AM – 3:00 PM</span></div>
          <div style="margin-top:20px">
            <div class="newsletter-label"><i class="fas fa-envelope-open" style="color:var(--orange);margin-right:6px"></i> Subscribe for deals &amp; updates</div>
            <div class="newsletter-form">
              <input type="email" placeholder="Your email address">
              <button type="button">Subscribe</button>
            </div>
          </div>
        </div>
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
    let currentIndex = 0;
    const slides = document.querySelectorAll('.carousel-slide');
    const dotsContainer = document.getElementById('carouselDots');
    const progressBar = document.getElementById('carouselProgress');
    const currentSlideSpan = document.getElementById('currentSlide');
    const totalSlidesSpan = document.getElementById('totalSlides');
    const totalSlides = slides.length;
    let autoPlayInterval;
    const autoPlayDelay = 5000;

    // Hero text data per slide
    const heroData = [
      { badge: 'Premium Networking Equipment', title: 'Your <span>Network</span> Infrastructure Partner in <span>Tanzania</span>', desc: 'From enterprise routers to structured cabling — BN-Infrastructure supplies, configures, and supports the full stack of networking hardware across Dar es Salaam and all regions.' },
      { badge: 'Managed Switches', title: 'Enterprise <span>Switching</span> for Every <span>Layer</span>', desc: 'Layer 2 and Layer 3 managed switches from leading brands. VLANs, PoE+, stacking — built for backbone performance.' },
      { badge: 'WiFi 6/6E Access Points', title: 'Next-Gen <span>Wireless</span> for <span>Seamless</span> Connectivity', desc: 'High-density WiFi 6 and 6E access points designed for enterprise, education, hospitality, and smart offices.' },
      { badge: 'Structured Cabling', title: '<span>Reliable</span> Cabling for <span>Future</span>-Ready Networks', desc: 'CAT6A, CAT7, fiber optic, patch panels, racks, and tools — everything for a structured cabling installation.' },
      { badge: 'UTM & Next-Gen Firewalls', title: '<span>Protect</span> Your Network with <span>Advanced</span> Security', desc: 'Next-gen firewalls, UTM appliances, VPNs, and intrusion prevention systems to safeguard your critical infrastructure.' }
    ];

    function updateSlider(index) {
      slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
      });
      // Update hero text content
      const data = heroData[index] || heroData[0];
      const badgeEl = document.getElementById('heroBadgeText');
      const titleEl = document.querySelector('.hero-text-content h1');
      const descEl = document.getElementById('heroDesc');
      if (badgeEl) badgeEl.textContent = data.badge;
      if (titleEl) titleEl.innerHTML = data.title;
      if (descEl) descEl.textContent = data.desc;
      // Update dots
      document.querySelectorAll('.carousel-dot').forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
      });
      // Update counter
      currentSlideSpan.textContent = String(index + 1).padStart(2, '0');
    }

    function updateProgress() {
      progressBar.style.transition = 'width 0.3s linear';
      progressBar.style.width = '0%';
      requestAnimationFrame(() => {
        progressBar.style.transition = 'width ' + (autoPlayDelay / 1000) + 's linear';
        progressBar.style.width = '100%';
      });
    }

    function goToSlide(index) {
      currentIndex = index;
      updateSlider(currentIndex);
      resetAutoPlay();
    }

    function nextSlide() {
      currentIndex = (currentIndex + 1) % totalSlides;
      updateSlider(currentIndex);
      resetAutoPlay();
    }

    function prevSlide() {
      currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
      updateSlider(currentIndex);
      resetAutoPlay();
    }

    function resetAutoPlay() {
      clearInterval(autoPlayInterval);
      updateProgress();
      autoPlayInterval = setInterval(() => {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateSlider(currentIndex);
        updateProgress();
      }, autoPlayDelay);
    }

    // Initialize dots
    if (totalSlidesSpan) totalSlidesSpan.textContent = String(totalSlides).padStart(2, '0');
    for (let i = 0; i < totalSlides; i++) {
      const dot = document.createElement('button');
      dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
      dot.onclick = () => goToSlide(i);
      dotsContainer.appendChild(dot);
    }

    // Show slide product tags - hide all, show current
    const slideTags = document.querySelectorAll('.slide-product-tag');
    function updateTags(index) {
      slideTags.forEach((tag, i) => {
        tag.style.display = i === index ? 'flex' : 'none';
      });
    }
    updateTags(0);
    // Patch updateSlider to also update tags
    const origUpdate = updateSlider;
    updateSlider = function(index) {
      origUpdate(index);
      updateTags(index);
    };

    updateProgress();
    autoPlayInterval = setInterval(() => {
      currentIndex = (currentIndex + 1) % totalSlides;
      updateSlider(currentIndex);
      updateProgress();
    }, autoPlayDelay);

    // Reveal animations
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.1 });
    document.querySelectorAll('.category-card, .product-card, .trust-item, .promo-card').forEach(el => {
      el.classList.add('reveal');
      observer.observe(el);
    });

    // Mobile menu
    function toggleMenu() {
      const nav = document.getElementById('mobileNav');
      const overlay = document.getElementById('navOverlay');
      nav.classList.toggle('open');
      overlay.classList.toggle('open');
    }

    // Add to Cart AJAX handler
    document.querySelectorAll('.add-to-cart-form').forEach(function(f) {
      f.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = this.querySelector('button[type="submit"]');
        if (btn.classList.contains('btn-in-cart')) return;
        var origHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        var fd = new FormData(this); fd.set('_ajax','1');
        var self = this;
        fetch(this.action, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function(r) {
          return r.text();
        }).then(function(txt) {
          try { var d = JSON.parse(txt); if (d.redirect) { window.location.href = d.redirect; return; } if (d.ok) { var badge = document.querySelector('.cart-badge'); if (badge) { badge.textContent = d.count || 1; badge.style.transform = 'scale(1.4)'; setTimeout(function() { badge.style.transform = 'scale(1)'; }, 200); } btn.innerHTML = '<i class="fas fa-check"></i> Added to Cart'; btn.style.background = '#059669'; btn.classList.add('btn-in-cart'); btn.disabled = false; return; } } catch(ex) {}
          self.submit();
        }).catch(function() {
          self.submit();
        });
      });
    });
  </script>

</body>
</html>
