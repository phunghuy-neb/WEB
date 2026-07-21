<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

$totalCustomers = (int) $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role = 'customer'")->fetch()['c'];
$totalOrders = (int) $pdo->query('SELECT COUNT(*) AS c FROM orders')->fetch()['c'];
$revenue = (int) $pdo->query("SELECT COALESCE(SUM(final_total), 0) AS r FROM orders WHERE status = 'completed'")->fetch()['r'];

// Top 5 khách chi tiêu nhiều nhất
$topCustomers = $pdo->query(
    "SELECT u.id, u.full_name, u.email, COALESCE(SUM(o.final_total), 0) AS spent
     FROM users u
     LEFT JOIN orders o ON o.user_id = u.id AND o.status = 'completed'
     WHERE u.role = 'customer'
     GROUP BY u.id
     ORDER BY spent DESC
     LIMIT 5"
)->fetchAll();

// Sản phẩm bán chạy (chỉ tính đơn đã hoàn thành)
$bestSellers = $pdo->query(
    "SELECT oi.product_name, SUM(oi.quantity) AS total_qty, SUM(oi.price * oi.quantity) AS total_revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.status = 'completed'
     GROUP BY oi.product_name
     ORDER BY total_qty DESC
     LIMIT 5"
)->fetchAll();

// Số khách đăng ký theo tháng
$byMonth = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS thang, COUNT(*) AS so_luong
     FROM users
     WHERE role = 'customer'
     GROUP BY thang
     ORDER BY thang ASC"
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-graph-up"></i> Thống kê tổng quan</h4>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-4 text-center h-100">
            <div class="text-muted mb-1">Tổng khách hàng</div>
            <div class="fs-2 fw-bold text-primary"><?= $totalCustomers ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center h-100">
            <div class="text-muted mb-1">Tổng đơn hàng</div>
            <div class="fs-2 fw-bold text-primary"><?= $totalOrders ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center h-100">
            <div class="text-muted mb-1">Doanh thu (đơn hoàn thành)</div>
            <div class="fs-4 fw-bold text-success"><?= money($revenue) ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h6 class="mb-3"><i class="bi bi-trophy"></i> Top 5 khách chi tiêu nhiều nhất</h6>
            <?php if (empty($topCustomers)): ?>
                <p class="text-muted mb-0">Chưa có dữ liệu.</p>
            <?php else: ?>
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>Khách hàng</th><th>Email</th><th class="text-end">Chi tiêu</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCustomers as $c): ?>
                            <tr>
                                <td><?= e($c['full_name']) ?></td>
                                <td><?= e($c['email']) ?></td>
                                <td class="text-end fw-bold"><?= money($c['spent']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h6 class="mb-3"><i class="bi bi-box-seam"></i> Sản phẩm bán chạy</h6>
            <?php if (empty($bestSellers)): ?>
                <p class="text-muted mb-0">Chưa có dữ liệu.</p>
            <?php else: ?>
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>Sản phẩm</th><th class="text-end">Số lượng</th><th class="text-end">Doanh thu</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bestSellers as $p): ?>
                            <tr>
                                <td><?= e($p['product_name']) ?></td>
                                <td class="text-end"><?= (int) $p['total_qty'] ?></td>
                                <td class="text-end fw-bold"><?= money($p['total_revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h6 class="mb-3"><i class="bi bi-person-plus"></i> Khách đăng ký theo tháng</h6>
            <?php if (empty($byMonth)): ?>
                <p class="text-muted mb-0">Chưa có dữ liệu.</p>
            <?php else: ?>
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>Tháng</th><th class="text-end">Số khách mới</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byMonth as $m): ?>
                            <tr>
                                <td><?= e($m['thang']) ?></td>
                                <td class="text-end"><?= (int) $m['so_luong'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
