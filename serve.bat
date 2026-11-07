@echo off
REM TaskFlow - fast local server for Windows / XAMPP.
REM
REM Why: XAMPP's PHP ships WITHOUT OPcache enabled, so every request recompiles the whole Laravel
REM framework (about 0.65 s per page). This starts the same built-in server (exactly like
REM "php artisan serve") with OPcache switched on for this process only (about 0.1 s per page).
REM It does NOT change your php.ini.
REM
REM Usage:  serve.bat            -> http://127.0.0.1:8000
REM         serve.bat 8123       -> http://127.0.0.1:8123
set PORT=%1
if "%PORT%"=="" set PORT=8000
echo TaskFlow running at http://127.0.0.1:%PORT%   (Ctrl+C to stop)
cd /d "%~dp0public"
php -d zend_extension=php_opcache.dll -d opcache.enable=1 -d opcache.enable_cli=1 -d opcache.memory_consumption=192 -d opcache.max_accelerated_files=20000 -d opcache.validate_timestamps=1 -d opcache.revalidate_freq=2 -S 127.0.0.1:%PORT% ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php
