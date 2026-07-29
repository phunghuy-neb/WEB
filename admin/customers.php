<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

$errors = [];

// Thêm khách hàng mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_customer') {
    verify_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($fullName === '') {
        $errors[] = 'Vui lòng nhập họ tên.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email này đã tồn tại.';
        }
    }
    if (mb_strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, password, phone, address, role)
             VALUES (?, ?, ?, ?, ?, 'customer')"
        );
        $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $address]);

        header('Location: ' . BASE_URL . 'admin/customers.php');
        exit;
    }
}

// Sửa khách hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'edit_customer') {
    verify_csrf();

    $customerId = (int) ($_POST['customer_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($fullName === '') {
        $errors[] = 'Vui lòng nhập họ tên.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $customerId]);
        if ($stmt->fetch()) {
            $errors[] = 'Email này đã được dùng bởi tài khoản khác.';
        }
    }
    if ($password !== '' && mb_strlen($password) < 6) {
        $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?
             WHERE id = ? AND role = 'customer'"
        );
        $stmt->execute([$fullName, $email, $phone, $address, $customerId]);

        // Chỉ đổi mật khẩu khi ô mật khẩu có nhập
        if ($password !== '') {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'customer'");
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $customerId]);
        }

        header('Location: ' . BASE_URL . 'admin/customers.php');
        exit;
    }
}

// Khách hàng đang sửa (nếu có)
$editCustomer = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([(int) $_GET['edit']]);
    $editCustomer = $stmt->fetch();
}

// Chỉ hiện form khi bấm Thêm khách hàng (?new=1) hoặc Sửa (?edit=<id>)
$showForm = isset($_GET['new']) || $editCustomer;

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

<div class="d-flex flex-wrap gap-2 mb-4">
    <form method="get" action="" class="mb-0">
        <div class="input-group" style="max-width:400px;">
            <input type="text" name="search" class="form-control" placeholder="Tìm theo tên hoặc email..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tìm kiếm</button>
        </div>
    </form>
    <a href="<?= BASE_URL ?>admin/customers.php?new=1#customerForm" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Thêm khách hàng
    </a>
</div>

<?php if ($showForm): ?>
    <div class="card p-3 mb-4 <?= $editCustomer ? 'border border-primary border-2' : '' ?>" id="customerForm">
        <h6 class="mb-3"><?= $editCustomer ? 'Sửa khách hàng' : 'Thêm khách hàng mới' ?></h6>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="<?= $editCustomer ? 'edit_customer' : 'add_customer' ?>">
            <?php if ($editCustomer): ?>
                <input type="hidden" name="customer_id" value="<?= $editCustomer['id'] ?>">
            <?php endif; ?>

            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Họ tên</label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?= e($editCustomer['full_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= e($editCustomer['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" <?= $editCustomer ? '' : 'required' ?>>
                    <?php if ($editCustomer): ?>
                        <div class="form-text">Để trống nếu không đổi mật khẩu.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= e($editCustomer['phone'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" name="address" class="form-control"
                           value="<?= e($editCustomer['address'] ?? '') ?>">
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <?= $editCustomer ? 'Cập nhật khách hàng' : 'Thêm khách hàng' ?>
                </button>
                <a href="<?= BASE_URL ?>admin/customers.php" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
<?php endif; ?>

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
                    <th>Trạng thái</th>
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
                            <span class="badge <?= $c['status'] === 'locked' ? 'bg-danger' : 'bg-success' ?>">
                                <?= $c['status'] === 'locked' ? 'Đã khóa' : 'Hoạt động' ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>admin/customers.php?edit=<?= $c['id'] ?>#customerForm" class="btn btn-sm btn-outline-primary">
                                Sửa
                            </a>
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
