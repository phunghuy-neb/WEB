-- ========================================================
-- Migration: thêm cột status (khóa / mở khóa tài khoản) vào bảng users
-- Chạy trên database shopping_crm ĐANG CÓ DỮ LIỆU — không cần import lại,
-- không mất dữ liệu cũ. Mọi tài khoản hiện có mặc định là 'active'.
-- ========================================================

ALTER TABLE users
  ADD COLUMN status ENUM('active','locked') NOT NULL DEFAULT 'active' AFTER points;
