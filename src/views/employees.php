<?php
/**
 * ========================================
 * View: Employee List
 * ========================================
 */

require_once '../src/controllers/EmployeeController.php';

// Khởi tạo Controller (Biến $pdo nên được include từ file cấu hình core hoặc index.php trước đó)
$controller = new EmployeeController($pdo);

$message = '';
$errors = [];

/**
 * Xử lý hành động Xóa nhân viên (POST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $result = $controller->delete($_POST['id'] ?? null);

        if ($result['success']) {
            $message = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

/**
 * Lấy danh sách gốc toàn bộ nhân viên
 */
$employees = $controller->index() ?? [];

/**
 * Xử lý bộ lọc Tìm kiếm (In-memory Search)
 */
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $employees = array_filter(
        $employees,
        function ($e) use ($search) {
            return
                stripos($e['full_name'] ?? '', $search) !== false ||
                stripos($e['employee_code'] ?? '', $search) !== false ||
                stripos($e['department'] ?? '', $search) !== false ||
                stripos($e['email'] ?? '', $search) !== false;
        }
    );
}

// Reset lại chỉ mục mảng sau khi filter
$empList = array_values($employees);

/**
 * Helper hiển thị dữ liệu an toàn (XSS Protection)
 */
function e($value, $default = '-')
{
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Nhân viên</title>
    <style>
        :root { 
            --primary:#2563eb; --primary-d:#1d4ed8; --success:#16a34a; --danger:#dc2626;
            --bg:#f1f5f9; --card:#fff; --text:#1e293b; --muted:#64748b; --border:#e2e8f0; 
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', system-ui, sans-serif; background:var(--bg); color:var(--text); }
        .wrap { max-width:1300px; margin:0 auto; padding:28px 24px; }

        /* Page header */
        .page-hd { 
            background:linear-gradient(135deg,#1e3a5f,#2563eb); border-radius:14px; padding:28px 32px;
            margin-bottom:22px; display:flex; justify-content:space-between; align-items:center;
            flex-wrap:wrap; gap:12px; box-shadow:0 4px 20px rgba(37,99,235,.25); 
        }
        .page-hd h1 { color:#fff; font-size:26px; font-weight:800; }
        .page-hd p { color:#93c5fd; font-size:14px; margin-top:4px; }

        /* Breadnav */
        .breadnav { 
            display:flex; align-items:center; gap:6px; background:#fff; padding:11px 18px;
            border-radius:10px; margin-bottom:18px; box-shadow:0 1px 3px rgba(0,0,0,.07); flex-wrap:wrap; 
        }
        .breadnav a { 
            color:var(--muted); text-decoration:none; font-size:13px; font-weight:500;
            padding:4px 10px; border-radius:6px; transition:all .2s; 
        }
        .breadnav a:hover { background:var(--bg); color:var(--primary); }
        .breadnav a.active { background:var(--primary); color:#fff; }
        .breadnav .sep { color:#cbd5e1; font-size:11px; }

        /* Alert */
        .alert { padding:13px 18px; border-radius:10px; margin-bottom:18px; font-size:14px; font-weight:500; }
        .alert-success { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .alert-error { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }

        /* Toolbar */
        .toolbar { 
            display:flex; justify-content:space-between; align-items:center; background:#fff;
            padding:14px 20px; border-radius:12px; margin-bottom:18px;
            box-shadow:0 1px 3px rgba(0,0,0,.07); gap:10px; flex-wrap:wrap; 
        }
        .toolbar-l { display:flex; align-items:center; gap:10px; }
        .toolbar h2 { font-size:16px; font-weight:700; }
        .badge-count { 
            background:#eff6ff; color:var(--primary); font-size:12px; font-weight:700;
            padding:3px 10px; border-radius:20px; 
        }
        .search-wrap { position:relative; }
        .search-wrap input { 
            padding:9px 14px 9px 36px; border:1px solid var(--border); border-radius:8px;
            font-size:14px; width:250px; transition:border-color .2s; 
        }
        .search-wrap input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.1); }
        .s-icon { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:14px; }
        .toolbar-search { display:flex; align-items:center; justify-content:center; gap:10px; flex:1 1 420px; min-width:280px; }
        .toolbar-actions { display:flex; align-items:center; justify-content:flex-end; gap:10px; flex-wrap:wrap; }
        .toolbar-search .search-wrap { flex:0 1 320px; min-width:260px; }
        .toolbar-search .search-wrap input { width:100%; min-height:44px; padding:10px 14px 10px 40px; border-radius:10px; font-weight:600; }
        .toolbar-search .s-icon { left:13px; font-size:15px; pointer-events:none; }
        .btn-clear { width:44px; min-width:44px; min-height:44px; justify-content:center; padding:0; background:#f1f5f9; color:var(--muted); border:1px solid var(--border); box-shadow:none; }

        /* Buttons */
        .btn { 
            padding:9px 16px; border:none; border-radius:8px; cursor:pointer; text-decoration:none;
            display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600;
            transition:all .2s; white-space:nowrap; 
        }
        .btn-primary { background:var(--primary); color:#fff; }
        .btn-primary:hover { background:var(--primary-d); box-shadow:0 4px 12px rgba(37,99,235,.3); }
        .btn-success { background:var(--success); color:#fff; }
        .btn-success:hover { background:#15803d; }
        .btn-sm { padding:6px 11px; font-size:12px; border-radius:6px; }
        .btn-edit { background:#eff6ff; color:var(--primary); border:1px solid #bfdbfe; }
        .btn-edit:hover { background:var(--primary); color:#fff; }
        .btn-del { background:#fef2f2; color:var(--danger); border:1px solid #fecaca; }
        .btn-del:hover { background:var(--danger); color:#fff; }
        .btn-csv { background:#f0fdf4; color:var(--success); border:1px solid #bbf7d0; }
        .btn-csv:hover { background:var(--success); color:#fff; }

        /* Table */
        .tbl-wrap { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(0,0,0,.07); overflow:hidden; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        thead { background:linear-gradient(135deg,#1e3a5f,#2563eb); }
        th { padding:14px 15px; text-align:left; font-weight:600; color:#fff; white-space:nowrap; }
        td { padding:13px 15px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
        tbody tr { transition:background .15s; }
        tbody tr:hover { background:#f8faff; }
        tbody tr:last-child td { border-bottom:none; }

        .emp-code { 
            font-weight:700; color:var(--primary); font-size:12px; background:#eff6ff;
            padding:3px 8px; border-radius:6px; 
        }
        .emp-name-cell { display:flex; align-items:center; gap:10px; }
        .avatar { 
            width:34px; height:34px; border-radius:50%; display:flex; align-items:center;
            justify-content:center; font-weight:700; font-size:13px; color:#fff; flex-shrink:0; 
        }
        .av0 { background:linear-gradient(135deg,#2563eb,#60a5fa); }
        .av1 { background:linear-gradient(135deg,#16a34a,#4ade80); }
        .av2 { background:linear-gradient(135deg,#7c3aed,#a78bfa); }
        .av3 { background:linear-gradient(135deg,#d97706,#fbbf24); }
        .av4 { background:linear-gradient(135deg,#0d9488,#2dd4bf); }
        .av5 { background:linear-gradient(135deg,#db2777,#f472b6); }

        .dept-tag { background:#f0f4ff; color:#4338ca; padding:3px 9px; border-radius:6px; font-size:12px; font-weight:600; }
        .status { padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; display:inline-block; }
        .s-active { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .s-inactive { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .s-leave { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }

        .actions { display:flex; gap:6px; }
        .tbl-footer { 
            padding:13px 20px; background:#f8faff; border-top:1px solid var(--border);
            font-size:13px; color:var(--muted); display:flex; justify-content:space-between; 
        }

        .empty { text-align:center; padding:60px 20px; color:var(--muted); }
        .empty .ei { font-size:48px; margin-bottom:12px; }
        .empty h3 { font-size:18px; font-weight:600; color:var(--text); margin-bottom:8px; }
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="wrap">

    <div class="page-hd">
        <div>
            <h1><i class="ui-icon icon-employees" aria-hidden="true"></i> Quản lý Nhân viên</h1>
            <p>Quản lý thông tin toàn bộ nhân viên công ty</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="../public/export-employees.php" class="btn" style="background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3);">Xuất CSV</a>
            <a href="?page=employees&action=add" class="btn" style="background:#fff; color:var(--primary); font-weight:700;"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm nhân viên</a>
        </div>
    </div>

    <div class="breadnav">
        <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
        <a href="?page=employees" class="active"><i class="ui-icon icon-employees" aria-hidden="true"></i> Nhân viên</a><span class="sep">›</span>
        <a href="?page=contracts"><i class="ui-icon icon-contracts" aria-hidden="true"></i> Hợp đồng</a><span class="sep">›</span>
        <a href="?page=salaries"><i class="ui-icon icon-salaries" aria-hidden="true"></i> Lương</a><span class="sep">›</span>
        <a href="?page=salary-stats"><i class="ui-icon icon-salary-stats" aria-hidden="true"></i> Thống kê lương</a><span class="sep">›</span>
        <a href="?page=reports"><i class="ui-icon icon-reports" aria-hidden="true"></i> Báo cáo</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

    <div class="toolbar">
        <div class="toolbar-l">
            <h2>Danh sách nhân viên</h2>
            <span class="badge-count"><?= count($empList) ?> người</span>
        </div>
        
        <form method="GET" class="toolbar-search">
            <input type="hidden" name="page" value="employees">
            <div class="search-wrap">
                <span class="s-icon icon-search" aria-hidden="true"></span>
                <input type="text" id="employee-search" name="search" placeholder="Tìm tên, mã NV, phòng ban..." value="<?= e($search) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Tìm</button>
            <?php if ($search): ?><a href="?page=employees" class="btn btn-clear" aria-label="Xoa tim kiem">x</a><?php endif; ?>
        </form>

        <div class="toolbar-actions">
            <a href="../public/export-employees.php" class="btn btn-csv">CSV</a>
            <a href="?page=employees&action=add" class="btn btn-primary"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm nhân viên</a>
        </div>
    </div>

    <div class="tbl-wrap">
        <?php if (count($empList) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Mã NV</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Phòng ban</th>
                    <th>Chức vụ</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($empList as $i => $emp): ?>
                <?php
                $empId    = isset($emp['id']) ? (int)$emp['id'] : 0;
                $initials = mb_strtoupper(mb_substr($emp['full_name'] ?? 'U', 0, 1, 'UTF-8'));
                
                // Đồng bộ chữ thường để tránh lỗi bóc tách 'Active' / 'active' từ database
                $statusLower = strtolower($emp['status'] ?? 'active');
                
                $sc = match($statusLower) { 
                    'active'   => 's-active', 
                    'inactive' => 's-inactive', 
                    default    => 's-leave' 
                };
                
                $sl = match($statusLower) { 
                    'active'    => '● Active', 
                    'inactive'  => '● Inactive', 
                    'on leave', 'leave' => '● Nghỉ phép', 
                    default     => '● ' . e($emp['status']) 
                };
                ?>
                <tr>
                    <td style="color:#94a3b8; font-size:12px;"><?= $i+1 ?></td>
                    <td><span class="emp-code"><?= e($emp['employee_code']) ?></span></td>
                    <td>
                        <div class="emp-name-cell">
                            <div class="avatar av<?= $i%6 ?>"><?= $initials ?></div>
                            <span style="font-weight:600;"><?= e($emp['full_name']) ?></span>
                        </div>
                    </td>
                    <td style="color:var(--muted);"><?= e($emp['email']) ?></td>
                    <td><?= e($emp['phone']) ?></td>
                    <td>
                        <?php if (!empty($emp['department'])): ?>
                            <span class="dept-tag"><?= e($emp['department']) ?></span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--muted); font-size:13px;"><?= e($emp['position']) ?></td>
                    <td><span class="status <?= $sc ?>"><?= $sl ?></span></td>
                    <td>
                        <div class="actions">
                            <a href="?page=my-pay-and-attendance&employee_id=<?= $empId ?>" class="btn btn-sm btn-edit">Xem</a>
                            <a href="?page=employees&action=edit&id=<?= $empId ?>" class="btn btn-sm btn-edit"><i class="ui-icon icon-edit" aria-hidden="true"></i> Sửa</a>
                            
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa nhân viên <?= addslashes($emp['full_name'] ?? '') ?>?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $empId ?>">
                                <button type="submit" class="btn btn-sm btn-del"><i class="ui-icon icon-delete" aria-hidden="true"></i> Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="tbl-footer">
            <span>Hiển thị <?= count($empList) ?> nhân viên<?= $search ? " · Tìm: \"" . e($search) . "\"" : '' ?></span>
            <span><i class="ui-icon icon-calendar" aria-hidden="true"></i> <?= date('d/m/Y H:i') ?></span>
        </div>
        
        <?php else: ?>
        <div class="empty">
            <div class="ei">👥</div>
            <h3><?= $search ? 'Không tìm thấy kết quả' : 'Chưa có nhân viên nào' ?></h3>
            <p style="margin-bottom:20px;"><?= $search ? "Không có nhân viên khớp với \"" . e($search) . "\"" : 'Bắt đầu bằng cách thêm nhân viên đầu tiên' ?></p>
            <?php if (!$search): ?><a href="?page=employees&action=add" class="btn btn-primary"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm nhân viên</a><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php if (($_GET['focus'] ?? '') === 'search'): ?>
<script>
    window.addEventListener('load', function () {
        const searchInput = document.getElementById('employee-search');
        if (searchInput) {
            searchInput.focus();
            searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>
<?php endif; ?>
</body>
</html>
