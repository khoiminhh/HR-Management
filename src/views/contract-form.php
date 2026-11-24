<?php
require_once '../src/controllers/ContractController.php';
$controller = new ContractController($pdo);
$action = $_GET['action'] ?? 'add'; $id = $_GET['id'] ?? null;
$contract = null; $employees = []; $contractTypes = []; $errors = []; $message = '';
if ($action === 'edit' && $id) {
    $result = $controller->showEditForm($id);
    if (isset($result['success']) && $result['success'] === false) $errors[] = $result['message'] ?? 'Hợp đồng không tồn tại';
    else { $contract = $result['contract'] ?? null; $employees = $result['employees'] ?? []; $contractTypes = $result['contractTypes'] ?? []; }
} else { $result = $controller->showAddForm(); $employees = $result['employees']; $contractTypes = $result['contractTypes']; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = ['employee_id'=>$_POST['employee_id']??'','contract_number'=>$_POST['contract_number']??'',
        'contract_type'=>$_POST['contract_type']??'','start_date'=>$_POST['start_date']??'',
        'end_date'=>$_POST['end_date']??'','salary'=>$_POST['salary']??'','status'=>$_POST['status']??'Active'];
    $result = ($action==='edit'&&$id) ? $controller->update($id,$formData) : $controller->store($formData);
    if (!empty($result['success'])) { $message = $result['message'] ?? 'Lưu hợp đồng thành công!'; header("Refresh: 2; url=?page=contracts"); }
    else $errors = $result['errors'] ?? [$result['message'] ?? 'Không thể lưu hợp đồng'];
}
$d = $contract ?? ['employee_id'=>'','contract_number'=>'','contract_type'=>'','start_date'=>date('Y-m-d'),'end_date'=>'','salary'=>'','status'=>'Active'];
$title = $action==='edit' ? 'Cập nhật hợp đồng' : 'Thêm hợp đồng mới';
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?></title>
<style>
:root{--primary:#2563eb;--primary-d:#1d4ed8;--danger:#dc2626;--bg:#f1f5f9;--card:#fff;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
.wrap{max-width:820px;margin:0 auto;padding:28px 24px;}
.page-hd{background:linear-gradient(135deg,#1e3a5f,#2563eb);border-radius:14px;padding:26px 32px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:0 4px 20px rgba(37,99,235,.25);}
.page-hd h1{color:#fff;font-size:24px;font-weight:800;} .page-hd p{color:#93c5fd;font-size:13px;margin-top:4px;}
.breadnav{display:flex;align-items:center;gap:6px;background:#fff;padding:11px 18px;border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-wrap:wrap;}
.breadnav a{color:var(--muted);text-decoration:none;font-size:13px;padding:4px 10px;border-radius:6px;transition:all .2s;}
.breadnav a:hover{background:var(--bg);color:var(--primary);} .breadnav .sep{color:#cbd5e1;font-size:11px;}
.alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500;}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.form-card{background:#fff;border-radius:14px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.07);}
.sec-label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);}
.form-sec{margin-bottom:28px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.fg{margin-bottom:18px;}
.fg label{display:block;margin-bottom:7px;font-weight:600;font-size:13px;color:var(--text);}
.fg label .req{color:var(--danger);}
.fg input,.fg select{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-family:inherit;font-size:14px;transition:all .2s;background:#fff;}
.fg input:focus,.fg select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1);}
.fg .hint{font-size:11px;color:var(--muted);margin-top:4px;}
.info-box{background:#eff6ff;border-left:4px solid var(--primary);padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px;color:#1e40af;}
.form-actions{display:flex;gap:10px;margin-top:8px;}
.btn{padding:11px 24px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;font-size:14px;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:8px;}
.btn-primary{background:var(--primary);color:#fff;flex:1;justify-content:center;}
.btn-primary:hover{background:var(--primary-d);}
.btn-cancel{background:#f1f5f9;color:var(--muted);justify-content:center;}
.btn-cancel:hover{background:#e2e8f0;}
@media(max-width:600px){.form-row{grid-template-columns:1fr;}}
</style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="wrap">
<div class="page-hd">
    <div><h1><?= $action==='edit'?'<i class="ui-icon icon-edit" aria-hidden="true"></i>':'<i class="ui-icon icon-add" aria-hidden="true"></i>' ?> <?= $title ?></h1><p><?= $action==='edit'?'Cập nhật thông tin hợp đồng lao động':'Tạo hợp đồng lao động mới' ?></p></div>
    <a href="?page=contracts" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">← Quay lại</a>
</div>
<div class="breadnav">
    <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
    <a href="?page=contracts"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Hợp đồng</a><span class="sep">›</span>
    <a href="#"><?= $title ?></a>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?> — Đang chuyển hướng...</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach($errors as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?></div><?php endif; ?>
<div class="form-card">
    <div class="info-box">Các trường có dấu <strong>*</strong> là bắt buộc. Ngày kết thúc để trống nếu là hợp đồng không xác định thời hạn.</div>
    <form method="POST">
        <div class="form-sec">
            <div class="sec-label"><i class="ui-icon icon-user" aria-hidden="true"></i> Thông tin nhân viên</div>
            <div class="fg">
                <label>Nhân viên <span class="req">*</span></label>
                <select name="employee_id" required>
                    <option value="">-- Chọn nhân viên --</option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= $d['employee_id']==$emp['id']?'selected':'' ?>><?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-sec">
            <div class="sec-label"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Thông tin hợp đồng</div>
            <div class="form-row">
                <div class="fg"><label>Số hợp đồng</label><input type="text" value="<?= htmlspecialchars($d['contract_number'] ?: 'Tự động') ?>" readonly></div>
                <div class="fg"><label>Loại hợp đồng <span class="req">*</span></label>
                    <select name="contract_type" required>
                        <option value="">-- Chọn loại --</option>
                        <?php foreach ($contractTypes as $type): ?><option value="<?= htmlspecialchars($type) ?>" <?= $d['contract_type']===$type?'selected':'' ?>><?= htmlspecialchars($type) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="fg"><label>Ngày bắt đầu <span class="req">*</span></label><input type="date" name="start_date" value="<?= htmlspecialchars($d['start_date']) ?>" required></div>
                <div class="fg"><label>Ngày kết thúc</label><input type="date" name="end_date" value="<?= htmlspecialchars($d['end_date']) ?>"><span class="hint">Để trống nếu không xác định thời hạn</span></div>
            </div>
        </div>
        <div class="form-sec">
            <div class="sec-label"><i class="ui-icon icon-salaries" aria-hidden="true"></i> Lương & Trạng thái</div>
            <div class="form-row">
                <div class="fg"><label>Mức lương (VNĐ)</label><input type="number" name="salary" value="<?= htmlspecialchars($d['salary']) ?>" placeholder="VD: 10000000" min="0"></div>
                <div class="fg"><label>Trạng thái</label>
                    <select name="status">
                        <option value="Active"     <?= $d['status']==='Active'     ?'selected':'' ?>>Đang hiệu lực</option>
                        <option value="Expired"    <?= $d['status']==='Expired'    ?'selected':'' ?>>Hết hạn</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="ui-icon icon-save" aria-hidden="true"></i> <?= $action==='edit'?'Cập nhật':'Thêm hợp đồng' ?></button>
            <a href="?page=contracts" class="btn btn-cancel"><i class="ui-icon icon-inactive" aria-hidden="true"></i> Hủy</a>
        </div>
    </form>
</div>
</div>
</body>
</html>

