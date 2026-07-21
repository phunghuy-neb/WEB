<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';
require_login();

// Xóa 1 sản phẩm khỏi giỏ
if (isset($_GET['remove'])) {
    $pid = (int) $_GET['remove'];
    unset($_SESSION['cart'][$pid]);
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

// Cập nhật số lượng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantities'])) {
    verify_csrf();

    $ids = array_map('intval', array_keys($_POST['quantities']));
    $stockMap = [];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $row) {
            $stockMap[$row['id']] = (int) $row['stock'];
        }
    }

    foreach ($_POST['quantities'] as $pid => $qty) {
        $pid = (int) $pid;
        $qty = (int) $qty;
        if ($qty <= 0 || !isset($stockMap[$pid])) {
            unset($_SESSION['cart'][$pid]);
        } else {
            // Không cho vượt quá tồn kho thực tế, kể cả khi số gửi lên bị chỉnh sửa
            $_SESSION['cart'][$pid] = min($qty, $stockMap[$pid]);
        }
    }
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;

if (!empty($cart)) {
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    foreach ($stmt->fetchAll() as $row) {
        $qty = (int) $cart[$row['id']];
        $subtotal = $row['price'] * $qty;
        $items[] = [
            'product_id' => $row['id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'quantity' => $qty,
            'stock' => $row['stock'],
            'subtotal' => $subtotal,
        ];
        $total += $subtotal;
    }
}

// Hạng thành viên hiện tại, để báo trước mức giảm giá sẽ áp dụng khi thanh toán
$spent = total_spent($pdo, $_SESSION['user_id']);
$tier = get_tier($pdo, $spent);
$tierDiscountPercent = (int) $tier['discount_percent'];

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-cart3"></i> Giỏ hàng</h4>

<?php if (empty($items)): ?>
    <div class="alert alert-info">
        Giỏ hàng đang trống. <a href="<?= BASE_URL ?>index.php">Tiếp tục mua sắm</a>
    </div>
<?php else: ?>
    <form method="post" action="">
        <?= csrf_field() ?>
        <div class="card p-3 mb-3">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th style="width:120px;">Số lượng</th>
                        <th>Thành tiền</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['name']) ?></td>
                            <td><?= money($item['price']) ?></td>
                            <td>
                                <input type="number" name="quantities[<?= $item['product_id'] ?>]"
                                       value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>"
                                       class="form-control form-control-sm">
                            </td>
                            <td><?= money($item['subtotal']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>cart.php?remove=<?= $item['product_id'] ?>"
                                   class="btn btn-sm btn-outline-danger" title="Xóa">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-arrow-repeat"></i> Cập nhật giỏ hàng
            </button>
            <div class="fs-5">
                Tổng cộng: <span class="fw-bold text-primary"><?= money($total) ?></span>
            </div>
        </div>
    </form>

    <div class="text-end mt-2">
        <?php if ($tierDiscountPercent > 0): ?>
            <small class="text-muted">
                Hạng <span class="badge <?= tier_badge_class($tier['name']) ?>"><?= e($tier['name']) ?></span>
                sẽ được giảm thêm <?= $tierDiscountPercent ?>%
                (<?= money(round($total * $tierDiscountPercent / 100)) ?>) khi thanh toán.
            </small>
        <?php else: ?>
            <small class="text-muted">
                Hạng <span class="badge <?= tier_badge_class($tier['name']) ?>"><?= e($tier['name']) ?></span>
                — chưa có ưu đãi giảm giá theo hạng.
            </small>
        <?php endif; ?>
    </div>

    <div class="text-end mt-3">
        <a href="<?= BASE_URL ?>checkout.php" class="btn btn-primary btn-lg">
            <i class="bi bi-credit-card"></i> Thanh toán
        </a>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
