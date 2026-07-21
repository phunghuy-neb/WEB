<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

$validStatuses = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];

// Cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    verify_csrf();

    $orderId = (int) $_POST['order_id'];
    $newStatus = $_POST['status'];

    if (in_array($newStatus, $validStatuses, true)) {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if ($order) {
            // Chỉ cộng điểm nếu đơn CHUYỂN sang completed và CHƯA từng được cộng điểm
            // (kiểm tra bằng point_history thay vì so sánh trạng thái trước đó, để tránh
            // cộng điểm lần nữa khi đơn từng đi qua chu kỳ completed -> cancelled -> completed)
            if ($newStatus === 'completed') {
                $stmt = $pdo->prepare(
                    "SELECT id FROM point_history WHERE order_id = ? AND type = 'earn'"
                );
                $stmt->execute([$orderId]);

                if (!$stmt->fetch()) {
                    $points = calc_points($order['final_total']);

                    $stmt = $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?');
                    $stmt->execute([$points, $order['user_id']]);

                    $stmt = $pdo->prepare(
                        "INSERT INTO point_history (user_id, order_id, points, type) VALUES (?, ?, ?, 'earn')"
                    );
                    $stmt->execute([$order['user_id'], $orderId, $points]);
                }
            }

            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $orderId]);
        }
    }

    header('Location: ' . BASE_URL . 'admin/orders.php');
    exit;
}

$orders = $pdo->query(
    "SELECT o.*, u.full_name, u.email
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC"
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-receipt"></i> Quản lý đơn hàng</h4>

<?php if (empty($orders)): ?>
    <div class="alert alert-info">Chưa có đơn hàng nào.</div>
<?php else: ?>
    <div class="card p-3">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th style="width:260px;">Cập nhật</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= e($o['code']) ?></td>
                        <td><?= e($o['full_name']) ?><br><small class="text-muted"><?= e($o['email']) ?></small></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
                        <td><?= money($o['final_total']) ?></td>
                        <td>
                            <span class="badge <?= order_status_class($o['status']) ?>">
                                <?= e(order_status_label($o['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" action="" class="d-flex gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <?php foreach ($validStatuses as $s): ?>
                                        <option value="<?= $s ?>" <?= $s === $o['status'] ? 'selected' : '' ?>>
                                            <?= e(order_status_label($s)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Cập nhật</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
