<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// اگر کاربر قبلاً لاگین کرده است
if (is_logged_in()) {
    redirect(SITE_URL . '/dashboard');
}

// پردازش فرم ورود
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    try {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, password, role FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/');
            }

            redirect(SITE_URL . '/dashboard');
        } else {
            $error = 'ایمیل یا رمز عبور اشتباه است';
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'خطا در ورود به سیستم';
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به حسابینو</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/auth.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-lg-5 col-md-7">
                <div class="text-center mb-4">
                    <img src="<?php echo SITE_URL; ?>/assets/img/logo.png" alt="حسابینو" class="img-fluid mb-3" style="max-width: 150px;">
                    <h2 class="mb-0">ورود به حسابینو</h2>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">ایمیل</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">لطفاً ایمیل خود را وارد کنید</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">رمز عبور</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">لطفاً رمز عبور خود را وارد کنید</div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/auth/forgot-password" class="text-decoration-none">فراموشی رمز عبور</a>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">ورود</button>
                            
                            <div class="text-center">
                                <span>حساب کاربری ندارید؟</span>
                                <a href="<?php echo SITE_URL; ?>/auth/register" class="text-decoration-none">ثبت‌نام کنید</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/auth.js"></script>
</body>
</html>