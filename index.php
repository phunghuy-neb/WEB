<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

if (is_admin()) {
    header('Location: ' . BASE_URL . 'admin/index.php');
    exit;
}

// Lọc theo danh mục + tìm kiếm theo tên (giữ lại khi redirect)
$search = trim($_GET['search'] ?? '');
$categoryId = (int) ($_GET['category'] ?? 0);
$filterParams = array_filter(['search' => $search, 'category' => $categoryId ?: null]);

// Thêm sản phẩm vào danh sách yêu thích
if (isset($_GET['add_wishlist'])) {
    require_login();
    $pid = (int) $_GET['add_wishlist'];
    $stmt = $pdo->prepare('INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)');
    $stmt->execute([$_SESSION['user_id'], $pid]);
    $query = $filterParams ? '?' . http_build_query($filterParams) : '';
    header('Location: ' . BASE_URL . 'index.php' . $query . '#product-' . $pid);
    exit;
}

// Bỏ yêu thích ngay tại trang danh sách (không cần vào mục yêu thích)
if (isset($_GET['remove_wishlist'])) {
    require_login();
    $pid = (int) $_GET['remove_wishlist'];
    $stmt = $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$_SESSION['user_id'], $pid]);
    $query = $filterParams ? '?' . http_build_query($filterParams) : '';
    header('Location: ' . BASE_URL . 'index.php' . $query . '#product-' . $pid);
    exit;
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$sql = 'SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE 1=1';
$params = [];
if ($search !== '') {
    $sql .= ' AND p.name LIKE ?';
    $params[] = "%$search%";
}
if ($categoryId > 0) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $categoryId;
}
$sql .= ' ORDER BY p.id';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$wishlistIds = [];
if (is_logged_in()) {
    $stmt = $pdo->prepare('SELECT product_id FROM wishlists WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $wishlistIds = array_column($stmt->fetchAll(), 'product_id');
}

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-grid"></i> Danh sách sản phẩm</h4>

<form method="get" action="" class="row g-2 mb-4">
    <div class="col-md-6">
        <input type="text" name="search" class="form-control" placeholder="Tìm sản phẩm theo tên..." value="<?= e($search) ?>">
    </div>
    <div class="col-md-4">
        <select name="category" class="form-select">
            <option value="0">-- Tất cả danh mục --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Lọc</button>
    </div>
</form>

<?php if (empty($products)): ?>
    <div class="alert alert-info">Không tìm thấy sản phẩm phù hợp.</div>
<?php endif; ?>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach ($products as $p): ?>
        <?php
        $imgFile = __DIR__ . '/assets/uploads/' . $p['image'];
        $hasImg = $p['image'] && file_exists($imgFile);
        $inWishlist = in_array($p['id'], $wishlistIds);
        $wishlistParamKey = $inWishlist ? 'remove_wishlist' : 'add_wishlist';
        $wishlistUrl = BASE_URL . 'index.php?' . http_build_query(array_merge($filterParams, [$wishlistParamKey => $p['id']])) . '#product-' . $p['id'];
        $discountPercent = product_discount_percent($p['original_price'], $p['price']);
        ?>
        <div class="col" id="product-<?= $p['id'] ?>">
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
                    <span class="badge bg-secondary align-self-start mb-2"><?= e($p['category_name'] ?? 'Khác') ?></span>
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

                        <?php if ($inWishlist): ?>
                            <a href="<?= e($wishlistUrl) ?>" class="btn btn-outline-danger btn-sm" title="Bỏ yêu thích">
                                <i class="bi bi-heart-fill"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?= e($wishlistUrl) ?>" class="btn btn-outline-secondary btn-sm" title="Yêu thích">
                                <i class="bi bi-heart"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
