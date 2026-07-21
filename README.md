# Shopping CRM — Web quản lý khách hàng mua sắm

Dự án bài tập môn Lập trình Web. PHP thuần (không framework, không Composer) +
Bootstrap 5 (CDN) + MySQL (PDO). Kiến trúc server-render: mỗi trang là 1 file
`.php` chứa cả xử lý (BE) lẫn giao diện (FE).

## 1. Cài đặt XAMPP

### Trên Linux

1. Tải bộ cài `.run` cho Linux tại https://www.apachefriends.org/download.html
2. Cấp quyền chạy và cài đặt:
   ```bash
   chmod +x xampp-linux-x64-*-installer.run
   sudo ./xampp-linux-x64-*-installer.run
   ```
   XAMPP sẽ được cài vào `/opt/lampp`.
3. Khởi động: `sudo /opt/lampp/lampp start`
4. Đặt thư mục dự án vào `/opt/lampp/htdocs/shopping-crm` (copy hoặc tạo symlink).

### Trên Windows

1. Tải bộ cài `.exe` cho Windows tại https://www.apachefriends.org/download.html
2. Chạy trình cài đặt, mặc định XAMPP nằm ở `C:\xampp`.
3. Mở **XAMPP Control Panel**, bấm **Start** cho Apache và MySQL.
4. Copy thư mục dự án vào `C:\xampp\htdocs\shopping-crm`.

> Lưu ý: tên file trong dự án đều viết chữ thường vì Linux phân biệt hoa/thường,
> nên dự án chạy đúng trên cả 2 hệ điều hành.

## 2. Import CSDL

Dùng 1 trong 2 cách:

**Cách 1 — phpMyAdmin (khuyên dùng cho người mới):**
1. Mở `http://localhost/phpmyadmin`
2. Tạo/để trống, chọn tab **Import**, chọn file `sql/schema.sql`, bấm **Go**.
   (File tự tạo database `shopping_crm` nên không cần tạo database trước.)

**Cách 2 — dòng lệnh:**
```bash
# Linux
/opt/lampp/bin/mysql -u root < sql/schema.sql

# Windows (chạy trong thư mục dự án bằng cmd)
C:\xampp\mysql\bin\mysql.exe -u root < sql\schema.sql
```

Sau khi import, truy cập `http://localhost/shopping-crm/` để bắt đầu dùng.

## 3. Tài khoản demo

Mật khẩu chung cho mọi tài khoản demo: **`admin123`**

| Vai trò | Email | Ghi chú |
|---|---|---|
| Admin | admin@shop.vn | Vào thẳng trang quản trị |
| Khách (VIP, hạng Kim cương) | a@gmail.com | Đã có 2 đơn hoàn thành, tổng chi tiêu 50.000.000đ |
| Khách (hạng Bạc, khách mới) | b@gmail.com | Đăng ký gần đây, 1 đơn hoàn thành |
| Khách (hạng Vàng) | c@gmail.com | 1 đơn hoàn thành |
| Khách (chưa mua) | d@gmail.com | Chưa có đơn hàng nào |

## 4. Bảo mật đã áp dụng

1. **Mã hóa mật khẩu** — mật khẩu không bao giờ lưu dạng chữ thường (plain text).
   Dùng `password_hash()` khi đăng ký/đổi mật khẩu và `password_verify()` khi đăng nhập
   (xem `register.php`, `login.php`, `profile.php`).
2. **Chống SQL Injection** — mọi truy vấn có tham số đều dùng prepared statement
   (`$pdo->prepare()` + `execute()`), không nối chuỗi SQL trực tiếp từ input người dùng.
3. **Phân quyền truy cập** — `require_login()` chặn khách chưa đăng nhập,
   `require_admin()` chặn khách thường vào khu vực quản trị; các trang chi tiết
   (đơn hàng, hồ sơ) luôn kiểm tra dữ liệu thuộc đúng người dùng đang đăng nhập
   trong câu truy vấn (không chỉ kiểm tra ở giao diện).
4. **Chống XSS** — mọi dữ liệu do người dùng nhập khi in ra HTML đều đi qua hàm
   `e()` (bọc `htmlspecialchars`) để tránh chèn mã HTML/JavaScript độc hại.
