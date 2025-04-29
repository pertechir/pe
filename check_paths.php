<?php
echo "<h1>بررسی مسیرها</h1>";

echo "<h2>مسیر فعلی:</h2>";
echo __DIR__;

echo "<h2>مسیر BASE_PATH:</h2>";
define('BASE_PATH', __DIR__);
echo BASE_PATH;

echo "<h2>بررسی دسترسی به فایل‌ها با مسیر مطلق:</h2>";
$files = [
    '/config/config.php',
    '/config/database.php',
    '/includes/functions.php',
    '/pages/dashboard/index.php'
];

foreach ($files as $file) {
    $full_path = BASE_PATH . $file;
    if (file_exists($full_path)) {
        echo "✅ فایل {$file} در مسیر {$full_path} وجود دارد<br>";
    } else {
        echo "❌ فایل {$file} در مسیر {$full_path} وجود ندارد!<br>";
    }
}