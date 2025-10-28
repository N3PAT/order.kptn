# ใช้ PHP 8.2 พร้อม Apache
FROM php:8.2-apache

# ติดตั้ง mysqli และ zip extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# คัดลอกไฟล์ทั้งหมดไปยัง container
COPY . /var/www/html/

# ตั้งโฟลเดอร์ทำงาน
WORKDIR /var/www/html/

# เปิดพอร์ต 80
EXPOSE 80

# รัน PHP built-in server
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html"]
