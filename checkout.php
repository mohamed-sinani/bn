<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/cart.php';

$brandName = 'BN-Infrastructure';

if (empty(cartGetItems())) {
    header('Location: /cart.php');
    exit;
}

$action = $_POST['action'] ?? '';
$error = '';

if ($action === 'place_order') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'bank_transfer';
    $notes = trim($_POST['notes'] ?? '');

    if (!$name || !$email || !$phone || !$address || !$city || !$region) {
        $error = 'Please fill in all required fields.';
    } else {
        $items = cartGetItems();
        $subtotal = cartSubtotal();
        $discount = $subtotal >= 10000000 ? round($subtotal * 0.05) : 0;
        $afterDiscount = $subtotal - $discount;
        $vat = round($afterDiscount * 0.18);
        $shipping = $afterDiscount >= 500000 ? 0 : 35000;
        $total = $afterDiscount + $vat + $shipping;
        $orderNum = generateOrderNumber();
        $uid = $_SESSION['user_id'] ?? null;

        $paymentStatus = ($paymentMethod === 'bank_transfer' || $paymentMethod === 'mpesa') ? 'pending' : 'pending';

        execute(
            "INSERT INTO orders (order_number, user_id, full_name, email, phone, company_name, address, city, region, subtotal, discount, vat, shipping, total, payment_method, payment_status, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())",
            [$orderNum, $uid, $name, $email, $phone, $company, $address, $city, $region, $subtotal, $discount, $vat, $shipping, $total, $paymentMethod, $paymentStatus, $notes]
        );

        $orderId = fetchOne("SELECT MAX(id) as id FROM orders")['id'];

        foreach ($items as $item) {
            execute(
                "INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$orderId, $item['id'], $item['name'], $item['sku'], $item['qty'], $item['price'], $item['price'] * $item['qty']]
            );
            execute("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?", [$item['qty'], $item['id']]);
        }

        execute("INSERT INTO order_tracking (order_id, status, note) VALUES (?, 'pending', 'Order placed successfully')", [$orderId]);

        execute(
            "INSERT INTO payments (order_id, amount, payment_method, payment_reference, status, paid_at) VALUES (?, ?, ?, ?, ?, ?)",
            [$orderId, $total, $paymentMethod, 'PAY-' . $orderNum, 'pending', null]
        );

        cartClear();
        $_SESSION['last_order'] = $orderId;

        $orderForEmail = fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if ($orderForEmail && $orderForEmail['email']) {
            sendOrderConfirmation($orderForEmail);
        }

        header('Location: /order-confirmation.php?order=' . $orderNum);
        exit;
    }
}

$items = cartGetItems();
$subtotal = cartSubtotal();
$discount = $subtotal >= 10000000 ? round($subtotal * 0.05) : 0;
$afterDiscount = $subtotal - $discount;
$vat = round($afterDiscount * 0.18);
$shipping = $afterDiscount >= 500000 ? 0 : 35000;
$total = $afterDiscount + $vat + $shipping;
$itemCount = cartCount();

$user = [];
if (isset($_SESSION['user_id'])) {
    $user = fetchOne("SELECT full_name, email, phone FROM users WHERE id = ?", [$_SESSION['user_id']]) ?? [];
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Secure Checkout — <?php echo $brandName; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--navy-light:#133057;--navy-dark:#071a2e;--orange:#F05A22;--orange-dark:#d44d1a;--bg:#F4F6F9;--card:#FFF;--text-primary:#0A2540;--text-secondary:#5a6a7e;--text-muted:#8fa0b3;--border:#e2e8f0;--green:#059669;--green-bg:rgba(5,150,105,0.08);--green-border:rgba(5,150,105,0.2);--shadow-sm:0 1px 3px rgba(10,37,64,0.08);--shadow-md:0 4px 12px rgba(10,37,64,0.1);--shadow-lg:0 10px 30px rgba(10,37,64,0.12)}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-primary);font-size:15px;line-height:1.6}
.navbar{background:var(--navy);padding:0 48px;display:flex;align-items:center;height:70px;position:sticky;top:0;z-index:1000;box-shadow:0 2px 12px rgba(0,0,0,0.2)}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0}
.nav-logo-icon{width:38px;height:38px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff}
.nav-logo-text{display:flex;flex-direction:column;line-height:1.1}
.nav-logo-text .brand{font-size:18px;font-weight:800;color:#fff;letter-spacing:-0.02em}
.nav-logo-text .tagline{font-size:10px;font-weight:400;color:rgba(255,255,255,0.5);letter-spacing:0.08em;text-transform:uppercase}
.hamburger{display:none;background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:6px}
.nav-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999}
.nav-overlay.open{display:block}
.mobile-nav{position:fixed;top:0;right:-280px;width:280px;height:100vh;background:var(--navy);z-index:1001;transition:right .3s;padding:80px 24px 24px;overflow-y:auto}
.mobile-nav.open{right:0}
.mobile-nav a{display:block;color:rgba(255,255,255,0.8);text-decoration:none;padding:12px 0;font-size:15px;font-weight:500;border-bottom:1px solid rgba(255,255,255,0.06)}
.mobile-nav a:hover{color:var(--orange)}
.mobile-nav .close-btn{position:absolute;top:16px;right:16px;background:none;border:none;color:#fff;font-size:24px;cursor:pointer}
.mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}
.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
.user-menu{position:relative;display:inline-block}
.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}
.user-name:hover{background:rgba(255,255,255,0.08)}
.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}
.user-dropdown.show{display:block}
.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}
.user-dropdown a:hover{background:#F4F6F9}
.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}
.reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}

/* breadcrumb */
.breadcrumb-bar{background:var(--card);border-bottom:1px solid var(--border);padding:14px 48px}
.breadcrumb-inner{max-width:1632px;margin:0 auto;display:flex;align-items:center;gap:8px}
.breadcrumb-inner a{font-size:13px;color:var(--text-secondary);text-decoration:none;transition:color .2s}
.breadcrumb-inner a:hover{color:var(--orange)}
.breadcrumb-inner .sep{font-size:11px;color:var(--text-muted)}
.breadcrumb-inner .current{font-size:13px;font-weight:600;color:var(--navy)}

/* stepper */
.stepper-bar{background:var(--card);border-bottom:1px solid var(--border);padding:20px 48px}
.stepper-inner{max-width:1632px;margin:0 auto;display:flex;align-items:center;justify-content:center;gap:0}
.stepper-step{display:flex;align-items:center;gap:10px;padding:0 8px}
.step-circle{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;transition:all .3s}
.step-circle.completed{background:var(--green);color:#fff;box-shadow:0 2px 8px rgba(5,150,105,0.3)}
.step-circle.active{background:var(--orange);color:#fff;box-shadow:0 2px 12px rgba(240,90,34,0.4)}
.step-circle.upcoming{background:var(--bg);color:var(--text-muted);border:2px solid var(--border)}
.step-label{display:flex;flex-direction:column;gap:1px}
.step-label .step-num{font-size:10px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em}
.step-label .step-name{font-size:13px;font-weight:700}
.step-label .step-name.completed-text{color:var(--green)}
.step-label .step-name.active-text{color:var(--orange)}
.step-label .step-name.upcoming-text{color:var(--text-muted)}
.stepper-connector{height:2px;width:80px;flex-shrink:0;background:var(--border);border-radius:1px;margin:0 4px;position:relative;overflow:hidden}
.stepper-connector.filled::after{content:'';position:absolute;inset:0;background:var(--green)}

/* main layout */
.main-content{padding:32px 48px 64px;max-width:1632px;margin:0 auto}
.page-header{margin-bottom:28px;display:flex;align-items:center;gap:14px}
.page-header h1{font-size:26px;font-weight:800;color:var(--navy);letter-spacing:-0.02em;display:flex;align-items:center;gap:12px}
.page-header h1 i{color:var(--green);font-size:22px}
.secure-label{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--green);background:var(--green-bg);border:1px solid var(--green-border);padding:5px 12px;border-radius:20px}
.secure-label i{font-size:11px}
.checkout-layout{display:grid;grid-template-columns:1fr 420px;gap:28px;align-items:start}
.left-col{display:flex;flex-direction:column;gap:22px}
.right-col{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}

/* section card */
.section-card{background:var(--card);border-radius:14px;border:1px solid var(--border);box-shadow:var(--shadow-sm);overflow:hidden}
.section-card-header{padding:18px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.section-card-header h2{font-size:15px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:8px}
.section-card-header h2 i{color:var(--orange);font-size:14px}
.section-number{width:24px;height:24px;background:var(--orange);border-radius:50%;color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* forms */
.form-body{padding:24px}
.form-grid{display:grid;gap:18px}
.form-grid-2{grid-template-columns:1fr 1fr}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
.form-label{font-size:12px;font-weight:700;color:var(--navy);letter-spacing:.02em;text-transform:uppercase}
.required-dot{color:var(--orange)}
.form-input,.form-select,.form-textarea{width:100%;border:1.5px solid var(--border);border-radius:9px;padding:11px 14px;font-family:'Inter',sans-serif;font-size:14px;color:var(--navy);background:#fff;outline:none;transition:border-color .2s,box-shadow .2s}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(240,90,34,0.1)}
.form-input::placeholder,.form-textarea::placeholder{color:var(--text-muted)}
.form-select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235a6a7e' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:36px}
.form-textarea{resize:vertical;min-height:80px;line-height:1.6}
.input-with-icon{position:relative}
.input-with-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;pointer-events:none}
.input-with-icon .form-input{padding-left:38px}
.prefilled-note{font-size:11px;color:var(--green);font-weight:500;display:flex;align-items:center;gap:4px;margin-top:3px}

/* delivery method */
.delivery-options{padding:20px 24px;display:flex;flex-direction:column;gap:12px}
.delivery-option{display:flex;align-items:center;gap:16px;border:2px solid var(--border);border-radius:11px;padding:16px 20px;cursor:pointer;transition:border-color .2s,background .2s,box-shadow .2s;position:relative}
.delivery-option:hover{border-color:rgba(240,90,34,0.4);background:rgba(240,90,34,0.02)}
.delivery-option.selected{border-color:var(--orange);background:rgba(240,90,34,0.04);box-shadow:0 0 0 3px rgba(240,90,34,0.08)}
.delivery-option input[type="radio"]{display:none}
.radio-indicator{width:20px;height:20px;border-radius:50%;border:2px solid var(--border);flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .2s}
.delivery-option.selected .radio-indicator{border-color:var(--orange);background:var(--orange)}
.delivery-option.selected .radio-indicator::after{content:'';width:7px;height:7px;background:#fff;border-radius:50%}
.delivery-icon-wrap{width:44px;height:44px;background:rgba(10,37,64,0.06);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--navy);flex-shrink:0;transition:background .2s,color .2s}
.delivery-option.selected .delivery-icon-wrap{background:rgba(240,90,34,0.12);color:var(--orange)}
.delivery-option-info{flex:1}
.delivery-option-name{font-size:14px;font-weight:700;color:var(--navy);margin-bottom:3px}
.delivery-option-desc{font-size:12px;color:var(--text-secondary)}
.delivery-time-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:4px;display:inline-flex;align-items:center;gap:4px;margin-top:4px}
.badge-standard{background:rgba(10,37,64,0.07);color:var(--navy)}
.badge-express{background:rgba(240,90,34,0.12);color:var(--orange)}
.delivery-price{text-align:right;flex-shrink:0}
.delivery-price .price-label{font-size:10px;color:var(--text-muted);font-weight:500;margin-bottom:2px}
.delivery-price .price-val{font-size:15px;font-weight:800;color:var(--navy)}
.delivery-price .price-val .currency{font-size:11px;font-weight:600;color:var(--text-secondary)}
.popular-badge-corner{position:absolute;top:-1px;right:16px;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#fff;background:var(--orange);padding:3px 8px;border-radius:0 0 6px 6px}

/* payment */
.payment-options{padding:20px 24px;display:flex;flex-direction:column;gap:12px}
.payment-option{display:flex;align-items:flex-start;gap:14px;border:2px solid var(--border);border-radius:11px;padding:16px 20px;cursor:pointer;transition:border-color .2s,background .2s,box-shadow .2s}
.payment-option:hover{border-color:rgba(240,90,34,0.4);background:rgba(240,90,34,0.02)}
.payment-option.selected{border-color:var(--orange);background:rgba(240,90,34,0.04);box-shadow:0 0 0 3px rgba(240,90,34,0.08)}
.payment-option input[type="radio"]{display:none}
.payment-radio-indicator{width:20px;height:20px;border-radius:50%;border:2px solid var(--border);flex-shrink:0;margin-top:2px;display:flex;align-items:center;justify-content:center;transition:all .2s}
.payment-option.selected .payment-radio-indicator{border-color:var(--orange);background:var(--orange)}
.payment-option.selected .payment-radio-indicator::after{content:'';width:7px;height:7px;background:#fff;border-radius:50%}
.payment-icon-wrap{width:42px;height:42px;border-radius:9px;background:rgba(10,37,64,0.06);display:flex;align-items:center;justify-content:center;font-size:17px;color:var(--navy);flex-shrink:0;transition:background .2s,color .2s}
.payment-option.selected .payment-icon-wrap{background:rgba(240,90,34,0.12);color:var(--orange)}
.payment-option-body{flex:1}
.payment-option-top{display:flex;align-items:center;gap:8px;margin-bottom:3px}
.payment-option-name{font-size:14px;font-weight:700;color:var(--navy)}
.most-popular{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;background:var(--green-bg);color:var(--green);border:1px solid var(--green-border);padding:2px 7px;border-radius:4px}
.payment-option-desc{font-size:12px;color:var(--text-secondary);line-height:1.5}
.payment-option-detail{display:none;margin-top:14px;padding-top:14px;border-top:1px solid var(--border)}
.payment-option.selected .payment-option-detail{display:block}
.bank-details-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.bank-detail-item{display:flex;flex-direction:column;gap:2px}
.bank-detail-label{font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em}
.bank-detail-value{font-size:13px;font-weight:600;color:var(--navy)}

/* summary */
.summary-card{background:var(--card);border-radius:14px;border:1px solid var(--border);box-shadow:var(--shadow-md);overflow:hidden}
.summary-header{padding:18px 24px 16px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,var(--navy) 0%,var(--navy-light) 100%)}
.summary-header h3{font-size:15px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px}
.summary-header h3 i{color:rgba(255,255,255,0.6);font-size:14px}
.mini-product-list{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;flex-direction:column;gap:0}
.mini-product-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f0f3f7}
.mini-product-item:last-child{border-bottom:none}
.mini-product-img{width:44px;height:44px;border-radius:8px;overflow:hidden;background:#f0f3f7;border:1px solid var(--border);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:16px}
.mini-product-info{flex:1;min-width:0}
.mini-product-name{font-size:12px;font-weight:600;color:var(--navy);line-height:1.3}
.mini-product-qty{font-size:11px;color:var(--text-muted);font-weight:500;margin-top:2px}
.mini-product-total{font-size:13px;font-weight:700;color:var(--navy);flex-shrink:0;white-space:nowrap}
.summary-body{padding:18px 20px}
.summary-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:11px}
.summary-row:last-of-type{margin-bottom:0}
.summary-row .s-label{font-size:13px;color:var(--text-secondary);font-weight:500;display:flex;align-items:center;gap:6px}
.summary-row .s-value{font-size:13px;font-weight:600;color:var(--navy)}
.summary-row .s-value.green{color:var(--green)}
.bulk-discount-row{background:var(--green-bg);border:1px solid var(--green-border);border-radius:8px;padding:9px 12px;margin:11px 0;display:flex;align-items:center;justify-content:space-between}
.bulk-discount-row .s-label{font-size:12px;font-weight:700;color:var(--green);display:flex;align-items:center;gap:6px}
.bulk-discount-row .s-value{font-size:13px;font-weight:800;color:var(--green)}
.summary-divider{height:1px;background:var(--border);margin:14px 0}
.summary-total-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0 4px}
.summary-total-row .total-label{font-size:15px;font-weight:700;color:var(--navy)}
.summary-total-row .total-value{font-size:22px;font-weight:800;color:var(--navy);letter-spacing:-0.02em}
.summary-total-row .total-value .currency{font-size:13px;font-weight:600;color:var(--text-secondary)}
.summary-vat-note{font-size:11px;color:var(--text-muted);text-align:right;margin-bottom:4px}
.summary-actions{padding:0 20px 20px;display:flex;flex-direction:column;gap:10px}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:14px 20px;border-radius:9px;font-family:'Inter',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .2s}
.btn-place-order{width:100%;background:var(--orange);color:#fff;border:none;padding:15px 20px;border-radius:10px;font-family:'Inter',sans-serif;font-size:16px;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;letter-spacing:-0.01em}
.btn-place-order:hover{background:var(--orange-dark);transform:translateY(-2px);box-shadow:0 8px 24px rgba(240,90,34,0.4)}
.btn-primary{background:var(--orange);color:#fff}
.btn-primary:hover{background:var(--orange-dark);transform:translateY(-1px)}
.btn-outline{background:transparent;color:var(--navy);border:1.5px solid var(--border)}
.btn-outline:hover{background:rgba(10,37,64,0.04)}
.security-badges-row{display:flex;align-items:center;justify-content:center;gap:14px;padding:12px 20px;border-top:1px solid var(--border)}
.sec-badge{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-muted);font-weight:500}
.sec-badge i{font-size:12px;color:var(--green)}
.ssl-note{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px 20px;background:#fafbfd;border-top:1px solid var(--border);font-size:11px;color:var(--text-muted);font-weight:500}
.ssl-note i{color:var(--green);font-size:12px}
.alert-error{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:12px;background:rgba(220,38,38,0.08);color:#dc2626;border:1px solid rgba(220,38,38,0.15)}

/* footer */
footer{background:var(--navy-dark);padding:48px 48px 0;margin-top:48px}
.footer-inner{max-width:1440px;margin:0 auto}
.footer-grid{display:grid;grid-template-columns:280px 1fr 1fr 300px;gap:48px;padding-bottom:48px;border-bottom:1px solid rgba(255,255,255,0.08)}
.footer-brand .logo{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.footer-brand .logo .icon{width:36px;height:36px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:17px;color:#fff}
.footer-brand .logo .name{font-size:18px;font-weight:800;color:#fff}
.footer-brand p{font-size:13px;color:rgba(255,255,255,0.5);line-height:1.7;margin-bottom:20px}
.footer-socials{display:flex;gap:8px}
.footer-social-btn{width:34px;height:34px;background:rgba(255,255,255,0.06);border-radius:7px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:14px;cursor:pointer;transition:all .2s;text-decoration:none}
.footer-social-btn:hover{background:var(--orange);color:#fff}
.footer-col h4{font-size:13px;font-weight:700;color:#fff;letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px}
.footer-col ul{list-style:none;display:flex;flex-direction:column;gap:10px}
.footer-col ul li a{font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;transition:color .2s;display:flex;align-items:center;gap:7px}
.footer-col ul li a:hover{color:rgba(255,255,255,0.9)}
.footer-col ul li a i{font-size:11px;color:var(--orange);opacity:.7}
.contact-item{display:flex;gap:10px;margin-bottom:14px}
.contact-item i{color:var(--orange);font-size:14px;margin-top:2px;flex-shrink:0}
.contact-item span{font-size:13px;color:rgba(255,255,255,0.5);line-height:1.5}
.newsletter-label{font-size:13px;color:#fff;margin-bottom:12px;line-height:1.5}
.newsletter-form{display:flex;gap:8px}
.newsletter-form input{flex:1;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:7px;padding:10px 14px;color:#fff;font-family:'Inter',sans-serif;font-size:13px;outline:none}
.newsletter-form input::placeholder{color:rgba(255,255,255,0.35)}
.newsletter-form input:focus{border-color:rgba(255,255,255,0.3)}
.newsletter-form button{background:var(--orange);color:#fff;border:none;border-radius:7px;padding:10px 16px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;white-space:nowrap}
.newsletter-form button:hover{background:var(--orange-dark)}
.footer-bottom{padding:20px 0;display:flex;align-items:center;justify-content:space-between}
.footer-bottom p{font-size:12px;color:rgba(255,255,255,0.35)}
.footer-bottom-links{display:flex;gap:20px}
.footer-bottom-links a{font-size:12px;color:rgba(255,255,255,0.35);text-decoration:none;transition:color .2s}
.footer-bottom-links a:hover{color:rgba(255,255,255,0.6)}
@media(max-width:1280px){.checkout-layout{grid-template-columns:1fr 380px}.main-content{padding:28px 32px 48px}.breadcrumb-bar,.stepper-bar{padding:14px 32px}.navbar{padding:0 32px}}
@media(max-width:1024px){.checkout-layout{grid-template-columns:1fr}.right-col{position:static}}
@media(max-width:768px){.navbar{padding:0 16px;height:60px}.hamburger{display:block}.main-content{padding:20px 20px 40px}.breadcrumb-bar,.stepper-bar{padding:12px 20px}.form-grid-2{grid-template-columns:1fr}.stepper-connector{width:40px}.bank-details-grid{grid-template-columns:1fr}}
button,a,input,select,textarea{transition:transform .2s,box-shadow .2s,background-color .2s,border-color .2s,color .2s}
button:focus-visible,input:focus-visible,select:focus-visible{outline:2px solid var(--orange);outline-offset:2px}
</style>
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-icon"><i class="fas fa-network-wired"></i></div>
    <div class="nav-logo-text"><span class="brand">BN-Infrastructure</span><span class="tagline">Tanzania</span></div>
  </a>
  <div style="margin-left:auto;font-size:13px;color:#fff;"><i class="fas fa-lock"></i> Secure Checkout</div>
  <button class="hamburger" onclick="toggleMenu()" style="margin-left:12px;"><i class="fas fa-bars"></i></button>
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
    <a href="cart.php">Cart</a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Checkout</span>
  </div>
</div>

<div class="stepper-bar">
  <div class="stepper-inner">
    <div class="stepper-step">
      <div class="step-circle completed"><i class="fas fa-check" style="font-size:14px;"></i></div>
      <div class="step-label">
        <span class="step-num">Step 1</span>
        <span class="step-name completed-text">Cart</span>
      </div>
    </div>
    <div class="stepper-connector filled"></div>
    <div class="stepper-step">
      <div class="step-circle active">2</div>
      <div class="step-label">
        <span class="step-num">Step 2</span>
        <span class="step-name active-text">Checkout</span>
      </div>
    </div>
    <div class="stepper-connector"></div>
    <div class="stepper-step">
      <div class="step-circle upcoming">3</div>
      <div class="step-label">
        <span class="step-num">Step 3</span>
        <span class="step-name upcoming-text">Confirmation</span>
      </div>
    </div>
  </div>
</div>

<div class="main-content">
  <div class="page-header">
    <h1><i class="fas fa-lock"></i> Secure Checkout</h1>
    <div class="secure-label"><i class="fas fa-shield-alt"></i> 256-bit SSL Encrypted</div>
  </div>

  <div class="checkout-layout">

    <div class="left-col">

      <?php if ($error): ?>
      <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST">
      <input type="hidden" name="action" value="place_order">

      <!-- Section 1: Delivery Information -->
      <div class="section-card">
        <div class="section-card-header">
          <h2><div class="section-number">1</div><i class="fas fa-building"></i> Delivery Information</h2>
        </div>
        <div class="form-body">
          <div class="form-grid form-grid-2">
            <div class="form-group full">
              <label class="form-label">Full Name <span class="required-dot">*</span></label>
              <div class="input-with-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($user['full_name'] ?? $_POST['name'] ?? ''); ?>" required placeholder="e.g. James Mwangi">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Email Address <span class="required-dot">*</span></label>
              <div class="input-with-icon">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email'] ?? $_POST['email'] ?? ''); ?>" required placeholder="procurement@company.co.tz">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Phone Number <span class="required-dot">*</span></label>
              <div class="input-with-icon">
                <i class="fas fa-phone-alt"></i>
                <input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required placeholder="+255 7XX XXX XXX">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Company Name</label>
              <div class="input-with-icon">
                <i class="fas fa-building"></i>
                <input type="text" name="company" class="form-input" value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>" placeholder="Safaricom Tanzania Ltd">
              </div>
            </div>
            <div class="form-group full">
              <label class="form-label">Street Address <span class="required-dot">*</span></label>
              <div class="input-with-icon" style="align-items:flex-start;">
                <i class="fas fa-map-marker-alt" style="margin-top:12px;"></i>
                <input type="text" name="address" class="form-input" style="padding-left:38px;" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required placeholder="Street address, building name, floor">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">City <span class="required-dot">*</span></label>
              <div class="input-with-icon">
                <i class="fas fa-city"></i>
                <input type="text" name="city" class="form-input" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required placeholder="e.g. Dar es Salaam">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Region <span class="required-dot">*</span></label>
              <select name="region" class="form-select" required>
                <option value="">Select region</option>
                <?php $regions = ['Arusha','Dar es Salaam','Dodoma','Geita','Iringa','Kagera','Katavi','Kigoma','Kilimanjaro','Lindi','Manyara','Mara','Mbeya','Mjini Magharibi','Morogoro','Mtwara','Mwanza','Njombe','Pemba North','Pemba South','Pwani','Rukwa','Ruvuma','Shinyanga','Simiyu','Singida','Songwe','Tabora','Tanga','Zanzibar North','Zanzibar South'];
                foreach ($regions as $r): ?>
                <option value="<?php echo $r; ?>" <?php echo ($_POST['region'] ?? '') === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group full">
              <label class="form-label">Order Notes</label>
              <textarea name="notes" class="form-textarea" rows="2" placeholder="Special delivery instructions, PO number, etc."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Delivery Method -->
      <div class="section-card">
        <div class="section-card-header">
          <h2><div class="section-number">2</div><i class="fas fa-truck"></i> Delivery Method</h2>
        </div>
        <div class="delivery-options">
          <?php $shippingFee = $shipping; ?>
          <label class="delivery-option <?php echo $shippingFee > 0 ? '' : 'selected'; ?>" id="delivery-standard" onclick="selectDelivery('standard')">
            <input type="radio" name="delivery" value="standard" <?php echo $shippingFee > 0 ? '' : 'checked'; ?>>
            <div class="radio-indicator"></div>
            <div class="delivery-icon-wrap"><i class="fas fa-box"></i></div>
            <div class="delivery-option-info">
              <div class="delivery-option-name">Standard Delivery</div>
              <div class="delivery-option-desc">Delivered to your address anywhere in Tanzania</div>
              <span class="delivery-time-badge badge-standard"><i class="fas fa-clock" style="font-size:9px;"></i> 3–5 Business Days</span>
            </div>
            <div class="delivery-price">
              <div class="price-label">Delivery Fee</div>
              <div class="price-val"><span class="currency">TSh</span> <?php echo $shippingFee > 0 ? number_format($shippingFee,0,'.',',') : '0'; ?></div>
            </div>
          </label>
          <label class="delivery-option <?php echo $shippingFee > 0 ? 'selected' : ''; ?>" id="delivery-express" onclick="selectDelivery('express')">
            <?php if ($shippingFee <= 0): ?><div class="popular-badge-corner">Free</div><?php endif; ?>
            <input type="radio" name="delivery" value="express" <?php echo $shippingFee > 0 ? 'checked' : ''; ?>>
            <div class="radio-indicator"></div>
            <div class="delivery-icon-wrap"><i class="fas fa-shipping-fast"></i></div>
            <div class="delivery-option-info">
              <div class="delivery-option-name">Express Delivery</div>
              <div class="delivery-option-desc">Priority dispatch with real-time tracking</div>
              <span class="delivery-time-badge badge-express"><i class="fas fa-bolt" style="font-size:9px;"></i> 1–2 Business Days</span>
            </div>
            <div class="delivery-price">
              <div class="price-label">Delivery Fee</div>
              <div class="price-val"><span class="currency">TSh</span> <?php echo $shippingFee > 0 ? number_format($shippingFee,0,'.',',') : '0'; ?></div>
            </div>
          </label>
        </div>
      </div>

      <!-- Section 3: Payment Method -->
      <div class="section-card">
        <div class="section-card-header">
          <h2><div class="section-number">3</div><i class="fas fa-credit-card"></i> Payment Method</h2>
        </div>
        <div style="padding:6px 24px 12px;font-size:12px;color:var(--text-muted);">This is a simulation. No real payment will be processed.</div>
        <div class="payment-options">
          <?php
          $selectedPmt = $_POST['payment_method'] ?? 'bank_transfer';
          $pmts = [
            'bank_transfer' => ['label'=>'Bank Transfer','icon'=>'fas fa-university','desc'=>'Direct EFT to BN-Infrastructure business account. Order processed upon payment confirmation.'],
            'mpesa' => ['label'=>'M-Pesa','icon'=>'fas fa-mobile-alt','desc'=>'Pay via M-Pesa. Instant confirmation.'],
            'tigo_pesa' => ['label'=>'Tigo Pesa','icon'=>'fas fa-mobile-alt','desc'=>'Pay via Tigo Pesa. Instant confirmation.'],
            'airtel_money' => ['label'=>'Airtel Money','icon'=>'fas fa-mobile-alt','desc'=>'Pay via Airtel Money. Instant confirmation.'],
            'credit_terms' => ['label'=>'Credit Terms','icon'=>'fas fa-hand-holding-usd','desc'=>'30/60-day payment terms available for verified businesses and government entities.'],
            'lpo' => ['label'=>'Purchase Order (LPO)','icon'=>'fas fa-file-invoice','desc'=>'Upload your Local Purchase Order. Suitable for government, NGOs, and enterprise procurement.']
          ];
          foreach ($pmts as $id => $p):
            $isSelected = $selectedPmt === $id;
          ?>
          <label class="payment-option <?php echo $isSelected ? 'selected' : ''; ?>">
            <input type="radio" name="payment_method" value="<?php echo $id; ?>" onchange="selectPayment(this)" <?php echo $isSelected ? 'checked' : ''; ?>>
            <div class="payment-radio-indicator"></div>
            <div class="payment-icon-wrap"><i class="<?php echo $p['icon']; ?>"></i></div>
            <div class="payment-option-body">
              <div class="payment-option-top">
                <span class="payment-option-name"><?php echo $p['label']; ?></span>
                <?php if ($id === 'bank_transfer'): ?><span class="most-popular"><i class="fas fa-star" style="font-size:8px;"></i> Most Popular</span><?php endif; ?>
              </div>
              <div class="payment-option-desc"><?php echo $p['desc']; ?></div>
              <?php if ($id === 'bank_transfer'): ?>
              <div class="payment-option-detail">
                <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Bank Account Details</div>
                <div class="bank-details-grid">
                  <div class="bank-detail-item"><span class="bank-detail-label">Bank Name</span><span class="bank-detail-value">CRDB Bank PLC</span></div>
                  <div class="bank-detail-item"><span class="bank-detail-label">Account Name</span><span class="bank-detail-value">BN-Infrastructure Tanzania Ltd</span></div>
                  <div class="bank-detail-item"><span class="bank-detail-label">Account Number</span><span class="bank-detail-value">01J1503500700</span></div>
                  <div class="bank-detail-item"><span class="bank-detail-label">Swift Code</span><span class="bank-detail-value">CORUTZTZ</span></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="display:flex;gap:12px;margin-top:16px;">
        <a href="cart.php" class="btn btn-outline" style="width:auto;padding:14px 24px;"><i class="fas fa-arrow-left"></i> Back to Cart</a>
        <button type="submit" class="btn btn-primary" style="flex:1;"><i class="fas fa-lock"></i> Place Order — TSh <?php echo number_format($total,0,'.',','); ?></button>
      </div>
      </form>
    </div>

    <div class="right-col">
      <div class="summary-card">
        <div class="summary-header">
          <h3><i class="fas fa-receipt"></i> Order Summary</h3>
        </div>
        <div class="mini-product-list">
          <?php foreach ($items as $item): ?>
          <div class="mini-product-item">
            <div class="mini-product-img"><i class="fas fa-box"></i></div>
            <div class="mini-product-info">
              <div class="mini-product-name"><?php echo htmlspecialchars($item['name']); ?></div>
              <div class="mini-product-qty">Qty: <?php echo $item['qty']; ?> &times; TSh <?php echo number_format($item['price'],0,'.',','); ?></div>
            </div>
            <div class="mini-product-total">TSh <?php echo number_format($item['price'] * $item['qty'],0,'.',','); ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="summary-body">
          <div class="summary-row">
            <span class="s-label"><i class="fas fa-box" style="color:var(--text-muted);font-size:11px;"></i> Subtotal (<?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?>)</span>
            <span class="s-value">TSh <?php echo number_format($subtotal,0,'.',','); ?></span>
          </div>
          <?php if ($discount > 0): ?>
          <div class="bulk-discount-row">
            <span class="s-label"><i class="fas fa-tags"></i> Bulk Discount (5%)</span>
            <span class="s-value">&minus;TSh <?php echo number_format($discount,0,'.',','); ?></span>
          </div>
          <?php endif; ?>
          <div class="summary-row">
            <span class="s-label"><i class="fas fa-receipt" style="color:var(--text-muted);font-size:11px;"></i> VAT (18%)</span>
            <span class="s-value">TSh <?php echo number_format($vat,0,'.',','); ?></span>
          </div>
          <div class="summary-row">
            <span class="s-label"><i class="fas fa-shipping-fast" style="color:var(--text-muted);font-size:11px;"></i> Shipping</span>
            <span class="s-value"><?php echo $shipping > 0 ? 'TSh '.number_format($shipping,0,'.',',') : '<span class="s-value green">Free</span>'; ?></span>
          </div>
          <div class="summary-divider"></div>
          <div class="summary-total-row">
            <span class="total-label">Grand Total</span>
            <span class="total-value"><span class="currency">TSh</span> <?php echo number_format($total,0,'.',','); ?></span>
          </div>
          <div class="summary-vat-note">All prices in Tanzanian Shillings &middot; 18% VAT included</div>
        </div>
        <div class="security-badges-row">
          <div class="sec-badge"><i class="fas fa-shield-alt"></i> Secure</div>
          <div class="sec-badge"><i class="fas fa-lock"></i> Encrypted</div>
          <div class="sec-badge"><i class="fas fa-check-circle"></i> Genuine</div>
        </div>
        <div class="ssl-note">
          <i class="fas fa-lock"></i> Your order is protected by 256-bit SSL encryption
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
function selectDelivery(type) {
  document.querySelectorAll('.delivery-option').forEach(function(o) { o.classList.remove('selected'); });
  document.getElementById('delivery-' + type).classList.add('selected');
}
function selectPayment(el) {
  document.querySelectorAll('.payment-option').forEach(function(o) { o.classList.remove('selected'); });
  el.closest('.payment-option').classList.add('selected');
}
function toggleMenu() {
  document.getElementById('mobileNav').classList.toggle('open');
  document.getElementById('navOverlay').classList.toggle('open');
}
</script>
<script>
<?php echo userMenuJs(); ?>
<?php echo scrollRevealJs(); ?>
</script>
</body></html>
