<?php
// تنظیمات نمایش خطاها
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تنظیمات مسیر
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__FILE__, 2));
}

// تنظیم URL سایت
$site_url = 'http://localhost/pe'; // این را به آدرس پروژه خود تغییر دهید
define('SITE_URL', $site_url);

// لود کردن فایل دیتابیس
require_once BASE_PATH . '/config/database.php';

// لود کردن توابع
require_once BASE_PATH . '/includes/functions.php';

// تنظیمات عمومی
define('SITE_NAME', 'حسابینو');
date_default_timezone_set('Asia/Tehran');

// بررسی سشن
if (!isset($_SESSION)) {
    session_start();
}