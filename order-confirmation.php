<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/cart.php';

$brandName = 'BN-Infrastructure';
$orderNumber = $_GET['order'] ?? '';
$order = null;
$orderItems = [];
$tracking = [];

if ($orderNumber) {
    $order = fetchOne("SELECT * FROM orders WHERE order_number = ?", [$orderNumber]);
    if ($order) {
        $orderItems = fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
        $tracking = fetchAll("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at ASC", [$order['id']]);
    }
}

if (!$order) {
    header('Location: /');
    exit;
}

$itemCount = array_sum(array_column($orderItems, 'quantity'));
$orderDate = date('M d, Y', strtotime($order['created_at']));
$paymentStatus = $order['payment_status'] ?? 'pending';
$isPaid = $paymentStatus === 'paid' || $paymentStatus === 'confirmed';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed — <?php echo $brandName; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A2540;--navy-light:#133057;--navy-dark:#071a2e;--orange:#F05A22;--orange-dark:#d44d1a;--orange-light:#ff6b35;--bg:#F4F6F9;--card:#FFF;--text-primary:#0A2540;--text-secondary:#5a6a7e;--text-muted:#8fa0b3;--border:#e2e8f0;--green:#059669;--green-light:#10b981;--green-bg:rgba(5,150,105,0.08);--green-border:rgba(5,150,105,0.2);--shadow-sm:0 1px 3px rgba(10,37,64,0.08),0 1px 2px rgba(10,37,64,0.04);--shadow-md:0 4px 12px rgba(10,37,64,0.1),0 2px 6px rgba(10,37,64,0.06);--shadow-lg:0 10px 30px rgba(10,37,64,0.12),0 4px 12px rgba(10,37,64,0.08)}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-primary);font-size:15px;line-height:1.6}
.navbar{background:var(--navy);padding:0 48px;display:flex;align-items:center;height:70px;position:sticky;top:0;z-index:1000;box-shadow:0 2px 12px rgba(0,0,0,0.2)}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0}
.nav-logo-icon{width:38px;height:38px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff}
.nav-logo-text{display:flex;flex-direction:column;line-height:1.1}
.nav-logo-text .brand{font-size:18px;font-weight:800;color:#fff;letter-spacing:-0.02em}
.nav-logo-text .tagline{font-size:10px;font-weight:400;color:#fff;letter-spacing:0.08em;text-transform:uppercase}
.hamburger{display:none;background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:6px;margin-left:auto}
.nav-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999}
.nav-overlay.open{display:block}
.mobile-nav{position:fixed;top:0;right:-280px;width:280px;height:100vh;background:var(--navy);z-index:1001;transition:right .3s ease;padding:80px 24px 24px;overflow-y:auto}
.mobile-nav.open{right:0}
.mobile-nav a{display:block;color:rgba(255,255,255,0.8);text-decoration:none;padding:12px 0;font-size:15px;font-weight:500;border-bottom:1px solid rgba(255,255,255,0.06)}
.mobile-nav a:hover{color:var(--orange)}
.mobile-nav .close-btn{position:absolute;top:16px;right:16px;background:none;border:none;color:#fff;font-size:24px;cursor:pointer}
.mobile-nav .mobile-user{display:block;color:var(--orange);padding:12px 0;font-size:15px;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.06)}.mobile-nav .mobile-logout{color:#fff!important;font-size:13px!important}
.user-menu{position:relative;display:inline-block}.user-name{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:13px;font-weight:500;cursor:pointer;padding:6px 12px;border-radius:7px;transition:background .2s;white-space:nowrap}.user-name:hover{background:rgba(255,255,255,0.08)}.user-dropdown{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;z-index:100;overflow:hidden;margin-top:6px}.user-dropdown.show{display:block}.user-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0A2540;font-size:13px;text-decoration:none;transition:background .15s}.user-dropdown a:hover{background:#F4F6F9}.user-dropdown a i{width:16px;color:#F05A22;font-size:12px}
.breadcrumb-bar{background:var(--card);border-bottom:1px solid var(--border);padding:14px 48px}
.breadcrumb-inner{max-width:1632px;margin:0 auto;display:flex;align-items:center;gap:8px}
.breadcrumb-inner a{font-size:13px;color:var(--text-secondary);text-decoration:none;transition:color .2s}
.breadcrumb-inner a:hover{color:var(--orange)}
.breadcrumb-inner .sep{font-size:11px;color:var(--text-muted)}
.breadcrumb-inner .done-crumb{font-size:13px;font-weight:500;color:var(--green)}
.breadcrumb-inner .current{font-size:13px;font-weight:600;color:var(--navy)}
.stepper-bar{background:var(--card);border-bottom:1px solid var(--border);padding:20px 48px}
.stepper-inner{max-width:1632px;margin:0 auto;display:flex;align-items:center;justify-content:center;gap:0}
.stepper-step{display:flex;align-items:center;gap:10px;padding:0 8px}
.step-circle{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;transition:all .3s}
.step-circle.completed{background:var(--green);color:#fff;box-shadow:0 2px 8px rgba(5,150,105,0.3)}
.step-circle.active{background:var(--orange);color:#fff;box-shadow:0 2px 12px rgba(240,90,34,0.4)}
.step-circle.upcoming{background:var(--bg);color:var(--text-muted);border:2px solid var(--border)}
.step-label{display:flex;flex-direction:column;gap:1px}
.step-label .step-num{font-size:10px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em}
.step-label .step-name{font-size:13px;font-weight:700}
.step-label .step-name.completed-text{color:var(--green)}
.step-label .step-name.active-text{color:var(--orange)}
.step-label .step-name.upcoming-text{color:var(--text-muted)}
.stepper-connector{height:2px;width:80px;flex-shrink:0;background:var(--border);border-radius:1px;margin:0 4px;position:relative;overflow:hidden}
.stepper-connector.filled::after{content:'';position:absolute;inset:0;background:var(--green)}
.success-hero{background:linear-gradient(135deg,rgba(5,150,105,0.07) 0%,rgba(5,150,105,0.04) 50%,rgba(16,185,129,0.06) 100%);border:1.5px solid var(--green-border);border-radius:18px;padding:40px 48px;display:flex;align-items:center;gap:32px;margin-bottom:28px;position:relative;overflow:hidden}
.success-hero::before{content:'';position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:radial-gradient(circle,rgba(5,150,105,0.1) 0%,transparent 70%);border-radius:50%}
.success-hero::after{content:'';position:absolute;bottom:-40px;left:40%;width:140px;height:140px;background:radial-gradient(circle,rgba(5,150,105,0.06) 0%,transparent 70%);border-radius:50%}
.success-checkmark{width:80px;height:80px;border-radius:50%;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:34px;color:#fff;flex-shrink:0;box-shadow:0 8px 32px rgba(5,150,105,0.35);animation:checkPop .5s cubic-bezier(0.34,1.56,0.64,1) forwards}
@keyframes checkPop{0%{transform:scale(0);opacity:0}100%{transform:scale(1);opacity:1}}
.success-hero-text{flex:1}
.success-hero-text h1{font-size:30px;font-weight:900;color:var(--navy);letter-spacing:-0.03em;margin-bottom:8px;line-height:1.15;animation:fadeSlideUp .4s ease .15s both}
.success-hero-text p{font-size:15px;color:var(--text-secondary);font-weight:400;margin-bottom:18px;animation:fadeSlideUp .4s ease .25s both}
@keyframes fadeSlideUp{0%{transform:translateY(12px);opacity:0}100%{transform:translateY(0);opacity:1}}
.success-badges{display:flex;align-items:center;gap:10px;flex-wrap:wrap;animation:fadeSlideUp .4s ease .35s both}
.order-number-badge{display:inline-flex;align-items:center;gap:8px;background:var(--orange);color:#fff;padding:8px 16px;border-radius:8px;font-size:14px;font-weight:800;letter-spacing:0.02em}
.order-number-badge i{font-size:12px;opacity:0.8}
.delivery-estimate-badge{display:inline-flex;align-items:center;gap:8px;background:var(--green-bg);color:var(--green);border:1.5px solid var(--green-border);padding:8px 16px;border-radius:8px;font-size:13px;font-weight:700}
.delivery-estimate-badge i{font-size:12px}
.success-confetti-side{display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;animation:fadeSlideUp .4s ease .2s both}
.conf-line{height:4px;border-radius:2px;opacity:0.5}
.main-wrapper{padding:32px 48px 64px}
.main-inner{max-width:1632px;margin:0 auto}
.confirm-layout{display:grid;grid-template-columns:1fr 380px;gap:28px;align-items:start}
.left-col{display:flex;flex-direction:column;gap:22px}
.right-col{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}
.section-card{background:var(--card);border-radius:14px;border:1px solid var(--border);box-shadow:var(--shadow-sm);overflow:hidden}
.section-card-header{padding:18px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.section-card-header h2{font-size:15px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:8px}
.section-card-header h2 i{color:var(--orange);font-size:14px}
.section-number{width:24px;height:24px;background:var(--orange);border-radius:50%;color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.mini-product-list{padding:8px 20px;border-bottom:1px solid var(--border)}
.mini-product-item{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid #f0f3f7}
.mini-product-item:last-child{border-bottom:none}
.mini-product-info{flex:1;min-width:0}
.mini-product-name{font-size:13px;font-weight:600;color:var(--navy);line-height:1.35}
.mini-product-qty{font-size:11px;color:var(--text-muted);font-weight:500;margin-top:2px}
.mini-product-total{font-size:14px;font-weight:700;color:var(--navy);flex-shrink:0;white-space:nowrap}
.summary-body{padding:18px 20px}
.summary-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:11px}
.summary-row:last-of-type{margin-bottom:0}
.summary-row .s-label{font-size:13px;color:var(--text-secondary);font-weight:500;display:flex;align-items:center;gap:6px}
.summary-row .s-value{font-size:13px;font-weight:600;color:var(--navy)}
.bulk-discount-row{background:var(--green-bg);border:1px solid var(--green-border);border-radius:8px;padding:9px 12px;margin:11px 0;display:flex;align-items:center;justify-content:space-between}
.bulk-discount-row .s-label{font-size:12px;font-weight:700;color:var(--green);display:flex;align-items:center;gap:6px}
.bulk-discount-row .s-value{font-size:13px;font-weight:800;color:var(--green)}
.summary-divider{height:1px;background:var(--border);margin:14px 0}
.summary-total-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0 4px}
.summary-total-row .total-label{font-size:15px;font-weight:700;color:var(--navy)}
.summary-total-row .total-value{font-size:24px;font-weight:900;color:var(--navy);letter-spacing:-0.03em}
.summary-total-row .total-value .currency{font-size:13px;font-weight:600;color:var(--text-secondary)}
.summary-vat-note{font-size:11px;color:var(--text-muted);text-align:right;margin-top:4px}
.delivery-details-grid{padding:22px 24px;display:grid;grid-template-columns:1fr 1fr;gap:18px}
.detail-item{display:flex;flex-direction:column;gap:4px}
.detail-item.full{grid-column:1/-1}
.detail-label{font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em}
.detail-value{font-size:14px;font-weight:600;color:var(--navy);line-height:1.4}
.delivery-method-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(240,90,34,0.1);color:var(--orange);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;border:1px solid rgba(240,90,34,0.2)}
.payment-details-body{padding:22px 24px;display:flex;flex-direction:column;gap:18px}
.payment-status-row{display:flex;align-items:center;justify-content:space-between}
.payment-method-info{display:flex;align-items:center;gap:10px}
.payment-icon-box{width:40px;height:40px;background:rgba(10,37,64,0.07);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;color:var(--navy)}
.payment-method-label{font-size:14px;font-weight:700;color:var(--navy)}
.payment-method-sub{font-size:12px;color:var(--text-secondary)}
.awaiting-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(240,90,34,0.1);color:var(--orange);border:1.5px solid rgba(240,90,34,0.25);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700}
.awaiting-badge i{font-size:11px;animation:pulse 1.5s ease infinite}
.paid-badge{display:inline-flex;align-items:center;gap:6px;background:var(--green-bg);color:var(--green);border:1.5px solid var(--green-border);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
.bank-details-box{background:#fafbfd;border:1px solid var(--border);border-radius:10px;padding:16px 18px}
.bank-box-title{font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:12px}
.bank-details-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.bank-detail-item{display:flex;flex-direction:column;gap:2px}
.bank-detail-label{font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em}
.bank-detail-value{font-size:13px;font-weight:700;color:var(--navy)}
.bank-ref-highlight{grid-column:1/-1;background:rgba(240,90,34,0.06);border:1px solid rgba(240,90,34,0.15);border-radius:8px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between}
.bank-ref-highlight .bank-detail-label{color:var(--orange)}
.bank-ref-highlight .bank-detail-value{font-size:14px;color:var(--orange);letter-spacing:0.01em}
.copy-btn{background:none;border:1px solid rgba(240,90,34,0.3);color:var(--orange);padding:4px 10px;border-radius:6px;font-family:'Inter',sans-serif;font-size:11px;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap}
.copy-btn:hover{background:rgba(240,90,34,0.08)}
.payment-instruction-box{background:rgba(10,37,64,0.03);border:1px solid var(--border);border-left:3px solid var(--orange);border-radius:0 8px 8px 0;padding:12px 14px;font-size:12px;color:var(--text-secondary);line-height:1.6}
.payment-instruction-box strong{color:var(--navy)}
.tracking-card{margin-top:0}
.tracking-timeline{padding:18px 24px}
.tracking-item{display:flex;gap:14px;padding-bottom:18px;position:relative}
.tracking-item:not(:last-child)::before{content:'';position:absolute;left:15px;top:30px;bottom:0;width:2px;background:var(--border)}
.tracking-dot{width:30px;height:30px;border-radius:50%;background:var(--bg);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;color:var(--text-muted)}
.tracking-dot.active{background:var(--green-bg);border-color:var(--green);color:var(--green)}
.tracking-content .t-status{font-size:14px;font-weight:600;color:var(--navy)}
.tracking-content .t-note{font-size:12px;color:var(--text-muted);margin-top:2px}
.tracking-content .t-time{font-size:11px;color:var(--text-muted);margin-top:2px}
.next-steps-card{background:var(--card);border-radius:14px;border:1px solid var(--border);box-shadow:var(--shadow-md);overflow:hidden}
.next-steps-header{padding:18px 22px 16px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,var(--navy) 0%,var(--navy-light) 100%)}
.next-steps-header h3{font-size:15px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px}
.next-steps-header h3 i{color:rgba(255,255,255,0.6)}
.next-steps-list{padding:16px 22px;display:flex;flex-direction:column}
.next-step-item{display:flex;gap:14px;padding:14px 0;position:relative}
.next-step-item:not(:last-child)::after{content:'';position:absolute;left:18px;top:46px;width:2px;height:calc(100% - 16px);background:var(--border)}
.next-step-item.active:not(:last-child)::after{background:rgba(240,90,34,0.2)}
.step-icon-circle{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;z-index:1}
.step-icon-circle.active{background:var(--orange);color:#fff;box-shadow:0 2px 10px rgba(240,90,34,0.35)}
.step-icon-circle.pending{background:#f0f3f7;color:var(--text-muted)}
.step-icon-circle.done{background:var(--green);color:#fff}
.next-step-content{flex:1;padding-top:2px}
.next-step-name{font-size:13px;font-weight:700;margin-bottom:3px;line-height:1.2}
.next-step-name.active{color:var(--orange)}
.next-step-name.pending{color:var(--text-secondary)}
.next-step-desc{font-size:12px;color:var(--text-muted);line-height:1.5}
.step-status-pill{font-size:10px;font-weight:700;padding:2px 8px;border-radius:4px;display:inline-block;margin-bottom:4px}
.pill-active{background:rgba(240,90,34,0.1);color:var(--orange)}
.pill-pending{background:#f0f3f7;color:var(--text-muted)}
.right-actions{display:flex;flex-direction:column;gap:10px;padding:0 22px 20px}
.btn-track{width:100%;background:var(--orange);color:#fff;border:none;padding:13px 20px;border-radius:10px;font-family:'Inter',sans-serif;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;letter-spacing:-0.01em;text-decoration:none}
.btn-track:hover{background:var(--orange-dark);transform:translateY(-1px);box-shadow:0 6px 20px rgba(240,90,34,0.35)}
.btn-invoice{width:100%;background:transparent;color:var(--navy);border:2px solid var(--navy);padding:11px 20px;border-radius:10px;font-family:'Inter',sans-serif;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;text-decoration:none}
.btn-invoice:hover{background:var(--navy);color:#fff;transform:translateY(-1px)}
.manager-card{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-light) 100%);border-radius:14px;padding:22px;overflow:hidden;position:relative}
.manager-card::before{content:'';position:absolute;top:-30px;right:-30px;width:100px;height:100px;background:rgba(255,255,255,0.04);border-radius:50%}
.manager-card-top{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.manager-avatar{width:44px;height:44px;border-radius:50%;overflow:hidden;border:2px solid rgba(255,255,255,0.2);flex-shrink:0;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;font-size:18px;color:rgba(255,255,255,0.6)}
.manager-info{flex:1}
.manager-label{font-size:10px;font-weight:600;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.08em}
.manager-name{font-size:15px;font-weight:700;color:#fff}
.manager-role{font-size:12px;color:rgba(255,255,255,0.5)}
.manager-card p{font-size:12px;color:rgba(255,255,255,0.55);line-height:1.55;margin-bottom:14px}
.btn-whatsapp{width:100%;background:#25D366;color:#fff;border:none;padding:11px 16px;border-radius:9px;font-family:'Inter',sans-serif;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;text-decoration:none}
.btn-whatsapp:hover{background:#1db954;transform:translateY(-1px);box-shadow:0 4px 14px rgba(37,211,102,0.4)}
.continue-bar{text-align:center;margin-top:8px}
.continue-bar a{font-size:13px;color:var(--text-secondary);text-decoration:none;display:inline-flex;align-items:center;gap:7px;font-weight:500;padding:10px 20px;border-radius:8px;transition:all .2s}
.continue-bar a:hover{color:var(--orange);background:rgba(240,90,34,0.06)}
button,a,input,select,textarea{transition:transform .2s ease,box-shadow .2s ease,background-color .2s ease,border-color .2s ease,color .2s ease}
button:focus-visible,input:focus-visible{outline:2px solid var(--orange);outline-offset:2px}
.reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
@media(max-width:1280px){.confirm-layout{grid-template-columns:1fr 340px}.main-wrapper{padding:28px 32px 48px}.breadcrumb-bar,.stepper-bar{padding:14px 32px}.navbar{padding:0 32px}}
@media(max-width:1024px){.confirm-layout{grid-template-columns:1fr}.right-col{position:static}.delivery-details-grid{grid-template-columns:1fr}.bank-details-grid{grid-template-columns:1fr}}
@media(max-width:768px){.navbar{padding:0 20px;height:60px}.hamburger{display:block}.main-wrapper{padding:20px 20px 40px}.breadcrumb-bar,.stepper-bar{padding:12px 20px}.stepper-connector{width:40px}.success-hero{flex-direction:column;text-align:center;padding:28px 24px}.success-badges{justify-content:center}.success-confetti-side{display:none}.step-label .step-name{font-size:11px}.step-circle{width:30px;height:30px;font-size:11px}}
footer{background:var(--navy-dark);padding:64px 48px 0;border-top:1px solid rgba(255,255,255,0.06)}.footer-inner{max-width:1632px;margin:0 auto}.footer-grid{display:grid;grid-template-columns:280px 1fr 1fr 300px;gap:48px;padding-bottom:48px;border-bottom:1px solid rgba(255,255,255,0.08)}.footer-brand .logo{display:flex;align-items:center;gap:10px;margin-bottom:16px}.footer-brand .logo .icon{width:36px;height:36px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:17px;color:#fff}.footer-brand .logo .name{font-size:18px;font-weight:800;color:#fff}.footer-brand p{font-size:13px;color:rgba(255,255,255,0.55);line-height:1.7;margin-bottom:20px}.footer-socials{display:flex;gap:8px}.footer-social-btn{width:34px;height:34px;background:rgba(255,255,255,0.06);border-radius:7px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:14px;cursor:pointer;transition:all .2s;text-decoration:none}.footer-social-btn:hover{background:var(--orange);color:#fff}.footer-col h4{font-size:13px;font-weight:700;color:#fff;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:18px}.footer-col ul{list-style:none;display:flex;flex-direction:column;gap:10px}.footer-col ul li a{font-size:13px;color:rgba(255,255,255,0.5);text-decoration:none;display:flex;align-items:center;gap:7px;transition:color .2s}.footer-col ul li a:hover{color:rgba(255,255,255,0.9)}.footer-col ul li a i{font-size:11px;color:var(--orange);opacity:.7}.contact-item{display:flex;gap:10px;margin-bottom:12px}.contact-item i{color:var(--orange);font-size:14px;margin-top:2px;flex-shrink:0}.contact-item span{font-size:13px;color:rgba(255,255,255,0.5);line-height:1.5}.footer-bottom{padding:20px 0;display:flex;align-items:center;justify-content:space-between}.footer-bottom p{font-size:12px;color:rgba(255,255,255,0.35)}.footer-bottom-links{display:flex;gap:20px}.footer-bottom-links a{font-size:12px;color:rgba(255,255,255,0.35);text-decoration:none;transition:color .2s}.footer-bottom-links a:hover{color:rgba(255,255,255,0.6)}
</style>
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-icon"><i class="fas fa-network-wired"></i></div>
    <div class="nav-logo-text"><span class="brand">BN-Infrastructure</span><span class="tagline">Tanzania</span></div>
  </a>
  <?php echo userNavHtml(); ?>
  <button class="hamburger" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
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
    <span class="done-crumb"><i class="fas fa-check-circle" style="font-size:11px;margin-right:3px;"></i>Checkout</span>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Confirmation</span>
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
      <div class="step-circle completed"><i class="fas fa-check" style="font-size:14px;"></i></div>
      <div class="step-label">
        <span class="step-num">Step 2</span>
        <span class="step-name completed-text">Checkout</span>
      </div>
    </div>
    <div class="stepper-connector filled"></div>
    <div class="stepper-step">
      <div class="step-circle active">3</div>
      <div class="step-label">
        <span class="step-num">Step 3</span>
        <span class="step-name active-text">Confirmation</span>
      </div>
    </div>
  </div>
</div>

<div class="main-wrapper">
  <div class="main-inner">

    <div class="success-hero">
      <div class="success-checkmark">
        <i class="fas fa-check"></i>
      </div>
      <div class="success-hero-text">
        <h1>Order Placed Successfully!</h1>
        <p>Thank you, <?php echo htmlspecialchars(explode(' ', $order['full_name'])[0]); ?>. Your order has been confirmed and is being processed by our team.</p>
        <div class="success-badges">
          <div class="order-number-badge">
            <i class="fas fa-hashtag"></i>
            #<?php echo htmlspecialchars($order['order_number']); ?>
          </div>
          <?php if (!empty($order['delivery_method'])): ?>
          <div class="delivery-estimate-badge">
            <i class="fas fa-bolt"></i>
            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['delivery_method']))); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="success-confetti-side">
        <div style="text-align:right;opacity:0.6;">
          <div style="font-size:11px;font-weight:600;color:var(--green);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Confirmed</div>
          <div style="font-size:28px;font-weight:900;color:var(--green);letter-spacing:-0.03em;line-height:1;">✓</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
          <div class="conf-line" style="width:60px;background:var(--green);"></div>
          <div class="conf-line" style="width:40px;background:var(--orange);"></div>
          <div class="conf-line" style="width:50px;background:var(--green);"></div>
        </div>
      </div>
    </div>

    <div class="confirm-layout">

      <div class="left-col">

        <div class="section-card">
          <div class="section-card-header">
            <h2>
              <div class="section-number">1</div>
              <i class="fas fa-receipt"></i> Order Summary
            </h2>
            <span style="font-size:12px;color:var(--text-muted);font-weight:500;"><?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?></span>
          </div>
          <div class="mini-product-list">
            <?php foreach ($orderItems as $item): ?>
            <div class="mini-product-item">
              <div class="mini-product-info">
                <div class="mini-product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                <div class="mini-product-qty">Qty: <?php echo $item['quantity']; ?> × TSh <?php echo number_format($item['unit_price'], 0, '.', ','); ?></div>
              </div>
              <div class="mini-product-total">TSh <?php echo number_format($item['total_price'], 0, '.', ','); ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="summary-body">
            <div class="summary-row">
              <span class="s-label"><i class="fas fa-box" style="color:var(--text-muted);font-size:11px;"></i> Subtotal (<?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?>)</span>
              <span class="s-value">TSh <?php echo number_format($order['subtotal'], 0, '.', ','); ?></span>
            </div>
            <?php if ($order['discount'] > 0): ?>
            <div class="bulk-discount-row">
              <span class="s-label"><i class="fas fa-tags"></i> Bulk Discount</span>
              <span class="s-value">−TSh <?php echo number_format($order['discount'], 0, '.', ','); ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row">
              <span class="s-label"><i class="fas fa-shipping-fast" style="color:var(--text-muted);font-size:11px;"></i> Shipping</span>
              <span class="s-value"><?php echo $order['shipping'] > 0 ? 'TSh '.number_format($order['shipping'], 0, '.', ',') : 'Free'; ?></span>
            </div>
            <div class="summary-row">
              <span class="s-label"><i class="fas fa-receipt" style="color:var(--text-muted);font-size:11px;"></i> VAT (18%)</span>
              <span class="s-value">TSh <?php echo number_format($order['vat'], 0, '.', ','); ?></span>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-total-row">
              <span class="total-label">Grand Total</span>
              <span class="total-value"><span class="currency">TSh </span><?php echo number_format($order['total'], 0, '.', ','); ?></span>
            </div>
            <div class="summary-vat-note">All prices in Tanzanian Shillings · 18% VAT included</div>
          </div>
        </div>

        <div class="section-card">
          <div class="section-card-header">
            <h2>
              <div class="section-number">2</div>
              <i class="fas fa-truck"></i> Delivery Details
            </h2>
          </div>
          <div class="delivery-details-grid">
            <?php if (!empty($order['company_name'])): ?>
            <div class="detail-item">
              <span class="detail-label">Company</span>
              <span class="detail-value"><?php echo htmlspecialchars($order['company_name']); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-item">
              <span class="detail-label">Contact Person</span>
              <span class="detail-value"><?php echo htmlspecialchars($order['full_name']); ?></span>
            </div>
            <?php if (!empty($order['address'])): ?>
            <div class="detail-item full">
              <span class="detail-label">Delivery Address</span>
              <span class="detail-value"><?php echo nl2br(htmlspecialchars($order['address'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($order['city']) || !empty($order['region'])): ?>
            <div class="detail-item">
              <span class="detail-label">Region / City</span>
              <span class="detail-value"><?php echo trim(implode(', ', array_filter([$order['city'], $order['region']]))); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($order['phone'])): ?>
            <div class="detail-item">
              <span class="detail-label">Phone Number</span>
              <span class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-item">
              <span class="detail-label">Email Address</span>
              <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
            </div>
            <?php if (!empty($order['delivery_method'])): ?>
            <div class="detail-item">
              <span class="detail-label">Delivery Method</span>
              <div style="margin-top:2px;">
                <span class="delivery-method-pill"><i class="fas fa-bolt"></i> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['delivery_method']))); ?></span>
              </div>
            </div>
            <?php endif; ?>
            <div class="detail-item">
              <span class="detail-label">Order Date</span>
              <span class="detail-value"><?php echo $orderDate; ?></span>
            </div>
          </div>
        </div>

        <div class="section-card">
          <div class="section-card-header">
            <h2>
              <div class="section-number">3</div>
              <i class="fas fa-credit-card"></i> Payment Details
            </h2>
          </div>
          <div class="payment-details-body">
            <div class="payment-status-row">
              <div class="payment-method-info">
                <div class="payment-icon-box"><i class="fas fa-university"></i></div>
                <div>
                  <div class="payment-method-label"><?php echo str_replace('_', ' ', ucwords($order['payment_method'], '_')); ?></div>
                  <div class="payment-method-sub">Bank Transfer</div>
                </div>
              </div>
              <?php if ($isPaid): ?>
              <div class="paid-badge"><i class="fas fa-check-circle"></i> Paid</div>
              <?php else: ?>
              <div class="awaiting-badge"><i class="fas fa-circle"></i> Awaiting Payment</div>
              <?php endif; ?>
            </div>

            <div class="bank-details-box">
              <div class="bank-box-title">Bank Account Details</div>
              <div class="bank-details-grid">
                <div class="bank-detail-item">
                  <span class="bank-detail-label">Bank Name</span>
                  <span class="bank-detail-value">CRDB Bank PLC</span>
                </div>
                <div class="bank-detail-item">
                  <span class="bank-detail-label">Account Name</span>
                  <span class="bank-detail-value">BN-Infrastructure Ltd</span>
                </div>
                <div class="bank-detail-item">
                  <span class="bank-detail-label">Account Number</span>
                  <span class="bank-detail-value">01J1503500700</span>
                </div>
                <div class="bank-detail-item">
                  <span class="bank-detail-label">Swift Code</span>
                  <span class="bank-detail-value">CORUTZTZ</span>
                </div>
                <div class="bank-ref-highlight">
                  <div>
                    <div class="bank-detail-label">Payment Reference (Required)</div>
                    <div class="bank-detail-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
                  </div>
                  <button class="copy-btn" onclick="copyRef(this, '<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES); ?>')"><i class="fas fa-copy" style="margin-right:4px;"></i>Copy</button>
                </div>
              </div>
            </div>

            <div class="payment-instruction-box">
              <strong>Important:</strong> Please include the payment reference <strong><?php echo htmlspecialchars($order['order_number']); ?></strong> in your transfer description. Your order will be processed within <strong>2–4 business hours</strong> of payment confirmation. Send your proof of payment to <strong>accounts@bn-infrastructure.com</strong>.
            </div>
          </div>
        </div>

        <?php if (!empty($tracking)): ?>
        <div class="section-card tracking-card">
          <div class="section-card-header">
            <h2>
              <i class="fas fa-truck"></i> Order Tracking
            </h2>
          </div>
          <div class="tracking-timeline">
            <?php foreach ($tracking as $i => $t): ?>
            <div class="tracking-item">
              <div class="tracking-dot <?php echo $i === 0 ? 'active' : ''; ?>"><i class="fas fa-<?php echo $i === 0 ? 'check' : 'circle'; ?>"></i></div>
              <div class="tracking-content">
                <div class="t-status"><?php echo ucfirst($t['status']); ?></div>
                <div class="t-note"><?php echo htmlspecialchars($t['note']); ?></div>
                <div class="t-time"><?php echo date('M d, Y h:i A', strtotime($t['created_at'])); ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <div class="right-col">

        <div class="next-steps-card">
          <div class="next-steps-header">
            <h3><i class="fas fa-route"></i> What Happens Next?</h3>
          </div>
          <div class="next-steps-list">

            <div class="next-step-item active">
              <div class="step-icon-circle active"><i class="fas fa-university"></i></div>
              <div class="next-step-content">
                <div class="step-status-pill pill-active"><?php echo $isPaid ? 'Completed' : 'In Progress'; ?></div>
                <div class="next-step-name active">1. Payment Verification</div>
                <div class="next-step-desc">Transfer TSh <?php echo number_format($order['total'], 0, '.', ','); ?> to our CRDB account. Our finance team verifies payment within 2–4 business hours.</div>
              </div>
            </div>

            <div class="next-step-item">
              <div class="step-icon-circle pending"><i class="fas fa-cog"></i></div>
              <div class="next-step-content">
                <div class="step-status-pill pill-pending">Upcoming</div>
                <div class="next-step-name pending">2. Order Processing</div>
                <div class="next-step-desc">Once payment is confirmed, your items are picked, quality-checked, and packed at our Mikocheni warehouse.</div>
              </div>
            </div>

            <div class="next-step-item">
              <div class="step-icon-circle pending"><i class="fas fa-shipping-fast"></i></div>
              <div class="next-step-content">
                <div class="step-status-pill pill-pending">Upcoming</div>
                <div class="next-step-name pending">3. Dispatch &amp; Tracking</div>
                <div class="next-step-desc">You'll receive an SMS and email with your tracking number and courier details once dispatched.</div>
              </div>
            </div>

            <div class="next-step-item">
              <div class="step-icon-circle pending"><i class="fas fa-map-marker-check" style="font-size:12px;"></i></div>
              <div class="next-step-content">
                <div class="step-status-pill pill-pending">Upcoming</div>
                <div class="next-step-name pending">4. Delivery</div>
                <div class="next-step-desc">Delivery to your registered address. You'll be notified when the courier is en route.</div>
              </div>
            </div>

          </div>

          <div class="right-actions">
            <a href="/track.php?order=<?php echo urlencode($order['order_number']); ?>" class="btn-track">
              <i class="fas fa-map-marker-alt"></i> Track Your Order
            </a>
            <button class="btn-invoice" onclick="handleInvoice(this)">
              <i class="fas fa-file-pdf"></i> Download Invoice PDF
            </button>
          </div>
        </div>

        <div class="manager-card">
          <div class="manager-card-top">
            <div class="manager-avatar">
              <i class="fas fa-user-tie"></i>
            </div>
            <div class="manager-info">
              <div class="manager-label">Your Account Manager</div>
              <div class="manager-name">Sales Team</div>
              <div class="manager-role">B2B Account Support</div>
            </div>
          </div>
          <p>Have questions about your order or need technical assistance? Our team is available Monday–Friday, 8am–6pm EAT.</p>
          <a href="https://wa.me/255763364721" target="_blank" class="btn-whatsapp">
            <i class="fab fa-whatsapp" style="font-size:16px;"></i>
            Chat on WhatsApp
          </a>
        </div>

        <div class="continue-bar" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
          <a href="catalog.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:var(--navy);color:#fff;border-radius:10px;font-weight:600;font-size:14px;">
            <i class="fas fa-store"></i> Continue Shopping
          </a>
          <a href="invoice.php?order=<?php echo urlencode($order['order_number']); ?>" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border:2px solid var(--border);color:var(--navy);border-radius:10px;font-weight:600;font-size:14px;">
            <i class="fas fa-file-invoice"></i> View Invoice
          </a>
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
        <div class="footer-col"><h4>Contact Us</h4><div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>Plot 45, Mikocheni Light Industrial Area, Dar es Salaam, Tanzania</span></div><div class="contact-item"><i class="fas fa-phone-alt"></i><span>+255 763 364 721 <br>+255 763 364 721</span></div><div class="contact-item"><i class="fas fa-envelope"></i><span>sales@bn-infrastructure.com</span></div></div>
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
<?php echo userMenuJs(); ?>
<?php echo scrollRevealJs(); ?>
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function toggleMenu(){document.getElementById('mobileNav').classList.toggle('open');document.getElementById('navOverlay').classList.toggle('open')}
function copyRef(btn, ref){navigator.clipboard.writeText(ref).then(function(){var orig=btn.innerHTML;btn.innerHTML='<i class="fas fa-check" style="margin-right:4px;"></i>Copied!';btn.style.background='rgba(5,150,105,0.12)';btn.style.color='var(--green)';btn.style.borderColor='var(--green-border)';setTimeout(function(){btn.innerHTML=orig;btn.style.background='';btn.style.color='';btn.style.borderColor=''},2000)})["catch"](function(){var dummy=document.createElement('input');dummy.value=ref;document.body.appendChild(dummy);dummy.select();document.execCommand('copy');document.body.removeChild(dummy);var orig=btn.innerHTML;btn.innerHTML='<i class="fas fa-check" style="margin-right:4px;"></i>Copied!';setTimeout(function(){btn.innerHTML=orig},2000)})}
function handleInvoice(btn){
  var orig=btn.innerHTML;
  btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Generating PDF…';
  btn.disabled=true;

  var itemsHtml='';
  document.querySelectorAll('.mini-product-item').forEach(function(el){
    var name=el.querySelector('.mini-product-name').textContent.trim();
    var qty=el.querySelector('.mini-product-qty').textContent.trim();
    var total=el.querySelector('.mini-product-total').textContent.trim();
    itemsHtml+='<tr><td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;color:#333;">'+name+'</td><td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;color:#666;text-align:center;">'+qty.replace('Qty: ','')+'</td><td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;color:#333;text-align:right;font-weight:700;">'+total+'</td></tr>';
  });

  var summaryRows='';
  document.querySelectorAll('.summary-row, .bulk-discount-row').forEach(function(el){
    var label=el.querySelector('.s-label')?el.querySelector('.s-label').textContent.trim():'';
    var value=el.querySelector('.s-value')?el.querySelector('.s-value').textContent.trim():'';
    if(label&&value){summaryRows+='<div style="display:flex;justify-content:space-between;padding:5px 0;font-size:13px;"><span style="color:#666;">'+label+'</span><span style="font-weight:600;color:#333;">'+value+'</span></div>';}
  });
  var totalLabel=document.querySelector('.total-label').textContent.trim();
  var totalValue=document.querySelector('.total-value').textContent.trim();

  var orderNum='<?php echo htmlspecialchars($order["order_number"], ENT_QUOTES); ?>';
  var orderDate='<?php echo $orderDate; ?>';
  var custName='<?php echo htmlspecialchars($order["full_name"], ENT_QUOTES); ?>';
  var custEmail='<?php echo htmlspecialchars($order["email"], ENT_QUOTES); ?>';
  var custPhone='<?php echo htmlspecialchars($order["phone"] ?? "", ENT_QUOTES); ?>';
  var custCompany='<?php echo htmlspecialchars($order["company_name"] ?? "", ENT_QUOTES); ?>';
  var custAddress='<?php echo htmlspecialchars($order["address"] ?? "", ENT_QUOTES); ?>';
  var custCity='<?php echo htmlspecialchars(implode(", ", array_filter([$order["city"] ?? "", $order["region"] ?? ""])), ENT_QUOTES); ?>';
  var paymentMethod='<?php echo htmlspecialchars(str_replace("_", " ", ucwords($order["payment_method"] ?? "Bank Transfer", "_")), ENT_QUOTES); ?>';

  var html='<div style="font-family:Inter,Arial,sans-serif;padding:40px;max-width:800px;margin:0 auto;color:#333;">';
  html+='<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px;padding-bottom:24px;border-bottom:3px solid #0A2540;">';
  html+='<div><div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><div style="width:36px;height:36px;background:#F05A22;border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-network-wired" style="color:#fff;font-size:16px;"></i></div><span style="font-size:20px;font-weight:800;color:#0A2540;">BN-Infrastructure</span></div><div style="font-size:12px;color:#888;">Plot 45, Mikocheni Light Industrial Area<br>Dar es Salaam, Tanzania<br>sales@bn-infrastructure.com</div></div>';
  html+='<div style="text-align:right;"><div style="font-size:28px;font-weight:900;color:#0A2540;letter-spacing:-0.02em;">INVOICE</div><div style="font-size:13px;color:#888;margin-top:4px;">#'+orderNum+'</div><div style="font-size:13px;color:#888;margin-top:2px;">'+orderDate+'</div></div>';
  html+='</div>';
  html+='<div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-bottom:32px;">';
  html+='<div><div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Bill To</div><div style="font-size:15px;font-weight:700;color:#0A2540;margin-bottom:2px;">'+(custCompany||custName)+'</div>';
  if(custCompany) html+='<div style="font-size:13px;color:#555;">'+custName+'</div>';
  html+='<div style="font-size:13px;color:#555;margin-top:4px;">'+custAddress+'</div><div style="font-size:13px;color:#555;">'+custCity+'</div><div style="font-size:13px;color:#555;margin-top:4px;">'+custPhone+'</div><div style="font-size:13px;color:#555;">'+custEmail+'</div></div>';
  html+='<div><div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Payment Details</div><div style="font-size:13px;color:#555;"><strong>Method:</strong> '+paymentMethod+'</div><div style="font-size:13px;color:#555;margin-top:4px;"><strong>Reference:</strong> '+orderNum+'</div><div style="margin-top:12px;padding:10px 14px;background:#FFF3ED;border:1px solid #FDDCC7;border-radius:6px;font-size:12px;color:#d44d1a;"><strong>Note:</strong> Please include payment reference '+orderNum+' in your transfer description.</div></div>';
  html+='</div>';
  html+='<table style="width:100%;border-collapse:collapse;margin-bottom:24px;"><thead><tr style="background:#0A2540;"><th style="padding:12px;text-align:left;color:#fff;font-size:12px;font-weight:700;">Product</th><th style="padding:12px;text-align:center;color:#fff;font-size:12px;font-weight:700;">Quantity</th><th style="padding:12px;text-align:right;color:#fff;font-size:12px;font-weight:700;">Total</th></tr></thead><tbody>'+itemsHtml+'</tbody></table>';
  html+='<div style="max-width:300px;margin-left:auto;">'+summaryRows;
  html+='<div style="border-top:3px solid #0A2540;margin-top:10px;padding-top:10px;display:flex;justify-content:space-between;"><span style="font-size:16px;font-weight:800;color:#0A2540;">'+totalLabel+'</span><span style="font-size:18px;font-weight:900;color:#0A2540;">'+totalValue+'</span></div></div>';
  html+='<div style="margin-top:36px;padding-top:20px;border-top:1px solid #eee;text-align:center;font-size:12px;color:#999;">Thank you for your order! | BN-Infrastructure | Tanzania</div>';
  html+='</div>';

  var container=document.createElement('div');
  container.innerHTML=html;
  document.body.appendChild(container);

  html2pdf().set({margin:0,filename:'Invoice-'+orderNum+'.pdf',image:{type:'jpeg',quality:0.98},html2canvas:{scale:2,useCORS:true},jsPDF:{unit:'mm',format:'a4',orientation:'portrait'}}).from(container).save().then(function(){
    document.body.removeChild(container);
    btn.innerHTML='<i class="fas fa-check"></i> Invoice Downloaded!';
    btn.style.background='var(--green)';btn.style.color='#fff';btn.style.borderColor='var(--green)';
    setTimeout(function(){btn.innerHTML=orig;btn.style.background='';btn.style.color='';btn.style.borderColor='';btn.disabled=false},2500);
  });
}
</script>
</body></html>
