<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/cart.php';

$brandName = 'BN-Infrastructure';
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $id = (int)($_POST['id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['_ajax']);
    if (!isLoggedIn()) {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $loginUrl = 'login.php';
        if ($referer) {
            $loginUrl .= '?redirect=' . urlencode($referer);
        }
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['redirect' => $loginUrl]);
            exit;
        }
        header('Location: ' . $loginUrl);
        exit;
    }
    $ok = cartAdd($id, $qty);
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok, 'count' => cartCount()]);
        exit;
    }
    $back = $_SERVER['HTTP_REFERER'] ?? 'catalog.php';
    header('Location: ' . $back);
    exit;
}
$id = (int)($_POST['id'] ?? 0);
$qty = max(1, (int)($_POST['qty'] ?? 1));
if ($action === 'update') {
    cartUpdate($id, $qty);
    header('Location: ?updated=1');
    exit;
}
if ($action === 'remove') {
    cartRemove($id);
    header('Location: ?removed=1');
    exit;
}
if ($action === 'clear') {
    cartClear();
    header('Location: ?cleared=1');
    exit;
}
if ($action === 'submit_quote') {
    $notes = $_POST['notes'] ?? '';
    $items = cartGetItems();
    if (!empty($items)) {
        $subtotal = cartSubtotal();
        $discount = $subtotal >= 10000000 ? round($subtotal * 0.05) : 0;
        $afterDiscount = $subtotal - $discount;
        $vat = round($afterDiscount * 0.18);
        $total = $afterDiscount + $vat;
        $qnum = generateQuoteNumber();
        $lpoFile = null;
        if (!empty($_FILES['lpo_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['lpo_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx'])) {
                $lpoFile = uniqid('lpo_') . '.' . $ext;
                move_uploaded_file($_FILES['lpo_file']['tmp_name'], __DIR__ . '/../uploads/' . $lpoFile);
            }
        }
        $uid = $_SESSION['user_id'] ?? null;
        execute(
            "INSERT INTO quotations (quotation_number, user_id, notes, lpo_file, subtotal, discount, vat, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
            [$qnum, $uid, $notes, $lpoFile, $subtotal, $discount, $vat, $total]
        );
        $qid = execute("SELECT LAST_INSERT_ID() as id", []);
        $qid = fetchOne("SELECT MAX(id) as id FROM quotations")['id'];
        foreach ($items as $item) {
            execute(
                "INSERT INTO quotation_items (quotation_id, product_id, product_name, product_sku, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$qid, $item['id'], $item['name'], $item['sku'], $item['qty'], $item['price'], $item['price'] * $item['qty']]
            );
        }
        cartClear();
        $quoteSuccess = $qnum;
    }
}

$cartItems = cartGetItems();
$subtotal = cartSubtotal();
$discount = $subtotal >= 10000000 ? round($subtotal * 0.05) : 0;
$afterDiscount = $subtotal - $discount;
$vat = round($afterDiscount * 0.18);
$total = $afterDiscount + $vat;
$itemCount = cartCount();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart & Quote Request — <?php echo $brandName; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--navy-light:#133057;--navy-dark:#071a2e;--orange:#F05A22;--orange-dark:#d44d1a;--orange-light:#ff6b35;--bg:#F4F6F9;--card:#FFF;--text-primary:#0A2540;--text-secondary:#5a6a7e;--text-muted:#8fa0b3;--border:#e2e8f0;--green:#059669;--green-bg:rgba(5,150,105,0.08);--green-border:rgba(5,150,105,0.2);--shadow-sm:0 1px 3px rgba(10,37,64,0.08),0 1px 2px rgba(10,37,64,0.04);--shadow-md:0 4px 12px rgba(10,37,64,0.1),0 2px 6px rgba(10,37,64,0.06);--shadow-lg:0 10px 30px rgba(10,37,64,0.12),0 4px 12px rgba(10,37,64,0.08)}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-primary);font-size:15px;line-height:1.6}
.announcement-bar{background:var(--orange);color:#fff;text-align:center;padding:9px 24px;font-size:13px;font-weight:500;letter-spacing:.01em}
.announcement-bar i{margin-right:6px;opacity:.9}.announcement-bar span{margin:0 18px;opacity:.7}
.navbar{background:var(--navy);padding:0 48px;display:flex;align-items:center;height:70px;position:sticky;top:0;z-index:1000;box-shadow:0 2px 12px rgba(0,0,0,0.2)}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;margin-right:40px;flex-shrink:0}
.nav-logo-icon{width:38px;height:38px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff}
.nav-logo-text{display:flex;flex-direction:column;line-height:1.1}
.nav-logo-text .brand{font-size:18px;font-weight:800;color:#fff;letter-spacing:-0.02em}
.nav-logo-text .tagline{font-size:10px;font-weight:400;color:rgba(255,255,255,0.5);letter-spacing:.08em;text-transform:uppercase}
.nav-links{display:flex;align-items:center;gap:4px;flex:1}
.nav-links a{text-decoration:none;color:rgba(255,255,255,0.75);font-size:14px;font-weight:500;padding:8px 14px;border-radius:6px;transition:color .2s,background .2s;white-space:nowrap}
.nav-links a:hover{color:#fff;background:rgba(255,255,255,0.08)}
.hamburger{display:none;background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:6px;margin-left:auto}
.nav-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999}
.nav-overlay.open{display:block}
.mobile-nav{position:fixed;top:0;right:-280px;width:280px;height:100vh;background:var(--navy);z-index:1001;transition:right .3s ease;padding:80px 24px 24px;overflow-y:auto}
.mobile-nav.open{right:0}
.mobile-nav a{display:block;color:rgba(255,255,255,0.8);text-decoration:none;padding:12px 0;font-size:15px;font-weight:500;border-bottom:1px solid rgba(255,255,255,0.06)}
.mobile-nav a:hover{color:var(--orange)}
.mobile-nav .close-btn{position:absolute;top:16px;right:16px;background:none;border:none;color:#fff;font-size:24px;cursor:pointer}
.mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
.nav-actions{display:flex;align-items:center;gap:12px;flex-shrink:0}
.btn-signin{background:transparent;border:1.5px solid rgba(255,255,255,0.3);color:rgba(255,255,255,0.85);padding:7px 16px;border-radius:7px;font-family:'Inter',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:all .2s;white-space:nowrap;text-decoration:none}
.btn-signin:hover{background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.5);color:#fff}
.cart-btn{background:var(--orange);border:none;color:#fff;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;transition:background .2s,transform .2s;font-size:15px}
.cart-btn:hover{background:var(--orange-dark)}.cart-badge{position:absolute;top:-5px;right:-5px;background:#fff;color:var(--orange);font-size:10px;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--orange)}
.breadcrumb-bar{background:var(--card);border-bottom:1px solid var(--border);padding:14px 32px}
.breadcrumb-inner{max-width:1632px;margin:0 auto;display:flex;align-items:center;gap:8px}
.breadcrumb-inner a{font-size:13px;color:var(--text-secondary);text-decoration:none;transition:color .2s}
.breadcrumb-inner a:hover{color:var(--orange)}.breadcrumb-inner .sep{font-size:11px;color:var(--text-muted)}
.breadcrumb-inner .current{font-size:13px;font-weight:600;color:var(--navy)}
.page-wrap{max-width:1632px;margin:0 auto;padding:36px 48px 64px}
.page-header{margin-bottom:28px}
.page-header h1{font-size:26px;font-weight:800;color:var(--navy);letter-spacing:-0.02em;display:flex;align-items:center;gap:12px}
.page-header h1 .brand-icon{color:var(--orange);font-size:22px}
.page-header h1 .item-count{font-size:14px;font-weight:600;color:var(--text-secondary);background:var(--bg);border:1px solid var(--border);padding:3px 12px;border-radius:20px}
.cart-layout{display:grid;grid-template-columns:1fr 400px;gap:28px;align-items:start}
.left-col{display:flex;flex-direction:column;gap:24px}
.section-card{background:var(--card);border-radius:14px;border:1px solid var(--border);box-shadow:var(--shadow-sm);overflow:hidden}
.section-card-header{padding:20px 24px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.section-card-header h2{font-size:16px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:8px}
.section-card-header h2 i{color:var(--orange);font-size:15px}
.select-all-row{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);cursor:pointer}
.select-all-row input[type="checkbox"]{width:16px;height:16px;accent-color:var(--navy);cursor:pointer}
.cart-items-list{padding:0}
.cart-item{display:grid;grid-template-columns:auto auto 1fr auto auto auto auto;gap:0 18px;align-items:center;padding:20px 24px;border-bottom:1px solid var(--border);transition:background .15s}
.cart-item:last-child{border-bottom:none}
.cart-item:hover{background:#fafbfc}
.cart-item-checkbox{display:flex;align-items:center}
.cart-item-checkbox input[type="checkbox"]{width:16px;height:16px;accent-color:var(--navy);cursor:pointer}
.cart-item-image{width:80px;height:80px;border-radius:10px;overflow:hidden;background:#f0f3f7;border:1px solid var(--border);flex-shrink:0;display:flex;align-items:center;justify-content:center}
.cart-item-image img{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.cart-item:hover .cart-item-image img{transform:scale(1.05)}
.cart-item-info{min-width:0}
.cart-item-brand{font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px}
.cart-item-name{font-size:14px;font-weight:700;color:var(--navy);line-height:1.3;margin-bottom:4px}
.cart-item-sku{font-size:11px;color:var(--text-muted);font-weight:500;letter-spacing:.03em;margin-bottom:6px}
.cart-item-badges{display:flex;gap:5px;flex-wrap:wrap}
.badge{font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;letter-spacing:.03em;display:inline-flex;align-items:center;gap:3px}
.badge-tag{background:rgba(10,37,64,0.07);color:var(--navy)}
.badge-stock{background:rgba(5,150,105,0.1);color:#059669;border:1px solid rgba(5,150,105,0.25)}
.badge-low{background:rgba(245,158,11,0.1);color:#d97706;border:1px solid rgba(245,158,11,0.25)}
.cart-item-unit-price{text-align:right;min-width:130px}
.cart-item-unit-price .label{font-size:10px;color:var(--text-muted);font-weight:500;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px}
.cart-item-unit-price .price{font-size:15px;font-weight:700;color:var(--navy)}
.qty-stepper{display:flex;align-items:center;gap:0;border:1.5px solid var(--border);border-radius:8px;overflow:hidden;background:#fff}
.qty-btn{width:34px;height:34px;background:transparent;border:none;cursor:pointer;font-size:14px;color:var(--navy);display:flex;align-items:center;justify-content:center;transition:background .15s,color .15s;font-weight:600}
.qty-btn:hover{background:var(--bg);color:var(--orange)}
.qty-input{width:40px;height:34px;border:none;border-left:1.5px solid var(--border);border-right:1.5px solid var(--border);text-align:center;font-family:'Inter',sans-serif;font-size:14px;font-weight:700;color:var(--navy);outline:none;background:#fff}
.cart-item-line-total{text-align:right;min-width:140px}
.cart-item-line-total .label{font-size:10px;color:var(--text-muted);font-weight:500;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px}
.cart-item-line-total .total{font-size:17px;font-weight:800;color:var(--navy)}
.cart-item-remove-col{display:flex;align-items:center;justify-content:center}
.btn-remove{width:32px;height:32px;background:transparent;border:1.5px solid var(--border);border-radius:7px;cursor:pointer;font-size:13px;color:var(--text-muted);display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0}
.btn-remove:hover{border-color:#ef4444;color:#ef4444;background:rgba(239,68,68,0.05)}
.cart-footer{padding:16px 24px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#fafbfd}
.continue-shopping{display:inline-flex;align-items:center;gap:7px;color:var(--orange);font-size:13px;font-weight:600;text-decoration:none;transition:gap .2s,color .2s}
.continue-shopping:hover{gap:10px;color:var(--orange-dark)}
.cart-total-items{font-size:13px;color:var(--text-secondary);font-weight:500}
.quote-section .section-card-header{display:flex;align-items:center;justify-content:space-between}
.optional-badge{font-size:11px;font-weight:600;color:var(--text-muted);background:var(--bg);border:1px solid var(--border);padding:3px 9px;border-radius:20px}
.quote-body{padding:24px;display:flex;flex-direction:column;gap:20px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-label{font-size:13px;font-weight:600;color:var(--navy);display:flex;align-items:center;gap:7px}
.form-label i{color:var(--orange);font-size:12px}
.form-hint{font-size:11px;color:var(--text-muted)}
.form-textarea{width:100%;border:1.5px solid var(--border);border-radius:9px;padding:14px 16px;font-family:'Inter',sans-serif;font-size:13px;color:var(--navy);background:#fff;resize:vertical;min-height:110px;outline:none;transition:border-color .2s,box-shadow .2s;line-height:1.6}
.form-textarea:focus{border-color:var(--navy);box-shadow:0 0 0 3px rgba(10,37,64,0.06)}
.form-textarea::placeholder{color:var(--text-muted)}
.quick-tags{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px}
.quick-tag{font-size:12px;font-weight:500;color:var(--text-secondary);background:var(--bg);border:1.5px solid var(--border);padding:5px 11px;border-radius:20px;cursor:pointer;transition:all .2s}
.quick-tag:hover{border-color:var(--orange);color:var(--orange);background:rgba(240,90,34,0.04)}
.quick-tag.selected{border-color:var(--navy);color:var(--navy);background:rgba(10,37,64,0.06)}
.lpo-upload-zone{border:2px dashed var(--border);border-radius:10px;padding:24px 20px;text-align:center;background:#fafbfd;transition:border-color .2s,background .2s;cursor:pointer;position:relative}
.lpo-upload-zone:hover{border-color:var(--navy);background:rgba(10,37,64,0.02)}
.lpo-upload-zone input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer}
.lpo-upload-icon{width:48px;height:48px;background:rgba(10,37,64,0.06);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--navy);margin:0 auto 12px}
.lpo-upload-zone:hover .lpo-upload-icon{background:rgba(240,90,34,0.1);color:var(--orange)}
.lpo-upload-text{font-size:13px;font-weight:600;color:var(--navy);margin-bottom:4px}
.lpo-upload-text span{color:var(--orange)}
.lpo-upload-hint{font-size:11px;color:var(--text-muted)}
.lpo-file-info{display:none;align-items:center;gap:10px;padding:10px 14px;background:var(--green-bg);border:1px solid var(--green-border);border-radius:8px;margin-top:10px}
.lpo-file-info.visible{display:flex}
.lpo-file-info i{color:var(--green);font-size:16px}
.lpo-file-info span{font-size:13px;font-weight:500;color:var(--navy);flex:1}
.lpo-file-remove-btn{background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:14px;padding:2px;border-radius:4px;transition:color .2s}
.lpo-file-remove-btn:hover{color:#ef4444}
.right-col{position:sticky;top:90px;display:flex;flex-direction:column;gap:20px}
.summary-card{background:var(--card);border-radius:14px;border:1px solid var(--border);box-shadow:var(--shadow-md);overflow:hidden}
.summary-header{padding:18px 24px 16px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,var(--navy)0%,var(--navy-light)100%)}
.summary-header h3{font-size:15px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px}
.summary-header h3 i{color:rgba(255,255,255,0.6);font-size:14px}
.summary-body{padding:22px 24px}
.summary-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.summary-row .label{font-size:13px;color:var(--text-secondary);font-weight:500;display:flex;align-items:center;gap:6px}
.summary-row .value{font-size:13px;font-weight:600;color:var(--navy)}
.summary-row .value.muted{font-size:12px;font-weight:500;color:var(--text-muted)}
.bulk-discount-row{background:var(--green-bg);border:1px solid var(--green-border);border-radius:8px;padding:10px 14px;margin:14px 0;display:flex;align-items:center;justify-content:space-between}
.bulk-discount-row .label{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:var(--green)}
.bulk-discount-row .label i{font-size:13px}
.bulk-discount-row .value{font-size:14px;font-weight:800;color:var(--green)}
.summary-divider{height:1px;background:var(--border);margin:16px 0}
.summary-total-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0 4px}
.summary-total-row .label{font-size:15px;font-weight:700;color:var(--navy)}
.summary-total-row .value{font-size:22px;font-weight:800;color:var(--navy);letter-spacing:-0.02em}
.summary-vat-note{font-size:11px;color:var(--text-muted);text-align:right;margin-bottom:20px}
.summary-actions{padding:0 24px 24px;display:flex;flex-direction:column;gap:10px}
.btn-checkout{width:100%;background:var(--orange);color:#fff;border:none;padding:14px 20px;border-radius:9px;font-family:'Inter',sans-serif;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s}
.btn-checkout:hover{background:var(--orange-dark);transform:translateY(-2px);box-shadow:0 6px 20px rgba(240,90,34,0.35)}
.btn-quote{width:100%;background:transparent;color:var(--navy);border:2px solid var(--navy);padding:12px 20px;border-radius:9px;font-family:'Inter',sans-serif;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s}
.btn-quote:hover{background:var(--navy);color:#fff;transform:translateY(-1px);box-shadow:0 4px 12px rgba(10,37,64,0.2)}
.btn-note{font-size:11px;color:var(--text-muted);text-align:center;display:flex;align-items:center;justify-content:center;gap:5px}
.secure-badges{padding:14px 24px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:center;gap:16px}
.secure-item{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-muted);font-weight:500}
.secure-item i{font-size:12px;color:var(--green)}
.why-card{background:var(--card);border-radius:14px;border:1px solid var(--border);box-shadow:var(--shadow-sm);overflow:hidden}
.why-card-header{padding:16px 20px 14px;border-bottom:1px solid var(--border);background:#fafbfd}
.why-card-header h3{font-size:14px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:7px}
.why-card-header h3 i{color:var(--orange)}
.why-items{padding:6px 8px}
.why-item{display:flex;align-items:flex-start;gap:12px;padding:12px;border-radius:8px;transition:background .15s}
.why-item:hover{background:#fafbfd}
.why-icon{width:36px;height:36px;background:rgba(10,37,64,0.06);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;color:var(--navy);flex-shrink:0;transition:background .2s,color .2s}
.why-item:hover .why-icon{background:rgba(240,90,34,0.1);color:var(--orange)}
.why-text h4{font-size:13px;font-weight:700;color:var(--navy);margin-bottom:2px}
.why-text p{font-size:12px;color:var(--text-secondary);line-height:1.5}
.help-card{background:linear-gradient(135deg,var(--navy)0%,var(--navy-light)100%);border-radius:14px;padding:20px;display:flex;align-items:center;gap:14px}
.help-icon{width:44px;height:44px;background:rgba(240,90,34,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--orange);flex-shrink:0}
.help-text h4{font-size:13px;font-weight:700;color:#fff;margin-bottom:2px}
.help-text p{font-size:12px;color:rgba(255,255,255,0.6)}
.help-btn{margin-left:auto;background:var(--orange);color:#fff;border:none;padding:8px 14px;border-radius:7px;font-family:'Inter',sans-serif;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0;transition:background .2s;text-decoration:none}
.help-btn:hover{background:var(--orange-dark)}
.empty-cart{display:<?php echo empty($cartItems) ? 'block' : 'none'; ?>;padding:60px 24px;text-align:center}
.empty-icon{width:72px;height:72px;background:rgba(10,37,64,0.06);border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:28px;color:var(--text-muted);margin:0 auto 16px}
.empty-cart h3{font-size:18px;font-weight:700;color:var(--navy);margin-bottom:8px}
.empty-cart p{font-size:14px;color:var(--text-secondary);margin-bottom:20px}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px}
.alert-success{background:rgba(5,150,105,0.1);color:#059669;border:1px solid rgba(5,150,105,0.2)}
.user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}.reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
@media(max-width:1200px){.page-wrap{padding:28px 32px 48px}.cart-layout{grid-template-columns:1fr 360px;gap:20px}.breadcrumb-bar{padding:14px 32px}.navbar{padding:0 32px}}
@media(max-width:1024px){.cart-layout{grid-template-columns:1fr}.right-col{position:static}}
@media(max-width:768px){.navbar{padding:0 20px;height:60px}.nav-links{display:none}.hamburger{display:block}.page-wrap{padding:20px 20px 40px}.breadcrumb-bar{padding:14px 20px}.cart-item{grid-template-columns:auto 1fr auto;grid-template-rows:auto auto auto;gap:10px 12px}.cart-item-unit-price,.cart-item-line-total,.cart-item-remove-col{grid-column:span 1}.section-card-header{flex-wrap:wrap;gap:10px}.summary-total-row .value{font-size:18px}}
</style>
</head>
<body>

<div class="announcement-bar"><i class="fas fa-truck"></i> Free delivery on orders above TSh 500,000 <span>|</span> Serving all regions in Tanzania</div>

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
    <a href="cart.php">Cart</a>
  </div>
  <div class="nav-actions">
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
    <a href="index.php">Home</a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Cart &amp; Quote Request</span>
  </div>
</div>

<div class="page-wrap reveal">
  <div class="page-header">
    <h1>
      <i class="fas fa-shopping-cart brand-icon"></i>
      Your Cart / Quote Request
      <span class="item-count"><?php echo $itemCount; ?> Item<?php echo $itemCount !== 1 ? 's' : ''; ?></span>
    </h1>
  </div>

  <?php if (isset($quoteSuccess)): ?>
  <div class="alert alert-success" style="max-width:1632px;margin:0 auto 16px;"><i class="fas fa-check-circle"></i> Quote request <strong><?php echo $quoteSuccess; ?></strong> submitted successfully! We will respond within 2-4 business hours.</div>
  <?php endif; ?>

  <div class="cart-layout">

    <!-- ═══ LEFT COLUMN ═══ -->
    <div class="left-col">

      <!-- Cart Items Card -->
      <div class="section-card">
        <div class="section-card-header">
          <h2><i class="fas fa-box-open"></i> Cart Items</h2>
          <?php if (!empty($cartItems)): ?>
          <label class="select-all-row">
            <input type="checkbox" id="selectAll" checked>
            <span>Select All</span>
          </label>
          <?php endif; ?>
        </div>

        <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
          <div class="empty-icon"><i class="fas fa-shopping-cart"></i></div>
          <h3>Your cart is empty</h3>
          <p>Browse our catalog to add network equipment to your cart.</p>
          <a href="catalog.php" style="display:inline-flex;align-items:center;gap:7px;background:var(--orange);color:#fff;padding:11px 22px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;transition:background .2s;">
            <i class="fas fa-th-large"></i> Browse Products
          </a>
        </div>
        <?php else: ?>
        <div class="cart-items-list">
          <?php
          $tags = [];
          foreach ($cartItems as $item):
            $itemTags = array_filter(array_map('trim', explode(',', $item['tags'] ?? '')));
            $stockStatus = $item['stock_status'] ?? 'in_stock';
          ?>
          <form method="POST" class="cart-item" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>">
            <div class="cart-item-checkbox">
              <input type="checkbox" class="item-checkbox" checked>
            </div>
            <div class="cart-item-image">
              <?php echo imageOrPlaceholder($item['image'] ?? '', $item['name'], $item['brand'] ?? ''); ?>
            </div>
            <div class="cart-item-info">
              <?php if (!empty($item['brand'])): ?>
              <div class="cart-item-brand"><?php echo htmlspecialchars($item['brand']); ?></div>
              <?php endif; ?>
              <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
              <div class="cart-item-sku">SKU: <?php echo htmlspecialchars($item['sku']); ?></div>
              <div class="cart-item-badges">
                <?php foreach (array_slice($itemTags, 0, 3) as $tag): ?>
                <span class="badge badge-tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                <?php endforeach; ?>
                <span class="badge <?php echo $stockStatus === 'in_stock' ? 'badge-stock' : 'badge-low'; ?>">
                  <i class="fas fa-circle" style="font-size:6px;"></i>
                  <?php echo $stockStatus === 'in_stock' ? 'In Stock' : ($stockStatus === 'low_stock' ? 'Low Stock' : 'Out of Stock'); ?>
                </span>
              </div>
            </div>
            <div class="cart-item-unit-price">
              <div class="label">Unit Price</div>
              <div class="price">TSh <?php echo number_format((float)$item['price'], 0, '.', ','); ?></div>
            </div>
            <div class="qty-stepper">
              <button type="button" class="qty-btn" onclick="updateQty(this, <?php echo $item['id']; ?>, -1)"><i class="fas fa-minus" style="font-size:11px;"></i></button>
              <input type="number" class="qty-input" value="<?php echo $item['qty']; ?>" min="1" max="999" data-id="<?php echo $item['id']; ?>" onchange="updateQtyInput(this)">
              <button type="button" class="qty-btn" onclick="updateQty(this, <?php echo $item['id']; ?>, 1)"><i class="fas fa-plus" style="font-size:11px;"></i></button>
            </div>
            <div class="cart-item-line-total">
              <div class="label">Line Total</div>
              <div class="total">TSh <?php echo number_format($item['price'] * $item['qty'], 0, '.', ','); ?></div>
            </div>
            <div class="cart-item-remove-col">
              <button type="submit" name="action" value="remove" class="btn-remove" title="Remove item"><i class="fas fa-trash-alt"></i></button>
            </div>
            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
          </form>
          <?php endforeach; ?>
        </div>

        <div class="cart-footer">
          <a href="catalog.php" class="continue-shopping"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
          <span class="cart-total-items"><?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?> in cart</span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Quote Request Section -->
      <?php if (!empty($cartItems)): ?>
      <div class="section-card quote-section">
        <div class="section-card-header">
          <h2><i class="fas fa-file-alt"></i> Request for Quote</h2>
          <span class="optional-badge">Optional</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="submit_quote">
          <div class="quote-body">
            <div class="form-group">
              <label class="form-label"><i class="fas fa-comment-alt"></i> Notes &amp; Custom Requirements</label>
              <div class="form-hint">Delivery location, installation needs, bulk requirements, payment terms, etc.</div>
              <div class="quick-tags">
                <span class="quick-tag" onclick="addQuickTag(this,'Bulk order discount required')">Bulk discount required</span>
                <span class="quick-tag" onclick="addQuickTag(this,'Installation service needed')">Installation service</span>
                <span class="quick-tag" onclick="addQuickTag(this,'Credit terms 30 days')">Credit terms 30 days</span>
                <span class="quick-tag" onclick="addQuickTag(this,'Urgent delivery required')">Urgent delivery</span>
                <span class="quick-tag" onclick="addQuickTag(this,'Delivery to upcountry required')">Upcountry delivery</span>
              </div>
              <textarea name="notes" class="form-textarea" id="quoteNotes" placeholder="e.g. Please confirm delivery to Mwanza City. We require installation services for the access points."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label"><i class="fas fa-file-upload"></i> Attach LPO / Tender Document</label>
              <div class="form-hint">Upload your Local Purchase Order, tender document, or project specs (PDF, DOC, XLS — max 10MB)</div>
              <div class="lpo-upload-zone" id="uploadZone">
                <input type="file" name="lpo_file" id="lpoFile" accept=".pdf,.doc,.docx,.xls,.xlsx" onchange="handleFileUpload(this)">
                <div class="lpo-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <div class="lpo-upload-text"><span>Click to upload</span> or drag and drop</div>
                <div class="lpo-upload-hint">PDF, DOC, DOCX, XLS, XLSX — max 10MB</div>
              </div>
              <div class="lpo-file-info" id="lpoFileInfo">
                <i class="fas fa-file-check"></i>
                <span id="lpoFileName">—</span>
                <button type="button" class="lpo-file-remove-btn" onclick="removeFile()"><i class="fas fa-times"></i></button>
              </div>
            </div>
            <button type="submit" class="btn-quote"><i class="fas fa-file-signature"></i> Submit Quote Request</button>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <!-- ═══ RIGHT COLUMN ═══ -->
    <div class="right-col">

      <!-- Order Summary Card -->
      <div class="summary-card">
        <div class="summary-header">
          <h3><i class="fas fa-receipt"></i> Order Summary</h3>
        </div>
        <div class="summary-body">
          <div class="summary-row">
            <span class="label"><i class="fas fa-box" style="color:var(--text-muted);font-size:12px;"></i> Subtotal (<?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?>)</span>
            <span class="value">TSh <?php echo number_format($subtotal, 0, '.', ','); ?></span>
          </div>

          <?php if ($discount > 0): ?>
          <div class="bulk-discount-row">
            <span class="label"><i class="fas fa-tags"></i> Bulk Discount (5%)</span>
            <span class="value">−TSh <?php echo number_format($discount, 0, '.', ','); ?></span>
          </div>
          <?php endif; ?>

          <div class="summary-row">
            <span class="label"><i class="fas fa-percent" style="color:var(--text-muted);font-size:11px;"></i> After Discount</span>
            <span class="value">TSh <?php echo number_format($afterDiscount, 0, '.', ','); ?></span>
          </div>

          <div class="summary-row">
            <span class="label"><i class="fas fa-receipt" style="color:var(--text-muted);font-size:11px;"></i> VAT (18%)</span>
            <span class="value">TSh <?php echo number_format($vat, 0, '.', ','); ?></span>
          </div>

          <div class="summary-row">
            <span class="label"><i class="fas fa-truck" style="color:var(--text-muted);font-size:11px;"></i> Shipping</span>
            <span class="value muted">Calculated at checkout</span>
          </div>

          <div class="summary-divider"></div>

          <div class="summary-total-row">
            <span class="label">Total (incl. VAT)</span>
            <span class="value">TSh <?php echo number_format($total, 0, '.', ','); ?></span>
          </div>
          <div class="summary-vat-note">All prices in Tanzanian Shillings · 18% VAT included</div>
        </div>

        <div class="summary-actions">
          <?php if (!empty($cartItems)): ?>
          <a href="/checkout.php" class="btn-checkout"><i class="fas fa-lock"></i> Proceed to Checkout</a>
          <?php endif; ?>
          <div class="btn-note">
            <i class="fas fa-info-circle"></i>
            Quote requests receive a response within 2–4 business hours
          </div>
        </div>

        <div class="secure-badges">
          <div class="secure-item"><i class="fas fa-shield-alt"></i> Secure</div>
          <div class="secure-item"><i class="fas fa-lock"></i> Encrypted</div>
          <div class="secure-item"><i class="fas fa-check-circle"></i> Genuine Products</div>
        </div>
      </div>

      <!-- Why Buy From Us -->
      <div class="why-card">
        <div class="why-card-header">
          <h3><i class="fas fa-star"></i> Why Buy From <?php echo $brandName; ?>?</h3>
        </div>
        <div class="why-items">
          <div class="why-item">
            <div class="why-icon"><i class="fas fa-certificate"></i></div>
            <div class="why-text">
              <h4>Genuine Products</h4>
              <p>All products are sourced directly from authorised distributors. 100% authentic with manufacturer warranties.</p>
            </div>
          </div>
          <div class="why-item">
            <div class="why-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="why-text">
              <h4>Credit Terms Available</h4>
              <p>Flexible 30/60-day credit terms available for verified businesses, ISPs and government entities.</p>
            </div>
          </div>
          <div class="why-item">
            <div class="why-icon"><i class="fas fa-user-tie"></i></div>
            <div class="why-text">
              <h4>Dedicated Account Manager</h4>
              <p>Every B2B client gets a dedicated account manager for pre-sales advice and after-sales support.</p>
            </div>
          </div>
          <div class="why-item">
            <div class="why-icon"><i class="fas fa-tags"></i></div>
            <div class="why-text">
              <h4>Bulk &amp; Tender Pricing</h4>
              <p>Volume discounts for large orders, government tenders, and repeat enterprise procurement.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Need Help Card -->
      <div class="help-card">
        <div class="help-icon"><i class="fas fa-headset"></i></div>
        <div class="help-text">
          <h4>Need Help?</h4>
          <p>Talk to a technical sales specialist right now</p>
        </div>
        <a href="tel:+255763364721" class="help-btn"><i class="fas fa-phone-alt"></i> Call Us</a>
      </div>

    </div>
    <!-- END RIGHT COLUMN -->

  </div>
</div>

<footer style="background:var(--navy-dark);padding:64px 48px 0;border-top:1px solid rgba(255,255,255,0.06);">
  <div style="max-width:1632px;margin:0 auto;">
    <div style="display:grid;grid-template-columns:280px 1fr 1fr 300px;gap:48px;padding-bottom:48px;border-bottom:1px solid rgba(255,255,255,0.08);">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <div style="width:36px;height:36px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:17px;color:#fff;"><i class="fas fa-network-wired"></i></div>
          <span style="font-size:18px;font-weight:800;color:#fff;">BN-Infrastructure</span>
        </div>
        <p style="font-size:13px;color:rgba(255,255,255,0.5);line-height:1.7;margin-bottom:20px;">Tanzania's leading B2B network infrastructure supplier. Empowering businesses with enterprise-grade connectivity solutions since 2012.</p>
        <div style="display:flex;gap:8px;">
          <a href="https://linkedin.com" target="_blank" style="width:34px;height:34px;background:rgba(255,255,255,0.06);border-radius:7px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:14px;text-decoration:none;transition:all .2s;" onmouseover="this.style.background='var(--orange)';this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='rgba(255,255,255,0.5)';"><i class="fab fa-linkedin-in"></i></a>
          <a href="https://twitter.com" target="_blank" style="width:34px;height:34px;background:rgba(255,255,255,0.06);border-radius:7px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:14px;text-decoration:none;transition:all .2s;" onmouseover="this.style.background='var(--orange)';this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='rgba(255,255,255,0.5)';"><i class="fab fa-twitter"></i></a>
          <a href="https://facebook.com" target="_blank" style="width:34px;height:34px;background:rgba(255,255,255,0.06);border-radius:7px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:14px;text-decoration:none;transition:all .2s;" onmouseover="this.style.background='var(--orange)';this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='rgba(255,255,255,0.5)';"><i class="fab fa-facebook-f"></i></a>
          <a href="https://wa.me/255763364721" target="_blank" style="width:34px;height:34px;background:rgba(255,255,255,0.06);border-radius:7px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:14px;text-decoration:none;transition:all .2s;" onmouseover="this.style.background='var(--orange)';this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='rgba(255,255,255,0.5)';"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>
      <div>
        <h4 style="font-size:13px;font-weight:700;color:#fff;letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px;">Quick Links</h4>
        <ul style="list-style:none;display:flex;flex-direction:column;gap:10px;">
          <li><a href="catalog.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> All Products</a></li>
          <li><a href="catalog.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> New Arrivals</a></li>
          <li><a href="catalog.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> Best Sellers</a></li>
          <li><a href="catalog.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> Special Offers</a></li>
          <li><a href="cart.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> Request a Quote</a></li>
          <li><a href="about.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> Bulk Orders</a></li>
        </ul>
      </div>
      <div>
        <h4 style="font-size:13px;font-weight:700;color:#fff;letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px;">Company</h4>
        <ul style="list-style:none;display:flex;flex-direction:column;gap:10px;">
          <li><a href="about.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> About BN-Infrastructure</a></li>
          <li><a href="catalog.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> Our Brands</a></li>
          <li><a href="about.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> Solutions</a></li>
          <li><a href="about.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> Blog &amp; Resources</a></li>
          <li><a href="about.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> Careers</a></li>
          <li><a href="privacy.php" style="font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.5)'"><i class="fas fa-chevron-right" style="font-size:11px;color:var(--orange);opacity:.7;"></i> Privacy Policy</a></li>
        </ul>
      </div>
      <div>
        <h4 style="font-size:13px;font-weight:700;color:#fff;letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px;">Contact Us</h4>
        <div style="display:flex;gap:10px;margin-bottom:12px;">
          <i class="fas fa-map-marker-alt" style="color:var(--orange);font-size:14px;margin-top:2px;flex-shrink:0;"></i>
          <span style="font-size:13px;color:rgba(255,255,255,0.5);line-height:1.5;">Plot 45, Mikocheni Light Industrial Area, Dar es Salaam, Tanzania</span>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:12px;">
          <i class="fas fa-phone-alt" style="color:var(--orange);font-size:14px;margin-top:2px;flex-shrink:0;"></i>
          <span style="font-size:13px;color:rgba(255,255,255,0.5);">+255 763 364 721<br>+255 763 364 721</span>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:20px;">
          <i class="fas fa-envelope" style="color:var(--orange);font-size:14px;margin-top:2px;flex-shrink:0;"></i>
          <span style="font-size:13px;color:rgba(255,255,255,0.5);">sales@bn-infrastructure.com</span>
        </div>
      </div>
    </div>
    <div style="padding:20px 0;display:flex;align-items:center;justify-content:space-between;">
      <p style="font-size:12px;color:rgba(255,255,255,0.35);">&copy; 2024 BN-Infrastructure Ltd. All rights reserved. | All prices in Tanzanian Shillings (TSh) excl. 18% VAT</p>
      <div style="display:flex;gap:20px;">
        <a href="terms.php" style="font-size:12px;color:rgba(255,255,255,0.35);text-decoration:none;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">Terms of Service</a>
        <a href="privacy.php" style="font-size:12px;color:rgba(255,255,255,0.35);text-decoration:none;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">Privacy Policy</a>
        <a href="shipping.php" style="font-size:12px;color:rgba(255,255,255,0.35);text-decoration:none;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">Shipping Policy</a>
        <a href="returns.php" style="font-size:12px;color:rgba(255,255,255,0.35);text-decoration:none;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">Returns</a>
      </div>
    </div>
  </div>
</footer>

<script>
function updateQty(btn, id, delta) {
    const input = btn.parentElement.querySelector('.qty-input');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > 999) val = 999;
    input.value = val;
    submitQtyUpdate(id, val);
}
function updateQtyInput(input) {
    let val = parseInt(input.value);
    if (val < 1) val = 1;
    if (val > 999) val = 999;
    input.value = val;
    submitQtyUpdate(input.dataset.id, val);
}
function submitQtyUpdate(id, qty) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="action" value="update"><input type="hidden" name="id" value="' + id + '"><input type="hidden" name="qty" value="' + qty + '">';
    document.body.appendChild(form);
    form.submit();
}
function toggleMenu(){document.getElementById('mobileNav').classList.toggle('open');document.getElementById('navOverlay').classList.toggle('open')}

// Select all
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.item-checkbox').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
        });
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('item-checkbox')) {
                var all = document.querySelectorAll('.item-checkbox');
                var checked = document.querySelectorAll('.item-checkbox:checked');
                selectAll.checked = all.length === checked.length;
            }
        });
    }
});

// Quick tags
function addQuickTag(el, text) {
    el.classList.toggle('selected');
    var textarea = document.getElementById('quoteNotes');
    var current = textarea.value;
    if (el.classList.contains('selected')) {
        textarea.value = current ? current + '\n\u2022 ' + text : '\u2022 ' + text;
    } else {
        textarea.value = current.replace('\n\u2022 ' + text, '').replace('\u2022 ' + text, '').trim();
    }
}

// File upload
function handleFileUpload(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        document.getElementById('lpoFileName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('lpoFileInfo').classList.add('visible');
        document.getElementById('uploadZone').style.borderColor = 'var(--green)';
        document.getElementById('uploadZone').style.background = 'rgba(5,150,105,0.03)';
    }
}
function removeFile() {
    document.getElementById('lpoFile').value = '';
    document.getElementById('lpoFileInfo').classList.remove('visible');
    document.getElementById('lpoFileName').textContent = '\u2014';
    document.getElementById('uploadZone').style.borderColor = '';
    document.getElementById('uploadZone').style.background = '';
}
var uploadZone = document.getElementById('uploadZone');
if (uploadZone) {
    uploadZone.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('dragover'); });
    uploadZone.addEventListener('dragleave', function() { this.classList.remove('dragover'); });
    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        var file = e.dataTransfer.files[0];
        if (file) {
            document.getElementById('lpoFileName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            document.getElementById('lpoFileInfo').classList.add('visible');
            this.style.borderColor = 'var(--green)';
        }
    });
}
</script>
<script>
<?php echo userMenuJs(); ?>
<?php echo scrollRevealJs(); ?>
</script>
</body></html>