<?php
require_once '../src/models/Schedule.php';
require_once '../src/models/Employee.php';
require_once '../src/models/Shift.php';
Auth::requireLogin(); Auth::requireRole(['Admin','HR','Manager']);
$scheduleModel = new Schedule($pdo);
$employeeModel = new Employee($pdo);
$shiftModel    = new Shift($pdo);
$action = $_GET['action']??'add'; $id = $_GET['id']??null;
$schedule = null; $errors = []; $message = '';
if ($action==='edit'&&$id) { $schedule=$scheduleModel->getById($id); if(!$schedule) $errors[]='Lịch không tồn tại'; }
$employees = $employeeModel->getAll();
$shifts    = $shiftModel->getAll();
$formTitle = $action==='edit' ? 'Cập nhật Lịch Làm Việc' : 'Thêm Lịch Làm Việc Mới';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $formData=['employee_id'=>$_POST['employee_id']??'','schedule_date'=>$_POST['schedule_date']??'',
               'shift_id'=>$_POST['shift_id']??null,'status'=>$_POST['status']??'Scheduled','notes'=>$_POST['notes']??''];
    if(empty($formData['employee_id'])) $errors[]='Vui lòng chọn nhân viên';
    if(empty($formData['schedule_date'])) $errors[]='Vui lòng chọn ngày làm việc';
    if(empty($errors)) {
        try {
            if($action==='add') {
                if($scheduleModel->checkExists($formData['employee_id'],$formData['schedule_date']))
                    $errors[]='Lịch này đã tồn tại cho nhân viên trong ngày đã chọn';
                else { $scheduleModel->create($formData); $message='Thêm lịch thành công!'; header("Refresh: 2; url=?page=schedules"); }
            } else { $scheduleModel->update($id,$formData); $message='Cập nhật lịch thành công!'; header("Refresh: 2; url=?page=schedules"); }
        } catch(Exception $e) { $errors[]='Lỗi: '.$e->getMessage(); }
    }
}
$d=$schedule??['employee_id'=>'','schedule_date'=>date('Y-m-d'),'shift_id'=>'','status'=>'Scheduled','notes'=>''];
?><!DOCTYPE html>
<html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $formTitle ?></title>
<style>
:root{--primary:#2563eb;--primary-d:#1d4ed8;--danger:#dc2626;--bg:#f1f5f9;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
.wrap{max-width:700px;margin:0 auto;padding:28px 24px;}
.page-hd{background:linear-gradient(135deg,#1e3a5f,#2563eb);border-radius:14px;padding:26px 32px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:0 4px 20px rgba(37,99,235,.25);}
.page-hd h1{color:#fff;font-size:22px;font-weight:800;}.page-hd p{color:#93c5fd;font-size:13px;margin-top:4px;}
.breadnav{display:flex;align-items:center;gap:6px;background:#fff;padding:11px 18px;border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-wrap:wrap;}
.breadnav a{color:var(--muted);text-decoration:none;font-size:13px;padding:4px 10px;border-radius:6px;transition:all .2s;}
.breadnav a:hover{background:var(--bg);color:var(--primary);}.breadnav .sep{color:#cbd5e1;font-size:11px;}
.alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500;}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.form-card{background:#fff;border-radius:14px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.07);}
.sec-label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);}
.form-sec{margin-bottom:24px;}
.fg{margin-bottom:18px;}
.fg label{display:block;margin-bottom:7px;font-weight:600;font-size:13px;color:var(--text);}
.fg label .req{color:var(--danger);}
.fg input,.fg select,.fg textarea{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-family:inherit;font-size:14px;transition:all .2s;background:#fff;}
.fg input:focus,.fg select:focus,.fg textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1);}
.fg textarea{resize:vertical;min-height:70px;}
.shift-preview{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-top:8px;font-size:13px;color:#15803d;font-weight:600;display:none;}
.status-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px;}
.status-opt{border:2px solid var(--border);border-radius:10px;padding:12px;cursor:pointer;transition:all .2s;text-align:center;}
.status-opt:hover{border-color:var(--primary);}
.status-opt input{display:none;}
.status-opt.selected-scheduled{border-color:#2563eb;background:#eff6ff;}
.status-opt.selected-present{border-color:#16a34a;background:#f0fdf4;}
.status-opt.selected-absent{border-color:#dc2626;background:#fef2f2;}
.status-opt.selected-leave{border-color:#d97706;background:#fffbeb;}
.status-opt .so-icon{font-size:20px;margin-bottom:4px;}
.status-opt .so-label{font-size:12px;font-weight:700;color:var(--text);}
.form-actions{display:flex;gap:10px;margin-top:8px;}
.btn{padding:11px 24px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;font-size:14px;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:8px;}
.btn-primary{background:var(--primary);color:#fff;flex:1;justify-content:center;}.btn-primary:hover{background:var(--primary-d);}
.btn-cancel{background:#f1f5f9;color:var(--muted);justify-content:center;}.btn-cancel:hover{background:#e2e8f0;}
</style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head><body>
<div class="wrap">
<div class="page-hd">
    <div><h1><?= $action==='edit'?'<i class="ui-icon icon-edit" aria-hidden="true"></i>':'<i class="ui-icon icon-add" aria-hidden="true"></i>' ?> <?= $formTitle ?></h1><p>Lập lịch làm việc cho nhân viên</p></div>
    <a href="?page=schedules" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">← Quay lại</a>
</div>
<div class="breadnav">
    <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
    <a href="?page=schedules"><i class="ui-icon icon-schedules" aria-hidden="true"></i> Lịch làm việc</a><span class="sep">›</span><a href="#"><?= $formTitle ?></a>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?> — Đang chuyển hướng...</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach($errors as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?></div><?php endif; ?>
<div class="form-card">
<form method="POST">
    <div class="form-sec">
        <div class="sec-label"><i class="ui-icon icon-user" aria-hidden="true"></i> Nhân viên & Ngày</div>
        <div class="fg"><label>Nhân viên <span class="req">*</span></label>
            <select name="employee_id" required>
                <option value="">-- Chọn nhân viên --</option>
                <?php foreach($employees as $emp): ?><option value="<?= $emp['id'] ?>" <?= $d['employee_id']==$emp['id']?'selected':'' ?>><?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_code']) ?>)</option><?php endforeach; ?>
            </select>
        </div>
        <div class="fg"><label>Ngày làm việc <span class="req">*</span></label><input type="date" name="schedule_date" value="<?= htmlspecialchars($d['schedule_date']) ?>" required></div>
    </div>
    <div class="form-sec">
        <div class="sec-label"><i class="ui-icon icon-shifts" aria-hidden="true"></i> Ca làm việc</div>
        <div class="fg"><label>Chọn ca</label>
            <select name="shift_id" onchange="showShiftPreview(this)">
                <option value="">-- Không có ca cụ thể --</option>
                <?php foreach($shifts as $sh): ?><option value="<?= $sh['id'] ?>" data-start="<?= substr($sh['start_time'],0,5) ?>" data-end="<?= substr($sh['end_time'],0,5) ?>" data-break="<?= $sh['break_time'] ?>" <?= $d['shift_id']==$sh['id']?'selected':'' ?>><?= htmlspecialchars($sh['shift_name']) ?> (<?= substr($sh['start_time'],0,5) ?> - <?= substr($sh['end_time'],0,5) ?>)</option><?php endforeach; ?>
            </select>
            <div class="shift-preview" id="shiftPreview"></div>
        </div>
    </div>
    <div class="form-sec">
        <div class="sec-label"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Trạng thái</div>
        <div class="fg">
            <label>Trạng thái <span class="req">*</span></label>
            <select name="status">
                <option value="Scheduled" <?= $d['status']==='Scheduled'?'selected':'' ?>>Đã lên lịch</option>
                <option value="Present"   <?= $d['status']==='Present'  ?'selected':'' ?>>Có mặt</option>
                <option value="Absent"    <?= $d['status']==='Absent'   ?'selected':'' ?>>Vắng mặt</option>
                <option value="Leave"     <?= $d['status']==='Leave'    ?'selected':'' ?>>Nghỉ phép</option>
            </select>
        </div>
    </div>
    <div class="form-sec">
        <div class="sec-label">Ghi chú</div>
        <div class="fg"><label>Ghi chú</label><textarea name="notes" placeholder="Ghi chú thêm nếu có..."><?= htmlspecialchars($d['notes']) ?></textarea></div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="ui-icon icon-save" aria-hidden="true"></i> <?= $action==='edit'?'Cập nhật':'Thêm lịch' ?></button>
        <a href="?page=schedules" class="btn btn-cancel"><i class="ui-icon icon-inactive" aria-hidden="true"></i> Hủy</a>
    </div>
</form>
</div>
</div>
<script>
function showShiftPreview(sel) {
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('shiftPreview');
    if (sel.value && opt.dataset.start) {
        box.style.display = 'block';
        box.textContent = `${opt.dataset.start} → ${opt.dataset.end}  |  Nghỉ ${opt.dataset.break} phút`;
    } else { box.style.display = 'none'; }
}
window.onload = function() {
    const sel = document.querySelector('select[name="shift_id"]');
    if (sel) showShiftPreview(sel);
};
</script>
</body></html>


