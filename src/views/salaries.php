<?php
require_once '../src/controllers/SalaryController.php';
require_once '../src/models/Salary.php';
$controller = new SalaryController($pdo);
$salaryModel = new Salary($pdo);
$message = ''; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'delete') {
    $result = $controller->delete($_POST['id']??null);
    if ($result['success']) $message = $result['message']; else $errors[] = $result['message'];
}
$salaries = $controller->index();
$filterMonth = $_GET['month'] ?? '';
$filterYear  = $_GET['year']  ?? '';
if ($filterMonth) $salaries = array_filter($salaries, fn($s) => $s['month'] == $filterMonth);
if ($filterYear)  $salaries = array_filter($salaries, fn($s) => $s['year']  == $filterYear);
$salaries = array_values($salaries);
$totalNet = array_sum(array_column($salaries, 'net_salary'));
$totalBase = array_sum(array_column($salaries, 'base_salary'));
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quản lý Lương</title>
<style>
:root{--primary:#2563eb;--primary-d:#1d4ed8;--success:#16a34a;--danger:#dc2626;--warning:#d97706;--bg:#f1f5f9;--card:#fff;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
.wrap{max-width:1400px;margin:0 auto;padding:28px 24px;}
.page-hd{background:linear-gradient(135deg,#1e3a5f,#2563eb);border-radius:14px;padding:26px 32px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:0 4px 20px rgba(37,99,235,.25);}
.page-hd h1{color:#fff;font-size:24px;font-weight:800;} .page-hd p{color:#93c5fd;font-size:13px;margin-top:4px;}
.breadnav{display:flex;align-items:center;gap:6px;background:#fff;padding:11px 18px;border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-wrap:wrap;}
.breadnav a{color:var(--muted);text-decoration:none;font-size:13px;padding:4px 10px;border-radius:6px;transition:all .2s;font-weight:500;}
.breadnav a:hover{background:var(--bg);color:var(--primary);} .breadnav a.active{background:var(--primary);color:#fff;} .breadnav .sep{color:#cbd5e1;font-size:11px;}
.alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500;}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;} .alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.summary-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px;}
.sc{background:#fff;border-radius:12px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);border-left:4px solid #ccc;}
.sc.blue{border-left-color:var(--primary);} .sc.green{border-left-color:var(--success);} .sc.orange{border-left-color:var(--warning);} .sc.purple{border-left-color:#7c3aed;}
.sc .val{font-size:20px;font-weight:800;color:var(--text);} .sc .lbl{font-size:12px;color:var(--muted);margin-top:4px;}
.filter-bar{background:#fff;padding:14px 20px;border-radius:12px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.filter-bar label{font-size:13px;font-weight:600;color:var(--text);}
.filter-bar select{padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;}
.toolbar{display:flex;justify-content:space-between;align-items:center;background:#fff;padding:14px 20px;border-radius:12px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);gap:10px;flex-wrap:wrap;}
.toolbar-l{display:flex;align-items:center;gap:10px;} .toolbar h2{font-size:16px;font-weight:700;}
.badge-count{background:#eff6ff;color:var(--primary);font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;}
.btn{padding:9px 16px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;transition:all .2s;white-space:nowrap;}
.btn-primary{background:var(--primary);color:#fff;} .btn-primary:hover{background:var(--primary-d);}
.btn-sm{padding:6px 11px;font-size:12px;border-radius:6px;}
.btn-edit{background:#eff6ff;color:var(--primary);border:1px solid #bfdbfe;} .btn-edit:hover{background:var(--primary);color:#fff;}
.btn-del{background:#fef2f2;color:var(--danger);border:1px solid #fecaca;} .btn-del:hover{background:var(--danger);color:#fff;}
.btn-csv{background:#f0fdf4;color:var(--success);border:1px solid #bbf7d0;} .btn-csv:hover{background:var(--success);color:#fff;}
.tbl-wrap{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.07);overflow:hidden;}
table{width:100%;border-collapse:collapse;font-size:14px;}
thead{background:linear-gradient(135deg,#1e3a5f,#2563eb);}
th{padding:13px 15px;text-align:left;font-weight:600;color:#fff;white-space:nowrap;}
td{padding:12px 15px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tbody tr:hover{background:#f8faff;} tbody tr:last-child td{border-bottom:none;}
.tbl-footer{padding:13px 20px;background:#f8faff;border-top:1px solid var(--border);font-size:13px;color:var(--muted);display:flex;justify-content:space-between;}
.money{text-align:right;font-weight:600;} .money-net{text-align:right;font-weight:800;color:var(--primary);font-size:15px;}
.money-red{color:var(--danger);} .money-green{color:var(--success);}
.month-tag{background:#eff6ff;color:var(--primary);padding:3px 8px;border-radius:6px;font-size:12px;font-weight:700;}
.actions{display:flex;gap:6px;}
.empty{text-align:center;padding:60px 20px;color:var(--muted);}
.empty .ei{font-size:48px;margin-bottom:12px;} .empty h3{font-size:18px;font-weight:600;color:var(--text);margin-bottom:8px;}
tfoot td{background:#f0f4ff;font-weight:700;padding:12px 15px;}
</style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="wrap">
<div class="page-hd">
    <div><h1><i class="ui-icon icon-salaries" aria-hidden="true"></i> Quản lý Lương</h1><p>Quản lý bảng lương và các khoản thu chi</p></div>
    <div style="display:flex;gap:10px;">
        <a href="../public/export-salaries.php" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);"> Xuất CSV</a>
        <a href="?page=salaries&action=add" class="btn" style="background:#fff;color:var(--primary);font-weight:700;"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm bảng lương</a>
    </div>
</div>
<div class="breadnav">
    <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
    <a href="?page=employees"><i class="ui-icon icon-employees" aria-hidden="true"></i> Nhân viên</a><span class="sep">›</span>
    <a href="?page=contracts"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Hợp đồng</a><span class="sep">›</span>
    <a href="?page=salaries" class="active"><i class="ui-icon icon-salaries" aria-hidden="true"></i> Lương</a><span class="sep">›</span>
    <a href="?page=salary-stats"><i class="ui-icon icon-salary-stats" aria-hidden="true"></i> Thống kê lương</a>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(', ',$errors)) ?></div><?php endif; ?>

<!-- Summary cards -->
<div class="summary-cards">
    <div class="sc blue"><div class="val"><?= count($salaries) ?></div><div class="lbl">Số bảng lương</div></div>
    <div class="sc green"><div class="val" style="font-size:15px;"><?= number_format($totalBase,0,',','.') ?>đ</div><div class="lbl">Tổng lương cơ bản</div></div>
    <div class="sc purple"><div class="val" style="font-size:15px;"><?= number_format($totalNet,0,',','.') ?>đ</div><div class="lbl">Tổng lương thực lĩnh</div></div>
    <div class="sc orange"><div class="val"><?= $filterMonth ? "T$filterMonth".($filterYear?"/$filterYear":'') : 'Tất cả' ?></div><div class="lbl">Kỳ đang xem</div></div>
</div>

<!-- Filter -->
<form method="GET" class="filter-bar">
    <input type="hidden" name="page" value="salaries">
    <label>Tháng:</label>
    <select name="month">
        <option value="">-- Tất cả --</option>
        <?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $filterMonth==$m?'selected':'' ?>>Tháng <?= $m ?></option><?php endfor; ?>
    </select>
    <label>Năm:</label>
    <select name="year">
        <option value="">-- Tất cả --</option>
        <?php for($y=date('Y')-2;$y<=date('Y');$y++): ?><option value="<?= $y ?>" <?= $filterYear==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
    </select>
    <button type="submit" class="btn btn-primary">Lọc</button>
    <?php if($filterMonth||$filterYear): ?><a href="?page=salaries" class="btn" style="background:#f1f5f9;color:var(--muted);">✕ Xóa lọc</a><?php endif; ?>
    <div style="margin-left:auto;display:flex;gap:8px;">
        <a href="?page=salary-stats" class="btn" style="background:#eff6ff;color:var(--primary);border:1px solid #bfdbfe;"><i class="ui-icon icon-salary-stats" aria-hidden="true"></i> Thống kê lương</a>
        <a href="?page=salaries&action=add" class="btn btn-primary"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm bảng lương</a>
    </div>
</form>

<div class="tbl-wrap">
<?php if (count($salaries) > 0): ?>
<table>
<thead><tr><th>#</th><th>Nhân viên</th><th>Tháng/Năm</th><th class="money">Lương CB</th><th class="money">Phụ cấp</th><th class="money">Khấu trừ</th><th class="money">Bảo hiểm</th><th class="money">Thực lĩnh</th><th>Ngày TT</th><th>Hành động</th></tr></thead>
<tbody>
<?php foreach ($salaries as $i => $s): ?>
<tr>
<td style="color:#94a3b8;font-size:12px;"><?= $i+1 ?></td>
<td><div style="font-weight:600;"><?= htmlspecialchars($s['full_name']) ?></div><small style="color:var(--muted);"><?= htmlspecialchars($s['employee_code']) ?></small></td>
<td><span class="month-tag"><?= str_pad($s['month'],2,'0',STR_PAD_LEFT) ?>/<?= $s['year'] ?></span></td>
<td class="money"><?= number_format($s['base_salary'],0,',','.') ?>đ</td>
<td class="money money-green">+<?= number_format($s['allowances'],0,',','.') ?>đ</td>
<td class="money money-red">-<?= number_format($s['deductions'],0,',','.') ?>đ</td>
<td class="money money-red">-<?= number_format($s['insurance'],0,',','.') ?>đ</td>
<td class="money-net"><?= number_format($s['net_salary'],0,',','.') ?>đ</td>
<td><?= $s['payment_date'] ? date('d/m/Y',strtotime($s['payment_date'])) : '-' ?></td>
<td><div class="actions">
    <a href="?page=salaries&action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-edit"><i class="ui-icon icon-edit" aria-hidden="true"></i></a>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa bảng lương này?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>">
        <button type="submit" class="btn btn-sm btn-del" aria-label="Xóa"><i class="ui-icon icon-delete" aria-hidden="true"></i></button>
    </form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr>
    <td colspan="3">Tổng cộng (<?= count($salaries) ?> nhân viên)</td>
    <td class="money"><?= number_format($totalBase,0,',','.') ?>đ</td>
    <td class="money money-green">+<?= number_format(array_sum(array_column($salaries,'allowances')),0,',','.') ?>đ</td>
    <td class="money money-red">-<?= number_format(array_sum(array_column($salaries,'deductions')),0,',','.') ?>đ</td>
    <td class="money money-red">-<?= number_format(array_sum(array_column($salaries,'insurance')),0,',','.') ?>đ</td>
    <td class="money-net"><?= number_format($totalNet,0,',','.') ?>đ</td>
    <td colspan="2"></td>
</tr>
</tfoot>
</table>
<div class="tbl-footer"><span>Hiển thị <?= count($salaries) ?> bảng lương</span><span><i class="ui-icon icon-calendar" aria-hidden="true"></i> <?= date('d/m/Y H:i') ?></span></div>
<?php else: ?>
<div class="empty"><div class="ei"></div><h3>Chưa có bảng lương</h3><p style="margin-bottom:20px;">Chưa có dữ liệu lương<?= ($filterMonth||$filterYear)?' trong kỳ này':'' ?></p><a href="?page=salaries&action=add" class="btn btn-primary"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm bảng lương</a></div>
<?php endif; ?>
</div>
</div>
</body>
</html>

