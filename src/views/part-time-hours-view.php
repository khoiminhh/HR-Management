<?php
/**
 * ========================================
 * View: Chi tiết giờ công Part-time
 * Biến nhận từ Controller->hours():
 *   $partTimer  - thông tin part-timer + tên nhân viên
 *   $workHours  - danh sách giờ công
 *   $summary    - tổng giờ & tổng tiền
 *   $month, $year - tháng/năm đang xem
 *   $message, $errors
 * ========================================
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giờ công: <?= htmlspecialchars($partTimer['full_name'] ?? '') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; }
        .header h1 { font-size: 24px; margin-bottom: 6px; }
        .header p { opacity: 0.85; font-size: 14px; }
        .nav { background: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; gap: 10px; flex-wrap: wrap; }
        .nav a { color: #667eea; text-decoration: none; padding: 8px 15px; border-radius: 4px; transition: background-color 0.3s; }
        .nav a:hover { background-color: #667eea; color: white; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex-wrap: wrap; gap: 10px; }
        .filter-bar { background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .filter-bar select, .filter-bar input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .summary-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .card .value { font-size: 28px; font-weight: 700; color: #667eea; }
        .card .label { font-size: 13px; color: #666; margin-top: 4px; }
        .btn { padding: 9px 18px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; transition: background-color 0.3s; }
        .btn-primary  { background-color: #667eea; color: white; }
        .btn-back     { background-color: #6c757d; color: white; }
        .btn-add-hour { background-color: #28a745; color: white; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        table thead { background-color: #667eea; color: white; }
        table th { padding: 14px 15px; text-align: left; font-weight: 600; }
        table td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        table tfoot td { background: #f4f4f4; font-weight: bold; padding: 12px 15px; }
        .amount { text-align: right; font-weight: 600; }
        .status { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #d4edda; color: #155724; }
        .message { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; }
        .message.error   { background-color: #f8d7da; color: #721c24; }
        .empty-state { text-align: center; padding: 40px; color: #999; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .add-hour-form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; display: none; }
        .add-hour-form.show { display: block; }
        .form-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .top-bar {
            border: 1px solid #dbe4ef;
            border-radius: 12px;
            padding: 14px 18px;
            box-shadow: 0 8px 24px rgba(23, 32, 51, .07);
        }
        .top-bar h2 {
            font-size: 18px;
            font-weight: 900;
            color: #172033;
        }
        .top-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }
        .month-filter {
            justify-content: center;
            gap: 10px;
        }
        .month-filter > label {
            margin: 0;
            color: #172033;
            font-size: 14px;
            font-weight: 900 !important;
            white-space: nowrap;
        }
        .month-filter .filter-field {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .month-filter label {
            margin: 0;
            color: #172033;
            font-size: 14px;
            font-weight: 900;
            white-space: nowrap;
        }
        .month-filter select {
            width: 180px;
            min-height: 44px;
            border-radius: 10px;
            font-weight: 700;
        }
        .month-filter .btn {
            min-height: 44px;
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 900;
        }
        @media (max-width: 768px) {
            .top-bar,
            .month-filter {
                align-items: stretch;
                flex-direction: column;
            }
            .top-actions,
            .month-filter .filter-field,
            .month-filter .btn,
            .month-filter select {
                width: 100%;
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
        <h1>⏱️ Giờ công: <?= htmlspecialchars($partTimer['full_name'] ?? 'N/A') ?></h1>
        <p>Mã NV: <?= htmlspecialchars($partTimer['employee_code'] ?? '') ?> &nbsp;|&nbsp;
           Lương/giờ: <?= number_format($partTimer['hourly_rate'] ?? 0, 0, ',', '.') ?> đ &nbsp;|&nbsp;
           Tối đa: <?= $partTimer['max_hours_per_week'] ?? 0 ?> giờ/tuần
        </p>
    </div>

    <!-- Nav -->
    <div class="nav">
        <a href="?page=dashboard">Trang chủ</a>
        <a href="?page=employees">Nhân viên</a>
        <a href="?page=part-timers">Part-time</a>
        <a href="?page=part-timers">← Quay lại danh sách</a>
    </div>

    <!-- Messages -->
    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="message error">
            <?php foreach ($errors as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Top bar -->
    <div class="top-bar">
        <h2>Giờ công tháng <?= $month ?>/<?= $year ?></h2>
        <div class="top-actions">
            <button class="btn btn-add-hour" onclick="toggleForm()"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm giờ công</button>
            <a href="?page=part-timers" class="btn btn-back">← Quay lại</a>
        </div>
    </div>

    <!-- Filter tháng/năm -->
    <form method="GET" class="filter-bar month-filter">
        <input type="hidden" name="page" value="part-timers">
        <input type="hidden" name="action" value="hours">
        <input type="hidden" name="id" value="<?= $partTimer['id'] ?>">
        <label style="font-weight:600;">Tháng:</label>
        <select name="month">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>Tháng <?= $m ?></option>
            <?php endfor; ?>
        </select>
        <label style="font-weight:600;">Năm:</label>
        <select name="year">
            <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-primary">Lọc</button>
    </form>

    <!-- Summary cards -->
    <div class="summary-cards">
        <div class="card">
            <div class="value"><?= number_format($summary['total_hours'] ?? 0, 1) ?></div>
            <div class="label">Tổng giờ làm</div>
        </div>
        <div class="card">
            <div class="value"><?= number_format($summary['total_amount'] ?? 0, 0, ',', '.') ?> đ</div>
            <div class="label">Tổng lương</div>
        </div>
        <div class="card">
            <div class="value"><?= count($workHours) ?></div>
            <div class="label">Số ngày làm</div>
        </div>
    </div>

    <!-- Form thêm giờ công -->
    <div class="add-hour-form" id="addHourForm">
        <h3 style="margin-bottom:15px;"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm giờ công mới</h3>
        <form method="POST" action="?page=part-timers&action=hours&id=<?= $partTimer['id'] ?>&month=<?= (int)$month ?>&year=<?= (int)$year ?>">
            <input type="hidden" name="action" value="add_hours">
            <div class="form-row">
                <div class="form-group">
                    <label>Ngày làm việc *</label>
                    <input type="date" name="work_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Số giờ *</label>
                    <input type="number" name="hours" min="0.5" max="12" step="0.5" required placeholder="VD: 4">
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <input type="text" name="notes" placeholder="Ghi chú (tuỳ chọn)">
                </div>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary"><i class="ui-icon icon-save" aria-hidden="true"></i> Lưu</button>
                <button type="button" class="btn btn-back" onclick="toggleForm()">Hủy</button>
            </div>
        </form>
    </div>

    <!-- Bảng giờ công -->
    <?php if (empty($workHours)): ?>
        <div class="empty-state">
            <p style="font-size:16px;">Chưa có dữ liệu giờ công trong tháng <?= $month ?>/<?= $year ?></p>
            <p style="margin-top:8px; font-size:13px;">Nhấn "Thêm giờ công" để bắt đầu ghi nhận.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Ngày làm việc</th>
                    <th>Số giờ</th>
                    <th>Lương/giờ</th>
                    <th class="amount">Thành tiền</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($workHours as $row): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['work_date'])) ?></td>
                        <td><?= $row['hours'] ?> giờ</td>
                        <td><?= number_format($row['hourly_rate'], 0, ',', '.') ?> đ</td>
                        <td class="amount"><?= number_format($row['amount'], 0, ',', '.') ?> đ</td>
                        <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="1">Tổng cộng</td>
                    <td><?= number_format($summary['total_hours'] ?? 0, 1) ?> giờ</td>
                    <td></td>
                    <td class="amount"><?= number_format($summary['total_amount'] ?? 0, 0, ',', '.') ?> đ</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>

<script>
function toggleForm() {
    const form = document.getElementById('addHourForm');
    form.classList.toggle('show');
}
</script>
</body>
</html>

