document.addEventListener('DOMContentLoaded', function() {
    // نمودار درآمد و هزینه
    const incomeExpenseChart = new Chart(
        document.getElementById('incomeExpenseChart'),
        {
            type: 'line',
            data: {
                labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'],
                datasets: [
                    {
                        label: 'درآمد',
                        data: [1200000, 1900000, 1500000, 2100000, 1800000, 2400000],
                        borderColor: '#2ecc71',
                        tension: 0.4
                    },
                    {
                        label: 'هزینه',
                        data: [800000, 1200000, 900000, 1500000, 1100000, 1600000],
                        borderColor: '#e74c3c',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'نمودار درآمد و هزینه ۶ ماه اخیر'
                    }
                }
            }
        }
    );

    // نمودار وضعیت موجودی
    const inventoryChart = new Chart(
        document.getElementById('inventoryChart'),
        {
            type: 'bar',
            data: {
                labels: ['محصول ۱', 'محصول ۲', 'محصول ۳', 'محصول ۴', 'محصول ۵'],
                datasets: [{
                    label: 'موجودی',
                    data: [45, 32, 18, 25, 36],
                    backgroundColor: '#3498db'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'وضعیت موجودی محصولات پرفروش'
                    }
                }
            }
        }
    );

    // نمودار دایره‌ای وضعیت فاکتورها
    const invoiceStatusChart = new Chart(
        document.getElementById('invoiceStatusChart'),
        {
            type: 'doughnut',
            data: {
                labels: ['پرداخت شده', 'معوق', 'لغو شده'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'وضعیت فاکتورها'
                    }
                }
            }
        }
    );
});

// تابع برای باز/بسته کردن سایدبار در حالت موبایل
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('show');
}

// به‌روزرسانی خودکار اعداد
function updateStats() {
    fetch('/api/dashboard/stats')
        .then(response => response.json())
        .then(data => {
            document.getElementById('todaySales').textContent = data.todaySales;
            document.getElementById('monthSales').textContent = data.monthSales;
            document.getElementById('pendingInvoices').textContent = data.pendingInvoices;
            document.getElementById('lowStock').textContent = data.lowStock;
        });
}

// هر ۵ دقیقه آمار را به‌روز کن
setInterval(updateStats, 300000);