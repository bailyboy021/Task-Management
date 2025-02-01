<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>


# Task Management

Task Management sederhana yang dibangun menggunakan Laravel 11. API ini mendukung pengelolaan data transaksi dengan database MySQL menggunakan Eloquent ORM dan dokumentasi API berbasis Swagger (l5-swagger).


## Teknologi yang Digunakan

- **Framework**: Laravel 11 (PHP 8.2)
- **Database**: MySQL
- **ORM**: Eloquent
- **Dokumentasi API**: Swagger melalui l5-swagger

## Instalasi

1.  Clone dari repository:

    ```bash
    git clone https://github.com/bailyboy021/Task-Management.git
    ```

2.  Pindah ke project directory:

    ```bash
    cd Task-Management
    ```

3.  Install Composer dependencies:

    ```bash
    composer install
    ```

4. Salin file .env.example menjadi .env lalu sesuaikan konfigurasi database dan L5_SWAGGER:

   ```bash
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sdit_group
   DB_USERNAME=root
   DB_PASSWORD=

   L5_SWAGGER_GENERATE_ALWAYS=true
   L5_SWAGGER_API_VERSION=1.0.0
   L5_SWAGGER_TITLE="API Documentation"
   L5_SWAGGER_DESCRIPTION="Documentation for SDIT-Group API"
   L5_SWAGGER_SCHEMES=https
   L5_SWAGGER_BASE_PATH=/api

5. Generate kunci aplikasi Laravel:

   ```bash
   php artisan key:generate

6. Migrasi database dan seed data awal:

   ```bash
   php artisan migrate --seed

6. Jalankan server:

   ```bash
   php artisan serve

## Dokumentasi API

Untuk melihat dokumentasi API:
1. Pastikan aplikasi berjalan di server lokal
2. Akses dokumentasi API di URL berikut:

   ```bash
   http://localhost:8000/api/documentation
