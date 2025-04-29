<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (is_logged_in()) {
    redirect(SITE_URL . '/dashboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'business_name' => $_POST['business_name'] ?? ''
    ];

    $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'email' => 'required|email',
        'phone' => 'required|phone',
        'password' => 'required',
        'password_confirm' => 'required'
    ];

    $errors = validate_form($data, $rules);

    if ($data['password'] !== $data['password_confirm']) {
        $errors['password_confirm'] = 'رمز عبور و تکرار آن مطابقت ندارند';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$data['email'], $data['phone']]);
            
            if ($stmt->rowCount() > 0) {
                $errors['email'] = 'این ایمیل یا شماره موبایل قبلاً ثبت شده است';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO users (first_name, last_name, email, phone, password, business_name)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $data['first_name'],
                    $data['last_name'],
                    $data['email'],
                    $data['phone'],
                    password_hash($data['password'], PASSWORD_DEFAULT),
                    $data['business_name']
                ]);

                if ($result) {
                    show_message('ثبت‌نام با موفقیت انجام شد. لطفاً وارد شوید.');
                    redirect(SITE_URL . '/auth/login');
                }
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            show_message('خطا در ثبت اطلاعات', 'error');
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/auth.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0">ثبت‌نام در حسابینو</h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">نام</label>
                                    <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                           id="first_name" name="first_name" value="<?php echo $_POST['first_name'] ?? ''; ?>" required>
                                    <?php if (isset($errors['first_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">نام خانوادگی</label>
                                    <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>"
                                           id="last_name" name="last_name" value="<?php echo $_POST['last_name'] ?? ''; ?>" required>
                                    <?php if (isset($errors['last_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">ایمیل</label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                       id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">شماره موبایل</label>
                                <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                                       id="phone" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>" 
                                       pattern="09[0-9]{9}" required>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="business_name" class="form-label">نام کسب و کار (اختیاری)</label>
                                <input type="text" class="form-control" id="business_name" name="business_name"
                                       value="<?php echo $_POST['business_name'] ?? ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">رمز عبور</label>
                                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                       id="password" name="password" required>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">تکرار رمز عبور</label>
                                <input type="password" class="form-control <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>"
                                       id="password_confirm" name="password_confirm" required>
                                <?php if (isset($errors['password_confirm'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['password_confirm']; ?></div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">ثبت‌نام</button>
                        </form>

                        <div class="text-center mt-3">
                            <p>قبلاً ثبت‌نام کرده‌اید؟ <a href="<?php echo SITE_URL; ?>/auth/login">ورود به حساب</a></p>
                        </div>
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