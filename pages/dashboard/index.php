<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';


if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login');
}

// دریافت آمار کلی
try {
    $stats = [
        'total_products' => $pdo->query("SELECT COUNT(*) FROM products WHERE user_id = {$_SESSION['user_id']}")->fetchColumn(),
        'total_services' => $pdo->query("SELECT COUNT(*) FROM services WHERE user_id = {$_SESSION['user_id']}")->fetchColumn(),
        'total_invoices' => $pdo->query("SELECT COUNT(*) FROM invoices WHERE user_id = {$_SESSION['user_id']}")->fetchColumn(),
        'total_customers' => $pdo->query("SELECT COUNT(*) FROM customers WHERE user_id = {$_SESSION['user_id']}")->fetchColumn()
    ];

    // دریافت فاکتورهای اخیر
    $recent_invoices = $pdo->query("
        SELECT i.*, c.first_name, c.last_name 
        FROM invoices i 
        JOIN customers c ON i.customer_id = c.id 
        WHERE i.user_id = {$_SESSION['user_id']} 
        ORDER BY i.created_at DESC 
        LIMIT 5
    ")->fetchAll();

    // دریافت محصولات کم موجود
    $low_stock_products = $pdo->query("
        SELECT * FROM products 
        WHERE user_id = {$_SESSION['user_id']} 
        AND stock_quantity <= minimum_stock 
        AND is_active = 1 
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    show_message('خطا در دریافت اطلاعات داشبورد', 'error');
}

$page_title = 'داشبورد';
require_once '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- کارت‌های آمار -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                تعداد محصولات</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_products']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-box-seam fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                تعداد خدمات</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_services']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-gear fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                تعداد فاکتورها</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_invoices']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-receipt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                تعداد مشتریان</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_customers']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- فاکتورهای اخیر و محصولات کم موجود -->
    <div class="row">
        <!-- فاکتورهای اخیر -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">فاکتورهای اخیر</h6>
                    <a href="<?php echo SITE_URL; ?>/invoices" class="btn btn-sm btn-primary">
                        مشاهده همه
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>شماره فاکتور</th>
                                    <th>مشتری</th>
                                    <th>تاریخ</th>
                                    <th>مبلغ کل</th>
                                    <th>وضعیت</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_invoices as $invoice): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/invoices/view/<?php echo $invoice['id']; ?>">
                                            <?php echo $invoice['invoice_number']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $invoice['first_name'] . ' ' . $invoice['last_name']; ?></td>
                                    <td><?php echo to_jalali($invoice['created_at']); ?></td>
                                    <td><?php echo format_price($invoice['total_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo get_invoice_status_color($invoice['status']); ?>">
                                            <?php echo get_invoice_status_text($invoice['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- محصولات کم موجود -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">محصولات کم موجود</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($low_stock_products)): ?>
                        <p class="text-center text-success mb-0">
                            <i class="bi bi-check-circle"></i>
                            همه محصولات موجودی کافی دارند
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>نام محصول</th>
                                        <th>موجودی</th>
                                        <th>حداقل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_products as $product): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/products/edit/<?php echo $product['id']; ?>">
                                                <?php echo $product['name']; ?>
                                            </a>
                                        </td>
                                        <td class="text-danger"><?php echo $product['stock_quantity']; ?></td>
                                        <td><?php echo $product['minimum_stock']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>