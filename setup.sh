#!/bin/bash

# ساخت دایرکتوری‌های اصلی
mkdir -p {config,includes,pages/{auth,dashboard,products,services,invoices},assets/{css,js,img,uploads},logs}

# ایجاد فایل‌های اصلی
touch config/config.php
touch config/database.php
touch includes/{functions,auth,header,footer,sidebar}.php
touch pages/auth/{login,register,forgot-password}.php
touch pages/errors/{404,500}.php
touch assets/css/{style,auth,dashboard}.css
touch assets/js/{main,auth,dashboard}.js
touch index.php
touch .htaccess

echo "ساختار پوشه‌ها و فایل‌های پروژه ایجاد شد."