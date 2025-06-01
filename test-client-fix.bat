@echo off
cd /d "C:\laragon\www\facturx"
echo Testing client with includes...
php artisan test tests/Feature/Api/V1/Customer/ClientTest.php --filter="can_show_client_with_includes"
pause
