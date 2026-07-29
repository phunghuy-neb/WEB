<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . (is_admin() ? 'admin/index.php' : 'index.php'));
    exit;
}

$errors = [];
$email = '';

$flash = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Vui lòng nhập email và mật khẩu.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Tài khoản bị khóa: không gán session, chặn đăng nhập
            if ($user['status'] === 'locked') {
                $errors[] = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                header('Location: ' . BASE_URL . ($user['role'] === 'admin' ? 'admin/index.php' : 'index.php'));
                exit;
            }
        } else {
            $errors[] = 'Email hoặc mật khẩu không đúng.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card p-4">
            <h4 class="mb-3 text-center"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</h4>

            <?php if ($flash): ?>
                <div class="alert alert-success"><?= e($flash) ?></div>
            <?php endif; ?>

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
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
            </form>

            <p class="text-center mt-3 mb-0">
                Chưa có tài khoản? <a href="<?= BASE_URL ?>register.php">Đăng ký</a>
            </p>

            <div class="alert alert-info mt-3 mb-0 small">
                <strong>Tài khoản demo</strong> (mật khẩu chung: <code>admin123</code>)<br>
                Admin: <code>admin@shop.vn</code><br>
                Khách: <code>a@gmail.com</code>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
