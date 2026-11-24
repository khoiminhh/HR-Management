<?php
require_once '../src/controllers/ContractController.php';
$controller = new ContractController($pdo);
$message = ''; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $result = $controller->delete($_POST['id'] ?? null);
    if ($result['success']) $message = $result['message']; else $errors[] = $result['message'];
}
$contracts = $controller->index();
$search = $_GET['search'] ?? '';
if ($search) $contracts = array_filter($contracts, fn($c) =>
    stripos($c['full_name'], $search) !== false || stripos($c['contract_number'], $search) !== false ||
    stripos($c['contract_type'], $search) !== false);
$contracts = array_values($contracts);
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quản lý Hợp đồng</title>
<style>
:root{--primary:#2563eb;--primary-d:#1d4ed8;--success:#16a34a;--danger:#dc2626;--bg:#f1f5f9;--card:#fff;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
.wrap{max-width:1300px;margin:0 auto;padding:28px 24px;}
.page-hd{background:linear-gradient(135deg,#1e3a5f,#2563eb);border-radius:14px;padding:26px 32px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:0 4px 20px rgba(37,99,235,.25);}
.page-hd h1{color:#fff;font-size:24px;font-weight:800;} .page-hd p{color:#93c5fd;font-size:13px;margin-top:4px;}
.breadnav{display:flex;align-items:center;gap:6px;background:#fff;padding:11px 18px;border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-wrap:wrap;}
.breadnav a{color:var(--muted);text-decoration:none;font-size:13px;padding:4px 10px;border-radius:6px;transition:all .2s;font-weight:500;}
.breadnav a:hover{background:var(--bg);color:var(--primary);} .breadnav a.active{background:var(--primary);color:#fff;} .breadnav .sep{color:#cbd5e1;font-size:11px;}
.alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500;}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.toolbar{display:flex;justify-content:space-between;align-items:center;background:#fff;padding:14px 20px;border-radius:12px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);gap:10px;flex-wrap:wrap;}
.toolbar-l{display:flex;align-items:center;gap:10px;} .toolbar h2{font-size:16px;font-weight:700;}
.badge-count{background:#eff6ff;color:var(--primary);font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;}
.search-wrap{position:relative;} .search-wrap input{padding:9px 14px 9px 36px;border:1px solid var(--border);border-radius:8px;font-size:14px;width:240px;transition:border-color .2s;}
.search-wrap input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1);}
.s-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;}
.btn{padding:9px 16px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;transition:all .2s;white-space:nowrap;}
.btn-primary{background:var(--primary);color:#fff;} .btn-primary:hover{background:var(--primary-d);box-shadow:0 4px 12px rgba(37,99,235,.3);}
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
.empty{text-align:center;padding:60px 20px;color:var(--muted);}
.empty .ei{font-size:48px;margin-bottom:12px;} .empty h3{font-size:18px;font-weight:600;color:var(--text);margin-bottom:8px;}
.actions{display:flex;gap:6px;}
.status{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;display:inline-block;}
.s-active{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.s-expired{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.s-terminated{background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1;}
.contract-no{font-weight:700;color:var(--purple,#7c3aed);font-size:12px;background:#faf5ff;padding:3px 8px;border-radius:6px;}
.type-tag{background:#fff7ed;color:#c2410c;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:600;}
.money{text-align:right;font-weight:700;color:var(--primary);}
</style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="wrap">
<div class="page-hd">
    <div><h1><i class="ui-icon icon-contracts" aria-hidden="true"></i> Quản lý Hợp đồng</h1><p>Quản lý toàn bộ hợp đồng lao động của nhân viên</p></div>
    <a href="?page=contracts&action=add" class="btn" style="background:#fff;color:var(--primary);font-weight:700;"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm hợp đồng</a>
</div>
<div class="breadnav">
    <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
    <a href="?page=employees"><i class="ui-icon icon-employees" aria-hidden="true"></i> Nhân viên</a><span class="sep">›</span>
    <a href="?page=contracts" class="active"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Hợp đồng</a><span class="sep">›</span>
    <a href="?page=salaries"><i class="ui-icon icon-salaries" aria-hidden="true"></i> Lương</a><span class="sep">›</span>
    <a href="?page=reports"><i class="ui-icon icon-reports" aria-hidden="true"></i> Báo cáo</a>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(', ', $errors)) ?></div><?php endif; ?>
<div class="toolbar">
    <div class="toolbar-l"><h2>Danh sách hợp đồng</h2><span class="badge-count"><?= count($contracts) ?></span></div>
    <form method="GET" class="toolbar-search">
        <input type="hidden" name="page" value="contracts">
        <div class="search-wrap"><span class="s-icon icon-search" aria-hidden="true"></span><input type="text" name="search" placeholder="Tìm tên NV, số HĐ..." value="<?= htmlspecialchars($search) ?>"></div>
        <button type="submit" class="btn btn-primary">Tìm</button>
        <?php if ($search): ?><a href="?page=contracts" class="btn btn-clear" aria-label="Xoa tim kiem">x</a><?php endif; ?>
    </form>
    <div class="toolbar-actions"><a href="?page=contracts&action=add" class="btn btn-primary"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm hợp đồng</a></div>
</div>
<div class="tbl-wrap">
<?php if (count($contracts) > 0): ?>
<table>
<thead><tr><th>#</th><th>Số HĐ</th><th>Nhân viên</th><th>Loại HĐ</th><th>Ngày bắt đầu</th><th>Ngày kết thúc</th><th class="money">Lương</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
<tbody>
<?php foreach ($contracts as $i => $c):
$sc = match(strtolower($c['status'])) { 'active'=>'s-active','expired'=>'s-expired','terminated'=>'s-terminated',default=>'s-active' };
$sl = match($c['status']) { 'Active'=>'● Hiệu lực','Expired'=>'● Hết hạn','Terminated'=>'● Chấm dứt',default=>$c['status'] };
?>
<tr>
<td style="color:#94a3b8;font-size:12px;"><?= $i+1 ?></td>
<td><span class="contract-no"><?= htmlspecialchars($c['contract_number']) ?></span></td>
<td><div style="font-weight:600;"><?= htmlspecialchars($c['full_name']) ?></div><small style="color:var(--muted);">Mã: <?= htmlspecialchars($c['employee_code']) ?></small></td>
<td><span class="type-tag"><?= htmlspecialchars($c['contract_type']) ?></span></td>
<td><?= date('d/m/Y', strtotime($c['start_date'])) ?></td>
<td><?= $c['end_date'] ? date('d/m/Y', strtotime($c['end_date'])) : '<span style="color:var(--muted);font-style:italic;">Vô thời hạn</span>' ?></td>
<td class="money"><?= $c['salary'] ? number_format($c['salary'],0,',','.').' đ' : '-' ?></td>
<td><span class="status <?= $sc ?>"><?= $sl ?></span></td>
<td><div class="actions">
    <a href="?page=contracts&action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-edit"><i class="ui-icon icon-edit" aria-hidden="true"></i> Sửa</a>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa hợp đồng này?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button type="submit" class="btn btn-sm btn-del" aria-label="Xóa"><i class="ui-icon icon-delete" aria-hidden="true"></i></button>
    </form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<div class="tbl-footer"><span>Hiển thị <?= count($contracts) ?> hợp đồng</span><span><i class="ui-icon icon-calendar" aria-hidden="true"></i> <?= date('d/m/Y H:i') ?></span></div>
<?php else: ?>
<div class="empty"><div class="ei"><?= $search?'':'' ?></div><h3><?= $search?'Không tìm thấy':'Chưa có hợp đồng nào' ?></h3>
<?php if(!$search): ?><a href="?page=contracts&action=add" class="btn btn-primary"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm hợp đồng</a><?php endif; ?></div>
<?php endif; ?>
</div>
</div>
</body>
</html>

