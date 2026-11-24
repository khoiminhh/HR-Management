<?php
/**
 * ========================================
 * View: Form Thêm/Sửa nhân viên
 * ========================================
 */

require_once '../src/controllers/EmployeeController.php';

$controller  = new EmployeeController($pdo);
$action      = $_GET['action'] ?? 'add';
$id          = $_GET['id'] ?? null;
$employee    = null;
$departments = [];
$positions   = [];
$positionsByDepartment = [];
$formData    = null;
$errors      = [];
$message     = '';

if ($action === 'edit' && $id) {
    $result = $controller->showEditForm($id);
    if (isset($result['success']) && !$result['success']) $errors[] = $result['message'];
    else { $employee = $result['employee']; $departments = $result['departments']; $positions = $result['positions'] ?? []; $positionsByDepartment = $result['positionsByDepartment'] ?? []; }
} else {
    $result = $controller->showAddForm();
    $departments = $result['departments'];
    $positions = $result['positions'] ?? [];
    $positionsByDepartment = $result['positionsByDepartment'] ?? [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'employee_code' => $_POST['employee_code'] ?? '',
        'full_name'     => $_POST['full_name'] ?? '',
        'date_of_birth' => $_POST['date_of_birth'] ?? '',
        'gender'        => $_POST['gender'] ?? '',
        'email'         => $_POST['email'] ?? '',
        'phone'         => $_POST['phone'] ?? '',
        'address'       => $_POST['address'] ?? '',
        'department_id' => $_POST['department_id'] ?? '',
        'position_id'   => $_POST['position_id'] ?? '',
        'hire_date'     => $_POST['hire_date'] ?? '',
    ];
    $result = ($action === 'edit' && $id) ? $controller->update($id, $formData) : $controller->store($formData);
    if ($result['success']) { $message = $result['message']; header("Refresh: 2; url=?page=employees"); }
    else $errors = $result['errors'];
}

$d = $formData ?? $employee ?? [
    'employee_code'=>'','full_name'=>'','date_of_birth'=>'','gender'=>'',
    'email'=>'','phone'=>'','address'=>'','position_id'=>'',
    'department_id'=>'','hire_date'=>date('Y-m-d')
];
$formTitle = ($action === 'edit') ? 'Cập nhật nhân viên' : 'Thêm nhân viên mới';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $formTitle ?></title>
    <style>
        :root{--primary:#2563eb;--primary-d:#1d4ed8;--success:#16a34a;--danger:#dc2626;
              --bg:#f1f5f9;--card:#fff;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
        .wrap{max-width:820px;margin:0 auto;padding:28px 24px;}
        .page-hd{background:linear-gradient(135deg,#1e3a5f,#2563eb);border-radius:14px;padding:26px 32px;
                 margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;
                 flex-wrap:wrap;gap:12px;box-shadow:0 4px 20px rgba(37,99,235,.25);}
        .page-hd h1{color:#fff;font-size:24px;font-weight:800;}
        .page-hd p{color:#93c5fd;font-size:13px;margin-top:4px;}
        .breadnav{display:flex;align-items:center;gap:6px;background:#fff;padding:11px 18px;
                  border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-wrap:wrap;}
        .breadnav a{color:var(--muted);text-decoration:none;font-size:13px;padding:4px 10px;border-radius:6px;transition:all .2s;}
        .breadnav a:hover{background:var(--bg);color:var(--primary);}
        .breadnav .sep{color:#cbd5e1;font-size:11px;}
        .alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500;}
        .alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
        .alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
        .form-card{background:#fff;border-radius:14px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.07);}
        .section-label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;
                       letter-spacing:1px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);}
        .form-section{margin-bottom:28px;}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
        .form-group{margin-bottom:18px;}
        .form-group label{display:block;margin-bottom:7px;font-weight:600;font-size:13px;color:var(--text);}
        .form-group label .req{color:var(--danger);}
        .form-group input,.form-group select,.form-group textarea{
            width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;
            font-family:inherit;font-size:14px;transition:all .2s;background:#fff;color:var(--text);}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{
            outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1);}
        .form-group textarea{resize:vertical;min-height:80px;}
        .form-actions{display:flex;gap:10px;margin-top:8px;}
        .btn{padding:11px 24px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;
             font-size:14px;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:8px;}
        .btn-primary{background:var(--primary);color:#fff;flex:1;justify-content:center;}
        .btn-primary:hover{background:var(--primary-d);box-shadow:0 4px 12px rgba(37,99,235,.3);}
        .btn-cancel{background:#f1f5f9;color:var(--muted);justify-content:center;}
        .btn-cancel:hover{background:#e2e8f0;color:var(--text);}
        @media(max-width:600px){.form-row{grid-template-columns:1fr;}}
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="wrap">
    <div class="page-hd">
        <div>
            <h1><?= $action==='edit'?'<i class="ui-icon icon-edit" aria-hidden="true"></i>':'<i class="ui-icon icon-add" aria-hidden="true"></i>' ?> <?= $formTitle ?></h1>
            <p><?= $action==='edit'?'Cập nhật thông tin nhân viên':'Thêm nhân viên mới vào hệ thống' ?></p>
        </div>
        <a href="?page=employees" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">← Quay lại</a>
    </div>
    <div class="breadnav">
        <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
        <a href="?page=employees"><i class="ui-icon icon-employees" aria-hidden="true"></i> Nhân viên</a><span class="sep">›</span>
        <a href="#"><?= $formTitle ?></a>
    </div>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?> — Đang chuyển hướng...</div><?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><?php foreach($errors as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?></div>
    <?php endif; ?>
    <div class="form-card">
        <form method="POST">
            <div class="form-section">
                <div class="section-label"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Thông tin cơ bản</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mã nhân viên <span class="req">*</span></label>
                        <input type="text" name="employee_code" value="<?= htmlspecialchars($d['employee_code']) ?>" required placeholder="VD: NV001">
                    </div>
                    <div class="form-group">
                        <label>Họ và tên <span class="req">*</span></label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($d['full_name']) ?>" required placeholder="Nguyễn Văn A">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ngày sinh</label>
                        <input type="date" name="date_of_birth" value="<?= htmlspecialchars($d['date_of_birth']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Giới tính</label>
                        <select name="gender">
                            <option value="">-- Chọn --</option>
                            <option value="Male"   <?= $d['gender']==='Male'   ?'selected':'' ?>>Nam</option>
                            <option value="Female" <?= $d['gender']==='Female' ?'selected':'' ?>>Nữ</option>
                            <option value="Other"  <?= $d['gender']==='Other'  ?'selected':'' ?>>Khác</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div class="section-label"> Thông tin liên hệ</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($d['email']) ?>" placeholder="example@company.com">
                    </div>
                    <div class="form-group">
                        <label>Điện thoại</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($d['phone']) ?>" placeholder="0901234567">
                    </div>
                </div>
                <div class="form-group">
                    <label>Địa chỉ</label>
                    <textarea name="address" placeholder="Số nhà, đường, phường/xã, quận/huyện..."><?= htmlspecialchars($d['address']) ?></textarea>
                </div>
            </div>
            <div class="form-section">
                <div class="section-label"><i class="ui-icon icon-departments" aria-hidden="true"></i> Thông tin công việc</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phòng ban</label>
                        <select name="department_id" id="department-select">
                            <option value="">-- Chọn phòng ban --</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?= (int)$dept['department_id'] ?>" <?= (string)($d['department_id'] ?? '') === (string)$dept['department_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Chức vụ</label>
                        <select name="position_id" id="position-select">
                            <option value="">-- Chọn phòng ban trước --</option>
                        </select>
                        <span class="hint" id="position-hint">Chức vụ sẽ tự lọc theo phòng ban đã chọn.</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ngày vào công ty</label>
                        <input type="date" name="hire_date" value="<?= htmlspecialchars($d['hire_date']) ?>">
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="ui-icon icon-save" aria-hidden="true"></i> <?= $action==='edit'?'Cập nhật':'Thêm nhân viên' ?></button>
                <a href="?page=employees" class="btn btn-cancel"><i class="ui-icon icon-inactive" aria-hidden="true"></i> Hủy</a>
            </div>
        </form>
    </div>
</div>
<script>
const positionsByDepartment = <?= json_encode($positionsByDepartment, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const initialPositionId = <?= json_encode((string)($d['position_id'] ?? '')) ?>;

function renderPositionsForDepartment(keepCurrent = true) {
    const departmentSelect = document.getElementById('department-select');
    const positionSelect = document.getElementById('position-select');
    const positionHint = document.getElementById('position-hint');

    if (!departmentSelect || !positionSelect) {
        return;
    }

    const departmentId = departmentSelect.value;
    const positions = positionsByDepartment[departmentId] || [];
    const selectedValue = keepCurrent ? (positionSelect.value || initialPositionId) : '';

    positionSelect.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = departmentId ? '-- Chọn chức vụ --' : '-- Chọn phòng ban trước --';
    positionSelect.appendChild(placeholder);

    positions.forEach((position) => {
        const option = document.createElement('option');
        option.value = String(position.position_id);
        option.textContent = position.position_name;

        if (String(position.position_id) === String(selectedValue)) {
            option.selected = true;
        }

        positionSelect.appendChild(option);
    });

    positionSelect.disabled = !departmentId || positions.length === 0;

    if (positionHint) {
        if (!departmentId) {
            positionHint.textContent = 'Chọn phòng ban để xem danh sách chức vụ phù hợp.';
        } else if (positions.length === 0) {
            positionHint.textContent = 'Phòng ban này chưa có chức vụ nào. Hãy thêm chức vụ trong trang Phòng ban.';
        } else {
            positionHint.textContent = `Có ${positions.length} chức vụ thuộc phòng ban đã chọn.`;
        }
    }
}

document.getElementById('department-select')?.addEventListener('change', () => {
    renderPositionsForDepartment(false);
});

renderPositionsForDepartment(true);
</script>
</body>
</html>

