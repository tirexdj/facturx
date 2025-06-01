@echo off
cd /d "C:\laragon\www\facturx"
echo Testing all client tests...
php artisan test tests/Feature/Api/V1/Customer/ClientTest.php
pause
