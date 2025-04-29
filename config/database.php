<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hesabino');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_persian_ci"
        ]
    );
} catch (PDOException $e) {
    error_log("خطا در اتصال به دیتابیس: " . $e->getMessage());
    die("خطا در اتصال به پایگاه داده");
}