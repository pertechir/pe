<?php
// نمایش خطاها
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تنظیم مسیر پایه
define('BASE_PATH', __DIR__);

// لود کردن تنظیمات
require_once BASE_PATH . '/config/config.php';

// دریافت صفحه درخواستی از URL
$request_url = $_SERVER['REQUEST_URI'];
$base_url = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$request_path = str_replace($base_url, '', $request_url);
$request_path = trim($request_path, '/');

// تنظیم صفحه پیش‌فرض
if (empty($request_path)) {
    $request_path = 'dashboard';
}

// تبدیل مسیر URL به مسیر فایل
$parts = explode('/', $request_path);
$page_file = BASE_PATH . '/pages/';

if (count($parts) >= 2) {
    // برای مسیرهایی مثل auth/login
    $page_file .= $parts[0] . '/' . $parts[1] . '.php';
} else {
    // برای مسیرهایی مثل dashboard
    $page_file .= $parts[0] . '/index.php';
}

// بررسی وجود فایل
if (file_exists($page_file)) {
    require_once $page_file;
} else {
    // صفحه 404
    http_response_code(404);
    require_once BASE_PATH . '/pages/errors/404.php';
}