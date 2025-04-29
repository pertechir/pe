<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>بررسی تنظیمات پروژه:</h2>";

// بررسی PHP
echo "<h3>نسخه PHP:</h3>";
echo PHP_VERSION;

// بررسی دسترسی به فایل‌ها
echo "<h3>بررسی دسترسی به فایل‌ها:</h3>";
$files = [
    'config/config.php',
    'config/database.php',
    'includes/functions.php',
    'includes/header.php',
    'includes/footer.php',
    'pages/auth/login.php',
    'pages/dashboard/index.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ فایل '$file' وجود دارد<br>";
    } else {
        echo "❌ فایل '$file' وجود ندارد!<br>";
    }
}

// بررسی اتصال به دیتابیس
echo "<h3>بررسی اتصال به دیتابیس:</h3>";
try {
    require_once 'config/database.php';
    echo "✅ اتصال به دیتابیس موفق بود";
} catch (Exception $e) {
    echo "❌ خطا در اتصال به دیتابیس: " . $e->getMessage();
}