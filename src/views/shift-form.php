<?php
require_once '../src/models/Shift.php';
Auth::requireLogin(); Auth::requireRole(['Admin','HR','Manager']);
$shiftModel = new Shift($pdo);
$action = $_GET['action']??'add'; $id = $_GET['id']??null;
$shift = null; $errors = []; $message = '';
if ($action==='edit'&&$id) { $shift=$shiftModel->getById($id); if(!$shift) $errors[]='Ca làm việc không tồn tại'; }
$formTitle = $action==='edit' ? 'Cập nhật Ca Làm Việc' : 'Thêm Ca Làm Việc Mới';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $formData=['shift_name'=>$_POST['shift_name']??'','start_time'=>$_POST['start_time']??'',
               'end_time'=>$_POST['end_time']??'','break_time'=>$_POST['break_time']??0,
               'description'=>$_POST['description']??'','status'=>$_POST['status']??'Active'];
    if(empty($formData['shift_name'])) $errors[]='Tên ca không được để trống';
    if(empty($formData['start_time'])) $errors[]='Giờ bắt đầu không được để trống';
    if(empty($formData['end_time']))   $errors[]='Giờ kết thúc không được để trống';
    if(empty($errors)) {
        try {
            if($action==='add') { $shiftModel->create($formData); $message='Thêm ca làm việc thành công!'; }
            else { $shiftModel->update($id,$formData); $message='Cập nhật ca làm việc thành công!'; }
            header("Refresh: 2; url=?page=shifts");
        } catch(Exception $e) { $errors[]='Lỗi: '.$e->getMessage(); }
    }
}
$d=$shift??['shift_name'=>'','start_time'=>'','end_time'=>'','break_time'=>0,'description'=>'','status'=>'Active'];
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $formTitle ?></title>
<style>
:root{--primary:#2563eb;--primary-d:#1d4ed8;--danger:#dc2626;--bg:#f1f5f9;--card:#fff;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
.wrap{max-width:700px;margin:0 auto;padding:28px 24px;}
.page-hd{background:linear-gradient(135deg,#1e3a5f,#2563eb);border-radius:14px;padding:26px 32px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:0 4px 20px rgba(37,99,235,.25);}
.page-hd h1{color:#fff;font-size:22px;font-weight:800;} .page-hd p{color:#93c5fd;font-size:13px;margin-top:4px;}
.breadnav{display:flex;align-items:center;gap:6px;background:#fff;padding:11px 18px;border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-wrap:wrap;}
.breadnav a{color:var(--muted);text-decoration:none;font-size:13px;padding:4px 10px;border-radius:6px;transition:all .2s;}
.breadnav a:hover{background:var(--bg);color:var(--primary);} .breadnav .sep{color:#cbd5e1;font-size:11px;}
.alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500;}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;} .alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.form-card{background:#fff;border-radius:14px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.07);}
.sec-label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);}
.form-sec{margin-bottom:24px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.fg{margin-bottom:16px;}
.fg label{display:block;margin-bottom:7px;font-weight:600;font-size:13px;color:var(--text);}
.fg label .req{color:var(--danger);}
.fg input,.fg select,.fg textarea{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-family:inherit;font-size:14px;transition:all .2s;background:#fff;}
.fg input:focus,.fg select:focus,.fg textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1);}
.fg textarea{resize:vertical;min-height:70px;}
.fg .hint{font-size:11px;color:var(--muted);margin-top:4px;}
.preview-box{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 18px;margin-top:4px;font-size:14px;font-weight:600;color:#15803d;}
.examples{background:#f8faff;border:1px solid var(--border);border-radius:10px;padding:16px;margin-top:8px;}
.examples h4{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;}
.ex-item{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px;}
.ex-item:last-child{border-bottom:none;} .ex-tag{background:#eff6ff;color:var(--primary);padding:2px 8px;border-radius:6px;font-weight:700;font-size:11px;}
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
    <div><h1><?= $action==='edit'?'<i class="ui-icon icon-edit" aria-hidden="true"></i>':'<i class="ui-icon icon-add" aria-hidden="true"></i>' ?> <?= $formTitle ?></h1><p>Quản lý ca làm việc trong công ty</p></div>
    <a href="?page=shifts" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">← Quay lại</a>
</div>
<div class="breadnav">
    <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
    <a href="?page=shifts"><i class="ui-icon icon-shifts" aria-hidden="true"></i> Ca làm việc</a><span class="sep">›</span><a href="#"><?= $formTitle ?></a>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?> — Đang chuyển hướng...</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach($errors as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?></div><?php endif; ?>
<div class="form-card">
<form method="POST">
    <div class="form-sec">
        <div class="sec-label"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Thông tin ca làm việc</div>
        <div class="fg"><label>Tên ca <span class="req">*</span></label><input type="text" name="shift_name" value="<?= htmlspecialchars($d['shift_name']) ?>" required placeholder="VD: Ca sáng, Ca chiều, Ca đêm..."><span class="hint">Đặt tên ngắn gọn, dễ nhận biết</span></div>
    </div>
    <div class="form-sec">
        <div class="sec-label"><i class="ui-icon icon-shifts" aria-hidden="true"></i> Giờ làm việc</div>
        <div class="form-row">
            <div class="fg"><label>Giờ bắt đầu <span class="req">*</span></label><input type="time" name="start_time" id="start_time" value="<?= htmlspecialchars($d['start_time']) ?>" required onchange="preview()"></div>
            <div class="fg"><label>Giờ kết thúc <span class="req">*</span></label><input type="time" name="end_time" id="end_time" value="<?= htmlspecialchars($d['end_time']) ?>" required onchange="preview()"></div>
        </div>
        <div class="fg"><label>Nghỉ giữa ca (phút)</label><input type="number" name="break_time" id="break_time" value="<?= htmlspecialchars($d['break_time']) ?>" min="0" step="15" onchange="preview()"><span class="hint">0 = không nghỉ; thường là 30 hoặc 60 phút</span></div>
        <div class="preview-box" id="preview"><i class="ui-icon icon-shifts" aria-hidden="true"></i> Điền giờ để xem trước</div>
        <div class="examples">
            <h4>Ví dụ phổ biến</h4>
            <div class="ex-item"><span class="ex-tag">Ca Sáng</span>08:00 → 12:00, nghỉ 0 phút</div>
            <div class="ex-item"><span class="ex-tag">Ca Chiều</span>13:00 → 17:00, nghỉ 0 phút</div>
            <div class="ex-item"><span class="ex-tag">Full-time</span>08:00 → 17:00, nghỉ 60 phút</div>
            <div class="ex-item"><span class="ex-tag">Ca Đêm</span>22:00 → 06:00, nghỉ 60 phút</div>
        </div>
    </div>
    <div class="form-sec">
        <div class="sec-label">Mô tả & Trạng thái</div>
        <div class="fg"><label>Mô tả</label><textarea name="description" placeholder="Mô tả thêm về ca làm việc..."><?= htmlspecialchars($d['description']) ?></textarea></div>
        <div class="fg"><label>Trạng thái</label>
            <select name="status">
                <option value="Active"   <?= $d['status']==='Active'  ?'selected':'' ?>>Hoạt động</option>
                <option value="Inactive" <?= $d['status']==='Inactive'?'selected':'' ?>>Không hoạt động</option>
            </select>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="ui-icon icon-save" aria-hidden="true"></i> <?= $action==='edit'?'Cập nhật':'Thêm ca làm việc' ?></button>
        <a href="?page=shifts" class="btn btn-cancel"><i class="ui-icon icon-inactive" aria-hidden="true"></i> Hủy</a>
    </div>
</form>
</div>
</div>
<script>
function preview(){
    const s=document.getElementById('start_time').value;
    const e=document.getElementById('end_time').value;
    const b=document.getElementById('break_time').value||0;
    const p=document.getElementById('preview');
    if(s&&e) p.textContent=`${s} → ${e}  |  Nghỉ ${b} phút`;
}
window.onload=preview;
</script>
</body>
</html>


