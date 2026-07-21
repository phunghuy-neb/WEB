<?php
// ======== BACKEND: xử lý dữ liệu ========
// (Không xử lý gì thêm — trạng thái đăng nhập đã có sẵn từ includes/functions.php)
?>
<!-- ======== FRONTEND: giao diện ======== -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<?php if (is_admin()): ?>
<!-- ---- Layout ADMIN: sidebar trái ---- -->
<div class="d-flex">
    <nav class="sidebar bg-dark text-white p-3 vh-100">
        <h5 class="mb-4"><i class="bi bi-shop"></i> Shopping CRM</h5>
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link text-white" href="<?= BASE_URL ?>admin/index.php"><i class="bi bi-graph-up"></i> Thống kê</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?= BASE_URL ?>admin/customers.php"><i class="bi bi-people"></i> Khách hàng</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?= BASE_URL ?>admin/orders.php"><i class="bi bi-receipt"></i> Đơn hàng</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?= BASE_URL ?>admin/products.php"><i class="bi bi-box-seam"></i> Sản phẩm</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?= BASE_URL ?>admin/vouchers.php"><i class="bi bi-ticket-perforated"></i> Voucher</a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-white" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
            </li>
        </ul>
    </nav>
    <main class="flex-fill p-4">
<?php else: ?>
<!-- ---- Layout KHÁCH: navbar ngang ---- -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>index.php"><i class="bi bi-shop"></i> Shopping CRM</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php">Sản phẩm</a></li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>cart.php"><i class="bi bi-cart3"></i> Giỏ hàng</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>wishlist.php"><i class="bi bi-heart"></i> Yêu thích</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>orders.php">Đơn của tôi</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>rewards.php">Điểm &amp; hạng</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>profile.php"><i class="bi bi-person-circle"></i> <?= e($_SESSION['full_name'] ?? '') ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>logout.php">Đăng xuất</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>login.php">Đăng nhập</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>register.php">Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
<?php endif; ?>
