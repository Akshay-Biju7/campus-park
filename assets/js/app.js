/**
 * CampusPark - College Parking & Mobility System
 * Modern Dark Helios Dashboard Client Script
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Auto-dismiss flash alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // 2. Mobile Sidebar Toggle
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const dashboardSidebar = document.getElementById('dashboardSidebar') || document.querySelector('.helios-sidebar') || document.querySelector('.dashboard-sidebar');
    if (sidebarToggleBtn && dashboardSidebar) {
        sidebarToggleBtn.addEventListener('click', function () {
            dashboardSidebar.classList.toggle('show');
        });

        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992) {
                if (!dashboardSidebar.contains(e.target) && !sidebarToggleBtn.contains(e.target)) {
                    dashboardSidebar.classList.remove('show');
                }
            }
        });
    }

    // 3. Interactive Filter Pills & Watchlist Tabs Active State Toggle
    const filterPills = document.querySelectorAll('.filter-pills-group .filter-pill-btn');
    filterPills.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (this.tagName === 'BUTTON') {
                filterPills.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });

    const watchlistTabs = document.querySelectorAll('.watchlist-pill-tabs .watchlist-tab');
    watchlistTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            watchlistTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const chartRangeBtns = document.querySelectorAll('.chart-range-pills .chart-range-btn');
    chartRangeBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            chartRangeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // 4. Client-side Table & Watchlist Search
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(function (input) {
        const targetSelector = input.getAttribute('data-table-search');
        const targetContainer = document.querySelector(targetSelector);

        if (!targetContainer) return;

        input.addEventListener('input', function () {
            const filterValue = this.value.toLowerCase().trim();
            const rows = targetContainer.querySelectorAll('tbody tr, .watchlist-row');

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                if (text.includes(filterValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // 5. Live Clock
    const liveClockEl = document.getElementById('liveClock');
    if (liveClockEl) {
        function updateClock() {
            const now = new Date();
            const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            liveClockEl.textContent = now.toLocaleTimeString('en-US', options);
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    // 6. Demo Autofill Helpers
    window.fillAdminDemo = function () {
        const usernameInput = document.getElementById('username') || document.querySelector('input[name="username"]');
        const passwordInput = document.getElementById('password') || document.querySelector('input[name="password"]');
        if (usernameInput) usernameInput.value = 'admin';
        if (passwordInput) passwordInput.value = 'admin123';
    };

    window.fillStudentDemo = function (roll, email, pass) {
        const loginInput = document.getElementById('login_id') || document.querySelector('input[name="login_id"]');
        const passwordInput = document.getElementById('password') || document.querySelector('input[name="password"]');
        if (loginInput) loginInput.value = roll || '22BTCS045';
        if (passwordInput) passwordInput.value = pass || 'student123';
    };

    // 7. Auto-uppercase for vehicle registration inputs
    const regPlateInputs = document.querySelectorAll('input[name="registration_number"]');
    regPlateInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    });
});
