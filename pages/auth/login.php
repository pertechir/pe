<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// اگر کاربر قبلاً لاگین کرده است، به داشبورد هدایت شود
if (is_logged_in()) {
    redirect(SITE_URL . '/dashboard');
}

$errors = [];
$success = '';

// پردازش فرم ورود
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // اعتبارسنجی ایمیل
    if (empty($email)) {
        $errors['email'] = 'ایمیل الزامی است';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'ایمیل معتبر نیست';
    }

    // اعتبارسنجی رمز عبور
    if (empty($password)) {
        $errors['password'] = 'رمز عبور الزامی است';
    }

    // اگر خطایی وجود نداشت
    if (empty($errors)) {
        try {
            // بررسی اطلاعات کاربر
            $stmt = $pdo->prepare("
                SELECT id, name, family, email, password, is_active, email_verified_at
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // بررسی فعال بودن حساب کاربری
                if (!$user['is_active']) {
                    $errors['general'] = 'حساب کاربری شما غیرفعال است';
                }
                // بررسی تایید ایمیل
                elseif (!$user['email_verified_at']) {
                    $errors['general'] = 'لطفاً ایمیل خود را تایید کنید';
                }
                else {
                    // ذخیره اطلاعات در سشن
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_family'] = $user['family'];
                    $_SESSION['user_email'] = $user['email'];

                    // اگر گزینه مرا به خاطر بسپار انتخاب شده باشد
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET remember_token = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$token, $user['id']]);

                        // ذخیره توکن در کوکی
                        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
                    }

                    // ثبت لاگ ورود
                    $stmt = $pdo->prepare("
                        INSERT INTO user_logs (user_id, action, ip_address, user_agent, created_at)
                        VALUES (?, 'login', ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $user['id'],
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT']
                    ]);

                    // بروزرسانی آخرین ورود
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET last_login_at = NOW(), 
                            last_login_ip = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$_SERVER['REMOTE_ADDR'], $user['id']]);

                    // انتقال به داشبورد
                    redirect(SITE_URL . '/dashboard');
                }
            } else {
                $errors['general'] = 'ایمیل یا رمز عبور اشتباه است';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors['general'] = 'خطا در ورود به سیستم';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به حسابینو</title>

    <?php if (!empty($errors)): ?>
        <meta name="error-message" content="<?php echo htmlspecialchars(implode(', ', $errors)); ?>">
    <?php endif; ?>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <meta name="success-message" content="<?php echo htmlspecialchars($_SESSION['success_message']); ?>">
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <!-- فونت‌ها -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    
    <!-- استایل‌ها -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/login.css">
</head>
<body>
    <!-- لودر -->
    <div class="loading-overlay">
        <div class="loader"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="<?php echo SITE_URL; ?>/assets/img/logo.png" alt="لوگو" class="login-logo">
                <h1 class="login-title">ورود به حسابینو</h1>
                <p class="mb-0">به سیستم مدیریت مالی حسابینو خوش آمدید</p>
            </div>

            <div class="login-content">
                <form id="loginForm" method="POST" class="needs-validation" novalidate>
                    <div class="form-floating mb-4">
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                               id="email" name="email" placeholder="ایمیل"
                               value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        <label for="email">
                            <i class="bi bi-envelope-fill me-2"></i>
                            ایمیل
                        </label>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-floating mb-4">
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                               id="password" name="password" placeholder="رمز عبور" required>
                        <label for="password">
                            <i class="bi bi-key-fill me-2"></i>
                            رمز عبور
                        </label>
                        <button type="button" class="password-toggle" id="passwordToggle">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-4">
                        <div class="col-auto">
                            <div class="form-check remember-me">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember"
                                       <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                            </div>
                        </div>
                        <div class="col text-end">
                            <a href="<?php echo SITE_URL; ?>/auth/forgot-password" class="text-decoration-none">
                                فراموشی رمز عبور؟
                            </a>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-left me-2"></i>
                            ورود به حساب
                        </button>
                    </div>
                </form>
            </div>

            <div class="login-footer">
                <p class="mb-0">
                    حساب کاربری ندارید؟
                    <a href="<?php echo SITE_URL; ?>/auth/register">ثبت‌نام کنید</a>
                </p>
            </div>
        </div>
    </div>

    <!-- اسکریپت‌ها -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/login.js"></script>
</body>
</html>