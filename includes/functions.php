<?php
// ======== BACKEND: xử lý dữ liệu ========
// Các hàm dùng chung cho toàn bộ dự án

session_start();

// Đường dẫn gốc (URL) của dự án, tự tính từ URL đang chạy — không hardcode.
// Chỉ có 1 cấp thư mục con là admin/, nên nếu trang đang chạy nằm trong đó
// thì lùi lên 1 cấp để ra đúng thư mục gốc của dự án.
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if (basename($scriptDir) === 'admin') {
    $scriptDir = dirname($scriptDir);
}
define('BASE_URL', rtrim($scriptDir, '/') . '/');

// ---- Kiểm tra đăng nhập / phân quyền ----
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

// ---- Chống XSS khi in dữ liệu ra HTML ----
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// ---- Chống giả mạo yêu cầu (CSRF): sinh & kiểm tra token theo session ----
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf() {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ (CSRF).');
    }
}

// ---- Định dạng số tiền theo VNĐ ----
function money($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}

// ---- Tổng chi tiêu của 1 khách hàng (chỉ tính đơn đã completed) ----
function total_spent($pdo, $uid) {
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(final_total), 0) AS total
         FROM orders WHERE user_id = ? AND status = 'completed'"
    );
    $stmt->execute([$uid]);
    return (int) $stmt->fetch()['total'];
}

// ---- Hạng thành viên theo tổng chi tiêu (hạng cao nhất mà min_spent <= chi tiêu) ----
function get_tier($pdo, $spent) {
    $stmt = $pdo->prepare(
        "SELECT * FROM membership_tiers WHERE min_spent <= ? ORDER BY min_spent DESC LIMIT 1"
    );
    $stmt->execute([$spent]);
    return $stmt->fetch();
}

// ---- % giảm giá của sản phẩm (so sánh original_price với price hiện tại) ----
function product_discount_percent($originalPrice, $price) {
    if (!$originalPrice || $originalPrice <= $price) {
        return 0;
    }
    return (int) round((($originalPrice - $price) / $originalPrice) * 100);
}

// ---- Nhãn phân loại khách hàng cho admin ----
function customer_label($spent, $created_at) {
    if ($spent == 0) {
        return 'Chưa mua';
    }
    $months = (time() - strtotime($created_at)) / (30 * 24 * 3600);
    if ($months < 3) {
        return 'Khách mới';
    }
    if ($spent >= 10000000) {
        return 'Khách VIP';
    }
    return 'Khách thân thiết';
}

// ---- Tính điểm tích lũy: 1 điểm / 10.000đ ----
function calc_points($amount) {
    return (int) floor($amount / 10000);
}

// ---- Class CSS cho badge hạng thành viên ----
function tier_badge_class($tierName) {
    $map = [
        'Đồng' => 'badge-tier-dong',
        'Bạc' => 'badge-tier-bac',
        'Vàng' => 'badge-tier-vang',
        'Kim cương' => 'badge-tier-kim-cuong',
    ];
    return $map[$tierName] ?? 'bg-secondary';
}

// ---- Nhãn tiếng Việt cho trạng thái đơn hàng ----
function order_status_label($status) {
    $map = [
        'pending' => 'Chờ xử lý',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];
    return $map[$status] ?? $status;
}

// ---- Class CSS cho badge trạng thái đơn hàng ----
function order_status_class($status) {
    return 'badge-status-' . $status;
}
