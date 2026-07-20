<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/cart.php';

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
    .navbar { background: var(--navy); padding: 0 48px; display: flex; align-items: center; gap: 0; height: 70px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 12px rgba(0,0,0,0.2); }
    .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; margin-right: 40px; flex-shrink: 0; }
    .nav-logo-icon { width: 38px; height: 38px; background: var(--orange); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; }
    .nav-logo-text { display: flex; flex-direction: column; line-height: 1.1; }
    .nav-logo-text .brand { font-size: 18px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
    .nav-logo-text .tagline { font-size: 10px; font-weight: 400; color: #fff; letter-spacing: 0.08em; text-transform: uppercase; }
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
    .search-bar i { color: #fff; font-size: 13px; }
    .search-bar input { background: none; border: none; outline: none; color: #fff; font-family: 'Inter', sans-serif; font-size: 13px; width: 100%; }
    .search-bar input::placeholder { color: rgba(255,255,255,0.45); }
    .btn-signin { background: transparent; border: 1.5px solid rgba(255,255,255,0.3); color: rgba(255,255,255,0.85); padding: 7px 16px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s; white-space: nowrap; text-decoration: none; }
    .btn-signin:hover { background: rgba(255,255,255,0.1); border-color: #fff; color: #fff; }
    .cart-btn { background: var(--orange); border: none; color: #fff; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; transition: background 0.2s, transform 0.2s; font-size: 15px; }
    .cart-btn:hover { background: var(--orange-dark); transform: translateY(-1px); }
    .cart-badge { position: absolute; top: -5px; right: -5px; background: #fff; color: var(--orange); font-size: 10px; font-weight: 700; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1.5px solid var(--orange); }
    .hero { background: var(--navy); padding: 0 48px; display: flex; align-items: center; min-height: 480px; position: relative; overflow: hidden; }
    .hero::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse at 70% 50%, rgba(240,90,34,0.08) 0%, transparent 60%), radial-gradient(ellipse at 20% 80%, rgba(255,255,255,0.03) 0%, transparent 50%); }
    .hero-grid { display: grid; grid-template-columns: 1fr 480px; gap: 60px; align-items: center; width: 100%; max-width: 1632px; margin: 0 auto; position: relative; z-index: 1; padding: 60px 0; }
    .hero-badge { display: inline-flex; align-items: center; gap: 7px; background: rgba(240,90,34,0.15); border: 1px solid rgba(240,90,34,0.3); color: #ff8555; font-size: 12px; font-weight: 600; padding: 5px 12px; border-radius: 20px; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 20px; }
    .hero h1 { font-size: clamp(32px, 3.2vw, 52px); font-weight: 800; color: #fff; line-height: 1.1; letter-spacing: -0.03em; margin-bottom: 18px; }
    .hero h1 span { color: var(--orange); }
    .hero p { font-size: 17px; color: rgba(255,255,255,0.65); line-height: 1.65; margin-bottom: 36px; max-width: 520px; font-weight: 400; }
    .hero-ctas { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
    .btn-primary { background: var(--orange); color: #fff; border: none; padding: 13px 28px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
    .btn-primary:hover { background: var(--orange-dark); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(240,90,34,0.35); }
    .btn-outline-white { background: transparent; color: #fff; border: 2px solid #fff; padding: 11px 26px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
    .btn-outline-white:hover { border-color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.08); transform: translateY(-2px); }
    .hero-stats { display: flex; gap: 32px; margin-top: 40px; padding-top: 32px; border-top: 1px solid rgba(255,255,255,0.1); }
    .hero-stat { text-align: left; }
    .hero-stat .number { font-size: 24px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
    .hero-stat .label { font-size: 12px; color: #fff; font-weight: 400; }
    .hero-image { position: relative; display: flex; align-items: center; justify-content: center; }
    .hero-slideshow { position: relative; width: 100%; height: 380px; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
    .hero-slideshow .slide { position: absolute; inset: 0; opacity: 0; transition: opacity 0.8s ease, transform 0.8s ease; transform: scale(1.05); }
    .hero-slideshow .slide.active { opacity: 1; transform: scale(1); }
    .hero-slideshow .slide img { width: 100%; height: 100%; object-fit: cover; }
    .slide-dots { position: absolute; bottom: -30px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; z-index: 10; }
    .dot { width: 10px; height: 10px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.6); background: transparent; cursor: pointer; transition: all 0.3s; padding: 0; }
    .dot.active { background: var(--orange); border-color: var(--orange); transform: scale(1.2); }
    .dot:hover { border-color: #fff; background: rgba(255,255,255,0.4); }
    .hero-image-badge { position: absolute; bottom: -16px; left: -16px; background: var(--orange); color: #fff; padding: 12px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; box-shadow: 0 8px 24px rgba(240,90,34,0.4); }
    .hero-image-badge2 { position: absolute; top: -12px; right: -12px; background: var(--card); color: var(--navy); padding: 10px 16px; border-radius: 10px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 7px; box-shadow: var(--shadow-lg); }
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
    .product-name { font-size: 14px; font-weight: 700; color: var(--navy); line-height: 1.35; margin-bottom: 10px; flex: 1; }
    .product-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
    .product-price-block { margin-bottom: 14px; }
    .product-price-label { font-size: 11px; color: var(--text-muted); font-weight: 500; margin-bottom: 2px; }
    .product-price { font-size: 20px; font-weight: 800; color: var(--navy); letter-spacing: -0.02em; }
    .product-price .currency { font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-right: 2px; }
    .product-price-old { font-size: 13px; color: var(--text-muted); text-decoration: line-through; margin-left: 8px; font-weight: 400; }
    .product-actions { display: flex; gap: 8px; }
    .btn-cart { flex: 1; background: var(--navy); color: #fff; border: none; padding: 9px 14px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; }
    .btn-cart:hover { background: var(--orange); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(240,90,34,0.3); }
    .btn-cart.btn-in-cart { background: #059669; pointer-events: none; }
    .btn-quote { background: transparent; color: var(--navy); border: 1.5px solid var(--border); padding: 9px 14px; border-radius: 7px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; white-space: nowrap; }
    .btn-quote:hover { border-color: var(--orange); color: var(--orange); transform: translateY(-1px); }
    .brands-strip { background: var(--card); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 30px 48px; }
    .brands-strip-inner { max-width: 1632px; margin: 0 auto; display: flex; align-items: center; gap: 0; }
    .brands-strip-label { font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; margin-right: 40px; padding-right: 40px; border-right: 1px solid var(--border); }
    .brands-list { display: flex; align-items: center; gap: 40px; flex: 1; }
    .brand-logo { font-size: 15px; font-weight: 800; color: var(--text-muted); letter-spacing: -0.02em; transition: color 0.2s; cursor: default; display: flex; align-items: center; gap: 6px; }
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
    .footer-brand p { font-size: 13px; color: #fff; line-height: 1.7; margin-bottom: 20px; }
    .footer-socials { display: flex; gap: 8px; }
    .footer-social-btn { width: 34px; height: 34px; background: rgba(255,255,255,0.06); border-radius: 7px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .footer-social-btn:hover { background: var(--orange); color: #fff; }
    .footer-col h4 { font-size: 13px; font-weight: 700; color: #fff; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 18px; }
    .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .footer-col ul li a { font-size: 13px; color: #fff; text-decoration: none; transition: color 0.2s; display: flex; align-items: center; gap: 7px; }
    .footer-col ul li a:hover { color: rgba(255,255,255,0.9); }
    .footer-col ul li a i { font-size: 11px; color: var(--orange); opacity: 0.7; }
    .contact-item { display: flex; gap: 10px; margin-bottom: 14px; }
    .contact-item i { color: var(--orange); font-size: 14px; margin-top: 2px; flex-shrink: 0; }
    .contact-item span { font-size: 13px; color: #fff; line-height: 1.5; }
    .newsletter-label { font-size: 13px; color: #fff; margin-bottom: 12px; line-height: 1.5; }
    .newsletter-form { display: flex; gap: 8px; }
    .newsletter-form input { flex: 1; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; padding: 10px 14px; color: #fff; font-family: 'Inter', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s; }
    .newsletter-form input::placeholder { color: #fff; }
    .newsletter-form input:focus { border-color: rgba(255,255,255,0.3); }
    .newsletter-form button { background: var(--orange); color: #fff; border: none; border-radius: 7px; padding: 10px 16px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background 0.2s; white-space: nowrap; }
    .newsletter-form button:hover { background: var(--orange-dark); }
    .footer-bottom { padding: 20px 0; display: flex; align-items: center; justify-content: space-between; }
    .footer-bottom p { font-size: 12px; color: #fff; }
    .footer-bottom-links { display: flex; gap: 20px; }
    .footer-bottom-links a { font-size: 12px; color: #fff; text-decoration: none; transition: color 0.2s; }
    .footer-bottom-links a:hover { color: #fff; }
    .section-divider { width: 100%; height: 1px; background: var(--border); }
    @media (max-width: 1200px) { .navbar, .hero, .section, .brands-strip, .trust-bar, .promo-section, footer { padding-left: 32px; padding-right: 32px; } .hero-grid { grid-template-columns: 1fr 380px; gap: 40px; } .products-grid, .categories-grid { grid-template-columns: repeat(2, 1fr); } .footer-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 768px) { .navbar { padding: 0 16px; height: 60px; } .nav-links { display: none; } .hamburger { display: block; } .btn-signin span { display: none; } .hero, .section, .brands-strip, .trust-bar, .promo-section, footer { padding-left: 20px; padding-right: 20px; } .hero-grid { grid-template-columns: 1fr; } .hero-image { display: none; } .categories-grid, .products-grid, .trust-bar-inner, .promo-grid { grid-template-columns: 1fr; } .footer-grid { grid-template-columns: 1fr; gap: 32px; } .footer-bottom { flex-direction: column; gap: 12px; text-align: center; } .search-bar { width: 160px; } }
    .user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}.img-placeholder{width:100%;height:200px;background:linear-gradient(135deg,#f8fafc,#e2e8f0);display:flex;align-items:center;justify-content:center;font-size:40px;color:#94a3b8}.reveal,.card-reveal,.product-reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
  </style>
</head>
<body>

  <div class="announcement-bar">
    <i class="fas fa-truck"></i> Free delivery on orders above TSh 500,000
    <span>|</span> <i class="fas fa-map-marker-alt"></i> Serving all regions in Tanzania
    <span>|</span> <i class="fas fa-headset"></i> Technical support: +255 763 364 721
  </div>

  <nav class="navbar">
    <a href="#" class="nav-logo">
      <div class="nav-logo-icon"><i class="fas fa-network-wired"></i></div>
      <div class="nav-logo-text"><span class="brand">BN-Infrastructure</span><span class="tagline">Tanzania</span></div>
    </a>
    <div class="nav-links">
      <a href="/catalog.php">Products</a>
      <a href="/about.php">Solutions</a>
      <a href="/track.php">Track Order</a>
      <a href="/login.php">Account</a>
    </div>
    <div class="nav-actions">
      <?php echo userNavHtml(); ?>
      <a href="/cart.php" class="cart-btn"><i class="fas fa-shopping-cart"></i><span class="cart-badge"><?php echo cartCount(); ?></span></a>
      <button class="hamburger" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
    </div>
  </nav>
  <div class="nav-overlay" id="navOverlay" onclick="toggleMenu()"></div>
  <div class="mobile-nav" id="mobileNav">
    <button class="close-btn" onclick="toggleMenu()"><i class="fas fa-times"></i></button>
    <a href="/catalog.php" onclick="toggleMenu()">Products</a>
    <a href="/about.php" onclick="toggleMenu()">Solutions</a>
    <a href="/track.php" onclick="toggleMenu()">Track Order</a>
    <?php echo mobileAccountHtml(); ?>
    <a href="/cart.php" onclick="toggleMenu()">Cart</a>
  </div>

  <section class="hero reveal">
    <div class="hero-grid">
      <div class="hero-content">
        <h1>Tanzania's <span>#1</span> Network Infrastructure Supplier</h1>
        <p>Routers, Switches, Access Points, Cabling &amp; More — purpose-built for businesses, ISPs, and enterprise networks across Tanzania.</p>
        <div class="hero-ctas">
          <a href="/catalog.php" class="btn-primary"><i class="fas fa-th-large"></i> Browse Catalog</a>
          <a href="/about.php" class="btn-outline-white"><i class="fas fa-magic"></i> Find Your Solution</a>
        </div>
        <div class="hero-stats">
          <div class="hero-stat"><div class="number"><?php echo $totalProducts; ?>+</div><div class="label">Products in Stock</div></div>
          <div class="hero-stat"><div class="number">50+</div><div class="label">Verified Brands</div></div>
          <div class="hero-stat"><div class="number">2,000+</div><div class="label">Business Clients</div></div>
          <div class="hero-stat"><div class="number">30</div><div class="label">Regions Served</div></div>
        </div>
      </div>
      <div class="hero-image">
        <div class="hero-slideshow" id="heroSlideshow">
          <div class="slide active"><img src="networking_equipment_collage.jpg" alt="Network equipment"></div>
          <div class="slide"><img src="cisco_catalyst_2960x_switch.jpg" alt="Cisco Catalyst Switch"></div>
          <div class="slide"><img src="mikrotik_ccr2004_router.jpg" alt="MikroTik Router"></div>
          <div class="slide"><img src="ubiquiti_unifi_ap_ax_access_point.jpg" alt="Ubiquiti Access Point"></div>
          <div class="slide"><img src="dell_r750_server.jpg" alt="Dell Server"></div>
          <div class="slide"><img src="fortinet_fortigate_60f.jpg" alt="Fortinet Firewall"></div>
        </div>
        <div class="slide-dots" id="slideDots">
          <button class="dot active" data-index="0"></button>
          <button class="dot" data-index="1"></button>
          <button class="dot" data-index="2"></button>
          <button class="dot" data-index="3"></button>
          <button class="dot" data-index="4"></button>
          <button class="dot" data-index="5"></button>
        </div>
        <div class="hero-image-badge"><i class="fas fa-boxes"></i> 500+ SKUs in Stock</div>
        <div class="hero-image-badge2"><i class="fas fa-check-circle" style="color:#059669"></i> Genuine Products</div>
      </div>
    </div>
  </section>

  <section class="section reveal" style="background: var(--card); border-bottom: 1px solid var(--border);">
    <div class="section-inner">
      <div class="section-header">
        <div><h2>Shop by Category</h2><p>Browse our complete range of enterprise network infrastructure</p></div>
        <a href="/catalog.php" class="view-all-link">View All Categories <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="categories-grid">
        <?php
        $catIcons = [
            'switches' => 'fa-sitemap', 'routers' => 'fa-server', 'access-points' => 'fa-wifi',
            'cabling' => 'fa-cable-car', 'firewalls' => 'fa-shield-alt', 'power' => 'fa-bolt',
            'transceivers' => 'fa-microchip', 'racks-enclosures' => 'fa-archive', 'tools-testers' => 'fa-tools'
        ];
        ?>
        <?php foreach ($categories as $cat):
            $count = fetchOne("SELECT COUNT(*) as c FROM products WHERE category_id = ?", [$cat['id']])['c'];
            $icon = $catIcons[$cat['slug']] ?? 'fa-box';
        ?>
        <a href="/catalog.php?category=<?php echo $cat['id']; ?>" class="category-card">
          <div class="category-icon"><i class="fas <?php echo $icon; ?>"></i></div>
          <div class="category-content"><h3><?php echo htmlspecialchars($cat['name']); ?></h3><p><?php echo htmlspecialchars($cat['description'] ?? ''); ?></p></div>
          <div class="category-footer"><span class="category-count"><?php echo $count; ?> Products</span><span class="view-all-link" style="font-size:13px;">View All <i class="fas fa-chevron-right" style="font-size:10px;"></i></span></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section products-section reveal">
    <div class="section-inner">
      <div class="section-header">
        <div><h2>Top Selling Products</h2><p>Trusted by enterprises and ISPs across Tanzania</p></div>
        <a href="/catalog.php" class="view-all-link">View All Products <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="products-grid">
        <?php if (empty($featuredProducts)): ?>
          <?php
          $featuredProducts = fetchAll(
              "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT 4"
          );
          ?>
        <?php endif; ?>
        <?php foreach ($featuredProducts as $p):
            $tags = $p['tags'] ? explode(',', $p['tags']) : [];
        ?>
        <div class="product-card">
          <div class="product-image-wrap">
            <?php echo imageOrPlaceholder($p['image'], $p['name'], $p['brand'] ?? ''); ?>
            <div class="product-badges">
              <span class="badge badge-brand"><i class="fas fa-shield-check"></i> <?php echo htmlspecialchars($p['brand'] ?: 'Generic'); ?></span>
              <?php if ($p['discount_percentage']): ?>
              <span class="badge badge-sale">-<?php echo $p['discount_percentage']; ?>% OFF</span>
              <?php elseif ($p['stock_status'] === 'out_of_stock'): ?>
              <span class="badge badge-out">Out of Stock</span>
              <?php else: ?>
              <span class="badge badge-stock">In Stock</span>
              <?php endif; ?>
            </div>
            <button class="product-wishlist"><i class="far fa-heart"></i></button>
          </div>
          <div class="product-body">
            <div class="product-sku">SKU: <?php echo htmlspecialchars($p['sku']); ?></div>
            <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
            <div class="product-meta">
              <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
              <span class="badge badge-brand" style="background:rgba(10,37,64,0.06);color:var(--navy);"><?php echo htmlspecialchars(trim($tag)); ?></span>
              <?php endforeach; ?>
              <?php echo getStockBadge($p['stock_status']); ?>
            </div>
            <div class="product-price-block">
              <div class="product-price-label">Unit Price (excl. VAT)</div>
              <div class="product-price">
                <span class="currency">TSh</span><?php echo number_format((float)$p['price'], 0, '.', ','); ?>
                <?php if ($p['old_price']): ?>
                <span class="product-price-old">TSh <?php echo number_format((float)$p['old_price'], 0, '.', ','); ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="product-actions">
              <?php $inCart = in_array($p['id'], $cartProductIds); ?>
              <form method="POST" action="/cart.php" style="flex:1;display:flex;" class="add-to-cart-form">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="btn-cart <?php echo $inCart ? 'btn-in-cart' : ''; ?>" data-product-id="<?php echo $p['id']; ?>"><i class="fas <?php echo $inCart ? 'fa-check' : 'fa-shopping-cart'; ?>"></i> <?php echo $inCart ? 'Added to Cart' : 'Add to Cart'; ?></button>
              </form>
              <form method="POST" action="/cart.php" style="display:flex;">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="btn-quote"><i class="fas fa-file-alt"></i> Quote</button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <div class="brands-strip">
    <div class="brands-strip-inner">
      <div class="brands-strip-label">Trusted Brands</div>
      <div class="brands-list">
        <div class="brand-logo"><i class="fas fa-network-wired"></i> MikroTik</div>
        <div class="brand-logo"><i class="fas fa-shield-alt"></i> Cisco</div>
        <div class="brand-logo"><i class="fas fa-wifi"></i> Ubiquiti</div>
        <div class="brand-logo"><i class="fas fa-server"></i> HP Aruba</div>
        <div class="brand-logo"><i class="fas fa-bolt"></i> Netgear</div>
        <div class="brand-logo"><i class="fas fa-link"></i> Panduit</div>
        <div class="brand-logo"><i class="fas fa-fire"></i> Fortinet</div>
        <div class="brand-logo"><i class="fas fa-lock"></i> TP-Link Omada</div>
      </div>
    </div>
  </div>

  <section class="promo-section" style="padding-top: 60px;">
    <div class="promo-section-inner">
      <div class="promo-grid">
        <div class="promo-card promo-card-1">
          <div class="promo-card-content"><h3>Bulk &amp; Tender Pricing</h3><p>Special rates for volume orders, government tenders, and ISP deployments</p><a href="#" class="promo-card-btn">Get a Quote <i class="fas fa-arrow-right"></i></a></div>
          <div class="promo-card-decor"><i class="fas fa-boxes"></i></div>
        </div>
        <div class="promo-card promo-card-2">
          <div class="promo-card-content"><h3>New Arrivals — WiFi 7</h3><p>Be the first to deploy next-gen WiFi 7 access points across your network</p><a href="#" class="promo-card-btn">Shop Now <i class="fas fa-arrow-right"></i></a></div>
          <div class="promo-card-decor"><i class="fas fa-wifi"></i></div>
        </div>
      </div>
    </div>
  </section>

  <div class="trust-bar reveal">
    <div class="trust-bar-inner">
      <div class="trust-item"><div class="trust-icon"><i class="fas fa-certificate"></i></div><div class="trust-text"><h4>Verified Brands</h4><p>All products sourced directly from authorised distributors. 100% genuine.</p></div></div>
      <div class="trust-item"><div class="trust-icon"><i class="fas fa-tags"></i></div><div class="trust-text"><h4>Bulk Pricing</h4><p>Volume discounts for ISPs, system integrators, and enterprise procurement.</p></div></div>
      <div class="trust-item"><div class="trust-icon"><i class="fas fa-truck"></i></div><div class="trust-text"><h4>Nationwide Delivery</h4><p>Reliable shipping to all 30 regions in Tanzania, from Dar es Salaam to Mwanza.</p></div></div>
      <div class="trust-item"><div class="trust-icon"><i class="fas fa-headset"></i></div><div class="trust-text"><h4>Technical Support</h4><p>Pre-sales consultation and post-sale technical assistance from certified engineers.</p></div></div>
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
        <div class="footer-col"><h4>Quick Links</h4><ul><li><a href="/catalog.php"><i class="fas fa-chevron-right"></i> All Products</a></li><li><a href="/catalog.php"><i class="fas fa-chevron-right"></i> New Arrivals</a></li><li><a href="/catalog.php"><i class="fas fa-chevron-right"></i> Best Sellers</a></li><li><a href="/catalog.php"><i class="fas fa-chevron-right"></i> Special Offers</a></li><li><a href="/about.php"><i class="fas fa-chevron-right"></i> Request a Quote</a></li><li><a href="/about.php"><i class="fas fa-chevron-right"></i> Bulk Orders</a></li></ul></div>
        <div class="footer-col"><h4>Company</h4><ul><li><a href="/about.php"><i class="fas fa-chevron-right"></i> About BN-Infrastructure</a></li><li><a href="/catalog.php"><i class="fas fa-chevron-right"></i> Our Brands</a></li><li><a href="/about.php"><i class="fas fa-chevron-right"></i> Solutions</a></li><li><a href="/about.php"><i class="fas fa-chevron-right"></i> Blog &amp; Resources</a></li><li><a href="/about.php"><i class="fas fa-chevron-right"></i> Careers</a></li><li><a href="/privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li></ul></div>
        <div class="footer-col"><h4>Contact Us</h4><div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>Plot 45, Mikocheni Light Industrial Area, Dar es Salaam, Tanzania</span></div><div class="contact-item"><i class="fas fa-phone-alt"></i><span>+255 763 364 721 <br>+255 763 364 721</span></div><div class="contact-item"><i class="fas fa-envelope"></i><span>sales@bn-infrastructure.com</span></div><div style="margin-top: 20px;"><h4>Newsletter</h4><div class="newsletter-label">Get product updates and exclusive deals</div><div class="newsletter-form"><input type="email" placeholder="Your email address"><button>Subscribe</button></div></div></div>
      </div>
      <div class="footer-bottom"><p>© 2024 BN-Infrastructure Ltd. All rights reserved. | All prices in Tanzanian Shillings (TSh) excl. 18% VAT</p><div class="footer-bottom-links"><a href="/terms.php">Terms of Service</a><a href="/privacy.php">Privacy Policy</a><a href="/shipping.php">Shipping Policy</a><a href="/returns.php">Returns</a></div></div>
    </div>
  </footer>

  <script>

    document.querySelectorAll('.product-wishlist').forEach(btn => {
      btn.addEventListener('click', function() {
        const icon = this.querySelector('i');
        if (icon.classList.contains('far')) { icon.classList.replace('far', 'fas'); this.style.color = '#F05A22'; this.style.opacity = '1'; }
        else { icon.classList.replace('fas', 'far'); this.style.color = ''; this.style.opacity = ''; }
      });
    });
    function toggleMenu() { document.getElementById('mobileNav').classList.toggle('open'); document.getElementById('navOverlay').classList.toggle('open'); }
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

    // Hero Slideshow
    (function(){
      var slides=document.querySelectorAll('.hero-slideshow .slide');
      var dots=document.querySelectorAll('.slide-dots .dot');
      var current=0;
      var timer;
      function go(n){
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current=(n+slides.length)%slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
      }
      function startAuto(){timer=setInterval(function(){go(current+1)},3500)}
      dots.forEach(function(d){d.addEventListener('click',function(){clearInterval(timer);go(parseInt(this.dataset.index));startAuto()})});
      startAuto();
    })();
  </script>

<script>
<?php echo userMenuJs(); ?>
<?php echo scrollRevealJs(); ?>
</script>
</body></html>
