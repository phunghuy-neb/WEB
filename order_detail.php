<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);

// Chỉ cho xem đơn của chính mình
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . BASE_URL . 'orders.php');
    exit;
}

// Mô phỏng cổng thanh toán: đơn vừa đặt (đánh dấu ở checkout.php) sẽ tự động
// chuyển sang "Hoàn thành" sau vài giây, giống quy trình xác nhận thanh toán thật.
$isSimulatingPayment = $order['status'] === 'pending' && ($_SESSION['auto_complete_order'] ?? null) == $id;

if ($isSimulatingPayment && isset($_GET['confirm_payment'])) {
    // Cộng điểm giống hệt logic khi admin chuyển đơn sang completed (admin/orders.php),
    // kiểm tra point_history để tránh cộng điểm 2 lần nếu lỡ tải lại trang.
    $stmt = $pdo->prepare("SELECT id FROM point_history WHERE order_id = ? AND type = 'earn'");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        $points = calc_points($order['final_total']);

        $stmt = $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?');
        $stmt->execute([$points, $order['user_id']]);

        $stmt = $pdo->prepare(
            "INSERT INTO point_history (user_id, order_id, points, type) VALUES (?, ?, ?, 'earn')"
        );
        $stmt->execute([$order['user_id'], $id, $points]);
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
    $stmt->execute([$id]);
    unset($_SESSION['auto_complete_order']);

    header('Location: ' . BASE_URL . 'order_detail.php?id=' . $id);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$voucherCode = null;
if ($order['voucher_id']) {
    $stmt = $pdo->prepare('SELECT code FROM vouchers WHERE id = ?');
    $stmt->execute([$order['voucher_id']]);
    $v = $stmt->fetch();
    $voucherCode = $v['code'] ?? null;
}

$steps = ['pending', 'confirmed', 'shipping', 'completed'];
$currentIndex = array_search($order['status'], $steps, true);

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<?php if ($isSimulatingPayment): ?>
    <meta http-equiv="refresh" content="5;url=<?= BASE_URL ?>order_detail.php?id=<?= $id ?>&confirm_payment=1">
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
        <div class="spinner-border spinner-border-sm" role="status"></div>
        <div>Đang xử lý thanh toán, đơn hàng sẽ tự động xác nhận sau ít giây...</div>
    </div>
<?php endif; ?>
<a href="<?= BASE_URL ?>orders.php" class="btn btn-link ps-0 mb-3"><i class="bi bi-arrow-left"></i> Quay lại danh sách đơn</a>

<div class="card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-1">Đơn hàng <?= e($order['code']) ?></h5>
            <small class="text-muted"><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></small>
        </div>
        <span class="badge fs-6 <?= order_status_class($order['status']) ?>">
            <?= e(order_status_label($order['status'])) ?>
        </span>
    </div>

    <?php if ($order['status'] === 'cancelled'): ?>
        <div class="alert alert-danger mb-3">Đơn hàng này đã bị hủy.</div>
    <?php else: ?>
        <!-- Thanh tiến trình trạng thái -->
        <div class="d-flex text-center mb-4">
            <?php foreach ($steps as $i => $s): ?>
                <?php $reached = $currentIndex !== false && $i <= $currentIndex; ?>
                <div class="flex-fill">
                    <span class="badge <?= $reached ? order_status_class($s) : 'bg-light text-muted border' ?>">
                        <?= e(order_status_label($s)) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table class="table align-middle mb-3">
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
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= money($item['price']) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td><?= money($item['price'] * $item['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row justify-content-end">
        <div class="col-md-5">
            <table class="table table-borderless mb-0">
                <tr>
                    <td>Tiền hàng</td>
                    <td class="text-end"><?= money($order['total']) ?></td>
                </tr>
                <?php if ($order['tier_discount'] > 0): ?>
                    <tr>
                        <td>Giảm giá hạng thành viên</td>
                        <td class="text-end text-success">- <?= money($order['tier_discount']) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($order['voucher_discount'] > 0): ?>
                    <tr>
                        <td>Giảm giá voucher<?= $voucherCode ? ' (' . e($voucherCode) . ')' : '' ?></td>
                        <td class="text-end text-success">- <?= money($order['voucher_discount']) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($order['tier_discount'] == 0 && $order['voucher_discount'] == 0 && $order['discount'] > 0): ?>
                    <tr>
                        <td>Giảm giá</td>
                        <td class="text-end text-success">- <?= money($order['discount']) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($order['points_used'] > 0): ?>
                    <tr>
                        <td>Dùng điểm (<?= (int) $order['points_used'] ?> điểm)</td>
                        <td class="text-end text-success">- <?= money($order['points_used'] * 1000) ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="fw-bold fs-5 border-top">
                    <td>Thành tiền</td>
                    <td class="text-end text-primary"><?= money($order['final_total']) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
