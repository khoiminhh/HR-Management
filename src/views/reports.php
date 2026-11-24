<?php
/**
 * ========================================
 * View: Báo cáo Dashboard
 * ========================================
 */

require_once '../src/controllers/ReportController.php';

$controller = new ReportController($pdo);
$reportType = $_GET['type'] ?? 'dashboard';

$data = [];
if ($reportType === 'dashboard') {
    $data = $controller->getDashboard();
} elseif ($reportType === 'department') {
    $data = $controller->getDepartmentReport();
} elseif ($reportType === 'contract') {
    $data = $controller->getContractReport();
} elseif ($reportType === 'salary') {
    $month = $_GET['month'] ?? (int)date('m');
    $year = $_GET['year'] ?? (int)date('Y');
    $data = $controller->getSalaryReport($month, $year);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo và Thống kê</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .nav {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .nav a {
            color: #667eea;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav a:hover {
            background-color: #f0f0f0;
        }
        
        .nav a.active {
            background-color: #667eea;
            color: white;
        }
        
        .report-nav {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .report-nav a {
            color: #667eea;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .report-nav a:hover,
        .report-nav a.active {
            background-color: #667eea;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
            text-align: center;
        }
        
        .stat-card.active {
            border-left-color: #28a745;
        }
        
        .stat-card.inactive {
            border-left-color: #dc3545;
        }
        
        .stat-card.on-leave {
            border-left-color: #ffc107;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .chart-container h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        table thead {
            background-color: #667eea;
            color: white;
        }
        
        table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        
        table tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .amount {
            text-align: right;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-expired {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .section h2 {
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            padding: 8px 16px;
            background-color: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: #5568d3;
        }
        
        .btn-print {
            background-color: #6c757d;
        }
        
        .btn-print:hover {
            background-color: #5a6268;
        }
        
        @media print {
            .nav, .report-nav, .filter-group, .btn {
                display: none;
            }

            /* Nút quay lại hiện đại */
.btn-return {
    display: inline-flex;
    align-items: center;
    padding: 10px 18px;
    background-color: #ffffff;
    color: #6f42c1; /* Màu tím chủ đạo */
    border: 1px solid #6f42c1;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    margin-bottom: 20px;
    transition: 0.3s;
}

.btn-return:hover {
    background-color: #6f42c1;
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(111, 66, 193, 0.3);
}
        }

        :root {
            --ui-primary: #2563eb;
            --ui-primary-dark: #1e40af;
            --ui-ink: #111827;
            --ui-text: #334155;
            --ui-muted: #64748b;
            --ui-bg: #eef2f7;
            --ui-border: #e2e8f0;
            --ui-success: #16a34a;
            --ui-danger: #dc2626;
            --ui-warning: #d97706;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(180deg, #f8fafc 0, var(--ui-bg) 360px);
            color: var(--ui-text);
        }

        .container {
            width: min(1530px, calc(100% - 48px));
            max-width: none;
            padding: 22px 0 42px;
        }

        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            border-radius: 10px;
            padding: 34px 40px;
            margin-bottom: 24px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .16);
        }

        .header h1 {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: 0;
            margin-bottom: 8px;
        }

        .header p {
            color: #bfdbfe;
            font-size: 15px;
        }

        .nav,
        .report-nav {
            background: rgba(255, 255, 255, .97);
            border: 1px solid var(--ui-border);
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
            align-items: center;
        }

        .nav {
            padding: 12px 18px;
            gap: 8px;
            margin-bottom: 20px;
        }

        .report-nav {
            padding: 16px 20px;
            gap: 12px;
            margin-bottom: 22px;
        }

        .nav a,
        .report-nav a,
        .btn-back {
            border-radius: 8px;
            color: #1d4ed8;
            font-weight: 650;
            text-decoration: none;
            transition: .18s ease;
        }

        .nav a {
            padding: 10px 14px;
            font-size: 14px;
        }

        .report-nav a {
            padding: 12px 18px;
            font-size: 15px;
        }

        .nav a:hover,
        .report-nav a:hover {
            background: #eff6ff;
            color: var(--ui-primary-dark);
        }

        .nav a.active,
        .report-nav a.active {
            background: var(--ui-primary);
            color: white;
            box-shadow: 0 10px 20px rgba(37, 99, 235, .22);
        }

        .btn-back {
            margin-left: auto;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px solid var(--ui-border);
            color: #475569 !important;
        }

        .btn-back:hover {
            background: #e2e8f0;
            color: #0f172a !important;
        }

        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .stat-card {
            padding: 22px 24px;
            border-radius: 10px;
            border: 1px solid var(--ui-border);
            border-left: 5px solid var(--ui-primary);
            box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
            min-height: 126px;
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            right: -58px;
            top: -56px;
            border-radius: 50%;
            background: rgba(37, 99, 235, .08);
        }

        .stat-card.active { border-left-color: var(--ui-success); }
        .stat-card.active::after { background: rgba(22, 163, 74, .1); }
        .stat-card.inactive { border-left-color: var(--ui-danger); }
        .stat-card.inactive::after { background: rgba(220, 38, 38, .1); }
        .stat-card.on-leave { border-left-color: var(--ui-warning); }
        .stat-card.on-leave::after { background: rgba(217, 119, 6, .12); }

        .stat-label {
            color: var(--ui-muted);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 900;
            color: var(--ui-primary);
            line-height: 1;
            margin: 14px 0 0;
        }

        .stat-card.active .stat-value { color: var(--ui-success); }
        .stat-card.inactive .stat-value { color: var(--ui-danger); }
        .stat-card.on-leave .stat-value { color: var(--ui-warning); }

        .chart-container,
        .section {
            border: 1px solid var(--ui-border);
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
            padding: 26px 28px;
            margin-bottom: 24px;
        }

        .chart-container h3,
        .section h2 {
            color: var(--ui-ink);
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .section h2 {
            border-bottom: 1px solid var(--ui-border);
            padding-bottom: 14px;
        }

        .chart-wrapper {
            height: 320px;
            max-width: 760px;
            margin: 0 auto;
        }

        .section .chart-wrapper {
            max-width: 100%;
        }

        table {
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--ui-border);
            border-radius: 10px;
            box-shadow: none;
            margin-bottom: 24px;
        }

        table thead {
            background: #0f172a;
        }

        table th {
            padding: 14px 16px;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }

        table td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--ui-border);
        }

        table tbody tr:hover {
            background: #f8fafc;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        .amount {
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
        }

        .filter-group {
            display: block;
            margin-bottom: 22px;
        }

        .filter-group form {
            display: flex !important;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            background: #f8fafc;
            border: 1px solid var(--ui-border);
            border-radius: 10px;
            padding: 12px;
        }

        .filter-group select {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: white;
            font-size: 14px;
        }

        .btn {
            padding: 10px 16px;
            background: var(--ui-primary);
            border-radius: 8px;
            font-weight: 800;
            transition: .18s ease;
        }

        .btn:hover {
            background: var(--ui-primary-dark);
        }

        .btn-print {
            background: #475569;
        }

        .btn-print:hover {
            background: #334155;
        }

        @media (max-width: 768px) {
            .container {
                width: min(100% - 24px, 1530px);
                padding-top: 12px;
            }

            .header {
                padding: 24px;
            }

            .header h1 {
                font-size: 24px;
            }

            .nav,
            .report-nav {
                padding: 10px;
            }

            .nav a,
            .report-nav a,
            .btn-back {
                width: 100%;
                margin-left: 0;
            }

            .chart-container,
            .section {
                padding: 18px;
            }

            .chart-wrapper {
                height: 260px;
            }
        }
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Báo cáo và Thống kê</h1>
            <p>Tổng hợp dữ liệu về nhân viên, hợp đồng, lương và các chỉ tiêu khác</p>
        </div>
        
        <!-- Navigation -->
        <div class="nav">
            <a href="?page=employees">Danh sách nhân viên</a>
            <a href="?page=contracts">Hợp đồng</a>
            <a href="?page=salaries">Lương</a>
            <a href="?page=reports" class="active">Báo cáo</a>
            <!-- Nút Quay lại Dashboard -->
<a href="index.php" class="btn-back">
    <i class="fas fa-arrow-left"></i> Quay lại Dashboard
</a>
        </div>
        
        <!-- Report Navigation -->
        <div class="report-nav">
            <a href="?page=reports&type=dashboard" class="<?= ($reportType === 'dashboard') ? 'active' : '' ?>"><i class="ui-icon icon-salary-stats" aria-hidden="true"></i> Tổng quan</a>
            <a href="?page=reports&type=department" class="<?= ($reportType === 'department') ? 'active' : '' ?>"><i class="ui-icon icon-departments" aria-hidden="true"></i> Phòng ban</a>
            <a href="?page=reports&type=contract" class="<?= ($reportType === 'contract') ? 'active' : '' ?>"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Hợp đồng</a>
            <a href="?page=reports&type=salary" class="<?= ($reportType === 'salary') ? 'active' : '' ?>"><i class="ui-icon icon-salaries" aria-hidden="true"></i> Lương</a>
        </div>
        
        <!-- Dashboard Report -->
        <?php if ($reportType === 'dashboard'): ?>
            <!-- Employee Stats -->
            <div class="stats-grid">
                <?php foreach ($data['employeeStats'] as $stat): ?>
                    <?php $statusClass = strtolower(str_replace(' ', '-', $stat['status'])); ?>
                    <div class="stat-card <?= htmlspecialchars($statusClass) ?>">
                        <div class="stat-label"><i class="ui-icon icon-employees" aria-hidden="true"></i> <?= htmlspecialchars($stat['status']) ?></div>
                        <div class="stat-value"><?= $stat['count'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Charts -->
            <div class="chart-container">
                <h3><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Thống kê nhân viên theo trạng thái</h3>
                <div class="chart-wrapper">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            
            <!-- Expiring Contracts -->
            <?php if (count($data['expiringContracts']) > 0): ?>
                <div class="section">
                    <h2>Hợp đồng sắp hết hạn (30 ngày tới)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Chức vụ</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Mức lương</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['expiringContracts'] as $contract): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($contract['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($contract['position']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($contract['start_date'])) ?></td>
                                    <td style="color: #dc3545; font-weight: bold;"><?= date('d/m/Y', strtotime($contract['end_date'])) ?></td>
                                    <td class="amount"><?= number_format($contract['salary'], 0, ',', '.') ?> đ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- New Employees -->
            <?php if (count($data['newEmployees']) > 0): ?>
                <div class="section">
                    <h2>Nhân viên mới (3 tháng gần đây)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Họ và tên</th>
                                <th>Mã NV</th>
                                <th>Chức vụ</th>
                                <th>Phòng ban</th>
                                <th>Ngày vào công ty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['newEmployees'] as $emp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($emp['full_name']) ?></td>
                                    <td><?= htmlspecialchars($emp['employee_code']) ?></td>
                                    <td><?= htmlspecialchars($emp['position']) ?></td>
                                    <td><?= htmlspecialchars($emp['department']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($emp['hire_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        
        <!-- Department Report -->
        <?php elseif ($reportType === 'department'): ?>
            <div class="section">
                <h2><i class="ui-icon icon-departments" aria-hidden="true"></i> Thống kê nhân viên theo phòng ban</h2>
                <div class="chart-wrapper" style="height: 300px; margin-bottom: 30px;">
                    <canvas id="departmentChart"></canvas>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Phòng ban</th>
                            <th class="amount">Số nhân viên</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['departmentStats'] as $dept): ?>
                            <tr>
                                <td><?= htmlspecialchars($dept['department']) ?></td>
                                <td class="amount"><?= $dept['count'] ?> người</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h2> Thống kê nhân viên theo chức vụ</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Chức vụ</th>
                            <th class="amount">Số nhân viên</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['positionStats'] as $pos): ?>
                            <tr>
                                <td><?= htmlspecialchars($pos['position']) ?></td>
                                <td class="amount"><?= $pos['count'] ?> người</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        
        <!-- Contract Report -->
        <?php elseif ($reportType === 'contract'): ?>
            <div class="stats-grid">
                <?php foreach ($data['contractStats'] as $stat): ?>
                    <div class="stat-card">
                        <div class="stat-label"><?= htmlspecialchars($stat['status']) ?></div>
                        <div class="stat-value"><?= $stat['count'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Expiring Contracts -->
            <?php if (count($data['expiringContracts']) > 0): ?>
                <div class="section">
                    <h2>Hợp đồng sắp hết hạn (90 ngày tới)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Chức vụ</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Mức lương</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['expiringContracts'] as $contract): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($contract['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($contract['position']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($contract['start_date'])) ?></td>
                                    <td style="color: #dc3545; font-weight: bold;"><?= date('d/m/Y', strtotime($contract['end_date'])) ?></td>
                                    <td class="amount"><?= number_format($contract['salary'], 0, ',', '.') ?> đ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Expired Contracts -->
            <?php if (count($data['expiredContracts']) > 0): ?>
                <div class="section">
                    <h2>Hợp đồng đã hết hạn</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Chức vụ</th>
                                <th>Ngày kết thúc</th>
                                <th>Mức lương</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['expiredContracts'] as $contract): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($contract['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($contract['position']) ?></td>
                                    <td style="color: #dc3545;"><?= date('d/m/Y', strtotime($contract['end_date'])) ?></td>
                                    <td class="amount"><?= number_format($contract['salary'], 0, ',', '.') ?> đ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        
        <!-- Salary Report -->
        <?php elseif ($reportType === 'salary'): ?>
            <div class="section">
                <h2><i class="ui-icon icon-salaries" aria-hidden="true"></i> Báo cáo lương tháng <?= str_pad($data['month'], 2, '0', STR_PAD_LEFT) ?>/<?= $data['year'] ?></h2>
                
                <div class="filter-group">
                    <form method="GET" style="display: flex; gap: 10px;">
                        <input type="hidden" name="page" value="reports">
                        <input type="hidden" name="type" value="salary">
                        <select name="month">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= ($data['month'] == $m) ? 'selected' : '' ?>>
                                    Tháng <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select name="year">
                            <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
                                <option value="<?= $y ?>" <?= ($data['year'] == $y) ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="btn">Tìm kiếm</button>
                        <button type="button" onclick="window.print()" class="btn btn-print">️ In báo cáo</button>
                    </form>
                </div>
                
                <!-- Summary -->
                <?php if ($data['salarySummary']): ?>
                    <div style="background-color: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin-bottom: 15px;">Tóm tắt</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div>
                                <strong>Số nhân viên:</strong> <?= $data['salarySummary']['total_employees'] ?> người
                            </div>
                            <div>
                                <strong>Tổng lương cơ bản:</strong> <?= number_format($data['salarySummary']['total_base_salary'], 0, ',', '.') ?> đ
                            </div>
                            <div>
                                <strong>Tổng lương ròng:</strong> <?= number_format($data['salarySummary']['total_net_salary'], 0, ',', '.') ?> đ
                            </div>
                            <div>
                                <strong>Trung bình lương ròng:</strong> <?= number_format($data['salarySummary']['avg_net_salary'], 0, ',', '.') ?> đ
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Salary by Department -->
                <?php if (count($data['salaryByDepartment']) > 0): ?>
                    <h3 style="margin-bottom: 15px; margin-top: 30px;">Lương theo phòng ban</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Phòng ban</th>
                                <th class="amount">Số nhân viên</th>
                                <th class="amount">Tổng lương cơ bản</th>
                                <th class="amount">Tổng lương ròng</th>
                                <th class="amount">Trung bình lương</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['salaryByDepartment'] as $dept): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dept['department']) ?></td>
                                    <td class="amount"><?= $dept['total_employees'] ?></td>
                                    <td class="amount"><?= number_format($dept['total_base_salary'], 0, ',', '.') ?> đ</td>
                                    <td class="amount"><?= number_format($dept['total_net_salary'], 0, ',', '.') ?> đ</td>
                                    <td class="amount"><?= number_format($dept['avg_net_salary'], 0, ',', '.') ?> đ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <!-- Salary Details -->
                <?php if (count($data['salaryData']) > 0): ?>
                    <h3 style="margin-bottom: 15px; margin-top: 30px;">Chi tiết lương nhân viên</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Chức vụ</th>
                                <th class="amount">Lương cơ bản</th>
                                <th class="amount">Phụ cấp</th>
                                <th class="amount">Khấu trừ</th>
                                <th class="amount">Bảo hiểm</th>
                                <th class="amount">Lương ròng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['salaryData'] as $salary): ?>
                                <tr>
                                    <td><?= htmlspecialchars($salary['full_name']) ?></td>
                                    <td><?= htmlspecialchars($salary['position']) ?></td>
                                    <td class="amount"><?= number_format($salary['base_salary'], 0, ',', '.') ?> đ</td>
                                    <td class="amount"><?= number_format($salary['allowances'], 0, ',', '.') ?> đ</td>
                                    <td class="amount"><?= number_format($salary['deductions'], 0, ',', '.') ?> đ</td>
                                    <td class="amount"><?= number_format($salary['insurance'], 0, ',', '.') ?> đ</td>
                                    <td class="amount" style="background-color: #f0f0f0; font-weight: bold;">
                                        <?= number_format($salary['net_salary'], 0, ',', '.') ?> đ
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Chart.js Scripts -->
    <script>
        // Status Chart
        <?php if ($reportType === 'dashboard' && !empty($data['employeeStats'])): ?>
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                const statusLabels = [<?php foreach ($data['employeeStats'] as $stat) echo "'" . htmlspecialchars($stat['status']) . "',"; ?>];
                const statusData = [<?php foreach ($data['employeeStats'] as $stat) echo $stat['count'] . ','; ?>];
                
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusData,
                            backgroundColor: [
                                '#28a745',
                                '#dc3545',
                                '#ffc107'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        <?php endif; ?>
        
        // Department Chart
        <?php if ($reportType === 'department' && !empty($data['departmentStats'])): ?>
            const deptCtx = document.getElementById('departmentChart');
            if (deptCtx) {
                const deptLabels = [<?php foreach ($data['departmentStats'] as $dept) echo "'" . htmlspecialchars($dept['department']) . "',"; ?>];
                const deptData = [<?php foreach ($data['departmentStats'] as $dept) echo $dept['count'] . ','; ?>];
                
                new Chart(deptCtx, {
                    type: 'bar',
                    data: {
                        labels: deptLabels,
                        datasets: [{
                            label: 'Số nhân viên',
                            data: deptData,
                            backgroundColor: '#667eea'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        <?php endif; ?>
    </script>
</body>
</html>


