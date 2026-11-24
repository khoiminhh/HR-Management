<?php
require_once '../src/models/Schedule.php';
require_once '../src/models/Employee.php';
Auth::requireLogin(); Auth::requireRole(['Admin','HR','Manager']);
$scheduleModel = new Schedule($pdo);
$message = ''; $errors = [];
$currentMonth = $_GET['month'] ?? (int)date('m');
$currentYear  = $_GET['year']  ?? (int)date('Y');
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete') {
    try { $scheduleModel->delete($_POST['id']??null); $message='Xóa lịch thành công!'; }
    catch(Exception $e) { $errors[]='Lỗi: '.$e->getMessage(); }
}
$schedules = $scheduleModel->getAll();
$stats = $scheduleModel->getStatsByMonth($currentMonth, $currentYear);
$statsByStatus = [];
foreach ($stats as $s) $statsByStatus[$s['status']] = $s['count'];
$filtered = array_values(array_filter($schedules, fn($s) =>
    date('m', strtotime($s['schedule_date'])) == $currentMonth &&
    date('Y', strtotime($s['schedule_date'])) == $currentYear
));
?><!DOCTYPE html>
<html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản lý Lịch Làm Việc</title>
<style>
:root{--primary:#2563eb;--primary-d:#1d4ed8;--success:#16a34a;--danger:#dc2626;--warning:#d97706;--bg:#f1f5f9;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
.wrap{max-width:1300px;margin:0 auto;padding:28px 24px;}
.page-hd{background:linear-gradient(135deg,#1e3a5f,#2563eb);border-radius:14px;padding:26px 32px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:0 4px 20px rgba(37,99,235,.25);}
.page-hd h1{color:#fff;font-size:24px;font-weight:800;}.page-hd p{color:#93c5fd;font-size:13px;margin-top:4px;}
.breadnav{display:flex;align-items:center;gap:6px;background:#fff;padding:11px 18px;border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-wrap:wrap;}
.breadnav a{color:var(--muted);text-decoration:none;font-size:13px;padding:4px 10px;border-radius:6px;transition:all .2s;font-weight:500;}
.breadnav a:hover{background:var(--bg);color:var(--primary);}.breadnav a.active{background:var(--primary);color:#fff;}.breadnav .sep{color:#cbd5e1;font-size:11px;}
.alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500;}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:20px;}
.sc{background:#fff;border-radius:12px;padding:18px 14px;box-shadow:0 1px 3px rgba(0,0,0,.07);border-left:4px solid;text-align:center;}
.sc.blue{border-left-color:var(--primary);}.sc.green{border-left-color:var(--success);}.sc.red{border-left-color:var(--danger);}.sc.yellow{border-left-color:var(--warning);}.sc.purple{border-left-color:#7c3aed;}
.sc .val{font-size:26px;font-weight:800;color:var(--text);}.sc .lbl{font-size:12px;color:var(--muted);margin-top:4px;}
.filter-bar{background:#fff;padding:14px 20px;border-radius:12px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;}
.filter-bar h2{font-size:16px;font-weight:700;}
.filter-inner{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.filter-inner select{padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;}
.btn{padding:9px 16px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;transition:all .2s;white-space:nowrap;}
.btn-primary{background:var(--primary);color:#fff;}.btn-primary:hover{background:var(--primary-d);}
.btn-sm{padding:6px 11px;font-size:12px;border-radius:6px;}
.btn-edit{background:#eff6ff;color:var(--primary);border:1px solid #bfdbfe;}.btn-edit:hover{background:var(--primary);color:#fff;}
.btn-del{background:#fef2f2;color:var(--danger);border:1px solid #fecaca;}.btn-del:hover{background:var(--danger);color:#fff;}
.tbl-wrap{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.07);overflow:hidden;}
table{width:100%;border-collapse:collapse;font-size:14px;}
thead{background:linear-gradient(135deg,#1e3a5f,#2563eb);}
th{padding:13px 15px;text-align:left;font-weight:600;color:#fff;white-space:nowrap;}
td{padding:12px 15px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tbody tr:hover{background:#f8faff;}tbody tr:last-child td{border-bottom:none;}
.tbl-footer{padding:13px 20px;background:#f8faff;border-top:1px solid var(--border);font-size:13px;color:var(--muted);display:flex;justify-content:space-between;}
.actions{display:flex;gap:6px;}
.status{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;display:inline-block;}
.s-scheduled{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;}.s-present{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.s-absent{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}.s-leave{background:#fffbeb;color:#d97706;border:1px solid #fde68a;}
.time-chip{background:#eff6ff;color:var(--primary);padding:4px 10px;border-radius:6px;font-weight:700;font-size:12px;white-space:nowrap;}
.emp-code{background:#f0f4ff;color:#4338ca;padding:2px 7px;border-radius:5px;font-size:11px;font-weight:700;}
.empty{text-align:center;padding:60px 20px;color:var(--muted);}
.empty .ei{font-size:48px;margin-bottom:12px;}.empty h3{font-size:18px;font-weight:600;color:var(--text);margin-bottom:8px;}
</style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head><body>
<div class="wrap">
<div class="page-hd">
    <div><h1><i class="ui-icon icon-schedules" aria-hidden="true"></i> Quản lý Lịch Làm Việc</h1><p>Lập lịch và quản lý lịch làm việc cho nhân viên</p></div>
    <a href="?page=schedules&action=add" class="btn" style="background:#fff;color:var(--primary);font-weight:700;"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm lịch</a>
</div>
<div class="breadnav">
    <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
    <a href="?page=employees"><i class="ui-icon icon-employees" aria-hidden="true"></i> Nhân viên</a><span class="sep">›</span>
    <a href="?page=shifts"><i class="ui-icon icon-shifts" aria-hidden="true"></i> Ca làm việc</a><span class="sep">›</span>
    <a href="?page=schedules" class="active"><i class="ui-icon icon-schedules" aria-hidden="true"></i> Lịch làm việc</a><span class="sep">›</span>
    <a href="?page=part-timers"><i class="ui-icon icon-part-time" aria-hidden="true"></i> Part-time</a>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(', ',$errors)) ?></div><?php endif; ?>
<div class="stat-grid">
    <div class="sc blue"><div class="val"><?= $statsByStatus['Scheduled']??0 ?></div><div class="lbl"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Đã lên lịch</div></div>
    <div class="sc green"><div class="val"><?= $statsByStatus['Present']??0 ?></div><div class="lbl">Có mặt</div></div>
    <div class="sc red"><div class="val"><?= $statsByStatus['Absent']??0 ?></div><div class="lbl">Vắng mặt</div></div>
    <div class="sc yellow"><div class="val"><?= $statsByStatus['Leave']??0 ?></div><div class="lbl">Nghỉ phép</div></div>
    <div class="sc purple"><div class="val"><?= count($filtered) ?></div><div class="lbl"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Tổng tháng này</div></div>
</div>
<div class="filter-bar">
    <h2>Lịch tháng <?= $currentMonth ?>/<?= $currentYear ?></h2>
    <div class="filter-inner">
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="page" value="schedules">
            <select name="month"><?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $currentMonth==$m?'selected':'' ?>>Tháng <?= str_pad($m,2,'0',STR_PAD_LEFT) ?></option><?php endfor; ?></select>
            <select name="year"><?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?><option value="<?= $y ?>" <?= $currentYear==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?></select>
            <button type="submit" class="btn btn-primary">Lọc</button>
        </form>
        <a href="?page=schedules&action=add" class="btn btn-primary"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm lịch</a>
    </div>
</div>
<div class="tbl-wrap">
<?php $days=['Sunday'=>'CN','Monday'=>'T2','Tuesday'=>'T3','Wednesday'=>'T4','Thursday'=>'T5','Friday'=>'T6','Saturday'=>'T7']; ?>
<?php if (count($filtered)>0): ?>
<table>
<thead><tr><th>#</th><th>Nhân viên</th><th>Ngày làm việc</th><th>Ca làm việc</th><th>Giờ làm</th><th>Trạng thái</th><th>Ghi chú</th><th>Hành động</th></tr></thead>
<tbody>
<?php foreach ($filtered as $i => $s):
$sc=match(strtolower($s['status'])){  'scheduled'=>'s-scheduled','present'=>'s-present','absent'=>'s-absent','leave'=>'s-leave',default=>'s-scheduled'};
$sl=match($s['status']){'Scheduled'=>'Lên lịch','Present'=>'Có mặt','Absent'=>'Vắng','Leave'=>'Nghỉ phép',default=>$s['status']};
$dn=$days[date('l',strtotime($s['schedule_date']))]??'';
?>
<tr>
<td style="color:#94a3b8;font-size:12px;"><?= $i+1 ?></td>
<td><div style="font-weight:600;"><?= htmlspecialchars($s['full_name']) ?></div><span class="emp-code"><?= htmlspecialchars($s['employee_code']) ?></span></td>
<td><div style="font-weight:600;"><?= date('d/m/Y',strtotime($s['schedule_date'])) ?></div><div style="font-size:11px;color:var(--muted);"><?= $dn ?></div></td>
<td><?= $s['shift_name']?'<strong>'.htmlspecialchars($s['shift_name']).'</strong>':'<span style="color:var(--muted)">-</span>' ?></td>
<td><?= ($s['start_time']&&$s['end_time'])?'<span class="time-chip">'.substr($s['start_time'],0,5).' → '.substr($s['end_time'],0,5).'</span>':'-' ?></td>
<td><span class="status <?= $sc ?>"><?= $sl ?></span></td>
<td style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($s['notes']??'-') ?></td>
<td><div class="actions">
    <a href="?page=schedules&action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-edit"><i class="ui-icon icon-edit" aria-hidden="true"></i></a>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa lịch này?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>">
        <button type="submit" class="btn btn-sm btn-del" aria-label="Xóa"><i class="ui-icon icon-delete" aria-hidden="true"></i></button>
    </form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<div class="tbl-footer"><span>Tháng <?= $currentMonth ?>/<?= $currentYear ?> — <?= count($filtered) ?> lịch</span><span><i class="ui-icon icon-calendar" aria-hidden="true"></i> <?= date('d/m/Y H:i') ?></span></div>
<?php else: ?>
<div class="empty"><div class="ei"><i class="ui-icon icon-calendar" aria-hidden="true"></i></div><h3>Chưa có lịch trong tháng <?= $currentMonth ?>/<?= $currentYear ?></h3><a href="?page=schedules&action=add" class="btn btn-primary" style="margin-top:12px;"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm lịch</a></div>
<?php endif; ?>
</div>
</div></body></html>


