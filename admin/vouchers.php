<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

// Xóa voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete_voucher') {
    verify_csrf();
    $stmt = $pdo->prepare('DELETE FROM vouchers WHERE id = ?');
    $stmt->execute([(int) ($_POST['voucher_id'] ?? 0)]);
    header('Location: ' . BASE_URL . 'admin/vouchers.php');
    exit;
}

// Thêm / sửa voucher
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['form_action'] ?? '', ['add_voucher', 'edit_voucher'], true)) {
    verify_csrf();

    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discountPercent = (int) ($_POST['discount_percent'] ?? 0);
    $minOrder = (int) ($_POST['min_order'] ?? 0);
    $expireDate = trim($_POST['expire_date'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    $voucherId = (int) ($_POST['voucher_id'] ?? 0);

    if ($code === '') {
        $errors[] = 'Vui lòng nhập mã voucher.';
    }
    if ($discountPercent < 1 || $discountPercent > 100) {
        $errors[] = 'Phần trăm giảm giá phải từ 1 đến 100.';
    }
    if ($expireDate === '') {
        $errors[] = 'Vui lòng chọn hạn sử dụng.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM vouchers WHERE code = ? AND id != ?');
        $stmt->execute([$code, $voucherId]);
        if ($stmt->fetch()) {
            $errors[] = 'Mã voucher đã tồn tại.';
        }
    }

    if (empty($errors)) {
        if ($_POST['form_action'] === 'add_voucher') {
            $stmt = $pdo->prepare(
                'INSERT INTO vouchers (code, discount_percent, min_order, expire_date, active)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$code, $discountPercent, $minOrder, $expireDate, $active]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE vouchers SET code = ?, discount_percent = ?, min_order = ?, expire_date = ?, active = ?
                 WHERE id = ?'
            );
            $stmt->execute([$code, $discountPercent, $minOrder, $expireDate, $active, $voucherId]);
        }

        header('Location: ' . BASE_URL . 'admin/vouchers.php');
        exit;
    }
}

$editVoucher = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM vouchers WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editVoucher = $stmt->fetch();
}

$vouchers = $pdo->query('SELECT * FROM vouchers ORDER BY id DESC')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<!-- ======== FRONTEND: giao diện ======== -->
<h4 class="mb-4"><i class="bi bi-ticket-perforated"></i> Quản lý voucher</h4>

<div class="card p-3 mb-4">
    <h6 class="mb-3"><?= $editVoucher ? 'Sửa voucher' : 'Thêm voucher mới' ?></h6>

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
        <input type="hidden" name="form_action" value="<?= $editVoucher ? 'edit_voucher' : 'add_voucher' ?>">
        <?php if ($editVoucher): ?>
            <input type="hidden" name="voucher_id" value="<?= $editVoucher['id'] ?>">
        <?php endif; ?>

        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Mã voucher</label>
                <input type="text" name="code" class="form-control" value="<?= e($editVoucher['code'] ?? '') ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">% giảm giá</label>
                <input type="number" name="discount_percent" class="form-control" min="1" max="100"
                       value="<?= e($editVoucher['discount_percent'] ?? '') ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Đơn tối thiểu (đ)</label>
                <input type="number" name="min_order" class="form-control" min="0"
                       value="<?= e($editVoucher['min_order'] ?? 0) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Hạn sử dụng</label>
                <input type="date" name="expire_date" class="form-control"
                       value="<?= e($editVoucher['expire_date'] ?? '') ?>" required>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <input type="checkbox" name="active" class="form-check-input" id="activeCheck"
                        <?= (!$editVoucher || $editVoucher['active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activeCheck">Đang hoạt động</label>
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <?= $editVoucher ? 'Cập nhật' : 'Thêm mới' ?>
                </button>
                <?php if ($editVoucher): ?>
                    <a href="<?= BASE_URL ?>admin/vouchers.php" class="btn btn-outline-secondary">Hủy</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="card p-3">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Mã</th>
                <th>% giảm</th>
                <th>Đơn tối thiểu</th>
                <th>Hạn dùng</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vouchers as $v): ?>
                <?php
                if (!$v['active']) {
                    $statusLabel = 'Ngừng';
                    $statusClass = 'bg-secondary';
                } elseif (strtotime($v['expire_date']) < strtotime('today')) {
                    $statusLabel = 'Hết hạn';
                    $statusClass = 'bg-danger';
                } else {
                    $statusLabel = 'Hoạt động';
                    $statusClass = 'bg-success';
                }
                ?>
                <tr>
                    <td><?= e($v['code']) ?></td>
                    <td><?= (int) $v['discount_percent'] ?>%</td>
                    <td><?= money($v['min_order']) ?></td>
                    <td><?= e(date('d/m/Y', strtotime($v['expire_date']))) ?></td>
                    <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                    <td>
                        <a href="<?= BASE_URL ?>admin/vouchers.php?edit=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form method="post" action="" class="d-inline" onsubmit="return confirm('Xóa voucher này?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="form_action" value="delete_voucher">
                            <input type="hidden" name="voucher_id" value="<?= $v['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
