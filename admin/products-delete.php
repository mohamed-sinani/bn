<?php
require_once __DIR__ . '/../src/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$product = fetchOne("SELECT id, name, sku FROM products WHERE id = ?", [$id]);

if (!$product) {
    header('Location: /admin/products.php?error=Product not found');
    exit;
}

execute("DELETE FROM products WHERE id = ?", [$id]);
header('Location: /admin/products.php?success=Product "' . urlencode($product['name']) . '" deleted successfully');
exit;
