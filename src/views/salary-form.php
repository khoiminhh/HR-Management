<?php
require_once '../src/controllers/SalaryController.php';
$controller = new SalaryController($pdo);
$action = $_GET['action']??'add'; $id = $_GET['id']??null;
$salary = null; $employees = []; $errors = []; $message = '';
if ($action==='edit'&&$id) { $result=$controller->showEditForm($id); if(isset($result['success']) && $result['success']===false) $errors[]=$result['message'] ?? 'Bảng lương không tồn tại'; else { $salary=$result['salary'] ?? null; $employees=$result['employees'] ?? []; } }
else { $result=$controller->showAddForm(); $employees=$result['employees'] ?? []; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $formData=['employee_id'=>$_POST['employee_id']??'','month'=>$_POST['month']??'','year'=>$_POST['year']??'',
               'base_salary'=>$_POST['base_salary']??'','allowances'=>$_POST['allowances']??0,
               'deductions'=>$_POST['deductions']??0,'insurance'=>$_POST['insurance']??0,'payment_date'=>$_POST['payment_date']??''];
    $result=($action==='edit'&&$id)?$controller->update($id,$formData):$controller->store($formData);
    if(!empty($result['success'])){$message=$result['message'] ?? 'Lưu bảng lương thành công!';header("Refresh: 2; url=?page=salaries");}else $errors=$result['errors'] ?? [$result['message'] ?? 'Không thể lưu bảng lương'];
}
$d=$salary??['employee_id'=>'','month'=>(int)date('m'),'year'=>(int)date('Y'),'base_salary'=>'','allowances'=>0,'deductions'=>0,'insurance'=>0,'payment_date'=>date('Y-m-d')];
$title=$action==='edit'?'Cập nhật bảng lương':'Thêm bảng lương mới';
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?></title>
<style>
:root{--primary:#2563eb;--primary-d:#1d4ed8;--success:#16a34a;--danger:#dc2626;--bg:#f1f5f9;--card:#fff;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
.wrap{max-width:860px;margin:0 auto;padding:28px 24px;}
.page-hd{background:linear-gradient(135deg,#1e3a5f,#2563eb);border-radius:14px;padding:26px 32px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:0 4px 20px rgba(37,99,235,.25);}
.page-hd h1{color:#fff;font-size:24px;font-weight:800;} .page-hd p{color:#93c5fd;font-size:13px;margin-top:4px;}
.breadnav{display:flex;align-items:center;gap:6px;background:#fff;padding:11px 18px;border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-wrap:wrap;}
.breadnav a{color:var(--muted);text-decoration:none;font-size:13px;padding:4px 10px;border-radius:6px;transition:all .2s;}
.breadnav a:hover{background:var(--bg);color:var(--primary);} .breadnav .sep{color:#cbd5e1;font-size:11px;}
.alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500;}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;} .alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.form-card{background:#fff;border-radius:14px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.07);}
.sec-label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);}
.form-sec{margin-bottom:28px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.fg{margin-bottom:18px;}
.fg label{display:block;margin-bottom:7px;font-weight:600;font-size:13px;color:var(--text);}
.fg label .req{color:var(--danger);}
.fg input,.fg select{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-family:inherit;font-size:14px;transition:all .2s;background:#fff;}
.fg input:focus,.fg select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1);}
/* Salary calculator */
.calc-box{background:#f8faff;border:2px dashed #bfdbfe;border-radius:12px;padding:20px;margin-top:4px;}
.calc-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;font-size:14px;}
.calc-row:last-child{border-bottom:none;border-top:2px solid var(--primary);padding-top:12px;margin-top:4px;}
.calc-row .cl{color:var(--muted);} .calc-row .cv{font-weight:700;text-align:right;}
.calc-row.total .cl{font-size:15px;font-weight:700;color:var(--text);} .calc-row.total .cv{font-size:18px;color:var(--success);}
.form-actions{display:flex;gap:10px;margin-top:8px;}
.btn{padding:11px 24px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;font-size:14px;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:8px;}
.btn-primary{background:var(--primary);color:#fff;flex:1;justify-content:center;} .btn-primary:hover{background:var(--primary-d);}
.btn-cancel{background:#f1f5f9;color:var(--muted);justify-content:center;} .btn-cancel:hover{background:#e2e8f0;}
@media(max-width:600px){.form-row{grid-template-columns:1fr;}}
</style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="wrap">
<div class="page-hd">
    <div><h1><?= $action==='edit'?'<i class="ui-icon icon-edit" aria-hidden="true"></i>':'<i class="ui-icon icon-add" aria-hidden="true"></i>' ?> <?= $title ?></h1><p>Lương ròng được tính tự động khi nhập số liệu</p></div>
    <a href="?page=salaries" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">← Quay lại</a>
</div>
<div class="breadnav">
    <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
    <a href="?page=salaries"><i class="ui-icon icon-salaries" aria-hidden="true"></i> Lương</a><span class="sep">›</span><a href="#"><?= $title ?></a>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?> — Đang chuyển hướng...</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach($errors as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?></div><?php endif; ?>
<div class="form-card">
<form method="POST">
    <div class="form-sec">
        <div class="sec-label"><i class="ui-icon icon-user" aria-hidden="true"></i> Thông tin nhân viên & kỳ lương</div>
        <div class="fg"><label>Nhân viên <span class="req">*</span></label>
            <select name="employee_id" id="employee_id" required onchange="fillEmployeeSalary()">
                <option value="">-- Chọn nhân viên --</option>
                <?php foreach ($employees as $emp): ?><option value="<?= $emp['id'] ?>" data-contracts="<?= htmlspecialchars(json_encode($emp['active_contracts'] ?? []), ENT_QUOTES, 'UTF-8') ?>" <?= $d['employee_id']==$emp['id']?'selected':'' ?>><?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_code']) ?>)</option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="fg"><label>Tháng <span class="req">*</span></label>
                <select name="month" required><?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $d['month']==$m?'selected':'' ?>>Tháng <?= str_pad($m,2,'0',STR_PAD_LEFT) ?></option><?php endfor; ?></select>
            </div>
            <div class="fg"><label>Năm <span class="req">*</span></label><input type="number" name="year" min="2020" max="2099" value="<?= htmlspecialchars($d['year']) ?>" required></div>
        </div>
    </div>
    <div class="form-sec">
        <div class="sec-label"><i class="ui-icon icon-salaries" aria-hidden="true"></i> Chi tiết lương</div>
        <div class="fg"><label>Lương cơ bản (VNĐ) <span class="req">*</span></label><input type="number" id="base_salary" name="base_salary" value="<?= htmlspecialchars($d['base_salary']) ?>" required oninput="this.dataset.autofilled='0';calc()" placeholder="VD: 10000000" min="0"></div>
        <div class="form-row">
            <div class="fg"><label>Phụ cấp (VNĐ)</label><input type="number" id="allowances" name="allowances" value="<?= htmlspecialchars($d['allowances']) ?>" oninput="calc()" min="0"></div>
            <div class="fg"><label>Khấu trừ (VNĐ)</label><input type="number" id="deductions" name="deductions" value="<?= htmlspecialchars($d['deductions']) ?>" oninput="calc()" min="0"></div>
        </div>
        <div class="fg"><label>Bảo hiểm - BHXH/BHYT/BHTN (VNĐ)</label><input type="number" id="insurance" name="insurance" value="<?= htmlspecialchars($d['insurance']) ?>" oninput="calc()" min="0"></div>
        <!-- Calculator -->
        <div class="calc-box">
            <div class="calc-row"><span class="cl">Lương cơ bản:</span><span class="cv" id="c-base">0 đ</span></div>
            <div class="calc-row"><span class="cl">+ Phụ cấp:</span><span class="cv" id="c-allowances" style="color:var(--success);">0 đ</span></div>
            <div class="calc-row"><span class="cl">− Khấu trừ:</span><span class="cv" id="c-deductions" style="color:var(--danger);">0 đ</span></div>
            <div class="calc-row"><span class="cl">− Bảo hiểm:</span><span class="cv" id="c-insurance" style="color:var(--danger);">0 đ</span></div>
            <div class="calc-row total"><span class="cl">= Lương thực lĩnh:</span><span class="cv" id="c-net">0 đ</span></div>
        </div>
    </div>
    <div class="form-sec">
        <div class="sec-label"><i class="ui-icon icon-calendar" aria-hidden="true"></i> Thanh toán</div>
        <div class="fg"><label>Ngày thanh toán</label><input type="date" name="payment_date" value="<?= htmlspecialchars($d['payment_date']) ?>"></div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="ui-icon icon-save" aria-hidden="true"></i> <?= $action==='edit'?'Cập nhật':'Thêm bảng lương' ?></button>
        <a href="?page=salaries" class="btn btn-cancel"><i class="ui-icon icon-inactive" aria-hidden="true"></i> Hủy</a>
    </div>
</form>
</div>
</div>
<script>
function fmt(n){return new Intl.NumberFormat('vi-VN').format(Math.max(0,n))+' đ';}
function fillEmployeeSalary(){
    const select = document.getElementById('employee_id');
    const base = document.getElementById('base_salary');
    const month = parseInt(document.querySelector('[name="month"]').value || 0, 10);
    const year = parseInt(document.querySelector('[name="year"]').value || 0, 10);
    const contracts = JSON.parse(select.options[select.selectedIndex]?.dataset.contracts || '[]');
    const periodStart = new Date(year, month - 1, 1);
    const periodEnd = new Date(year, month, 0);
    const active = contracts.find(function(contract) {
        const start = new Date(contract.start_date + 'T00:00:00');
        const end = contract.end_date ? new Date(contract.end_date + 'T00:00:00') : null;
        return start <= periodEnd && (!end || end >= periodStart);
    });

    if (active && parseFloat(active.salary) > 0) {
        base.value = active.salary;
        base.dataset.autofilled = '1';
    } else {
        base.value = '';
        base.dataset.autofilled = '1';
    }

    calc();
}
function calc(){
    const baseInput = document.getElementById('base_salary');
    baseInput.dataset.autofilled = baseInput.dataset.autofilled || '';
    const b=parseFloat(baseInput.value)||0;
    const a=parseFloat(document.getElementById('allowances').value)||0;
    const d=parseFloat(document.getElementById('deductions').value)||0;
    const i=parseFloat(document.getElementById('insurance').value)||0;
    document.getElementById('c-base').textContent=fmt(b);
    document.getElementById('c-allowances').textContent=fmt(a);
    document.getElementById('c-deductions').textContent=fmt(d);
    document.getElementById('c-insurance').textContent=fmt(i);
    document.getElementById('c-net').textContent=fmt(b+a-d-i);
}
window.onload=function(){
    document.querySelector('[name="month"]').addEventListener('change', fillEmployeeSalary);
    document.querySelector('[name="year"]').addEventListener('input', fillEmployeeSalary);
    fillEmployeeSalary();
    calc();
};
</script>
</body>
</html>

