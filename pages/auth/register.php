<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// اگر کاربر لاگین کرده است، به داشبورد هدایت شود
if (is_logged_in()) {
    redirect(SITE_URL . '/dashboard');
}

// کلاس مدیریت ثبت‌نام
class Registration {
    private $pdo;
    private $data;
    private $errors = [];
    
    // انواع کسب و کار
    public $businessTypes = [
        'shop' => [
            'title' => 'فروشگاه',
            'icon' => 'store',
            'description' => 'مناسب برای مغازه‌ها و فروشگاه‌های فیزیکی و آنلاین'
        ],
        'company' => [
            'title' => 'شرکت',
            'icon' => 'building',
            'description' => 'مناسب برای شرکت‌های خصوصی، دولتی و استارتاپ‌ها'
        ],
        'manufacturer' => [
            'title' => 'تولیدی',
            'icon' => 'factory',
            'description' => 'مناسب برای کارگاه‌ها و کارخانه‌های تولیدی'
        ],
        'service' => [
            'title' => 'خدماتی',
            'icon' => 'wrench',
            'description' => 'مناسب برای ارائه‌دهندگان خدمات و مشاغل آزاد'
        ],
        'other' => [
            'title' => 'سایر',
            'icon' => 'briefcase',
            'description' => 'سایر کسب و کارها'
        ]
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // اعتبارسنجی داده‌ها
    public function validate($data) {
        $this->data = $data;
        
        // اعتبارسنجی نام
        if (empty($data['name'])) {
            $this->errors['name'] = 'نام الزامی است';
        } elseif (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 50) {
            $this->errors['name'] = 'نام باید بین 2 تا 50 حرف باشد';
        }

        // اعتبارسنجی نام خانوادگی
        if (empty($data['family'])) {
            $this->errors['family'] = 'نام خانوادگی الزامی است';
        } elseif (mb_strlen($data['family']) < 2 || mb_strlen($data['family']) > 50) {
            $this->errors['family'] = 'نام خانوادگی باید بین 2 تا 50 حرف باشد';
        }

        // اعتبارسنجی ایمیل
        if (empty($data['email'])) {
            $this->errors['email'] = 'ایمیل الزامی است';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'ایمیل معتبر نیست';
        } else {
            // بررسی تکراری نبودن ایمیل
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->rowCount() > 0) {
                $this->errors['email'] = 'این ایمیل قبلاً ثبت شده است';
            }
        }

        // اعتبارسنجی شماره موبایل
        if (empty($data['phone'])) {
            $this->errors['phone'] = 'شماره موبایل الزامی است';
        } elseif (!preg_match('/^09[0-9]{9}$/', $data['phone'])) {
            $this->errors['phone'] = 'شماره موبایل معتبر نیست';
        } else {
            // بررسی تکراری نبودن شماره موبایل
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$data['phone']]);
            if ($stmt->rowCount() > 0) {
                $this->errors['phone'] = 'این شماره موبایل قبلاً ثبت شده است';
            }
        }

        // اعتبارسنجی رمز عبور
        if (empty($data['password'])) {
            $this->errors['password'] = 'رمز عبور الزامی است';
        } elseif (strlen($data['password']) < 8) {
            $this->errors['password'] = 'رمز عبور باید حداقل 8 کاراکتر باشد';
        } elseif (!preg_match('/[A-Z]/', $data['password'])) {
            $this->errors['password'] = 'رمز عبور باید شامل حداقل یک حرف بزرگ باشد';
        } elseif (!preg_match('/[a-z]/', $data['password'])) {
            $this->errors['password'] = 'رمز عبور باید شامل حداقل یک حرف کوچک باشد';
        } elseif (!preg_match('/[0-9]/', $data['password'])) {
            $this->errors['password'] = 'رمز عبور باید شامل حداقل یک عدد باشد';
        }

        if ($data['password'] !== $data['password_confirm']) {
            $this->errors['password_confirm'] = 'رمز عبور و تکرار آن مطابقت ندارند';
        }

        // اعتبارسنجی نوع کسب و کار
        if (!empty($data['business_type']) && !array_key_exists($data['business_type'], $this->businessTypes)) {
            $this->errors['business_type'] = 'نوع کسب و کار معتبر نیست';
        }

        return empty($this->errors);
    }

    // ثبت کاربر جدید
    public function register() {
        try {
            $this->pdo->beginTransaction();

            // درج در جدول users
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, family, email, phone, password, business_type, business_name, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->execute([
                trim($this->data['name']),
                trim($this->data['family']),
                trim($this->data['email']),
                trim($this->data['phone']),
                password_hash($this->data['password'], PASSWORD_DEFAULT),
                $this->data['business_type'] ?? null,
                !empty($this->data['business_name']) ? trim($this->data['business_name']) : null
            ]);

            $userId = $this->pdo->lastInsertId();

            // ثبت لاگ ورود
            $stmt = $this->pdo->prepare("
                INSERT INTO user_logs (user_id, action, ip_address, user_agent, created_at)
                VALUES (?, 'register', ?, ?, NOW())
            ");

            $stmt->execute([
                $userId,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);

            $this->pdo->commit();
            return true;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    // دریافت خطاها
    public function getErrors() {
        return $this->errors;
    }
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration = new Registration($pdo);
    
    $data = [
        'name' => $_POST['name'] ?? '',
        'family' => $_POST['family'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'business_type' => $_POST['business_type'] ?? '',
        'business_name' => $_POST['business_name'] ?? ''
    ];

    if ($registration->validate($data)) {
        if ($registration->register()) {
            $_SESSION['success_message'] = 'ثبت‌نام با موفقیت انجام شد';
            redirect(SITE_URL . '/auth/login');
        } else {
            $error_message = 'خطا در ثبت اطلاعات';
        }
    } else {
        $errors = $registration->getErrors();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام در حسابینو</title>
    <!-- Bootstrap RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
        }

        body {
            background-image: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
        }

        .register-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.2);
        }

        .register-header {
            background-color: var(--accent-color);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .register-header h1 {
            font-size: 2rem;
            margin: 0;
        }

        .register-content {
            padding: 40px;
        }

        .form-control, .form-select {
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }

        .business-type-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .business-type-option {
            flex: 1;
            min-width: 200px;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .business-type-option:hover {
            border-color: var(--accent-color);
            background-color: #f8f9fa;
        }

        .business-type-option.selected {
            border-color: var(--accent-color);
            background-color: #ebf5fb;
        }

        .business-type-option i {
            font-size: 2rem;
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 0.875rem;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .step-dots {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #e9ecef;
            margin: 0 5px;
            transition: all 0.3s ease;
        }

        .step-dot.active {
            background-color: var(--accent-color);
        }

        @media (max-width: 768px) {
            .register-content {
                padding: 20px;
            }

            .business-type-option {
                min-width: 100%;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <div class="register-container">
                    <div class="register-header">
                        <h1>
                            <i class="bi bi-person-plus-fill me-2"></i>
                            ثبت‌نام در حسابینو
                        </h1>
                        <p class="mb-0">حساب کاربری خود را ایجاد کنید</p>
                    </div>

                    <div class="register-content">
                        <!-- نقاط مراحل -->
                        <div class="step-dots">
                            <span class="step-dot active" data-step="1"></span>
                            <span class="step-dot" data-step="2"></span>
                            <span class="step-dot" data-step="3"></span>
                        </div>

                        <form method="POST" class="needs-validation" novalidate>
                            <!-- مرحله 1: اطلاعات شخصی -->
                            <div class="form-step active" data-step="1">
                                <h4 class="mb-4">
                                    <i class="bi bi-person me-2"></i>
                                    اطلاعات شخصی
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">نام</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">نام خانوادگی</label>
                                        <input type="text" name="family" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ایمیل</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">شماره موبایل</label>
                                        <input type="tel" name="phone" class="form-control" pattern="09[0-9]{9}" required>
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
                                <h4 class="mb-4">
                                    <i class="bi bi-building me-2"></i>
                                    اطلاعات کسب و کار
                                </h4>

                                <div class="business-type-container">
                                    <?php foreach ($registration->businessTypes as $key => $type): ?>
                                        <div class="business-type-option" data-value="<?php echo $key; ?>">
                                            <i class="bi bi-<?php echo $type['icon']; ?>"></i>
                                            <h5><?php echo $type['title']; ?></h5>
                                            <p class="text-muted mb-0"><?php echo $type['description']; ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="business_type" id="business_type" value="">

                                <div class="mb-3">
                                    <label class="form-label">نام کسب و کار</label>
                                    <input type="text" name="business_name" class="form-control">
                                </div>

                                <div class="d-flex gap-2 mt-4">
                                    <button type="button" class="btn btn-outline-secondary prev-step">
                                        <i class="bi bi-arrow-right me-2"></i>
                                        مرحله قبل
                                    </button>
                                    <button type="button" class="btn btn-primary next-step">
                                        مرحله بعد
                                        <i class="bi bi-arrow-left ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- مرحله 3: رمز عبور -->
                            <div class="form-step" data-step="3">
                                <h4 class="mb-4">
                                    <i class="bi bi-shield-lock me-2"></i>
                                    تنظیم رمز عبور
                                </h4>

                                <div class="mb-3">
                                    <label class="form-label">رمز عبور</label>
                                    <div class="input-group">
                                        <input type="password" name="password" class="form-control" id="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength"></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">تکرار رمز عبور</label>
                                    <div class="input-group">
                                        <input type="password" name="password_confirm" class="form-control" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-4">
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
                            <p>قبلاً ثبت‌نام کرده‌اید؟ 
                                <a href="<?php echo SITE_URL; ?>/auth/login">ورود به حساب</a>
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
        // نمایش SweetAlert برای خوش‌آمدگویی
        Swal.fire({
            title: 'به حسابینو خوش آمدید',
            text: 'برای شروع، اطلاعات خود را وارد کنید',
            icon: 'info',
            confirmButtonText: 'شروع ثبت‌نام'
        });

        // مدیریت مراحل فرم
        const form = document.querySelector('form');
        const steps = document.querySelectorAll('.form-step');
        const dots = document.querySelectorAll('.step-dot');
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

            dots.forEach(dot => {
                dot.classList.remove('active');
                if (dot.dataset.step <= stepNumber) {
                    dot.classList.add('active');
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
        const businessTypeOptions = document.querySelectorAll('.business-type-option');
        const businessTypeInput = document.getElementById('business_type');

        businessTypeOptions.forEach(option => {
            option.addEventListener('click', () => {
                businessTypeOptions.forEach(opt => opt.classList.remove('selected'));
                option.classList.add('selected');
                businessTypeInput.value = option.dataset.value;
            });
        });

        // نمایش/مخفی کردن رمز عبور
        const togglePassword = document.getElementById('togglePassword');
        const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
        const password = document.querySelector('input[name="password"]');
        const passwordConfirm = document.querySelector('input[name="password_confirm"]');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('bi-eye-fill');
            this.querySelector('i').classList.toggle('bi-eye-slash-fill');
        });

        togglePasswordConfirm.addEventListener('click', function() {
            const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordConfirm.setAttribute('type', type);
            this.querySelector('i').classList.toggle('bi-eye-fill');
            this.querySelector('i').classList.toggle('bi-eye-slash-fill');
        });

        // بررسی قدرت رمز عبور
        password.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            updatePasswordStrengthIndicator(strength);
        });

        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^A-Za-z0-9]/)) strength++;
            return strength;
        }

        function updatePasswordStrengthIndicator(strength) {
            const indicator = password.parentNode.parentNode.querySelector('.password-strength');
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

            indicator.innerHTML = `
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-${color}" style="width: ${strength * 20}%"></div>
                </div>
                <small class="text-${color} mt-1 d-block">${text}</small>
            `;
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
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                if (html.includes('success')) {
                    Swal.fire({
                        title: 'ثبت‌نام موفق',
                        text: 'ثبت‌نام شما با موفقیت انجام شد. در حال انتقال به صفحه ورود...',
                        icon: 'success',
                        confirmButtonText: 'باشه'
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

        // نمایش پیام موفقیت
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                title: 'موفق',
                text: '<?php echo $_SESSION['success_message']; ?>',
                icon: 'success',
                confirmButtonText: 'باشه'
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>