<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login');
}

// تنظیمات صفحه
$page_title = 'داشبورد مدیریت';
$page_css = 'dashboard';
$breadcrumbs = [
    'خانه' => SITE_URL,
    'داشبورد' => SITE_URL . '/dashboard'
];

// دریافت آمار کلی
try {
    // آمار فروش امروز
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as count,
            SUM(total_amount) as total,
            SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
            SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount
        FROM invoices 
        WHERE user_id = ? AND DATE(created_at) = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $today_stats = $stmt->fetch();

    // آمار ماه جاری
    $first_day_of_month = date('Y-m-01');
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as count,
            SUM(total_amount) as total,
            SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount
        FROM invoices 
        WHERE user_id = ? AND DATE(created_at) >= ?
    ");
    $stmt->execute([$_SESSION['user_id'], $first_day_of_month]);
    $month_stats = $stmt->fetch();

    // محصولات کم موجود
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            c.name as category_name,
            (SELECT SUM(quantity) FROM invoice_items WHERE product_id = p.id) as total_sold
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ? 
        AND p.stock_quantity <= p.minimum_stock 
        AND p.is_active = 1
        ORDER BY p.stock_quantity ASC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $low_stock_products = $stmt->fetchAll();

    // فاکتورهای معوق
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            c.first_name,
            c.last_name,
            c.phone,
            (SELECT SUM(paid_amount) FROM payments WHERE invoice_id = i.id) as paid_amount
        FROM invoices i
        JOIN customers c ON i.customer_id = c.id
        WHERE i.user_id = ? 
        AND i.status = 'pending'
        AND i.due_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
        ORDER BY i.due_date ASC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $pending_invoices = $stmt->fetchAll();

    // چک‌های سررسید
    $stmt = $pdo->prepare("
        SELECT 
            ch.*,
            c.first_name,
            c.last_name
        FROM checks ch
        JOIN customers c ON ch.customer_id = c.id
        WHERE ch.user_id = ?
        AND ch.status = 'pending'
        AND ch.due_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
        ORDER BY ch.due_date ASC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $upcoming_checks = $stmt->fetchAll();

    // نمودار فروش 6 ماه اخیر
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as total_amount,
            COUNT(*) as count
        FROM invoices
        WHERE user_id = ?
        AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $sales_chart_data = $stmt->fetchAll();

    // محصولات پرفروش
    $stmt = $pdo->prepare("
        SELECT 
            p.name,
            SUM(ii.quantity) as total_quantity,
            SUM(ii.quantity * ii.price) as total_amount
        FROM invoice_items ii
        JOIN products p ON ii.product_id = p.id
        JOIN invoices i ON ii.invoice_id = i.id
        WHERE i.user_id = ?
        AND i.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        GROUP BY p.id
        ORDER BY total_quantity DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $top_products = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    show_message('خطا در دریافت اطلاعات داشبورد', 'error');
}

// لود قالب صفحه
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-container">
    <!-- ردیف اول - کارت‌های آماری -->
    <div class="row g-4 mb-4">
        <!-- فروش امروز -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card bg-gradient-primary">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="bi bi-cart-check"></i>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-title">فروش امروز</div>
                        <div class="stat-card-value">
                            <?php echo number_format($today_stats['total'] ?? 0); ?>
                            <small>تومان</small>
                        </div>
                        <div class="stat-card-compare">
                            <span class="badge bg-success">
                                <i class="bi bi-arrow-up"></i>
                                12%
                            </span>
                            <span class="text-white-50">نسبت به دیروز</span>
                        </div>
                    </div>
                </div>
                <div class="stat-card-chart">
                    <canvas id="todaySalesChart" height="50"></canvas>
                </div>
            </div>
        </div>

        <!-- فروش ماه -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card bg-gradient-success">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-title">فروش این ماه</div>
                        <div class="stat-card-value">
                            <?php echo number_format($month_stats['total'] ?? 0); ?>
                            <small>تومان</small>
                        </div>
                        <div class="stat-card-compare">
                            <span class="badge bg-success">
                                <i class="bi bi-arrow-up"></i>
                                8%
                            </span>
                            <span class="text-white-50">نسبت به ماه قبل</span>
                        </div>
                    </div>
                </div>
                <div class="stat-card-chart">
                    <canvas id="monthSalesChart" height="50"></canvas>
                </div>
            </div>
        </div>

        <!-- فاکتورهای معوق -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card bg-gradient-warning">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-title">فاکتورهای معوق</div>
                        <div class="stat-card-value">
                            <?php echo number_format(count($pending_invoices)); ?>
                            <small>فاکتور</small>
                        </div>
                        <div class="stat-card-amount">
                            <?php
                            $pending_total = array_sum(array_map(function($invoice) {
                                return $invoice['total_amount'] - ($invoice['paid_amount'] ?? 0);
                            }, $pending_invoices));
                            echo number_format($pending_total) . ' تومان';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- محصولات کم موجود -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card bg-gradient-danger">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-title">محصولات کم موجود</div>
                        <div class="stat-card-value">
                            <?php echo count($low_stock_products); ?>
                            <small>محصول</small>
                        </div>
                        <div class="stat-card-compare">
                            <a href="<?php echo SITE_URL; ?>/products/low-stock" class="text-white">
                                مشاهده لیست <i class="bi bi-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ردیف دوم - نمودارها -->
    <div class="row g-4 mb-4">
        <!-- نمودار فروش -->
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">نمودار فروش 6 ماه اخیر</h5>
                    <div class="card-actions">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-light active" data-chart-view="amount">مبلغ</button>
                            <button type="button" class="btn btn-sm btn-light" data-chart-view="count">تعداد</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- نمودار دایره‌ای -->
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">وضعیت فاکتورها</h5>
                </div>
                <div class="card-body">
                    <canvas id="invoiceStatusChart" height="260"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ردیف سوم - جداول -->
    <div class="row g-4">
        <!-- فاکتورهای معوق -->
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">فاکتورهای معوق</h5>
                    <div class="card-actions">
                        <a href="<?php echo SITE_URL; ?>/invoices/pending" class="btn btn-sm btn-primary">
                            مشاهده همه
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>شماره</th>
                                    <th>مشتری</th>
                                    <th>مبلغ کل</th>
                                    <th>مبلغ پرداختی</th>
                                    <th>سررسید</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_invoices as $invoice): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/invoices/view/<?php echo $invoice['id']; ?>">
                                            <?php echo $invoice['invoice_number']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo $invoice['first_name'] . ' ' . $invoice['last_name']; ?>
                                        <div class="text-muted small"><?php echo $invoice['phone']; ?></div>
                                    </td>
                                    <td><?php echo number_format($invoice['total_amount']); ?></td>
                                    <td><?php echo number_format($invoice['paid_amount'] ?? 0); ?></td>
                                    <td>
                                        <?php
                                        $due_date = new DateTime($invoice['due_date']);
                                        $now = new DateTime();
                                        $diff = $now->diff($due_date);
                                        $days_remaining = $diff->days;
                                        $badge_class = 'bg-success';
                                        if ($days_remaining <= 3) {
                                            $badge_class = 'bg-danger';
                                        } elseif ($days_remaining <= 7) {
                                            $badge_class = 'bg-warning';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo to_jalali($invoice['due_date']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="recordPayment(<?php echo $invoice['id']; ?>)">
                                                ثبت پرداخت
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split" 
                                                    data-bs-toggle="dropdown">
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/invoices/view/<?php echo $invoice['id']; ?>">
                                                        <i class="bi bi-eye"></i> مشاهده
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/invoices/print/<?php echo $invoice['id']; ?>">
                                                        <i class="bi bi-printer"></i> چاپ
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" 
                                                       onclick="sendReminder(<?php echo $invoice['id']; ?>)">
                                                        <i class="bi bi-bell"></i> ارسال یادآور
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
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
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">محصولات کم موجود</h5>
                    <div class="card-actions">
                        <a href="<?php echo SITE_URL; ?>/products/low-stock" class="btn btn-sm btn-primary">
                            مشاهده همه
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>محصول</th>
                                    <th>دسته‌بندی</th>
                                    <th>موجودی</th>
                                    <th>حداقل موجودی</th>
                                    <th>فروش ماه</th>
                                    <th>عملیات</th>
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
                                    <td><?php echo $product['category_name']; ?></td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $product['minimum_stock']; ?></td>
                                    <td><?php echo $product['total_sold'] ?? 0; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="updateStock(<?php echo $product['id']; ?>)">
                                            بروزرسانی موجودی
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال ثبت پرداخت -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ثبت پرداخت</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" name="invoice_id" id="payment_invoice_id">
                    <div class="mb-3">
                        <label for="payment_amount" class="form-label">مبلغ پرداختی</label>
                        <input type="number" class="form-control" id="payment_amount" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">تاریخ پرداخت</label>
                        <input type="date" class="form-control" id="payment_date" name="date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">روش پرداخت</label>
                        <select class="form-select" id="payment_method" name="method" required>
                            <option value="cash">نقدی</option>
                            <option value="card">کارت به کارت</option>
                            <option value="check">چک</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="payment_description" class="form-label">توضیحات</label>
                        <textarea class="form-control" id="payment_description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-primary" onclick="submitPayment()">ثبت پرداخت</button>
            </div>
        </div>
    </div>
</div>

<!-- مودال بروزرسانی موجودی -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">بروزرسانی موجودی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="stockForm">
                    <input type="hidden" name="product_id" id="stock_product_id">
                    <div class="mb-3">
                        <label for="stock_quantity" class="form-label">موجودی جدید</label>
                        <input type="number" class="form-control" id="stock_quantity" name="quantity" required>
                    </div>
                    <div class="mb-3">
                        <label for="stock_description" class="form-label">توضیحات</label>
                        <textarea class="form-control" id="stock_description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-primary" onclick="submitStock()">ذخیره تغییرات</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<!-- اسکریپت‌های مخصوص داشبورد -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // نمودار فروش 6 ماه اخیر
    const salesChartCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesChartCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($sales_chart_data, 'month')); ?>,
            datasets: [{
                label: 'مبلغ فروش',
                data: <?php echo json_encode(array_column($sales_chart_data, 'total_amount')); ?>,
                borderColor: '#3498db',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'نمودار فروش 6 ماه اخیر'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fa-IR').format(value) + ' تومان';
                        }
                    }
                }
            }
        }
    });

    // نمودار وضعیت فاکتورها
    const invoiceStatusChartCtx = document.getElementById('invoiceStatusChart').getContext('2d');
    const invoiceStatusChart = new Chart(invoiceStatusChartCtx, {
        type: 'doughnut',
        data: {
            labels: ['پرداخت شده', 'معوق', 'لغو شده'],
            datasets: [{
                data: [65, 25, 10],
                backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'وضعیت فاکتورها'
                }
            }
        }
    });
});

// تابع ثبت پرداخت
function recordPayment(invoiceId) {
    document.getElementById('payment_invoice_id').value = invoiceId;
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

// تابع ارسال فرم پرداخت
function submitPayment() {
    const form = document.getElementById('paymentForm');
    const formData = new FormData(form);

    fetch(SITE_URL + '/api/payments/create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: 'پرداخت با موفقیت ثبت شد'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: data.message
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: 'خطا در ثبت پرداخت'
        });
    });
}

// تابع بروزرسانی موجودی
function updateStock(productId) {
    document.getElementById('stock_product_id').value = productId;
    const modal = new bootstrap.Modal(document.getElementById('stockModal'));
    modal.show();
}

// تابع ارسال فرم موجودی
function submitStock() {
    const form = document.getElementById('stockForm');
    const formData = new FormData(form);

    fetch(SITE_URL + '/api/products/update-stock', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: 'موجودی با موفقیت بروزرسانی شد'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: data.message
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: 'خطا در بروزرسانی موجودی'
        });
    });
}

// تابع ارسال یادآور
function sendReminder(invoiceId) {
    Swal.fire({
        title: 'ارسال یادآور',
        text: 'آیا از ارسال یادآور برای این فاکتور اطمینان دارید؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'بله، ارسال شود',
        cancelButtonText: 'خیر'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(SITE_URL + '/api/invoices/send-reminder/' + invoiceId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                                                        title: 'موفقیت',
                            text: 'یادآور با موفقیت ارسال شد',
                            confirmButtonText: 'تایید'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطا',
                            text: data.message || 'خطا در ارسال یادآور',
                            confirmButtonText: 'تایید'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: 'خطا در ارسال یادآور',
                        confirmButtonText: 'تایید'
                    });
                });
        }
    });
}

// به‌روزرسانی خودکار آمار
function updateStats() {
    fetch(SITE_URL + '/api/dashboard/stats')
        .then(response => response.json())
        .then(data => {
            // به‌روزرسانی کارت‌های آماری
            document.querySelectorAll('[data-stat]').forEach(element => {
                const stat = element.dataset.stat;
                if (data[stat] !== undefined) {
                    element.textContent = new Intl.NumberFormat('fa-IR').format(data[stat]);
                }
            });

            // به‌روزرسانی نمودارها
            salesChart.data.datasets[0].data = data.salesChartData;
            salesChart.update();
            
            invoiceStatusChart.data.datasets[0].data = [
                data.paidInvoices,
                data.pendingInvoices,
                data.canceledInvoices
            ];
            invoiceStatusChart.update();
        })
        .catch(error => console.error('Error updating stats:', error));
}

// هر 5 دقیقه آمار را به‌روز کن
setInterval(updateStats, 300000);

// تنظیم تاریخ شمسی در فرم‌ها
document.querySelectorAll('input[type="date"]').forEach(input => {
    const datepicker = new JDate(input, {
        format: 'YYYY/MM/DD',
        autoClose: true,
        initialValue: true,
        persianDigits: true,
        altField: input,
        altFormat: 'YYYY-MM-DD'
    });
});

// تنظیم فرمت اعداد فارسی
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = new Intl.NumberFormat('fa-IR').format(value);
    });
});

// تنظیم اسکرول نرم برای لینک‌های داخلی
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// نمایش tooltip برای المان‌های دارای data-tooltip
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl, {
        trigger: 'hover'
    });
});


                        