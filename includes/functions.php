<?php

/**
 * تابع نمایش پیام‌های خطا و موفقیت
 */
function show_message($message, $type = 'success') {
    $_SESSION[$type . '_message'] = $message;
}

/**
 * تابع تبدیل تاریخ میلادی به شمسی
 */
function to_jalali($g_date, $format = 'Y/m/d') {
    $g_date = date('Y-m-d', strtotime($g_date));
    $g_year = substr($g_date, 0, 4);
    $g_month = substr($g_date, 5, 2);
    $g_day = substr($g_date, 8, 2);
    
    require_once 'jdf.php';
    $j_date = gregorian_to_jalali($g_year, $g_month, $g_day, '/');
    
    return $j_date;
}

/**
 * تابع فرمت کردن قیمت
 */
function format_price($price) {
    return number_format($price) . ' تومان';
}

/**
 * تابع تولید شماره فاکتور
 */
function generate_invoice_number() {
    return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
}

/**
 * تابع بررسی لاگین بودن کاربر
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * تابع بررسی دسترسی ادمین
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * تابع ریدایرکت
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * تابع اعتبارسنجی فرم
 */
function validate_form($data, $rules) {
    $errors = [];
    foreach ($rules as $field => $rule) {
        if (strpos($rule, 'required') !== false && empty($data[$field])) {
            $errors[$field] = 'این فیلد الزامی است';
        }
        if (strpos($rule, 'email') !== false && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = 'ایمیل نامعتبر است';
        }
        if (strpos($rule, 'phone') !== false && !preg_match('/^09[0-9]{9}$/', $data[$field])) {
            $errors[$field] = 'شماره موبایل نامعتبر است';
        }
    }
    return $errors;
}
function get_absolute_path($path) {
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__FILE__, 2));
    }
    return BASE_PATH . '/' . ltrim($path, '/');
}