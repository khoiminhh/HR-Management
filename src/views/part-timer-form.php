<?php
/**
 * ========================================
 * View: Form Thêm / Sửa Part-timer
 * ========================================
 */

$isEdit    = isset($partTimer) && $partTimer;
$pageTitle = $isEdit ? 'Cập nhật Part-timer' : 'Thêm Part-timer mới';
$formAction = $isEdit
    ? '?page=part-timers&action=edit&id=' . $partTimer['id']
    : '?page=part-timers&action=add';
$submitAction = $isEdit ? 'update' : 'create';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; }
        .header h1 { font-size: 24px; }
        .nav { background: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; gap: 10px; flex-wrap: wrap; }
        .nav a { color: #667eea; text-decoration: none; padding: 8px 15px; border-radius: 4px; transition: background-color 0.3s; }
        .nav a:hover { background-color: #667eea; color: white; }
        .card { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; }
        .form-group input,
        .form-group select { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; transition: border-color 0.3s; }
        .form-group input:focus,
        .form-group select:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,0.15); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; transition: background-color 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a6fd6; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-group { display: flex; gap: 10px; margin-top: 10px; }
        .message { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .message.error { background: #f8d7da; color: #721c24; }
        .required { color: red; }
        .employee-info { background: #f0f4ff; border: 1px solid #c7d2fe; border-radius: 6px; padding: 12px; margin-top: 8px; font-size: 13px; color: #4338ca; display: none; }
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="ui-icon icon-employees" aria-hidden="true"></i> <?= $pageTitle ?></h1>
        <p>Quản lý nhân viên part-time và giờ công</p>
    </div>

    <div class="nav">
        <a href="?page=dashboard">Trang chủ</a>
        <a href="?page=employees">Nhân viên</a>
        <a href="?page=part-timers">Part-time</a>
        <a href="?page=part-timers">← Quay lại danh sách</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <?php foreach ($errors as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="<?= $formAction ?>">
            <input type="hidden" name="action" value="<?= $submitAction ?>">

            <?php if (!$isEdit): ?>
            <!-- Chỉ hiện khi Thêm mới -->
            <div class="form-group">
                <label>Chọn nhân viên <span class="required">*</span></label>
                <select name="employee_id" id="employee_id" required onchange="showEmployeeInfo(this)" <?= empty($employees) ? 'disabled' : '' ?>>
                    <option value="">-- Chọn nhân viên --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"
                            data-code="<?= htmlspecialchars($emp['employee_code']) ?>"
                            data-dept="<?= htmlspecialchars($emp['department'] ?? '') ?>"
                            data-pos="<?= htmlspecialchars($emp['position'] ?? '') ?>"
                            <?= (isset($_POST['employee_id']) && $_POST['employee_id'] == $emp['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['full_name']) ?> - <?= htmlspecialchars($emp['employee_code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="employee-info" id="emp-info"></div>
                <?php if (empty($employees)): ?>
                    <div class="employee-info" style="display:block;">Hay tao hoac cap nhat nhan vien voi chuc vu Part-time truoc, sau do quay lai them vao danh sach part-time.</div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <!-- Khi edit: chỉ hiện tên, không cho đổi -->
            <div class="form-group">
                <label>Nhân viên</label>
                <input type="text" value="<?= htmlspecialchars($partTimer['full_name']) ?> - <?= htmlspecialchars($partTimer['employee_code']) ?>" disabled>
            </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Lương theo giờ (VNĐ) <span class="required">*</span></label>
                    <input type="number" name="hourly_rate" min="0" step="1000" required
                        value="<?= htmlspecialchars($_POST['hourly_rate'] ?? $partTimer['hourly_rate'] ?? '') ?>"
                        placeholder="VD: 28000">
                </div>
                <div class="form-group">
                    <label>Giờ/tuần tối đa <span class="required">*</span></label>
                    <input type="number" name="max_hours_per_week" min="1" max="48" required
                        value="<?= htmlspecialchars($_POST['max_hours_per_week'] ?? $partTimer['max_hours_per_week'] ?? 30) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Ngày bắt đầu <span class="required">*</span></label>
                    <input type="date" name="start_date" required
                        value="<?= htmlspecialchars($_POST['start_date'] ?? $partTimer['start_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="date" name="end_date"
                        value="<?= htmlspecialchars($_POST['end_date'] ?? $partTimer['end_date'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Loại hợp đồng</label>
                    <select name="contract_type">
                        <option value="Part-time" <?= (($_POST['contract_type'] ?? $partTimer['contract_type'] ?? '') === 'Part-time') ? 'selected' : '' ?>>Part-time</option>
                        <option value="Thời vụ"   <?= (($_POST['contract_type'] ?? $partTimer['contract_type'] ?? '') === 'Thời vụ')   ? 'selected' : '' ?>>Thời vụ</option>
                        <option value="CTV"       <?= (($_POST['contract_type'] ?? $partTimer['contract_type'] ?? '') === 'CTV')       ? 'selected' : '' ?>>Cộng tác viên</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Trạng thái <span class="required">*</span></label>
                    <select name="status" required>
                        <option value="Active"   <?= (($_POST['status'] ?? $partTimer['status'] ?? 'Active') === 'Active')   ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= (($_POST['status'] ?? $partTimer['status'] ?? '') === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? '<i class="ui-icon icon-save" aria-hidden="true"></i> Cập nhật' : '<i class="ui-icon icon-add" aria-hidden="true"></i> Thêm mới' ?>
                </button>
                <a href="?page=part-timers" class="btn btn-secondary"><i class="ui-icon icon-inactive" aria-hidden="true"></i> Hủy</a>
            </div>
        </form>
    </div>
</div>

<script>
function showEmployeeInfo(select) {
    const opt  = select.options[select.selectedIndex];
    const info = document.getElementById('emp-info');
    if (select.value) {
        info.style.display = 'block';
        info.innerHTML = `Mã NV: <strong>${opt.dataset.code}</strong> &nbsp;|&nbsp; 
                          Phòng ban: <strong>${opt.dataset.dept || 'N/A'}</strong> &nbsp;|&nbsp; 
                          Chức vụ: <strong>${opt.dataset.pos || 'N/A'}</strong>`;
    } else {
        info.style.display = 'none';
    }
}
</script>
</body>
</html>

