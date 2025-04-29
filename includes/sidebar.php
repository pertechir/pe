<div class="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo SITE_URL; ?>/assets/img/logo.png" alt="حسابینو" class="sidebar-logo">
        <h5 class="sidebar-title">حسابینو</h5>
    </div>
    
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?php echo SITE_URL; ?>/dashboard" class="nav-link <?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>داشبورد</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#sales-menu">
                    <i class="bi bi-cart"></i>
                    <span>فروش</span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                <div class="collapse" id="sales-menu">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/invoices/create" class="nav-link">صدور فاکتور</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/invoices" class="nav-link">لیست فاکتورها</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/customers" class="nav-link">مشتریان</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#inventory-menu">
                    <i class="bi bi-box-seam"></i>
                    <span>انبار و کالا</span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                <div class="collapse" id="inventory-menu">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/products" class="nav-link">محصولات</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/categories" class="nav-link">دسته‌بندی‌ها</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/inventory" class="nav-link">موجودی انبار</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#financial-menu">
                    <i class="bi bi-cash-stack"></i>
                    <span>امور مالی</span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                <div class="collapse" id="financial-menu">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/transactions" class="nav-link">تراکنش‌ها</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/checks" class="nav-link">مدیریت چک‌ها</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/expenses" class="nav-link">هزینه‌ها</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#reports-menu">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>گزارشات</span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                <div class="collapse" id="reports-menu">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/reports/sales" class="nav-link">گزارش فروش</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/reports/inventory" class="nav-link">گزارش موجودی</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/reports/financial" class="nav-link">گزارش مالی</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a href="<?php echo SITE_URL; ?>/settings" class="nav-link">
                    <i class="bi bi-gear"></i>
                    <span>تنظیمات</span>
                </a>
            </li>
        </ul>
    </div>
</div>