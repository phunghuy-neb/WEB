<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';
require_login();

$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();

$infoErrors = [];
$passwordErrors = [];
$infoSuccess = null;
$passwordSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'update_info') {
    verify_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($fullName === '') {
        $infoErrors[] = 'Vui lòng nhập họ tên.';
    }

    if (empty($infoErrors)) {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?');
        $stmt->execute([$fullName, $phone, $address, $uid]);

        $_SESSION['full_name'] = $fullName;
        $user['full_name'] = $fullName;
        $user['phone'] = $phone;
        $user['address'] = $address;
        $infoSuccess = 'Cập nhật thông tin thành công.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'change_password') {
    verify_csrf();

    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!password_verify($oldPassword, $user['password'])) {
        $passwordErrors[] = 'Mật khẩu cũ không đúng.';
    }
    if (strlen($newPassword) < 6) {
        $passwordErrors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    }
    if ($newPassword !== $confirmPassword) {
        $passwordErrors[] = 'Xác nhận mật khẩu không khớp.';
    }

    if (empty($passwordErrors)) {
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $uid]);
        $passwordSuccess = 'Đổi mật khẩu thành công.';
    }
}

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-person-circle"></i> Tài khoản của tôi</h4>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card p-4">
            <h5 class="mb-3">Thông tin cá nhân</h5>

            <?php if ($infoSuccess): ?>
                <div class="alert alert-success"><?= e($infoSuccess) ?></div>
            <?php endif; ?>
            <?php if (!empty($infoErrors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($infoErrors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="update_info">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Họ tên</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($user['phone']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" name="address" class="form-control" value="<?= e($user['address']) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-4">
            <h5 class="mb-3">Đổi mật khẩu</h5>

            <?php if ($passwordSuccess): ?>
                <div class="alert alert-success"><?= e($passwordSuccess) ?></div>
            <?php endif; ?>
            <?php if (!empty($passwordErrors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($passwordErrors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="change_password">
                <div class="mb-3">
                    <label class="form-label">Mật khẩu cũ</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu mới</label>
                    <input type="password" name="new_password" class="form-control" required>
                    <div class="form-text">Ít nhất 6 ký tự.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
