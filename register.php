<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . (is_admin() ? 'admin/index.php' : 'index.php'));
    exit;
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['phone'] = trim($_POST['phone'] ?? '');
    $old['address'] = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($old['full_name'] === '') {
        $errors[] = 'Vui lòng nhập họ tên.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'Email đã được sử dụng.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, password, phone, address, role)
             VALUES (?, ?, ?, ?, ?, \'customer\')'
        );
        $stmt->execute([
            $old['full_name'],
            $old['email'],
            password_hash($password, PASSWORD_DEFAULT),
            $old['phone'],
            $old['address'],
        ]);

        $_SESSION['flash_success'] = 'Đăng ký thành công, vui lòng đăng nhập.';
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card p-4">
            <h4 class="mb-3 text-center"><i class="bi bi-person-plus"></i> Đăng ký tài khoản</h4>

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
                <div class="mb-3">
                    <label class="form-label">Họ tên</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($old['full_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($old['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                    <div class="form-text">Ít nhất 6 ký tự.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($old['phone']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" name="address" class="form-control" value="<?= e($old['address']) ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100">Đăng ký</button>
            </form>

            <p class="text-center mt-3 mb-0">
                Đã có tài khoản? <a href="<?= BASE_URL ?>login.php">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
