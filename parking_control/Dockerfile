# --- Stage 1: Build Frontend Vue.js ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
# Chạy lệnh build của bạn (npm run build hoặc npm run prod tùy theo package.json)
RUN npm run build

# --- Stage 2: Chạy Backend Laravel với PHP & Nginx ---
FROM php:8.2-fpm-alpine

# Cài đặt các extension cần thiết cho Laravel, MySQL & PostgreSQL
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    libzip-dev \
    postgresql-dev  # <--- Bổ sung thư viện phát triển PostgreSQL cho Alpine

# Cài đặt các driver PHP mở rộng (Đã thêm pdo_pgsql và pgsql)
RUN docker-php-ext-install pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy toàn bộ code dự án vào container
COPY . .

RUN rm -f .env

# Copy phần Frontend đã được build từ Stage 1 vào thư mục public của Laravel
COPY --from=frontend-builder /app/public /var/www/public

# Cài đặt các gói thư viện PHP qua Composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Phân quyền cho thư mục storage và bootstrap/cache của Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Cấu hình Nginx
RUN echo 'server { \
    listen 80; \
    index index.php index.html; \
    root /var/www/public; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        try_files $uri =404; \
        fastcgi_split_path_info ^(.+\.php)(/.+)$; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        fastcgi_param PATH_INFO $fastcgi_path_info; \
    } \
}' > /etc/nginx/http.d/default.conf

EXPOSE 80

# Chạy tự động tạo bảng (migrate) trên PostgreSQL và bật server
CMD php artisan config:clear && php artisan cache:clear && php artisan migrate --force && php-fpm -D && nginx -g "daemon off;"