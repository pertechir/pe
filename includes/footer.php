    </main>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

    <?php if (isset($_SESSION['success_message'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'موفقیت',
            text: '<?php echo $_SESSION['success_message']; ?>',
            confirmButtonText: 'باشه'
        });
    </script>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'خطا',
            text: '<?php echo $_SESSION['error_message']; ?>',
            confirmButtonText: 'باشه'
        });
    </script>
    <?php unset($_SESSION['error_message']); endif; ?>
</body>
</html>