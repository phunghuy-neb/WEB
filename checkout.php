<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';
require_login();

$uid = $_SESSION['user_id'];

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

$ids = array_map('intval', array_keys($cart));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);

$items = [];
$total = 0;
foreach ($stmt->fetchAll() as $row) {
    $qty = (int) $cart[$row['id']];
    $subtotal = $row['price'] * $qty;
    $items[] = [
        'product_id' => $row['id'],
        'name' => $row['name'],
        'price' => $row['price'],
        'quantity' => $qty,
        'subtotal' => $subtotal,
    ];
    $total += $subtotal;
}

// Hạng thành viên hiện tại (dựa trên tổng chi tiêu các đơn đã completed)
$spent = total_spent($pdo, $uid);
$tier = get_tier($pdo, $spent);
$tierDiscountPercent = (int) ($tier['discount_percent'] ?? 0);

$stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
$stmt->execute([$uid]);
$userPoints = (int) $stmt->fetch()['points'];

$errors = [];
$voucherCode = '';
$pointsUsed = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $voucherCode = trim($_POST['voucher_code'] ?? '');
    $pointsUsed = (int) ($_POST['points_used'] ?? 0);

    $voucher = null;
    if ($voucherCode !== '') {
        $stmt = $pdo->prepare(
            'SELECT * FROM vouchers
             WHERE code = ? AND active = 1 AND expire_date >= CURDATE()'
        );
        $stmt->execute([$voucherCode]);
        $voucher = $stmt->fetch();

        if (!$voucher) {
            $errors[] = 'Mã voucher không hợp lệ hoặc đã hết hạn.';
        } elseif ($total < $voucher['min_order']) {
            $errors[] = 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng voucher (' . money($voucher['min_order']) . ').';
        }
    }

    if ($pointsUsed < 0) {
        $errors[] = 'Số điểm dùng không hợp lệ.';
    } elseif ($pointsUsed > $userPoints) {
        $errors[] = 'Số điểm dùng vượt quá số điểm hiện có (' . $userPoints . ' điểm).';
    }

    $tierDiscountAmount = (int) round($total * $tierDiscountPercent / 100);
    $voucherDiscountAmount = $voucher ? (int) round($total * $voucher['discount_percent'] / 100) : 0;
    $discount = $tierDiscountAmount + $voucherDiscountAmount;

    if (empty($errors)) {
        // Điểm dùng không được vượt quá giá trị đơn hàng còn lại sau các giảm giá khác
        $maxUsablePoints = (int) floor(max(0, $total - $discount) / 1000);
        if ($pointsUsed > $maxUsablePoints) {
            $errors[] = 'Số điểm dùng vượt quá giá trị đơn hàng còn lại (tối đa ' . $maxUsablePoints . ' điểm).';
        }
    }

    if (empty($errors)) {
        $pointsValue = $pointsUsed * 1000;
        $finalTotal = max(0, $total - $discount - $pointsValue);

        try {
            $pdo->beginTransaction();

            // Khóa dòng tồn kho + điểm ngay trong transaction để 2 request đồng thời
            // không thể cùng vượt quá số lượng/điểm còn lại (race condition)
            foreach ($items as $item) {
                $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ? FOR UPDATE');
                $stmt->execute([$item['product_id']]);
                $stockRow = $stmt->fetch();
                if (!$stockRow || $stockRow['stock'] < $item['quantity']) {
                    throw new RuntimeException('Sản phẩm "' . $item['name'] . '" không đủ tồn kho.');
                }
            }

            $stmt = $pdo->prepare('SELECT points FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$uid]);
            $lockedPoints = (int) $stmt->fetch()['points'];
            if ($pointsUsed > $lockedPoints) {
                throw new RuntimeException('Số điểm dùng vượt quá số điểm hiện có.');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO orders (code, user_id, total, discount, tier_discount, voucher_discount, final_total, voucher_id, points_used, status)
                 VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([
                $uid, $total, $discount, $tierDiscountAmount, $voucherDiscountAmount,
                $finalTotal, $voucher['id'] ?? null, $pointsUsed,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $code = 'DH' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare('UPDATE orders SET code = ? WHERE id = ?');
            $stmt->execute([$code, $orderId]);

            $stmtItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmtStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($items as $item) {
                $stmtItem->execute([$orderId, $item['product_id'], $item['name'], $item['price'], $item['quantity']]);
                $stmtStock->execute([$item['quantity'], $item['product_id']]);
            }

            if ($pointsUsed > 0) {
                $stmt = $pdo->prepare('UPDATE users SET points = points - ? WHERE id = ?');
                $stmt->execute([$pointsUsed, $uid]);

                $stmt = $pdo->prepare(
                    "INSERT INTO point_history (user_id, order_id, points, type) VALUES (?, ?, ?, 'redeem')"
                );
                $stmt->execute([$uid, $orderId, -$pointsUsed]);
            }

            $pdo->commit();

            unset($_SESSION['cart']);
            // Đánh dấu đơn vừa đặt để order_detail.php mô phỏng cổng thanh toán
            // (tự chuyển sang "Hoàn thành" sau vài giây)
            $_SESSION['auto_complete_order'] = $orderId;
            header('Location: ' . BASE_URL . 'order_detail.php?id=' . $orderId);
            exit;
        } catch (RuntimeException $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Có lỗi xảy ra khi xử lý đơn hàng, vui lòng thử lại.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-credit-card"></i> Xác nhận đơn hàng</h4>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card p-3">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>SL</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['name']) ?></td>
                            <td><?= money($item['price']) ?></td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td><?= money($item['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-end fw-bold fs-5 mt-2">
                Tổng tiền hàng: <?= money($total) ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-3">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <p>
                Hạng thành viên:
                <span class="badge <?= tier_badge_class($tier['name']) ?>"><?= e($tier['name']) ?></span>
                giảm <?= $tierDiscountPercent ?>%
                <?php if ($tierDiscountPercent > 0): ?>
                    (tương đương <strong class="text-success">-<?= money(round($total * $tierDiscountPercent / 100)) ?></strong>)
                <?php endif; ?>
            </p>
            <p>Điểm hiện có: <strong><?= $userPoints ?></strong> điểm (1 điểm = 1.000đ)</p>

            <form method="post" action="">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Mã voucher (nếu có)</label>
                    <input type="text" name="voucher_code" class="form-control" value="<?= e($voucherCode) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Số điểm muốn dùng</label>
                    <input type="number" name="points_used" class="form-control" min="0" max="<?= $userPoints ?>"
                           value="<?= (int) $pointsUsed ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle"></i> Đặt hàng
                </button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
