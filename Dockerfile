# ใช้ PHP 8.2 พร้อม Apache เป็นฐาน
FROM php:8.2-apache

# คัดลอกไฟล์ทั้งหมดใน repo ไปยัง container
COPY . /var/www/html/

# ตั้งโฟลเดอร์ทำงาน
WORKDIR /var/www/html/

# เปิดพอร์ต 80 (HTTP)
EXPOSE 80

# คำสั่งรัน PHP built-in server
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html"]
