<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';
require_login();

$uid = $_SESSION['user_id'];

if (isset($_GET['add'])) {
    $pid = (int) $_GET['add'];
    $stmt = $pdo->prepare('INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)');
    $stmt->execute([$uid, $pid]);
    header('Location: ' . BASE_URL . 'wishlist.php');
    exit;
}

if (isset($_GET['remove'])) {
    $pid = (int) $_GET['remove'];
    $stmt = $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$uid, $pid]);
    header('Location: ' . BASE_URL . 'wishlist.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT p.*
     FROM wishlists w
     JOIN products p ON p.id = w.product_id
     WHERE w.user_id = ?
     ORDER BY w.id DESC'
);
$stmt->execute([$uid]);
$products = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-heart"></i> Sản phẩm yêu thích</h4>

<?php if (empty($products)): ?>
    <div class="alert alert-info">
        Bạn chưa có sản phẩm yêu thích nào. <a href="<?= BASE_URL ?>index.php">Khám phá sản phẩm</a>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($products as $p): ?>
            <?php
            $imgFile = __DIR__ . '/assets/uploads/' . $p['image'];
            $hasImg = $p['image'] && file_exists($imgFile);
            $discountPercent = product_discount_percent($p['original_price'], $p['price']);
            ?>
            <div class="col">
                <div class="card h-100 position-relative">
                    <?php if ($discountPercent > 0): ?>
                        <span class="badge bg-danger position-absolute top-0 end-0 m-2">-<?= $discountPercent ?>%</span>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>product.php?id=<?= $p['id'] ?>">
                        <?php if ($hasImg): ?>
                            <img src="<?= BASE_URL ?>assets/uploads/<?= e($p['image']) ?>" class="card-img-top product-thumb" alt="<?= e($p['name']) ?>">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                                <i class="bi bi-image text-secondary" style="font-size:3rem;"></i>
                            </div>
                        <?php endif; ?>
                    </a>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">
                            <a href="<?= BASE_URL ?>product.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
                                <?= e($p['name']) ?>
                            </a>
                        </h6>
                        <?php if ($discountPercent > 0): ?>
                            <div class="mb-2">
                                <span class="text-muted text-decoration-line-through small"><?= money($p['original_price']) ?></span>
                                <span class="fw-bold fs-5 text-danger ms-1"><?= money($p['price']) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="fw-bold fs-5 text-primary mb-2"><?= money($p['price']) ?></div>
                        <?php endif; ?>
                        <div class="mt-auto d-flex gap-2">
                            <?php if ($p['stock'] > 0): ?>
                                <a href="<?= BASE_URL ?>add_to_cart.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm flex-fill">
                                    <i class="bi bi-cart-plus"></i> Thêm giỏ
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm flex-fill" disabled>Hết hàng</button>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>wishlist.php?remove=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm" title="Xóa khỏi yêu thích">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
