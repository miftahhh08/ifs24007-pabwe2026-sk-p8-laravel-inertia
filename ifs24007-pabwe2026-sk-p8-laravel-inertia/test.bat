@echo off
REM Menjalankan seluruh test + coverage (wajib 100%). Butuh ekstensi Xdebug atau PCOV.
php artisan config:clear
php artisan test --coverage --min=100
