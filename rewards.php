<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';
require_login();

$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
$stmt->execute([$uid]);
$points = (int) $stmt->fetch()['points'];

$spent = total_spent($pdo, $uid);
$tier = get_tier($pdo, $spent);

$stmt = $pdo->prepare('SELECT * FROM membership_tiers WHERE min_spent > ? ORDER BY min_spent ASC LIMIT 1');
$stmt->execute([$tier['min_spent']]);
$nextTier = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT ph.*, o.code AS order_code
     FROM point_history ph
     LEFT JOIN orders o ON o.id = ph.order_id
     WHERE ph.user_id = ?
     ORDER BY ph.created_at DESC'
);
$stmt->execute([$uid]);
$history = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-gem"></i> Điểm thưởng &amp; hạng thành viên</h4>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-4 text-center h-100">
            <div class="text-muted mb-1">Điểm hiện có</div>
            <div class="fs-2 fw-bold text-primary"><?= $points ?></div>
            <div class="text-muted small">1 điểm = 1.000đ</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center h-100">
            <div class="text-muted mb-1">Hạng hiện tại</div>
            <span class="badge fs-5 <?= tier_badge_class($tier['name']) ?>"><?= e($tier['name']) ?></span>
            <div class="text-muted small mt-2">Giảm <?= (int) $tier['discount_percent'] ?>% mỗi đơn</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center h-100">
            <div class="text-muted mb-1">Tổng chi tiêu</div>
            <div class="fs-5 fw-bold"><?= money($spent) ?></div>
            <?php if ($nextTier): ?>
                <div class="text-muted small mt-2">
                    Chi thêm <strong><?= money($nextTier['min_spent'] - $spent) ?></strong>
                    để lên hạng <span class="badge <?= tier_badge_class($nextTier['name']) ?>"><?= e($nextTier['name']) ?></span>
                </div>
            <?php else: ?>
                <div class="text-success small mt-2">Bạn đang ở hạng cao nhất!</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<h5 class="mb-3">Lịch sử điểm</h5>
<?php if (empty($history)): ?>
    <div class="alert alert-info">Chưa có lịch sử điểm.</div>
<?php else: ?>
    <div class="card p-3">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Loại</th>
                    <th>Điểm</th>
                    <th>Đơn hàng</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?= e(date('d/m/Y H:i', strtotime($h['created_at']))) ?></td>
                        <td>
                            <?php if ($h['type'] === 'earn'): ?>
                                <span class="badge bg-success">Tích điểm</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Dùng điểm</span>
                            <?php endif; ?>
                        </td>
                        <td class="<?= $h['points'] >= 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                            <?= $h['points'] > 0 ? '+' : '' ?><?= (int) $h['points'] ?>
                        </td>
                        <td><?= $h['order_code'] ? e($h['order_code']) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
