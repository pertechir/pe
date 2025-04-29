<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// اگر کاربر لاگین کرده است، به داشبورد هدایت شود
if (is_logged_in()) {
    redirect(SITE_URL . '/dashboard');
}

// تعریف انواع کسب و کار
$businessTypes = [
    'shop' => [
        'title' => 'فروشگاه',
        'icon' => 'shop',
        'description' => 'مناسب برای مغازه‌ها و فروشگاه‌های فیزیکی و آنلاین',
        'color' => '#3498db'
    ],
    'company' => [
        'title' => 'شرکت',
        'icon' => 'building',
        'description' => 'مناسب برای شرکت‌های خصوصی، دولتی و استارتاپ‌ها',
        'color' => '#2ecc71'
    ],
    'manufacturer' => [
        'title' => 'تولیدی',
        'icon' => 'gear',
        'description' => 'مناسب برای کارگاه‌ها و کارخانه‌های تولیدی',
        'color' => '#e67e22'
    ],
    'service' => [
        'title' => 'خدماتی',
        'icon' => 'tools',
        'description' => 'مناسب برای ارائه‌دهندگان خدمات و مشاغل آزاد',
        'color' => '#9b59b6'
    ],
    'other' => [
        'title' => 'سایر',
        'icon' => 'three-dots',
        'description' => 'سایر کسب و کارها',
        'color' => '#34495e'
    ]
];

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'family' => trim($_POST['family'] ?? ''),
        'email' => trim(strtolower($_POST['email'] ?? '')),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'business_type' => $_POST['business_type'] ?? '',
        'business_name' => trim($_POST['business_name'] ?? '')
    ];

    // اعتبارسنجی نام
    if (empty($data['name'])) {
        $errors['name'] = 'نام الزامی است';
    } elseif (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 50) {
        $errors['name'] = 'نام باید بین 2 تا 50 حرف باشد';
    }

    // اعتبارسنجی نام خانوادگی
    if (empty($data['family'])) {
        $errors['family'] = 'نام خانوادگی الزامی است';
    } elseif (mb_strlen($data['family']) < 2 || mb_strlen($data['family']) > 50) {
        $errors['family'] = 'نام خانوادگی باید بین 2 تا 50 حرف باشد';
    }

    // اعتبارسنجی ایمیل
    if (empty($data['email'])) {
        $errors['email'] = 'ایمیل الزامی است';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'ایمیل معتبر نیست';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->rowCount() > 0) {
            $errors['email'] = 'این ایمیل قبلاً ثبت شده است';
        }
    }

    // اعتبارسنجی شماره موبایل
    if (empty($data['phone'])) {
        $errors['phone'] = 'شماره موبایل الزامی است';
    } elseif (!preg_match('/^09[0-9]{9}$/', $data['phone'])) {
        $errors['phone'] = 'شماره موبایل معتبر نیست';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$data['phone']]);
        if ($stmt->rowCount() > 0) {
            $errors['phone'] = 'این شماره موبایل قبلاً ثبت شده است';
        }
    }

    // اعتبارسنجی رمز عبور
    if (empty($data['password'])) {
        $errors['password'] = 'رمز عبور الزامی است';
    } elseif (strlen($data['password']) < 8) {
        $errors['password'] = 'رمز عبور باید حداقل 8 کاراکتر باشد';
    } elseif (!preg_match('/[A-Z]/', $data['password'])) {
        $errors['password'] = 'رمز عبور باید شامل حداقل یک حرف بزرگ باشد';
    } elseif (!preg_match('/[a-z]/', $data['password'])) {
        $errors['password'] = 'رمز عبور باید شامل حداقل یک حرف کوچک باشد';
    } elseif (!preg_match('/[0-9]/', $data['password'])) {
        $errors['password'] = 'رمز عبور باید شامل حداقل یک عدد باشد';
    }

    if ($data['password'] !== $data['password_confirm']) {
        $errors['password_confirm'] = 'رمز عبور و تکرار آن مطابقت ندارند';
    }

    // اعتبارسنجی نوع کسب و کار
    if (!empty($data['business_type']) && !array_key_exists($data['business_type'], $businessTypes)) {
        $errors['business_type'] = 'نوع کسب و کار معتبر نیست';
    }

    // اعتبارسنجی نام کسب و کار
    if (!empty($data['business_name']) && mb_strlen($data['business_name']) > 100) {
        $errors['business_name'] = 'نام کسب و کار نباید بیشتر از 100 حرف باشد';
    }

    // اگر خطایی وجود نداشت، ثبت‌نام انجام شود
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // درج کاربر جدید
            $stmt = $pdo->prepare("
                INSERT INTO users (name, family, email, phone, password, business_type, business_name, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->execute([
                $data['name'],
                $data['family'],
                $data['email'],
                $data['phone'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['business_type'] ?: null,
                $data['business_name'] ?: null
            ]);

            $userId = $pdo->lastInsertId();

            // ثبت لاگ ورود
            $stmt = $pdo->prepare("
                INSERT INTO user_logs (user_id, action, ip_address, user_agent, created_at)
                VALUES (?, 'register', ?, ?, NOW())
            ");

            $stmt->execute([
                $userId,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);

            $pdo->commit();

            // ذخیره پیام موفقیت در سشن
            $_SESSION['success_message'] = 'ثبت‌نام با موفقیت انجام شد. لطفاً وارد شوید.';
            
            // هدایت به صفحه ورود
            redirect(SITE_URL . '/auth/login');

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            $errors['general'] = 'خطا در ثبت اطلاعات. لطفاً دوباره تلاش کنید.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام در حسابینو</title>
    
    <!-- فونت‌ها -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    
    <!-- استایل‌ها -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/auth.css">
    
    <style>
        /* استایل‌های اضافی */
        .form-floating > label {
            right: 0;
            left: auto;
            transform-origin: 100% 0;
            padding: 1rem 0.75rem;
        }
        
        .form-floating > .form-control {
            padding: 1rem 0.75rem;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xxl-6 col-xl-8 col-lg-10">
                <div class="register-card">
                    <div class="register-header">
                        <h1 class="h3 mb-3">ثبت‌نام در حسابینو</h1>
                        <p class="mb-0">برای شروع استفاده از سیستم، اطلاعات خود را وارد کنید</p>
                    </div>

                    <div class="register-steps mb-4">
                        <div class="step-item active" data-step="1">
                            <div class="step-number">1</div>
                            <div class="step-title">اطلاعات شخصی</div>
                        </div>
                        <div class="step-item" data-step="2">
                            <div class="step-number">2</div>
                            <div class="step-title">اطلاعات کسب و کار</div>
                        </div>
                        <div class="step-item" data-step="3">
                            <div class="step-number">3</div>
                            <div class="step-title">رمز عبور</div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- مرحله 1: اطلاعات شخصی -->
                            <div class="form-step active" data-step="1">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                                   id="name" name="name" placeholder=" "
                                                   value="<?php echo $_POST['name'] ?? ''; ?>" required>
                                            <label for="name">نام</label>
                                            <?php if (isset($errors['name'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control <?php echo isset($errors['family']) ? 'is-invalid' : ''; ?>"
                                                   id="family" name="family" placeholder=" "
                                                   value="<?php echo $_POST['family'] ?? ''; ?>" required>
                                            <label for="family">نام خانوادگی</label>
                                            <?php if (isset($errors['family'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['family']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                                   id="email" name="email" placeholder=" "
                                                   value="<?php echo $_POST['email'] ?? ''; ?>" required>
                                            <label for="email">ایمیل</label>
                                            <?php if (isset($errors['email'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                                                   id="phone" name="phone" placeholder=" "
                                                   value="<?php echo $_POST['phone'] ?? ''; ?>"
                                                   pattern="09[0-9]{9}" required>
                                            <label for="phone">شماره موبایل</label>
                                            <?php if (isset($errors['phone'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="button" class="btn btn-primary next-step">
                                        مرحله بعد
                                        <i class="bi bi-arrow-left ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- مرحله 2: اطلاعات کسب و کار -->
                            <div class="form-step" data-step="2">
                                <h5 class="mb-4">نوع کسب و کار خود را انتخاب کنید</h5>

                                <div class="business-type-grid">
                                    <?php foreach ($businessTypes as $key => $type): ?>
                                        <div class="business-type-item" data-value="<?php echo $key; ?>">
                                            <div class="business-type-icon">
                                                <i class="bi bi-<?php echo $type['icon']; ?>" style="color: <?php echo $type['color']; ?>"></i>
                                            </div>
                                            <h5><?php echo $type['title']; ?></h5>
                                            <p class="text-muted small mb-0"><?php echo $type['description']; ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="business_type" id="business_type" value="<?php echo $_POST['business_type'] ?? ''; ?>">

                                <div class="form-floating mt-4">
                                    <input type="text" class="form-control <?php echo isset($errors['business_name']) ? 'is-invalid' : ''; ?>"
                                           id="business_name" name="business_name" placeholder=" "
                                           value="<?php echo $_POST['business_name'] ?? ''; ?>">
                                    <label for="business_name">نام کسب و کار (اختیاری)</label>
                                    <?php if (isset($errors['business_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['business_name']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-2 mt-4">
                                    <button type="button" class="btn btn-outline-secondary prev-step">
                                        <i class="bi bi-arrow-right me-2"></i>
                                        مرحله قبل
                                    </button>
                                    <button type="button" class="btn btn-primary flex-grow-1 next-step">
                                        مرحله بعد
                                        <i class="bi bi-arrow-left ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- مرحله 3: رمز عبور -->
                            <div class="form-step" data-step="3">
                                <div class="mb-4">
                                    <div class="form-floating">
                                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                               id="password" name="password" placeholder=" " required>
                                        <label for="password">رمز عبور</label>
                                        <?php if (isset($errors['password'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="password-strength mt-2"></div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-floating">
                                        <input type="password" class="form-control <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>"
                                               id="password_confirm" name="password_confirm" placeholder=" " required>
                                        <label for="password_confirm">تکرار رمز عبور</label>
                                        <?php if (isset($errors['password_confirm'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['password_confirm']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary prev-step">
                                        <i class="bi bi-arrow-right me-2"></i>
                                        مرحله قبل
                                    </button>
                                    <button type="submit" class="btn btn-success flex-grow-1">
                                        <i class="bi bi-check-lg me-2"></i>
                                        تکمیل ثبت‌نام
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">
                                قبلاً ثبت‌نام کرده‌اید؟
                                <a href="<?php echo SITE_URL; ?>/auth/login" class="text-decoration-none">ورود به حساب</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // متغیرهای مورد نیاز
        const form = document.querySelector('form');
        const steps = document.querySelectorAll('.form-step');
        const stepItems = document.querySelectorAll('.step-item');
        const nextButtons = document.querySelectorAll('.next-step');
        const prevButtons = document.querySelectorAll('.prev-step');
        let currentStep = 1;

        // نمایش مرحله
        function showStep(stepNumber) {
            steps.forEach(step => {
                step.classList.remove('active');
                if (step.dataset.step == stepNumber) {
                    step.classList.add('active');
                }
            });

            stepItems.forEach(item => {
                item.classList.remove('active');
                if (item.dataset.step <= stepNumber) {
                    item.classList.add('active');
                }
            });

            currentStep = stepNumber;
        }

        // اعتبارسنجی مرحله فعلی
        function validateCurrentStep() {
            const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
            const inputs = currentStepElement.querySelectorAll('input[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            return isValid;
        }

        // دکمه‌های بعدی و قبلی
        nextButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (validateCurrentStep()) {
                    showStep(currentStep + 1);
                } else {
                    Swal.fire({
                        title: 'خطا',
                        text: 'لطفاً همه فیلدهای الزامی را پر کنید',
                        icon: 'error',
                        confirmButtonText: 'باشه'
                    });
                }
            });
        });

        prevButtons.forEach(button => {
            button.addEventListener('click', () => {
                showStep(currentStep - 1);
            });
        });

        // انتخاب نوع کسب و کار
        const businessTypeItems = document.querySelectorAll('.business-type-item');
        const businessTypeInput = document.getElementById('business_type');

        businessTypeItems.forEach(item => {
            item.addEventListener('click', () => {
                businessTypeItems.forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
                businessTypeInput.value = item.dataset.value;
            });
        });

        // بررسی قدرت رمز عبور
        const password = document.getElementById('password');
        password.addEventListener('input', function() {
                        const strength = checkPasswordStrength(this.value);
            updatePasswordStrengthIndicator(strength);
        });

        // تابع بررسی قدرت رمز عبور
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^A-Za-z0-9]/)) strength++;
            return strength;
        }

        // نمایش نشانگر قدرت رمز عبور
        function updatePasswordStrengthIndicator(strength) {
            const container = document.querySelector('.password-strength');
            let text = '';
            let color = '';

            switch(strength) {
                case 1:
                    text = 'خیلی ضعیف';
                    color = 'danger';
                    break;
                case 2:
                    text = 'ضعیف';
                    color = 'warning';
                    break;
                case 3:
                    text = 'متوسط';
                    color = 'info';
                    break;
                case 4:
                    text = 'قوی';
                    color = 'primary';
                    break;
                case 5:
                    text = 'خیلی قوی';
                    color = 'success';
                    break;
                default:
                    text = 'رمز عبور خود را وارد کنید';
                    color = 'secondary';
            }

            container.innerHTML = `
                <div class="progress" style="height: 5px">
                    <div class="progress-bar bg-${color}" style="width: ${strength * 20}%" role="progressbar"></div>
                </div>
                <small class="text-${color} mt-1 d-block">${text}</small>
            `;
        }

        // نمایش/مخفی کردن رمز عبور
        const togglePassword = document.getElementById('togglePassword');
        const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
        const passwordConfirm = document.getElementById('password_confirm');

        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.innerHTML = type === 'password' ? 
                    '<i class="bi bi-eye-fill"></i>' : 
                    '<i class="bi bi-eye-slash-fill"></i>';
            });
        }

        if (togglePasswordConfirm) {
            togglePasswordConfirm.addEventListener('click', function() {
                const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordConfirm.setAttribute('type', type);
                this.innerHTML = type === 'password' ? 
                    '<i class="bi bi-eye-fill"></i>' : 
                    '<i class="bi bi-eye-slash-fill"></i>';
            });
        }

        // ارسال فرم
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!validateCurrentStep()) {
                Swal.fire({
                    title: 'خطا',
                    text: 'لطفاً همه فیلدهای الزامی را پر کنید',
                    icon: 'error',
                    confirmButtonText: 'باشه'
                });
                return;
            }

            const formData = new FormData(this);
            
            // نمایش loading
            Swal.fire({
                title: 'در حال پردازش',
                text: 'لطفاً صبر کنید...',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                if (html.includes('success_message')) {
                    Swal.fire({
                        title: 'ثبت‌نام موفق',
                        text: 'ثبت‌نام شما با موفقیت انجام شد. در حال انتقال به صفحه ورود...',
                        icon: 'success',
                        confirmButtonText: 'باشه',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = '<?php echo SITE_URL; ?>/auth/login';
                    });
                } else {
                    Swal.fire({
                        title: 'خطا',
                        text: 'متأسفانه در فرآیند ثبت‌نام خطایی رخ داد. لطفاً دوباره تلاش کنید.',
                        icon: 'error',
                        confirmButtonText: 'باشه'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'خطا',
                    text: 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.',
                    icon: 'error',
                    confirmButtonText: 'باشه'
                });
            });
        });

        // نمایش خطاها در صورت وجود
        <?php if (isset($errors) && !empty($errors)): ?>
            Swal.fire({
                title: 'خطا در اطلاعات',
                html: '<?php echo implode("<br>", array_values($errors)); ?>',
                icon: 'warning',
                confirmButtonText: 'باشه'
            });
        <?php endif; ?>

        // نمایش پیام خوش‌آمدگویی
        Swal.fire({
            title: 'به حسابینو خوش آمدید',
            text: 'برای شروع، اطلاعات خود را وارد کنید',
            icon: 'info',
            confirmButtonText: 'شروع ثبت‌نام',
            allowOutsideClick: false
        });
    });
    </script>
</body>
</html>