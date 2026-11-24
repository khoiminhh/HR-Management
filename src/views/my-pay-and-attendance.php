<?php
/**
 * View: Nhân viên - Hồ sơ, ca làm, chấm công và bảng lương cá nhân
 */

Auth::requireLogin();

$month = (int)($_GET['month'] ?? date('m'));
$year  = (int)($_GET['year'] ?? date('Y'));

$canViewAllEmployees = $canViewAllEmployees ?? false;
$employeeOptions = $employeeOptions ?? [];
$employee = $employee ?? ['full_name' => Auth::getFullName(), 'employee_code' => ''];
$selectedEmployeeId = (int)($employee['id'] ?? ($_GET['employee_id'] ?? 0));
$salaryRows = $salaryRows ?? [];
$attendanceRows = $attendanceRows ?? [];
$scheduleRows = $scheduleRows ?? [];
$workedShiftRows = $workedShiftRows ?? [];
$plannedShiftRows = $plannedShiftRows ?? [];
$activeShiftRows = $activeShiftRows ?? [];
$contractRows = $contractRows ?? [];
$partTimerInfo = $partTimerInfo ?? null;
$partTimeRows = $partTimeRows ?? [];
$partTimeSummary = $partTimeSummary ?? ['total_hours' => 0, 'total_amount' => 0];
$summary = $summary ?? ['total_net' => 0, 'total_hours' => 0, 'total_scheduled_hours' => 0, 'scheduled_days' => 0, 'worked_shift_days' => 0, 'part_time_amount' => 0];
$message = $message ?? '';
$errors = $errors ?? [];
$todayAttendance = $todayAttendance ?? null;
$attendanceRequestRows = $attendanceRequestRows ?? [];
$currentAuthUser = Auth::user();
$username = htmlspecialchars($currentAuthUser['full_name'] ?? $currentAuthUser['username'] ?? Auth::getFullName());
$role = htmlspecialchars(strtoupper($currentAuthUser['role'] ?? 'USER'));

function myPayFmtMoney($n) {
    return number_format((float)$n, 0, ',', '.') . ' đ';
}

function myPayFmtDate($date) {
    return !empty($date) ? date('d/m/Y', strtotime($date)) : '-';
}

function myPayFmtTime($time) {
    return !empty($time) ? substr($time, 0, 5) : '-';
}

function myPayScheduleStatus($status) {
    $labels = [
        'Scheduled' => 'Đã lên lịch',
        'Present' => 'Có mặt',
        'Absent' => 'Vắng',
        'Leave' => 'Nghỉ phép'
    ];

    return $labels[$status] ?? ($status ?: '-');
}

function myPayStatusClass($status) {
    return match (strtolower((string)$status)) {
        'present' => 'ok',
        'absent' => 'bad',
        'leave' => 'warn',
        default => 'info'
    };
}

function myPayWorkShiftStatus($row) {
    $actualHours = (float)($row['actual_hours'] ?? 0);
    $attendanceStatus = strtolower((string)($row['attendance_status'] ?? ''));
    $scheduleStatus = strtolower((string)($row['schedule_status'] ?? ''));

    if ($actualHours > 0 || $attendanceStatus === 'present') {
        return ['label' => 'Đã đi làm', 'class' => 'ok'];
    }

    if ($attendanceStatus === 'absent' || $scheduleStatus === 'absent') {
        return ['label' => 'Vắng', 'class' => 'bad'];
    }

    if ($attendanceStatus === 'leave' || $scheduleStatus === 'leave') {
        return ['label' => 'Nghỉ phép', 'class' => 'warn'];
    }

    if (!empty($row['schedule_id'])) {
        return ['label' => 'Đã xếp ca', 'class' => 'info'];
    }

    return ['label' => 'Có chấm công', 'class' => 'info'];
}

function myPayContractStatusClass($status) {
    return match (strtolower((string)$status)) {
        'active' => 'ok',
        'expired', 'terminated' => 'bad',
        'pending' => 'warn',
        default => 'info'
    };
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin nhân viên</title>
    <style>
        :root{--primary:#2563eb;--primary-d:#1d4ed8;--accent:#0f766e;--success:#16a34a;--warning:#d97706;--danger:#dc2626;--bg:#f1f5f9;--card:#fff;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;--sidebar-w:270px;--topbar-h:66px;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',system-ui,sans-serif;background:radial-gradient(circle at 85% 8%,rgba(20,184,166,.12),transparent 32rem),linear-gradient(180deg,#f8fbff 0%,#eef4fa 100%);color:var(--text);}
        .header{position:fixed;top:8px;left:8px;right:8px;height:var(--topbar-h);z-index:110;display:flex;align-items:center;justify-content:space-between;padding:0 20px;border-radius:18px;background:linear-gradient(135deg,rgba(30,64,175,.98),rgba(15,118,110,.95));box-shadow:0 18px 45px rgba(15,23,42,.20);overflow:hidden;}
        .header::before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,rgba(255,255,255,.10),transparent 42%),radial-gradient(circle at 38% 20%,rgba(255,255,255,.16),transparent 18rem);pointer-events:none;}
        .header-left,.header-right{position:relative;z-index:1;display:flex;align-items:center;gap:12px;}
        .brand-mark{width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);display:grid;place-items:center;box-shadow:inset 0 1px 0 rgba(255,255,255,.2);}
        .brand-mark span{width:22px;height:22px;border-radius:8px;background:linear-gradient(135deg,#38bdf8,#22c55e);display:block;position:relative;}
        .brand-mark span::after{content:'';position:absolute;right:-4px;bottom:-4px;width:12px;height:12px;border-radius:5px;background:#f59e0b;border:2px solid rgba(255,255,255,.8);}
        .header-logo{color:#fff;font-size:22px;font-weight:900;white-space:nowrap;}
        .header-subtitle{font-size:11px;color:rgba(255,255,255,.66);letter-spacing:1.5px;text-transform:uppercase;font-weight:800;}
        .user-badge{height:38px;border-radius:13px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.18);display:flex;align-items:center;gap:9px;padding:0 12px;color:#fff;font-weight:800;box-shadow:inset 0 1px 0 rgba(255,255,255,.14);}
        .user-badge .role-tag{background:#dbeafe;color:#1d4ed8;border-radius:999px;padding:3px 9px;font-size:12px;}
        .btn-icon{width:38px;height:38px;border-radius:13px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.12);color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;cursor:pointer;}
        .sidebar{position:fixed;top:86px;left:8px;bottom:8px;width:var(--sidebar-w);padding:14px 10px;border-radius:22px;background:linear-gradient(180deg,#0b1222 0%,#111827 54%,#0f172a 100%);border:1px solid rgba(148,163,184,.16);box-shadow:18px 0 44px rgba(15,23,42,.18);z-index:100;overflow-y:auto;}
        .sidebar-brand{margin:2px 8px 14px;padding:14px;border-radius:18px;background:linear-gradient(135deg,rgba(37,99,235,.22),rgba(20,184,166,.16));border:1px solid rgba(148,163,184,.16);}
        .sidebar-brand .sb-title{color:#fff;font-size:14px;font-weight:900;line-height:1.25;}
        .sidebar-brand .sb-sub{color:#8ea5c5;font-size:12px;margin-top:4px;font-weight:700;}
        .nav-section{padding:13px 14px 7px;color:#62728a;font-size:10px;font-weight:900;letter-spacing:1.4px;text-transform:uppercase;}
        .nav-divider{height:1px;margin:8px 12px;background:rgba(148,163,184,.14);}
        .nav-link{min-height:46px;margin:4px 6px;padding:9px 12px;border-radius:15px;display:flex;align-items:center;gap:12px;color:#a8b5c9;text-decoration:none;font-size:14px;font-weight:800;border:1px solid transparent;position:relative;overflow:hidden;}
        .nav-link::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(20,184,166,.10));opacity:0;transition:opacity .18s ease;}
        .nav-link:hover::before,.nav-link.active::before{opacity:1;}
        .nav-link:hover{color:#f8fafc;border-color:rgba(96,165,250,.18);transform:translateX(3px);}
        .nav-link.active{color:#fff;background:linear-gradient(135deg,rgba(37,99,235,.28),rgba(15,118,110,.18));border-color:rgba(96,165,250,.28);box-shadow:0 12px 26px rgba(37,99,235,.18);}
        .nav-link .icon{position:relative;z-index:1;width:28px;height:28px;border-radius:10px;display:grid;place-items:center;background:rgba(255,255,255,.08);flex:0 0 28px;}
        .nav-link.active .icon{background:#dbeafe;color:#1d4ed8;}
        .nav-link .label{position:relative;z-index:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        .wrap{max-width:none;margin-left:calc(var(--sidebar-w) + 8px);margin-right:0;margin-top:86px;padding:34px 30px;}
        .page-hd{background:linear-gradient(135deg,#164e63,#2563eb);border-radius:14px;padding:26px 32px;margin-bottom:18px;box-shadow:0 4px 20px rgba(37,99,235,.22);display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
        .page-hd h1{color:#fff;font-size:25px;font-weight:800;}
        .page-hd p{color:#bfdbfe;font-size:14px;margin-top:6px;}
        .id-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:18px;display:grid;grid-template-columns:1fr;gap:16px;}
        @media(min-width:860px){.id-card{grid-template-columns:280px 1fr;align-items:stretch;}}
        .code-box{background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:18px;display:flex;flex-direction:column;justify-content:center;gap:8px;}
        .code-box .label{font-size:12px;text-transform:uppercase;color:var(--muted);font-weight:800;letter-spacing:.3px;}
        .code-box .code{font-size:28px;font-weight:900;color:var(--primary);word-break:break-word;}
        .code-box .name{font-size:16px;font-weight:800;color:var(--text);}
        .type-chip{display:inline-flex;width:max-content;background:#ecfeff;color:#0f766e;border:1px solid #99f6e4;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:900;}
        .profile-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;}
        .profile-item{background:#fff;border:1px solid #edf2f7;border-radius:8px;padding:12px;}
        .profile-item .k{font-size:12px;color:var(--muted);font-weight:800;margin-bottom:5px;}
        .profile-item .v{font-size:14px;color:var(--text);font-weight:700;}
        .filter-bar{display:flex;gap:12px;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid var(--border);border-radius:12px;padding:14px 18px;margin-bottom:18px;}
        .filter-bar label{font-size:13px;font-weight:800;color:var(--text);}
        .filter-bar select{padding:8px 12px;border-radius:8px;border:1px solid var(--border);background:#fff;}
        .admin-picker{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px 18px;margin-bottom:18px;display:grid;grid-template-columns:1fr;gap:12px;}
        .admin-picker .picker-title{font-size:15px;font-weight:900;color:var(--text);}
        .admin-picker .picker-sub{font-size:13px;color:var(--muted);margin-top:4px;}
        .admin-picker select{width:100%;padding:10px 12px;border-radius:9px;border:1px solid var(--border);background:#fff;font-weight:700;}
        @media(min-width:780px){.admin-picker{grid-template-columns:1fr 420px;align-items:end;}}
        .btn{padding:9px 16px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:800;transition:all .2s;white-space:nowrap;}
        .btn-primary{background:var(--primary);color:#fff;} .btn-primary:hover{background:var(--primary-d);}
        .btn-soft{background:#eff6ff;color:var(--primary);border:1px solid #bfdbfe;}
        .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:18px;}
        .sc{background:#fff;border:1px solid var(--border);border-radius:12px;padding:17px;box-shadow:0 1px 3px rgba(0,0,0,.05);border-left:4px solid var(--primary);}
        .sc.green{border-left-color:var(--success);} .sc.teal{border-left-color:var(--accent);} .sc.orange{border-left-color:var(--warning);}
        .sc .k{font-size:12px;color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.3px;}
        .sc .v{margin-top:8px;font-size:22px;font-weight:900;color:var(--text);}
        .section-grid{display:grid;grid-template-columns:1fr;gap:18px;}
        @media(min-width:1040px){.section-grid{grid-template-columns:1fr 1fr;}}
        .card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;}
        .card.full{grid-column:1/-1;}
        .card-hd{padding:14px 16px;background:#f8fafc;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;}
        .card-hd h3{font-size:15px;color:var(--text);font-weight:900;}
        .badge{background:#eff6ff;color:var(--primary);font-size:12px;font-weight:900;padding:4px 10px;border-radius:999px;border:1px solid #bfdbfe;}
        table{width:100%;border-collapse:collapse;font-size:14px;}
        th,td{padding:12px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
        th{background:#f8fafc;color:#475569;text-align:left;font-size:13px;font-weight:900;white-space:nowrap;}
        tbody tr:hover{background:#f8fbff;}
        .right{text-align:right;}
        .chip{display:inline-flex;align-items:center;padding:4px 9px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap;}
        .chip.info{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;}
        .chip.ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
        .chip.bad{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
        .chip.warn{background:#fffbeb;color:#b45309;border:1px solid #fde68a;}
        .time-chip{background:#ecfeff;color:#0e7490;padding:4px 9px;border-radius:7px;font-size:12px;font-weight:900;white-space:nowrap;}
        .empty{padding:36px 16px;text-align:center;color:var(--muted);}
        .empty .i{font-size:36px;margin-bottom:8px;}
        .back{margin-top:16px;display:inline-block;}
        .alert{padding:13px 16px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:700;}
        .alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
        .alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
        .attendance-actions{display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:18px;}
        @media(min-width:960px){.attendance-actions{grid-template-columns:.9fr 1.1fr;}}
        .attendance-box{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px;}
        .attendance-box h3{font-size:16px;font-weight:900;margin-bottom:10px;color:var(--text);}
        .attendance-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin:12px 0;}
        .attendance-meta div{background:#f8fafc;border:1px solid #edf2f7;border-radius:8px;padding:10px;}
        .attendance-meta span{display:block;font-size:12px;color:var(--muted);font-weight:800;margin-bottom:4px;}
        .attendance-meta strong{font-size:15px;color:var(--text);}
        .inline-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;align-items:end;}
        .inline-form label{font-size:12px;font-weight:900;color:var(--text);}
        .inline-form input,.inline-form textarea{width:100%;margin-top:6px;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-family:inherit;font-weight:700;background:#fff;}
        .inline-form textarea{min-height:42px;resize:vertical;}
        @media(max-width:640px){.wrap{padding:68px 12px 18px;}.page-hd{padding:22px 18px;}.page-hd h1{font-size:21px;}th,td{padding:10px 12px;}.sc .v{font-size:19px;}}
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
<link rel="stylesheet" href="css/official-ui.css">
<style>
    body > .wrap {
        width: auto !important;
        max-width: none !important;
        margin-left: calc(var(--sidebar-w) + 32px) !important;
        margin-right: 0 !important;
        margin-top: 86px !important;
        padding: 34px 30px !important;
    }

    @media (max-width: 900px) {
        .sidebar {
            transform: translateX(calc(-100% - 18px));
            z-index: 120;
        }

        .sidebar.show {
            transform: translateX(0);
        }

        body > .wrap {
            margin-left: 0 !important;
            padding: 22px 14px !important;
        }
    }
</style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <div class="brand-mark"><span></span></div>
        <div class="header-logo">HR Management</div>
        <div class="header-subtitle">Hệ thống quản lý nhân sự</div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div><?php endif; ?>
    <div class="header-right">
        <div class="user-badge">
            <div class="name"><i class="ui-icon icon-user" aria-hidden="true"></i> <?= $username ?></div>
            <span class="role-tag"><?= $role ?></span>
        </div>
        <button onclick="toggleDarkMode()" class="btn-icon" title="Dark mode" aria-label="Dark mode"><i class="ui-icon icon-moon" aria-hidden="true"></i></button>
        <button onclick="toggleSidebar()" class="btn-icon" title="Menu" aria-label="Menu"><i class="ui-icon icon-menu" aria-hidden="true"></i></button>
        <a href="logout.php" class="btn-icon" title="Dang xuat" aria-label="Dang xuat"><i class="ui-icon icon-logout" aria-hidden="true"></i></a>
    </div>
</div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sb-title">HR Management</div>
        <div class="sb-sub">Workforce Operations</div>
    </div>
    <div class="nav-section">Tổng quan</div>
    <a href="?page=dashboard" class="nav-link"><span class="icon icon-dashboard"></span><span class="label">Dashboard</span></a>

    <?php if (Auth::hasRole(['Admin', 'HR'])): ?>
    <div class="nav-divider"></div>
    <div class="nav-section">Nhân sự</div>
    <a href="?page=employees" class="nav-link"><span class="icon icon-employees"></span><span class="label">Nhân viên</span></a>
    <a href="?page=employees&focus=search" class="nav-link"><span class="icon icon-search"></span><span class="label">Tìm kiếm NV</span></a>
    <a href="?page=contracts" class="nav-link"><span class="icon icon-contracts"></span><span class="label">Hợp đồng</span></a>
    <a href="?page=departments" class="nav-link"><span class="icon icon-departments"></span><span class="label">Phòng ban</span></a>
    <?php endif; ?>

    <div class="nav-divider"></div>
    <div class="nav-section">Chấm công & Lịch</div>
    <a href="?page=shifts" class="nav-link"><span class="icon icon-shifts"></span><span class="label">Ca làm việc</span></a>
    <a href="?page=schedules" class="nav-link"><span class="icon icon-schedules"></span><span class="label">Lịch làm việc</span></a>
    <?php if (Auth::hasRole(['Admin', 'HR'])): ?>
    <a href="?page=part-timers" class="nav-link"><span class="icon icon-part-time"></span><span class="label">Part-time</span></a>
    <?php endif; ?>

    <div class="nav-divider"></div>
    <div class="nav-section">Tài chính</div>
    <a href="?page=my-pay-and-attendance<?= $canViewAllEmployees && $selectedEmployeeId ? '&employee_id=' . (int)$selectedEmployeeId : '' ?>" class="nav-link active"><span class="icon icon-profile"></span><span class="label">Thông tin nhân viên</span></a>
    <?php if (!Auth::hasRole('Employee')): ?>
    <a href="?page=salaries" class="nav-link"><span class="icon icon-salaries"></span><span class="label">Bảng lương</span></a>
    <a href="?page=salary-stats" class="nav-link"><span class="icon icon-salary-stats"></span><span class="label">Thống kê lương</span></a>
    <?php endif; ?>

    <?php if (Auth::hasRole(['Admin', 'HR', 'Manager'])): ?>
    <div class="nav-divider"></div>
    <div class="nav-section">Báo cáo</div>
    <a href="?page=reports" class="nav-link"><span class="icon icon-reports"></span><span class="label">Báo cáo</span></a>
    <?php endif; ?>

    <?php if (Auth::hasRole('Admin')): ?>
    <div class="nav-divider"></div>
    <div class="nav-section">Quản trị</div>
    <a href="?page=users" class="nav-link"><span class="icon icon-users"></span><span class="label">Người dùng</span></a>
    <?php endif; ?>
</aside><div class="wrap">
    <div class="page-hd">
        <div>
            <h1>Thông tin nhân viên</h1>
            <p><?= $canViewAllEmployees ? 'Admin/HR/Manager có thể xem đầy đủ hồ sơ, ca làm, chấm công và bảng lương của từng nhân viên' : 'Chỉ hiển thị hồ sơ, ca làm, chấm công và bảng lương của tài khoản đang đăng nhập' ?></p>
        </div>
        <a class="btn btn-soft" href="?page=dashboard">← Dashboard</a>
    </div>

    <?php if ($canViewAllEmployees): ?>
    <form class="admin-picker" method="GET">
        <input type="hidden" name="page" value="my-pay-and-attendance">
        <input type="hidden" name="month" value="<?= (int)$month ?>">
        <input type="hidden" name="year" value="<?= (int)$year ?>">
        <div>
            <div class="picker-title">Chọn nhân viên cần xem</div>
            <div class="picker-sub">Quyền xem: Admin, HR, Manager. Nhân viên thường chỉ xem được thông tin của chính mình.</div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <select name="employee_id" required>
                <?php foreach ($employeeOptions as $emp): ?>
                    <option value="<?= (int)$emp['id'] ?>" <?= (int)$emp['id'] === $selectedEmployeeId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['employee_code'] ?? '') ?> - <?= htmlspecialchars($emp['full_name'] ?? '') ?><?= !empty($emp['department']) ? ' (' . htmlspecialchars($emp['department']) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Xem</button>
        </div>
    </form>
    <?php endif; ?>

    <section class="id-card">
        <div class="code-box">
            <div class="label">Mã nhân viên</div>
            <div class="code"><?= htmlspecialchars($employee['employee_code'] ?? '-') ?></div>
            <div class="name"><?= htmlspecialchars($employee['full_name'] ?? Auth::getFullName()) ?></div>
            <div class="type-chip"><?= $partTimerInfo ? 'Nhân viên part-time' : 'Nhân viên full-time' ?></div>
        </div>
        <div class="profile-grid">
            <div class="profile-item"><div class="k">Họ tên</div><div class="v"><?= htmlspecialchars($employee['full_name'] ?? '-') ?></div></div>
            <div class="profile-item"><div class="k">Ngày sinh</div><div class="v"><?= myPayFmtDate($employee['date_of_birth'] ?? '') ?></div></div>
            <div class="profile-item"><div class="k">Quê quán / địa chỉ</div><div class="v"><?= htmlspecialchars($employee['address'] ?? '-') ?></div></div>
            <div class="profile-item"><div class="k">Chức vụ hiện tại</div><div class="v"><?= htmlspecialchars($employee['position'] ?? '-') ?></div></div>
            <div class="profile-item"><div class="k">Phòng ban</div><div class="v"><?= htmlspecialchars($employee['department'] ?? '-') ?></div></div>
            <div class="profile-item"><div class="k">Ngày vào làm</div><div class="v"><?= myPayFmtDate($employee['hire_date'] ?? '') ?></div></div>
            <?php if ($canViewAllEmployees): ?>
                <div class="profile-item"><div class="k">Email</div><div class="v"><?= htmlspecialchars($employee['email'] ?? '-') ?></div></div>
                <div class="profile-item"><div class="k">Điện thoại</div><div class="v"><?= htmlspecialchars($employee['phone'] ?? '-') ?></div></div>
                <div class="profile-item"><div class="k">Giới tính</div><div class="v"><?= htmlspecialchars($employee['gender'] ?? '-') ?></div></div>
                <div class="profile-item"><div class="k">Trạng thái</div><div class="v"><?= htmlspecialchars($employee['status'] ?? '-') ?></div></div>
            <?php endif; ?>
            <?php if ($partTimerInfo): ?>
                <div class="profile-item"><div class="k">Lương theo giờ</div><div class="v"><?= myPayFmtMoney($partTimerInfo['hourly_rate'] ?? 0) ?>/giờ</div></div>
                <div class="profile-item"><div class="k">Tối đa mỗi tuần</div><div class="v"><?= (int)($partTimerInfo['max_hours_per_week'] ?? 0) ?> giờ</div></div>
            <?php endif; ?>
        </div>
    </section>

    <form class="filter-bar" method="GET">
        <input type="hidden" name="page" value="my-pay-and-attendance">
        <?php if ($canViewAllEmployees): ?>
            <input type="hidden" name="employee_id" value="<?= (int)$selectedEmployeeId ?>">
        <?php endif; ?>
        <label>Tháng</label>
        <select name="month">
            <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $m===$month?'selected':'' ?>>Tháng <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?></option>
            <?php endfor; ?>
        </select>
        <label>Năm</label>
        <select name="year">
            <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
                <option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button class="btn btn-primary" type="submit">Xem</button>
        <a class="btn btn-soft" href="?page=my-pay-and-attendance<?= $canViewAllEmployees ? '&employee_id=' . (int)$selectedEmployeeId : '' ?>">Tháng hiện tại</a>
    </form>

    <div class="summary-grid">
        <div class="sc green">
            <div class="k"><?= $partTimerInfo ? 'Lương part-time' : 'Lương thực nhận' ?></div>
            <div class="v"><?= myPayFmtMoney($partTimerInfo ? ($summary['part_time_amount'] ?? 0) : ($summary['total_net'] ?? 0)) ?></div>
        </div>
        <div class="sc teal">
            <div class="k">Giờ chấm công</div>
            <div class="v"><?= number_format((float)($summary['total_hours'] ?? 0), 1) ?> giờ</div>
        </div>
        <div class="sc orange">
            <div class="k">Giờ ca dự kiến</div>
            <div class="v"><?= number_format((float)($summary['total_scheduled_hours'] ?? 0), 1) ?> giờ</div>
        </div>
        <div class="sc">
            <div class="k">Ngày có lịch</div>
            <div class="v"><?= (int)($summary['scheduled_days'] ?? 0) ?> ngày</div>
        </div>
        <div class="sc green">
            <div class="k">Ca đã đi làm</div>
            <div class="v"><?= (int)($summary['worked_shift_days'] ?? 0) ?> ca</div>
        </div>
    </div>

    <?php if (!$canViewAllEmployees && $selectedEmployeeId): ?>
    <div class="attendance-actions">
        <div class="attendance-box">
            <h3>Chấm công trên web hôm nay</h3>
            <div class="attendance-meta">
                <div><span>Ngày</span><strong><?= date('d/m/Y') ?></strong></div>
                <div><span>Giờ vào</span><strong><?= myPayFmtTime($todayAttendance['check_in'] ?? '') ?></strong></div>
                <div><span>Giờ ra</span><strong><?= myPayFmtTime($todayAttendance['check_out'] ?? '') ?></strong></div>
                <div><span>Giờ công</span><strong><?= number_format((float)($todayAttendance['working_hours'] ?? 0), 1) ?> giờ</strong></div>
            </div>
            <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn btn-primary" type="submit" name="action" value="web_check_in">Chấm vào</button>
                <button class="btn btn-soft" type="submit" name="action" value="web_check_out">Chấm ra</button>
            </form>
        </div>

        <div class="attendance-box">
            <h3>Gửi yêu cầu quên chấm công</h3>
            <form class="inline-form" method="POST">
                <input type="hidden" name="action" value="request_attendance">
                <label>Ngày
                    <input type="date" name="attendance_date" value="<?= date('Y-m-d') ?>" required>
                </label>
                <label>Giờ vào
                    <input type="time" name="requested_check_in" required>
                </label>
                <label>Giờ ra
                    <input type="time" name="requested_check_out" required>
                </label>
                <label style="grid-column:1/-1;">Lý do
                    <textarea name="reason" placeholder="Ví dụ: quên chấm công tại máy ở công ty" required></textarea>
                </label>
                <button class="btn btn-primary" type="submit">Gửi yêu cầu duyệt</button>
            </form>
        </div>
    </div>

    <div class="card full" style="margin-bottom:18px;">
        <div class="card-hd">
            <h3>Yêu cầu chấm công đã gửi</h3>
            <span class="badge"><?= count($attendanceRequestRows) ?> đơn gần nhất</span>
        </div>
        <?php if (empty($attendanceRequestRows)): ?>
            <div class="empty"><div class="i"><i class="ui-icon icon-calendar" aria-hidden="true"></i></div><div>Chưa có yêu cầu chấm công nào</div></div>
        <?php else: ?>
            <div style="overflow:auto;">
                <table>
                    <thead><tr><th>Ngày</th><th>Giờ đề nghị</th><th>Lý do</th><th>Trạng thái</th><th>Phản hồi</th></tr></thead>
                    <tbody>
                    <?php foreach ($attendanceRequestRows as $req): ?>
                        <?php $reqClass = strtolower($req['status']) === 'approved' ? 'ok' : (strtolower($req['status']) === 'rejected' ? 'bad' : 'warn'); ?>
                        <tr>
                            <td><?= myPayFmtDate($req['attendance_date'] ?? '') ?></td>
                            <td><span class="time-chip"><?= myPayFmtTime($req['requested_check_in'] ?? '') ?> → <?= myPayFmtTime($req['requested_check_out'] ?? '') ?></span></td>
                            <td><?= htmlspecialchars($req['reason'] ?? '-') ?></td>
                            <td><span class="chip <?= $reqClass ?>"><?= htmlspecialchars($req['status'] ?? '-') ?></span></td>
                            <td><?= htmlspecialchars($req['admin_note'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="section-grid">
        <div class="card">
            <div class="card-hd">
                <h3>Phòng ban của tôi</h3>
                <span class="badge"><?= htmlspecialchars($employee['department'] ?? '-') ?></span>
            </div>
            <div class="profile-grid" style="padding:16px;">
                <div class="profile-item"><div class="k">Phòng ban</div><div class="v"><?= htmlspecialchars($employee['department'] ?? '-') ?></div></div>
                <div class="profile-item"><div class="k">Chức vụ</div><div class="v"><?= htmlspecialchars($employee['position'] ?? '-') ?></div></div>
                <div class="profile-item"><div class="k">Ngày vào làm</div><div class="v"><?= myPayFmtDate($employee['hire_date'] ?? '') ?></div></div>
                <div class="profile-item"><div class="k">Trạng thái</div><div class="v"><?= htmlspecialchars($employee['status'] ?? '-') ?></div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-hd">
                <h3>Ca làm việc đang áp dụng</h3>
                <span class="badge"><?= count($activeShiftRows) ?> ca</span>
            </div>
            <?php if (empty($activeShiftRows)): ?>
                <div class="empty"><div class="i"><i class="ui-icon icon-shifts" aria-hidden="true"></i></div><div>Chưa có ca làm việc đang áp dụng</div></div>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Tên ca</th>
                                <th>Giờ làm</th>
                                <th>Từ ngày</th>
                                <th>Đến ngày</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($activeShiftRows as $shift): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($shift['shift_name'] ?? '-') ?></strong></td>
                                <td><span class="time-chip"><?= myPayFmtTime($shift['start_time'] ?? '') ?> → <?= myPayFmtTime($shift['end_time'] ?? '') ?></span></td>
                                <td><?= myPayFmtDate($shift['start_date'] ?? '') ?></td>
                                <td><?= myPayFmtDate($shift['end_date'] ?? '') ?></td>
                                <td><span class="chip <?= myPayStatusClass($shift['status'] ?? '') ?>"><?= htmlspecialchars($shift['status'] ?? '-') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card full">
            <div class="card-hd">
                <h3>Hợp đồng của tôi</h3>
                <span class="badge"><?= count($contractRows) ?> hợp đồng</span>
            </div>
            <?php if (empty($contractRows)): ?>
                <div class="empty"><div class="i"></div><div>Chưa có dữ liệu hợp đồng</div></div>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Số hợp đồng</th>
                                <th>Loại hợp đồng</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th class="right">Mức lương</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($contractRows as $contract): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($contract['contract_number'] ?? '-') ?></strong></td>
                                <td><?= htmlspecialchars($contract['contract_type'] ?? '-') ?></td>
                                <td><?= myPayFmtDate($contract['start_date'] ?? '') ?></td>
                                <td><?= myPayFmtDate($contract['end_date'] ?? '') ?></td>
                                <td class="right"><?= myPayFmtMoney($contract['salary'] ?? 0) ?></td>
                                <td><span class="chip <?= myPayContractStatusClass($contract['status'] ?? '') ?>"><?= htmlspecialchars($contract['status'] ?? '-') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card full">
            <div class="card-hd">
                <h3>Công ca đi làm tháng <?= (int)$month ?>/<?= (int)$year ?></h3>
                <span class="badge"><?= count($workedShiftRows) ?> dòng</span>
            </div>
            <?php if (empty($workedShiftRows)): ?>
                <div class="empty"><div class="i">⏱</div><div>Chưa có dữ liệu công ca trong tháng này</div></div>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Ca làm</th>
                                <th>Giờ ca</th>
                                <th>Vào / ra</th>
                                <th class="right">Giờ ca</th>
                                <th class="right">Giờ thực tế</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($workedShiftRows as $row): ?>
                            <?php $workStatus = myPayWorkShiftStatus($row); ?>
                            <tr>
                                <td><?= myPayFmtDate($row['work_date'] ?? '') ?></td>
                                <td><strong><?= htmlspecialchars($row['shift_name'] ?? '-') ?></strong></td>
                                <td><span class="time-chip"><?= myPayFmtTime($row['start_time'] ?? '') ?> → <?= myPayFmtTime($row['end_time'] ?? '') ?></span></td>
                                <td><span class="time-chip"><?= myPayFmtTime($row['check_in_time'] ?? '') ?> → <?= myPayFmtTime($row['check_out_time'] ?? '') ?></span></td>
                                <td class="right"><?= number_format((float)($row['planned_hours'] ?? 0), 1) ?></td>
                                <td class="right"><strong><?= number_format((float)($row['actual_hours'] ?? 0), 1) ?></strong></td>
                                <td><span class="chip <?= $workStatus['class'] ?>"><?= htmlspecialchars($workStatus['label']) ?></span></td>
                                <td><?= htmlspecialchars($row['attendance_notes'] ?: ($row['schedule_notes'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-hd">
                <h3>Ca làm đã được sắp trong tháng</h3>
                <span class="badge"><?= count($plannedShiftRows) ?> ca</span>
            </div>
            <?php if (empty($plannedShiftRows)): ?>
                <div class="empty"><div class="i"><i class="ui-icon icon-shifts" aria-hidden="true"></i></div><div>Chưa có ca làm được sắp trong tháng này</div></div>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table>
                        <thead><tr><th>Tên ca</th><th>Giờ làm</th><th>Số ngày</th><th>Khoảng ngày</th></tr></thead>
                        <tbody>
                        <?php foreach($plannedShiftRows as $shift): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($shift['shift_name'] ?? '-') ?></strong></td>
                                <td><span class="time-chip"><?= myPayFmtTime($shift['start_time'] ?? '') ?> → <?= myPayFmtTime($shift['end_time'] ?? '') ?></span></td>
                                <td><strong><?= (int)($shift['scheduled_days'] ?? 0) ?></strong></td>
                                <td><?= myPayFmtDate($shift['first_date'] ?? '') ?> - <?= myPayFmtDate($shift['last_date'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-hd">
                <h3>Chấm công tháng <?= (int)$month ?>/<?= (int)$year ?></h3>
                <span class="badge"><?= count($attendanceRows) ?> ngày</span>
            </div>
            <?php if (empty($attendanceRows)): ?>
                <div class="empty"><div class="i"></div><div>Chưa có dữ liệu chấm công trong tháng này</div></div>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table>
                        <thead><tr><th>Ngày</th><th class="right">Giờ công</th></tr></thead>
                        <tbody>
                        <?php foreach($attendanceRows as $ar): ?>
                            <tr>
                                <td><?= myPayFmtDate($ar['work_date'] ?? '') ?></td>
                                <td class="right"><strong><?= number_format((float)($ar['hours_worked'] ?? 0), 1) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card full">
            <div class="card-hd">
                <h3>Lịch làm việc đã được sắp tháng <?= (int)$month ?>/<?= (int)$year ?></h3>
                <span class="badge"><?= count($scheduleRows) ?> lịch</span>
            </div>
            <?php if (empty($scheduleRows)): ?>
                <div class="empty"><div class="i"><i class="ui-icon icon-calendar" aria-hidden="true"></i></div><div>Chưa có lịch ca làm trong tháng này</div></div>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Ca làm</th>
                                <th>Giờ làm</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($scheduleRows as $row): ?>
                            <tr>
                                <td><?= myPayFmtDate($row['schedule_date'] ?? '') ?></td>
                                <td><strong><?= htmlspecialchars($row['shift_name'] ?? '-') ?></strong></td>
                                <td><span class="time-chip"><?= myPayFmtTime($row['start_time'] ?? '') ?> → <?= myPayFmtTime($row['end_time'] ?? '') ?></span></td>
                                <td><span class="chip <?= myPayStatusClass($row['status'] ?? '') ?>"><?= htmlspecialchars(myPayScheduleStatus($row['status'] ?? '')) ?></span></td>
                                <td><?= htmlspecialchars($row['notes'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($partTimerInfo): ?>
        <div class="card full">
            <div class="card-hd">
                <h3>Giờ công và lương part-time tháng <?= (int)$month ?>/<?= (int)$year ?></h3>
                <span class="badge"><?= count($partTimeRows) ?> ngày</span>
            </div>
            <?php if (empty($partTimeRows)): ?>
                <div class="empty"><div class="i"></div><div>Chưa có giờ công part-time trong tháng này</div></div>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Ngày làm</th>
                                <th class="right">Giờ tính lương</th>
                                <th class="right">Lương/giờ</th>
                                <th class="right">Thành tiền</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($partTimeRows as $row): ?>
                            <tr>
                                <td><?= myPayFmtDate($row['work_date'] ?? '') ?></td>
                                <td class="right"><strong><?= number_format((float)($row['hours'] ?? 0), 1) ?></strong></td>
                                <td class="right"><?= myPayFmtMoney($row['hourly_rate'] ?? 0) ?></td>
                                <td class="right"><strong><?= myPayFmtMoney($row['amount'] ?? 0) ?></strong></td>
                                <td><?= htmlspecialchars($row['notes'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Tổng</th>
                                <th class="right"><?= number_format((float)($partTimeSummary['total_hours'] ?? 0), 1) ?> giờ</th>
                                <th></th>
                                <th class="right"><?= myPayFmtMoney($partTimeSummary['total_amount'] ?? 0) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card full">
            <div class="card-hd">
                <h3>Bảng lương tháng <?= (int)$month ?>/<?= (int)$year ?></h3>
                <span class="badge"><?= count($salaryRows) ?> bảng</span>
            </div>
            <?php if (empty($salaryRows)): ?>
                <div class="empty"><div class="i"></div><div>Chưa có dữ liệu lương trong tháng này</div></div>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Tháng/Năm</th>
                                <th class="right">Lương CB</th>
                                <th class="right">Phụ cấp</th>
                                <th class="right">Khấu trừ</th>
                                <th class="right">Bảo hiểm</th>
                                <th>Ngày thanh toán</th>
                                <th class="right">Thực lĩnh</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($salaryRows as $r): ?>
                            <tr>
                                <td><?= str_pad((int)$r['month'],2,'0',STR_PAD_LEFT) ?>/<?= (int)$r['year'] ?></td>
                                <td class="right"><?= myPayFmtMoney($r['base_salary'] ?? 0) ?></td>
                                <td class="right">+<?= myPayFmtMoney($r['allowances'] ?? 0) ?></td>
                                <td class="right">-<?= myPayFmtMoney($r['deductions'] ?? 0) ?></td>
                                <td class="right">-<?= myPayFmtMoney($r['insurance'] ?? 0) ?></td>
                                <td><?= myPayFmtDate($r['payment_date'] ?? '') ?></td>
                                <td class="right"><strong><?= myPayFmtMoney($r['net_salary'] ?? 0) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <a class="btn btn-soft back" href="?page=dashboard">← Quay lại</a>
</div>
<script>
function toggleSidebar() {
    document.getElementById('sidebar')?.classList.toggle('show');
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}

window.addEventListener('load', function () {
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
});
</script>
</body>
</html>



