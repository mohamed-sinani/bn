<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function cartInit() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function cartAdd($productId, $quantity = 1) {
    cartInit();
    $product = fetchOne("SELECT id, name, sku, price, image, stock_qty, brand, stock_status, tags FROM products WHERE id = ?", [$productId]);
    if (!$product) return false;
    $pid = (int)$productId;
    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]['qty'] += $quantity;
    } else {
        $_SESSION['cart'][$pid] = [
            'id' => $pid,
            'name' => $product['name'],
            'sku' => $product['sku'],
            'price' => (float)$product['price'],
            'image' => $product['image'],
            'brand' => $product['brand'] ?? '',
            'stock_status' => $product['stock_status'] ?? 'in_stock',
            'tags' => $product['tags'] ?? '',
            'qty' => $quantity
        ];
    }
    return true;
}

function cartUpdate($productId, $quantity) {
    cartInit();
    $pid = (int)$productId;
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$pid]);
    } elseif (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]['qty'] = $quantity;
    }
}

function cartRemove($productId) {
    cartInit();
    unset($_SESSION['cart'][(int)$productId]);
}

function cartGetItems() {
    cartInit();
    return $_SESSION['cart'];
}

function cartCount() {
    cartInit();
    return array_sum(array_column($_SESSION['cart'], 'qty'));
}

function cartSubtotal() {
    cartInit();
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['qty'];
    }
    return $total;
}

function cartContains($productId) {
    cartInit();
    return isset($_SESSION['cart'][(int)$productId]);
}

function cartClear() {
    $_SESSION['cart'] = [];
}

function generateOrderNumber() {
    return 'NZ-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

function generateQuoteNumber() {
    return 'QT-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

cartInit();
