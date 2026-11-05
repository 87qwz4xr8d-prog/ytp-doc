-- ขั้นตอนสำหรับผู้ดูแลเซิร์ฟเวอร์ ก่อนนำเข้า schema.sql ใน phpMyAdmin
-- รันไฟล์นี้ด้วยบัญชีที่มีสิทธิ์สร้างฐานข้อมูล เช่น root
-- แล้วเปลี่ยนรหัสผ่านก่อนนำไปใช้จริง

CREATE DATABASE IF NOT EXISTS novel_library
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'novel_user'@'localhost' IDENTIFIED BY 'NovelLib@2026';
GRANT ALL PRIVILEGES ON novel_library.* TO 'novel_user'@'localhost';
FLUSH PRIVILEGES;
