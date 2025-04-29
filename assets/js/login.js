class LoginForm {
    constructor() {
        this.form = document.getElementById('loginForm');
        this.emailInput = document.getElementById('email');
        this.passwordInput = document.getElementById('password');
        this.rememberCheck = document.getElementById('remember');
        this.submitButton = document.querySelector('button[type="submit"]');
        this.passwordToggle = document.getElementById('passwordToggle');
        this.loadingOverlay = document.querySelector('.loading-overlay');

        this.setupEventListeners();
        this.setupPasswordToggle();
        this.setupFormValidation();
        this.setupParallaxEffect();
    }

    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // انیمیشن برای فیلدها هنگام تایپ
        const inputs = this.form.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.closest('.form-floating').style.transform = 'translateZ(30px)';
            });
            
            input.addEventListener('blur', () => {
                input.closest('.form-floating').style.transform = 'translateZ(10px)';
            });
        });
    }

    setupPasswordToggle() {
        if (this.passwordToggle) {
            this.passwordToggle.addEventListener('click', () => {
                const type = this.passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                this.passwordInput.setAttribute('type', type);
                this.passwordToggle.innerHTML = type === 'password' ? 
                    '<i class="bi bi-eye-fill"></i>' : 
                    '<i class="bi bi-eye-slash-fill"></i>';
            });
        }
    }

    setupFormValidation() {
        // اعتبارسنجی ایمیل
        this.emailInput.addEventListener('input', () => {
            const isValid = this.validateEmail(this.emailInput.value);
            this.emailInput.classList.toggle('is-invalid', !isValid);
        });

        // اعتبارسنجی رمز عبور
        this.passwordInput.addEventListener('input', () => {
            const isValid = this.passwordInput.value.length >= 8;
            this.passwordInput.classList.toggle('is-invalid', !isValid);
        });
    }

    setupParallaxEffect() {
        const card = document.querySelector('.login-card');
        
        document.addEventListener('mousemove', (e) => {
            const { clientX, clientY } = e;
            const { innerWidth, innerHeight } = window;
            
            const xAxis = (clientX - innerWidth / 2) / 50;
            const yAxis = (clientY - innerHeight / 2) / 50;
            
            card.style.transform = `rotateY(${xAxis}deg) rotateX(${-yAxis}deg)`;
        });

        // برگشت به حالت اولیه هنگام خروج موس
        document.addEventListener('mouseleave', () => {
            card.style.transform = 'rotateY(0) rotateX(0)';
        });
    }

    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    showLoading() {
        this.loadingOverlay.classList.add('active');
        this.submitButton.disabled = true;
    }

    hideLoading() {
        this.loadingOverlay.classList.remove('active');
        this.submitButton.disabled = false;
    }

    showError(message) {
        Swal.fire({
            title: 'خطا',
            text: message,
            icon: 'error',
            confirmButtonText: 'باشه'
        });
    }

    showSuccess(message) {
        Swal.fire({
            title: 'موفق',
            text: message,
            icon: 'success',
            confirmButtonText: 'باشه'
        });
    }

    async handleSubmit(e) {
        e.preventDefault();

        if (!this.form.checkValidity()) {
            this.showError('لطفاً همه فیلدها را به درستی پر کنید.');
            return;
        }

        this.showLoading();

        const formData = new FormData(this.form);

        try {
            const response = await fetch(this.form.action, {
                method: 'POST',
                body: formData
            });

            const data = await response.text();

            if (data.includes('success')) {
                this.showSuccess('ورود موفقیت‌آمیز. در حال انتقال...');
                setTimeout(() => {
                    window.location.href = '/dashboard';
                }, 1500);
            } else {
                this.showError('نام کاربری یا رمز عبور اشتباه است.');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.');
        } finally {
            this.hideLoading();
        }
    }
}

// راه‌اندازی فرم لاگین هنگام لود صفحه
document.addEventListener('DOMContentLoaded', () => {
    new LoginForm();

    // نمایش پیام خطا در صورت وجود
    const errorMessage = document.querySelector('meta[name="error-message"]')?.content;
    if (errorMessage) {
        Swal.fire({
            title: 'خطا',
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'باشه'
        });
    }

    // نمایش پیام موفقیت در صورت وجود
    const successMessage = document.querySelector('meta[name="success-message"]')?.content;
    if (successMessage) {
        Swal.fire({
            title: 'موفق',
            text: successMessage,
            icon: 'success',
            confirmButtonText: 'باشه'
        });
    }
});