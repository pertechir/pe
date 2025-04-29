<?php
if (!defined('BASE_PATH')) {
    die('دسترسی مستقیم به این فایل مجاز نیست');
}

// دریافت منوی فعال
$current_page = str_replace(SITE_URL . '/', '', $_SERVER['REQUEST_URI']);
$current_page = explode('/', $current_page)[0];

// دریافت اعلان‌های کاربر
try {
    // تعداد محصولات کم موجود
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM products 
        WHERE user_id = ? AND stock_quantity <= minimum_stock
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $low_stock_count = $stmt->fetch()['count'];

    // تعداد فاکتورهای معوق
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM invoices 
        WHERE user_id = ? AND status = 'pending' 
        AND due_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $pending_invoices_count = $stmt->fetch()['count'];

    // تعداد چک‌های سررسید
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM checks 
        WHERE user_id = ? AND status = 'pending' 
        AND due_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $upcoming_checks_count = $stmt->fetch()['count'];

    // تعداد پیام‌های جدید
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM messages 
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_messages_count = $stmt->fetch()['count'];

    // تعداد یادآورها
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reminders 
        WHERE user_id = ? AND is_done = 0 
        AND due_date <= DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $reminders_count = $stmt->fetch()['count'];

    // دریافت اطلاعات کاربر

    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.family,
            u.email,
            u.phone,
            (SELECT COUNT(*) FROM logins WHERE user_id = u.id) as login_count,
            (SELECT login_at FROM logins WHERE user_id = u.id ORDER BY login_at DESC LIMIT 1) as last_login
        FROM users u 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // اگر اطلاعات کاربر یافت نشد، مقادیر پیش‌فرض تنظیم شود
    if (!$user_info) {
        $user_info = [
            'name' => 'کاربر',
            'family' => 'مهمان',
            'email' => 'guest@example.com',
            'login_count' => 0,
            'last_login' => null
        ];
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    // تنظیم مقادیر پیش‌فرض در صورت خطا
    $user_info = [
        'name' => 'کاربر',
        'family' => 'مهمان',
        'email' => 'guest@example.com',
        'login_count' => 0,
        'last_login' => null
    ];

    // آمار کلی
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM customers WHERE user_id = ?) as customers_count,
            (SELECT COUNT(*) FROM products WHERE user_id = ?) as products_count,
            (SELECT COUNT(*) FROM invoices WHERE user_id = ?) as invoices_count,
            (SELECT SUM(amount) FROM payments WHERE user_id = ?) as total_payments
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $stats = $stmt->fetch();

} catch (PDOException $e) {
    error_log($e->getMessage());
    // مقادیر پیش‌فرض در صورت خطا
    $low_stock_count = 0;
    $pending_invoices_count = 0;
    $upcoming_checks_count = 0;
    $unread_messages_count = 0;
    $reminders_count = 0;
    $user_info = [];
    $stats = [
        'customers_count' => 0,
        'products_count' => 0,
        'invoices_count' => 0,
        'total_payments' => 0
    ];
}

// ساختار منو
$menu = [
    'dashboard' => [
        'title' => 'داشبورد',
        'icon' => 'bi bi-grid-1x2',
        'url' => SITE_URL . '/dashboard',
        'badge' => null
    ],
    'products' => [
        'title' => 'محصولات',
        'icon' => 'bi bi-box',
        'badge' => $low_stock_count ? ['text' => $low_stock_count, 'class' => 'bg-danger'] : null,
        'submenu' => [
            [
                'title' => 'لیست محصولات',
                'url' => SITE_URL . '/products',
                'icon' => 'bi bi-list'
            ],
            [
                'title' => 'افزودن محصول',
                'url' => SITE_URL . '/products/add',
                'icon' => 'bi bi-plus'
            ],
            [
                'title' => 'محصولات کم موجود',
                'url' => SITE_URL . '/products/low-stock',
                'icon' => 'bi bi-exclamation-triangle',
                'badge' => $low_stock_count ? ['text' => $low_stock_count, 'class' => 'bg-danger'] : null
            ],
            [
                'title' => 'دسته‌بندی‌ها',
                'url' => SITE_URL . '/categories',
                'icon' => 'bi bi-folder'
            ],
            [
                'title' => 'واحدهای اندازه‌گیری',
                'url' => SITE_URL . '/units',
                'icon' => 'bi bi-rulers'
            ],
            [
                'title' => 'گزارش موجودی',
                'url' => SITE_URL . '/products/inventory-report',
                'icon' => 'bi bi-file-earmark-text'
            ],
            [
                'title' => 'تاریخچه تغییرات',
                'url' => SITE_URL . '/products/history',
                'icon' => 'bi bi-clock-history'
            ]
        ]
    ],
    'customers' => [
        'title' => 'مشتریان',
        'icon' => 'bi bi-people',
        'submenu' => [
            [
                'title' => 'لیست مشتریان',
                'url' => SITE_URL . '/customers',
                'icon' => 'bi bi-list'
            ],
            [
                'title' => 'افزودن مشتری',
                'url' => SITE_URL . '/customers/add',
                'icon' => 'bi bi-plus'
            ],
            [
                'title' => 'گروه‌های مشتریان',
                'url' => SITE_URL . '/customers/groups',
                'icon' => 'bi bi-people-fill'
            ],
            [
                'title' => 'پیام‌های دریافتی',
                'url' => SITE_URL . '/customers/messages',
                'icon' => 'bi bi-chat-dots',
                'badge' => $unread_messages_count ? ['text' => $unread_messages_count, 'class' => 'bg-success'] : null
            ],
            [
                'title' => 'گزارش مشتریان',
                'url' => SITE_URL . '/customers/report',
                'icon' => 'bi bi-file-earmark-text'
            ]
        ]
    ],
    'invoices' => [
        'title' => 'فاکتورها',
        'icon' => 'bi bi-receipt',
        'badge' => $pending_invoices_count ? ['text' => $pending_invoices_count, 'class' => 'bg-warning'] : null,
        'submenu' => [
            [
                'title' => 'لیست فاکتورها',
                'url' => SITE_URL . '/invoices',
                'icon' => 'bi bi-list'
            ],
            [
                'title' => 'فاکتور جدید',
                'url' => SITE_URL . '/invoices/add',
                'icon' => 'bi bi-plus'
            ],
            [
                'title' => 'فاکتورهای معوق',
                'url' => SITE_URL . '/invoices/pending',
                'icon' => 'bi bi-clock',
                'badge' => $pending_invoices_count ? ['text' => $pending_invoices_count, 'class' => 'bg-warning'] : null
            ],
            [
                'title' => 'پیش‌فاکتورها',
                'url' => SITE_URL . '/invoices/quotes',
                'icon' => 'bi bi-file-earmark'
            ],
            [
                'title' => 'فاکتورهای برگشتی',
                'url' => SITE_URL . '/invoices/returns',
                'icon' => 'bi bi-arrow-return-left'
            ],
            [
                'title' => 'گزارش فروش',
                'url' => SITE_URL . '/invoices/report',
                'icon' => 'bi bi-graph-up'
            ]
        ]
    ],
    'payments' => [
        'title' => 'پرداخت‌ها',
        'icon' => 'bi bi-cash-stack',
        'submenu' => [
            [
                'title' => 'لیست پرداخت‌ها',
                'url' => SITE_URL . '/payments',
                'icon' => 'bi bi-list'
            ],
            [
                'title' => 'پرداخت جدید',
                'url' => SITE_URL . '/payments/add',
                'icon' => 'bi bi-plus'
            ],
            [
                'title' => 'چک‌های دریافتی',
                'url' => SITE_URL . '/payments/checks',
                'icon' => 'bi bi-credit-card',
                'badge' => $upcoming_checks_count ? ['text' => $upcoming_checks_count, 'class' => 'bg-info'] : null
            ],
            [
                'title' => 'صندوق',
                'url' => SITE_URL . '/payments/cash',
                'icon' => 'bi bi-safe'
            ],
            [
                'title' => 'حساب‌های بانکی',
                'url' => SITE_URL . '/payments/accounts',
                'icon' => 'bi bi-bank'
            ],
            [
                'title' => 'گزارش مالی',
                'url' => SITE_URL . '/payments/report',
                'icon' => 'bi bi-file-earmark-text'
            ]
        ]
    ],
    'expenses' => [
        'title' => 'هزینه‌ها',
        'icon' => 'bi bi-wallet2',
        'submenu' => [
            [
                'title' => 'لیست هزینه‌ها',
                'url' => SITE_URL . '/expenses',
                'icon' => 'bi bi-list'
            ],
            [
                'title' => 'ثبت هزینه',
                'url' => SITE_URL . '/expenses/add',
                'icon' => 'bi bi-plus'
            ],
            [
                'title' => 'دسته‌بندی هزینه‌ها',
                'url' => SITE_URL . '/expenses/categories',
                'icon' => 'bi bi-folder'
            ],
            [
                'title' => 'گزارش هزینه‌ها',
                'url' => SITE_URL . '/expenses/report',
                'icon' => 'bi bi-file-earmark-text'
            ]
        ]
    ],
    'reports' => [
        'title' => 'گزارشات',
        'icon' => 'bi bi-file-earmark-text',
        'submenu' => [
            [
                'title' => 'گزارش سود و زیان',
                'url' => SITE_URL . '/reports/profit-loss',
                'icon' => 'bi bi-graph-up'
            ],
            [
                'title' => 'گزارش فروش',
                'url' => SITE_URL . '/reports/sales',
                'icon' => 'bi bi-cart'
            ],
            [
                'title' => 'گزارش مالی',
                'url' => SITE_URL . '/reports/financial',
                'icon' => 'bi bi-cash'
            ],
            [
                'title' => 'گزارش موجودی',
                'url' => SITE_URL . '/reports/inventory',
                'icon' => 'bi bi-box'
            ],
            [
                'title' => 'گزارش مشتریان',
                'url' => SITE_URL . '/reports/customers',
                'icon' => 'bi bi-people'
            ],
            [
                'title' => 'گزارش چک‌ها',
                'url' => SITE_URL . '/reports/checks',
                'icon' => 'bi bi-credit-card'
            ]
        ]
    ],
    'settings' => [
        'title' => 'تنظیمات',
        'icon' => 'bi bi-gear',
        'submenu' => [
            [
                'title' => 'اطلاعات کسب و کار',
                'url' => SITE_URL . '/settings/business',
                'icon' => 'bi bi-building'
            ],
            [
                'title' => 'تنظیمات کاربری',
                'url' => SITE_URL . '/settings/profile',
                'icon' => 'bi bi-person'
            ],
            [
                'title' => 'قالب فاکتور',
                'url' => SITE_URL . '/settings/invoice-template',
                'icon' => 'bi bi-file-earmark'
            ],
            [
                'title' => 'یادآورها',
                'url' => SITE_URL . '/settings/reminders',
                'icon' => 'bi bi-bell',
                'badge' => $reminders_count ? ['text' => $reminders_count, 'class' => 'bg-danger'] : null
            ],
            [
                'title' => 'پشتیبان‌گیری',
                'url' => SITE_URL . '/settings/backup',
                'icon' => 'bi bi-cloud-arrow-up'
            ],
            [
                'title' => 'گزارش فعالیت‌ها',
                'url' => SITE_URL . '/settings/activity-log',
                'icon' => 'bi bi-clock-history'
            ]
        ]
    ]
];
?>

<div class="sidebar">
    <!-- لوگو -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <a href="<?php echo SITE_URL; ?>/dashboard">
                <img src="<?php echo SITE_URL; ?>/assets/img/logo.png" alt="حسابینو">
            </a>
        </div>
        <button type="button" class="btn-close btn-close-white d-lg-none" id="closeSidebar"></button>
    </div>

    <!-- پروفایل کاربر -->
    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <div class="sidebar-user-name">
                <?php echo $user_info['name'] . ' ' . $user_info['family']; ?>
            </div>
            <div class="sidebar-user-email" title="<?php echo $user_info['email']; ?>">
                <?php echo $user_info['email']; ?>
            </div>
        </div>
        <div class="sidebar-user-actions">
            <a href="<?php echo SITE_URL; ?>/settings/profile" class="btn btn-light btn-sm" title="تنظیمات">
                <i class="bi bi-gear"></i>
            </a>
            <a href="<?php echo SITE_URL; ?>/auth/logout" class="btn btn-light btn-sm" title="خروج">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- آمار کلی -->
    <div class="sidebar-stats">
        <div class="row g-2">
            <div class="col-6">
                <div class="sidebar-stat-item">
                    <div class="sidebar-stat-icon">
                        <i class="bi bi-people text-primary"></i>
                    </div>
                    <div class="sidebar-stat-info">
                        <div class="sidebar-stat-number">
                            <?php echo number_format($stats['customers_count']); ?>
                        </div>
                        <div class="sidebar-stat-label">مشتری</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="sidebar-stat-item">
                    <div class="sidebar-stat-icon">
                        <i class="bi bi-box text-success"></i>
                    </div>
                    <div class="sidebar-stat-info">
                        <div class="sidebar-stat-number">
                            <?php echo number_format($stats['products_count']); ?>
                        </div>
                        <div class="sidebar-stat-label">محصول</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="sidebar-stat-item">
                    <div class="sidebar-stat-icon">
                        <i class="bi bi-receipt text-warning"></i>
                    </div>
                    <div class="sidebar-stat-info">
                        <div class="sidebar-stat-number">
                            <?php echo number_format($stats['invoices_count']); ?>
                        </div>
                        <div class="sidebar-stat-label">فاکتور</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="sidebar-stat-item">
                    <div class="sidebar-stat-icon">
                        <i class="bi bi-cash-stack text-danger"></i>
                    </div>
                    <div class="sidebar-stat-info">
                        <div class="sidebar-stat-number">
                            <?php echo number_format($stats['total_payments']); ?>
                        </div>
                        <div class="sidebar-stat-label">فروش</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- منو -->
    <div class="sidebar-menu">
        <?php foreach ($menu as $key => $item): ?>
            <?php if (isset($item['submenu'])): ?>
                <div class="sidebar-menu-item">
                    <a href="#sidebar-<?php echo $key; ?>" class="sidebar-menu-button <?php echo $current_page == $key ? 'active' : ''; ?>" 
                       data-bs-toggle="collapse" role="button">
                        <i class="<?php echo $item['icon']; ?>"></i>
                        <span><?php echo $item['title']; ?></span>
                        <?php if (isset($item['badge']) && $item['badge']): ?>
                            <span class="badge <?php echo $item['badge']['class']; ?> rounded-pill">
                                <?php echo $item['badge']['text']; ?>
                            </span>
                        <?php endif; ?>
                        <i class="bi bi-chevron-down sidebar-menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $current_page == $key ? 'show' : ''; ?>" id="sidebar-<?php echo $key; ?>">
                        <div class="sidebar-submenu">
                            <?php foreach ($item['submenu'] as $submenu): ?>
                                <a href="<?php echo $submenu['url']; ?>" class="sidebar-submenu-item">
                                    <i class="<?php echo $submenu['icon']; ?>"></i>
                                    <span><?php echo $submenu['title']; ?></span>
                                    <?php if (isset($submenu['badge']) && $submenu['badge']): ?>
                                        <span class="badge <?php echo $submenu['badge']['class']; ?> rounded-pill">
                                            <?php echo $submenu['badge']['text']; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="sidebar-menu-item">
                    <a href="<?php echo $item['url']; ?>" class="sidebar-menu-button <?php echo $current_page == $key ? 'active' : ''; ?>">
                        <i class="<?php echo $item['icon']; ?>"></i>
                        <span><?php echo $item['title']; ?></span>
                        <?php if (isset($item['badge']) && $item['badge']): ?>
                            <span class="badge <?php echo $item['badge']['class']; ?> rounded-pill">
                                <?php echo $item['badge']['text']; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- فوتر سایدبار -->
    <div class="sidebar-footer">
        <div class="sidebar-footer-item">
            <i class="bi bi-clock"></i>
            <span>آخرین ورود:</span>
            <?php echo isset($user_info['last_login']) ? jdate('Y/m/d H:i', strtotime($user_info['last_login'])) : 'اولین ورود'; ?>
        </div>
        <div class="sidebar-footer-item">
            <i class="bi bi-person"></i>
            <span>تعداد ورود:</span>
            <?php echo isset($user_info['login_count']) ? number_format($user_info['login_count']) : '0'; ?> بار
        </div>
    </div>
</div>

<style>
/* استایل‌های سایدبار */
.sidebar {
    width: 280px;
    height: 100vh;
    position: fixed;
    top: 0;
    right: 0;
    background: #2c3e50;
    color: #ecf0f1;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    z-index: 1000;
}

.sidebar-header {
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-logo img {
    height: 40px;
}

.sidebar-user {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-user-info {
    margin-bottom: 0.5rem;
}

.sidebar-user-name {
    font-size: 1rem;
    font-weight: 500;
}

.sidebar-user-email {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-user-actions {
    display: flex;
    gap: 0.5rem;
}

.sidebar-stats {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-stat-item {
    background: rgba(255, 255, 255, 0.1);
    padding: 0.75rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.sidebar-stat-icon {
    font-size: 1.5rem;
}

.sidebar-stat-number {
    font-size: 1rem;
    font-weight: 500;
}

.sidebar-stat-label {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
}

.sidebar-menu {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 0;
}

.sidebar-menu-button {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: #ecf0f1;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.sidebar-menu-button:hover,
.sidebar-menu-button.active {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.sidebar-menu-button i:first-child {
    margin-left: 0.75rem;
    font-size: 1.1rem;
}

.sidebar-menu-button span {
    flex: 1;
}

.sidebar-menu-arrow {
    font-size: 0.875rem;
    transition: transform 0.3s ease;
}

.sidebar-menu-button.active .sidebar-menu-arrow {
    transform: rotate(180deg);
}

.sidebar-submenu {
    padding: 0.5rem 0;
    background: rgba(0, 0, 0, 0.1);
}

.sidebar-submenu-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem 0.5rem 2rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: all 0.3s ease;
}

.sidebar-submenu-item:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.05);
}

.sidebar-submenu-item i {
    margin-left: 0.75rem;
    font-size: 0.875rem;
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 0.875rem;
}

.sidebar-footer-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.5rem;
}

.sidebar-footer-item:last-child {
    margin-bottom: 0;
}

.sidebar-footer-item i {
    font-size: 1rem;
}

/* Badge های منو */
.sidebar .badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: normal;
}

/* اسکرول‌بار سفارشی */
.sidebar-menu::-webkit-scrollbar {
    width: 6px;
}

.sidebar-menu::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar-menu::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* حالت موبایل */
@media (max-width: 992px) {
    .sidebar {
        transform: translateX(100%);
    }

    .sidebar.show {
        transform: translateX(0);
    }

    body.sidebar-open {
        overflow: hidden;
    }

    .sidebar-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
    }

    .sidebar-backdrop.show {
        display: block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تنظیم ارتفاع منو
    function adjustMenuHeight() {
        const sidebar = document.querySelector('.sidebar');
        const header = document.querySelector('.sidebar-header');
        const user = document.querySelector('.sidebar-user');
        const stats = document.querySelector('.sidebar-stats');
        const footer = document.querySelector('.sidebar-footer');
        const menu = document.querySelector('.sidebar-menu');

        const availableHeight = sidebar.offsetHeight - 
            header.offsetHeight - 
            user.offsetHeight - 
            stats.offsetHeight - 
            footer.offsetHeight;

        menu.style.height = `${availableHeight}px`;
    }

    // مدیریت نمایش/مخفی کردن سایدبار در موبایل
    const sidebar = document.querySelector('.sidebar');
    const sidebarBackdrop = document.createElement('div');
    sidebarBackdrop.classList.add('sidebar-backdrop');
    document.body.appendChild(sidebarBackdrop);

    document.getElementById('sidebarToggle').addEventListener('click', function() {
        sidebar.classList.add('show');
        sidebarBackdrop.classList.add('show');
        document.body.classList.add('sidebar-open');
    });

    document.getElementById('closeSidebar').addEventListener('click', function() {
        sidebar.classList.remove('show');
        sidebarBackdrop.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    });

    sidebarBackdrop.addEventListener('click', function() {
        sidebar.classList.remove('show');
        sidebarBackdrop.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    });

    // تنظیم ارتفاع منو در لود صفحه و تغییر سایز
    adjustMenuHeight();
    window.addEventListener('resize', adjustMenuHeight);

    // افکت‌های هاور برای المان‌های منو
    const menuButtons = document.querySelectorAll('.sidebar-menu-button');
    menuButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateX(-5px)';
            this.style.transition = 'all 0.3s ease';
        });

        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    // افکت‌های هاور برای زیرمنوها
    const submenuItems = document.querySelectorAll('.sidebar-submenu-item');
    submenuItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(-5px)';
            this.style.transition = 'all 0.3s ease';
        });

        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    // آپدیت خودکار بج‌ها هر 5 دقیقه
    function updateBadges() {
        fetch(SITE_URL + '/api/notifications/counts')
            .then(response => response.json())
            .then(data => {
                // بروزرسانی تعداد محصولات کم موجود
                const lowStockBadges = document.querySelectorAll('[data-badge="low-stock"]');
                lowStockBadges.forEach(badge => {
                    badge.textContent = data.low_stock_count;
                    badge.style.display = data.low_stock_count > 0 ? 'inline-block' : 'none';
                });

                // بروزرسانی تعداد فاکتورهای معوق
                const pendingInvoiceBadges = document.querySelectorAll('[data-badge="pending-invoices"]');
                pendingInvoiceBadges.forEach(badge => {
                    badge.textContent = data.pending_invoices_count;
                    badge.style.display = data.pending_invoices_count > 0 ? 'inline-block' : 'none';
                });

                // بروزرسانی تعداد چک‌های سررسید
                const upcomingCheckBadges = document.querySelectorAll('[data-badge="upcoming-checks"]');
                upcomingCheckBadges.forEach(badge => {
                    badge.textContent = data.upcoming_checks_count;
                    badge.style.display = data.upcoming_checks_count > 0 ? 'inline-block' : 'none';
                });

                // بروزرسانی تعداد پیام‌های خوانده نشده
                const unreadMessageBadges = document.querySelectorAll('[data-badge="unread-messages"]');
                unreadMessageBadges.forEach(badge => {
                    badge.textContent = data.unread_messages_count;
                    badge.style.display = data.unread_messages_count > 0 ? 'inline-block' : 'none';
                });

                // بروزرسانی تعداد یادآورها
                const reminderBadges = document.querySelectorAll('[data-badge="reminders"]');
                reminderBadges.forEach(badge => {
                    badge.textContent = data.reminders_count;
                    badge.style.display = data.reminders_count > 0 ? 'inline-block' : 'none';
                });
            })
            .catch(error => console.error('Error updating badges:', error));
    }

    // آپدیت اولیه بج‌ها
    updateBadges();

    // آپدیت خودکار هر 5 دقیقه
    setInterval(updateBadges, 300000);

    // مدیریت کلیک روی منوهای آکاردئونی
    const accordionButtons = document.querySelectorAll('.sidebar-menu-button[data-bs-toggle="collapse"]');
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const submenu = this.nextElementSibling;
            const isOpen = submenu.classList.contains('show');
            
            // بستن سایر منوهای باز
            document.querySelectorAll('.sidebar-menu .collapse.show').forEach(item => {
                if (item !== submenu) {
                    item.classList.remove('show');
                    item.previousElementSibling.classList.remove('active');
                }
            });

            // تغییر وضعیت دکمه فعلی
            this.classList.toggle('active', !isOpen);
        });
    });
});