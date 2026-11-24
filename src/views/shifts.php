<?php
require_once '../src/models/Shift.php';
Auth::requireLogin(); Auth::requireRole(['Admin','HR','Manager']);
$shiftModel = new Shift($pdo);
$message = ''; $errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete') {
    try { $shiftModel->delete($_POST['id']??null); $message='Xóa ca làm việc thành công!'; }
    catch(Exception $e) { $errors[]='Lỗi: '.$e->getMessage(); }
}
$shifts = $shiftModel->getAll();
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $shifts = array_filter($shifts, fn($s) =>
        stripos($s['shift_name'] ?? '', $search) !== false ||
        stripos($s['description'] ?? '', $search) !== false ||
        stripos($s['status'] ?? '', $search) !== false ||
        stripos(substr($s['start_time'] ?? '', 0, 5), $search) !== false ||
        stripos(substr($s['end_time'] ?? '', 0, 5), $search) !== false
    );
}
$shifts = array_values($shifts);
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quản lý Ca Làm Việc</title>
<style>
:root{--primary:#2563eb;--primary-d:#1d4ed8;--success:#16a34a;--danger:#dc2626;--bg:#f1f5f9;--card:#fff;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
.wrap{max-width:1200px;margin:0 auto;padding:28px 24px;}
.page-hd{background:linear-gradient(135deg,#1e3a5f,#2563eb);border-radius:14px;padding:26px 32px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:0 4px 20px rgba(37,99,235,.25);}
.page-hd h1{color:#fff;font-size:24px;font-weight:800;} .page-hd p{color:#93c5fd;font-size:13px;margin-top:4px;}
.breadnav{display:flex;align-items:center;gap:6px;background:#fff;padding:11px 18px;border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-wrap:wrap;}
.breadnav a{color:var(--muted);text-decoration:none;font-size:13px;padding:4px 10px;border-radius:6px;transition:all .2s;font-weight:500;}
.breadnav a:hover{background:var(--bg);color:var(--primary);} .breadnav a.active{background:var(--primary);color:#fff;} .breadnav .sep{color:#cbd5e1;font-size:11px;}
.alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500;}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;} .alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.toolbar{display:flex;justify-content:space-between;align-items:center;background:#fff;padding:14px 20px;border-radius:12px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);gap:10px;flex-wrap:wrap;}
.toolbar-l{display:flex;align-items:center;gap:10px;} .toolbar h2{font-size:16px;font-weight:700;}
.badge-count{background:#eff6ff;color:var(--primary);font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;}
.btn{padding:9px 16px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;transition:all .2s;white-space:nowrap;}
.btn-primary{background:var(--primary);color:#fff;} .btn-primary:hover{background:var(--primary-d);}
.btn-sm{padding:6px 11px;font-size:12px;border-radius:6px;}
.btn-edit{background:#eff6ff;color:var(--primary);border:1px solid #bfdbfe;} .btn-edit:hover{background:var(--primary);color:#fff;}
.btn-del{background:#fef2f2;color:var(--danger);border:1px solid #fecaca;} .btn-del:hover{background:var(--danger);color:#fff;}
.tbl-wrap{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.07);overflow:hidden;}
table{width:100%;border-collapse:collapse;font-size:14px;}
thead{background:linear-gradient(135deg,#1e3a5f,#2563eb);}
th{padding:13px 15px;text-align:left;font-weight:600;color:#fff;white-space:nowrap;}
td{padding:12px 15px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tbody tr:hover{background:#f8faff;} tbody tr:last-child td{border-bottom:none;}
.tbl-footer{padding:13px 20px;background:#f8faff;border-top:1px solid var(--border);font-size:13px;color:var(--muted);display:flex;justify-content:space-between;}
.actions{display:flex;gap:6px;}
.status{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;display:inline-block;}
.s-active{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;} .s-inactive{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.time-chip{background:#eff6ff;color:var(--primary);padding:5px 12px;border-radius:8px;font-weight:700;font-size:14px;display:inline-block;}
.break-chip{background:#fff7ed;color:#c2410c;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:600;}
.shift-name{font-weight:700;font-size:14px;}
</style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="wrap">
<div class="page-hd">
    <div><h1><i class="ui-icon icon-shifts" aria-hidden="true"></i> Quản lý Ca Làm Việc</h1><p>Quản lý các ca làm việc trong công ty</p></div>
    <a href="?page=shifts&action=add" class="btn" style="background:#fff;color:var(--primary);font-weight:700;"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm ca làm việc</a>
</div>
<div class="breadnav">
    <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
    <a href="?page=employees"><i class="ui-icon icon-employees" aria-hidden="true"></i> Nhân viên</a><span class="sep">›</span>
    <a href="?page=shifts" class="active"><i class="ui-icon icon-shifts" aria-hidden="true"></i> Ca làm việc</a><span class="sep">›</span>
    <a href="?page=schedules"><i class="ui-icon icon-schedules" aria-hidden="true"></i> Lịch làm việc</a><span class="sep">›</span>
    <a href="?page=part-timers"><i class="ui-icon icon-part-time" aria-hidden="true"></i> Part-time</a>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(', ',$errors)) ?></div><?php endif; ?>
<div class="toolbar">
    <div class="toolbar-l"><h2>Danh sách ca làm việc</h2><span class="badge-count"><?= count($shifts) ?></span></div>
    <form method="GET" class="toolbar-search">
        <input type="hidden" name="page" value="shifts">
        <div class="search-wrap"><span class="s-icon icon-search" aria-hidden="true"></span><input type="text" name="search" placeholder="Tim ten ca, gio, trang thai..." value="<?= htmlspecialchars($search) ?>"></div>
        <button type="submit" class="btn btn-primary">Tim</button>
        <?php if ($search): ?><a href="?page=shifts" class="btn btn-clear" aria-label="Xoa tim kiem">x</a><?php endif; ?>
    </form>
    <div class="toolbar-actions"><a href="?page=shifts&action=add" class="btn btn-primary"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm ca làm việc</a></div>
</div>
<div class="tbl-wrap">
<?php if (count($shifts) > 0): ?>
<table>
<thead><tr><th>#</th><th>Tên ca</th><th>Giờ bắt đầu</th><th>Giờ kết thúc</th><th>Nghỉ giữa ca</th><th>Mô tả</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
<tbody>
<?php foreach ($shifts as $i => $s): ?>
<tr>
<td style="color:#94a3b8;font-size:12px;"><?= $i+1 ?></td>
<td><span class="shift-name"><?= htmlspecialchars($s['shift_name']) ?></span></td>
<td><span class="time-chip"><?= substr($s['start_time'],0,5) ?></span></td>
<td><span class="time-chip"><?= substr($s['end_time'],0,5) ?></span></td>
<td><span class="break-chip"><?= $s['break_time'] ?> phút</span></td>
<td style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($s['description']??'-') ?></td>
<td><span class="status <?= strtolower($s['status'])==='active'?'s-active':'s-inactive' ?>"><?= $s['status']==='Active'?'● Active':'● Inactive' ?></span></td>
<td><div class="actions">
    <a href="?page=shifts&action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-edit"><i class="ui-icon icon-edit" aria-hidden="true"></i> Sửa</a>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa ca này?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>">
        <button type="submit" class="btn btn-sm btn-del" aria-label="Xóa"><i class="ui-icon icon-delete" aria-hidden="true"></i></button>
    </form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<div class="tbl-footer"><span>Tổng <?= count($shifts) ?> ca làm việc<?= $search ? ' - Tim: "' . htmlspecialchars($search) . '"' : '' ?></span><span><i class="ui-icon icon-calendar" aria-hidden="true"></i> <?= date('d/m/Y H:i') ?></span></div>
<?php else: ?>
<div style="text-align:center;padding:60px 20px;color:var(--muted);">
    <div style="font-size:48px;margin-bottom:12px;"><i class="ui-icon icon-shifts" aria-hidden="true"></i></div>
    <h3 style="font-size:18px;font-weight:600;margin-bottom:8px;"><?= $search ? 'Khong tim thay ca lam viec' : 'Chưa có ca làm việc nào' ?></h3>
    <?php if (!$search): ?><a href="?page=shifts&action=add" class="btn btn-primary"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm ca làm việc</a><?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
</body>
</html>


