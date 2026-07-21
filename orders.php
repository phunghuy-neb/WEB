<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';
require_login();

$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-receipt"></i> Đơn hàng của tôi</h4>

<?php if (empty($orders)): ?>
    <div class="alert alert-info">
        Bạn chưa có đơn hàng nào. <a href="<?= BASE_URL ?>index.php">Mua sắm ngay</a>
    </div>
<?php else: ?>
    <div class="card p-3">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= e($o['code']) ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
                        <td><?= money($o['final_total']) ?></td>
                        <td>
                            <span class="badge <?= order_status_class($o['status']) ?>">
                                <?= e(order_status_label($o['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>order_detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">
                                Xem chi tiết
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
