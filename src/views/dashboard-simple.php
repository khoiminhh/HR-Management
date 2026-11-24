<?php
/**
 * ========================================
 * View: Dashboard
 * ========================================
 */

require_once '../src/models/Employee.php';

if (file_exists('../src/models/PartTimer.php')) {
    require_once '../src/models/PartTimer.php';
}

Auth::requireLogin();

$employee = new Employee($pdo);

$employees = $employee->getAll();

$totalEmployees = count($employees);

/**
 * Employee status
 */
$activeCount = 0;
$inactiveCount = 0;

foreach ($employees as $emp) {

    $status = strtolower(
        $emp['status'] ?? 'active'
    );

    if ($status === 'active') {

        $activeCount++;

    } else {

        $inactiveCount++;
    }
}

/**
 * Total salary this month
 */
try {

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(total_salary),0)
         FROM salaries
         WHERE salary_month=?
         AND salary_year=?"
    );

    $stmt->execute([
        date('m'),
        date('Y')
    ]);

    $totalSalaryThisMonth =
        (float)$stmt->fetchColumn();

} catch (Exception $e) {

    $totalSalaryThisMonth = 0;
}

/**
 * Active part timers
 */
try {

    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM part_timers
         WHERE status='Active'"
    );

    $activePartTimers =
        (int)$stmt->fetchColumn();

} catch (Exception $e) {

    $activePartTimers = 0;
}

/**
 * Total contracts
 */
try {

    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM contracts"
    );

    $totalContracts =
        (int)$stmt->fetchColumn();

} catch (Exception $e) {

    $totalContracts = 0;
}

/**
 * Part-time alerts
 */
$ptAlerts = [];

try {

    if (class_exists('PartTimer')) {

        $partTimerModel =
            new PartTimer($pdo);

        $ptAlerts =
            $partTimerModel->getAllAlerts(
                (int)date('m'),
                (int)date('Y')
            );
    }

} catch (Exception $e) {

    $ptAlerts = [];
}

/**
 * Current user
 */
$user = Auth::user();

$username = htmlspecialchars(
    $user['full_name']
    ?? $user['username']
    ?? 'Admin',
    ENT_QUOTES,
    'UTF-8'
);

$role = htmlspecialchars(
    strtoupper(
        $user['role']
        ?? 'ADMIN'
    ),
    ENT_QUOTES,
    'UTF-8'
);

if (!function_exists('dashboard_e')) {
    function dashboard_e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HR Management</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary:#2563eb; --primary-d:#1d4ed8; --success:#16a34a; --danger:#dc2626;
            --warning:#d97706; --purple:#7c3aed; --teal:#0d9488;
            --bg:#f1f5f9; --card:#ffffff; --text:#1e293b; --muted:#64748b;
            --border:#e2e8f0; --sidebar-w:240px;
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}

        /* HEADER */
        .header{position:fixed;top:0;left:0;right:0;z-index:100;background:linear-gradient(135deg,#1e3a5f,#2563eb);
                height:60px;display:flex;align-items:center;justify-content:space-between;padding:0 20px;
                box-shadow:0 2px 12px rgba(0,0,0,.2);}
        .header-left{display:flex;align-items:center;gap:14px;}
        .header-logo{font-size:22px;font-weight:800;color:white;letter-spacing:-.5px;}
        .header-logo span{color:#93c5fd;}
        .header-subtitle{color:#93c5fd;font-size:12px;font-weight:500;letter-spacing:1px;text-transform:uppercase;}
        .header-right{display:flex;align-items:center;gap:12px;}
        .user-badge{background:rgba(255,255,255,.15);border-radius:8px;padding:6px 12px;display:flex;align-items:center;gap:8px;}
        .user-badge .name{color:white;font-weight:600;font-size:13px;}
        .user-badge .role-tag{background:#3b82f6;color:white;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;text-transform:uppercase;}
        .btn-icon{background:rgba(255,255,255,.15);border:none;color:white;width:36px;height:36px;border-radius:8px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:background .2s;text-decoration:none;}
        .btn-icon:hover{background:rgba(255,255,255,.25);}

        /* SIDEBAR */
        .sidebar{position:fixed;top:60px;left:0;bottom:0;width:var(--sidebar-w);background:#0f172a;overflow-y:auto;z-index:90;transition:transform .3s ease;padding:16px 0;}
        .sidebar::-webkit-scrollbar{width:4px;} .sidebar::-webkit-scrollbar-thumb{background:#334155;border-radius:2px;}
        .nav-section{padding:8px 16px 4px;font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1.5px;}
        .nav-link{display:flex;align-items:center;gap:10px;padding:10px 16px;color:#94a3b8;text-decoration:none;font-size:14px;font-weight:500;transition:all .2s;margin:1px 0;position:relative;}
        .nav-link:hover{background:rgba(255,255,255,.06);color:#e2e8f0;}
        .nav-link.active{background:linear-gradient(90deg,rgba(37,99,235,.3),transparent);color:#60a5fa;border-left:3px solid #3b82f6;}
        .nav-link .icon{font-size:16px;width:20px;text-align:center;}
        .nav-divider{height:1px;background:#1e293b;margin:10px 16px;}
        .nav-badge{margin-left:auto;background:#ef4444;color:white;font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;}

        /* MAIN */
        .main-content{margin-left:var(--sidebar-w);margin-top:60px;padding:28px;min-height:calc(100vh - 60px);}

        /* PAGE HEADER */
        .page-header{margin-bottom:28px;}
        .page-header h1{font-size:28px;font-weight:800;color:#1e293b;letter-spacing:-.5px;}
        .page-header p{color:var(--muted);font-size:14px;margin-top:4px;}
        .date-chip{display:inline-block;background:white;border:1px solid var(--border);padding:4px 12px;border-radius:20px;font-size:12px;color:var(--muted);margin-top:8px;}

        /* STAT CARDS */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-bottom:28px;}
        .stat-card{background:var(--card);border-radius:14px;padding:22px;box-shadow:0 1px 3px rgba(0,0,0,.07),0 4px 16px rgba(0,0,0,.04);border:1px solid var(--border);position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s;}
        .stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.1);}
        .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;}
        .stat-card.blue::before{background:linear-gradient(90deg,#2563eb,#60a5fa);}
        .stat-card.green::before{background:linear-gradient(90deg,#16a34a,#4ade80);}
        .stat-card.red::before{background:linear-gradient(90deg,#dc2626,#f87171);}
        .stat-card.orange::before{background:linear-gradient(90deg,#d97706,#fbbf24);}
        .stat-card.purple::before{background:linear-gradient(90deg,#7c3aed,#a78bfa);}
        .stat-card.teal::before{background:linear-gradient(90deg,#0d9488,#2dd4bf);}
        .stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px;}
        .stat-card.blue .stat-icon{background:#eff6ff;} .stat-card.green .stat-icon{background:#f0fdf4;}
        .stat-card.red .stat-icon{background:#fef2f2;} .stat-card.orange .stat-icon{background:#fffbeb;}
        .stat-card.purple .stat-icon{background:#faf5ff;} .stat-card.teal .stat-icon{background:#f0fdfa;}
        .stat-value{font-size:30px;font-weight:800;color:var(--text);line-height:1;}
        .stat-label{font-size:13px;color:var(--muted);margin-top:6px;font-weight:500;}
        .stat-sub{font-size:12px;color:#94a3b8;margin-top:4px;}

        /* SECTION TITLE */
        .section-title{font-size:18px;font-weight:700;color:var(--text);margin-bottom:16px;display:flex;align-items:center;gap:8px;}
        .section-title::after{content:'';flex:1;height:1px;background:var(--border);}

        /* PT ALERT CARDS */
        .pt-alert-section{margin-bottom:28px;}
        .pt-alerts{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px;}
        .pt-alert-card{background:#fff;border-radius:12px;padding:18px;border-left:4px solid #dc2626;box-shadow:0 1px 3px rgba(0,0,0,.08);}
        .pt-alert-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;}
        .pt-name{font-weight:700;font-size:15px;color:var(--text);display:block;}
        .pt-code{font-size:11px;color:var(--muted);background:#f1f5f9;padding:2px 7px;border-radius:5px;margin-top:3px;display:inline-block;}
        .pt-link{font-size:12px;color:var(--primary);text-decoration:none;font-weight:600;white-space:nowrap;}
        .pt-link:hover{text-decoration:underline;}
        .pt-progress-wrap{display:flex;align-items:center;gap:8px;margin-bottom:8px;}
        .pt-progress-bg{flex:1;height:10px;background:#e2e8f0;border-radius:6px;overflow:hidden;}
        .pt-progress-fill{height:100%;border-radius:6px;transition:width .4s;}
        .pt-percent{font-size:12px;font-weight:700;color:var(--muted);min-width:36px;text-align:right;}
        .pt-stats{display:flex;gap:14px;font-size:12px;color:var(--muted);margin-bottom:10px;flex-wrap:wrap;}
        .pt-badge{font-size:12px;font-weight:600;padding:6px 10px;border-radius:7px;margin-top:6px;}
        .pt-badge.red{background:#fef2f2;color:#991b1b;}
        .pt-badge.orange{background:#fffbeb;color:#92400e;}
        .pt-badge.yellow{background:#fefce8;color:#713f12;}

        /* QUICK ACCESS */
        .quick-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px;margin-bottom:28px;}
        .quick-btn{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:20px 14px;text-decoration:none;display:flex;flex-direction:column;align-items:center;gap:10px;transition:all .2s;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.06);}
        .quick-btn:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,.1);border-color:transparent;}
        .quick-btn .q-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;}
        .quick-btn .q-label{font-size:13px;font-weight:600;color:var(--text);}
        .q-blue{background:#eff6ff;} .q-green{background:#f0fdf4;} .q-purple{background:#faf5ff;}
        .q-orange{background:#fffbeb;} .q-teal{background:#f0fdfa;} .q-pink{background:#fdf2f8;}
        .q-red{background:#fef2f2;} .q-indigo{background:#eef2ff;} .q-yellow{background:#fefce8;}

        /* FOOTER */
        .footer{margin-left:var(--sidebar-w);background:white;border-top:1px solid var(--border);padding:16px 28px;text-align:center;color:var(--muted);font-size:12px;}

        /* DARK MODE */
        body.dark-mode{--bg:#0f172a;--card:#1e293b;--text:#f1f5f9;--muted:#94a3b8;--border:#334155;}
        body.dark-mode .header{background:linear-gradient(135deg,#0f172a,#1e3a5f);}
        body.dark-mode .sidebar{background:#020617;}
        body.dark-mode .pt-alert-card{background:#1e293b;}
        body.dark-mode .footer{background:#1e293b;border-color:#334155;}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%);} .sidebar.show{transform:translateX(0);}
            .main-content{margin-left:0;} .footer{margin-left:0;}
            .stats-grid{grid-template-columns:1fr 1fr;}
        }
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
<link rel="stylesheet" href="css/official-ui.css">
    <style>
        :root{--sidebar-w:270px;--topbar-h:66px;}
        body{background:radial-gradient(circle at 85% 8%,rgba(20,184,166,.12),transparent 32rem),linear-gradient(180deg,#f8fbff 0%,#eef4fa 100%) !important;}
        .header{top:8px !important;left:8px !important;right:8px !important;height:var(--topbar-h) !important;padding:0 20px !important;border-radius:18px !important;background:linear-gradient(135deg,rgba(30,64,175,.98),rgba(15,118,110,.95)) !important;box-shadow:0 18px 45px rgba(15,23,42,.20) !important;}
        .header::before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,rgba(255,255,255,.10),transparent 42%),radial-gradient(circle at 38% 20%,rgba(255,255,255,.16),transparent 18rem);pointer-events:none;}
        .header-left,.header-right{position:relative;z-index:1;}
        .brand-lockup{display:flex;align-items:center;gap:12px;min-width:0;}
        .brand-mark{width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);display:grid;place-items:center;box-shadow:inset 0 1px 0 rgba(255,255,255,.2);}
        .brand-mark span{width:22px;height:22px;border-radius:8px;background:linear-gradient(135deg,#38bdf8,#22c55e);display:block;position:relative;}
        .brand-mark span::after{content:'';position:absolute;right:-4px;bottom:-4px;width:12px;height:12px;border-radius:5px;background:#f59e0b;border:2px solid rgba(255,255,255,.8);}
        .header-logo{font-size:22px !important;letter-spacing:0 !important;white-space:nowrap;}
        .header-subtitle{font-size:11px !important;color:rgba(255,255,255,.66) !important;letter-spacing:1.5px !important;}
        .user-badge{height:38px;border-radius:13px !important;background:rgba(255,255,255,.14) !important;border:1px solid rgba(255,255,255,.18);box-shadow:inset 0 1px 0 rgba(255,255,255,.14);}
        .user-badge .role-tag{background:#dbeafe !important;color:#1d4ed8 !important;border-radius:999px !important;padding:3px 9px !important;}
        .btn-icon{width:38px !important;height:38px !important;border-radius:13px !important;background:rgba(255,255,255,.13) !important;border:1px solid rgba(255,255,255,.12) !important;}
        .btn-icon:hover{background:rgba(255,255,255,.22) !important;transform:translateY(-1px);}

        .sidebar{top:86px !important;left:8px !important;bottom:8px !important;width:var(--sidebar-w) !important;padding:14px 10px !important;border-radius:22px !important;background:linear-gradient(180deg,#0b1222 0%,#111827 54%,#0f172a 100%) !important;border:1px solid rgba(148,163,184,.16) !important;box-shadow:18px 0 44px rgba(15,23,42,.18) !important;}
        .sidebar-brand{margin:2px 8px 14px;padding:14px;border-radius:18px;background:linear-gradient(135deg,rgba(37,99,235,.22),rgba(20,184,166,.16));border:1px solid rgba(148,163,184,.16);}
        .sidebar-brand .sb-title{color:#fff;font-size:14px;font-weight:900;line-height:1.25;}
        .sidebar-brand .sb-sub{color:#8ea5c5;font-size:12px;margin-top:4px;font-weight:700;}
        .nav-section{padding:13px 14px 7px !important;color:#62728a !important;font-size:10px !important;letter-spacing:1.4px !important;}
        .nav-divider{margin:8px 12px !important;background:rgba(148,163,184,.14) !important;}
        .nav-link{min-height:46px;margin:4px 6px !important;padding:9px 12px !important;border-radius:15px !important;color:#a8b5c9 !important;gap:12px !important;font-weight:800 !important;border:1px solid transparent;overflow:hidden;}
        .nav-link::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(20,184,166,.10));opacity:0;transition:opacity .18s ease;}
        .nav-link:hover::before,.nav-link.active::before{opacity:1;}
        .nav-link:hover{color:#f8fafc !important;border-color:rgba(96,165,250,.18);transform:translateX(3px);}
        .nav-link.active{color:#fff !important;background:linear-gradient(135deg,rgba(37,99,235,.28),rgba(15,118,110,.18)) !important;border-left:0 !important;border-color:rgba(96,165,250,.28);box-shadow:0 12px 26px rgba(37,99,235,.18);}
        .nav-link .icon{position:relative;z-index:1;width:28px !important;height:28px;border-radius:10px;display:grid;place-items:center;background:rgba(255,255,255,.08);font-size:15px !important;flex:0 0 28px;}
        .nav-link.active .icon{background:#dbeafe;color:#1d4ed8;}
        .nav-link .label{position:relative;z-index:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        .nav-badge{position:relative;z-index:1;min-width:22px;height:22px;border-radius:999px !important;display:inline-flex;align-items:center;justify-content:center;margin-left:auto !important;background:#ef4444 !important;box-shadow:0 0 0 4px rgba(239,68,68,.14);}
        .main-content{margin-left:calc(var(--sidebar-w) + 8px) !important;margin-top:86px !important;padding:34px 30px !important;background:transparent !important;}
        .footer{margin-left:calc(var(--sidebar-w) + 8px) !important;background:transparent !important;border-top:0 !important;}
        .page-header{padding:4px 0 2px;}
        .date-chip{box-shadow:0 8px 18px rgba(15,23,42,.08);}

        body.dark-mode{--bg:#0b1222;--card:#111827;--text:#e5eefb;--muted:#94a3b8;--border:#243247;}
        body.dark-mode .main-content{background:transparent !important;}
        body.dark-mode .sidebar{background:linear-gradient(180deg,#050816,#0b1222 56%,#070b14 100%) !important;}

        @media(max-width:768px){
            .header{left:8px !important;right:8px !important;}
            .header-subtitle{display:none;}
            .sidebar{transform:translateX(calc(-100% - 18px));z-index:120;}
            .sidebar.show{transform:translateX(0);}
            .main-content{margin-left:0 !important;margin-top:86px !important;padding:22px 14px !important;}
            .footer{margin-left:0 !important;}
            .user-badge .name{max-width:95px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div class="header-left">
        <div class="brand-mark"><span></span></div>
        <div class="header-logo">HR Management</div>
        <div class="header-subtitle">Hệ thống quản lý nhân sự</div>
    </div>
    <div class="header-right">
        <div class="user-badge">
            <div class="name"><i class="ui-icon icon-user" aria-hidden="true"></i> <?= $username ?></div>
            <span class="role-tag"><?= $role ?></span>
        </div>
        <button onclick="toggleDarkMode()" class="btn-icon" title="Dark mode" aria-label="Dark mode"><i class="ui-icon icon-moon" aria-hidden="true"></i></button>
        <button onclick="toggleSidebar()" class="btn-icon" title="Menu" aria-label="Menu"><i class="ui-icon icon-menu" aria-hidden="true"></i></button>
        <a href="logout.php" class="btn-icon" title="Đăng xuất" aria-label="Đăng xuất"><i class="ui-icon icon-logout" aria-hidden="true"></i></a>
    </div>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sb-title">HR Management</div>
        <div class="sb-sub">Workforce Operations</div>
    </div>
    <div class="nav-section">Tổng quan</div>
    <a href="?page=dashboard" class="nav-link active"><span class="icon icon-dashboard"></span><span class="label">Dashboard</span></a>

    <div class="nav-divider"></div>
    <div class="nav-section">Nhân sự</div>
    <a href="?page=employees"   class="nav-link"><span class="icon icon-employees"></span><span class="label">Nhân viên</span></a>
    <a href="?page=employees&focus=search" class="nav-link"><span class="icon icon-search"></span><span class="label">Tìm kiếm NV</span></a>
    <a href="?page=contracts"   class="nav-link"><span class="icon icon-contracts"></span><span class="label">Hợp đồng</span></a>
    <a href="?page=departments" class="nav-link"><span class="icon icon-departments"></span><span class="label">Phòng ban</span></a>

    <div class="nav-divider"></div>
    <div class="nav-section">Chấm công & Lịch</div>
    <?php if (!Auth::hasRole('Employee')): ?>
    <a href="?page=shifts"      class="nav-link"><span class="icon icon-shifts"></span><span class="label">Ca làm việc</span></a>
    <a href="?page=schedules"   class="nav-link"><span class="icon icon-schedules"></span><span class="label">Lịch làm việc</span></a>
    <?php endif; ?>
    <?php if (Auth::hasRole(['Admin', 'HR'])): ?>
    <a href="?page=attendance-requests" class="nav-link"><span class="icon icon-calendar"></span><span class="label">Duyệt chấm công</span></a>
    <?php endif; ?>
    <?php if (!Auth::hasRole('Employee')): ?>
    <a href="?page=part-timers" class="nav-link">
        <span class="icon icon-part-time"></span><span class="label">Part-time</span>
        <?php if (!empty($ptAlerts)): ?>
            <span class="nav-badge"><?= count($ptAlerts) ?></span>
        <?php endif; ?>
    </a>
    <?php endif; ?>

    <div class="nav-divider"></div>
    <div class="nav-section">Tài chính</div>
    <a href="?page=my-pay-and-attendance" class="nav-link"><span class="icon icon-profile"></span><span class="label">Thông tin nhân viên</span></a>
    <?php if (!Auth::hasRole('Employee')): ?>
    <a href="?page=salaries"     class="nav-link"><span class="icon icon-salaries"></span><span class="label">Bảng lương</span></a>
    <a href="?page=salary-stats" class="nav-link"><span class="icon icon-salary-stats"></span><span class="label">Thống kê lương</span></a>
    <?php endif; ?>


    <div class="nav-divider"></div>
    <div class="nav-section">Báo cáo</div>
    <a href="?page=reports" class="nav-link"><span class="icon icon-reports"></span><span class="label">Báo cáo</span></a>

    <?php if (Auth::hasRole('Admin')): ?>
    <div class="nav-divider"></div>
    <div class="nav-section">Quản trị</div>
    <a href="?page=users" class="nav-link"><span class="icon icon-users"></span><span class="label">Người dùng</span></a>
    <?php endif; ?>
</div>

<!-- MAIN -->
<div class="main-content" id="mainContent">

    <div class="page-header">
        <h1><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</h1>
        <p>Tổng quan hệ thống quản lý nhân sự</p>
        <span class="date-chip"><i class="ui-icon icon-calendar" aria-hidden="true"></i> <?= date('l, d/m/Y') ?></span>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon icon-employees"></div>
            <div class="stat-value"><?= $totalEmployees ?></div>
            <div class="stat-label">Tổng nhân viên</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon icon-success"></div>
            <div class="stat-value"><?= $activeCount ?></div>
            <div class="stat-label">Đang làm việc</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon icon-inactive"></div>
            <div class="stat-value"><?= $inactiveCount ?></div>
            <div class="stat-label">Đã nghỉ</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon icon-contracts"></div>
            <div class="stat-value"><?= $totalContracts ?></div>
            <div class="stat-label">Hợp đồng</div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon icon-part-time"></div>
            <div class="stat-value"><?= $activePartTimers ?></div>
            <div class="stat-label">Part-timer active</div>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon icon-salaries"></div>
            <div class="stat-value" style="font-size:18px;"><?= number_format($totalSalaryThisMonth,0,',','.') ?>đ</div>
            <div class="stat-label">Chi lương tháng <?= date('m/Y') ?></div>
        </div>
    </div>

    <!-- Cảnh báo Part-time -->
    <?php if (!empty($ptAlerts)): ?>
    <div class="pt-alert-section">
        <h2 class="section-title"><i class="ui-icon icon-alert" aria-hidden="true"></i> Cảnh báo Part-time tháng <?= date('m/Y') ?></h2>
        <div class="pt-alerts">
            <?php foreach ($ptAlerts as $pt):
                $a = array_merge([
                    'status' => 'ok',
                    'bhxh_required' => false,
                    'percent' => 0,
                    'total_amount' => 0,
                    'total_hours' => 0,
                    'remaining_amount' => 0,
                    'remaining_hours' => 0,
                    'bhxh_message' => '',
                ], $pt['analysis'] ?? []);

                $isExceeded  = $a['status'] === 'exceeded';
                $isWarning   = $a['status'] === 'warning';
                $isBhxh      = (bool)$a['bhxh_required'];
                $borderColor = ($isBhxh || $isExceeded) ? '#dc2626' : '#d97706';
                $bgColor     = ($isBhxh || $isExceeded) ? '#fef2f2' : '#fffbeb';
                $fillColor   = ($isExceeded || $isBhxh)  ? '#dc2626' : ($isWarning ? '#d97706' : '#16a34a');
                $percent     = max(0, min(100, (float)$a['percent']));
            ?>
            <div class="pt-alert-card" style="border-left-color:<?= $borderColor ?>;background:<?= $bgColor ?>;">
                <div class="pt-alert-top">
                    <div>
                        <span class="pt-name"><?= dashboard_e($pt['full_name'] ?? '') ?></span>
                        <span class="pt-code"><?= dashboard_e($pt['employee_code'] ?? '') ?></span>
                    </div>
                    <a href="?page=part-timers&action=hours&id=<?= (int)($pt['id'] ?? 0) ?>" class="pt-link">Xem chi tiết →</a>
                </div>
                <div class="pt-progress-wrap">
                    <div class="pt-progress-bg">
                        <div class="pt-progress-fill" style="width:<?= $percent ?>%;background:<?= $fillColor ?>;"></div>
                    </div>
                    <span class="pt-percent"><?= dashboard_e($a['percent']) ?>%</span>
                </div>
                <div class="pt-stats">
                    <span><i class="ui-icon icon-money" aria-hidden="true"></i> <?= number_format($a['total_amount'],0,',','.') ?> / <?= number_format(PartTimer::SALARY_MAX,0,',','.') ?> đ</span>
                    <span>⏱️ <?= number_format($a['total_hours'],1) ?> giờ</span>
                </div>
                <?php if ($isExceeded): ?>
                    <div class="pt-badge red">Đã đạt giới hạn — Không được thêm giờ</div>
                <?php elseif ($isWarning): ?>
                    <div class="pt-badge orange">Còn <?= number_format($a['remaining_amount'],0,',','.') ?> đ (<?= dashboard_e($a['remaining_hours']) ?> giờ)</div>
                <?php endif; ?>
                <?php if ($isBhxh): ?>
                    <div class="pt-badge red">Bắt buộc đóng BHXH tháng này</div>
                <?php elseif ($a['total_amount'] > 0): ?>
                    <div class="pt-badge yellow"><?= dashboard_e($a['bhxh_message']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Truy cập nhanh -->
    <h2 class="section-title"> Truy cập nhanh</h2>
    <div class="quick-grid">
        <?php if (Auth::hasRole('Employee')): ?>
            <a href="?page=my-pay-and-attendance" class="quick-btn qb-4"><div class="q-icon q-orange icon-profile"></div><span class="q-label">Thông tin nhân viên</span></a>
        <?php else: ?>
            <a href="?page=employees&action=add" class="quick-btn qb-1"><div class="q-icon q-blue icon-add"></div><span class="q-label">Thêm NV</span></a>
            <a href="?page=employees"            class="quick-btn qb-2"><div class="q-icon q-green icon-employees"></div><span class="q-label">Danh sách NV</span></a>
            <a href="?page=contracts"            class="quick-btn qb-3"><div class="q-icon q-purple icon-contracts"></div><span class="q-label">Hợp đồng</span></a>
            <a href="?page=salaries"             class="quick-btn qb-4"><div class="q-icon q-orange icon-salaries"></div><span class="q-label">Bảng lương</span></a>
            <a href="?page=salary-stats"         class="quick-btn qb-5"><div class="q-icon q-pink icon-salary-stats"></div><span class="q-label">Thống kê lương</span></a>
            <a href="?page=shifts"               class="quick-btn qb-6"><div class="q-icon q-teal icon-shifts"></div><span class="q-label">Ca làm việc</span></a>
            <a href="?page=schedules"            class="quick-btn qb-7"><div class="q-icon q-indigo icon-schedules"></div><span class="q-label">Lịch làm việc</span></a>
            <?php if (Auth::hasRole(['Admin', 'HR'])): ?>
            <a href="?page=attendance-requests"  class="quick-btn qb-7"><div class="q-icon q-indigo icon-calendar"></div><span class="q-label">Duyệt công</span></a>
            <?php endif; ?>
            <a href="?page=part-timers"          class="quick-btn qb-8"><div class="q-icon q-red icon-part-time"></div><span class="q-label">Part-time</span></a>
            <a href="?page=reports"              class="quick-btn qb-9"><div class="q-icon q-yellow icon-reports"></div><span class="q-label">Báo cáo</span></a>
        <?php endif; ?>
    </div>

</div><!-- /main-content -->

<div class="footer">© 2026 Hệ thống Quản lý Nhân sự. Phát triển bằng PHP & MySQL.</div>

<script>
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('mainContent').classList.toggle('expanded');
}
window.addEventListener('load', () => {
    if (localStorage.getItem('darkMode') === 'true') document.body.classList.add('dark-mode');
});
</script>
</body>
</html>


