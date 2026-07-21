<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM users WHERE role = 'customer'";
$params = [];
if ($search !== '') {
    $sql .= ' AND (full_name LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

foreach ($customers as &$c) {
    $c['spent'] = total_spent($pdo, $c['id']);
    $c['tier'] = get_tier($pdo, $c['spent']);
    $c['label'] = customer_label($c['spent'], $c['created_at']);
}
unset($c);

require __DIR__ . '/../includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-people"></i> Danh sách khách hàng</h4>

<form method="get" action="" class="mb-4">
    <div class="input-group" style="max-width:400px;">
        <input type="text" name="search" class="form-control" placeholder="Tìm theo tên hoặc email..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tìm kiếm</button>
    </div>
</form>

<?php if (empty($customers)): ?>
    <div class="alert alert-info">Không tìm thấy khách hàng nào.</div>
<?php else: ?>
    <div class="card p-3">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Tổng chi tiêu</th>
                    <th>Hạng</th>
                    <th>Phân loại</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?= e($c['full_name']) ?></td>
                        <td><?= e($c['email']) ?></td>
                        <td><?= money($c['spent']) ?></td>
                        <td><span class="badge <?= tier_badge_class($c['tier']['name']) ?>"><?= e($c['tier']['name']) ?></span></td>
                        <td><?= e($c['label']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>admin/customer_detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                Xem chi tiết
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
