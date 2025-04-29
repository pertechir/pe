<?php
$page_title = 'صفحه مورد نظر یافت نشد';
$page_css = 'error';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="error-page">
    <div class="error-box">
        <h1 class="error-code">404</h1>
        <h2 class="error-title">صفحه مورد نظر یافت نشد!</h2>
        <p class="error-description">متأسفانه صفحه‌ای که به دنبال آن هستید وجود ندارد یا حذف شده است.</p>
        <div class="error-actions">
            <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                <i class="bi bi-house"></i>
                بازگشت به خانه
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i>
                بازگشت به صفحه قبل
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>