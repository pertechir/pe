<?php
if (!defined('BASE_PATH')) {
    die('دسترسی مستقیم به این فایل مجاز نیست');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - حسابینو' : 'حسابینو'; ?></title>
    
    <!-- فونت ایران‌سنس -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    
    <!-- فایل‌های CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/sidebar.css" rel="stylesheet">

    <!-- اگر صفحه استایل اختصاصی دارد -->
    <?php if (isset($page_css)): ?>
        <link href="<?php echo SITE_URL; ?>/assets/css/<?php echo $page_css; ?>.css" rel="stylesheet">
    <?php endif; ?>

    <!-- Chart.js برای نمودارها -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- نوار مدیریت بالای صفحه -->
    <nav class="admin-header navbar navbar-expand-lg">
        <div class="container-fluid">
            <!-- دکمه تاگل سایدبار -->
            <button type="button" id="sidebarToggle" class="btn">
                <i class="bi bi-list"></i>
            </button>

            <!-- لوگو -->
            <a class="navbar-brand d-lg-none" href="<?php echo SITE_URL; ?>/dashboard">
                <img src="<?php echo SITE_URL; ?>/assets/img/logo-sm.png" alt="حسابینو" height="30">
            </a>

            <!-- بخش راست نوار -->
            <div class="d-flex align-items-center">
                <!-- جستجو -->
                <form class="search-form d-none d-md-flex me-4">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="جستجو...">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <!-- اعلان‌ها -->
                <div class="nav-item dropdown me-3">
                    <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php
                        // تعداد اعلان‌های خوانده نشده
                        $unread_notifications = 0; // این را از دیتابیس بخوانید
                        if ($unread_notifications > 0):
                        ?>
                        <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notifications-dropdown">
                        <div class="notifications-header">
                            <h6 class="mb-0">اعلان‌ها</h6>
                            <?php if ($unread_notifications > 0): ?>
                            <a href="#" class="mark-all-read">علامت همه به‌عنوان خوانده‌شده</a>
                            <?php endif; ?>
                        </div>
                        <div class="notifications-body">
                            <!-- اینجا لیست اعلان‌ها را نمایش دهید -->
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-check2-circle"></i>
                                <p class="mb-0">اعلان جدیدی ندارید</p>
                            </div>
                        </div>
                        <div class="notifications-footer">
                            <a href="<?php echo SITE_URL; ?>/notifications">مشاهده همه اعلان‌ها</a>
                        </div>
                    </div>
                </div>

                <!-- منوی کاربر -->
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-dropdown" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <img src="<?php echo get_user_avatar($_SESSION['user_id'] ?? 0); ?>" alt="تصویر کاربر" class="user-avatar">
                        <span class="d-none d-md-inline ms-2"><?php echo $_SESSION['user_name'] ?? 'کاربر'; ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header">
                            <h6 class="mb-0"><?php echo $_SESSION['user_name'] ?? 'کاربر'; ?></h6>
                            <small class="text-muted"><?php echo get_user_role_name($_SESSION['user_role'] ?? ''); ?></small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile">
                            <i class="bi bi-person"></i> پروفایل
                        </a>
                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/settings">
                            <i class="bi bi-gear"></i> تنظیمات
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/logout">
                            <i class="bi bi-box-arrow-right"></i> خروج
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ساختار اصلی صفحه -->
    <div class="d-flex wrapper">
        <!-- سایدبار -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- محتوای اصلی -->
        <div id="content" class="main-content">
            <!-- نوار بالای محتوا -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?php echo $page_title ?? 'حسابینو'; ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb float-sm-end">
                                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/dashboard">داشبورد</a></li>
                                    <?php if (isset($breadcrumbs)): ?>
                                        <?php foreach ($breadcrumbs as $label => $url): ?>
                                            <li class="breadcrumb-item"><a href="<?php echo $url; ?>"><?php echo $label; ?></a></li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if (isset($page_title)): ?>
                                        <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
                                    <?php endif; ?>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- محتوای اصلی صفحه -->
            <div class="content">
                <div class="container-fluid">