<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, stock FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if ($product && $product['stock'] > 0) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $currentQty = $_SESSION['cart'][$id] ?? 0;
    $_SESSION['cart'][$id] = min($currentQty + 1, $product['stock']);
}

header('Location: ' . BASE_URL . 'cart.php');
exit;
