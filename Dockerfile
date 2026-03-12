# Gunakan image PHP 8.4 murni
FROM php:8.4-cli

# Install dependensi sistem operasi yang dibutuhkan Laravel & MySQL
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    libonig-dev \
    libzip-dev \
    zip

# Install ekstensi PHP untuk MySQL dan kawan-kawan
RUN docker-php-ext-install pdo_mysql mbstring zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Tentukan folder kerja di dalam server
WORKDIR /app

# Salin seluruh kode Laravel-mu ke dalam server Render
COPY . .

# Install dependensi Laravel (abaikan paket testing/dev)
RUN composer install --optimize-autoloader --no-dev

# Berikan hak akses untuk folder cache dan storage
RUN chmod -R 775 storage bootstrap/cache

# Jalankan migrasi database otomatis, lalu nyalakan server API-nya
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}