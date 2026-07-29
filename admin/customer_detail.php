<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: ' . BASE_URL . 'admin/customers.php');
    exit;
}

// ---- Khóa / mở khóa tài khoản (đảo trạng thái hiện tại) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'toggle_status') {
    verify_csrf();

    $newStatus = $customer['status'] === 'locked' ? 'active' : 'locked';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'customer'");
    $stmt->execute([$newStatus, $id]);

    header('Location: ' . BASE_URL . 'admin/customer_detail.php?id=' . $id);
    exit;
}

$spent = total_spent($pdo, $id);
$tier = get_tier($pdo, $spent);
$label = customer_label($spent, $customer['created_at']);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$id]);
$orders = $stmt->fetchAll();

// Sản phẩm mua nhiều nhất (không tính đơn đã hủy)
$stmt = $pdo->prepare(
    "SELECT oi.product_name, SUM(oi.quantity) AS total_qty
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.user_id = ? AND o.status != 'cancelled'
     GROUP BY oi.product_name
     ORDER BY total_qty DESC
     LIMIT 5"
);
$stmt->execute([$id]);
$topProducts = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<a href="<?= BASE_URL ?>admin/customers.php" class="btn btn-link ps-0 mb-3"><i class="bi bi-arrow-left"></i> Quay lại danh sách khách hàng</a>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card p-4 h-100">
            <h5 class="mb-3">Thông tin cá nhân</h5>
            <p class="mb-1"><strong>Họ tên:</strong> <?= e($customer['full_name']) ?></p>
            <p class="mb-1"><strong>Email:</strong> <?= e($customer['email']) ?></p>
            <p class="mb-1"><strong>Điện thoại:</strong> <?= e($customer['phone']) ?></p>
            <p class="mb-1"><strong>Địa chỉ:</strong> <?= e($customer['address']) ?></p>
            <p class="mb-1"><strong>Ngày đăng ký:</strong> <?= e(date('d/m/Y', strtotime($customer['created_at']))) ?></p>
            <p class="mb-0"><strong>Trạng thái:</strong>
                <span class="badge <?= $customer['status'] === 'locked' ? 'bg-danger' : 'bg-success' ?>">
                    <?= $customer['status'] === 'locked' ? 'Đã khóa' : 'Hoạt động' ?>
                </span>
            </p>
            <form method="post" action="" class="mt-3"
                  onsubmit="return confirm('<?= $customer['status'] === 'locked' ? 'Bạn có chắc muốn mở khóa tài khoản này?' : 'Bạn có chắc muốn khóa tài khoản này? Khách sẽ không thể đăng nhập nữa.' ?>')">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="toggle_status">
                <?php if ($customer['status'] === 'locked'): ?>
                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-unlock"></i> Mở khóa tài khoản</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-lock"></i> Khóa tài khoản</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-4 h-100">
            <h5 class="mb-3">Tổng quan mua sắm</h5>
            <p class="mb-1"><strong>Tổng chi tiêu:</strong> <?= money($spent) ?></p>
            <p class="mb-1"><strong>Hạng thành viên:</strong>
                <span class="badge <?= tier_badge_class($tier['name']) ?>"><?= e($tier['name']) ?></span>
            </p>
            <p class="mb-1"><strong>Phân loại:</strong> <?= e($label) ?></p>
            <p class="mb-0"><strong>Điểm hiện có:</strong> <?= (int) $customer['points'] ?> điểm</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card p-3 h-100">
            <h6 class="mb-3"><i class="bi bi-receipt"></i> Lịch sử mua hàng</h6>
            <?php if (empty($orders)): ?>
                <p class="text-muted mb-0">Khách hàng chưa có đơn hàng nào.</p>
            <?php else: ?>
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>Mã đơn</th><th>Ngày</th><th class="text-end">Tổng tiền</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><?= e($o['code']) ?></td>
                                <td><?= e(date('d/m/Y', strtotime($o['created_at']))) ?></td>
                                <td class="text-end"><?= money($o['final_total']) ?></td>
                                <td>
                                    <span class="badge <?= order_status_class($o['status']) ?>">
                                        <?= e(order_status_label($o['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-3 h-100">
            <h6 class="mb-3"><i class="bi bi-star"></i> Sản phẩm mua nhiều nhất</h6>
            <?php if (empty($topProducts)): ?>
                <p class="text-muted mb-0">Chưa có dữ liệu.</p>
            <?php else: ?>
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>Sản phẩm</th><th class="text-end">Số lượng</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $p): ?>
                            <tr>
                                <td><?= e($p['product_name']) ?></td>
                                <td class="text-end"><?= (int) $p['total_qty'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
