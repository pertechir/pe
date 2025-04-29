document.addEventListener('DOMContentLoaded', function() {
    // اعتبارسنجی فرم‌ها
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // نمایش پیام‌های خطا و موفقیت با SweetAlert2
    if (typeof Swal !== 'undefined') {
        const successMessage = document.querySelector('meta[name="success_message"]')?.content;
        const errorMessage = document.querySelector('meta[name="error_message"]')?.content;

        if (successMessage) {
            Swal.fire({
                icon: 'success',
                title: 'موفقیت',
                text: successMessage,
                confirmButtonText: 'باشه'
            });
        }

        if (errorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: errorMessage,
                confirmButtonText: 'باشه'
            });
        }
    }

    // بررسی قدرت رمز عبور
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            const strengthBar = document.getElementById('password-strength');
            if (strengthBar) {
                strengthBar.style.width = (strength * 20) + '%';
                
                switch(strength) {
                    case 0:
                    case 1:
                        strengthBar.className = 'progress-bar bg-danger';
                        break;
                    case 2:
                    case 3:
                        strengthBar.className = 'progress-bar bg-warning';
                        break;
                    case 4:
                    case 5:
                        strengthBar.className = 'progress-bar bg-success';
                        break;
                }
            }
        });
    }

    // بررسی تطابق رمز عبور
    const passwordConfirmInput = document.getElementById('password_confirm');
    if (passwordConfirmInput && passwordInput) {
        passwordConfirmInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('رمز عبور و تکرار آن مطابقت ندارند');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});