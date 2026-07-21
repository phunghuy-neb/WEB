# Dự án: Web quản lý khách hàng mua sắm (bài tập môn Lập trình Web)

## Ràng buộc bắt buộc — KHÔNG được vi phạm
- PHP THUẦN. KHÔNG framework, KHÔNG Composer, KHÔNG router, KHÔNG OOP nặng, KHÔNG API/AJAX.
- Kiến trúc server-render: mỗi trang là 1 file .php chứa CẢ xử lý (BE) lẫn giao diện (FE).
- Giao diện: Bootstrap 5 qua CDN + Bootstrap Icons CDN. KHÔNG tải về, KHÔNG build.
- CSDL: MySQL, kết nối bằng PDO.
- Chạy trên XAMPP cả Linux lẫn Windows:
  - Tên file TOÀN CHỮ THƯỜNG (Linux phân biệt hoa/thường).
  - include/require dùng __DIR__, KHÔNG hardcode đường dẫn tuyệt đối.
- Ưu tiên ĐƠN GIẢN, DỄ GIẢI THÍCH cho giáo viên. Không kỹ thuật cao siêu.
- Toàn bộ giao diện, thông báo, comment: TIẾNG VIỆT.

## Quy ước tách BE / FE trong MỖI file (quan trọng — để trình bày với giáo viên)
- BE (xử lý PHP: truy vấn DB, $_POST, $_SESSION, header redirect) DỒN HẾT LÊN ĐẦU FILE.
- FE (HTML + Bootstrap) nằm DƯỚI, sau dòng `require 'includes/header.php'`.
- Vùng FE chỉ được echo/<?= ?> biến đã tính sẵn — TUYỆT ĐỐI không viết $pdo->query giữa HTML.
- Mỗi file có comment mốc rõ ràng:
    // ======== BACKEND: xử lý dữ liệu ========
    (và trước phần HTML)
    <!-- ======== FRONTEND: giao diện ======== -->

## Quy ước kỹ thuật (áp dụng nhất quán)
- config/db.php: tạo biến $pdo (PDO, ERRMODE_EXCEPTION, FETCH_ASSOC, EMULATE_PREPARES=false),
  host=localhost, user=root, pass='' (mặc định XAMPP), charset=utf8mb4.
- MỌI truy vấn có tham số dùng prepared statement ($pdo->prepare + execute) — chống SQL Injection.
- Mật khẩu: password_hash() khi lưu, password_verify() khi đăng nhập.
- includes/functions.php gom hàm dùng chung: session_start, is_logged_in(), is_admin(),
  require_login(), require_admin(), e() (htmlspecialchars chống XSS), money() (định dạng VNĐ),
  total_spent($pdo,$uid), get_tier($pdo,$spent), customer_label(...), calc_points($amount).
- Trang admin: gọi require_admin() ở dòng đầu. Trang khách cần đăng nhập: require_login().
- includes/header.php + footer.php: layout chung. Admin dùng SIDEBAR TRÁI
  (menu: Thống kê, Khách hàng, Đơn hàng, Sản phẩm, Voucher). Khách dùng navbar ngang.

## Giao diện (FE) — Bootstrap tùy biến VỪA PHẢI (không để trần)
- Thêm assets/css/style.css: 1 màu chủ đạo (xanh #2563eb), bo góc card, đổ bóng nhẹ, thoáng.
- Trạng thái đơn hiển thị bằng badge màu: pending=xám, confirmed=xanh dương, shipping=vàng,
  completed=xanh lá, cancelled=đỏ.
- Hạng thành viên hiển thị badge màu theo hạng (Đồng/Bạc/Vàng/Kim cương).
- Dùng Bootstrap Icons cho menu và nút. Form có label rõ, responsive bằng lưới col-md-*.
- Không animation phức tạp, không thư viện ngoài Bootstrap.

## Quy tắc nghiệp vụ
- Tổng chi tiêu = SUM(final_total) các đơn status='completed'. TÍNH BẰNG SQL, không lưu cứng.
- Hạng thành viên = hạng cao nhất mà min_spent <= tổng chi tiêu.
- Phân loại KH (nhãn admin): chi tiêu=0 -> "Chưa mua"; đăng ký < 3 tháng -> "Khách mới";
  chi tiêu >= 10tr -> "Khách VIP"; còn lại -> "Khách thân thiết".
- Tích điểm: 1 điểm / 10.000đ, cộng vào users.points + ghi point_history khi đơn -> 'completed'.
- Dùng điểm: 1 điểm = 1.000đ, trừ vào final_total khi checkout, ghi point_history (số âm).
- Voucher: giảm % nếu đơn >= min_order, còn hạn (expire_date) và active=1.

## Cấu trúc thư mục (tạo đúng như sau)
shopping-crm/
├── config/db.php                    (BE thuần: kết nối PDO)
├── includes/functions.php           (BE thuần: hàm chung)
├── includes/header.php              (FE thuần: layout đầu trang)
├── includes/footer.php              (FE thuần: layout cuối trang)
├── assets/css/style.css             (FE: CSS tùy chỉnh)
├── assets/uploads/                  (ảnh sản phẩm)
├── sql/schema.sql                   (tạo DB + 9 bảng + dữ liệu mẫu)
├── index.php                        (khách: danh sách sản phẩm)
├── product.php                      (khách: chi tiết sản phẩm)
├── register.php / login.php         (khách: xác thực)
├── logout.php                       (BE thuần: hủy session + redirect)
├── profile.php                      (khách: tài khoản cá nhân)
├── add_to_cart.php                  (BE thuần: thêm giỏ + redirect)
├── cart.php / checkout.php          (khách: giỏ hàng + đặt hàng)
├── orders.php / order_detail.php    (khách: đơn của tôi)
├── rewards.php                      (khách: ví điểm & hạng)
├── wishlist.php                     (khách: sản phẩm yêu thích)
└── admin/
    ├── index.php                    (dashboard thống kê)
    ├── customers.php                (danh sách khách)
    ├── customer_detail.php          (hồ sơ 1 khách)
    ├── orders.php                   (quản lý đơn + đổi trạng thái)
    ├── products.php                 (CRUD sản phẩm + danh mục)
    └── vouchers.php                 (CRUD voucher)

## Cách làm việc
- Làm theo GIAI ĐOẠN tôi yêu cầu, KHÔNG làm trước các giai đoạn sau.
- Sau mỗi giai đoạn: liệt kê file đã tạo/sửa và DỪNG LẠI để tôi test.
- Trước khi báo xong, tự chạy `php -l` lint từng file .php vừa tạo (nếu có PHP CLI).