<?php
/**
 * ========================================
 * View: Thống kê lương Full-time & Part-time
 * Nhận từ SalaryStatsController:
 *   $filter, $date, $month, $year, $week
 *   $fulltime['rows'], $fulltime['summary']
 *   $parttime['rows'], $parttime['summary']
 *   $chartData['labels'], $chartData['ftData'], $chartData['ptData']
 * ========================================
 */
$filter = $_GET['filter'] ?? 'month';
$date   = $_GET['date']   ?? date('Y-m-d');
$month  = $_GET['month']  ?? date('m');
$year   = $_GET['year']   ?? date('Y');
$week   = $_GET['week']   ?? date('W');

$filterLabels = ['day' => 'Ngày', 'week' => 'Tuần', 'month' => 'Tháng', 'year' => 'Năm'];

function fmtMoney($n) {
    return number_format($n, 0, ',', '.') . ' đ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê lương</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        /* Header */
        .header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
                  color: white; padding: 30px; border-radius: 12px; margin-bottom: 24px;
                  display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header h1 { font-size: 26px; font-weight: 700; }
        .header p  { opacity: 0.75; font-size: 14px; margin-top: 4px; }

        /* Nav */
        .nav { background: white; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px;
               box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 8px; flex-wrap: wrap; }
        .nav a { color: #0f3460; text-decoration: none; padding: 8px 14px; border-radius: 6px;
                 font-size: 14px; transition: all 0.2s; }
        .nav a:hover { background: #0f3460; color: white; }

        /* Filter bar */
        .filter-bar { background: white; padding: 16px 20px; border-radius: 10px; margin-bottom: 20px;
                      box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .filter-tabs { display: flex; gap: 6px; }
        .filter-tab { padding: 8px 18px; border: 2px solid #e0e0e0; border-radius: 20px; background: white;
                      cursor: pointer; font-size: 13px; font-weight: 600; color: #666; text-decoration: none;
                      transition: all 0.2s; }
        .filter-tab.active { background: #0f3460; color: white; border-color: #0f3460; }
        .filter-tab:hover:not(.active) { border-color: #0f3460; color: #0f3460; }
        .filter-inputs { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .filter-inputs input, .filter-inputs select { padding: 8px 12px; border: 1px solid #ddd;
            border-radius: 6px; font-size: 14px; }
        .btn-filter { padding: 9px 20px; background: #0f3460; color: white; border: none;
                      border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }

        /* Summary cards */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 16px; margin-bottom: 24px; }
        .summary-card { background: white; border-radius: 10px; padding: 20px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #ccc; }
        .summary-card.blue   { border-left-color: #0f3460; }
        .summary-card.green  { border-left-color: #28a745; }
        .summary-card.orange { border-left-color: #fd7e14; }
        .summary-card.purple { border-left-color: #6f42c1; }
        .summary-card.teal   { border-left-color: #20c997; }
        .summary-card .label { font-size: 12px; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-card .value { font-size: 22px; font-weight: 700; color: #1a1a2e; margin-top: 6px; }
        .summary-card .sub   { font-size: 12px; color: #aaa; margin-top: 4px; }

        /* Chart */
        .chart-section { background: white; border-radius: 10px; padding: 24px;
                         box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .chart-section h3 { font-size: 16px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px; }
        .chart-wrapper { position: relative; height: 300px; }

        /* Tables */
        .table-section { background: white; border-radius: 10px; padding: 24px;
                         box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .table-section h3 { font-size: 16px; font-weight: 700; color: #1a1a2e; margin-bottom: 16px;
                            display: flex; align-items: center; gap: 8px; }
        .badge { padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-blue  { background: #e8f0fe; color: #0f3460; }
        .badge-green { background: #e8f5e9; color: #28a745; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        table thead { background: #f8f9fa; }
        table th { padding: 12px 14px; text-align: left; font-weight: 600; color: #555;
                   border-bottom: 2px solid #e9ecef; white-space: nowrap; }
        table td { padding: 11px 14px; border-bottom: 1px solid #f0f0f0; white-space: nowrap; }
        table tbody tr:hover { background: #f8f9fc; }
        table tfoot td { background: #f0f4ff; font-weight: 700; padding: 12px 14px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .money { text-align: right; font-weight: 600; color: #0f3460; }
        .money-green { text-align: right; font-weight: 600; color: #28a745; }
        .empty-state { text-align: center; padding: 40px; color: #bbb; }
        .empty-state .icon { font-size: 40px; margin-bottom: 10px; }

        /* Tabs */
        .section-tabs { display: flex; gap: 0; margin-bottom: 20px; border-radius: 10px; overflow: hidden;
                        border: 1px solid #e0e0e0; }
        .section-tab { flex: 1; padding: 12px; text-align: center; font-weight: 600; font-size: 14px;
                       cursor: pointer; background: white; color: #666; border: none; transition: all 0.2s; }
        .section-tab.active { background: #0f3460; color: white; }

        @media (max-width: 768px) {
            .summary-grid { grid-template-columns: 1fr 1fr; }
            .header { flex-direction: column; }
        }
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="container">

    <!-- Header -->
    <div class="header">
        <div>
            <h1><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Thống kê lương</h1>
            <p>Full-time & Part-time · Lọc theo <?= $filterLabels[$filter] ?></p>
        </div>
        <div style="font-size:13px; opacity:0.7;">
            Cập nhật: <?= date('d/m/Y H:i') ?>
        </div>
    </div>

    <!-- Nav -->
    <div class="nav">
        <a href="?page=dashboard">Trang chủ</a>
        <a href="?page=employees">Nhân viên</a>
        <a href="?page=salaries">Bảng lương</a>
        <a href="?page=part-timers">Part-time</a>
        <a href="?page=salary-stats" style="font-weight:700; color:#e63946;"><i class="ui-icon icon-salary-stats" aria-hidden="true"></i> Thống kê lương</a>
    </div>

    <!-- Filter bar -->
    <form method="GET" class="filter-bar">
        <input type="hidden" name="page" value="salary-stats">
        <div class="filter-tabs">
            <?php foreach ($filterLabels as $key => $label): ?>
                <a href="?page=salary-stats&filter=<?= $key ?>&month=<?= $month ?>&year=<?= $year ?>"
                   class="filter-tab <?= $filter === $key ? 'active' : '' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="filter" value="<?= $filter ?>">
        <div class="filter-inputs">
            <?php if ($filter === 'day'): ?>
                <label style="font-size:13px; font-weight:600;">Ngày:</label>
                <input type="date" name="date" value="<?= $date ?>">
            <?php elseif ($filter === 'week'): ?>
                <label style="font-size:13px; font-weight:600;">Tuần:</label>
                <input type="week" name="week_date" value="<?= $year ?>-W<?= str_pad($week, 2, '0', STR_PAD_LEFT) ?>">
                <input type="hidden" name="week"  value="<?= $week ?>">
                <input type="hidden" name="year"  value="<?= $year ?>">
            <?php elseif ($filter === 'month'): ?>
                <label style="font-size:13px; font-weight:600;">Tháng:</label>
                <select name="month">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year">
                    <?php for ($y = date('Y') - 3; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            <?php else: ?>
                <label style="font-size:13px; font-weight:600;">Năm:</label>
                <select name="year">
                    <?php for ($y = date('Y') - 3; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            <?php endif; ?>
            <button type="submit" class="btn-filter">Lọc</button>
        </div>
    </form>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card blue">
            <div class="label"> Nhân viên Full-time</div>
            <div class="value"><?= $fulltime['summary']['count'] ?></div>
            <div class="sub">người có bảng lương</div>
        </div>
        <div class="summary-card green">
            <div class="label"><i class="ui-icon icon-salaries" aria-hidden="true"></i> Tổng lương Full-time</div>
            <div class="value"><?= fmtMoney($fulltime['summary']['total_net']) ?></div>
            <div class="sub">Lương cơ bản: <?= fmtMoney($fulltime['summary']['total_base']) ?></div>
        </div>
        <div class="summary-card orange">
            <div class="label">⏱️ Nhân viên Part-time</div>
            <div class="value"><?= $parttime['summary']['count'] ?></div>
            <div class="sub">Tổng: <?= number_format($parttime['summary']['total_hours'], 1) ?> giờ</div>
        </div>
        <div class="summary-card purple">
            <div class="label"> Tổng lương Part-time</div>
            <div class="value"><?= fmtMoney($parttime['summary']['total_amount']) ?></div>
            <div class="sub">Theo giờ thực làm</div>
        </div>
        <div class="summary-card teal">
            <div class="label"><i class="ui-icon icon-salary-stats" aria-hidden="true"></i> Tổng chi lương</div>
            <div class="value"><?= fmtMoney($fulltime['summary']['total_net'] + $parttime['summary']['total_amount']) ?></div>
            <div class="sub">Full-time + Part-time</div>
        </div>
    </div>

    <!-- Chart -->
    <div class="chart-section">
        <h3><i class="ui-icon icon-salary-stats" aria-hidden="true"></i> Biểu đồ so sánh lương Full-time & Part-time</h3>
        <div class="chart-wrapper">
            <canvas id="salaryChart"></canvas>
        </div>
    </div>

    <!-- Full-time Table -->
    <div class="table-section">
        <h3> Bảng lương Full-time <span class="badge badge-blue"><?= $fulltime['summary']['count'] ?> nhân viên</span></h3>
        <div class="table-wrap">
            <?php if (empty($fulltime['rows'])): ?>
                <div class="empty-state">
                    <div class="icon"></div>
                    <p>Không có dữ liệu lương full-time trong kỳ này</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nhân viên</th>
                        <th>Mã NV</th>
                        <th>Phòng ban</th>
                        <th class="text-right">Lương cơ bản</th>
                        <th class="text-right">Phụ cấp</th>
                        <th class="text-right">Khấu trừ</th>
                        <th class="text-right">Bảo hiểm</th>
                        <th class="text-right">Thực lĩnh</th>
                        <?php if ($filter !== 'year'): ?>
                        <th>Ngày thanh toán</th>
                        <?php else: ?>
                        <th class="text-center">Số tháng</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fulltime['rows'] as $i => $row): ?>
                    <tr>
                        <td style="color:#aaa;"><?= $i + 1 ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($row['full_name']) ?></td>
                        <td style="color:#888;"><?= htmlspecialchars($row['employee_code']) ?></td>
                        <td><?= htmlspecialchars($row['department'] ?? '-') ?></td>
                        <td class="money"><?= fmtMoney($row['base_salary']) ?></td>
                        <td class="money-green">+<?= fmtMoney($row['allowances']) ?></td>
                        <td style="text-align:right; color:#dc3545;">-<?= fmtMoney($row['deductions']) ?></td>
                        <td style="text-align:right; color:#fd7e14;">-<?= fmtMoney($row['insurance']) ?></td>
                        <td class="money" style="color:#0f3460; font-size:15px;"><?= fmtMoney($row['net_salary']) ?></td>
                        <?php if ($filter !== 'year'): ?>
                        <td><?= $row['payment_date'] ? date('d/m/Y', strtotime($row['payment_date'])) : '-' ?></td>
                        <?php else: ?>
                        <td class="text-center"><?= $row['months_count'] ?? '-' ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Tổng cộng</td>
                        <td class="money"><?= fmtMoney($fulltime['summary']['total_base']) ?></td>
                        <td class="money-green">+<?= fmtMoney($fulltime['summary']['total_allowances']) ?></td>
                        <td style="text-align:right; color:#dc3545;">-<?= fmtMoney($fulltime['summary']['total_deductions']) ?></td>
                        <td style="text-align:right; color:#fd7e14;">-<?= fmtMoney($fulltime['summary']['total_insurance']) ?></td>
                        <td class="money" style="font-size:15px;"><?= fmtMoney($fulltime['summary']['total_net']) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Part-time Table -->
    <div class="table-section">
        <h3>⏱️ Bảng lương Part-time <span class="badge badge-green"><?= $parttime['summary']['count'] ?> nhân viên</span></h3>
        <div class="table-wrap">
            <?php if (empty($parttime['rows'])): ?>
                <div class="empty-state">
                    <div class="icon"></div>
                    <p>Không có dữ liệu lương part-time trong kỳ này</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nhân viên</th>
                        <th>Mã NV</th>
                        <th>Phòng ban</th>
                        <th class="text-right">Lương/giờ</th>
                        <th class="text-right">Tổng giờ</th>
                        <?php if ($filter === 'month' || $filter === 'year'): ?>
                        <th class="text-center">Ngày làm</th>
                        <?php endif; ?>
                        <?php if ($filter === 'year'): ?>
                        <th class="text-center">Tháng HĐ</th>
                        <?php endif; ?>
                        <th class="text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parttime['rows'] as $i => $row): ?>
                    <tr>
                        <td style="color:#aaa;"><?= $i + 1 ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($row['full_name']) ?></td>
                        <td style="color:#888;"><?= htmlspecialchars($row['employee_code']) ?></td>
                        <td><?= htmlspecialchars($row['department'] ?? '-') ?></td>
                        <td class="money"><?= fmtMoney($row['hourly_rate']) ?></td>
                        <td class="text-right"><?= number_format($row['total_hours'] ?? $row['hours'] ?? 0, 1) ?> giờ</td>
                        <?php if ($filter === 'month' || $filter === 'year'): ?>
                        <td class="text-center"><?= $row['work_days'] ?? '-' ?></td>
                        <?php endif; ?>
                        <?php if ($filter === 'year'): ?>
                        <td class="text-center"><?= $row['months_active'] ?? '-' ?></td>
                        <?php endif; ?>
                        <td class="money-green" style="font-size:15px;"><?= fmtMoney($row['total_amount'] ?? $row['amount'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Tổng cộng</td>
                        <td></td>
                        <td class="text-right font-weight-bold"><?= number_format($parttime['summary']['total_hours'], 1) ?> giờ</td>
                        <?php if ($filter === 'month' || $filter === 'year'): ?>
                        <td></td>
                        <?php endif; ?>
                        <?php if ($filter === 'year'): ?>
                        <td></td>
                        <?php endif; ?>
                        <td class="money-green" style="font-size:15px;"><?= fmtMoney($parttime['summary']['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
const labels  = <?= json_encode($chartData['labels']) ?>;
const ftData  = <?= json_encode($chartData['ftData']) ?>;
const ptData  = <?= json_encode($chartData['ptData']) ?>;

const ctx = document.getElementById('salaryChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Lương Full-time',
                data: ftData,
                backgroundColor: 'rgba(15, 52, 96, 0.8)',
                borderColor: '#0f3460',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Lương Part-time',
                data: ptData,
                backgroundColor: 'rgba(40, 167, 69, 0.75)',
                borderColor: '#28a745',
                borderWidth: 1,
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'top', labels: { font: { size: 13 }, padding: 16 } },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return ctx.dataset.label + ': ' +
                            new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(ctx.raw);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(val) {
                        if (val >= 1000000) return (val/1000000).toFixed(0) + 'M';
                        if (val >= 1000)    return (val/1000).toFixed(0) + 'K';
                        return val;
                    }
                },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: { grid: { display: false } }
        }
    }
});

// Handle week filter
document.querySelector('input[name="week_date"]')?.addEventListener('change', function() {
    const val = this.value; // format: 2026-W19
    if (val) {
        const parts = val.split('-W');
        document.querySelector('input[name="week"]').value  = parts[1];
        document.querySelector('input[name="year"]').value  = parts[0];
    }
});
</script>
</body>
</html>

