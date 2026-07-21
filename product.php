<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);

// Thêm sản phẩm vào danh sách yêu thích
if (isset($_GET['add_wishlist'])) {
    require_login();
    $stmt = $pdo->prepare('INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)');
    $stmt->execute([$_SESSION['user_id'], $id]);
    header('Location: ' . BASE_URL . 'product.php?id=' . $id);
    exit;
}

// Bỏ yêu thích ngay tại trang chi tiết
if (isset($_GET['remove_wishlist'])) {
    require_login();
    $stmt = $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$_SESSION['user_id'], $id]);
    header('Location: ' . BASE_URL . 'product.php?id=' . $id);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.id = ?'
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$inWishlist = false;
if (is_logged_in()) {
    $stmt = $pdo->prepare('SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$_SESSION['user_id'], $id]);
    $inWishlist = (bool) $stmt->fetch();
}

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<a href="<?= BASE_URL ?>index.php" class="btn btn-link ps-0 mb-3"><i class="bi bi-arrow-left"></i> Quay lại danh sách</a>

<div class="row g-4">
    <div class="col-md-5">
        <?php
        $imgFile = __DIR__ . '/assets/uploads/' . $product['image'];
        $hasImg = $product['image'] && file_exists($imgFile);
        ?>
        <div class="card">
            <?php if ($hasImg): ?>
                <img src="<?= BASE_URL ?>assets/uploads/<?= e($product['image']) ?>" class="card-img-top" alt="<?= e($product['name']) ?>">
            <?php else: ?>
                <div class="bg-light d-flex align-items-center justify-content-center" style="height:300px;">
                    <i class="bi bi-image text-secondary" style="font-size:4rem;"></i>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-7">
        <?php $discountPercent = product_discount_percent($product['original_price'], $product['price']); ?>
        <span class="badge bg-secondary mb-2"><?= e($product['category_name'] ?? 'Khác') ?></span>
        <?php if ($discountPercent > 0): ?>
            <span class="badge bg-danger mb-2">-<?= $discountPercent ?>%</span>
        <?php endif; ?>
        <h3><?= e($product['name']) ?></h3>
        <?php if ($discountPercent > 0): ?>
            <div class="mb-3">
                <span class="text-muted text-decoration-line-through fs-5"><?= money($product['original_price']) ?></span>
                <span class="fs-3 fw-bold text-danger ms-2"><?= money($product['price']) ?></span>
            </div>
        <?php else: ?>
            <div class="fs-3 fw-bold text-primary mb-3"><?= money($product['price']) ?></div>
        <?php endif; ?>
        <p><?= nl2br(e($product['description'])) ?></p>
        <p class="text-muted">
            Tồn kho:
            <?php if ($product['stock'] > 0): ?>
                <?= (int) $product['stock'] ?> sản phẩm
            <?php else: ?>
                <span class="text-danger">Hết hàng</span>
            <?php endif; ?>
        </p>

        <div class="d-flex gap-2 mt-3">
            <?php if ($product['stock'] > 0): ?>
                <a href="<?= BASE_URL ?>add_to_cart.php?id=<?= $product['id'] ?>" class="btn btn-primary">
                    <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                </a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>Hết hàng</button>
            <?php endif; ?>

            <?php if ($inWishlist): ?>
                <a href="<?= BASE_URL ?>product.php?id=<?= $product['id'] ?>&remove_wishlist=1" class="btn btn-outline-danger">
                    <i class="bi bi-heart-fill"></i> Bỏ yêu thích
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>product.php?id=<?= $product['id'] ?>&add_wishlist=1" class="btn btn-outline-secondary">
                    <i class="bi bi-heart"></i> Yêu thích
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
