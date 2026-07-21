<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

$uploadDir = __DIR__ . '/../assets/uploads/';
$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

// Xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete_product') {
    verify_csrf();

    $id = (int) ($_POST['product_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT image FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);

    if ($product && $product['image'] && file_exists($uploadDir . $product['image'])) {
        unlink($uploadDir . $product['image']);
    }

    header('Location: ' . BASE_URL . 'admin/products.php');
    exit;
}

// Thêm danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_category') {
    verify_csrf();

    $categoryName = trim($_POST['category_name'] ?? '');
    if ($categoryName !== '') {
        $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
        $stmt->execute([$categoryName]);
    }
    header('Location: ' . BASE_URL . 'admin/products.php');
    exit;
}

// Thêm / sửa sản phẩm
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['form_action'] ?? '', ['add_product', 'edit_product'], true)) {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
    $price = (int) ($_POST['price'] ?? 0);
    $originalPriceInput = trim($_POST['original_price'] ?? '');
    $originalPrice = $originalPriceInput === '' ? null : (int) $originalPriceInput;
    $stock = (int) ($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = 'Vui lòng nhập tên sản phẩm.';
    }
    if ($price < 0 || $stock < 0) {
        $errors[] = 'Giá và tồn kho không được âm.';
    }
    if ($originalPrice !== null && $originalPrice <= $price) {
        $errors[] = 'Giá gốc phải lớn hơn giá bán (để hiển thị giảm giá).';
    }

    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            $errors[] = 'Ảnh phải có định dạng jpg, jpeg, png hoặc webp.';
        } else {
            $imageName = uniqid('sp_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }
    }

    if (empty($errors)) {
        if ($_POST['form_action'] === 'add_product') {
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, category_id, price, original_price, stock, image, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $categoryId, $price, $originalPrice, $stock, $imageName, $description]);
        } else {
            $id = (int) ($_POST['product_id'] ?? 0);
            if ($imageName === null) {
                $imageName = trim($_POST['old_image'] ?? '') ?: null;
            }
            $stmt = $pdo->prepare(
                'UPDATE products SET name = ?, category_id = ?, price = ?, original_price = ?, stock = ?, image = ?, description = ?
                 WHERE id = ?'
            );
            $stmt->execute([$name, $categoryId, $price, $originalPrice, $stock, $imageName, $description, $id]);
        }

        header('Location: ' . BASE_URL . 'admin/products.php');
        exit;
    }
}

// Sản phẩm đang sửa (nếu có)
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editProduct = $stmt->fetch();
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$products = $pdo->query(
    'SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     ORDER BY p.id DESC'
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-box-seam"></i> Quản lý sản phẩm</h4>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-3 h-100">
            <h6 class="mb-3">Danh mục</h6>
            <ul class="list-unstyled mb-3">
                <?php foreach ($categories as $cat): ?>
                    <li class="mb-1"><span class="badge bg-secondary"><?= e($cat['name']) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <form method="post" action="" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="add_category">
                <input type="text" name="category_name" class="form-control form-control-sm" placeholder="Tên danh mục mới" required>
                <button type="submit" class="btn btn-sm btn-primary text-nowrap">Thêm</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card p-3 h-100">
            <h6 class="mb-3"><?= $editProduct ? 'Sửa sản phẩm' : 'Thêm sản phẩm mới' ?></h6>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="<?= $editProduct ? 'edit_product' : 'add_product' ?>">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
                    <input type="hidden" name="old_image" value="<?= e($editProduct['image']) ?>">
                <?php endif; ?>

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Tên sản phẩm</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($editProduct['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Danh mục</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= isset($editProduct) && $editProduct['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Giá bán (đ)</label>
                        <input type="number" name="price" class="form-control" min="0"
                               value="<?= e($editProduct['price'] ?? 0) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Giá gốc (nếu giảm giá)</label>
                        <input type="number" name="original_price" class="form-control" min="0"
                               placeholder="Để trống nếu không giảm giá"
                               value="<?= e($editProduct['original_price'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tồn kho</label>
                        <input type="number" name="stock" class="form-control" min="0"
                               value="<?= e($editProduct['stock'] ?? 0) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ảnh sản phẩm</label>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        <?php if ($editProduct && $editProduct['image']): ?>
                            <div class="form-text">Ảnh hiện tại: <?= e($editProduct['image']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="2"><?= e($editProduct['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?= $editProduct ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm' ?>
                    </button>
                    <?php if ($editProduct): ?>
                        <a href="<?= BASE_URL ?>admin/products.php" class="btn btn-outline-secondary">Hủy</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card p-3">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Ảnh</th>
                <th>Tên</th>
                <th>Danh mục</th>
                <th>Giá</th>
                <th>Tồn kho</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <?php
                $imgFile = $uploadDir . $p['image'];
                $hasImg = $p['image'] && file_exists($imgFile);
                $discountPercent = product_discount_percent($p['original_price'], $p['price']);
                ?>
                <tr>
                    <td style="width:70px;">
                        <?php if ($hasImg): ?>
                            <img src="<?= BASE_URL ?>assets/uploads/<?= e($p['image']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="width:50px;height:50px;border-radius:8px;">
                                <i class="bi bi-image text-secondary"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e($p['category_name'] ?? 'Khác') ?></td>
                    <td>
                        <?php if ($discountPercent > 0): ?>
                            <span class="text-muted text-decoration-line-through small d-block"><?= money($p['original_price']) ?></span>
                            <span class="text-danger fw-bold"><?= money($p['price']) ?></span>
                            <span class="badge bg-danger">-<?= $discountPercent ?>%</span>
                        <?php else: ?>
                            <?= money($p['price']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $p['stock'] ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>admin/products.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form method="post" action="" class="d-inline" onsubmit="return confirm('Xóa sản phẩm này?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="form_action" value="delete_product">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
