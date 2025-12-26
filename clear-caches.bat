@echo off
echo ================================================
echo HUBIZZ - Clear Laravel Caches
echo ================================================
echo.

echo [1/5] Clearing application cache...
php artisan cache:clear

echo.
echo [2/5] Clearing configuration cache...
php artisan config:clear

echo.
echo [3/5] Clearing view cache...
php artisan view:clear

echo.
echo [4/5] Clearing route cache...
php artisan route:clear

echo.
echo [5/5] Clearing compiled classes...
php artisan clear-compiled

echo.
echo ================================================
echo All caches cleared successfully!
echo ================================================
echo.
pause
