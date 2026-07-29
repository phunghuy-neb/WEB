-- ========================================================
-- CSDL: shopping_crm — Web quản lý khách hàng mua sắm
-- Tạo database, 9 bảng và dữ liệu mẫu để test ngay các thống kê
-- ========================================================

CREATE DATABASE IF NOT EXISTS shopping_crm
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE shopping_crm;

-- ---------- 1. users: tài khoản khách hàng + admin ----------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  address VARCHAR(255),
  role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  points INT NOT NULL DEFAULT 0,
  status ENUM('active','locked') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- 2. membership_tiers: hạng thành viên ----------
CREATE TABLE membership_tiers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  min_spent INT NOT NULL DEFAULT 0,
  discount_percent INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- 3. categories: danh mục sản phẩm ----------
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- 4. products: sản phẩm ----------
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category_id INT,
  price INT NOT NULL DEFAULT 0,
  original_price INT DEFAULT NULL,
  stock INT NOT NULL DEFAULT 0,
  image VARCHAR(255),
  description TEXT,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- 5. orders: đơn hàng ----------
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  total INT NOT NULL DEFAULT 0,
  discount INT NOT NULL DEFAULT 0,
  tier_discount INT NOT NULL DEFAULT 0,
  voucher_discount INT NOT NULL DEFAULT 0,
  final_total INT NOT NULL DEFAULT 0,
  voucher_id INT,
  points_used INT NOT NULL DEFAULT 0,
  status ENUM('pending','confirmed','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- 6. order_items: chi tiết đơn hàng ----------
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT,
  product_name VARCHAR(150) NOT NULL,
  price INT NOT NULL DEFAULT 0,
  quantity INT NOT NULL DEFAULT 1,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- 7. vouchers: mã giảm giá ----------
CREATE TABLE vouchers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  discount_percent INT NOT NULL DEFAULT 0,
  min_order INT NOT NULL DEFAULT 0,
  expire_date DATE,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- 8. point_history: lịch sử tích/dùng điểm ----------
CREATE TABLE point_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  order_id INT,
  points INT NOT NULL,
  type ENUM('earn','redeem') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- 9. wishlists: sản phẩm yêu thích ----------
CREATE TABLE wishlists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  UNIQUE KEY uq_user_product (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ========================================================
-- DỮ LIỆU MẪU
-- ========================================================

-- 4 hạng thành viên
INSERT INTO membership_tiers (name, min_spent, discount_percent) VALUES
('Đồng', 0, 0),
('Bạc', 1000000, 3),
('Vàng', 5000000, 5),
('Kim cương', 10000000, 8);

-- 1 admin + 4 khách (ngày đăng ký khác nhau để test thống kê)
-- Mật khẩu chung cho tất cả: admin123 (đã băm bcrypt)
INSERT INTO users (id, full_name, email, password, phone, address, role, points, created_at) VALUES
(1, 'Quản trị viên', 'admin@shop.vn', '$2y$10$iESlAlNCobtMdTYnu5mYYeLg/9Wa5hDSdwWCvCrhldsTspyFf1v/e', '0900000000', 'Hà Nội', 'admin', 0, '2025-01-01 08:00:00'),
(2, 'Nguyễn Văn A', 'a@gmail.com', '$2y$10$iESlAlNCobtMdTYnu5mYYeLg/9Wa5hDSdwWCvCrhldsTspyFf1v/e', '0911111111', 'Quận 1, TP.HCM', 'customer', 5000, '2024-11-15 09:30:00'),
(3, 'Trần Thị B', 'b@gmail.com', '$2y$10$iESlAlNCobtMdTYnu5mYYeLg/9Wa5hDSdwWCvCrhldsTspyFf1v/e', '0922222222', 'Cầu Giấy, Hà Nội', 'customer', 300, '2026-06-01 14:00:00'),
(4, 'Lê Văn C', 'c@gmail.com', '$2y$10$iESlAlNCobtMdTYnu5mYYeLg/9Wa5hDSdwWCvCrhldsTspyFf1v/e', '0933333333', 'Hải Châu, Đà Nẵng', 'customer', 650, '2025-03-10 10:15:00'),
(5, 'Phạm Thị D', 'd@gmail.com', '$2y$10$iESlAlNCobtMdTYnu5mYYeLg/9Wa5hDSdwWCvCrhldsTspyFf1v/e', '0944444444', 'Ninh Kiều, Cần Thơ', 'customer', 0, '2026-07-15 16:45:00');

-- 4 danh mục
INSERT INTO categories (id, name) VALUES
(1, 'Điện thoại'),
(2, 'Laptop'),
(3, 'Phụ kiện'),
(4, 'Hàng giảm giá');

-- 26 sản phẩm (6 mẫu ban đầu + 20 bổ sung, kèm ảnh thật trong assets/uploads/)
INSERT INTO products (id, name, category_id, price, original_price, stock, image, description) VALUES
(1, 'iPhone 15', 1, 20000000, NULL, 20, 'sp_iphone15.jpg', 'Điện thoại iPhone 15 chính hãng, bảo hành 12 tháng.'),
(2, 'Samsung Galaxy S24', 1, 18000000, NULL, 15, 'sp_galaxys24.jpg', 'Điện thoại Samsung Galaxy S24 cao cấp, camera AI.'),
(3, 'MacBook Air M2', 2, 25000000, NULL, 10, 'sp_macbookairm2.jpg', 'Laptop MacBook Air chip M2, mỏng nhẹ, pin trâu.'),
(4, 'Dell XPS 13', 4, 17600000, 22000000, 8, 'sp_dellxps13.jpg', 'Laptop Dell XPS 13 màn hình InfinityEdge, hiệu năng cao. Đang giảm giá 20%.'),
(5, 'Tai nghe AirPods Pro', 3, 5000000, NULL, 30, 'sp_airpodspro.jpg', 'Tai nghe không dây chống ồn chủ động.'),
(6, 'Bàn phím cơ Logitech', 3, 1500000, NULL, 25, 'sp_logitechkeyboard.jpg', 'Bàn phím cơ chơi game, đèn RGB.'),
-- Điện thoại
(7, 'iPhone 15 Pro Max', 4, 27200000, 32000000, 12, 'sp_iphone15promax.jpg', 'Điện thoại cao cấp nhất dòng iPhone 15, khung titan, camera zoom quang 5x. Đang giảm giá 15%.'),
(8, 'Samsung Galaxy Z Fold5', 1, 40000000, NULL, 6, 'sp_galaxyzfold5.jpg', 'Điện thoại màn hình gập Samsung, đa nhiệm mạnh mẽ.'),
(9, 'Xiaomi 14', 1, 15000000, NULL, 18, 'sp_xiaomi14.jpg', 'Điện thoại Xiaomi 14 camera hợp tác Leica, hiệu năng flagship.'),
(10, 'OPPO Reno11', 1, 12000000, NULL, 20, 'sp_opporeno11.jpg', 'Điện thoại OPPO Reno11 thiết kế mỏng nhẹ, camera chân dung AI.'),
(11, 'Google Pixel 8', 1, 19000000, NULL, 10, 'sp_googlepixel8.jpg', 'Điện thoại Google Pixel 8, chip Tensor G3, camera chụp đêm xuất sắc.'),
(12, 'Vivo V30', 1, 10500000, NULL, 15, 'sp_vivov30.jpg', 'Điện thoại Vivo V30 camera Zeiss, quay chân dung nổi bật.'),
-- Laptop
(13, 'MacBook Pro 14 M3', 2, 45000000, NULL, 8, 'sp_macbookpro14.jpg', 'Laptop MacBook Pro 14 inch chip M3, màn hình Liquid Retina XDR.'),
(14, 'Asus ZenBook 14 OLED', 2, 20000000, NULL, 14, 'sp_asuszenbook14.jpg', 'Laptop mỏng nhẹ màn hình OLED sắc nét, phù hợp văn phòng.'),
(15, 'Lenovo ThinkPad X1 Carbon', 2, 35000000, NULL, 7, 'sp_thinkpadx1carbon.jpg', 'Laptop doanh nhân bền bỉ, bàn phím gõ êm, bảo mật cao.'),
(16, 'HP Spectre x360', 2, 28000000, NULL, 9, 'sp_hpspectrex360.jpg', 'Laptop 2-trong-1 xoay gập 360 độ, thiết kế sang trọng.'),
(17, 'Acer Swift 5', 2, 18500000, NULL, 16, 'sp_acerswift5.jpg', 'Laptop siêu nhẹ, hiệu năng ổn định cho công việc hàng ngày.'),
(18, 'LG Gram 16', 2, 30000000, NULL, 10, 'sp_lggram16.jpg', 'Laptop 16 inch cực nhẹ, pin dùng cả ngày dài.'),
-- Phụ kiện
(19, 'Chuột Logitech MX Master 3S', 3, 2500000, NULL, 40, 'sp_logitechmxmaster3s.jpg', 'Chuột không dây cao cấp, cuộn nhanh, phù hợp dân văn phòng.'),
(20, 'Sạc dự phòng Anker 20000mAh', 3, 900000, NULL, 50, 'sp_ankerpowerbank.jpg', 'Pin sạc dự phòng dung lượng lớn, sạc nhanh 2 chiều.'),
(21, 'Loa Bluetooth JBL Flip 6', 4, 2100000, 2800000, 25, 'sp_jblflip6.jpg', 'Loa Bluetooth chống nước IP67, âm bass mạnh mẽ. Đang giảm giá 25%.'),
(22, 'Ốp lưng chống sốc đa năng', 3, 300000, NULL, 60, 'sp_ophongchongshoc.jpg', 'Ốp lưng bảo vệ điện thoại toàn diện, chống sốc 4 góc.'),
(23, 'Cáp sạc USB-C to USB-C 2m', 3, 150000, NULL, 100, 'sp_capusbc.jpg', 'Cáp sạc nhanh chuẩn USB-C, dài 2m, bền bỉ.'),
(24, 'Balo laptop chống nước', 3, 600000, NULL, 35, 'sp_balolaptop.jpg', 'Balo đựng laptop chống thấm nước, nhiều ngăn tiện lợi.'),
(25, 'Webcam Logitech C920', 3, 1800000, NULL, 20, 'sp_webcamc920.jpg', 'Webcam Full HD 1080p, hình ảnh sắc nét cho học tập/họp online.'),
(26, 'Tai nghe nhét tai Galaxy Buds2 Pro', 4, 2800000, 3500000, 22, 'sp_galaxybuds2pro.jpg', 'Tai nghe không dây chống ồn chủ động, âm thanh Hi-Fi 24bit. Đang giảm giá 20%.');

-- Đơn hàng completed (để top chi tiêu & doanh thu ra số ngay)
-- Nguyễn Văn A (id=2): tổng chi tiêu 50.000.000 -> hạng Kim cương, nhãn Khách VIP
INSERT INTO orders (id, code, user_id, total, discount, final_total, voucher_id, points_used, status, created_at) VALUES
(1, 'DH0001', 2, 25000000, 0, 25000000, NULL, 0, 'completed', '2025-01-20 10:00:00'),
(2, 'DH0002', 2, 25000000, 0, 25000000, NULL, 0, 'completed', '2025-06-15 15:30:00'),
-- Trần Thị B (id=3): tổng chi tiêu 3.000.000 -> hạng Bạc, nhãn Khách mới (đăng ký < 3 tháng)
(3, 'DH0003', 3, 3000000, 0, 3000000, NULL, 0, 'completed', '2026-06-10 11:20:00'),
-- Lê Văn C (id=4): tổng chi tiêu 6.500.000 -> hạng Vàng, nhãn Khách thân thiết
(4, 'DH0004', 4, 6500000, 0, 6500000, NULL, 0, 'completed', '2025-04-01 09:00:00');
-- Phạm Thị D (id=5): chưa có đơn nào -> chi tiêu 0, nhãn Chưa mua

INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES
(1, 1, 'iPhone 15', 20000000, 1),
(1, 5, 'Tai nghe AirPods Pro', 5000000, 1),
(2, 3, 'MacBook Air M2', 25000000, 1),
(3, 6, 'Bàn phím cơ Logitech', 1500000, 2),
(4, 5, 'Tai nghe AirPods Pro', 5000000, 1),
(4, 6, 'Bàn phím cơ Logitech', 1500000, 1);

-- Lịch sử tích điểm khớp với các đơn completed ở trên (1 điểm / 10.000đ)
INSERT INTO point_history (user_id, order_id, points, type, created_at) VALUES
(2, 1, 2500, 'earn', '2025-01-20 10:00:00'),
(2, 2, 2500, 'earn', '2025-06-15 15:30:00'),
(3, 3, 300, 'earn', '2026-06-10 11:20:00'),
(4, 4, 650, 'earn', '2025-04-01 09:00:00');
