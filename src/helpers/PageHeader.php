<?php
/**
 * ========================================
 * Page Helper - Header & Navigation
 * ========================================
 */

function getPageHeader($title = 'Dashboard') {
    $user = Auth::user();
    $username = htmlspecialchars($user['full_name'] ?? $user['username'] ?? Auth::getFullName());
    $role = htmlspecialchars(strtoupper($user['role'] ?? 'USER'));
    
    return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title - HR Management</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/official-ui.css">
</head>
<body>
    <!-- Top Navbar -->
    <div class="header">
        <div class="header-left">
            <div class="header-logo">HR Management</div>
            <div class="header-title">$title</div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-info-name">$username</div>
                <div class="user-info-role">$role</div>
            </div>
            <button onclick="toggleDarkMode()" class="btn-icon" aria-label="Dark mode"><i class="ui-icon icon-moon" aria-hidden="true"></i></button>
            <button onclick="toggleSidebar()" class="btn-icon" aria-label="Menu"><i class="ui-icon icon-menu" aria-hidden="true"></i></button>
            <a href="logout.php" class="btn-icon" aria-label="Đăng xuất"><i class="ui-icon icon-logout" aria-hidden="true"></i></a>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="nav" id="sidebar">
        <div class="nav-header">MENU</div>
        <div class="nav-left">
            <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a>
            <a href="?page=employees"><i class="ui-icon icon-employees" aria-hidden="true"></i> Nhân viên</a>
            <a href="?page=employees&focus=search"><i class="ui-icon icon-search" aria-hidden="true"></i> Tìm kiếm NV</a>
            <a href="?page=contracts"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Hợp đồng</a>
            <a href="?page=salaries"><i class="ui-icon icon-salaries" aria-hidden="true"></i> Lương</a>
            <a href="?page=shifts"><i class="ui-icon icon-shifts" aria-hidden="true"></i> Ca làm việc</a>
            <a href="?page=schedules"><i class="ui-icon icon-schedules" aria-hidden="true"></i> Lịch làm việc</a>
            <a href="?page=part-timers"><i class="ui-icon icon-part-time" aria-hidden="true"></i> Part-time</a>
            <a href="?page=reports"><i class="ui-icon icon-reports" aria-hidden="true"></i> Báo cáo</a>
            <?php if (Auth::hasRole('Admin')): ?>
                <div class="nav-divider"></div>
                <div class="nav-header">ADMIN</div>
                <a href="?page=users"><i class="ui-icon icon-users" aria-hidden="true"></i> Người dùng</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Page Header -->
        <div class="page-header">
            <h1>$title</h1>
        </div>

        <div style="animation: slideDown 0.5s ease-out;">
HTML;
}

function getPageFooter() {
    return <<<HTML
        </div>
    </div>

    <div class="footer">
        <p>© 2026 Hệ thống Quản lý Nhân sự. Phát triển bằng PHP & MySQL.</p>
    </div>

    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('with-sidebar');
        }

        function loadDarkModePreference() {
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark-mode');
            }
        }

        window.addEventListener('load', loadDarkModePreference);
    </script>
</body>
</html>
HTML;
}

?>
